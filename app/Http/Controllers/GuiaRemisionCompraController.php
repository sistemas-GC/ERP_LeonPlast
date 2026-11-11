<?php

namespace App\Http\Controllers;

use App\Models\GuiaRemisionCompra;
use App\Models\DetalleGuiaCompra;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Services\KardexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GuiaRemisionCompraController extends Controller
{
    public function index(Request $request)
    {
        $fecha_desde = $request->input('fecha_desde', now()->startOfMonth()->toDateString());
        $fecha_hasta = $request->input('fecha_hasta', now()->endOfMonth()->toDateString());

        $query = GuiaRemisionCompra::with('datosProveedor', 'creador')
            ->withSum('detalles', 'cantidad')
            ->whereDate('fecha_emision', '>=', $fecha_desde)
            ->whereDate('fecha_emision', '<=', $fecha_hasta);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_guia', 'like', "%$search%")
                  ->orWhere('proveedor', 'like', "%$search%")
                  ->orWhere('ruc_proveedor', 'like', "%$search%");
            });
        }

        $guias = $query->orderBy('fecha_registro', 'desc')
            ->paginate(15)
            ->appends($request->all());

        return view('guia_compras.index', compact('guias', 'fecha_desde', 'fecha_hasta'));
    }

    public function create()
    {
        $proveedores = Proveedor::where('activo', 1)->get();
        $productos = Producto::where('estado', 1)->get();
        $unidades_medida = \App\Models\UnidadMedida::where('estado', 1)->get();

        return view('guia_compras.create', compact('proveedores', 'productos', 'unidades_medida'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'proveedor' => 'required|string|max:100',
            'ruc_proveedor' => 'nullable|string|max:11',
            'numero_guia' => 'required|string|max:20',
            'fecha_emision' => 'required|date',
            'productos' => 'required|array|min:1',
            'productos.*.codigo_producto' => 'required|string',
            'productos.*.cantidad' => 'required|numeric|min:0.01',
            'productos.*.lote' => 'nullable|string|max:50',
            'productos.*.fecha_vencimiento' => 'nullable|date',
            'observaciones' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // 1. Guardar la Guía
            $guia = GuiaRemisionCompra::create([
                'proveedor' => $request->proveedor,
                'ruc_proveedor' => $request->ruc_proveedor,
                'numero_guia' => $request->numero_guia,
                'fecha_emision' => $request->fecha_emision,
                'estado' => 'RECIBIDA',
                'observaciones' => $request->observaciones,
                'usuario_registro' => Auth::id()
            ]);

            $kardexService = app(KardexService::class);

            // 2. Guardar Detalles e Ingresar al Kardex
            foreach ($request->productos as $item) {
                $producto = Producto::where('codigo', $item['codigo_producto'])->first();
                $almacen_destino = 'ALM04'; // ALMACEN COMPRAS NAC/IMP

                $unidad_medida = $item['codigo_unidad_medida'] ?? ($producto->unidad_medida_codigo ?? 'NIU');

                $fechaGuiaStr = !empty($guia->fecha_emision) ? date('dmY', strtotime($guia->fecha_emision)) : '';
                $loteGuiaDefault = strtoupper(trim($guia->numero_guia)) . ($fechaGuiaStr ? ('-' . $fechaGuiaStr) : '');
                $loteItem = !empty(trim($item['lote'] ?? '')) ? trim($item['lote']) : $loteGuiaDefault;

                $detalle = DetalleGuiaCompra::create([
                    'id_guia' => $guia->id_guia,
                    'codigo_producto' => $item['codigo_producto'],
                    'descripcion_producto' => $producto->descripcion ?? '',
                    'cantidad' => $item['cantidad'],
                    'codigo_unidad_medida' => $unidad_medida,
                    'codigo_almacen' => $almacen_destino,
                    'lote' => $loteItem,
                    'fecha_vencimiento' => !empty($item['fecha_vencimiento']) ? $item['fecha_vencimiento'] : $guia->fecha_emision
                ]);

                // 3. Obtener el Costo Promedio Actual para valorizar la entrada de la Guía
                $ultimoKardex = DB::table('kardex')
                    ->where('codigo_producto', $item['codigo_producto'])
                    ->where('codigo_almacen', $almacen_destino)
                    ->orderBy('id_kardex', 'desc')
                    ->first();
                
                $costoPromedioActual = $ultimoKardex?->costo_promedio ?? 0;

                // Si es el primer ingreso absoluto y el costo promedio es 0, usamos 0.
                // Cuando llegue la Factura vinculada, se regularizará si es necesario o en la refacturación.

                // 5. Obtener Inventario actual por producto, almacen y lote
                $inventario = DB::table('inventario')
                    ->where('codigo_producto', $item['codigo_producto'])
                    ->where('codigo_almacen', $almacen_destino)
                    ->where('lote', $loteItem)
                    ->first();

                // 4. Calcular Costos usando KardexService (acumula en Kardex)
                $costos = $kardexService->calcularCostos(
                    $item['codigo_producto'], 
                    $almacen_destino,
                    $item['cantidad'], // cantidad que entra
                    $costoPromedioActual, // precio de entrada
                    0, // cantidad salida
                    $inventario ? $inventario->stock_actual : 0  // saldo actual
                );

                $stock_lote_actual = $inventario ? floatval($inventario->stock_actual) : 0;
                $nuevo_saldo_lote = $stock_lote_actual + floatval($item['cantidad']);

                // 5. Actualizar tabla Inventario (stock físico por lote)
                if ($inventario) {
                    DB::table('inventario')
                        ->where('id_inventario', $inventario->id_inventario)
                        ->update([
                            'stock_actual' => $nuevo_saldo_lote,
                            'costo_promedio' => $costos['costo_promedio'],
                            'fecha_ultimo_movimiento' => now(),
                            'usuario_ultimo_movimiento' => Auth::id(),
                            'fecha_vencimiento' => !empty($item['fecha_vencimiento']) ? $item['fecha_vencimiento'] : $inventario->fecha_vencimiento
                        ]);
                } else {
                    DB::table('inventario')->insert([
                        'codigo_producto' => $item['codigo_producto'],
                        'codigo_almacen' => $almacen_destino,
                        'codigo_unidad_medida' => $producto->unidad_medida_codigo ?? 'NIU',
                        'lote' => $loteItem,
                        'fecha_vencimiento' => !empty($item['fecha_vencimiento']) ? $item['fecha_vencimiento'] : null,
                        'stock_actual' => $nuevo_saldo_lote,
                        'stock_minimo' => 0,
                        'costo_promedio' => $costos['costo_promedio'],
                        'fecha_ultimo_movimiento' => now(),
                        'usuario_ultimo_movimiento' => Auth::id()
                    ]);
                }

                // 6. Registrar en Kardex
                DB::table('kardex')->insert([
                    'codigo_producto'      => $item['codigo_producto'],
                    'codigo_almacen'       => $almacen_destino,
                    'codigo_unidad_medida' => $unidad_medida,
                    'fecha_movimiento'     => now(),
                    'tipo_movimiento'      => 'INGRESO',
                    'documento'            => 'GUIA REMISION',
                    'numero_documento'     => $guia->numero_guia,
                    'cantidad_entrada'     => $item['cantidad'],
                    'costo_entrada'        => $costoPromedioActual,
                    'total_entrada'        => $item['cantidad'] * $costoPromedioActual,
                    'cantidad_salida'      => 0,
                    'costo_salida'         => 0,
                    'total_salida'         => 0,
                    'cantidad_saldo'       => $costos['cantidad_saldo'],
                    'costo_promedio'       => $costos['costo_promedio'],
                    'total_saldo'          => $costos['total_saldo'],
                    'lote'                 => $loteItem,
                    'usuario_registro'     => Auth::id()
                ]);

                // 7. Registrar en movimientos_inventario para trazabilidad
                DB::table('movimientos_inventario')->insert([
                    'codigo_almacen'       => $almacen_destino,
                    'codigo_producto'      => $item['codigo_producto'],
                    'codigo_unidad_medida' => $unidad_medida,
                    'lote'                 => $loteItem,
                    'fecha_vencimiento'    => $item['fecha_vencimiento'] ?? null,
                    'tipo_movimiento'      => 'INGRESO',
                    'cantidad'             => $item['cantidad'],
                    'costo_unitario'       => $costoPromedioActual,
                    'total'                => $item['cantidad'] * $costoPromedioActual,
                    'documento_referencia' => 'GUIA REMISION COMPRA',
                    'numero_referencia'    => $guia->numero_guia,
                    'observaciones'        => 'Recepción de Guía',
                    'usuario_movimiento'   => Auth::id(),
                    'tiene_kardex'         => 1,
                    'fecha_movimiento'     => now(),
                    'estado'               => 1,
                ]);

                // 8. Lógica de Terceros: Conciliación FIFO
                $mapeoTercero = DB::table('terceros_mapeo_productos')
                    ->where('codigo_producto_destino', $item['codigo_producto'])
                    ->where('estado', 1)
                    ->first();
                
                if ($mapeoTercero) {
                    $codigoOrigen = $mapeoTercero->codigo_producto_origen;
                    
                    // Buscar guias de salida pendientes de este proveedor que tengan este codigo origen
                    $salidasPendientes = DB::table('guia_remision_terceros_salida as c')
                        ->join('guia_remision_terceros_salida_detalle as d', 'c.id_guia_salida', '=', 'd.id_guia_salida')
                        ->where('c.proveedor_destino', $request->proveedor)
                        ->where('d.codigo_producto', $codigoOrigen)
                        ->whereIn('d.estado_detalle', ['PENDIENTE', 'PARCIAL'])
                        ->where('c.estado_guia', '!=', 'ANULADA')
                        ->orderBy('c.fecha_emision', 'asc')
                        ->orderBy('d.id_detalle_salida', 'asc')
                        ->select('d.*', 'c.numero_guia')
                        ->get();
                    
                    $cantidadAAmortizar = $item['cantidad'];
                    
                    foreach ($salidasPendientes as $salida) {
                        if ($cantidadAAmortizar <= 0) break;
                        
                        $saldoPendienteSalida = $salida->cantidad_enviada - $salida->cantidad_devuelta - $salida->cantidad_merma;
                        
                        if ($saldoPendienteSalida <= 0) continue;
                        
                        $cantidadAmortizada = min($cantidadAAmortizar, $saldoPendienteSalida);
                        
                        // Registrar en tabla conciliacion_terceros
                        DB::table('conciliacion_terceros')->insert([
                            'id_detalle_salida' => $salida->id_detalle_salida,
                            'id_detalle_compra' => $detalle->id_detalle_guia,
                            'cantidad_amortizada' => $cantidadAmortizada,
                            'fecha_conciliacion' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        
                        // Actualizar detalle salida
                        $nuevaCantidadDevuelta = $salida->cantidad_devuelta + $cantidadAmortizada;
                        // Si la diferencia es menor a 0.01 se considera completado por exactitud
                        $nuevoEstado = ($salida->cantidad_enviada - $nuevaCantidadDevuelta - $salida->cantidad_merma <= 0.01) ? 'COMPLETADO' : 'PARCIAL';
                        
                        DB::table('guia_remision_terceros_salida_detalle')
                            ->where('id_detalle_salida', $salida->id_detalle_salida)
                            ->update([
                                'cantidad_devuelta' => $nuevaCantidadDevuelta,
                                'estado_detalle' => $nuevoEstado,
                                'updated_at' => now(),
                            ]);
                            
                        $cantidadAAmortizar -= $cantidadAmortizada;
                    }
                }
            }

            DB::commit();
            return redirect()->route('guia_compras.create')->with('success_ask', 'Guía de Remisión registrada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Error al registrar guía: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $guia = GuiaRemisionCompra::with('detalles.producto', 'creador', 'datosProveedor', 'compra')->findOrFail($id);
        return view('guia_compras.show', compact('guia'));
    }

    public function edit($id)
    {
        $guia = GuiaRemisionCompra::with('detalles.producto')->findOrFail($id);
        
        if ($guia->estado !== 'RECIBIDA') {
            return redirect()->route('guia_compras.show', $id)->with('error', 'Solo se pueden editar guías en estado RECIBIDA.');
        }

        $proveedores = Proveedor::where('activo', 1)->get();
        $productos = Producto::where('estado', 1)->get();
        $unidades_medida = \App\Models\UnidadMedida::where('estado', 1)->get();

        return view('guia_compras.edit', compact('guia', 'proveedores', 'productos', 'unidades_medida'));
    }

    public function update(Request $request, $id)
    {
        $guia = GuiaRemisionCompra::findOrFail($id);
        
        if ($guia->estado !== 'RECIBIDA') {
            return redirect()->route('guia_compras.show', $id)->with('error', 'Solo se pueden editar guías en estado RECIBIDA.');
        }

        $request->validate([
            'proveedor' => 'required|string|max:100',
            'ruc_proveedor' => 'nullable|string|max:11',
            'numero_guia' => 'required|string|max:20',
            'fecha_emision' => 'required|date',
            'productos' => 'required|array|min:1',
            'productos.*.codigo_producto' => 'required|string',
            'productos.*.cantidad' => 'required|numeric|min:0.01',
            'productos.*.lote' => 'nullable|string|max:50',
            'productos.*.fecha_vencimiento' => 'nullable|date',
            'observaciones' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $almacen_destino = 'ALM04';

            // 1. Revertir inventario de ALM04 de los detalles antiguos
            $kardexService = app(KardexService::class);
            foreach ($guia->detalles as $detalle_antiguo) {
                $loteAntiguo = !empty($detalle_antiguo->lote) ? $detalle_antiguo->lote : null;
                $inventario = DB::table('inventario')
                    ->where('codigo_producto', $detalle_antiguo->codigo_producto)
                    ->where('codigo_almacen', $almacen_destino)
                    ->where('lote', $loteAntiguo)
                    ->lockForUpdate()
                    ->first();
                    
                $stock_actual = $inventario ? $inventario->stock_actual : 0;
                $nuevo_saldo = max(0, $stock_actual - $detalle_antiguo->cantidad);
                
                if ($inventario) {
                    DB::table('inventario')
                        ->where('id_inventario', $inventario->id_inventario)
                        ->update([
                            'stock_actual' => $nuevo_saldo,
                            'fecha_ultimo_movimiento' => now(),
                            'usuario_ultimo_movimiento' => Auth::id()
                        ]);
                }

                $ultimoKardex = DB::table('kardex')
                    ->where('codigo_producto', $detalle_antiguo->codigo_producto)
                    ->where('codigo_almacen', $almacen_destino)
                    ->orderBy('id_kardex', 'desc')
                    ->first();
                $costoPromedioActual = $ultimoKardex ? $ultimoKardex->costo_promedio : 0;
                
                $costos = $kardexService->calcularCostos(
                    $detalle_antiguo->codigo_producto, 
                    $almacen_destino,
                    0, $costoPromedioActual,
                    $detalle_antiguo->cantidad, $nuevo_saldo
                );

                DB::table('kardex')->insert([
                    'codigo_producto'      => $detalle_antiguo->codigo_producto,
                    'codigo_almacen'       => $almacen_destino,
                    'codigo_unidad_medida' => $detalle_antiguo->codigo_unidad_medida,
                    'fecha_movimiento'     => now(),
                    'tipo_movimiento'      => 'EXTORNO',
                    'documento'            => 'GUIA REMISION',
                    'numero_documento'     => $guia->numero_guia,
                    'cantidad_entrada'     => 0,
                    'costo_entrada'        => 0,
                    'total_entrada'        => 0,
                    'cantidad_salida'      => $detalle_antiguo->cantidad,
                    'costo_salida'         => $costoPromedioActual,
                    'total_salida'         => $detalle_antiguo->cantidad * $costoPromedioActual,
                    'cantidad_saldo'       => $costos['cantidad_saldo'],
                    'costo_promedio'       => $costos['costo_promedio'],
                    'total_saldo'          => $costos['total_saldo'],
                    'observaciones'        => 'EXTORNO POR EDICION DE GUIA',
                    'usuario_registro'     => Auth::id()
                ]);
            }

            // 2. Eliminar detalles antiguos
            DetalleGuiaCompra::where('id_guia', $id)->delete();

            // 3. Actualizar la cabecera
            $guia->update([
                'proveedor' => $request->proveedor,
                'ruc_proveedor' => $request->ruc_proveedor,
                'numero_guia' => $request->numero_guia,
                'fecha_emision' => $request->fecha_emision,
                'observaciones' => $request->observaciones
            ]);

            // 4. Crear los nuevos detalles e ingresarlos a ALM04
            foreach ($request->productos as $item) {
                $producto = Producto::where('codigo', $item['codigo_producto'])->first();
                $unidad_medida = $item['codigo_unidad_medida'] ?? ($producto->unidad_medida_codigo ?? 'NIU');

                $fechaGuiaStr = !empty($guia->fecha_emision) ? date('dmY', strtotime($guia->fecha_emision)) : '';
                $loteGuiaDefault = strtoupper(trim($guia->numero_guia)) . ($fechaGuiaStr ? ('-' . $fechaGuiaStr) : '');
                $loteItem = !empty(trim($item['lote'] ?? '')) ? trim($item['lote']) : $loteGuiaDefault;

                DetalleGuiaCompra::create([
                    'id_guia' => $guia->id_guia,
                    'codigo_producto' => $item['codigo_producto'],
                    'descripcion_producto' => $producto->descripcion ?? '',
                    'cantidad' => $item['cantidad'],
                    'codigo_unidad_medida' => $unidad_medida,
                    'codigo_almacen' => $almacen_destino,
                    'lote' => $loteItem,
                    'fecha_vencimiento' => !empty($item['fecha_vencimiento']) ? $item['fecha_vencimiento'] : $guia->fecha_emision
                ]);

                $inventario = DB::table('inventario')
                    ->where('codigo_producto', $item['codigo_producto'])
                    ->where('codigo_almacen', $almacen_destino)
                    ->where('lote', $loteItem)
                    ->lockForUpdate()
                    ->first();
                    
                $stock_actual = $inventario ? $inventario->stock_actual : 0;
                $nuevo_saldo = $stock_actual + $item['cantidad'];
                
                if ($inventario) {
                    DB::table('inventario')
                        ->where('id_inventario', $inventario->id_inventario)
                        ->update([
                            'stock_actual' => $nuevo_saldo,
                            'fecha_ultimo_movimiento' => now(),
                            'usuario_ultimo_movimiento' => Auth::id(),
                            'fecha_vencimiento' => !empty($item['fecha_vencimiento']) ? $item['fecha_vencimiento'] : $inventario->fecha_vencimiento
                        ]);
                } else {
                    DB::table('inventario')->insert([
                        'codigo_producto' => $item['codigo_producto'],
                        'codigo_almacen' => $almacen_destino,
                        'lote' => $loteItem,
                        'fecha_vencimiento' => !empty($item['fecha_vencimiento']) ? $item['fecha_vencimiento'] : null,
                        'stock_actual' => $nuevo_saldo,
                        'fecha_ultimo_movimiento' => now(),
                        'usuario_ultimo_movimiento' => Auth::id()
                    ]);
                }

                $ultimoKardex = DB::table('kardex')
                    ->where('codigo_producto', $item['codigo_producto'])
                    ->where('codigo_almacen', $almacen_destino)
                    ->orderBy('id_kardex', 'desc')
                    ->first();
                $costoPromedioActual = $ultimoKardex ? $ultimoKardex->costo_promedio : 0;
                
                $costos = $kardexService->calcularCostos(
                    $item['codigo_producto'], 
                    $almacen_destino,
                    $item['cantidad'], $costoPromedioActual,
                    0, $nuevo_saldo
                );

                DB::table('kardex')->insert([
                    'codigo_producto'      => $item['codigo_producto'],
                    'codigo_almacen'       => $almacen_destino,
                    'codigo_unidad_medida' => $unidad_medida,
                    'fecha_movimiento'     => now(),
                    'tipo_movimiento'      => 'INGRESO',
                    'documento'            => 'GUIA REMISION',
                    'numero_documento'     => $guia->numero_guia,
                    'cantidad_entrada'     => $item['cantidad'],
                    'costo_entrada'        => $costoPromedioActual,
                    'total_entrada'        => $item['cantidad'] * $costoPromedioActual,
                    'cantidad_salida'      => 0,
                    'costo_salida'         => 0,
                    'total_salida'         => 0,
                    'cantidad_saldo'       => $costos['cantidad_saldo'],
                    'costo_promedio'       => $costos['costo_promedio'],
                    'total_saldo'          => $costos['total_saldo'],
                    'lote'                 => $loteItem,
                    'observaciones'        => 'INGRESO POR EDICION DE GUIA',
                    'usuario_registro'     => Auth::id()
                ]);
            }

            DB::commit();
            return redirect()->route('guia_compras.show', $id)->with('success_ask', 'Guía de Remisión actualizada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Error al actualizar guía: ' . $e->getMessage());
        }
    }

    public function deshacerUbicacion(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $guia = GuiaRemisionCompra::findOrFail($id);
            
            if ($guia->estado !== 'UBICADA') {
                throw new \Exception("La guía no está en estado UBICADA.");
            }

            $kardexService = app(KardexService::class);
            $almacen_origen = 'ALM04';

            // Buscar los ingresos de transferencia asociados a esta guia
            $ingresosTransferencia = DB::table('kardex')
                ->where('numero_documento', $guia->numero_guia)
                ->where('documento', 'TRANSFERENCIA')
                ->where('tipo_movimiento', 'INGRESO')
                ->get();

            if($ingresosTransferencia->isEmpty()){
                throw new \Exception("No se encontraron movimientos de ubicación asociados a esta guía.");
            }

            // 1. Validar stock en los almacenes destino (saltar los que ya fueron extornados manualmente)
            foreach ($ingresosTransferencia as $ingreso) {
                if (strpos($ingreso->observaciones, '[EXTORNADO]') !== false) {
                    continue; // Ya fue extornado manualmente, saltar validación de stock
                }

                $loteIngresoVal = !empty($ingreso->lote) ? $ingreso->lote : null;
                $inventarioDestino = DB::table('inventario')
                    ->where('codigo_producto', $ingreso->codigo_producto)
                    ->where('codigo_almacen', $ingreso->codigo_almacen)
                    ->when($loteIngresoVal, function($q) use ($loteIngresoVal) {
                        $q->where('lote', $loteIngresoVal);
                    }, function($q) {
                        $q->whereNull('lote');
                    })
                    ->lockForUpdate()
                    ->first();

                if (!$inventarioDestino || round(floatval($inventarioDestino->stock_actual), 4) < round(floatval($ingreso->cantidad_entrada), 4)) {
                    throw new \Exception("Stock insuficiente en {$ingreso->codigo_almacen} para el producto {$ingreso->codigo_producto} (Lote: {$ingreso->lote}). Ya fue consumido o transferido, extorne los consumos posteriores primero.");
                }
            }

            // 2. Proceder a deshacer (extornar el ingreso en destino y extornar la salida de ALM04)
            foreach ($ingresosTransferencia as $ingreso) {
                if (strpos($ingreso->observaciones, '[EXTORNADO]') !== false) {
                    continue; // Ya fue extornado manualmente, no generar doble extorno
                }

                // a) Extornar el INGRESO del almacen_destino (es decir, hacer una salida)
                $loteIngreso = !empty($ingreso->lote) ? $ingreso->lote : null;
                $inventarioDestino = DB::table('inventario')
                    ->where('codigo_producto', $ingreso->codigo_producto)
                    ->where('codigo_almacen', $ingreso->codigo_almacen)
                    ->where('lote', $loteIngreso)
                    ->first();
                
                $nuevo_saldo_destino = $inventarioDestino->stock_actual - $ingreso->cantidad_entrada;
                
                DB::table('inventario')
                    ->where('id_inventario', $inventarioDestino->id_inventario)
                    ->update([
                        'stock_actual' => $nuevo_saldo_destino,
                        'fecha_ultimo_movimiento' => now(),
                        'usuario_ultimo_movimiento' => Auth::id()
                    ]);

                $ultimoKardexDestino = DB::table('kardex')
                    ->where('codigo_producto', $ingreso->codigo_producto)
                    ->where('codigo_almacen', $ingreso->codigo_almacen)
                    ->orderBy('id_kardex', 'desc')
                    ->first();
                $costoActualDestino = $ultimoKardexDestino ? $ultimoKardexDestino->costo_promedio : 0;

                $costosSalidaDest = $kardexService->calcularCostos(
                    $ingreso->codigo_producto, $ingreso->codigo_almacen,
                    0, 0,
                    $ingreso->cantidad_entrada, $nuevo_saldo_destino
                );

                DB::table('kardex')->insert([
                    'codigo_almacen'   => $ingreso->codigo_almacen,
                    'codigo_producto'  => $ingreso->codigo_producto,
                    'codigo_unidad_medida' => $ingreso->codigo_unidad_medida,
                    'fecha_movimiento'     => now(),
                    'tipo_movimiento'  => 'EXTORNO',
                    'documento'        => 'TRANSFERENCIA',
                    'numero_documento' => $guia->numero_guia,
                    'cantidad_entrada' => 0,
                    'costo_entrada'    => 0,
                    'total_entrada'    => 0,
                    'cantidad_salida'  => $ingreso->cantidad_entrada,
                    'costo_salida'     => $costoActualDestino,
                    'total_salida'     => $ingreso->cantidad_entrada * $costoActualDestino,
                    'cantidad_saldo'   => $costosSalidaDest['cantidad_saldo'],
                    'costo_promedio'   => $costosSalidaDest['costo_promedio'],
                    'total_saldo'      => $costosSalidaDest['total_saldo'],
                    'lote'             => $ingreso->lote,
                    'observaciones'    => "Extorno de Ubicación de Guía desde {$almacen_origen}",
                    'usuario_registro' => Auth::id()
                ]);

                // b) Extornar la SALIDA de ALM04 (es decir, hacer un ingreso a ALM04)
                $inventarioOrigen = DB::table('inventario')
                    ->where('codigo_producto', $ingreso->codigo_producto)
                    ->where('codigo_almacen', $almacen_origen)
                    ->where('lote', $loteIngreso)
                    ->lockForUpdate()
                    ->first();

                $stock_origen_anterior = $inventarioOrigen ? $inventarioOrigen->stock_actual : 0;
                $nuevo_saldo_origen = $stock_origen_anterior + $ingreso->cantidad_entrada;

                if ($inventarioOrigen) {
                    DB::table('inventario')
                        ->where('id_inventario', $inventarioOrigen->id_inventario)
                        ->update([
                            'stock_actual' => $nuevo_saldo_origen,
                            'fecha_ultimo_movimiento' => now(),
                            'usuario_ultimo_movimiento' => Auth::id()
                        ]);
                } else {
                    DB::table('inventario')->insert([
                        'codigo_producto' => $ingreso->codigo_producto,
                        'codigo_almacen' => $almacen_origen,
                        'lote' => $loteIngreso,
                        'stock_actual' => $nuevo_saldo_origen,
                        'fecha_ultimo_movimiento' => now(),
                        'usuario_ultimo_movimiento' => Auth::id()
                    ]);
                }

                $ultimoKardexOrigen = DB::table('kardex')
                    ->where('codigo_producto', $ingreso->codigo_producto)
                    ->where('codigo_almacen', $almacen_origen)
                    ->orderBy('id_kardex', 'desc')
                    ->first();
                $costoActualOrigen = $ultimoKardexOrigen ? $ultimoKardexOrigen->costo_promedio : 0;

                $costosIngresoOri = $kardexService->calcularCostos(
                    $ingreso->codigo_producto, $almacen_origen,
                    $ingreso->cantidad_entrada, $costoActualOrigen,
                    0, $nuevo_saldo_origen
                );

                DB::table('kardex')->insert([
                    'codigo_almacen'   => $almacen_origen,
                    'codigo_producto'  => $ingreso->codigo_producto,
                    'codigo_unidad_medida' => $ingreso->codigo_unidad_medida,
                    'fecha_movimiento' => now(),
                    'tipo_movimiento'  => 'EXTORNO',
                    'documento'        => 'TRANSFERENCIA',
                    'numero_documento' => $guia->numero_guia,
                    'cantidad_entrada' => $ingreso->cantidad_entrada,
                    'costo_entrada'    => $costoActualOrigen,
                    'total_entrada'    => $ingreso->cantidad_entrada * $costoActualOrigen,
                    'cantidad_salida'  => 0,
                    'costo_salida'     => 0,
                    'total_salida'     => 0,
                    'cantidad_saldo'   => $costosIngresoOri['cantidad_saldo'],
                    'costo_promedio'   => $costosIngresoOri['costo_promedio'],
                    'total_saldo'      => $costosIngresoOri['total_saldo'],
                    'lote'             => $ingreso->lote,
                    'observaciones'    => "Retorno a ALM04 por Extorno de Ubicación de Guía",
                    'usuario_registro' => Auth::id()
                ]);
            }

            // Cambiar estado a RECIBIDA
            $guia->update(['estado' => 'RECIBIDA']);
            DB::commit();

            return redirect()->back()->with('success_ask', 'Ubicación deshecha correctamente. La guía regresó a RECIBIDA y el stock volvió a ALM04.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al deshacer ubicación: ' . $e->getMessage());
        }
    }
}