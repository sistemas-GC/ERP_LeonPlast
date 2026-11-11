<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Merma, Producto, Almacen, ParametroSistema};
use Illuminate\Support\Facades\{DB, Auth};
use App\Services\KardexService;

class MermaController extends Controller
{
    public static function getNumeroDoc($merma) {
        $id = str_pad($merma->id_merma ?? $merma->id, 6, '0', STR_PAD_LEFT);
        $tipo = strtoupper($merma->tipo_merma ?? '');
        $motivo = strtolower($merma->motivo ?? '');

        if ($tipo === 'MOLIDO') {
            return 'MOLIDO-' . $id;
        }
        if ($tipo === 'LIMPIEZA' || $tipo === 'MIXTO' || strpos($motivo, 'limpieza') !== false) {
            return 'LIMPIEZA-' . $id;
        }
        if ($tipo === 'RECUPERABLE') {
            return 'RECICLAD-' . $id;
        }
        return 'MERMA-' . $id;
    }

    public function index(Request $request)
    {
        $query = Merma::with(['producto', 'almacen', 'usuarioRegistro', 'ordenProduccion']);
        
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('codigo_producto', 'like', "%{$request->search}%")
                  ->orWhere('descripcion_producto', 'like', "%{$request->search}%");
            });
        }

        if ($request->fecha) {
            $query->whereDate('fecha_merma', $request->fecha);
        }

        $mermas = $query->orderBy('fecha_merma', 'desc')->orderBy('created_at', 'desc')->paginate(10);
        return view('mermas.index', compact('mermas', 'request'));
    }

    public function opciones()
    {
        return view('mermas.opciones');
    }

    public function create(Request $request)
    {
        $tipo = $request->input('tipo');
        if (!in_array($tipo, ['pura', 'molido', 'limpieza', 'recuperado_maquina'])) {
            return redirect()->route('mermas.opciones')->with('error', 'Seleccione un tipo de merma vÃ¡lido.');
        }

        $almacenes = Almacen::where('activo', 1)->get();
        $trabajadores = DB::table('trabajador')->where('estado', 1)->orderBy('nombre')->get();
        
        $ordenes = DB::table('orden_produccion_global')
            ->where('activo', 1)
            ->select('idop', 'codigo_op', 'descripcion_producto_proceso', 'estado')
            ->orderBy('idop', 'desc')
            ->get();
            
        return view('mermas.create', compact('almacenes', 'ordenes', 'tipo', 'trabajadores'));
    }

    public function getProcesosPorOP(Request $request)
    {
        $idop = $request->idop;
        
        $procesos = DB::table('orden_proceso')
            ->where('idop', $idop)
            ->where('estado', 1)
            ->select('id', 'descripcion_proceso', 'estado_avance', 'observaciones')
            ->orderBy('secuencia')
            ->get();
            
        foreach ($procesos as $p) {
            $componentes = DB::table('componentes_orden_produccion_global')
                ->where('id_proceso', $p->id)
                ->where(function($query) {
                    $query->where('estado', 1)->orWhereNull('estado');
                })
                ->get(['descripcion_formula_produccion']);
                
            $formulas = $componentes->pluck('descripcion_formula_produccion')
                ->filter(function($val) { return !empty($val) && $val != 'N/A'; })
                ->unique();
                
            $extra = '';
            if ($formulas->count() > 0) {
                $extra = $formulas->implode(' / ');
            } elseif (!empty($p->observaciones)) {
                $extra = $p->observaciones;
            }
            
            $p->descripcion_completa = $p->descripcion_proceso . ' (' . $p->estado_avance . ')';
            if ($extra) {
                $p->descripcion_completa .= ' - ' . $extra;
            }
        }
            
        return response()->json($procesos);
    }

    public function getComponentesEnsamblado(Request $request)
    {
        $idop = $request->idop;
        $id_proceso = $request->id_proceso;
        $codigo_almacen = $request->codigo_almacen;

        $componentes = DB::table('componentes_orden_produccion_global as c')
            ->join('producto as p', 'c.codigo_producto', '=', 'p.codigo')
            ->join('inventario as i', function($join) use ($codigo_almacen) {
                $join->on('c.codigo_producto', '=', 'i.codigo_producto')
                     ->where('i.codigo_almacen', '=', $codigo_almacen)
                     ->where('i.stock_actual', '>', 0);
            })
            ->where('c.idop', $idop)
            ->where('c.id_proceso', $id_proceso)
            ->where('c.estado', 1)
            ->select(
                'c.codigo_producto',
                'p.descripcion',
                'i.stock_actual',
                'c.codigo_unidad_medida',
                DB::raw('SUM(c.cantidad) as cantidad_total')
            )
            ->groupBy('c.codigo_producto', 'p.descripcion', 'i.stock_actual', 'c.codigo_unidad_medida')
            ->get();

        return response()->json($componentes);
    }

    public function getProductosPorOP(Request $request)
    {
        $idop = $request->idop;
        $id_proceso = $request->id_proceso;
        
        $productos = DB::table('inventario')
            ->join('producto', 'inventario.codigo_producto', '=', 'producto.codigo')
            ->join('produccion_ingresos_proceso', function($join) use ($idop, $id_proceso) {
                $join->on('produccion_ingresos_proceso.codigo_producto_proceso', '=', 'inventario.codigo_producto')
                     ->on('produccion_ingresos_proceso.codigo_almacen', '=', 'inventario.codigo_almacen')
                     ->where('produccion_ingresos_proceso.idop', '=', $idop);
                
                if ($id_proceso) {
                    $join->where('produccion_ingresos_proceso.id_proceso', '=', $id_proceso);
                }
            })
            // Se elimina la restricciÃ³n de stock > 0 porque la merma descuenta de la materia prima, 
            // no del producto generado (el cual pudo haber sido transferido a otro almacÃ©n).
            ->select(
                'producto.codigo', 
                'producto.descripcion', 
                'inventario.codigo_almacen', 
                'inventario.stock_actual', 
                'inventario.costo_promedio'
            )
            ->distinct()
            ->get();

        return response()->json($productos);
    }

    public function store(Request $request, KardexService $kardexService)
    {
        $request->validate([
            'fecha_merma' => 'required|date',
            'id_orden_produccion' => 'required|integer',
            'codigo_producto' => 'required|exists:producto,codigo',
            'codigo_almacen' => 'required|exists:almacen,codigo_almacen',
            'cantidad_pura' => 'nullable|numeric|min:0',
            'cantidad_recuperada' => 'nullable|numeric|min:0',
            'motivo' => 'nullable|string|max:255',
            'hora_inicio' => 'nullable|date_format:H:i',
            'hora_fin' => 'nullable|date_format:H:i',
            'codigo_trabajador' => 'nullable|string|max:50',
            'es_ensamblado' => 'nullable|in:0,1',
            'componentes' => 'nullable|array'
        ]);

        $fechaMerma = $request->fecha_merma;
        $fechaMov = \Carbon\Carbon::parse($fechaMerma)->format('Y-m-d') . ' ' . now()->format('H:i:s');

        $es_ensamblado = $request->es_ensamblado === '1';

        if (!$es_ensamblado) {
            $pura = (float) $request->cantidad_pura;
            $recuperada = (float) $request->cantidad_recuperada;
            $cantidadTotalMerma = $pura + $recuperada;

            if ($cantidadTotalMerma <= 0) {
                return back()->with('error', 'Debe ingresar al menos una cantidad mayor a 0.');
            }
        } else {
            $hasMerma = false;
            if ($request->componentes) {
                foreach ($request->componentes as $cod => $cant) {
                    $p = (float)($cant['pura'] ?? 0);
                    $r = (float)($cant['recuperada'] ?? 0);
                    if ($p > 0 || $r > 0) $hasMerma = true;
                }
            }
            if (!$hasMerma) {
                return back()->with('error', 'Debe ingresar al menos una cantidad mayor a 0 en algÃºn componente.');
            }
        }

        if (str_starts_with($request->codigo_producto, 'REC-')) {
            return back()->with('error', 'No se puede registrar merma de un producto ya recuperado (REC-).');
        }

        try {
            DB::beginTransaction();
            if ($es_ensamblado) {
                $totalPuraGlobal = 0;
                $totalRecuperadaGlobal = 0;
                $totalValorSalidaGlobal = 0;
                $detallesKardex = [];

                $codigosComponentes = collect($request->componentes)->filter(fn($c) => ((float)($c['pura'] ?? 0) + (float)($c['recuperada'] ?? 0)) > 0)->keys()->toArray();
                $productosComponentes = Producto::whereIn('codigo', $codigosComponentes)->get()->keyBy('codigo');

                foreach ($request->componentes as $cod => $cant) {
                    $pura = (float)($cant['pura'] ?? 0);
                    $recuperada = (float)($cant['recuperada'] ?? 0);
                    $cantidadTotal = $pura + $recuperada;

                    if ($cantidadTotal <= 0) continue;

                    $compProducto = $productosComponentes[$cod] ?? Producto::findOrFail($cod);
                    $invs = DB::table('inventario')
                        ->where('codigo_producto', $cod)
                        ->where('codigo_almacen', $request->codigo_almacen)
                        ->where('stock_actual', '>', 0)
                        ->orderBy('fecha_vencimiento', 'asc')
                        ->orderBy('lote', 'asc')
                        ->lockForUpdate()
                        ->get();

                    $saldoRequerido = $cantidadTotal;
                    foreach ($invs as $inv) {
                        if ($saldoRequerido <= 0) break;

                        $consumoParcial = min($saldoRequerido, $inv->stock_actual);
                        $costoSalida = $inv->costo_promedio;
                        $totalSalida = round($consumoParcial * $costoSalida, 2);

                        $propPura = ($cantidadTotal > 0) ? ($pura / $cantidadTotal) * $consumoParcial : 0;
                        $propRec = ($cantidadTotal > 0) ? ($recuperada / $cantidadTotal) * $consumoParcial : 0;

                        $totalPuraGlobal += $propPura;
                        $totalRecuperadaGlobal += $propRec;
                        $totalValorSalidaGlobal += $totalSalida;

                        $detallesKardex[] = [
                            'codigo' => $cod,
                            'cantidad' => $consumoParcial,
                            'costo' => $costoSalida,
                            'total' => $totalSalida,
                            'inv' => $inv
                        ];
                        $saldoRequerido -= $consumoParcial;
                    }

                    if ($saldoRequerido > 0) {
                        throw new \Exception("Stock insuficiente del componente {$cod} ({$compProducto->descripcion}) en el almacÃ©n {$request->codigo_almacen}. Faltan {$saldoRequerido}.");
                    }
                }

                $cantidadTotalGlobal = $totalPuraGlobal + $totalRecuperadaGlobal;
                
                if ($cantidadTotalGlobal <= 0) {
                    throw new \Exception("Debe ingresar al menos una cantidad mayor a 0.");
                }

                $costoUnitarioGlobal = round($totalValorSalidaGlobal / $cantidadTotalGlobal, 6);

                $prodPrincipal = Producto::findOrFail($request->codigo_producto);
                $merma = Merma::create([
                    'id_orden_produccion' => $request->id_orden_produccion,
                    'codigo_producto' => $request->codigo_producto,
                    'descripcion_producto' => $prodPrincipal->descripcion,
                    'cantidad' => $cantidadTotalGlobal,
                    'costo_unitario' => $costoUnitarioGlobal,
                    'costo_total' => $totalValorSalidaGlobal,
                    'motivo' => $request->motivo,
                    'tipo_merma' => ($totalPuraGlobal > 0 && $totalRecuperadaGlobal > 0) ? 'MIXTO' : (($totalPuraGlobal > 0) ? 'PURA' : 'RECUPERABLE'),
                    'codigo_almacen' => $request->codigo_almacen,
                    'fecha_merma' => $fechaMerma,
                    'hora_inicio' => $request->hora_inicio,
                    'hora_fin' => $request->hora_fin,
                    'codigo_trabajador' => $request->codigo_trabajador,
                    'estado' => 'REGISTRADA',
                    'usuario_registro' => Auth::id()
                ]);

                $numeroDoc = self::getNumeroDoc($merma);

                foreach ($detallesKardex as $det) {
                    $nuevoStock = $det['inv']->stock_actual - $det['cantidad'];

                    DB::table('kardex')->insert([
                        'codigo_producto' => $det['codigo'],
                        'codigo_almacen' => $request->codigo_almacen,
                        'fecha_movimiento' => $fechaMov,
                        'tipo_movimiento' => 'SALIDA',
                        'documento' => 'LIMPIEZA',
                        'numero_documento' => $numeroDoc,
                        'cantidad_entrada' => 0,
                        'costo_entrada' => 0,
                        'total_entrada' => 0,
                        'cantidad_salida' => $det['cantidad'],
                        'costo_salida' => $det['costo'],
                        'total_salida' => $det['total'],
                        'cantidad_saldo' => $nuevoStock,
                        'costo_promedio' => $det['inv']->costo_promedio,
                        'total_saldo' => $nuevoStock * $det['inv']->costo_promedio,
                        'lote' => $det['inv']->lote,
                        'observaciones' => "SALIDA PROPORCIONAL POR LIMPIEZA OP-{$request->id_orden_produccion} MERMA $numeroDoc",
                        'usuario_registro' => Auth::id()
                    ]);

                    DB::table('inventario')
                        ->where('id_inventario', $det['inv']->id_inventario)
                        ->update([
                            'stock_actual' => $nuevoStock,
                            'fecha_ultimo_movimiento' => now(),
                            'usuario_ultimo_movimiento' => Auth::id()
                        ]);
                }

                if ($totalRecuperadaGlobal > 0) {
                    $param = ParametroSistema::where('codigo_parametro', 'PORCENTAJE_COSTO_RECICLADO')->lockForUpdate()->first();
                    $porcentaje = $param ? (float) $param->valor : 0.8;
                    
                    $costoRecicladoGlobal = $costoUnitarioGlobal * $porcentaje;
                    $totalEntradaRecGlobal = round($totalRecuperadaGlobal * $costoRecicladoGlobal, 2);

                    $codRecuperado = 'REC-' . $request->codigo_producto;

                    $prodRecuperado = Producto::firstOrCreate(
                        ['codigo' => $codRecuperado],
                        [
                            'descripcion' => 'RECUPERADO - ' . $prodPrincipal->descripcion,
                            'codigo_tipo_producto' => $prodPrincipal->codigo_tipo_producto,
                            'codigo_unidad_medida' => $prodPrincipal->codigo_unidad_medida,
                            'estado' => 1
                        ]
                    );

                    $invRec = DB::table('inventario')
                        ->where('codigo_producto', $codRecuperado)
                        ->where('codigo_almacen', $request->codigo_almacen)
                        ->lockForUpdate()
                        ->first();

                    if ($invRec) {
                        $nuevoStockRec = $invRec->stock_actual + $totalRecuperadaGlobal;
                        $nuevoTotalValor = ($invRec->stock_actual * $invRec->costo_promedio) + $totalEntradaRecGlobal;
                        $nuevoCostoPromedio = $nuevoStockRec > 0 ? round($nuevoTotalValor / $nuevoStockRec, 6) : 0;

                        DB::table('inventario')->where('id_inventario', $invRec->id_inventario)->update([
                            'stock_actual' => $nuevoStockRec,
                            'costo_promedio' => $nuevoCostoPromedio,
                            'fecha_ultimo_movimiento' => now(),
                            'usuario_ultimo_movimiento' => Auth::id()
                        ]);
                    } else {
                        $nuevoStockRec = $totalRecuperadaGlobal;
                        $nuevoCostoPromedio = round($totalEntradaRecGlobal / $totalRecuperadaGlobal, 6);

                        DB::table('inventario')->insert([
                            'codigo_producto' => $codRecuperado,
                            'codigo_almacen' => $request->codigo_almacen,
                            'stock_actual' => $nuevoStockRec,
                            'costo_promedio' => $nuevoCostoPromedio,
                            'fecha_ultimo_movimiento' => now(),
                            'usuario_ultimo_movimiento' => Auth::id()
                        ]);
                    }

                    DB::table('kardex')->insert([
                        'codigo_producto' => $codRecuperado,
                        'codigo_almacen' => $request->codigo_almacen,
                        'fecha_movimiento' => $fechaMov,
                        'tipo_movimiento' => 'INGRESO',
                        'documento' => 'RECICLADO DE LIMPIEZA',
                        'numero_documento' => $numeroDoc,
                        'cantidad_entrada' => $totalRecuperadaGlobal,
                        'costo_entrada' => $costoRecicladoGlobal,
                        'total_entrada' => $totalEntradaRecGlobal,
                        'cantidad_salida' => 0,
                        'costo_salida' => 0,
                        'total_salida' => 0,
                        'cantidad_saldo' => $nuevoStockRec,
                        'costo_promedio' => $nuevoCostoPromedio,
                        'total_saldo' => $nuevoStockRec * $nuevoCostoPromedio,
                        'observaciones' => "RECUPERACIÃ“N POR LIMPIEZA OP-{$request->id_orden_produccion} MERMA $numeroDoc",
                        'usuario_registro' => Auth::id()
                    ]);
                }

                DB::commit();
                return redirect()->route('mermas.index')->with('success', 'Merma de Ensamblado registrada exitosamente.');
            }

            // ================== FLUJO INYECTADO (ESTÃNDAR) ==================
            
            $productoOrigen = Producto::findOrFail($request->codigo_producto);

            // Obtener el proceso de la OP que generÃ³ este producto
            $procesoIngreso = DB::table('produccion_ingresos_proceso')
                ->where('idop', $request->id_orden_produccion)
                ->where('codigo_producto_proceso', $request->codigo_producto)
                ->first();

            if (!$procesoIngreso) {
                return back()->with('error', 'No se encontrÃ³ el proceso de producciÃ³n para este producto en la OP seleccionada.');
            }

            $cantidadPlanificadaPEP = (float) $procesoIngreso->cantidad;
            if ($cantidadPlanificadaPEP <= 0) {
                return back()->with('error', 'La cantidad planificada del proceso es invÃ¡lida.');
            }

            // Factor de consumo proporcional
            $factor = $cantidadTotalMerma / $cantidadPlanificadaPEP;

            // Obtener las materias primas del proceso (agrupadas para evitar duplicados en el Kardex)
            $componentes = DB::table('componentes_orden_produccion_global')
                ->where('idop', $request->id_orden_produccion)
                ->where('id_proceso', $procesoIngreso->id_proceso)
                ->whereIn('codigo_tipo_producto', ['MTP', 'MAT', 'INS'])
                ->where('estado', 1)
                ->selectRaw('codigo_producto, MAX(descripcion_producto) as descripcion_producto, SUM(cantidad) as cantidad')
                ->groupBy('codigo_producto')
                ->get();

            if ($componentes->isEmpty()) {
                return back()->with('error', 'No hay materias primas configuradas en este proceso para calcular el consumo de la merma.');
            }

            $costoTotalMerma = 0;

            // 1. Crear Merma Principal
            $merma = Merma::create([
                'id_orden_produccion' => $request->id_orden_produccion,
                'codigo_producto' => $request->codigo_producto,
                'descripcion_producto' => $productoOrigen->descripcion,
                'cantidad' => $cantidadTotalMerma,
                'costo_unitario' => 0, // Se actualizarÃ¡ al final
                'costo_total' => 0,
                'motivo' => $request->motivo,
                'tipo_merma' => ($pura > 0 && $recuperada > 0) ? 'MIXTO' : (($pura > 0) ? 'PURA' : 'RECUPERABLE'),
                'codigo_almacen' => $request->codigo_almacen,
                'fecha_merma' => $fechaMerma,
                'hora_inicio' => $request->hora_inicio,
                'hora_fin' => $request->hora_fin,
                'codigo_trabajador' => $request->codigo_trabajador,
                'estado' => 'REGISTRADA',
                'usuario_registro' => Auth::id()
            ]);

            $numeroDoc = self::getNumeroDoc($merma);

            // 2. Consumir cada componente (Materia Prima)
            foreach ($componentes as $comp) {
                $cantidadConsumir = round($comp->cantidad * $factor, 6);
                
                if ($cantidadConsumir <= 0) continue;

                $invs = DB::table('inventario')
                    ->where('codigo_producto', $comp->codigo_producto)
                    ->where('codigo_almacen', $request->codigo_almacen)
                    ->where('stock_actual', '>', 0)
                    ->orderBy('fecha_vencimiento', 'asc')
                    ->orderBy('lote', 'asc')
                    ->lockForUpdate()
                    ->get();

                $saldoRequerido = $cantidadConsumir;

                foreach ($invs as $inv) {
                    if ($saldoRequerido <= 0) break;

                    $consumoParcial = min($saldoRequerido, $inv->stock_actual);
                    $costoSalida = $inv->costo_promedio;
                    $totalSalida = round($consumoParcial * $costoSalida, 2);
                    $costoTotalMerma += $totalSalida;
                    
                    $nuevoStock = $inv->stock_actual - $consumoParcial;

                    // SALIDA de materia prima
                    DB::table('kardex')->insert([
                        'codigo_producto' => $comp->codigo_producto,
                        'codigo_almacen' => $request->codigo_almacen,
                        'fecha_movimiento' => $fechaMov,
                        'tipo_movimiento' => 'SALIDA',
                        'documento' => ($pura > 0 && $recuperada <= 0) ? 'MERMA PURA' : ($request->has('es_molido') ? 'RECUPERADO MOLIDO' : 'RECICLADO INYECCION'),
                        'numero_documento' => $numeroDoc,
                        'cantidad_entrada' => 0,
                        'costo_entrada' => 0,
                        'total_entrada' => 0,
                        'cantidad_salida' => $consumoParcial,
                        'costo_salida' => $costoSalida,
                        'total_salida' => $totalSalida,
                        'cantidad_saldo' => $nuevoStock,
                        'costo_promedio' => $inv->costo_promedio,
                        'total_saldo' => $nuevoStock * $inv->costo_promedio,
                        'lote' => $inv->lote,
                        'observaciones' => "CONSUMO VIRGEN POR MERMA OP-{$request->id_orden_produccion}",
                        'usuario_registro' => Auth::id()
                    ]);

                    DB::table('inventario')
                        ->where('id_inventario', $inv->id_inventario)
                        ->update([
                            'stock_actual' => $nuevoStock,
                            'fecha_ultimo_movimiento' => now(),
                            'usuario_ultimo_movimiento' => Auth::id()
                        ]);

                    $saldoRequerido -= $consumoParcial;
                }

                if ($saldoRequerido > 0) {
                    throw new \Exception("Stock insuficiente de materia prima {$comp->codigo_producto} ({$comp->descripcion_producto}) en el almacÃ©n {$request->codigo_almacen} para cubrir la merma. Faltan {$saldoRequerido}.");
                }
            }

            $costoUnitarioMerma = $cantidadTotalMerma > 0 ? round($costoTotalMerma / $cantidadTotalMerma, 6) : 0;

            // Actualizar costo de la merma
            $merma->update([
                'costo_unitario' => $costoUnitarioMerma,
                'costo_total' => $costoTotalMerma
            ]);

            // 3. ENTRADA de producto recuperado (si aplica)
            if ($recuperada > 0) {
                $param = ParametroSistema::where('codigo_parametro', 'PORCENTAJE_COSTO_RECICLADO')->lockForUpdate()->first();
                $porcentaje = $param ? (float) $param->valor : 0.8;
                
                $costoReciclado = $costoUnitarioMerma * $porcentaje;
                $totalEntradaRec = round($recuperada * $costoReciclado, 2);

                $esMolido = $request->has('es_molido');
                
                if ($esMolido) {
                    $partes = explode('-', $request->codigo_producto, 2);
                    $codigoSufijo = count($partes) > 1 ? $partes[1] : $request->codigo_producto;
                    $codRecuperado = 'MO07-' . $codigoSufijo;
                    
                    $descLimpia = str_replace(['CASCARA', 'COLADA'], '', $productoOrigen->descripcion);
                    $descLimpia = str_replace(['INYECTADO '], '', $descLimpia);
                    $descRecuperado = 'MOLIDO RECICLADO ' . trim($descLimpia);
                } else {
                    $codRecuperado = 'REC-' . $request->codigo_producto;
                    $descRecuperado = 'RECUPERADO - ' . $productoOrigen->descripcion;
                }

                $prodRecuperado = Producto::firstOrCreate(
                    ['codigo' => $codRecuperado],
                    [
                        'descripcion' => $descRecuperado,
                        'codigo_tipo_producto' => $productoOrigen->codigo_tipo_producto,
                        'codigo_unidad_medida' => $productoOrigen->codigo_unidad_medida,
                        'estado' => 1
                    ]
                );
                
                DB::table('inventario')->updateOrInsert(
                    ['codigo_producto' => $codRecuperado, 'codigo_almacen' => $request->codigo_almacen],
                    [
                        'stock_minimo' => 0,
                        'stock_maximo' => 0
                    ]
                );
                
                $invRec = DB::table('inventario')
                    ->where('codigo_producto', $codRecuperado)
                    ->where('codigo_almacen', $request->codigo_almacen)
                    ->lockForUpdate()
                    ->first();
                
                $saldoAnteriorRec = $invRec->stock_actual ?? 0;
                $costoPromAnteriorRec = $invRec->costo_promedio ?? 0;
                $nuevoSaldoRec = $saldoAnteriorRec + $recuperada;
                $nuevoTotalSaldoRec = ($saldoAnteriorRec * $costoPromAnteriorRec) + $totalEntradaRec;
                $nuevoCostoPromRec = $nuevoSaldoRec > 0 ? round($nuevoTotalSaldoRec / $nuevoSaldoRec, 6) : 0;

                DB::table('kardex')->insert([
                    'codigo_producto' => $codRecuperado,
                    'codigo_almacen' => $request->codigo_almacen,
                    'fecha_movimiento' => $fechaMov,
                    'tipo_movimiento' => 'INGRESO',
                    'documento' => $esMolido ? 'RECUPERADO MOLIDO' : 'RECICLADO INYECCION',
                    'numero_documento' => $numeroDoc,
                    'cantidad_entrada' => $recuperada,
                    'costo_entrada' => $costoReciclado,
                    'total_entrada' => $totalEntradaRec,
                    'cantidad_salida' => 0,
                    'costo_salida' => 0,
                    'total_salida' => 0,
                    'cantidad_saldo' => $nuevoSaldoRec,
                    'costo_promedio' => $nuevoCostoPromRec,
                    'total_saldo' => round($nuevoSaldoRec * $nuevoCostoPromRec, 6),
                    'observaciones' => "INGRESO DE MATERIAL RECUPERADO DE MERMA OP-{$request->id_orden_produccion}",
                    'usuario_registro' => Auth::id()
                ]);

                DB::table('inventario')
                    ->where('codigo_producto', $codRecuperado)
                    ->where('codigo_almacen', $request->codigo_almacen)
                    ->update([
                        'stock_actual' => $nuevoSaldoRec,
                        'costo_promedio' => $nuevoCostoPromRec,
                        'fecha_ultimo_movimiento' => now(),
                        'usuario_ultimo_movimiento' => Auth::id()
                    ]);
            }

            DB::commit();
            return redirect()->route('mermas.index')->with('success', 'Merma registrada exitosamente consumiendo materiales vÃ­rgenes de la OP.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al registrar merma: ' . $e->getMessage());
        }
    }

    public function anular($id)
    {
        try {
            DB::beginTransaction();
            
            $merma = Merma::findOrFail($id);
            
            if ($merma->estado === 'ANULADA') {
                return back()->with('error', 'La merma ya se encuentra anulada.');
            }
            
            $numeroDoc = self::getNumeroDoc($merma);
            
            $movimientos = DB::table('kardex')
                ->where('numero_documento', $numeroDoc)
                ->orderBy('id_kardex', 'desc')
                ->get();
                
            foreach ($movimientos as $mov) {
                $inv = DB::table('inventario')
                    ->where('codigo_producto', $mov->codigo_producto)
                    ->where('codigo_almacen', $mov->codigo_almacen)
                    ->lockForUpdate()
                    ->first();
                    
                if (!$inv) continue;
                
                if ($mov->tipo_movimiento === 'SALIDA') {
                    // Revertir salida -> sumar stock
                    $nuevoStock = $inv->stock_actual + $mov->cantidad_salida;
                } else {
                    // Revertir ingreso -> restar stock
                    $nuevoStock = $inv->stock_actual - $mov->cantidad_entrada;
                    if ($nuevoStock < 0) {
                        throw new \Exception("No se puede anular porque el stock del producto {$mov->codigo_producto} quedarÃ­a en negativo.");
                    }
                }
                
                // Registrar el movimiento de extorno
                DB::table('kardex')->insert([
                    'codigo_producto' => $mov->codigo_producto,
                    'codigo_almacen' => $mov->codigo_almacen,
                    'fecha_movimiento' => now(),
                    'tipo_movimiento' => 'EXTORNO',
                    'documento' => 'ANULACION MERMA',
                    'numero_documento' => 'ANUL-' . $numeroDoc,
                    'cantidad_entrada' => $mov->tipo_movimiento === 'SALIDA' ? $mov->cantidad_salida : 0,
                    'costo_entrada' => $mov->tipo_movimiento === 'SALIDA' ? $mov->costo_salida : 0,
                    'total_entrada' => $mov->tipo_movimiento === 'SALIDA' ? $mov->total_salida : 0,
                    'cantidad_salida' => $mov->tipo_movimiento === 'INGRESO' ? $mov->cantidad_entrada : 0,
                    'costo_salida' => $mov->tipo_movimiento === 'INGRESO' ? $mov->costo_entrada : 0,
                    'total_salida' => $mov->tipo_movimiento === 'INGRESO' ? $mov->total_entrada : 0,
                    'cantidad_saldo' => $nuevoStock,
                    'costo_promedio' => $inv->costo_promedio,
                    'total_saldo' => $nuevoStock * $inv->costo_promedio,
                    'observaciones' => "EXTORNO POR ANULACION DE MERMA OP-{$merma->id_orden_produccion}",
                    'usuario_registro' => Auth::id()
                ]);
                
                DB::table('inventario')
                    ->where('id_inventario', $inv->id_inventario)
                    ->update([
                        'stock_actual' => $nuevoStock,
                        'fecha_ultimo_movimiento' => now(),
                        'usuario_ultimo_movimiento' => Auth::id()
                    ]);
            }
            
            $merma->update(['estado' => 'ANULADA']);
            
            DB::commit();
            return redirect()->route('mermas.index')->with('success', 'La merma ha sido anulada y los movimientos de inventario han sido extornados.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al anular la merma: ' . $e->getMessage());
        }
    }

    public function detalle($id)
    {
        $merma = Merma::with(['ordenProduccion', 'trabajador'])->findOrFail($id);
        
        $numeroDoc = self::getNumeroDoc($merma);
        
        $movimientos = DB::table('kardex')
            ->where('numero_documento', $numeroDoc)
            ->where('tipo_movimiento', '!=', 'EXTORNO') // We skip extornos to only show what the merma initially did
            ->orderBy('id_kardex', 'asc')
            ->get();
            
        return view('mermas.detalle', compact('merma', 'movimientos'));
    }

    public function reportePdf(Request $request, $idop)
    {
        $op = DB::table('orden_produccion_global')->where('idop', $idop)->first();
        
        if (!$op) {
            return back()->with('error', 'Orden de ProducciÃ³n no encontrada.');
        }

        $mermas = Merma::with('trabajador')->where('id_orden_produccion', $idop)->get();

        $numeroDocs = $mermas->map(function($m) {
            return self::getNumeroDoc($m);
        });

        $kardexDocs = DB::table('kardex')
            ->whereIn('numero_documento', $numeroDocs)
            ->select('numero_documento', 'documento')
            ->get();

        $ingresos = DB::table('produccion_ingresos_proceso')
            ->where('idop', $idop)
            ->get();

        // Para obtener el operario y hora inicio de los ingresos, buscamos en los componentes del proceso
        $componentes = DB::table('componentes_orden_produccion_global')
            ->where('idop', $idop)
            ->select('id_proceso', 'descripcion_trabajador', 'hora_inicio', 'hora_fin', 'fecha_inicio', 'fecha_fin', 'codigo_formula_produccion', 'lote_produccion_pep', 'descripcion_producto', 'descripcion_centro_trabajo')
            ->get();
            
        $procesos = DB::table('orden_proceso')->where('idop', $idop)->get()->keyBy('id');

        if (empty($op->descripcion_centro_trabajo_produccion)) {
            $centroComp = $componentes->firstWhere('descripcion_centro_trabajo', '!=', null);
            if ($centroComp) {
                $op->descripcion_centro_trabajo_produccion = $centroComp->descripcion_centro_trabajo;
            }
        }

        $registros = collect();

        foreach ($mermas as $m) {
            $color = '-';
            $prod = DB::table('producto')->where('codigo', $m->codigo_producto)->first();
            if ($prod && $prod->codigo_color) {
                $colorDb = DB::table('color')->where('codigo', $prod->codigo_color)->first();
                $color = $colorDb ? $colorDb->descripcion : $prod->codigo_color;
            }

            $prefix = '[MERMA]';
            $motivoText = $m->tipo_merma;

            $numeroDoc = self::getNumeroDoc($m);
            $esLimpieza = $kardexDocs->where('numero_documento', $numeroDoc)
                ->whereIn('documento', ['LIMPIEZA', 'RECICLADO DE LIMPIEZA'])
                ->isNotEmpty();

            if ($esLimpieza) {
                $prefix = '[LIMPIEZA]';
                $motivoText = 'RECICLADO DE LIMPIEZA';
            } elseif ($m->tipo_merma === 'RECUPERABLE') {
                $prefix = '[RECICLADO]';
                $motivoText = 'RECICLADO INYECCION';
            } elseif ($m->tipo_merma === 'MOLIDO') {
                $prefix = '[MOLIDO]';
                $motivoText = 'RECUPERADO MOLIDO';
            } elseif ($m->tipo_merma === 'MIXTO' || strpos(strtolower($m->motivo ?? ''), 'limpieza') !== false) {
                $prefix = '[LIMPIEZA]';
                $motivoText = 'RECICLADO DE LIMPIEZA';
            }

            $finalMotivo = $motivoText . ($m->motivo ? ' - ' . $m->motivo : '');

            $registros->push((object)[
                'fecha' => $m->fecha_merma,
                'hora_inicio' => $m->hora_inicio,
                'hora_fin' => $m->hora_fin,
                'trabajador_nombre' => $m->trabajador->nombre ?? $m->codigo_trabajador,
                'cantidad' => $m->cantidad,
                'motivo' => $finalMotivo,
                'tipo' => 'MERMA',
                'prefix' => $prefix,
                'color' => $color,
                'timestamp' => \Carbon\Carbon::parse($m->fecha_merma . ' ' . ($m->hora_inicio ?? '00:00:00'))->timestamp
            ]);
        }

        foreach ($ingresos as $i) {
            $fechaObj = \Carbon\Carbon::parse($i->fecha_ingreso);
            $fecha = $fechaObj->format('Y-m-d');
            
            // Buscar datos del operario en los componentes del mismo proceso, idealmente cercanos en tiempo
            // Ya que hay mÃºltiples agrupaciones, usamos el componente que mÃ¡s se aproxime o el primero
            $comp = $componentes->where('lote_produccion_pep', $i->lote_produccion)->first();
            if (!$comp) {
                $comp = $componentes->where('id_proceso', $i->id_proceso)->first();
            }
            
            $hora_ini = $fechaObj->format('H:i'); 
            
            if ($comp && $comp->hora_inicio) {
                $hora_ini = \Carbon\Carbon::parse($comp->hora_inicio)->format('H:i');
            }
            
            if ($comp && $comp->hora_fin) {
                $hora_fin = \Carbon\Carbon::parse($comp->hora_fin)->format('H:i');
            } else {
                $hora_fin = \Carbon\Carbon::parse($i->fecha_ingreso)->addMinutes(5)->format('H:i');
            }
            
            if ($comp && $comp->fecha_inicio) {
                $fechaObj = \Carbon\Carbon::parse($comp->fecha_inicio . ' ' . ($comp->hora_inicio ?? '00:00:00'));
                $fecha = $fechaObj->format('Y-m-d');
            }

            $trabajador = $comp->descripcion_trabajador ?? 'Administrador';

            $color = '-';
            $prod = DB::table('producto')->where('codigo', $i->codigo_producto_proceso)->first();
            if ($prod && $prod->codigo_color) {
                $colorDb = DB::table('color')->where('codigo', $prod->codigo_color)->first();
                $color = $colorDb ? $colorDb->descripcion : $prod->codigo_color;
            }

            $procesoRel = $procesos->get($i->id_proceso);
            $tipoOp = 'INYECTADA';
            if ($procesoRel) {
                switch ($procesoRel->codigo_proceso) {
                    case '17': $tipoOp = 'TROQUELADA'; break;
                    case '10': $tipoOp = 'ENSAMBLADA'; break;
                    case '18': $tipoOp = 'MOLIDA'; break;
                    case '16': $tipoOp = 'MEZCLADA'; break;
                    case '15': $tipoOp = 'INYECTADA'; break;
                }
            }

            $registros->push((object)[
                'fecha' => $fecha,
                'hora_inicio' => $hora_ini,
                'hora_fin' => $hora_fin,
                'trabajador_nombre' => $trabajador,
                'cantidad' => $i->cantidad,
                'motivo' => 'PRODUCCION ' . $tipoOp . ' (' . $i->descripcion_producto_proceso . ')',
                'tipo' => 'INGRESO',
                'color' => $color,
                'timestamp' => $fechaObj->timestamp
            ]);
        }

        // Actividades manuales y tiempos
        $actividades = DB::table('componentes_orden_produccion_global')
            ->where('idop', $idop)
            ->where('estado', 1)
            ->whereIn('codigo_tipo_producto', ['ACT', 'MANUAL'])
            ->get();

        foreach ($actividades as $act) {
            $fechaObj = \Carbon\Carbon::parse($act->fecha_inicio . ' ' . $act->hora_inicio);
            $motivoAct = (!empty($act->numero_ot) ? '[OT: ' . $act->numero_ot . '] ' : '') . ($act->descripcion_producto ?? 'Registro Manual');
            if (!empty($act->observacion)) {
                $motivoAct .= ' (' . $act->observacion . ')';
            }
            $registros->push((object)[
                'fecha' => \Carbon\Carbon::parse($act->fecha_inicio)->format('Y-m-d'),
                'hora_inicio' => \Carbon\Carbon::parse($act->hora_inicio)->format('H:i'),
                'hora_fin' => \Carbon\Carbon::parse($act->hora_fin)->format('H:i'),
                'trabajador_nombre' => $act->descripcion_trabajador ?? '-',
                'cantidad' => $act->cantidad,
                'motivo' => $motivoAct,
                'tipo' => 'ACTIVIDAD',
                'color' => '-',
                'timestamp' => $fechaObj->timestamp
            ]);
        }

        // Ordenar cronolÃ³gicamente
        $registros = $registros->sortBy('timestamp')->values();

        $kilosPorColor = $registros->where('tipo', 'INGRESO')->sum('cantidad'); 

        $data = [
            'op' => $op,
            'registros' => $registros,
            'kilosPorColor' => $kilosPorColor
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('mermas.pdf.reporte', $data)->setPaper('a4', 'landscape');

        if ($request->has('download') && $request->input('download') == '1') {
            return $pdf->download('Reporte_Produccion_OP_'.$op->codigo_op.'.pdf');
        }

        return $pdf->stream('Reporte_Produccion_OP_'.$op->codigo_op.'.pdf', ['Attachment' => false]);
    }
}
