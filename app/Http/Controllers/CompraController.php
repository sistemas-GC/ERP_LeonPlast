<?php

namespace App\Http\Controllers;

use App\Models\{Compra, DetalleCompra, Proveedor, Producto, Almacen, UnidadMedida};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Auth};

class CompraController extends Controller
{
    public function index(Request $request) {
        $fecha_desde = $request->input('fecha_desde', now()->startOfMonth()->toDateString());
        $fecha_hasta = $request->input('fecha_hasta', now()->endOfMonth()->toDateString());

        $query = Compra::with(['datosProveedor', 'creador'])
            ->whereDate('fecha_compra', '>=', $fecha_desde)
            ->whereDate('fecha_compra', '<=', $fecha_hasta);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('numero_documento', 'LIKE', "%{$request->search}%")
                  ->orWhere('proveedor', 'LIKE', "%{$request->search}%")
                  ->orWhere('serie_documento', 'LIKE', "%{$request->search}%");
            });
        }



        $compras = $query->orderBy('fecha_compra', 'desc')->paginate(15);
        $compras->appends($request->all());

        return view('compras.index', compact('compras', 'fecha_desde', 'fecha_hasta'));
    }

    public function create() 
    {
        $proveedores = Proveedor::where('activo', 1)->get();
        $almacenes = Almacen::where('activo', 1)->get();
        $unidades_medida = UnidadMedida::where('estado', 1)->get();
        $guiasPendientes = \App\Models\GuiaRemisionCompra::whereIn('estado', ['RECIBIDA', 'UBICADA'])
            ->whereNull('id_compra')
            ->get();
        $tipos_comprobante = \App\Models\ParametroSistema::where('categoria', 'TIPO_COMPROBANTE')->get();

        return view('compras.create', compact('proveedores', 'almacenes', 'unidades_medida', 'guiasPendientes', 'tipos_comprobante'));
    }

    public function getGuiaAjax($id) 
    {
        $guia = \App\Models\GuiaRemisionCompra::with(['detalles.producto', 'datosProveedor'])->findOrFail($id);
        return response()->json($guia);
    }

    public function getGuiasMultiAjax(Request $request) 
    {
        $ids = $request->input('ids', []);
        $guias = \App\Models\GuiaRemisionCompra::with(['detalles.producto', 'datosProveedor'])
            ->whereIn('id_guia', $ids)
            ->get();
        return response()->json($guias);
    }

    public function show($id)
    {
        // Cargamos la compra con sus relaciones para evitar consultas extra en la vista
        $compra = Compra::with(['datosProveedor', 'detalles.producto', 'cuotas', 'creador'])
            ->findOrFail($id);

        return view('compras.show', compact('compra'));
    }

    public function store(Request $request) {
        if ($request->filled('numero_documento') && ctype_digit(trim($request->numero_documento))) {
            $request->merge([
                'numero_documento' => str_pad(trim($request->numero_documento), 8, '0', STR_PAD_LEFT),
            ]);
        }
        if ($request->filled('serie_documento')) {
            $request->merge([
                'serie_documento' => strtoupper(trim($request->serie_documento)),
            ]);
        }

        $request->validate([
            'tipo_documento' => 'required|string|max:50',
            'serie_documento' => 'required|string|max:20',
            'numero_documento' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('compras')->where(function ($query) use ($request) {
                    return $query->where('ruc_proveedor', $request->ruc_proveedor)
                                 ->where('serie_documento', strtoupper($request->serie_documento))
                                 ->where('tipo_documento', $request->tipo_documento)
                                 ->where('estado', '!=', 'CANCELADA');
                })
            ],
            'fecha_compra' => 'required|date',
            'condicion_pago' => 'nullable|in:CONTADO,CREDITO',
            'fecha_vencimiento' => 'nullable|date',
            'cuotas' => 'nullable|array',
            'cuotas.*.fecha_vencimiento' => 'required_with:cuotas|date',
            'cuotas.*.monto' => 'required_with:cuotas|numeric|min:0.01',
            'ruc_proveedor' => 'required|string|max:20|exists:proveedores,ruc',
            'productos' => 'required|array|min:1',
            'productos.*.codigo' => 'required|string|max:50|exists:producto,codigo',
            'productos.*.cantidad' => 'required|numeric|min:0.01',
            'productos.*.precio' => 'required|numeric|decimal:0,9|min:0',
            'productos.*.codigo_almacen' => 'required|string|max:20',
            'productos.*.codigo_unidad_medida' => 'nullable|string|max:10',
            'productos.*.lote' => 'nullable|string|max:50',
            'productos.*.fecha_vencimiento' => 'nullable|date',
            'moneda' => 'required|in:PEN,USD',
            'tipo_cambio' => 'nullable|numeric|min:0.001',
            'ids_guias' => 'nullable|array',
            'ids_guias.*' => 'exists:guia_remision_compras,id_guia'
        ]);

        try {
            DB::beginTransaction();

            // Validación de cuotas al crédito
            $condicionPago = $request->input('condicion_pago', 'CONTADO');
            $fechaVencimiento = $request->fecha_vencimiento;

            if ($condicionPago === 'CREDITO') {
                if (empty($request->cuotas) || !is_array($request->cuotas) || count($request->cuotas) === 0) {
                    throw new \Exception("Para compras al crédito debe registrar al menos una cuota con su fecha de vencimiento y monto.");
                }

                $sumaCuotas = 0;
                foreach ($request->cuotas as $c) {
                    $sumaCuotas += floatval($c['monto'] ?? 0);
                }

                $totalGeneral = floatval($request->total_general);
                if (abs($sumaCuotas - $totalGeneral) > 0.05) {
                    throw new \Exception("La suma de las cuotas (" . number_format($sumaCuotas, 2) . ") no coincide con el importe total de la factura (" . number_format($totalGeneral, 2) . "). Diferencia: " . number_format(abs($sumaCuotas - $totalGeneral), 2));
                }

                if (empty($fechaVencimiento)) {
                    $fechas = array_column($request->cuotas, 'fecha_vencimiento');
                    rsort($fechas);
                    $fechaVencimiento = $fechas[0] ?? $request->fecha_compra;
                }
            } else {
                $fechaVencimiento = $request->fecha_compra;
            }

            // G1: Validación unique dentro de la transacción para evitar race condition
            $dup = Compra::where('ruc_proveedor', $request->ruc_proveedor)
                ->where('serie_documento', strtoupper($request->serie_documento))
                ->where('tipo_documento', $request->tipo_documento)
                ->where('numero_documento', $request->numero_documento)
                ->where('estado', '!=', 'CANCELADA')
                ->lockForUpdate()
                ->exists();
            if ($dup) {
                throw new \Exception("Ya existe una compra con el documento {$request->tipo_documento} {$request->serie_documento}-{$request->numero_documento} para este proveedor.");
            }

            $prov = Proveedor::where('ruc', $request->ruc_proveedor)->firstOrFail();

            $estadoCompra = !empty($request->ids_guias) ? 'RECIBIDA' : 'PENDIENTE';

            $compraData = [
                'tipo_documento'   => $request->tipo_documento,
                'serie_documento'  => strtoupper($request->serie_documento),
                'numero_documento' => $request->numero_documento,
                'proveedor'        => $prov->razon_social,
                'ruc_proveedor'    => $request->ruc_proveedor,
                'fecha_compra'     => $request->fecha_compra,
                'condicion_pago'   => $condicionPago,
                'fecha_vencimiento'=> $fechaVencimiento,
                'subtotal'         => $request->total_subtotal,
                'igv'              => $request->total_impuestos,
                'total'            => $request->total_general,
                'moneda'           => $request->moneda,
                'tipo_cambio'      => $request->moneda === 'USD' ? $request->tipo_cambio : 1.000,
                'estado'           => $estadoCompra,
                'usuario_creacion' => Auth::id() ?? 1
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('compras', 'igv_incluido')) {
                $compraData['igv_incluido'] = $request->has('igv_incluido') ? 1 : 0;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('compras', 'afecto_igv')) {
                $compraData['afecto_igv'] = !$request->has('inafecto_igv');
            }

            $compra = Compra::create($compraData);

            if ($condicionPago === 'CREDITO' && !empty($request->cuotas)) {
                foreach ($request->cuotas as $idx => $cuota) {
                    \App\Models\CompraCuota::create([
                        'id_compra' => $compra->id_compra,
                        'numero_cuota' => $cuota['numero_cuota'] ?? ($idx + 1),
                        'fecha_vencimiento' => $cuota['fecha_vencimiento'],
                        'monto' => floatval($cuota['monto']),
                    ]);
                }
            }

            if (!empty($request->ids_guias)) {
                \App\Models\GuiaRemisionCompra::whereIn('id_guia', $request->ids_guias)
                    ->where('estado', 'RECIBIDA')
                    ->update([
                        'id_compra' => $compra->id_compra,
                        'estado' => 'FACTURADA'
                    ]);

                \App\Models\GuiaRemisionCompra::whereIn('id_guia', $request->ids_guias)
                    ->where('estado', '!=', 'RECIBIDA')
                    ->update([
                        'id_compra' => $compra->id_compra
                    ]);
            }

            $isAfecto = !$request->has('inafecto_igv');
            $igv_incluido = $request->has('igv_incluido');

            $codigosProductos = collect($request->productos)->pluck('codigo')->unique()->values()->toArray();
            $productosIndexados = Producto::whereIn('codigo', $codigosProductos)->get()->keyBy('codigo');

            foreach ($request->productos as $item) {
                $prod = $productosIndexados[$item['codigo']] ?? null;
                $cant = floatval($item['cantidad']) > 0 ? floatval($item['cantidad']) : 1;
                
                // Si el usuario especificó o ajustó un subtotal de fila, respetarlo
                if (isset($item['subtotal']) && is_numeric($item['subtotal']) && floatval($item['subtotal']) > 0) {
                    $sub_row = floatval($item['subtotal']);
                } else {
                    $sub_row = $cant * floatval($item['precio']);
                }

                if ($isAfecto && $igv_incluido) {
                    $sub = round($sub_row / 1.18, 2);
                    $igv_item = round($sub_row - $sub, 2);
                    $total_item = $sub_row;
                    $precio_unitario = $sub / $cant;
                } elseif ($isAfecto) {
                    $sub = round($sub_row, 2);
                    $igv_item = round($sub * 0.18, 2);
                    $total_item = $sub + $igv_item;
                    $precio_unitario = $sub / $cant;
                } else { // Inafecto
                    $sub = round($sub_row, 2);
                    $igv_item = 0;
                    $total_item = $sub;
                    $precio_unitario = $sub / $cant;
                }

                DetalleCompra::create([
                    'id_compra' => $compra->id_compra,
                    'codigo_producto' => $item['codigo'],
                    'descripcion_producto' => $prod->descripcion ?? '',
                    'cantidad' => $cant,
                    'precio_unitario' => $precio_unitario,
                    'codigo_unidad_medida' => $item['codigo_unidad_medida'] ?? null,
                    'subtotal' => $sub,
                    'igv' => $igv_item,
                    'total' => $total_item,
                    'codigo_almacen' => $item['codigo_almacen'],
                    'lote' => $item['lote'] ?? null,
                    'fecha_vencimiento' => $item['fecha_vencimiento'] ?? null
                ]);
            }

            if (!empty($request->ids_guias)) {
                $kardexService = app(\App\Services\KardexService::class);
                $guiasToUpdate = \App\Models\GuiaRemisionCompra::whereIn('id_guia', $request->ids_guias)->get();
                $recalcularPendientes = [];
                foreach ($guiasToUpdate as $guia) {
                    foreach ($request->productos as $item) {
                        $cant = floatval($item['cantidad']) > 0 ? floatval($item['cantidad']) : 1;
                        if (isset($item['subtotal']) && is_numeric($item['subtotal']) && floatval($item['subtotal']) > 0) {
                            $sub_row = floatval($item['subtotal']);
                        } else {
                            $sub_row = $cant * floatval($item['precio']);
                        }
                        $precio_unitario = ($isAfecto && $igv_incluido) ? ($sub_row / 1.18 / $cant) : ($sub_row / $cant);
                        $tc = $request->moneda === 'USD' ? $request->tipo_cambio : 1.000;
                        $costo_kardex_pen = $precio_unitario * $tc;

                        DB::table('kardex')
                            ->where('numero_documento', $guia->numero_guia)
                            ->where('tipo_movimiento', 'INGRESO')
                            ->where('codigo_producto', $item['codigo'])
                            ->update(['costo_entrada' => $costo_kardex_pen]);

                        $almacenesAfectados = DB::table('kardex')
                            ->where('numero_documento', $guia->numero_guia)
                            ->where('codigo_producto', $item['codigo'])
                            ->pluck('codigo_almacen')
                            ->unique();

                        foreach ($almacenesAfectados as $almacen) {
                            $key = $item['codigo'] . '|' . $almacen;
                            $recalcularPendientes[$key] = ['codigo' => $item['codigo'], 'almacen' => $almacen];
                        }
                    }
                }
                foreach ($recalcularPendientes as $rp) {
                    $kardexService->recalcular($rp['codigo'], $rp['almacen']);
                }
            }

            DB::commit();

            return redirect()->route('compras.index')->with('success', 'Compra registrada y Kardex actualizado.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function edit($id) {
        $compra = Compra::with(['detalles', 'cuotas'])->findOrFail($id);
        $proveedores = Proveedor::where('activo', 1)->get();
        $almacenes = Almacen::where('activo', 1)->get();
        $unidades_medida = UnidadMedida::where('estado', 1)->get();
        $tipos_comprobante = \App\Models\ParametroSistema::where('categoria', 'TIPO_COMPROBANTE')->get();
        return view('compras.edit', compact('compra', 'proveedores', 'almacenes', 'unidades_medida', 'tipos_comprobante'));
    }

    public function update(Request $request, $id) {
        if ($request->filled('numero_documento') && ctype_digit(trim($request->numero_documento))) {
            $request->merge([
                'numero_documento' => str_pad(trim($request->numero_documento), 8, '0', STR_PAD_LEFT),
            ]);
        }
        if ($request->filled('serie_documento')) {
            $request->merge([
                'serie_documento' => strtoupper(trim($request->serie_documento)),
            ]);
        }

        $request->validate([
            'tipo_documento' => 'required|string|max:50',
            'serie_documento' => 'required|string|max:20',
            'numero_documento' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('compras')->where(function ($query) use ($request) {
                    return $query->where('ruc_proveedor', $request->ruc_proveedor)
                                 ->where('serie_documento', strtoupper($request->serie_documento))
                                 ->where('tipo_documento', $request->tipo_documento)
                                 ->where('estado', '!=', 'CANCELADA');
                })->ignore($id, 'id_compra')
            ],
            'fecha_compra' => 'required|date',
            'condicion_pago' => 'nullable|in:CONTADO,CREDITO',
            'fecha_vencimiento' => 'nullable|date',
            'cuotas' => 'nullable|array',
            'cuotas.*.fecha_vencimiento' => 'required_with:cuotas|date',
            'cuotas.*.monto' => 'required_with:cuotas|numeric|min:0.01',
            'ruc_proveedor' => 'required|string|max:20|exists:proveedores,ruc',
            'productos' => 'required|array|min:1',
            'productos.*.codigo' => 'required|string|max:50',
            'productos.*.cantidad' => 'required|numeric|min:0.01',
            'productos.*.precio' => 'required|numeric|decimal:0,9|min:0',
            'productos.*.codigo_almacen' => 'required|string|max:20',
            'productos.*.codigo_unidad_medida' => 'nullable|string|max:10',
            'productos.*.lote' => 'nullable|string|max:50',
            'productos.*.fecha_vencimiento' => 'nullable|date',
            'moneda' => 'required|in:PEN,USD',
            'tipo_cambio' => 'nullable|numeric|min:0.001',
        ]);

        try {
            DB::beginTransaction();
            $compra = Compra::findOrFail($id);

            if ($compra->estado !== 'PENDIENTE') {
                return back()->with('error', 'Solo se pueden editar compras en estado PENDIENTE.');
            }

            // Validación de cuotas al crédito
            $condicionPago = $request->input('condicion_pago', 'CONTADO');
            $fechaVencimiento = $request->fecha_vencimiento;

            if ($condicionPago === 'CREDITO') {
                if (empty($request->cuotas) || !is_array($request->cuotas) || count($request->cuotas) === 0) {
                    throw new \Exception("Para compras al crédito debe registrar al menos una cuota con su fecha de vencimiento y monto.");
                }

                $sumaCuotas = 0;
                foreach ($request->cuotas as $c) {
                    $sumaCuotas += floatval($c['monto'] ?? 0);
                }

                $totalGeneral = floatval($request->total_general);
                if (abs($sumaCuotas - $totalGeneral) > 0.05) {
                    throw new \Exception("La suma de las cuotas (" . number_format($sumaCuotas, 2) . ") no coincide con el importe total de la factura (" . number_format($totalGeneral, 2) . "). Diferencia: " . number_format(abs($sumaCuotas - $totalGeneral), 2));
                }

                if (empty($fechaVencimiento)) {
                    $fechas = array_column($request->cuotas, 'fecha_vencimiento');
                    rsort($fechas);
                    $fechaVencimiento = $fechas[0] ?? $request->fecha_compra;
                }
            } else {
                $fechaVencimiento = $request->fecha_compra;
            }

            $prov = Proveedor::where('ruc', $request->ruc_proveedor)->firstOrFail();

            $updateData = [
                'tipo_documento' => $request->tipo_documento,
                'serie_documento' => strtoupper($request->serie_documento),
                'numero_documento' => $request->numero_documento,
                'fecha_compra' => $request->fecha_compra,
                'condicion_pago' => $condicionPago,
                'fecha_vencimiento' => $fechaVencimiento,
                'proveedor' => $prov->razon_social,
                'ruc_proveedor' => $request->ruc_proveedor,
                'subtotal' => $request->total_subtotal,
                'igv' => $request->total_impuestos,
                'total' => $request->total_general,
                'moneda' => $request->moneda,
                'tipo_cambio' => $request->moneda === 'USD' ? $request->tipo_cambio : 1.000,
                'usuario_aprobacion' => Auth::id() ?? 1,
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('compras', 'igv_incluido')) {
                $updateData['igv_incluido'] = $request->has('igv_incluido') ? 1 : 0;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('compras', 'afecto_igv')) {
                $updateData['afecto_igv'] = !$request->has('inafecto_igv');
            }

            $compra->update($updateData);

            // Sincronizar cuotas
            \App\Models\CompraCuota::where('id_compra', $id)->delete();
            if ($condicionPago === 'CREDITO' && !empty($request->cuotas)) {
                foreach ($request->cuotas as $idx => $cuota) {
                    \App\Models\CompraCuota::create([
                        'id_compra' => $id,
                        'numero_cuota' => $cuota['numero_cuota'] ?? ($idx + 1),
                        'fecha_vencimiento' => $cuota['fecha_vencimiento'],
                        'monto' => floatval($cuota['monto']),
                    ]);
                }
            }

            DetalleCompra::where('id_compra', $id)->delete();
            $isAfecto = !$request->has('inafecto_igv');
            $igv_incluido = $request->has('igv_incluido');
            $codigosProductos = collect($request->productos)->pluck('codigo')->unique()->values()->toArray();
            $productosIndexados = Producto::whereIn('codigo', $codigosProductos)->get()->keyBy('codigo');
            foreach ($request->productos as $item) {
                $prod = $productosIndexados[$item['codigo']] ?? null;
                $cant = floatval($item['cantidad']) > 0 ? floatval($item['cantidad']) : 1;
                
                // Si el usuario especificó o ajustó un subtotal de fila, respetarlo
                if (isset($item['subtotal']) && is_numeric($item['subtotal']) && floatval($item['subtotal']) > 0) {
                    $sub_row = floatval($item['subtotal']);
                } else {
                    $sub_row = $cant * floatval($item['precio']);
                }

                if ($isAfecto && $igv_incluido) {
                    $sub = round($sub_row / 1.18, 2);
                    $igv_item = round($sub_row - $sub, 2);
                    $total_item = $sub_row;
                    $precio_unitario = $sub / $cant;
                } elseif ($isAfecto) {
                    $sub = round($sub_row, 2);
                    $igv_item = round($sub * 0.18, 2);
                    $total_item = $sub + $igv_item;
                    $precio_unitario = $sub / $cant;
                } else { // Inafecto
                    $sub = round($sub_row, 2);
                    $igv_item = 0;
                    $total_item = $sub;
                    $precio_unitario = $sub / $cant;
                }

                DetalleCompra::create([
                    'id_compra' => $id,
                    'codigo_producto' => $item['codigo'],
                    'descripcion_producto' => $prod->descripcion ?? '',
                    'cantidad' => $cant,
                    'precio_unitario' => $precio_unitario,
                    'codigo_unidad_medida' => $item['codigo_unidad_medida'] ?? null,
                    'subtotal' => $sub,
                    'igv' => $igv_item,
                    'total' => $total_item,
                    'codigo_almacen' => $item['codigo_almacen'],
                    'lote' => $item['lote'] ?? null,
                    'fecha_vencimiento' => $item['fecha_vencimiento'] ?? null
                ]);
            }

            if ($compra->guias && $compra->guias->count() > 0) {
                $kardexService = app(\App\Services\KardexService::class);
                $recalcularPendientes = [];
                foreach ($compra->guias as $guia) {
                    foreach ($request->productos as $item) {
                        $cant = floatval($item['cantidad']) > 0 ? floatval($item['cantidad']) : 1;
                        if (isset($item['subtotal']) && is_numeric($item['subtotal']) && floatval($item['subtotal']) > 0) {
                            $sub_row = floatval($item['subtotal']);
                        } else {
                            $sub_row = $cant * floatval($item['precio']);
                        }
                        $precio_unitario = ($isAfecto && $igv_incluido) ? ($sub_row / 1.18 / $cant) : ($sub_row / $cant);
                        $tc = $request->moneda === 'USD' ? $request->tipo_cambio : 1.000;
                        $costo_kardex_pen = $precio_unitario * $tc;

                        DB::table('kardex')
                            ->where('numero_documento', $guia->numero_guia)
                            ->where('tipo_movimiento', 'INGRESO')
                            ->where('codigo_producto', $item['codigo'])
                            ->update(['costo_entrada' => $costo_kardex_pen]);

                        $almacenesAfectados = DB::table('kardex')
                            ->where('numero_documento', $guia->numero_guia)
                            ->where('codigo_producto', $item['codigo'])
                            ->pluck('codigo_almacen')
                            ->unique();

                        foreach ($almacenesAfectados as $almacen) {
                            $key = $item['codigo'] . '|' . $almacen;
                            $recalcularPendientes[$key] = ['codigo' => $item['codigo'], 'almacen' => $almacen];
                        }
                    }
                }
                foreach ($recalcularPendientes as $rp) {
                    $kardexService->recalcular($rp['codigo'], $rp['almacen']);
                }
            }

            DB::commit();

            return redirect()->route('compras.index')->with('success', 'Actualizado y Kardex recalculado.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function anular(Request $request, $id) {
        try {
            DB::beginTransaction();
            $compra = Compra::with('guias')->where('id_compra', $id)->lockForUpdate()->firstOrFail();

            if ($compra->estado !== 'PENDIENTE') {
                throw new \Exception('Solo se pueden anular compras en estado PENDIENTE.');
            }

            $guiasFacturadas = $compra->guias->whereIn('estado', ['FACTURADA', 'UBICADA']);
            if ($guiasFacturadas->count() > 0) {
                $nums = $guiasFacturadas->pluck('numero_guia')->implode(', ');
                throw new \Exception("No se puede anular la compra porque tiene guías vinculadas: {$nums}. Anule las guías primero.");
            }

            $compra->update([
                'estado' => 'CANCELADA',
                'motivo_anulacion' => $request->confirmacion ?? 'Anulación solicitada',
                'usuario_aprobacion' => Auth::id(),
            ]);
            DB::commit();
            return redirect()->route('compras.index')->with('success', 'Compra anulada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
}