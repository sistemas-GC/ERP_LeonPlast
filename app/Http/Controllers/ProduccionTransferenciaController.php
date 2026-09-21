<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransferenciaAlmacen;
use App\Models\TransferenciaAlmacenDetalle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProduccionTransferenciaController extends Controller
{
    public function index(Request $request)
    {
        $fecha_desde = $request->input('fecha_desde', now()->startOfMonth()->toDateString());
        $fecha_hasta = $request->input('fecha_hasta', now()->endOfMonth()->toDateString());

        $query = TransferenciaAlmacen::with(['almacenOrigen', 'almacenDestino', 'usuario'])
            ->whereDate('fecha_transferencia', '>=', $fecha_desde)
            ->whereDate('fecha_transferencia', '<=', $fecha_hasta)
            ->orderBy('fecha_transferencia', 'desc')
            ->orderBy('id_transferencia', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_transferencia', 'like', "%$search%")
                  ->orWhere('observaciones', 'like', "%$search%");
            });
        }

        $transferencias = $query->paginate(15)->appends($request->all());

        return view('produccion.transferencias.index', compact('transferencias', 'fecha_desde', 'fecha_hasta'));
    }

    public function create()
    {
        $almacenes = DB::table('almacen')->where('activo', 1)->orderBy('descripcion')->get();
        return view('produccion.transferencias.create', compact('almacenes'));
    }

    /**
     * Busca y retorna productos con stock disponible en el almacén de origen,
     * TOTALIZANDO el stock de todos sus lotes individuales.
     */
    public function buscarProductos(Request $request)
    {
        $almacen_origen = $request->codigo_almacen_origen;
        $search = trim($request->search ?? '');

        if (!$almacen_origen) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $query = DB::table('inventario as i')
            ->join('producto as p', 'i.codigo_producto', '=', 'p.codigo')
            ->leftJoin('tipo_producto as tp', 'p.codigo_tipo_producto', '=', 'tp.codigo')
            ->leftJoin('unidad_medida as u', 'p.codigo_unidad_medida', '=', 'u.codigo')
            ->where('i.codigo_almacen', $almacen_origen)
            ->where('i.stock_actual', '>', 0)
            ->where(function($q) {
                $q->where('i.estado', 1)->orWhereNull('i.estado');
            });

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('p.descripcion', 'like', "%$search%")
                  ->orWhere('p.codigo', 'like', "%$search%");
            });
        }

        $productos = $query->select(
            'i.codigo_producto',
            'p.descripcion as producto_descripcion',
            'p.codigo_tipo_producto',
            'tp.descripcion as tipo_descripcion',
            'p.codigo_unidad_medida',
            'u.descripcion as unidad_descripcion',
            DB::raw('SUM(i.stock_actual) as stock_total_disponible'),
            DB::raw('COUNT(i.id_inventario) as total_lotes')
        )
        ->groupBy(
            'i.codigo_producto',
            'p.descripcion',
            'p.codigo_tipo_producto',
            'tp.descripcion',
            'p.codigo_unidad_medida',
            'u.descripcion'
        )
        ->havingRaw('SUM(i.stock_actual) > 0')
        ->orderBy('p.descripcion')
        ->limit(60)
        ->get();

        return response()->json(['success' => true, 'data' => $productos]);
    }

    /**
     * Procesa la transferencia consumiendo los lotes de forma FIFO (First In, First Out)
     */
    public function store(Request $request)
    {
        $request->validate([
            'fecha_transferencia'    => 'required|date',
            'codigo_almacen_origen'  => 'required|string|exists:almacen,codigo_almacen',
            'codigo_almacen_destino' => 'required|string|exists:almacen,codigo_almacen|different:codigo_almacen_origen',
            'observaciones'          => 'nullable|string|max:500',
            'items'                  => 'required|array|min:1',
            'items.*.codigo_producto'=> 'required|string|exists:producto,codigo',
            'items.*.cantidad'       => 'required|numeric|min:0.0001',
        ], [
            'codigo_almacen_destino.different' => 'El almacén de destino no puede ser igual al de origen.',
            'items.required' => 'Debe agregar al menos un producto a transferir.',
            'items.*.cantidad.min' => 'La cantidad a transferir debe ser mayor a cero.',
        ]);

        try {
            DB::beginTransaction();

            $origen = $request->codigo_almacen_origen;
            $destino = $request->codigo_almacen_destino;
            $usuario_id = Auth::id();

            // Generar número correlativo con prefijo TRP- (Transferencia de Producción)
            $lastTrans = TransferenciaAlmacen::where('numero_transferencia', 'like', 'TRP-%')
                ->orderBy('id_transferencia', 'desc')
                ->lockForUpdate()
                ->first();

            $num = 1;
            if ($lastTrans) {
                if (preg_match('/^TRP-(\d+)$/', $lastTrans->numero_transferencia, $matches)) {
                    $num = intval($matches[1]) + 1;
                } else {
                    $maxNum = DB::table('transferencias_almacen')
                        ->where('numero_transferencia', 'like', 'TRP-%')
                        ->selectRaw("MAX(CAST(SUBSTRING(numero_transferencia, 5) AS UNSIGNED)) as max_num")
                        ->value('max_num');
                    $num = ($maxNum ?? 0) + 1;
                }
            }

            while (TransferenciaAlmacen::where('numero_transferencia', 'TRP-' . str_pad($num, 6, '0', STR_PAD_LEFT))->exists()) {
                $num++;
            }

            $numero_transferencia = 'TRP-' . str_pad($num, 6, '0', STR_PAD_LEFT);

            $transferencia = TransferenciaAlmacen::create([
                'numero_transferencia'   => $numero_transferencia,
                'codigo_almacen_origen'  => $origen,
                'codigo_almacen_destino' => $destino,
                'fecha_transferencia'    => $request->fecha_transferencia,
                'observaciones'          => $request->observaciones,
                'estado'                 => 'COMPLETADO',
                'usuario_registro'       => $usuario_id,
            ]);

            foreach ($request->items as $item) {
                $codigoProd = $item['codigo_producto'];
                $cantidadSolicitada = floatval($item['cantidad']);

                if ($cantidadSolicitada <= 0) continue;

                // Obtener todos los lotes disponibles del producto en el almacén de origen
                // Ordenados por FIFO: primero vencimiento más antiguo, luego fecha de registro/ID
                $lotesDisponibles = DB::table('inventario')
                    ->where('codigo_almacen', $origen)
                    ->where('codigo_producto', $codigoProd)
                    ->where('stock_actual', '>', 0)
                    ->where(function($q) {
                        $q->where('estado', 1)->orWhereNull('estado');
                    })
                    ->orderByRaw('COALESCE(fecha_vencimiento, "9999-12-31") ASC')
                    ->orderBy('id_inventario', 'asc')
                    ->lockForUpdate()
                    ->get();

                $stockTotalDisponible = $lotesDisponibles->sum('stock_actual');

                if ($stockTotalDisponible < $cantidadSolicitada) {
                    throw new \Exception("Stock insuficiente en origen ({$origen}) para el producto {$codigoProd}. Solicitado: {$cantidadSolicitada}, Disponible Total: {$stockTotalDisponible}");
                }

                $pendientePorTransferir = $cantidadSolicitada;

                foreach ($lotesDisponibles as $loteOrigen) {
                    if ($pendientePorTransferir <= 0) break;

                    $disponibleEnLote = floatval($loteOrigen->stock_actual);
                    $tomarDeLote = min($disponibleEnLote, $pendientePorTransferir);
                    $costoUnitario = floatval($loteOrigen->costo_promedio ?? 0);
                    $loteCodigo = $loteOrigen->lote;

                    // 1. Descontar del origen
                    DB::table('inventario')->where('id_inventario', $loteOrigen->id_inventario)->update([
                        'stock_actual' => DB::raw("stock_actual - {$tomarDeLote}"),
                        'fecha_ultimo_movimiento' => $request->fecha_transferencia,
                        'usuario_ultimo_movimiento' => $usuario_id
                    ]);

                    // 2. Movimiento Salida Origen
                    $movSalidaId = DB::table('movimientos_inventario')->insertGetId([
                        'codigo_almacen'       => $origen,
                        'codigo_producto'      => $codigoProd,
                        'lote'                 => $loteCodigo,
                        'tipo_movimiento'      => 'SALIDA',
                        'cantidad'             => $tomarDeLote,
                        'costo_unitario'       => $costoUnitario,
                        'total'                => $tomarDeLote * $costoUnitario,
                        'documento_referencia' => 'TRANSFERENCIA_PROD_SALIDA',
                        'numero_referencia'    => $numero_transferencia,
                        'observaciones'        => "Transferencia de Producción hacia {$destino}",
                        'usuario_movimiento'   => $usuario_id,
                        'fecha_movimiento'     => $request->fecha_transferencia,
                        'estado'               => 1,
                        'tiene_kardex'         => true,
                    ]);

                    // 3. Kardex Salida Origen
                    $stockActualOrigen = DB::table('inventario')
                        ->where('codigo_producto', $codigoProd)
                        ->where('codigo_almacen', $origen)
                        ->sum('stock_actual') ?? 0;
                    $totalSalida = $tomarDeLote * $costoUnitario;
                    $totalSaldoOrigen = $stockActualOrigen * $costoUnitario;

                    DB::table('kardex')->insert([
                        'codigo_almacen'               => $origen,
                        'codigo_producto'              => $codigoProd,
                        'fecha_movimiento'             => $request->fecha_transferencia,
                        'tipo_movimiento'              => 'SALIDA',
                        'documento'                    => 'TRANSFERENCIA_PROD',
                        'numero_documento'             => $numero_transferencia,
                        'cantidad_entrada'             => 0,
                        'costo_entrada'                => 0,
                        'total_entrada'                => 0,
                        'cantidad_salida'              => $tomarDeLote,
                        'costo_salida'                 => $costoUnitario,
                        'total_salida'                 => $totalSalida,
                        'cantidad_saldo'               => $stockActualOrigen,
                        'costo_promedio'               => $costoUnitario,
                        'total_saldo'                  => $totalSaldoOrigen,
                        'codigo_referencia_movimiento' => $movSalidaId,
                        'lote'                         => $loteCodigo,
                        'observaciones'                => "Envío a {$destino}",
                        'usuario_registro'             => $usuario_id,
                    ]);

                    // 4. Ingresar al destino respetando el lote original
                    $invDestino = DB::table('inventario')
                        ->where('codigo_almacen', $destino)
                        ->where('codigo_producto', $codigoProd)
                        ->where('lote', $loteCodigo)
                        ->lockForUpdate()
                        ->first();

                    if ($invDestino) {
                        DB::table('inventario')->where('id_inventario', $invDestino->id_inventario)->update([
                            'stock_actual' => DB::raw("stock_actual + {$tomarDeLote}"),
                            'estado' => 1,
                            'fecha_ultimo_movimiento' => $request->fecha_transferencia,
                            'usuario_ultimo_movimiento' => $usuario_id
                        ]);
                    } else {
                        DB::table('inventario')->insert([
                            'codigo_producto'           => $codigoProd,
                            'codigo_almacen'            => $destino,
                            'stock_actual'              => $tomarDeLote,
                            'stock_minimo'              => 0,
                            'costo_promedio'            => $costoUnitario,
                            'lote'                      => $loteCodigo,
                            'fecha_vencimiento'         => $loteOrigen->fecha_vencimiento,
                            'estado'                    => 1,
                            'fecha_ultimo_movimiento'   => $request->fecha_transferencia,
                            'usuario_ultimo_movimiento' => $usuario_id,
                        ]);
                    }

                    // 5. Movimiento Ingreso Destino
                    $movIngresoId = DB::table('movimientos_inventario')->insertGetId([
                        'codigo_almacen'       => $destino,
                        'codigo_producto'      => $codigoProd,
                        'lote'                 => $loteCodigo,
                        'tipo_movimiento'      => 'INGRESO',
                        'cantidad'             => $tomarDeLote,
                        'costo_unitario'       => $costoUnitario,
                        'total'                => $tomarDeLote * $costoUnitario,
                        'documento_referencia' => 'TRANSFERENCIA_PROD_INGRESO',
                        'numero_referencia'    => $numero_transferencia,
                        'observaciones'        => "Recepción de Producción desde {$origen}",
                        'usuario_movimiento'   => $usuario_id,
                        'fecha_movimiento'     => $request->fecha_transferencia,
                        'estado'               => 1,
                        'tiene_kardex'         => true,
                    ]);

                    // 6. Kardex Ingreso Destino
                    $stockActualDestino = DB::table('inventario')
                        ->where('codigo_producto', $codigoProd)
                        ->where('codigo_almacen', $destino)
                        ->sum('stock_actual') ?? 0;
                    $totalEntrada = $tomarDeLote * $costoUnitario;
                    $totalSaldoDestino = $stockActualDestino * $costoUnitario;

                    DB::table('kardex')->insert([
                        'codigo_almacen'               => $destino,
                        'codigo_producto'              => $codigoProd,
                        'fecha_movimiento'             => $request->fecha_transferencia,
                        'tipo_movimiento'              => 'INGRESO',
                        'documento'                    => 'TRANSFERENCIA_PROD',
                        'numero_documento'             => $numero_transferencia,
                        'cantidad_entrada'             => $tomarDeLote,
                        'costo_entrada'                => $costoUnitario,
                        'total_entrada'                => $totalEntrada,
                        'cantidad_salida'              => 0,
                        'costo_salida'                 => 0,
                        'total_salida'                 => 0,
                        'cantidad_saldo'               => $stockActualDestino,
                        'costo_promedio'               => $costoUnitario,
                        'total_saldo'                  => $totalSaldoDestino,
                        'codigo_referencia_movimiento' => $movIngresoId,
                        'lote'                         => $loteCodigo,
                        'observaciones'                => "Recepción desde {$origen}",
                        'usuario_registro'             => $usuario_id,
                    ]);

                    // 7. Registrar detalle de lote
                    TransferenciaAlmacenDetalle::create([
                        'id_transferencia' => $transferencia->id_transferencia,
                        'codigo_producto'  => $codigoProd,
                        'lote'             => $loteCodigo,
                        'cantidad'         => $tomarDeLote,
                    ]);

                    $pendientePorTransferir -= $tomarDeLote;
                }
            }

            DB::commit();

            return redirect()->route('produccion.transferencias.show', $transferencia->id_transferencia)
                ->with('success', "Transferencia de Producción {$numero_transferencia} procesada exitosamente.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al transferir: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $transferencia = TransferenciaAlmacen::with([
            'almacenOrigen',
            'almacenDestino',
            'usuario',
            'detalles.producto.unidadMedida',
            'detalles.producto.tipo'
        ])->findOrFail($id);

        // Agrupar detalles por producto para la vista resumen
        $resumenProductos = $transferencia->detalles->groupBy('codigo_producto')->map(function($items) {
            $primerItem = $items->first();
            return [
                'codigo_producto'     => $primerItem->codigo_producto,
                'producto'            => $primerItem->producto,
                'cantidad_total'      => $items->sum('cantidad'),
                'total_lotes'         => $items->count(),
                'lotes_utilizados'    => $items->pluck('lote')->filter()->values()->toArray(),
            ];
        });

        return view('produccion.transferencias.show', compact('transferencia', 'resumenProductos'));
    }

    public function anular($id)
    {
        try {
            DB::beginTransaction();

            $transferencia = TransferenciaAlmacen::where('id_transferencia', $id)->lockForUpdate()->firstOrFail();

            if ($transferencia->estado === 'ANULADO') {
                throw new \Exception("La transferencia ya se encuentra anulada.");
            }

            $origen = $transferencia->codigo_almacen_origen;
            $destino = $transferencia->codigo_almacen_destino;
            $usuario_id = Auth::id();

            $detalles = TransferenciaAlmacenDetalle::where('id_transferencia', $id)->get();

            foreach ($detalles as $det) {
                // 1. Verificar stock en destino para poder anular
                $invDestino = DB::table('inventario')
                    ->where('codigo_almacen', $destino)
                    ->where('codigo_producto', $det->codigo_producto)
                    ->where('lote', $det->lote)
                    ->lockForUpdate()
                    ->first();

                if (!$invDestino || $invDestino->stock_actual < $det->cantidad) {
                    throw new \Exception("Stock insuficiente en el almacén de destino ({$destino}) para revertir el lote {$det->lote} del producto {$det->codigo_producto}.");
                }

                $costoUnitario = floatval($invDestino->costo_promedio ?? 0);

                // 2. Descontar del destino
                DB::table('inventario')->where('id_inventario', $invDestino->id_inventario)->update([
                    'stock_actual' => DB::raw("stock_actual - {$det->cantidad}"),
                    'fecha_ultimo_movimiento' => now(),
                    'usuario_ultimo_movimiento' => $usuario_id
                ]);

                // Movimiento Salida Destino
                $movSalidaId = DB::table('movimientos_inventario')->insertGetId([
                    'codigo_almacen'       => $destino,
                    'codigo_producto'      => $det->codigo_producto,
                    'lote'                 => $det->lote,
                    'tipo_movimiento'      => 'SALIDA',
                    'cantidad'             => $det->cantidad,
                    'costo_unitario'       => $costoUnitario,
                    'total'                => $det->cantidad * $costoUnitario,
                    'documento_referencia' => 'ANULACION_TRANSFERENCIA_PROD',
                    'numero_referencia'    => $transferencia->numero_transferencia,
                    'observaciones'        => "Anulación Transferencia Producción",
                    'usuario_movimiento'   => $usuario_id,
                    'fecha_movimiento'     => now(),
                    'estado'               => 1,
                    'tiene_kardex'         => true,
                ]);

                // Kardex Salida Destino
                $stockActualDestino = DB::table('inventario')
                    ->where('codigo_producto', $det->codigo_producto)
                    ->where('codigo_almacen', $destino)
                    ->sum('stock_actual') ?? 0;
                $totalSalida = $det->cantidad * $costoUnitario;
                $totalSaldoDestino = $stockActualDestino * $costoUnitario;

                DB::table('kardex')->insert([
                    'codigo_almacen'               => $destino,
                    'codigo_producto'              => $det->codigo_producto,
                    'fecha_movimiento'             => now(),
                    'tipo_movimiento'              => 'SALIDA',
                    'documento'                    => 'ANULACION_TRP',
                    'numero_documento'             => $transferencia->numero_transferencia,
                    'cantidad_entrada'             => 0,
                    'costo_entrada'                => 0,
                    'total_entrada'                => 0,
                    'cantidad_salida'              => $det->cantidad,
                    'costo_salida'                 => $costoUnitario,
                    'total_salida'                 => $totalSalida,
                    'cantidad_saldo'               => $stockActualDestino,
                    'costo_promedio'               => $costoUnitario,
                    'total_saldo'                  => $totalSaldoDestino,
                    'codigo_referencia_movimiento' => $movSalidaId,
                    'lote'                         => $det->lote,
                    'observaciones'                => "Anulación de transferencia",
                    'usuario_registro'             => $usuario_id,
                ]);

                // 3. Devolver al origen
                $invOrigen = DB::table('inventario')
                    ->where('codigo_almacen', $origen)
                    ->where('codigo_producto', $det->codigo_producto)
                    ->where('lote', $det->lote)
                    ->lockForUpdate()
                    ->first();

                if ($invOrigen) {
                    DB::table('inventario')->where('id_inventario', $invOrigen->id_inventario)->update([
                        'stock_actual' => DB::raw("stock_actual + {$det->cantidad}"),
                        'estado' => 1,
                        'fecha_ultimo_movimiento' => now(),
                        'usuario_ultimo_movimiento' => $usuario_id
                    ]);
                } else {
                    DB::table('inventario')->insert([
                        'codigo_producto'           => $det->codigo_producto,
                        'codigo_almacen'            => $origen,
                        'stock_actual'              => $det->cantidad,
                        'stock_minimo'              => 0,
                        'costo_promedio'            => $costoUnitario,
                        'lote'                      => $det->lote,
                        'fecha_vencimiento'         => null,
                        'estado'                    => 1,
                        'fecha_ultimo_movimiento'   => now(),
                        'usuario_ultimo_movimiento' => $usuario_id,
                    ]);
                }

                // Movimiento Ingreso Origen
                $movIngresoId = DB::table('movimientos_inventario')->insertGetId([
                    'codigo_almacen'       => $origen,
                    'codigo_producto'      => $det->codigo_producto,
                    'lote'                 => $det->lote,
                    'tipo_movimiento'      => 'INGRESO',
                    'cantidad'             => $det->cantidad,
                    'costo_unitario'       => $costoUnitario,
                    'total'                => $det->cantidad * $costoUnitario,
                    'documento_referencia' => 'ANULACION_TRANSFERENCIA_PROD',
                    'numero_referencia'    => $transferencia->numero_transferencia,
                    'observaciones'        => "Anulación Transferencia Producción",
                    'usuario_movimiento'   => $usuario_id,
                    'fecha_movimiento'     => now(),
                    'estado'               => 1,
                    'tiene_kardex'         => true,
                ]);

                // Kardex Ingreso Origen
                $stockActualOrigen = DB::table('inventario')
                    ->where('codigo_producto', $det->codigo_producto)
                    ->where('codigo_almacen', $origen)
                    ->sum('stock_actual') ?? 0;
                $totalEntrada = $det->cantidad * $costoUnitario;
                $totalSaldoOrigen = $stockActualOrigen * $costoUnitario;

                DB::table('kardex')->insert([
                    'codigo_almacen'               => $origen,
                    'codigo_producto'              => $det->codigo_producto,
                    'fecha_movimiento'             => now(),
                    'tipo_movimiento'              => 'INGRESO',
                    'documento'                    => 'ANULACION_TRP',
                    'numero_documento'             => $transferencia->numero_transferencia,
                    'cantidad_entrada'             => $det->cantidad,
                    'costo_entrada'                => $costoUnitario,
                    'total_entrada'                => $totalEntrada,
                    'cantidad_salida'              => 0,
                    'costo_salida'                 => 0,
                    'total_salida'                 => 0,
                    'cantidad_saldo'               => $stockActualOrigen,
                    'costo_promedio'               => $costoUnitario,
                    'total_saldo'                  => $totalSaldoOrigen,
                    'codigo_referencia_movimiento' => $movIngresoId,
                    'lote'                         => $det->lote,
                    'observaciones'                => "Anulación",
                    'usuario_registro'             => $usuario_id,
                ]);
            }

            $transferencia->update(['estado' => 'ANULADO']);

            DB::commit();

            return back()->with('success', 'Transferencia de producción anulada exitosamente y saldos revertidos.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al anular: ' . $e->getMessage());
        }
    }
}
