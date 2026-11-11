<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransferenciaAlmacen;
use App\Models\TransferenciaAlmacenDetalle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TransferenciaAlmacenController extends Controller
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
        
        return view('inventario.transferencias.index', compact('transferencias', 'fecha_desde', 'fecha_hasta'));
    }

    public function create()
    {
        $almacenes = DB::table('almacen')->where('activo', 1)->get();
        return view('inventario.transferencias.create', compact('almacenes'));
    }

    public function buscarLotes(Request $request)
    {
        $almacen_origen = $request->codigo_almacen_origen;
        $search = $request->search;

        $query = DB::table('inventario as i')
            ->join('producto as p', 'i.codigo_producto', '=', 'p.codigo')
            ->where('i.codigo_almacen', $almacen_origen)
            ->where('i.stock_actual', '>', 0)
            ->where(function($q) {
                $q->where('i.estado', 1)->orWhereNull('i.estado');
            });

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('p.descripcion', 'like', "%$search%")
                  ->orWhere('p.codigo', 'like', "%$search%")
                  ->orWhere('i.lote', 'like', "%$search%");
            });
        }

        $lotes = $query->select(
            'i.id_inventario',
            'i.codigo_producto',
            'p.descripcion as producto_descripcion',
            'p.codigo_unidad_medida',
            'i.lote',
            'i.stock_actual',
            'i.fecha_vencimiento',
            'i.costo_promedio'
        )->orderBy('p.descripcion')->orderBy('i.fecha_vencimiento')->limit(50)->get();

        return response()->json(['success' => true, 'data' => $lotes]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'fecha_transferencia'    => 'required|date',
            'codigo_almacen_origen'  => 'required|string|exists:almacen,codigo_almacen',
            'codigo_almacen_destino' => 'required|string|exists:almacen,codigo_almacen|different:codigo_almacen_origen',
            'observaciones'          => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.codigo_producto'=> 'required|string',
            'items.*.lote'           => 'nullable|string',
            'items.*.cantidad'       => 'required|numeric|min:0.0001',
            'items.*.id_inventario'  => 'required|integer',
        ]);

        try {
            DB::beginTransaction();

            // Generar número correlativo con prefijo TR- (Transferencia de Almacén)
            $lastTrans = TransferenciaAlmacen::where('numero_transferencia', 'like', 'TR-%')
                ->where('numero_transferencia', 'not like', 'TRP-%')
                ->orderBy('id_transferencia', 'desc')
                ->lockForUpdate()
                ->first();

            $num = 1;
            if ($lastTrans) {
                if (preg_match('/^TR-(\d+)$/', $lastTrans->numero_transferencia, $matches)) {
                    $num = intval($matches[1]) + 1;
                } else {
                    $maxNum = DB::table('transferencias_almacen')
                        ->where('numero_transferencia', 'like', 'TR-%')
                        ->where('numero_transferencia', 'not like', 'TRP-%')
                        ->selectRaw("MAX(CAST(SUBSTRING(numero_transferencia, 4) AS UNSIGNED)) as max_num")
                        ->value('max_num');
                    $num = ($maxNum ?? 0) + 1;
                }
            }

            while (TransferenciaAlmacen::where('numero_transferencia', 'TR-' . str_pad($num, 6, '0', STR_PAD_LEFT))->exists()) {
                $num++;
            }

            $numero_transferencia = 'TR-' . str_pad($num, 6, '0', STR_PAD_LEFT);

            $horaTransferencia = now()->format('H:i:s');
            if ($request->filled('hora_transferencia')) {
                $horaTransferencia = strlen($request->hora_transferencia) === 5 ? $request->hora_transferencia . ':00' : $request->hora_transferencia;
            }
            $fechaHoraTransferencia = \Carbon\Carbon::parse($request->fecha_transferencia . ' ' . $horaTransferencia)->format('Y-m-d H:i:s');

            $transferencia = TransferenciaAlmacen::create([
                'numero_transferencia'   => $numero_transferencia,
                'codigo_almacen_origen'  => $request->codigo_almacen_origen,
                'codigo_almacen_destino' => $request->codigo_almacen_destino,
                'fecha_transferencia'    => $fechaHoraTransferencia,
                'observaciones'          => $request->observaciones,
                'estado'                 => 'COMPLETADO',
                'usuario_registro'       => Auth::id(),
            ]);

            $origen = $request->codigo_almacen_origen;
            $destino = $request->codigo_almacen_destino;
            $usuario_id = Auth::id();

            foreach ($request->items as $item) {
                $invOrigen = DB::table('inventario')->where('id_inventario', $item['id_inventario'])->lockForUpdate()->first();
                
                if (!$invOrigen || round(floatval($invOrigen->stock_actual), 4) < round(floatval($item['cantidad']), 4)) {
                    throw new \Exception("Stock insuficiente en origen para el lote {$item['lote']} del producto {$item['codigo_producto']}. Disponible: " . ($invOrigen->stock_actual ?? 0));
                }

                // 1. Descontar del origen
                DB::table('inventario')->where('id_inventario', $invOrigen->id_inventario)->update([
                    'stock_actual' => DB::raw("GREATEST(0, stock_actual - {$item['cantidad']})"),
                    'fecha_ultimo_movimiento' => $fechaHoraTransferencia,
                    'usuario_ultimo_movimiento' => $usuario_id
                ]);

                // 2. Movimiento Salida Origen
                $costoUnitario = $invOrigen->costo_promedio ?? 0;
                $movSalidaId = DB::table('movimientos_inventario')->insertGetId([
                    'codigo_almacen'       => $origen,
                    'codigo_producto'      => $item['codigo_producto'],
                    'lote'                 => $item['lote'],
                    'tipo_movimiento'      => 'SALIDA',
                    'cantidad'             => $item['cantidad'],
                    'costo_unitario'       => $costoUnitario,
                    'total'                => $item['cantidad'] * $costoUnitario,
                    'documento_referencia' => 'TRANSFERENCIA_SALIDA',
                    'numero_referencia'    => $numero_transferencia,
                    'observaciones'        => "Transferencia a $destino",
                    'usuario_movimiento'   => $usuario_id,
                    'fecha_movimiento'     => $fechaHoraTransferencia,
                    'estado'               => 1,
                    'tiene_kardex'         => true,
                ]);

                // Kardex Salida Origen
                $stockActualOrigen = DB::table('inventario')->where('codigo_producto', $item['codigo_producto'])->where('codigo_almacen', $origen)->sum('stock_actual') ?? 0;
                $totalSalida = $item['cantidad'] * $costoUnitario;
                $totalSaldoOrigen = $stockActualOrigen * $costoUnitario;
                DB::table('kardex')->insert([
                    'codigo_almacen'               => $origen,
                    'codigo_producto'              => $item['codigo_producto'],
                    'fecha_movimiento'             => $fechaHoraTransferencia,
                    'tipo_movimiento'              => 'SALIDA',
                    'documento'                    => 'TRANSFERENCIA',
                    'numero_documento'             => $numero_transferencia,
                    'cantidad_entrada'             => 0,
                    'costo_entrada'                => 0,
                    'total_entrada'                => 0,
                    'cantidad_salida'              => $item['cantidad'],
                    'costo_salida'                 => $costoUnitario,
                    'total_salida'                 => $totalSalida,
                    'cantidad_saldo'               => $stockActualOrigen,
                    'costo_promedio'               => $costoUnitario,
                    'total_saldo'                  => $totalSaldoOrigen,
                    'codigo_referencia_movimiento' => $movSalidaId,
                    'lote'                         => $item['lote'],
                    'observaciones'                => "Envío a $destino",
                    'usuario_registro'             => $usuario_id,
                ]);

                // 3. Ingresar al destino (con lock)
                $invDestino = DB::table('inventario')
                    ->where('codigo_almacen', $destino)
                    ->where('codigo_producto', $item['codigo_producto'])
                    ->where('lote', $item['lote'])
                    ->lockForUpdate()
                    ->first();

                if ($invDestino) {
                    DB::table('inventario')->where('id_inventario', $invDestino->id_inventario)->update([
                        'stock_actual' => DB::raw("stock_actual + {$item['cantidad']}"),
                        'estado' => 1,
                        'fecha_ultimo_movimiento' => $fechaHoraTransferencia,
                        'usuario_ultimo_movimiento' => $usuario_id
                    ]);
                } else {
                    DB::table('inventario')->insert([
                        'codigo_producto'           => $item['codigo_producto'],
                        'codigo_almacen'            => $destino,
                        'stock_actual'              => $item['cantidad'],
                        'stock_minimo'              => 0,
                        'costo_promedio'            => $costoUnitario,
                        'lote'                      => $item['lote'],
                        'fecha_vencimiento'         => $invOrigen->fecha_vencimiento,
                        'estado'                    => 1,
                        'fecha_ultimo_movimiento'   => $fechaHoraTransferencia,
                        'usuario_ultimo_movimiento' => $usuario_id,
                    ]);
                }

                // 4. Movimiento Ingreso Destino
                $movIngresoId = DB::table('movimientos_inventario')->insertGetId([
                    'codigo_almacen'       => $destino,
                    'codigo_producto'      => $item['codigo_producto'],
                    'lote'                 => $item['lote'],
                    'tipo_movimiento'      => 'INGRESO',
                    'cantidad'             => $item['cantidad'],
                    'costo_unitario'       => $costoUnitario,
                    'total'                => $item['cantidad'] * $costoUnitario,
                    'documento_referencia' => 'TRANSFERENCIA_INGRESO',
                    'numero_referencia'    => $numero_transferencia,
                    'observaciones'        => "Recepción desde $origen",
                    'usuario_movimiento'   => $usuario_id,
                    'fecha_movimiento'     => $fechaHoraTransferencia,
                    'estado'               => 1,
                    'tiene_kardex'         => true,
                ]);

                // Kardex Ingreso Destino
                $stockActualDestino = DB::table('inventario')->where('codigo_producto', $item['codigo_producto'])->where('codigo_almacen', $destino)->sum('stock_actual') ?? 0;
                $totalEntrada = $item['cantidad'] * $costoUnitario;
                $totalSaldoDestino = $stockActualDestino * $costoUnitario;
                DB::table('kardex')->insert([
                    'codigo_almacen'               => $destino,
                    'codigo_producto'              => $item['codigo_producto'],
                    'fecha_movimiento'             => $fechaHoraTransferencia,
                    'tipo_movimiento'              => 'INGRESO',
                    'documento'                    => 'TRANSFERENCIA',
                    'numero_documento'             => $numero_transferencia,
                    'cantidad_entrada'             => $item['cantidad'],
                    'costo_entrada'                => $costoUnitario,
                    'total_entrada'                => $totalEntrada,
                    'cantidad_salida'              => 0,
                    'costo_salida'                 => 0,
                    'total_salida'                 => 0,
                    'cantidad_saldo'               => $stockActualDestino,
                    'costo_promedio'               => $costoUnitario,
                    'total_saldo'                  => $totalSaldoDestino,
                    'codigo_referencia_movimiento' => $movIngresoId,
                    'lote'                         => $item['lote'],
                    'observaciones'                => "Recepción desde $origen",
                    'usuario_registro'             => $usuario_id,
                ]);

                // 5. Registrar Detalle
                TransferenciaAlmacenDetalle::create([
                    'id_transferencia' => $transferencia->id_transferencia,
                    'codigo_producto'  => $item['codigo_producto'],
                    'lote'             => $item['lote'],
                    'cantidad'         => $item['cantidad'],
                ]);
            }

            DB::commit();
            return redirect()->route('inventario.transferencias.show', $transferencia->id_transferencia)
                             ->with('success', 'Transferencia registrada correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al transferir: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $transferencia = TransferenciaAlmacen::with(['almacenOrigen', 'almacenDestino', 'usuario', 'detalles.producto'])
            ->findOrFail($id);
            
        return view('inventario.transferencias.show', compact('transferencia'));
    }

    public function anular($id)
    {
        try {
            DB::beginTransaction();

            $transferencia = TransferenciaAlmacen::where('id_transferencia', $id)->lockForUpdate()->firstOrFail();

            if ($transferencia->estado === 'ANULADO') {
                throw new \Exception("La transferencia ya está anulada.");
            }

            $origen = $transferencia->codigo_almacen_origen;
            $destino = $transferencia->codigo_almacen_destino;
            $usuario_id = Auth::id();

            $detalles = TransferenciaAlmacenDetalle::where('id_transferencia', $id)->get();

            foreach ($detalles as $det) {
                // Verificar stock en destino para poder anular
                $invDestino = DB::table('inventario')
                    ->where('codigo_almacen', $destino)
                    ->where('codigo_producto', $det->codigo_producto)
                    ->where('lote', $det->lote)
                    ->lockForUpdate()
                    ->first();

                if (!$invDestino || round(floatval($invDestino->stock_actual), 4) < round(floatval($det->cantidad), 4)) {
                    throw new \Exception("Stock insuficiente en el almacén de destino para revertir el lote {$det->lote} del producto {$det->codigo_producto}.");
                }

                // 1. Quitar del destino
                DB::table('inventario')->where('id_inventario', $invDestino->id_inventario)->update([
                    'stock_actual' => DB::raw("GREATEST(0, stock_actual - {$det->cantidad})"),
                    'fecha_ultimo_movimiento' => now(),
                    'usuario_ultimo_movimiento' => $usuario_id
                ]);

                // Movimiento y Kardex Salida por anulación
                $costoUnitario = $invDestino->costo_promedio ?? 0;
                $movSalidaId = DB::table('movimientos_inventario')->insertGetId([
                    'codigo_almacen'       => $destino,
                    'codigo_producto'      => $det->codigo_producto,
                    'lote'                 => $det->lote,
                    'tipo_movimiento'      => 'SALIDA',
                    'cantidad'             => $det->cantidad,
                    'costo_unitario'       => $costoUnitario,
                    'total'                => $det->cantidad * $costoUnitario,
                    'documento_referencia' => 'ANULACION_TRANSFERENCIA',
                    'numero_referencia'    => $transferencia->numero_transferencia,
                    'observaciones'        => "Anulación de transferencia",
                    'usuario_movimiento'   => $usuario_id,
                    'fecha_movimiento'     => now(),
                    'estado'               => 1,
                    'tiene_kardex'         => true,
                ]);

                $stockActualDestino = DB::table('inventario')->where('codigo_producto', $det->codigo_producto)->where('codigo_almacen', $destino)->sum('stock_actual') ?? 0;
                $totalSalida = $det->cantidad * $costoUnitario;
                $totalSaldoDestino = $stockActualDestino * $costoUnitario;
                DB::table('kardex')->insert([
                    'codigo_almacen'               => $destino,
                    'codigo_producto'              => $det->codigo_producto,
                    'fecha_movimiento'             => now(),
                    'tipo_movimiento'              => 'SALIDA',
                    'documento'                    => 'ANULACION_TRANSFERENCIA',
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
                    'observaciones'                => "Anulación",
                    'usuario_registro'             => $usuario_id,
                ]);

                // 2. Devolver al origen
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

                // Movimiento y Kardex Ingreso por anulación
                $movIngresoId = DB::table('movimientos_inventario')->insertGetId([
                    'codigo_almacen'       => $origen,
                    'codigo_producto'      => $det->codigo_producto,
                    'lote'                 => $det->lote,
                    'tipo_movimiento'      => 'INGRESO',
                    'cantidad'             => $det->cantidad,
                    'costo_unitario'       => $costoUnitario,
                    'total'                => $det->cantidad * $costoUnitario,
                    'documento_referencia' => 'ANULACION_TRANSFERENCIA',
                    'numero_referencia'    => $transferencia->numero_transferencia,
                    'observaciones'        => "Anulación de transferencia",
                    'usuario_movimiento'   => $usuario_id,
                    'fecha_movimiento'     => now(),
                    'estado'               => 1,
                    'tiene_kardex'         => true,
                ]);

                $stockActualOrigen = DB::table('inventario')->where('codigo_producto', $det->codigo_producto)->where('codigo_almacen', $origen)->sum('stock_actual') ?? 0;
                $totalEntradaInv = $det->cantidad * $costoUnitario;
                $totalSaldoOrigen = $stockActualOrigen * $costoUnitario;
                DB::table('kardex')->insert([
                    'codigo_almacen'               => $origen,
                    'codigo_producto'              => $det->codigo_producto,
                    'fecha_movimiento'             => now(),
                    'tipo_movimiento'              => 'INGRESO',
                    'documento'                    => 'ANULACION_TRANSFERENCIA',
                    'numero_documento'             => $transferencia->numero_transferencia,
                    'cantidad_entrada'             => $det->cantidad,
                    'costo_entrada'                => $costoUnitario,
                    'total_entrada'                => $totalEntradaInv,
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
            return back()->with('success', 'Transferencia anulada exitosamente. Se ha revertido el inventario.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al anular: ' . $e->getMessage());
        }
    }
}
