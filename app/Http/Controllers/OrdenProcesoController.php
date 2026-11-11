<?php

namespace App\Http\Controllers;

use App\Models\OrdenProduccion;
use App\Models\OrdenProceso;
use App\Models\ProcesoProduccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\ComponenteOrdenProduccion;

class OrdenProcesoController extends Controller
{
    public function index($idop)
    {
        $orden = OrdenProduccion::with('productoProceso')->where('idop', $idop)->where('activo', 1)->firstOrFail();
        
        $procesos = OrdenProceso::where('idop', $idop)
            ->where('estado', 1)
            ->orderBy('secuencia', 'asc')
            ->get();
            
        // Batch: load all componentes for all procesos in one query
        $procesoIds = $procesos->pluck('id')->toArray();
        $componentesPorProceso = DB::table('componentes_orden_produccion_global')
            ->whereIn('id_proceso', $procesoIds)
            ->where(function($query) {
                $query->where('estado', 1)->orWhereNull('estado');
            })
            ->get(['id_proceso', 'descripcion_producto', 'descripcion_color', 'descripcion_formula_produccion'])
            ->groupBy('id_proceso');

        // Batch: load all proceso descriptions in one query
        $codigoProcesos = $procesos->pluck('codigo_proceso')->unique()->toArray();
        $descPorCodigo = DB::table('proceso_produccion')
            ->whereIn('codigo', $codigoProcesos)
            ->pluck('descripcion', 'codigo');

        foreach ($procesos as $proceso) {
            $componentes = $componentesPorProceso->get($proceso->id, collect());
                
            $proceso->total_componentes = $componentes->count();
            
            $formulas = $componentes->pluck('descripcion_formula_produccion')
                ->filter(fn($val) => !empty($val) && $val != 'N/A')
                ->unique();
                
            $proceso->nombres_componentes = $formulas->count() > 0 ? $formulas->implode(' / ') : null;
                
            $proceso->proceso_desc = $descPorCodigo[$proceso->codigo_proceso] ?? $proceso->descripcion_proceso;
        }
        
        return view('produccion.procesos.index', compact('orden', 'procesos'));
    }

    public function create($idop)
    {
        $orden = OrdenProduccion::with('productoProceso.rutas')->where('idop', $idop)->where('activo', 1)->firstOrFail();
        
        if ($orden->productoProceso && $orden->productoProceso->rutas->count() > 0) {
            $cat_procesos = $orden->productoProceso->rutas;
        } else {
            $cat_procesos = ProcesoProduccion::where('estado', 1)->get();
        }
        
        return view('produccion.procesos.create', compact('orden', 'cat_procesos'));
    }

    public function store(Request $request, $idop)
    {
        $request->validate([
            'codigo_proceso' => 'required|string',
            'observaciones' => 'nullable|string',
        ]);

        try {
            $procesoRef = ProcesoProduccion::findOrFail($request->codigo_proceso);
            
            $orden = OrdenProduccion::with('productoProceso.rutas')->where('idop', $idop)->where('activo', 1)->firstOrFail();

            if ($orden->productoProceso && $orden->productoProceso->rutas->count() > 0) {
                if (!$orden->productoProceso->rutas->contains('codigo', $request->codigo_proceso)) {
                    throw new \Exception('El proceso seleccionado no pertenece a la ruta de producción de este producto.');
                }
            }

            $max_seq = OrdenProceso::where('idop', $idop)->where('estado', 1)->max('secuencia');
            $secuencia_nueva = $max_seq ? $max_seq + 10 : 10;
            
            OrdenProceso::create([
                'idop' => $idop,
                'secuencia' => $secuencia_nueva,
                'codigo_proceso' => $request->codigo_proceso,
                'descripcion_proceso' => $procesoRef->descripcion,
                'observaciones' => $request->observaciones,
                'estado' => 1,
                'estado_avance' => 'PENDIENTE',
                'fecha_inicio' => $orden->fecha // As derived from legacy code
            ]);
            
            return redirect()->route('ordenes.procesos.index', $idop)->with('success', 'Proceso registrado correctamente.');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $proceso = OrdenProceso::where('id', $id)->lockForUpdate()->firstOrFail();
            if ($proceso->estado_avance === 'COMPLETADO') {
                throw new \Exception("No se puede anular un proceso que ya está COMPLETADO.");
            }
            $orden_id = $proceso->idop;
            
            // 1. VALIDACIÓN DE BLOQUEO: Verificar si el PEP generado ya fue consumido
            $ingresos_generados = DB::table('produccion_ingresos_proceso')
                ->where('id_proceso', $id)
                ->whereIn('estado', ['PENDIENTE', 'APROBADO'])
                ->get();
                
            foreach ($ingresos_generados as $ing) {
                if ($ing->estado === 'APROBADO') {
                    $stock_actual = DB::table('inventario')
                        ->where('codigo_producto', $ing->codigo_producto_proceso)
                        ->where('lote', $ing->lote_produccion)
                        ->where('estado', 1)
                        ->value('stock_actual');
                        
                    if ($stock_actual !== null && round($stock_actual, 2) < round($ing->cantidad, 2)) {
                        throw new \Exception("El lote {$ing->lote_produccion} ya registra salidas. Anule consumos posteriores primero.");
                    }
                }
            }
            
            $numero_ref_prefijo = "OP-{$orden_id}-PROC-{$id}";
            $usuario_id = Auth::id();
            
            // 2. EXTORNO DE PRODUCTOS EN PROCESO (PEP)
            $todos_ingresos = DB::table('produccion_ingresos_proceso')
                ->where('id_proceso', $id)
                ->get();
                
            foreach ($todos_ingresos as $ing) {
                if ($ing->estado === 'APROBADO') {
                    DB::table('inventario')
                        ->where('codigo_producto', $ing->codigo_producto_proceso)
                        ->where('lote', $ing->lote_produccion)
                        ->update([
                            'stock_actual' => DB::raw("stock_actual - {$ing->cantidad}"),
                            'fecha_ultimo_movimiento' => now(),
                            'usuario_ultimo_movimiento' => $usuario_id
                        ]);
                        
                    $codigo_almacen = DB::table('inventario')
                        ->where('codigo_producto', $ing->codigo_producto_proceso)
                        ->where('lote', $ing->lote_produccion)
                        ->value('codigo_almacen');

                    $stockPEP = DB::table('inventario')
                        ->where('codigo_producto', $ing->codigo_producto_proceso)
                        ->where('lote', $ing->lote_produccion)
                        ->value('stock_actual') ?? 0;
                        
                    $movIdExt = DB::table('movimientos_inventario')->insertGetId([
                        'codigo_almacen' => $codigo_almacen,
                        'codigo_producto' => $ing->codigo_producto_proceso,
                        'lote' => $ing->lote_produccion,
                        'tipo_movimiento' => 'SALIDA',
                        'cantidad' => $ing->cantidad,
                        'costo_unitario' => 0,
                        'total' => 0,
                        'documento_referencia' => 'EXTORNO_PROD',
                        'numero_referencia' => $numero_ref_prefijo,
                        'idop' => $orden_id,
                        'observaciones' => 'Anulación de proceso',
                        'usuario_movimiento' => $usuario_id,
                        'fecha_movimiento' => now(),
                        'estado' => 1,
                        'tiene_kardex' => true
                    ]);

                    DB::table('kardex')->insert([
                        'codigo_almacen'       => $codigo_almacen,
                        'codigo_producto'      => $ing->codigo_producto_proceso,
                        'fecha_movimiento'     => now(),
                        'tipo_movimiento'      => 'EXTORNO',
                        'documento'            => 'EXTORNO_PROD',
                        'numero_documento'     => $numero_ref_prefijo,
                        'cantidad_entrada'     => 0,
                        'cantidad_salida'      => $ing->cantidad,
                        'cantidad_saldo'       => $stockPEP,
                        'codigo_referencia_movimiento' => $movIdExt,
                        'observaciones'        => "Anulación de PEP por anulación de proceso OP-{$orden_id} [ANULADO]",
                        'usuario_registro'     => $usuario_id
                    ]);

                    // Marcar el original como anulado
                    DB::table('kardex')
                        ->where('numero_documento', $numero_ref_prefijo)
                        ->where('tipo_movimiento', 'INGRESO')
                        ->where('documento', 'RECEPCION_PEP')
                        ->update([
                            'observaciones' => DB::raw("CONCAT(COALESCE(observaciones, ''), ' [ANULADO]')"),
                            'cantidad_saldo' => 0,
                            'total_saldo' => 0
                        ]);

                    DB::table('movimientos_inventario')
                        ->where('numero_referencia', $numero_ref_prefijo)
                        ->where('tipo_movimiento', 'INGRESO')
                        ->where('documento_referencia', 'PRODUCCION_PEP')
                        ->update([
                            'estado' => 0,
                            'observaciones' => DB::raw("CONCAT(COALESCE(observaciones, ''), ' [ANULADO]')")
                        ]);
                }
            }
            
            // 3. EXTORNO DE MATERIAS PRIMAS (MTP)
            $consumos = DB::table('movimientos_inventario')
                ->where('numero_referencia', 'LIKE', $numero_ref_prefijo . '%')
                ->where('tipo_movimiento', 'SALIDA')
                ->where('documento_referencia', 'PRODUCCION')
                ->where('estado', 1)
                ->get();
                
            $resumenDevoluciones = [];
            foreach ($consumos as $s) {
                // Devolver stock al inventario
                DB::table('inventario')
                    ->where('codigo_producto', $s->codigo_producto)
                    ->where('lote', $s->lote)
                    ->where('codigo_almacen', $s->codigo_almacen)
                    ->update([
                        'stock_actual' => DB::raw("stock_actual + {$s->cantidad}"),
                        'fecha_ultimo_movimiento' => now()
                    ]);
                    
                // Activar el lote si estaba inactivo
                DB::table('inventario')
                    ->where('codigo_producto', $s->codigo_producto)
                    ->where('lote', $s->lote)
                    ->where('codigo_almacen', $s->codigo_almacen)
                    ->where('stock_actual', '>', 0)
                    ->update([
                        'estado' => 1,
                        'fecha_ultimo_movimiento' => now(),
                        'usuario_ultimo_movimiento' => $usuario_id
                    ]);

                $stockDevuelto = DB::table('inventario')
                    ->where('codigo_producto', $s->codigo_producto)
                    ->where('lote', $s->lote)
                    ->where('codigo_almacen', $s->codigo_almacen)
                    ->value('stock_actual') ?? $s->cantidad;
                    
                // Registrar el ingreso por devolución
                $movDevId = DB::table('movimientos_inventario')->insertGetId([
                    'codigo_almacen' => $s->codigo_almacen,
                    'codigo_producto' => $s->codigo_producto,
                    'lote' => $s->lote,
                    'tipo_movimiento' => 'INGRESO',
                    'cantidad' => $s->cantidad,
                    'costo_unitario' => $s->costo_unitario,
                    'total' => ($s->cantidad * $s->costo_unitario),
                    'documento_referencia' => 'EXTORNO_CONS',
                    'numero_referencia' => $numero_ref_prefijo,
                    'idop' => $orden_id,
                    'observaciones' => 'Devolución por anulación de proceso',
                    'usuario_movimiento' => $usuario_id,
                    'fecha_movimiento' => now(),
                    'estado' => 1,
                    'tiene_kardex' => true
                ]);

                $key = $s->codigo_producto . '|' . $s->codigo_almacen;
                if (!isset($resumenDevoluciones[$key])) {
                    $resumenDevoluciones[$key] = [
                        'producto' => $s->codigo_producto,
                        'almacen' => $s->codigo_almacen,
                        'cantidad' => 0,
                        'primer_mov_id' => $movDevId,
                        'stock_final' => $stockDevuelto
                    ];
                }
                $resumenDevoluciones[$key]['cantidad'] += $s->cantidad;
            }

            foreach ($resumenDevoluciones as $dev) {
                $stockFinal = DB::table('inventario')
                    ->where('codigo_producto', $dev['producto'])
                    ->where('codigo_almacen', $dev['almacen'])
                    ->sum('stock_actual') ?? $dev['stock_final'];

                DB::table('kardex')->insert([
                    'codigo_almacen'       => $dev['almacen'],
                    'codigo_producto'      => $dev['producto'],
                    'fecha_movimiento'     => now(),
                    'tipo_movimiento'      => 'EXTORNO',
                    'documento'            => 'EXTORNO_CONS',
                    'numero_documento'     => $numero_ref_prefijo,
                    'cantidad_entrada'     => $dev['cantidad'],
                    'cantidad_salida'      => 0,
                    'cantidad_saldo'       => $stockFinal,
                    'codigo_referencia_movimiento' => $dev['primer_mov_id'],
                    'observaciones'        => "Devolución de MTP por anulación de proceso OP-{$orden_id}",
                    'usuario_registro'     => $usuario_id
                ]);

                // Marcar los consumos originales como anulados
                DB::table('kardex')
                    ->where('numero_documento', 'LIKE', $numero_ref_prefijo . '%')
                    ->where('tipo_movimiento', 'SALIDA')
                    ->where('documento', 'PRODUCCION')
                    ->update(['observaciones' => DB::raw("CONCAT(COALESCE(observaciones, ''), ' [ANULADO]')")]);
            }
            
            // 4. BAJA LÓGICA
            DB::table('componentes_orden_produccion_global')->where('id_proceso', $id)->update(['estado' => 0]);
            DB::table('produccion_ingresos_proceso')->where('id_proceso', $id)->update(['estado' => 'ANULADO']);
            $proceso->update(['estado' => 0]);
            
            DB::commit();
            return redirect()->route('ordenes.procesos.index', $orden_id)
                ->with('success', "Proceso anulado correctamente. Se revirtieron " . count($consumos) . " consumos de materia prima y el stock ha sido restaurado.");
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al anular: ' . $e->getMessage());
        }
    }

    public function ejecutar($idop, $id)
    {
        $orden = OrdenProduccion::where('idop', $idop)->firstOrFail();
        $proceso = OrdenProceso::where('id', $id)->firstOrFail();
        $proceso_desc = DB::table('proceso_produccion')->where('codigo', $proceso->codigo_proceso)->value('descripcion');
        $proceso->proceso_desc = $proceso_desc ?? $proceso->descripcion_proceso;

        $estado_proceso_actual = strtoupper(trim($proceso->estado_avance ?? 'PENDIENTE'));
        if (empty($estado_proceso_actual)) $estado_proceso_actual = 'PENDIENTE';

        $desc_proceso_upper = strtoupper(DB::table('proceso_produccion')->where('codigo', $proceso->codigo_proceso)->value('descripcion') ?? '');
        $es_actividad = ($proceso->codigo_proceso == '1');
        $es_mezclado = ($proceso->codigo_proceso == '16') || str_contains($desc_proceso_upper, 'MEZCLADO');
        $es_inyectado = ($proceso->codigo_proceso == '15') || str_contains($desc_proceso_upper, 'INYECTADO');
        $es_ensamblado_manual = str_contains($desc_proceso_upper, 'ENSAMBLADO MANUAL');
        $es_ensamblado = ($proceso->codigo_proceso == '10') || (str_contains($desc_proceso_upper, 'ENSAMBLADO') && !$es_ensamblado_manual);
        $es_encartonado = ($proceso->codigo_proceso == '8') || str_contains($desc_proceso_upper, 'ENCARTONADO');
        $es_embolsado = ($proceso->codigo_proceso == '5') || str_contains($desc_proceso_upper, 'EMBOLSADO');
        $es_molido = ($proceso->codigo_proceso == '18') || str_contains($desc_proceso_upper, 'MOLIDO');
        $es_troquelado = ($proceso->codigo_proceso == '17') || str_contains($desc_proceso_upper, 'TROQUELADO');
        $es_horneado = ($proceso->codigo_proceso == '13') || str_contains($desc_proceso_upper, 'HORNEADO');
        $peso_inicial = $orden->cantidad;
        $tara = $orden->tara;
        $peso_neto = max(0, floatval($peso_inicial) - floatval($tara));

        $proceso_nombre = strtoupper(trim($proceso->proceso_desc));
        $formulas_disponibles = DB::table('formula_produccion')
            ->select('codigo', 'descripcion')
            ->where('estado', 1)
            ->where(function($query) use ($proceso_nombre) {
                // Filtro principal por descripción
                $query->where('descripcion', 'LIKE', "%{$proceso_nombre}%");
                
                // Mapeo de prefijos comunes
                if (str_contains($proceso_nombre, 'MEZCLADO')) {
                    $query->orWhere('codigo', 'LIKE', 'MZ%');
                } elseif (str_contains($proceso_nombre, 'INYECTADO')) {
                    $query->orWhere('codigo', 'LIKE', 'INY%')
                          ->orWhere('codigo', 'LIKE', 'CA%'); // Cascara
                } elseif (str_contains($proceso_nombre, 'HORNEADO')) {
                    $query->orWhere('codigo', 'LIKE', 'C1%');
                } elseif (str_contains($proceso_nombre, 'TROQUELADO')) {
                    $query->orWhere('codigo', 'LIKE', 'C2%');
                } elseif (str_contains($proceso_nombre, 'ENSAMBLADO')) {
                    $query->orWhere(function($q) {
                        $q->where('codigo', 'LIKE', 'EN%')
                          ->where('codigo', 'NOT LIKE', 'EN07-Z%');
                    });
                } elseif (str_contains($proceso_nombre, 'ENCARTONADO')) {
                    $query->orWhere('codigo', 'LIKE', 'ENC%')
                          ->orWhere('codigo', 'LIKE', 'EN07-Z%');
                } elseif (str_contains($proceso_nombre, 'EMBOLSADO')) {
                    $query->orWhere('codigo', 'LIKE', 'EMB%')
                          ->orWhere('codigo', 'LIKE', 'GA07-Z%')
                          ->orWhere('descripcion', 'LIKE', '%EMBOLS%');
                } elseif (str_contains($proceso_nombre, 'MOLIDO')) {
                    $query->orWhere('codigo', 'LIKE', 'MO%');
                }
            })
            ->orderBy('codigo')
            ->get();

        if ($es_ensamblado || $es_ensamblado_manual) {
            $almacen_ensamblado = DB::table('proceso_produccion')->where('codigo', $proceso->codigo_proceso)->value('codigo_almacen');
            
            // 1. Obtener lotes producidos en esta OP
            $lotes_op = DB::table('produccion_ingresos_proceso')
                ->where('idop', $idop)
                ->pluck('lote_produccion')
                ->toArray();
                
            // 2. Buscar stock de esos lotes en el almacén de ensamblado
            $cascaras_disponibles = collect();
            if (!empty($lotes_op)) {
                $cascaras_disponibles = DB::table('inventario as i')
                    ->join('produccion_ingresos_proceso as p', 'i.lote', '=', 'p.lote_produccion')
                    ->where('i.codigo_almacen', $almacen_ensamblado)
                    ->where('i.stock_actual', '>', 0)
                    ->whereIn('i.lote', $lotes_op)
                    ->select('i.codigo_producto', 'p.codigo_molde')
                    ->groupBy('i.codigo_producto', 'p.codigo_molde')
                    ->get();
            }

            // 3. Filtrar fórmulas
            $formulas_disponibles = $formulas_disponibles->filter(function($f) use ($cascaras_disponibles) {
                if ($cascaras_disponibles->isEmpty()) return false;

                $materiales = DB::table('composicion_formula')
                    ->where('codigo_formula', $f->codigo)
                    ->get();
                
                $tieneCascaraValida = false;
                foreach ($materiales as $m) {
                    if (str_starts_with($m->codigo_producto, 'CA')) {
                        foreach ($cascaras_disponibles as $c) {
                            $matchMolde = empty($m->codigo_molde) || $m->codigo_molde === $c->codigo_molde;
                            if ($c->codigo_producto === $m->codigo_producto && $matchMolde) {
                                $tieneCascaraValida = true;
                                break 2;
                            }
                        }
                    }
                }
                return $tieneCascaraValida;
            })->values();
        }

        $inventario_merma_clip = collect();
        $inventario_merma_cascara = collect();
        $inventario_merma_carton = collect();
        if ($es_ensamblado || $es_ensamblado_manual) {
            $almacen_ensamblado = DB::table('proceso_produccion')->where('codigo', $proceso->codigo_proceso)->value('codigo_almacen');
            
            $stock_ensamblado = DB::table('inventario as i')
                ->join('producto as p', 'i.codigo_producto', '=', 'p.codigo')
                ->leftJoin('unidad_medida as u', 'p.codigo_unidad_medida', '=', 'u.codigo')
                ->where('i.codigo_almacen', $almacen_ensamblado)
                ->where('i.stock_actual', '>', 0)
                ->select(
                    'i.codigo_producto', 
                    'p.descripcion', 
                    'p.codigo_tipo_producto', 
                    'u.descripcion as unidad_medida',
                    DB::raw('SUM(i.stock_actual) as stock_total')
                )
                ->groupBy('i.codigo_producto', 'p.descripcion', 'p.codigo_tipo_producto', 'u.descripcion')
                ->get();
                
            $inventario_merma_clip = $stock_ensamblado->filter(function($i) {
                return str_contains(strtoupper($i->descripcion), 'CLIP') && !str_starts_with($i->codigo_producto, 'CA');
            })->values();

            $inventario_merma_cascara = $stock_ensamblado->filter(function($i) {
                return str_starts_with($i->codigo_producto, 'CA') || str_contains(strtoupper($i->descripcion), 'CASCARA');
            })->values();
        }

        $ops_disponibles_encartonado = [];
        if ($es_encartonado) {
            $almacen_encartonado = DB::table('proceso_produccion')->where('codigo', $proceso->codigo_proceso)->value('codigo_almacen') ?? 'PS5E';
            
            $ops_disponibles_encartonado = $this->queryOpsDisponiblesEncartonado($almacen_encartonado);

            $stock_encartonado = DB::table('inventario as i')
                ->join('producto as p', 'i.codigo_producto', '=', 'p.codigo')
                ->leftJoin('unidad_medida as u', 'p.codigo_unidad_medida', '=', 'u.codigo')
                ->where('i.codigo_almacen', $almacen_encartonado)
                ->where('i.stock_actual', '>', 0)
                ->select(
                    'i.codigo_producto', 
                    'p.descripcion', 
                    'p.codigo_tipo_producto', 
                    'u.descripcion as unidad_medida',
                    DB::raw('SUM(i.stock_actual) as stock_total')
                )
                ->groupBy('i.codigo_producto', 'p.descripcion', 'p.codigo_tipo_producto', 'u.descripcion')
                ->get();
                
            $inventario_merma_clip = $stock_encartonado->filter(function($i) {
                return str_contains(strtoupper($i->descripcion), 'CLIP') && !str_starts_with($i->codigo_producto, 'CA');
            })->values();

            $inventario_merma_cascara = $stock_encartonado->filter(function($i) {
                return str_starts_with($i->codigo_producto, 'CA') || str_contains(strtoupper($i->descripcion), 'CASCARA') || str_starts_with($i->codigo_producto, 'EN');
            })->values();

            $inventario_merma_carton = $stock_encartonado->filter(function($i) {
                return str_contains(strtoupper($i->descripcion), 'CARTON') || in_array($i->codigo_tipo_producto, ['AUX', 'ENV', 'EMB']);
            })->values();
        }

        $stock_disponible_embolsado = collect();
        $bolsas_disponibles_embolsado = collect();
        $inventario_merma_empaque = collect();
        if ($es_embolsado || $es_troquelado) {
            $almacen_proceso = DB::table('proceso_produccion')->where('codigo', $proceso->codigo_proceso)->value('codigo_almacen') ?? ($es_embolsado ? 'PS5F' : null);
            
            // 1. Consultar stock disponible en el almacén del proceso
            $stock_empaque = collect();
            if ($almacen_proceso) {
                $stock_empaque = DB::table('inventario as i')
                    ->join('producto as p', 'i.codigo_producto', '=', 'p.codigo')
                    ->leftJoin('unidad_medida as u', 'p.codigo_unidad_medida', '=', 'u.codigo')
                    ->where('i.codigo_almacen', $almacen_proceso)
                    ->where('i.stock_actual', '>', 0)
                    ->select(
                        'i.codigo_producto', 
                        'p.descripcion', 
                        'p.codigo_tipo_producto', 
                        'u.descripcion as unidad_medida',
                        DB::raw('SUM(i.stock_actual) as stock_total')
                    )
                    ->groupBy('i.codigo_producto', 'p.descripcion', 'p.codigo_tipo_producto', 'u.descripcion')
                    ->get();
            }

            $bolsas_disponibles = $stock_empaque->filter(function($i) {
                $desc = strtoupper($i->descripcion);
                return str_contains($desc, 'BOLSA') || in_array($i->codigo_tipo_producto, ['EMB', 'ENV', 'AUX', 'INS']) || str_starts_with($i->codigo_producto, '01.20.007');
            })->values();

            // 2. Si no hay bolsas con stock en el almacén del proceso, buscar bolsas disponibles en cualquier almacén con stock > 0
            if ($bolsas_disponibles->isEmpty()) {
                $bolsas_disponibles = DB::table('inventario as i')
                    ->join('producto as p', 'i.codigo_producto', '=', 'p.codigo')
                    ->leftJoin('unidad_medida as u', 'p.codigo_unidad_medida', '=', 'u.codigo')
                    ->where('i.stock_actual', '>', 0)
                    ->where(function($q) {
                        $q->where('p.descripcion', 'LIKE', '%BOLSA%')
                          ->orWhere('p.descripcion', 'LIKE', '%EMPAQUE%')
                          ->orWhereIn('p.codigo_tipo_producto', ['EMB', 'ENV', 'AUX', 'INS'])
                          ->orWhere('p.codigo', 'LIKE', '01.20.007%');
                    })
                    ->select(
                        'i.codigo_producto', 
                        'p.descripcion', 
                        'p.codigo_tipo_producto', 
                        'u.descripcion as unidad_medida',
                        DB::raw('SUM(i.stock_actual) as stock_total')
                    )
                    ->groupBy('i.codigo_producto', 'p.descripcion', 'p.codigo_tipo_producto', 'u.descripcion')
                    ->get();
            }

            // 3. Fallback: Si no hay stock registrado en inventario, listar del catálogo maestro de productos de empaque
            if ($bolsas_disponibles->isEmpty()) {
                $bolsas_disponibles = DB::table('producto as p')
                    ->leftJoin('unidad_medida as u', 'p.codigo_unidad_medida', '=', 'u.codigo')
                    ->where('p.estado', 1)
                    ->where(function($q) {
                        $q->where('p.descripcion', 'LIKE', '%BOLSA%')
                          ->orWhere('p.descripcion', 'LIKE', '%EMPAQUE%')
                          ->orWhereIn('p.codigo_tipo_producto', ['EMB', 'ENV', 'AUX', 'INS'])
                          ->orWhere('p.codigo', 'LIKE', '01.20.007%');
                    })
                    ->select(
                        'p.codigo as codigo_producto', 
                        'p.descripcion', 
                        'p.codigo_tipo_producto', 
                        'u.descripcion as unidad_medida',
                        DB::raw('0 as stock_total')
                    )
                    ->orderBy('p.descripcion')
                    ->get();
            }

            $inventario_merma_empaque = $bolsas_disponibles;

            if ($es_embolsado) {
                $bolsas_disponibles_embolsado = $bolsas_disponibles;
                $stock_disponible_embolsado = $stock_empaque->filter(function($i) {
                    $desc = strtoupper($i->descripcion);
                    return !str_contains($desc, 'BOLSA') && !in_array($i->codigo_tipo_producto, ['EMB', 'ENV']);
                })->values();

                $inventario_merma_clip = $stock_empaque->filter(function($i) {
                    return str_contains(strtoupper($i->descripcion), 'CLIP') && !str_starts_with($i->codigo_producto, 'CA');
                })->values();

                $inventario_merma_cascara = $stock_empaque->filter(function($i) {
                    return str_starts_with($i->codigo_producto, 'CA') || str_contains(strtoupper($i->descripcion), 'CASCARA') || str_starts_with($i->codigo_producto, 'EN');
                })->values();

                $inventario_merma_carton = $stock_empaque->filter(function($i) {
                    return str_contains(strtoupper($i->descripcion), 'CARTON') || in_array($i->codigo_tipo_producto, ['AUX', 'ENV']) || str_starts_with($i->codigo_producto, '01.20.004');
                })->values();
            }
        }

        $registrados = ComponenteOrdenProduccion::where('id_proceso', $id)->where('estado', 1)->orderBy('fecha_inicio', 'asc')->orderBy('hora_inicio', 'asc')->get();
        $tiene_componentes = ($registrados->count() > 0);

        $peso_consumido = 0;
        $merma_empaque_total = 0;
        $peso_bruto = $peso_inicial;
        if ($es_troquelado) {
            $merma_empaque_total = $registrados->where('tipo_operacion', 'merma_empaque')->sum('cantidad');
            $peso_bruto = max(0, floatval($peso_inicial) - floatval($merma_empaque_total));
            $peso_neto = max(0, floatval($peso_bruto) - floatval($tara));
            $peso_consumido = $registrados->where('codigo_tipo_producto', '!=', 'ACT')
                                          ->where('tipo_operacion', '!=', 'merma_empaque')
                                          ->sum('cantidad');
        }

        // Agrupar por fórmula y fecha de creación (Cargas)
        $cargas_agrupadas = collect();
        if ($es_inyectado || $es_mezclado || $es_ensamblado || $es_ensamblado_manual || $es_encartonado || $es_embolsado || $es_molido || $es_troquelado || $es_horneado) {
            $cargas_agrupadas = $registrados->groupBy(function($item) {
                if ($item->codigo_tipo_producto === 'ACT' || $item->codigo_tipo_producto === 'MANUAL') {
                    $formula = 'MANUAL_ACT_' . $item->id_op_componentes;
                } else {
                    $formula = $item->codigo_formula_produccion ?? 'MANUAL_' . $item->id_op_componentes;
                }
                // Include tipo_operacion to prevent merging different operation types (e.g., limpieza vs recuperado_maquina)
                $tipoOp = $item->tipo_operacion ?? 'produccion';
                // Group by creation minute and exact execution times to keep batches together but separate independent records
                $createdAtMinute = \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i');
                return $formula . '_' . $tipoOp . '_' . $item->fecha_inicio . '_' . $item->hora_inicio . '_' . $item->hora_fin . '_' . $createdAtMinute;
            });

        }

        $tipos_producto = DB::table('tipo_producto')->select('codigo', 'descripcion')->where('estado', 1)->get();
        
        $colores = DB::table('color')->select('codigo', 'descripcion')->where('activo', 1)->get();
        $unidades = DB::table('unidad_medida')->select('codigo', 'descripcion')->where('estado', 1)->get();
        $trabajadores = DB::table('trabajador')->select('codigo', 'nombre')->where('estado', 1)->get();

        $moldes = [];
        if ($es_inyectado) {
            $productCode = $orden->codigo_producto_proceso;
            
            $query = DB::table('molde as m')
                ->select('m.codigo', 'm.descripcion', DB::raw('(SELECT codigo_formula FROM composicion_formula cf WHERE cf.codigo_molde = m.codigo LIMIT 1) as codigo_formula'))
                ->where('m.activo', 1);

            if ($productCode) {
                $query->join('producto_molde as pm', 'm.codigo', '=', 'pm.codigo_molde')
                      ->where('pm.codigo_producto_proceso', $productCode);
            }

            $moldes = $query->get();
            
            // Si no hay moldes vinculados al producto, traer todos los activos como fallback
            if ($moldes->isEmpty()) {
                $query_fallback = DB::table('molde')
                    ->select('codigo', 'descripcion', DB::raw('(SELECT codigo_formula FROM composicion_formula cf WHERE cf.codigo_molde = molde.codigo LIMIT 1) as codigo_formula'))
                    ->where('activo', 1);
                    
                $descProducto = strtoupper($orden->descripcion_producto_proceso);
                if (str_contains($descProducto, 'GANCHO')) {
                    $query_fallback->where('descripcion', 'LIKE', '%GANCHO%');
                } elseif (str_contains($descProducto, 'JABONERA')) {
                    $query_fallback->where('descripcion', 'LIKE', '%JABONERA%');
                } elseif (str_contains($descProducto, 'COLADOR')) {
                    $query_fallback->where('descripcion', 'LIKE', '%COLADOR%');
                } elseif (str_contains($descProducto, 'MATAMOSCA')) {
                    $query_fallback->where('descripcion', 'LIKE', '%MATAMOSCA%');
                }

                $moldes = $query_fallback->get();
            }
        }

        $query_centros = DB::table('centro_trabajo_produccion as ct')
            ->join('proceso_centro_trabajo as pct', 'ct.codigo', '=', 'pct.codigo_centro_trabajo')
            ->select('ct.codigo', 'ct.descripcion')
            ->where('pct.codigo_proceso', $proceso->codigo_proceso);

        if ($es_inyectado) {
            $descProducto = strtoupper($orden->descripcion_producto_proceso);
            if (str_contains($descProducto, 'GANCHO')) {
                $query_centros->where('ct.codigo', '!=', 'INY-001');
            } elseif (str_contains($descProducto, 'COLADOR')) {
                $query_centros->where('ct.codigo', 'INY-001');
            }
        }

        $centros_trabajo = $query_centros->get();

        // Si no hay centros vinculados para ensamblado manual o encartonado, fallback
        if ($centros_trabajo->isEmpty() && $es_ensamblado_manual) {
            $centros_trabajo = DB::table('centro_trabajo_produccion')
                ->select('codigo', 'descripcion')
                ->where('descripcion', 'LIKE', '%ENSAMBLADO MANUAL%')
                ->where('estado', 1)
                ->get();
        } elseif ($centros_trabajo->isEmpty() && $es_encartonado) {
            $centros_trabajo = DB::table('centro_trabajo_produccion')
                ->select('codigo', 'descripcion')
                ->where(function($q) {
                    $q->where('codigo', 'LIKE', 'ENC%')
                      ->orWhere('descripcion', 'LIKE', '%ENCARTON%');
                })
                ->where('estado', 1)
                ->get();
        } elseif ($centros_trabajo->isEmpty() && $es_embolsado) {
            $centros_trabajo = DB::table('centro_trabajo_produccion')
                ->select('codigo', 'descripcion')
                ->where(function($q) {
                    $q->where('codigo', 'LIKE', 'CON%')
                      ->orWhere('codigo', 'LIKE', 'SEB%')
                      ->orWhere('codigo', 'LIKE', 'SELL%')
                      ->orWhere('descripcion', 'LIKE', '%EMBOLS%')
                      ->orWhere('descripcion', 'LIKE', '%SELLA%');
                })
                ->where('estado', 1)
                ->get();
        }

        $almacenes = DB::table('almacen')->where('activo', 1)->get();
        $proceso_produccion_almacen = DB::table('proceso_produccion')->where('codigo', $proceso->codigo_proceso)->value('codigo_almacen');

        // Sugerencia para encadenamiento de inyectadas: la inicial toma la última inyectada final registrada
        $inyectada_inicial_sugerida = $proceso->inyectada_final ?? $proceso->inyectada_inicial ?? '';
        $producto_sugerido = null;

        return view('produccion.procesos.ejecucion', compact(
            'orden', 'proceso', 'estado_proceso_actual', 'es_actividad', 'es_mezclado', 'es_inyectado', 'es_ensamblado', 'es_ensamblado_manual', 'es_encartonado', 'es_molido', 'es_troquelado', 'es_horneado', 'peso_inicial', 'tara', 'peso_neto', 'peso_consumido', 'peso_bruto', 'merma_empaque_total',
            'formulas_disponibles', 'inventario_merma_clip', 'inventario_merma_cascara', 'inventario_merma_carton', 'registrados', 'cargas_agrupadas', 'tiene_componentes', 'tipos_producto',
            'colores', 'unidades', 'trabajadores', 'moldes', 'centros_trabajo', 'almacenes', 'proceso_produccion_almacen', 'producto_sugerido', 'inyectada_inicial_sugerida', 'ops_disponibles_encartonado', 'es_embolsado', 'stock_disponible_embolsado', 'bolsas_disponibles_embolsado', 'inventario_merma_empaque'
        ));
    }

    public function queryOpsDisponiblesEncartonado($codigo_almacen)
    {
        if (!$codigo_almacen) {
            $codigo_almacen = 'PS5E';
        }

        $stockQuery = DB::table('inventario as i')
            ->join('producto as p', 'i.codigo_producto', '=', 'p.codigo')
            ->leftJoin('produccion_ingresos_proceso as pip', 'i.lote', '=', 'pip.lote_produccion')
            ->leftJoin('orden_produccion_global as op', 'pip.idop', '=', 'op.idop')
            ->leftJoin('color as c', 'p.codigo_color', '=', 'c.codigo')
            ->where('i.codigo_almacen', $codigo_almacen)
            ->where('i.stock_actual', '>', 0)
            ->where(function($q) {
                $q->where('i.codigo_producto', 'LIKE', 'EN%')
                  ->orWhere('p.codigo_tipo_producto', 'PEP')
                  ->orWhere('p.descripcion', 'LIKE', '%ENSAMBLADO%');
            })
            ->select(
                DB::raw('COALESCE(op.idop, 0) as idop'),
                DB::raw('COALESCE(op.codigo_op, "SIN_OP") as codigo_op'),
                DB::raw('COALESCE(op.descripcion_producto_proceso, p.descripcion) as descripcion_op'),
                'i.codigo_producto',
                'p.descripcion as descripcion_producto',
                'p.codigo_color',
                'c.descripcion as descripcion_color',
                'i.lote',
                'i.stock_actual'
            )
            ->get();

        $coloresCatalog = DB::table('color')->pluck('descripcion', 'codigo')->toArray();

        $ops = [];
        foreach ($stockQuery as $item) {
            $key = $item->idop;
            if (!isset($ops[$key])) {
                $ops[$key] = [
                    'idop' => $item->idop,
                    'codigo_op' => $item->codigo_op,
                    'descripcion_op' => $item->descripcion_op,
                    'stock_total' => 0,
                    'colores' => []
                ];
            }

            $ops[$key]['stock_total'] += floatval($item->stock_actual);

            // Determinar código de color
            $colorCod = $item->codigo_color;
            if (empty($colorCod)) {
                if (preg_match('/EN07-([A-Z0-9]+)/i', $item->codigo_producto, $matches)) {
                    $colorCod = $matches[1];
                } elseif (preg_match('/([A-Z0-9]{3,5})/', $item->codigo_producto, $matches)) {
                    $colorCod = $matches[1];
                } else {
                    $colorCod = $item->codigo_producto;
                }
            }

            // Nombre de color
            $descColor = $item->descripcion_color ?? ($coloresCatalog[$colorCod] ?? null);
            if (empty($descColor)) {
                if (preg_match('/COLOR\s+(.+)$/i', $item->descripcion_producto, $m)) {
                    $descColor = trim($m[1]);
                } else {
                    $descColor = $colorCod;
                }
            }

            // Swatch de color visual
            $swatch = '#94a3b8';
            $cUpper = strtoupper($descColor . ' ' . $colorCod);
            if (str_contains($cUpper, 'AZUL')) $swatch = '#2563eb';
            elseif (str_contains($cUpper, 'CELESTE')) $swatch = '#0ea5e9';
            elseif (str_contains($cUpper, 'VERDE')) $swatch = '#16a34a';
            elseif (str_contains($cUpper, 'MAGENTA')) $swatch = '#d946ef';
            elseif (str_contains($cUpper, 'NARANJA')) $swatch = '#ea580c';
            elseif (str_contains($cUpper, 'ROSADO')) $swatch = '#ec4899';
            elseif (str_contains($cUpper, 'ROJO')) $swatch = '#dc2626';
            elseif (str_contains($cUpper, 'VIOLETA')) $swatch = '#7c3aed';
            elseif (str_contains($cUpper, 'BLANCO')) $swatch = '#f1f5f9';
            elseif (str_contains($cUpper, 'AMARILLO')) $swatch = '#eab308';

            if (!isset($ops[$key]['colores'][$colorCod])) {
                $ops[$key]['colores'][$colorCod] = [
                    'codigo_color' => $colorCod,
                    'nombre_color' => $descColor,
                    'swatch' => $swatch,
                    'codigo_producto' => $item->codigo_producto,
                    'descripcion_producto' => $item->descripcion_producto,
                    'stock' => 0
                ];
            }
            $ops[$key]['colores'][$colorCod]['stock'] += floatval($item->stock_actual);
        }

        $result = [];
        foreach ($ops as $opData) {
            $opData['stock_total'] = round($opData['stock_total'], 2);
            $opData['total_colores'] = count($opData['colores']);
            $coloresArray = [];
            foreach ($opData['colores'] as $c) {
                $c['stock'] = round($c['stock'], 2);
                $coloresArray[] = $c;
            }
            $opData['colores'] = $coloresArray;
            $result[] = $opData;
        }

        return $result;
    }

    public function getOpsDisponiblesEncartonado(Request $request)
    {
        $codigo_almacen = $request->input('codigo_almacen');
        $ops = $this->queryOpsDisponiblesEncartonado($codigo_almacen);
        return response()->json([
            'success' => true,
            'almacen' => $codigo_almacen,
            'ops' => $ops
        ]);
    }

    public function getFormulaComponents(Request $request)
    {
        $codigo_formula = $request->codigo_formula;
        $codigo_molde = $request->codigo_molde;
        $codigo_almacen = $request->codigo_almacen;
        
        if (!$codigo_formula) {
            return response()->json(['success' => false, 'message' => 'Código de fórmula no proporcionado.']);
        }
        
        $query = DB::table('composicion_formula as cf')
            ->join('producto as p', 'cf.codigo_producto', '=', 'p.codigo')
            ->leftJoin('unidad_medida as u', 'cf.codigo_unidad_medida', '=', 'u.codigo')
            ->leftJoin('tipo_producto as tp', 'p.codigo_tipo_producto', '=', 'tp.codigo')
            ->select(
                'cf.codigo_producto',
                'p.descripcion as descripcion_producto',
                'p.codigo_tipo_producto',
                'tp.descripcion as descripcion_tipo_producto',
                'cf.cantidad_nominal',
                'cf.codigo_unidad_medida',
                'u.descripcion as descripcion_unidad_medida',
                'cf.codigo_molde'
            )
            ->where('cf.codigo_formula', $codigo_formula);

        if ($codigo_molde) {
            $query->where(function($q) use ($codigo_molde) {
                $q->where('cf.codigo_molde', $codigo_molde)
                  ->orWhereNull('cf.codigo_molde');
            });
        }

        $componentes = $query->get();

        // Lógica de ensamblado (dinámica) si no se pasa molde desde el UI (omitir para encartonado)
        $es_formula_encartonado = str_starts_with($codigo_formula, 'ENC-') || str_starts_with($codigo_formula, 'EN07-Z');
        if (!$codigo_molde && !$es_formula_encartonado) {
            $componentesFiltrados = collect();
            $productosConMolde = [];

            foreach ($componentes as $comp) {
                if (!empty($comp->codigo_molde)) {
                    if (!array_key_exists($comp->codigo_producto, $productosConMolde)) {
                        // Buscar el molde del lote más antiguo en stock
                        $queryStock = DB::table('inventario as i')
                            ->join('almacen as a', 'i.codigo_almacen', '=', 'a.codigo_almacen')
                            ->where('i.codigo_producto', $comp->codigo_producto)
                            ->where('a.activo', 1)
                            ->where(function($q) { $q->where('i.estado', 1)->orWhereNull('i.estado'); })
                            ->where('i.stock_actual', '>', 0);
                            
                        if ($codigo_almacen) {
                            $queryStock->where('i.codigo_almacen', $codigo_almacen);
                        }

                        $loteActivo = $queryStock->orderBy('i.fecha_vencimiento', 'asc')
                            ->orderBy('i.id_inventario', 'asc')
                            ->first();

                        $moldeStock = null;
                        if ($loteActivo && $loteActivo->lote) {
                            $moldeStock = DB::table('produccion_ingresos_proceso')
                                ->where('lote_produccion', $loteActivo->lote)
                                ->value('codigo_molde');
                        }
                        $productosConMolde[$comp->codigo_producto] = $moldeStock;
                    }

                    // Solo incluir si coincide con el molde en stock
                    if ($comp->codigo_molde === $productosConMolde[$comp->codigo_producto]) {
                        $componentesFiltrados->push($comp);
                    } else {
                        $moldeRequerido = $comp->codigo_molde;
                        $moldeEnStock = $productosConMolde[$comp->codigo_producto] ?? 'Ninguno';
                        return response()->json(['success' => false, 'message' => "El producto {$comp->descripcion_producto} requiere el molde {$moldeRequerido}, pero el stock disponible proviene del molde: {$moldeEnStock}."]);
                    }
                } else {
                    // Si no tiene molde (ej. Clip) se incluye directamente
                    $componentesFiltrados->push($comp);
                }
            }
            $componentes = $componentesFiltrados;
        }

        if ($componentes->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'La fórmula no tiene componentes o no coinciden los parámetros de stock/molde.']);
        }

        $formulaData = DB::table('formula_produccion')->where('codigo', $codigo_formula)->first();
        $codigo_material_reciclado = $formulaData ? $formulaData->codigo_material_reciclado : null;

        $descripcion_material_reciclado = null;
        if ($codigo_material_reciclado) {
            $descripcion_material_reciclado = DB::table('producto')->where('codigo', $codigo_material_reciclado)->value('descripcion');
        }

        return response()->json([
            'success' => true, 
            'componentes' => $componentes->values(),
            'codigo_material_reciclado' => $codigo_material_reciclado,
            'descripcion_material_reciclado' => $descripcion_material_reciclado
        ]);
    }

    public function verificarStock(Request $request)
    {
        $productos = $request->input('productos', []);
        $codigo_almacen = $request->codigo_almacen;

        if (!$codigo_almacen || empty($productos)) {
            return response()->json(['success' => false, 'message' => 'Parámetros incompletos.']);
        }

        $stocks = DB::table('inventario')
            ->where('codigo_almacen', $codigo_almacen)
            ->whereIn('codigo_producto', $productos)
            ->selectRaw('codigo_producto, SUM(stock_actual) as stock_total')
            ->groupBy('codigo_producto')
            ->pluck('stock_total', 'codigo_producto');

        $result = [];
        foreach ($productos as $prod) {
            $result[$prod] = floatval($stocks[$prod] ?? 0);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function storeComponentes(Request $request, $idop, $id)
    {
        $componentes = json_decode($request->componentes_json ?? '[]', true);
        
        if (empty($componentes)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'No hay datos para guardar.'], 422);
            }
            return back()->with('error', 'No hay datos para guardar.');
        }

        try {
            DB::beginTransaction();

            $usuario_id = Auth::id();
            $batch_id = mt_rand(1000, 9999);
            $numero_referencia = "OP-{$idop}-PROC-{$id}-" . $batch_id;
            
            $trace_movimientos = 0;
            $trace_componentes = count($componentes);
            $consumosResumen = []; // [key => ['producto','almacen','cantidad','primer_mov_id']]

            $codigo_molde_op = null;
            foreach ($componentes as $comp) {
                if (!empty($comp['codigo_molde'])) {
                    $codigo_molde_op = $comp['codigo_molde'];
                    break;
                }
            }

            $descripcion_molde_op = null;
            if ($codigo_molde_op) {
                $descripcion_molde_op = DB::table('molde')->where('codigo', $codigo_molde_op)->value('descripcion');
            }

            $orden = OrdenProduccion::findOrFail($idop);
            if ($orden->estado === 'COMPLETADO' || $orden->activo == 0) {
                throw new \Exception("No se pueden registrar componentes en una orden " . ($orden->activo == 0 ? 'ANULADA' : 'COMPLETADA') . ".");
            }

            $proceso = OrdenProceso::findOrFail($id);
            if ($proceso->estado_avance === 'COMPLETADO') {
                throw new \Exception("No se pueden registrar componentes en un proceso COMPLETADO.");
            }

            $es_actividad = ($proceso->codigo_proceso == '1');

            if ($es_actividad) {
                $codigosActividad = collect($componentes)->pluck('codigo_producto')->unique()->filter()->values()->toArray();
                $descActividades = DB::table('actividad_produccion')->whereIn('codigo', $codigosActividad)->pluck('descripcion', 'codigo');
                $codigosTrabajadorAct = collect($componentes)->pluck('codigo_trabajador')->unique()->filter()->values()->toArray();
                $descTrabajadorAct = DB::table('trabajador')->whereIn('codigo', $codigosTrabajadorAct)->pluck('nombre', 'codigo');

                foreach ($componentes as $comp) {
                    $codigo_act = $comp['codigo_producto'];
                    $desc_act = $descActividades[$codigo_act] ?? '';
                    
                    $codigo_trabajador = !empty($comp['codigo_trabajador']) ? $comp['codigo_trabajador'] : null;

                    DB::table('producto')->insertOrIgnore([
                        'codigo' => $codigo_act,
                        'descripcion' => $desc_act,
                        'codigo_tipo_producto' => 'ACT',
                        'es_producto_proceso' => 0,
                        'estado' => 1
                    ]);

                    ComponenteOrdenProduccion::create([
                        'idop' => $idop,
                        'id_proceso' => $id,
                        'codigo_tipo_producto' => 'ACT',
                        'descripcion_tipo_producto' => 'ACTIVIDADES PRODUCCION',
                        'codigo_producto' => $codigo_act,
                        'descripcion_producto' => $desc_act,
                        'codigo_trabajador' => $codigo_trabajador,
                        'descripcion_trabajador' => $codigo_trabajador ? ($descTrabajadorAct[$codigo_trabajador] ?? '') : null,
                        'codigo_unidad_medida' => 'UNI',
                        'descripcion_unidad_medida' => 'UNIDADES',
                        'cantidad' => 1,
                        'fecha_inicio' => $comp['fecha_inicio'],
                        'fecha_fin' => $comp['fecha_fin'],
                        'hora_inicio' => $comp['hora_inicio'],
                        'hora_fin' => $comp['hora_fin'],
                        'fecha_inicio_maquina' => $comp['fecha_inicio_maquina'] ?? $comp['fecha_inicio'],
                        'hora_inicio_maquina' => $comp['hora_inicio_maquina'] ?? $comp['hora_inicio'],
                        'fecha_fin_maquina' => $comp['fecha_fin_maquina'] ?? $comp['fecha_fin'],
                        'hora_fin_maquina' => $comp['hora_fin_maquina'] ?? $comp['hora_fin'],
                        'inyectada_inicial' => isset($comp['inyectada_inicial']) && $comp['inyectada_inicial'] !== '' && $comp['inyectada_inicial'] !== null ? intval($comp['inyectada_inicial']) : null,
                        'inyectada_final' => isset($comp['inyectada_final']) && $comp['inyectada_final'] !== '' && $comp['inyectada_final'] !== null ? intval($comp['inyectada_final']) : null,
                        'numero_ot' => !empty($comp['numero_ot']) ? trim($comp['numero_ot']) : null,
                        'observacion' => $comp['observacion'] ?? null,
                        'estado' => 1,
                        'tipo_operacion' => $request->input('tipo_operacion_origen', 'inyectado')
                    ]);
                }
                DB::commit();
                if ($request->ajax()) {
                    return response()->json(['success' => true, 'message' => 'Actividades registradas correctamente.']);
                }
                return back()->with('success', 'Actividades registradas correctamente.');
            }

            // Flujo Producción
            $fecha_movimiento_general = now()->format('Y-m-d H:i:s');
            if (count($componentes) > 0 && !empty($componentes[0]['fecha_inicio'])) {
                $hora_inicio = !empty($componentes[0]['hora_inicio']) ? $componentes[0]['hora_inicio'] : '00:00:00';
                if (strlen($hora_inicio) == 5) {
                    $hora_inicio .= ':00';
                }
                $fecha_movimiento_general = $componentes[0]['fecha_inicio'] . ' ' . $hora_inicio;
            }
            $faltantes = [];
            $cantidades_agrupadas = [];
            $total_insumos_ingresados = 0;

            foreach ($componentes as $comp) {
                $codigo_tipo = $comp['codigo_tipo_producto'] ?? 'MTP';
                // Si es Actividad, es únicamente referencial (HH / HM): NO validar stock
                if ($codigo_tipo === 'ACT') {
                    continue;
                }

                $cod = $comp['codigo_producto'];
                $cant = floatval($comp['cantidad']);
                if (!isset($cantidades_agrupadas[$cod])) { $cantidades_agrupadas[$cod] = 0; }
                $cantidades_agrupadas[$cod] += $cant;
                $total_insumos_ingresados += $cant;
            }

            // Validación de Saldo de Rollo en Troquelado
            $desc_proceso_upper = strtoupper(DB::table('proceso_produccion')->where('codigo', $proceso->codigo_proceso)->value('descripcion') ?? ($proceso->descripcion_proceso ?? ''));
            $es_troquelado = ($proceso->codigo_proceso == '17') || str_contains($desc_proceso_upper, 'TROQUELADO');
            if ($es_troquelado) {
                $tipo_op_origen = $request->input('tipo_operacion_origen', 'inyectado');

                $merma_empaque_previo = DB::table('componentes_orden_produccion_global')
                    ->where('id_proceso', $id)
                    ->where(function($q) { $q->where('estado', 1)->orWhereNull('estado'); })
                    ->where('tipo_operacion', 'merma_empaque')
                    ->sum('cantidad') ?? 0;

                $consumido_previo = DB::table('componentes_orden_produccion_global')
                    ->where('id_proceso', $id)
                    ->where(function($q) { $q->where('estado', 1)->orWhereNull('estado'); })
                    ->where('codigo_tipo_producto', '!=', 'ACT')
                    ->where('tipo_operacion', '!=', 'merma_empaque')
                    ->sum('cantidad') ?? 0;

                $nuevo_consumo = 0;
                foreach ($componentes as $comp) {
                    if (($comp['codigo_tipo_producto'] ?? 'MTP') !== 'ACT') {
                        $nuevo_consumo += floatval($comp['cantidad'] ?? 0);
                    }
                }

                if ($tipo_op_origen === 'merma_empaque') {
                    if (($merma_empaque_previo + $nuevo_consumo) > (floatval($orden->cantidad) + 0.001)) {
                        throw new \Exception("La merma de empaque total (" . number_format($merma_empaque_previo + $nuevo_consumo, 2) . " KG) excede el Peso Bruto inicial del Rollo (" . number_format($orden->cantidad, 2) . " KG).");
                    }
                } else {
                    $peso_bruto_efectivo = max(0, floatval($orden->cantidad) - floatval($merma_empaque_previo));
                    $peso_neto = max(0, floatval($peso_bruto_efectivo) - floatval($orden->tara));

                    if ($peso_neto > 0 && ($consumido_previo + $nuevo_consumo) > ($peso_neto + 0.001)) {
                        $saldo_disp = max(0, $peso_neto - $consumido_previo);
                        throw new \Exception("El consumo total (" . number_format($consumido_previo + $nuevo_consumo, 2) . " KG) excede el Peso Neto del Rollo (" . number_format($peso_neto, 2) . " KG). Saldo disponible: " . number_format($saldo_disp, 2) . " KG.");
                    }
                }
            }

            $codigo_almacen_consumo = $request->input('codigo_almacen_consumo');
            if (empty($codigo_almacen_consumo) && !$es_actividad && !empty($cantidades_agrupadas)) {
                throw new \Exception("Debe seleccionar un Almacén de Consumo.");
            }

            if (!empty($cantidades_agrupadas)) {
                $nomProductos = DB::table('producto')
                    ->whereIn('codigo', array_keys($cantidades_agrupadas))
                    ->pluck('descripcion', 'codigo');

                foreach ($cantidades_agrupadas as $codigo_prod => $cant_req) {
                    $query_disp = DB::table('inventario as i')
                        ->join('almacen as a', 'i.codigo_almacen', '=', 'a.codigo_almacen')
                        ->where('i.codigo_producto', $codigo_prod)
                        ->where('a.activo', 1)
                        ->where(function($q) { $q->where('i.estado', 1)->orWhereNull('i.estado'); });

                    if ($codigo_almacen_consumo) {
                        $query_disp->where('i.codigo_almacen', $codigo_almacen_consumo);
                    }

                    $stock_disp = $query_disp->lockForUpdate()->sum('i.stock_actual') ?? 0;

                    if ($stock_disp < $cant_req) {
                        $alm_msg = $codigo_almacen_consumo ? " en el almacén seleccionado ($codigo_almacen_consumo)" : " en almacén";
                        $nombre_prod = $nomProductos[$codigo_prod] ?? $codigo_prod;
                        $faltantes[] = "[$nombre_prod] Req: " . number_format($cant_req, 2) . " | Disp: " . number_format($stock_disp, 2) . $alm_msg;
                    }
                }

                if (!empty($faltantes)) {
                    throw new \Exception("STOCK INSUFICIENTE. Faltan materiales en almacén: " . implode(" ; ", $faltantes));
                }
            }

            $grupos_pep = []; 
            $productos_resultantes_arr = json_decode($request->productos_resultantes_json ?? '[]', true);
            $has_manual_products = count($productos_resultantes_arr) > 0;

            // Pre-cargar catálogos para eliminar N+1 en descripciones (8 queries por componente)
            $todosCodigosTipoProducto = collect($componentes)->pluck('codigo_tipo_producto')->unique()->filter()->push('MTP')->values()->toArray();
            $todosCodigosProducto = collect($componentes)->pluck('codigo_producto')->unique()->filter()->values()->toArray();
            $todosCodigosCentroTrabajo = collect($componentes)->pluck('codigo_centro_trabajo')->unique()->filter()->values()->toArray();
            $todosCodigosMolde = collect($componentes)->pluck('codigo_molde')->unique()->filter()->values()->toArray();
            $todosCodigosTrabajador = collect($componentes)->pluck('codigo_trabajador')->unique()->filter()->values()->toArray();
            $todosCodigosUnidad = collect($componentes)->pluck('codigo_unidad_medida')->unique()->filter()->push('KG')->values()->toArray();
            $todosCodigosColor = collect($componentes)->pluck('codigo_color')->unique()->filter()->values()->toArray();
            $todosCodigosFormula = collect($componentes)->pluck('codigo_formula')->unique()->filter()->values()->toArray();

            $descTipoProducto = DB::table('tipo_producto')->whereIn('codigo', $todosCodigosTipoProducto)->pluck('descripcion', 'codigo');
            $descProducto = DB::table('producto')->whereIn('codigo', $todosCodigosProducto)->pluck('descripcion', 'codigo');
            $descCentroTrabajo = DB::table('centro_trabajo_produccion')->whereIn('codigo', $todosCodigosCentroTrabajo)->pluck('descripcion', 'codigo');
            $descMolde = DB::table('molde')->whereIn('codigo', $todosCodigosMolde)->pluck('descripcion', 'codigo');
            $descTrabajador = DB::table('trabajador')->whereIn('codigo', $todosCodigosTrabajador)->pluck('nombre', 'codigo');
            $descUnidad = DB::table('unidad_medida')->whereIn('codigo', $todosCodigosUnidad)->pluck('descripcion', 'codigo');
            $descColor = DB::table('color')->whereIn('codigo', $todosCodigosColor)->pluck('descripcion', 'codigo');
            $descFormula = DB::table('formula_produccion')->whereIn('codigo', $todosCodigosFormula)->pluck('descripcion', 'codigo');

            foreach ($componentes as $comp) {
                $codigo_producto = $comp['codigo_producto'];
                $cantidad = floatval($comp['cantidad']);
                $codigo_tipo = $comp['codigo_tipo_producto'] ?? 'MTP';
                
                $codigo_formula = ($codigo_tipo === 'ACT') ? null : (!empty($comp['codigo_formula']) ? $comp['codigo_formula'] : null);
                $codigo_color = !empty($comp['codigo_color']) ? $comp['codigo_color'] : null;
                $codigo_centro_trabajo = !empty($comp['codigo_centro_trabajo']) ? $comp['codigo_centro_trabajo'] : null;
                $codigo_molde = !empty($comp['codigo_molde']) ? $comp['codigo_molde'] : null;
                $codigo_trabajador = !empty($comp['codigo_trabajador']) ? $comp['codigo_trabajador'] : null;

                $tipo_op_origen = $request->input('tipo_operacion_origen', 'inyectado');
                $es_merma_directa = in_array($tipo_op_origen, ['recuperado_clip', 'merma_cascara', 'merma_carton']) || ($tipo_op_origen === 'merma_empaque' && empty($codigo_formula)) || ($tipo_op_origen === 'merma_clip' && empty($codigo_formula));
                
                if ($es_merma_directa || $codigo_tipo === 'ACT') {
                    // No agrupar PEPs para mermas directas sin formula ni para actividades.
                } elseif (!empty($codigo_formula)) {
                    $codigo_pep = $this->determinarCodigoPEP($codigo_formula);
                    if (empty($codigo_pep)) {
                        if ($tipo_op_origen === 'embolsado') {
                            $codigo_pep = $codigo_formula;
                        } else {
                            throw new \Exception("La fórmula [$codigo_formula] no tiene un Producto Resultante asociado.");
                        }
                    }

                    $key = $codigo_pep . '_' . ($codigo_color ?: 'SC');
                    if (!isset($grupos_pep[$key])) {
                        $descPepProd = $descProducto[$codigo_pep] ?? DB::table('producto')->where('codigo', $codigo_pep)->value('descripcion') ?? '';
                        $esEncartonadoOp = ($tipo_op_origen === 'encartonado') || str_starts_with($codigo_pep, 'EN07-Z');
                        $esEmbolsadoOp = ($tipo_op_origen === 'embolsado') || str_starts_with($codigo_pep, 'GA07-Z');
                        
                        if ($esEncartonadoOp) {
                            $unidadPep = 'SET';
                            $cantPepInicial = floatval($request->input('cantidad_sets', 0));
                        } elseif ($esEmbolsadoOp) {
                            $prodRow = DB::table('producto')->where('codigo', $codigo_pep)->first(['codigo_unidad_medida']);
                            $unidadPep = (!empty($prodRow->codigo_unidad_medida)) ? $prodRow->codigo_unidad_medida : 'BOL';
                            $cantPepInicial = floatval($request->input('cantidad_bolsas', $request->input('cantidad_total', 0)));
                        } else {
                            $unidadPep = $comp['codigo_unidad_medida'] ?? 'KG';
                            $cantPepInicial = 0;
                        }

                        $grupos_pep[$key] = [
                            'codigo_pep' => $codigo_pep,
                            'descripcion_pep' => $descPepProd,
                            'formula' => $codigo_formula,
                            'color' => $codigo_color,
                            'cant' => $cantPepInicial,
                            'unidad' => $unidadPep,
                            'ids_componentes' => []
                        ];
                    }
                    if (($tipo_op_origen !== 'encartonado') && !str_starts_with($codigo_pep, 'EN07-Z') && ($tipo_op_origen !== 'embolsado') && !str_starts_with($codigo_pep, 'GA07-Z')) {
                        $grupos_pep[$key]['cant'] += $cantidad;
                    }
                } elseif (!$has_manual_products && !empty($comp['codigo_tipo_producto']) && $comp['codigo_tipo_producto'] === 'PEP') {
                    $codigo_pep = $this->determinarPEPdesdeProducto($codigo_producto);
                    if (!empty($codigo_pep)) {
                        $key = $codigo_pep . '_' . ($codigo_color ?: 'SC');
                        if (!isset($grupos_pep[$key])) {
                            $grupos_pep[$key] = [
                                'codigo_pep' => $codigo_pep,
                                'descripcion_pep' => $descProducto[$codigo_pep] ?? '',
                                'formula' => null,
                                'color' => $codigo_color,
                                'cant' => 0,
                                'unidad' => $comp['codigo_unidad_medida'] ?? 'KG',
                                'ids_componentes' => []
                            ];
                        }
                        $grupos_pep[$key]['cant'] += $cantidad;
                    }
                } elseif (!$has_manual_products && !empty($comp['codigo_tipo_producto']) && $comp['codigo_tipo_producto'] !== 'ACT') {
                    $opR = DB::table('orden_produccion_global')->where('idop', $idop)->first(['descripcion_producto_proceso']);
                    if ($opR && !empty($opR->descripcion_producto_proceso)) {
                        $producto_pep = DB::table('producto')
                            ->where('descripcion', $opR->descripcion_producto_proceso)
                            ->where('codigo_tipo_producto', 'PEP')
                            ->where('estado', 1)
                            ->first(['codigo']);
                        $codigo_pep = $producto_pep ? $producto_pep->codigo : null;
                        
                        if ($codigo_pep) {
                            $key = $codigo_pep . '_' . ($codigo_color ?: 'SC');
                            if (!isset($grupos_pep[$key])) {
                                $grupos_pep[$key] = [
                                    'codigo_pep' => $codigo_pep,
                                    'descripcion_pep' => $descProducto[$codigo_pep] ?? '',
                                    'formula' => null,
                                    'color' => $codigo_color,
                                    'cant' => 0,
                                    'unidad' => $comp['codigo_unidad_medida'] ?? 'KG',
                                    'ids_componentes' => []
                                ];
                            }
                            $grupos_pep[$key]['cant'] += $cantidad;
                        }
                    }
                }

                $desc_act = null;
                if ($codigo_tipo === 'ACT') {
                    $desc_act = DB::table('actividad_produccion')->where('codigo', $codigo_producto)->value('descripcion') 
                        ?? ($descProducto[$codigo_producto] ?? $codigo_producto);

                    DB::table('producto')->insertOrIgnore([
                        'codigo' => $codigo_producto,
                        'descripcion' => $desc_act,
                        'codigo_tipo_producto' => 'ACT',
                        'es_producto_proceso' => 0,
                        'estado' => 1
                    ]);
                }

                $componente = ComponenteOrdenProduccion::create([
                    'idop' => $idop,
                    'id_proceso' => $id,
                    'codigo_tipo_producto' => $codigo_tipo,
                    'descripcion_tipo_producto' => ($codigo_tipo === 'ACT') ? 'ACTIVIDADES PRODUCCION' : ($descTipoProducto[$codigo_tipo] ?? ''),
                    'codigo_producto' => $codigo_producto,
                    'descripcion_producto' => ($codigo_tipo === 'ACT') ? ($desc_act ?? $descProducto[$codigo_producto] ?? $codigo_producto) : ($descProducto[$codigo_producto] ?? ''),
                    'codigo_centro_trabajo' => $codigo_centro_trabajo,
                    'descripcion_centro_trabajo' => $codigo_centro_trabajo ? ($descCentroTrabajo[$codigo_centro_trabajo] ?? '') : null,
                    'codigo_molde' => $codigo_molde,
                    'descripcion_molde' => $codigo_molde ? ($descMolde[$codigo_molde] ?? '') : null,
                    'codigo_trabajador' => $codigo_trabajador,
                    'descripcion_trabajador' => $codigo_trabajador ? ($descTrabajador[$codigo_trabajador] ?? '') : null,
                    'codigo_unidad_medida' => $comp['codigo_unidad_medida'] ?? 'KG',
                    'descripcion_unidad_medida' => $descUnidad[$comp['codigo_unidad_medida'] ?? 'KG'] ?? '',
                    'cantidad' => $cantidad,
                    'codigo_color' => $codigo_color,
                    'descripcion_color' => $codigo_color ? ($descColor[$codigo_color] ?? '') : null,
                    'codigo_formula_produccion' => $codigo_formula,
                    'descripcion_formula_produccion' => $codigo_formula ? ($descFormula[$codigo_formula] ?? '') : null,
                    'fecha_inicio' => $comp['fecha_inicio'],
                    'fecha_fin' => $comp['fecha_fin'],
                    'hora_inicio' => $comp['hora_inicio'],
                    'hora_fin' => $comp['hora_fin'],
                    'fecha_inicio_maquina' => $comp['fecha_inicio_maquina'] ?? $comp['fecha_inicio'],
                    'hora_inicio_maquina' => $comp['hora_inicio_maquina'] ?? $comp['hora_inicio'],
                    'fecha_fin_maquina' => $comp['fecha_fin_maquina'] ?? $comp['fecha_fin'],
                    'hora_fin_maquina' => $comp['hora_fin_maquina'] ?? $comp['hora_fin'],
                    'inyectada_inicial' => isset($comp['inyectada_inicial']) && $comp['inyectada_inicial'] !== '' && $comp['inyectada_inicial'] !== null ? intval($comp['inyectada_inicial']) : null,
                    'inyectada_final' => isset($comp['inyectada_final']) && $comp['inyectada_final'] !== '' && $comp['inyectada_final'] !== null ? intval($comp['inyectada_final']) : null,
                    'numero_ot' => !empty($comp['numero_ot']) ? trim($comp['numero_ot']) : null,
                    'observacion' => $comp['observacion'] ?? ($tipo_op_origen === 'merma_empaque' ? 'MERMA EMPAQUE' : ($tipo_op_origen === 'merma_clip' ? 'MERMA CLIP' : null)),
                    'estado' => 1,
                    'tipo_operacion' => $request->input('tipo_operacion_origen', 'inyectado'),
                ]);

                if (isset($key) && isset($grupos_pep[$key])) {
                    $grupos_pep[$key]['ids_componentes'][] = $componente->id_op_componentes;
                }
                unset($key);

                if ($codigo_tipo === 'ACT') {
                    // Las actividades manuales son únicamente de referencia (HH / HM).
                    // No deben consumir inventario ni afectar Kardex.
                    continue;
                }

                $idComponente = $componente->id_op_componentes;

                $cantidad_restante = $cantidad;
                $es_clip_recuperado = false;
                if ($request->input('usar_clip_recuperado') == 1) {
                    $desc_prod = strtolower($descProducto[$codigo_producto] ?? '');
                    if (str_contains($desc_prod, 'clip')) {
                        $es_clip_recuperado = true;
                    }
                }

                $query_lotes = DB::table('inventario as i')
                    ->join('almacen as a', 'i.codigo_almacen', '=', 'a.codigo_almacen')
                    ->where('i.codigo_producto', $codigo_producto)
                    ->where('a.activo', 1);
                    
                if ($es_clip_recuperado) {
                    $query_lotes->where('i.estado', 0)->where('i.lote', 'LIKE', '%-REC');
                } else {
                    $query_lotes->where(function($q) { $q->where('i.estado', 1)->orWhereNull('i.estado'); });
                }

                if ($codigo_almacen_consumo) {
                    $query_lotes->where('i.codigo_almacen', $codigo_almacen_consumo);
                }

                $tipo_op_orig = $request->input('tipo_operacion_origen');
                $idop_origen = $request->input('idop_origen');
                $comp_idop_origen = !empty($comp['idop_origen']) ? $comp['idop_origen'] : $idop_origen;
                if ($tipo_op_orig === 'encartonado' && !empty($comp_idop_origen) && intval($comp_idop_origen) > 0) {
                    if (str_starts_with($codigo_producto, 'EN') || str_starts_with($codigo_producto, 'CA')) {
                        $lotes_op = DB::table('produccion_ingresos_proceso')
                            ->where('idop', $comp_idop_origen)
                            ->pluck('lote_produccion')
                            ->toArray();
                        if (!empty($lotes_op)) {
                            $query_lotes->whereIn('i.lote', $lotes_op);
                        }
                    }
                }

                $lotes = $query_lotes->select('i.id_inventario', 'i.stock_actual', 'i.lote', 'i.costo_promedio', 'i.codigo_almacen')
                    ->orderBy('i.fecha_vencimiento', 'asc')
                    ->orderBy('i.id_inventario', 'asc')
                    ->lockForUpdate()
                    ->get();
                
                foreach ($lotes as $lote) {
                    if ($cantidad_restante <= 0) break;
                    $consumo = min($lote->stock_actual, $cantidad_restante);
                    
                    $movId = DB::table('movimientos_inventario')->insertGetId([
                        'codigo_almacen' => $lote->codigo_almacen,
                        'codigo_producto' => $codigo_producto,
                        'lote' => $lote->lote,
                        'tipo_movimiento' => 'SALIDA',
                        'cantidad' => $consumo,
                        'costo_unitario' => $lote->costo_promedio,
                        'total' => $consumo * $lote->costo_promedio,
                        'documento_referencia' => 'PRODUCCION',
                        'numero_referencia' => $numero_referencia,
                        'idop' => $idop,
                        'componente_origen_id' => $idComponente,
                        'observaciones' => 'Consumo proceso',
                        'usuario_movimiento' => $usuario_id,
                        'fecha_movimiento' => $fecha_movimiento_general,
                        'estado' => 1
                    ]);
                    
                    $costoConsumo = $consumo * ($lote->costo_promedio ?? 0);
                    $key = $codigo_producto . '|' . $lote->codigo_almacen . '|' . $lote->lote;
                    if (!isset($consumosResumen[$key])) {
                        $consumosResumen[$key] = [
                            'producto' => $codigo_producto,
                            'almacen' => $lote->codigo_almacen,
                            'lote' => $lote->lote,
                            'cantidad' => 0,
                            'total_costo' => 0,
                            'primer_mov_id' => $movId
                        ];
                    }
                    $consumosResumen[$key]['cantidad'] += $consumo;
                    $consumosResumen[$key]['total_costo'] += $costoConsumo;
                    
                    $trace_movimientos++;
                    $nuevo_stock = $lote->stock_actual - $consumo;
                    
                    DB::table('inventario')->where('id_inventario', $lote->id_inventario)->update([
                        'stock_actual' => $nuevo_stock,
                        'estado' => ($nuevo_stock > 0 ? 1 : 0),
                        'fecha_ultimo_movimiento' => $fecha_movimiento_general,
                        'usuario_ultimo_movimiento' => $usuario_id
                    ]);
                    
                    $cantidad_restante -= $consumo;
                }

                if ($cantidad_restante > 0) {
                    $desc_prod = DB::table('producto')->where('codigo', $codigo_producto)->value('descripcion') ?? $codigo_producto;
                    throw new \Exception(
                        "Stock insuficiente para el producto {$desc_prod}. "
                        . "Faltan " . number_format($cantidad_restante, 4) . " unidades."
                    );
                }
            }

            // Kardex SALIDA por cada producto consumido
            foreach ($consumosResumen as $resumen) {
                $stockActual = DB::table('inventario')
                    ->where('codigo_producto', $resumen['producto'])
                    ->where('codigo_almacen', $resumen['almacen'])
                    ->sum('stock_actual') ?? 0;

                $costoSalidaProm = $resumen['cantidad'] > 0
                    ? round($resumen['total_costo'] / $resumen['cantidad'], 9)
                    : 0;
                $totalSaldo = $stockActual * $costoSalidaProm;

                DB::table('kardex')->insert([
                    'codigo_almacen'       => $resumen['almacen'],
                    'codigo_producto'      => $resumen['producto'],
                    'lote'                 => $resumen['lote'],
                    'fecha_movimiento'     => $fecha_movimiento_general,
                    'tipo_movimiento'      => 'SALIDA',
                    'documento'            => 'PRODUCCION',
                    'numero_documento'     => $numero_referencia,
                    'cantidad_entrada'     => 0,
                    'costo_entrada'        => 0,
                    'total_entrada'        => 0,
                    'cantidad_salida'      => $resumen['cantidad'],
                    'costo_salida'         => $costoSalidaProm,
                    'total_salida'         => round($resumen['total_costo'], 2),
                    'cantidad_saldo'       => max(0, $stockActual),
                    'costo_promedio'       => $costoSalidaProm,
                    'total_saldo'          => round($totalSaldo, 9),
                    'codigo_referencia_movimiento' => $resumen['primer_mov_id'],
                    'observaciones'        => 'Consumo de producción',
                    'usuario_registro'     => $usuario_id
                ]);
            }

            DB::table('movimientos_inventario')
                ->where('numero_referencia', $numero_referencia)
                ->where('documento_referencia', 'PRODUCCION')
                ->update(['tiene_kardex' => true]);

            if (in_array($request->input('tipo_operacion_origen'), ['merma_clip', 'recuperado_clip']) && $request->input('destino_merma_clip') === 'recuperado') {
                foreach ($consumosResumen as $resumen) {
                    if ($resumen['cantidad'] <= 0) continue;
                    
                    $lote_recuperado = $resumen['lote'] . '-REC';
                    $almacen_rec = $resumen['almacen'];
                    $codigo_prod_rec = $resumen['producto'];
                    $cantidad_rec = $resumen['cantidad'];
                    $costo_unitario_rec = $resumen['cantidad'] > 0 ? round($resumen['total_costo'] / $resumen['cantidad'], 9) : 0;
                    $total_costo_rec = $resumen['total_costo'];

                    $invExistente = DB::table('inventario')
                        ->where('codigo_producto', $codigo_prod_rec)
                        ->where('codigo_almacen', $almacen_rec)
                        ->where('lote', $lote_recuperado)
                        ->lockForUpdate()
                        ->first();

                    if ($invExistente) {
                        DB::table('inventario')->where('id_inventario', $invExistente->id_inventario)->update([
                            'stock_actual' => $invExistente->stock_actual + $cantidad_rec,
                            'fecha_ultimo_movimiento' => $fecha_movimiento_general,
                            'usuario_ultimo_movimiento' => $usuario_id
                        ]);
                    } else {
                        DB::table('inventario')->insert([
                            'codigo_almacen' => $almacen_rec,
                            'codigo_producto' => $codigo_prod_rec,
                            'lote' => $lote_recuperado,
                            'estado' => 0, // Rechazado / Recuperado
                            'stock_actual' => $cantidad_rec,
                            'costo_promedio' => $costo_unitario_rec,
                            'ultimo_costo' => $costo_unitario_rec,
                            'fecha_ultimo_movimiento' => $fecha_movimiento_general,
                            'usuario_ultimo_movimiento' => $usuario_id
                        ]);
                    }

                    $movIngId = DB::table('movimientos_inventario')->insertGetId([
                        'codigo_almacen' => $almacen_rec,
                        'codigo_producto' => $codigo_prod_rec,
                        'lote' => $lote_recuperado,
                        'tipo_movimiento' => 'INGRESO',
                        'cantidad' => $cantidad_rec,
                        'costo_unitario' => $costo_unitario_rec,
                        'total' => $total_costo_rec,
                        'documento_referencia' => 'PRODUCCION',
                        'numero_referencia' => $numero_referencia,
                        'idop' => $idop,
                        'observaciones' => 'Ingreso de clip recuperado',
                        'usuario_movimiento' => $usuario_id,
                        'fecha_movimiento' => $fecha_movimiento_general,
                        'estado' => 1,
                        'tiene_kardex' => true
                    ]);

                    $stockActualRec = DB::table('inventario')
                        ->where('codigo_producto', $codigo_prod_rec)
                        ->where('codigo_almacen', $almacen_rec)
                        ->sum('stock_actual') ?? 0;

                    $totalSaldoRec = $stockActualRec * $costo_unitario_rec;

                    DB::table('kardex')->insert([
                        'codigo_almacen'       => $almacen_rec,
                        'codigo_producto'      => $codigo_prod_rec,
                        'lote'                 => $lote_recuperado,
                        'fecha_movimiento'     => $fecha_movimiento_general,
                        'tipo_movimiento'      => 'INGRESO',
                        'documento'            => 'PRODUCCION',
                        'numero_documento'     => $numero_referencia,
                        'cantidad_entrada'     => $cantidad_rec,
                        'costo_entrada'        => $costo_unitario_rec,
                        'total_entrada'        => round($total_costo_rec, 2),
                        'cantidad_salida'      => 0,
                        'costo_salida'         => 0,
                        'total_salida'         => 0,
                        'cantidad_saldo'       => max(0, $stockActualRec),
                        'costo_promedio'       => $costo_unitario_rec,
                        'total_saldo'          => round($totalSaldoRec, 9),
                        'codigo_referencia_movimiento' => $movIngId,
                        'observaciones'        => 'Ingreso clip recuperado',
                        'usuario_registro'     => $usuario_id
                    ]);
                    $trace_movimientos++;
                }
            }

            $productos_resultantes = json_decode($request->productos_resultantes_json ?? '[]', true);
            $productos_manuales = [];
            foreach ($productos_resultantes as $pr) {
                if (floatval($pr['cantidad']) > 0) {
                    $productos_manuales[$pr['codigo_producto']] = true;
                }
            }

            $tipo_op_origen = $request->input('tipo_operacion_origen', 'inyectado');
            $almacen_origen = $request->input('codigo_almacen_consumo', 'ALM-PEP');
            $es_merma = in_array($tipo_op_origen, ['purga', 'limpieza', 'merma_pura', 'recuperado_molido', 'recuperado_maquina', 'merma_empaque', 'merma_clip', 'merma_cascara', 'merma_carton']);

            $costo_total_consumos = 0;
            foreach ($consumosResumen as $res) {
                $costo_total_consumos += round($res['total_costo'], 2);
            }

            $ingresos_creados = 0;
            foreach ($grupos_pep as $g) {
                if ($g['cant'] > 0 && !isset($productos_manuales[$g['codigo_pep']])) {
                    $lote_pep = $this->generarCodigoPEP($g['codigo_pep'], $g['color'], $id);
                    
                    if (!empty($g['ids_componentes'])) {
                        DB::table('componentes_orden_produccion_global')
                            ->whereIn('id_op_componentes', $g['ids_componentes'])
                            ->update(['lote_produccion_pep' => $lote_pep]);
                    }

                    if ($es_merma) {
                        $prodPrincipal = DB::table('producto')->where('codigo', $g['codigo_pep'])->first();
                        $descBase = $prodPrincipal->descripcion ?? $g['descripcion_pep'] ?? '';

                        $tipoMerma = 'RECUPERABLE';
                        $motivoM = 'RECUPERADO MOLIDO DE MAQUINA';

                        if ($tipo_op_origen === 'purga') {
                            $tipoMerma = 'PURA';
                            $motivoM = 'PURGA';
                        } elseif ($tipo_op_origen === 'limpieza') {
                            $tipoMerma = 'RECUPERABLE';
                            $motivoM = 'LIMPIEZA';
                        } elseif ($tipo_op_origen === 'merma_empaque') {
                            $tipoMerma = 'RECUPERABLE';
                            $motivoM = 'MERMA EMPAQUE';
                        } elseif ($tipo_op_origen === 'merma_clip') {
                            $tipoMerma = 'RECUPERABLE';
                            $motivoM = 'MERMA CLIP';
                        } elseif ($tipo_op_origen === 'merma_cascara') {
                            $tipoMerma = 'RECUPERABLE';
                            $motivoM = 'CASCARA PARA MOLIDO';
                        } elseif ($tipo_op_origen === 'merma_carton') {
                            $tipoMerma = 'DESECHO';
                            $motivoM = 'MERMA CARTON';
                        } elseif ($tipo_op_origen === 'recuperado_molido') {
                            $tipoMerma = 'MOLIDO';
                            $motivoM = 'RECUPERADO PARA MOLER';
                        } elseif ($tipo_op_origen === 'recuperado_maquina') {
                            $tipoMerma = 'RECUPERABLE';
                            $motivoM = 'RECUPERADO MOLIDO DE MAQUINA';
                        } elseif ($tipo_op_origen === 'merma_pura') {
                            $tipoMerma = 'PURA';
                            $motivoM = 'MERMA PURA';
                        }

                        $costo_unitario_merma = round($costo_total_consumos / $g['cant'], 6);

                        $id_merma = DB::table('mermas')->insertGetId([
                            'fecha_merma' => \Carbon\Carbon::parse($fecha_movimiento_general)->format('Y-m-d'),
                            'hora_inicio' => $componentes[0]['hora_inicio'] ?? null,
                            'hora_fin' => $componentes[0]['hora_fin'] ?? null,
                            'codigo_trabajador' => $componentes[0]['codigo_trabajador'] ?? null,
                            'codigo_producto' => $g['codigo_pep'],
                            'descripcion_producto' => $descBase,
                            'cantidad' => $g['cant'],
                            'costo_unitario' => $costo_unitario_merma,
                            'costo_total' => $costo_total_consumos,
                            'motivo' => $motivoM,
                            'tipo_merma' => $tipoMerma,
                            'codigo_almacen' => $almacen_origen,
                            'id_orden_produccion' => $idop,
                            'estado' => 1,
                            'usuario_registro' => $usuario_id,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        if (!in_array($tipo_op_origen, ['merma_pura', 'purga'])) {
                            // Generar el ingreso a Kardex de la Merma Recuperable
                            $param = DB::table('parametros_sistema')->where('codigo_parametro', 'PORCENTAJE_COSTO_RECICLADO')->first();
                            $porcentaje = $param ? (float) $param->valor : 0.8;
                            
                            $costoReciclado = $costo_unitario_merma * $porcentaje;
                            $totalEntradaRec = round($g['cant'] * $costoReciclado, 2);

                            $codRecuperado = $this->determinarCodigoRecuperado($g['codigo_pep'], $g['formula'], $tipo_op_origen);
                            $prodRec = DB::table('producto')->where('codigo', $codRecuperado)->first();
                            
                            if (!$prodRec) {
                                DB::table('producto')->insert([
                                    'codigo' => $codRecuperado,
                                    'descripcion' => 'RECUPERADO - ' . $descBase,
                                    'codigo_tipo_producto' => 'REC',
                                    'codigo_unidad_medida' => $prodPrincipal->codigo_unidad_medida ?? 'KG',
                                    'estado' => 1
                                ]);
                            }

                            $lote_rec = $this->generarCodigoPEP($codRecuperado, $g['color'], $id);

                            $invRec = DB::table('inventario')
                                ->where('codigo_producto', $codRecuperado)
                                ->where('codigo_almacen', $almacen_origen)
                                ->where('lote', $lote_rec)
                                ->first();

                            if (!$invRec) {
                                DB::table('inventario')->insert([
                                    'codigo_producto' => $codRecuperado,
                                    'codigo_almacen' => $almacen_origen,
                                    'lote' => $lote_rec,
                                    'stock_actual' => $g['cant'],
                                    'costo_promedio' => $costoReciclado,
                                    'ultimo_costo' => $costoReciclado,
                                    'stock_minimo' => 0,
                                    'stock_maximo' => 0,
                                    'estado' => 1,
                                    'fecha_ultimo_movimiento' => $fecha_movimiento_general,
                                    'usuario_ultimo_movimiento' => $usuario_id
                                ]);
                            } else {
                                $saldoAnteriorRec = $invRec->stock_actual ?? 0;
                                $costoPromAnteriorRec = $invRec->costo_promedio ?? 0;
                                $nuevoSaldoRec = $saldoAnteriorRec + $g['cant'];
                                $nuevoTotalSaldoRec = ($saldoAnteriorRec * $costoPromAnteriorRec) + $totalEntradaRec;
                                $nuevoCostoPromRec = $nuevoSaldoRec > 0 ? round($nuevoTotalSaldoRec / $nuevoSaldoRec, 6) : 0;

                                DB::table('inventario')
                                    ->where('id_inventario', $invRec->id_inventario)
                                    ->update([
                                        'stock_actual' => $nuevoSaldoRec,
                                        'costo_promedio' => $nuevoCostoPromRec,
                                        'fecha_ultimo_movimiento' => $fecha_movimiento_general,
                                        'usuario_ultimo_movimiento' => $usuario_id
                                    ]);
                            }

                            $stockTotalAlmacen = DB::table('inventario')
                                ->where('codigo_producto', $codRecuperado)
                                ->where('codigo_almacen', $almacen_origen)
                                ->sum('stock_actual') ?? 0;

                            $numeroDocMerma = 'MERMA-' . str_pad($id_merma, 6, '0', STR_PAD_LEFT);

                            DB::table('kardex')->insert([
                                'codigo_producto' => $codRecuperado,
                                'codigo_almacen' => $almacen_origen,
                                'lote' => $lote_rec,
                                'fecha_movimiento' => $fecha_movimiento_general,
                                'tipo_movimiento' => 'INGRESO',
                                'documento' => $motivoM,
                                'numero_documento' => $numeroDocMerma,
                                'cantidad_entrada' => $g['cant'],
                                'costo_entrada' => $costoReciclado,
                                'total_entrada' => $totalEntradaRec,
                                'cantidad_salida' => 0,
                                'costo_salida' => 0,
                                'total_salida' => 0,
                                'cantidad_saldo' => $stockTotalAlmacen,
                                'costo_promedio' => $costoReciclado,
                                'total_saldo' => round($stockTotalAlmacen * $costoReciclado, 6),
                                'observaciones' => "INGRESO DE MATERIAL RECUPERADO DE MERMA OP-{$idop}",
                                'usuario_registro' => $usuario_id
                            ]);
                        }
                    } else {
                        $costo_unitario_pep = $g['cant'] > 0 ? round($costo_total_consumos / $g['cant'], 6) : 0;
                        $totalEntradaPep = round($g['cant'] * $costo_unitario_pep, 2);

                        // 1. Registrar o actualizar en inventario directamente en el almacén de consumo
                        $invPep = DB::table('inventario')
                            ->where('codigo_producto', $g['codigo_pep'])
                            ->where('codigo_almacen', $almacen_origen)
                            ->where('lote', $lote_pep)
                            ->first();

                        if (!$invPep) {
                            DB::table('inventario')->insert([
                                'codigo_producto' => $g['codigo_pep'],
                                'codigo_almacen' => $almacen_origen,
                                'lote' => $lote_pep,
                                'stock_actual' => $g['cant'],
                                'costo_promedio' => $costo_unitario_pep,
                                'ultimo_costo' => $costo_unitario_pep,
                                'stock_minimo' => 0,
                                'stock_maximo' => 0,
                                'estado' => 1,
                                'fecha_ultimo_movimiento' => $fecha_movimiento_general,
                                'usuario_ultimo_movimiento' => $usuario_id
                            ]);
                        } else {
                            $saldoAnt = $invPep->stock_actual ?? 0;
                            $costoPromAnt = $invPep->costo_promedio ?? 0;
                            $nuevoSaldo = $saldoAnt + $g['cant'];
                            $nuevoTotalSaldo = ($saldoAnt * $costoPromAnt) + $totalEntradaPep;
                            $nuevoCostoProm = $nuevoSaldo > 0 ? round($nuevoTotalSaldo / $nuevoSaldo, 6) : 0;

                            DB::table('inventario')
                                ->where('id_inventario', $invPep->id_inventario)
                                ->update([
                                    'stock_actual' => $nuevoSaldo,
                                    'costo_promedio' => $nuevoCostoProm,
                                    'fecha_ultimo_movimiento' => $fecha_movimiento_general,
                                    'usuario_ultimo_movimiento' => $usuario_id
                                ]);
                        }

                        $stockTotalAlmacenPep = DB::table('inventario')
                            ->where('codigo_producto', $g['codigo_pep'])
                            ->where('codigo_almacen', $almacen_origen)
                            ->sum('stock_actual') ?? 0;

                        $numeroDocPep = "OP-{$idop}-PROC-{$id}";

                        // 2. Movimiento Inventario
                        $movPepId = DB::table('movimientos_inventario')->insertGetId([
                            'codigo_almacen' => $almacen_origen,
                            'codigo_producto' => $g['codigo_pep'],
                            'lote' => $lote_pep,
                            'tipo_movimiento' => 'INGRESO',
                            'cantidad' => $g['cant'],
                            'costo_unitario' => $costo_unitario_pep,
                            'total' => $totalEntradaPep,
                            'documento_referencia' => 'PRODUCCION_PEP',
                            'numero_referencia' => $numeroDocPep,
                            'idop' => $idop,
                            'observaciones' => "Ingreso directo de producción OP-{$idop} Proceso {$id}",
                            'usuario_movimiento' => $usuario_id,
                            'fecha_movimiento' => $fecha_movimiento_general,
                            'estado' => 1,
                            'tiene_kardex' => true
                        ]);

                        // 3. Kardex
                        DB::table('kardex')->insert([
                            'codigo_producto' => $g['codigo_pep'],
                            'codigo_almacen' => $almacen_origen,
                            'lote' => $lote_pep,
                            'fecha_movimiento' => $fecha_movimiento_general,
                            'tipo_movimiento' => 'INGRESO',
                            'documento' => 'RECEPCION_PEP',
                            'numero_documento' => $numeroDocPep,
                            'cantidad_entrada' => $g['cant'],
                            'costo_entrada' => $costo_unitario_pep,
                            'total_entrada' => $totalEntradaPep,
                            'cantidad_salida' => 0,
                            'costo_salida' => 0,
                            'total_salida' => 0,
                            'cantidad_saldo' => $stockTotalAlmacenPep,
                            'costo_promedio' => $costo_unitario_pep,
                            'total_saldo' => round($stockTotalAlmacenPep * $costo_unitario_pep, 6),
                            'codigo_referencia_movimiento' => $movPepId,
                            'observaciones' => "Ingreso directo de producción OP-{$idop}",
                            'usuario_registro' => $usuario_id
                        ]);

                        // 4. Registro histórico en produccion_ingresos_proceso (APROBADO directo)
                        DB::table('produccion_ingresos_proceso')->insert([
                            'idop' => $idop,
                            'id_proceso' => $id,
                            'codigo_producto_proceso' => $g['codigo_pep'],
                            'descripcion_producto_proceso' => $g['descripcion_pep'],
                            'cantidad' => $g['cant'],
                            'codigo_unidad_medida' => $g['unidad'],
                            'codigo_almacen' => $almacen_origen,
                            'lote_produccion' => $lote_pep,
                            'fecha_ingreso' => $fecha_movimiento_general,
                            'usuario_registro' => $usuario_id,
                            'estado' => 'APROBADO',
                            'codigo_molde' => $codigo_molde_op,
                            'descripcion_molde' => $descripcion_molde_op
                        ]);
                        $ingresos_creados++;
                    }
                }
            }

            // Procesar productos resultantes manuales (no formula-based)
            $productos_resultantes = json_decode($request->productos_resultantes_json ?? '[]', true);
            foreach ($productos_resultantes as $pr) {
                $codigo_pr = $pr['codigo_producto'];
                $cantidad_pr = floatval($pr['cantidad']);
                $unidad_pr = $pr['codigo_unidad_medida'] ?? 'KG';

                if ($cantidad_pr > 0) {
                    $desc_pr = DB::table('producto')->where('codigo', $codigo_pr)->value('descripcion') ?? $codigo_pr;
                    $lote_pr = $this->generarCodigoPEP($codigo_pr, null, $id);
                    $costo_unitario_pr = $cantidad_pr > 0 ? round($costo_total_consumos / $cantidad_pr, 6) : 0;
                    $totalEntradaPr = round($cantidad_pr * $costo_unitario_pr, 2);

                    $invPr = DB::table('inventario')
                        ->where('codigo_producto', $codigo_pr)
                        ->where('codigo_almacen', $almacen_origen)
                        ->where('lote', $lote_pr)
                        ->first();

                    if (!$invPr) {
                        DB::table('inventario')->insert([
                            'codigo_producto' => $codigo_pr,
                            'codigo_almacen' => $almacen_origen,
                            'lote' => $lote_pr,
                            'stock_actual' => $cantidad_pr,
                            'costo_promedio' => $costo_unitario_pr,
                            'ultimo_costo' => $costo_unitario_pr,
                            'stock_minimo' => 0,
                            'stock_maximo' => 0,
                            'estado' => 1,
                            'fecha_ultimo_movimiento' => $fecha_movimiento_general,
                            'usuario_ultimo_movimiento' => $usuario_id
                        ]);
                    } else {
                        $saldoAnt = $invPr->stock_actual ?? 0;
                        $costoPromAnt = $invPr->costo_promedio ?? 0;
                        $nuevoSaldo = $saldoAnt + $cantidad_pr;
                        $nuevoTotalSaldo = ($saldoAnt * $costoPromAnt) + $totalEntradaPr;
                        $nuevoCostoProm = $nuevoSaldo > 0 ? round($nuevoTotalSaldo / $nuevoSaldo, 6) : 0;

                        DB::table('inventario')
                            ->where('id_inventario', $invPr->id_inventario)
                            ->update([
                                'stock_actual' => $nuevoSaldo,
                                'costo_promedio' => $nuevoCostoProm,
                                'fecha_ultimo_movimiento' => $fecha_movimiento_general,
                                'usuario_ultimo_movimiento' => $usuario_id
                            ]);
                    }

                    $stockTotalAlmacenPr = DB::table('inventario')
                        ->where('codigo_producto', $codigo_pr)
                        ->where('codigo_almacen', $almacen_origen)
                        ->sum('stock_actual') ?? 0;

                    $numeroDocPr = "OP-{$idop}-PROC-{$id}";

                    $movPrId = DB::table('movimientos_inventario')->insertGetId([
                        'codigo_almacen' => $almacen_origen,
                        'codigo_producto' => $codigo_pr,
                        'lote' => $lote_pr,
                        'tipo_movimiento' => 'INGRESO',
                        'cantidad' => $cantidad_pr,
                        'costo_unitario' => $costo_unitario_pr,
                        'total' => $totalEntradaPr,
                        'documento_referencia' => 'PRODUCCION',
                        'numero_referencia' => $numeroDocPr,
                        'idop' => $idop,
                        'observaciones' => "Ingreso directo de producto manual OP-{$idop} Proceso {$id}",
                        'usuario_movimiento' => $usuario_id,
                        'fecha_movimiento' => $fecha_movimiento_general,
                        'estado' => 1,
                        'tiene_kardex' => true
                    ]);

                    DB::table('kardex')->insert([
                        'codigo_producto' => $codigo_pr,
                        'codigo_almacen' => $almacen_origen,
                        'lote' => $lote_pr,
                        'fecha_movimiento' => $fecha_movimiento_general,
                        'tipo_movimiento' => 'INGRESO',
                        'documento' => 'RECEPCION_PEP',
                        'numero_documento' => $numeroDocPr,
                        'cantidad_entrada' => $cantidad_pr,
                        'costo_entrada' => $costo_unitario_pr,
                        'total_entrada' => $totalEntradaPr,
                        'cantidad_salida' => 0,
                        'costo_salida' => 0,
                        'total_salida' => 0,
                        'cantidad_saldo' => $stockTotalAlmacenPr,
                        'costo_promedio' => $costo_unitario_pr,
                        'total_saldo' => round($stockTotalAlmacenPr * $costo_unitario_pr, 6),
                        'codigo_referencia_movimiento' => $movPrId,
                        'observaciones' => "Ingreso directo de producto manual OP-{$idop}",
                        'usuario_registro' => $usuario_id
                    ]);

                    DB::table('produccion_ingresos_proceso')->insert([
                        'idop' => $idop,
                        'id_proceso' => $id,
                        'codigo_producto_proceso' => $codigo_pr,
                        'descripcion_producto_proceso' => $desc_pr,
                        'cantidad' => $cantidad_pr,
                        'codigo_unidad_medida' => $unidad_pr,
                        'codigo_almacen' => $almacen_origen,
                        'lote_produccion' => $lote_pr,
                        'fecha_ingreso' => $fecha_movimiento_general,
                        'usuario_registro' => $usuario_id,
                        'estado' => 'APROBADO',
                        'codigo_molde' => $codigo_molde_op,
                        'descripcion_molde' => $descripcion_molde_op
                    ]);
                    $ingresos_creados++;
                }
            }
            OrdenProceso::where('id', $id)->where(function($q) {
                $q->where('estado_avance', 'PENDIENTE')->orWhereNull('estado_avance');
            })->update(['estado_avance' => 'EN_PROCESO']);

            DB::table('orden_produccion_global')->where('idop', $idop)->where('estado', 'PENDIENTE')->update(['estado' => 'EN_PROCESO']);

            $kardexService = app(\App\Services\KardexService::class);
            $recalculados = [];
            foreach ($consumosResumen as $res) {
                $k = $res['producto'] . '|' . $res['almacen'];
                if (!isset($recalculados[$k])) {
                    $kardexService->recalcular($res['producto'], $res['almacen']);
                    $recalculados[$k] = true;
                }
            }

            DB::commit();
            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => "Componentes guardados. Movimientos: $trace_movimientos, PEPs: $ingresos_creados."]);
            }
            return back()->with('success', "Componentes guardados. Movimientos: $trace_movimientos, PEPs: $ingresos_creados.");
            
        } catch (\Exception $e) {
            \Log::error('Error en storeComponentes: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Error al procesar: ' . $e->getMessage()], 422);
            }
            return back()->with('error', 'Error al procesar: ' . $e->getMessage());
        }
    }

    public function updateComponente(Request $request, $idop, $id, $id_componente)
    {
        $request->validate([
            'cantidad'        => 'required|numeric|min:0.01',
            'codigo_trabajador' => 'nullable|string|max:20',
            'fecha_inicio'    => 'nullable|date',
            'fecha_fin'       => 'nullable|date',
            'hora_inicio'     => 'nullable',
            'hora_fin'        => 'nullable',
            'fecha_inicio_maquina' => 'nullable|date',
            'fecha_fin_maquina'    => 'nullable|date',
            'hora_inicio_maquina'  => 'nullable',
            'hora_fin_maquina'     => 'nullable',
            'inyectada_inicial'    => 'nullable|integer',
            'inyectada_final'      => 'nullable|integer',
            'numero_ot'            => 'nullable|string|max:50',
            'observacion'          => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $componente = ComponenteOrdenProduccion::where('id_op_componentes', $id_componente)
                ->where('id_proceso', $id)
                ->where('estado', 1)
                ->lockForUpdate()
                ->firstOrFail();

            $orden = OrdenProduccion::findOrFail($idop);
            if ($orden->estado === 'COMPLETADO' || $orden->activo == 0) {
                throw new \Exception("No se pueden modificar componentes en una orden " . ($orden->activo == 0 ? 'ANULADA' : 'COMPLETADA') . ".");
            }

            $proceso = OrdenProceso::findOrFail($id);
            if ($proceso->estado_avance === 'COMPLETADO') {
                throw new \Exception("No se puede modificar un componente de un proceso COMPLETADO.");
            }

            if ($componente->codigo_tipo_producto === 'ACT') {
                $componente->update([
                    'cantidad' => $request->has('cantidad') ? floatval($request->cantidad) : $componente->cantidad,
                    'codigo_trabajador' => $request->codigo_trabajador,
                    'descripcion_trabajador' => $request->codigo_trabajador
                        ? DB::table('trabajador')->where('codigo', $request->codigo_trabajador)->value('nombre')
                        : $componente->descripcion_trabajador,
                    'fecha_inicio' => $request->has('fecha_inicio') ? $request->input('fecha_inicio') : $componente->fecha_inicio,
                    'fecha_fin'    => $request->has('fecha_fin') ? $request->input('fecha_fin') : $componente->fecha_fin,
                    'hora_inicio'  => $request->has('hora_inicio') ? $request->input('hora_inicio') : $componente->hora_inicio,
                    'hora_fin'     => $request->has('hora_fin') ? $request->input('hora_fin') : $componente->hora_fin,
                    'fecha_inicio_maquina' => $request->has('fecha_inicio_maquina') ? $request->input('fecha_inicio_maquina') : $componente->fecha_inicio_maquina,
                    'fecha_fin_maquina'    => $request->has('fecha_fin_maquina') ? $request->input('fecha_fin_maquina') : $componente->fecha_fin_maquina,
                    'hora_inicio_maquina'  => $request->has('hora_inicio_maquina') ? $request->input('hora_inicio_maquina') : $componente->hora_inicio_maquina,
                    'hora_fin_maquina'     => $request->has('hora_fin_maquina') ? $request->input('hora_fin_maquina') : $componente->hora_fin_maquina,
                    'inyectada_inicial'    => $request->has('inyectada_inicial') ? ($request->filled('inyectada_inicial') ? intval($request->input('inyectada_inicial')) : null) : $componente->inyectada_inicial,
                    'inyectada_final'      => $request->has('inyectada_final') ? ($request->filled('inyectada_final') ? intval($request->input('inyectada_final')) : null) : $componente->inyectada_final,
                    'numero_ot'            => $request->has('numero_ot') ? (!empty($request->input('numero_ot')) ? trim($request->input('numero_ot')) : null) : $componente->numero_ot,
                    'observacion'          => $request->has('observacion') ? $request->input('observacion') : $componente->observacion,
                ]);
                DB::commit();
                return back()->with('success', 'Actividad actualizada correctamente.');
            }

            $nuevaCantidad = floatval($request->cantidad);
            $originalCantidad = floatval($componente->cantidad);
            $diferencia = $nuevaCantidad - $originalCantidad;

            $movimientosOrigen = DB::table('movimientos_inventario')
                ->where('componente_origen_id', $id_componente)
                ->where('documento_referencia', 'PRODUCCION')
                ->where('estado', 1)
                ->orderBy('id_movimiento', 'asc')
                ->get();

            if ($movimientosOrigen->isEmpty()) {
                $componente->update([
                    'codigo_trabajador' => $request->codigo_trabajador,
                    'descripcion_trabajador' => $request->codigo_trabajador
                        ? DB::table('trabajador')->where('codigo', $request->codigo_trabajador)->value('nombre')
                        : $componente->descripcion_trabajador,
                    'fecha_inicio' => $request->has('fecha_inicio') ? $request->input('fecha_inicio') : $componente->fecha_inicio,
                    'fecha_fin'    => $request->has('fecha_fin') ? $request->input('fecha_fin') : $componente->fecha_fin,
                    'hora_inicio'  => $request->has('hora_inicio') ? $request->input('hora_inicio') : $componente->hora_inicio,
                    'hora_fin'     => $request->has('hora_fin') ? $request->input('hora_fin') : $componente->hora_fin,
                    'fecha_inicio_maquina' => $request->has('fecha_inicio_maquina') ? $request->input('fecha_inicio_maquina') : $componente->fecha_inicio_maquina,
                    'fecha_fin_maquina'    => $request->has('fecha_fin_maquina') ? $request->input('fecha_fin_maquina') : $componente->fecha_fin_maquina,
                    'hora_inicio_maquina'  => $request->has('hora_inicio_maquina') ? $request->input('hora_inicio_maquina') : $componente->hora_inicio_maquina,
                    'hora_fin_maquina'     => $request->has('hora_fin_maquina') ? $request->input('hora_fin_maquina') : $componente->hora_fin_maquina,
                    'inyectada_inicial'    => $request->has('inyectada_inicial') ? ($request->filled('inyectada_inicial') ? intval($request->input('inyectada_inicial')) : null) : $componente->inyectada_inicial,
                    'inyectada_final'      => $request->has('inyectada_final') ? ($request->filled('inyectada_final') ? intval($request->input('inyectada_final')) : null) : $componente->inyectada_final,
                    'numero_ot'            => $request->has('numero_ot') ? (!empty($request->input('numero_ot')) ? trim($request->input('numero_ot')) : null) : $componente->numero_ot,
                    'observacion'          => $request->has('observacion') ? $request->input('observacion') : $componente->observacion,
                    'cantidad'     => $nuevaCantidad,
                ]);
                DB::commit();
                return back()->with('success', 'Componente actualizado (sin movimientos de inventario asociados).');
            }

            $codigo_producto = $componente->codigo_producto;
            $numero_referencia = $movimientosOrigen->first()->numero_referencia;
            $idop = $componente->idop;
            $usuario_id = Auth::id();

            $pepGenerado = DB::table('produccion_ingresos_proceso')
                ->where('id_proceso', $id)
                ->where('estado', 'APROBADO')
                ->exists();

            if ($pepGenerado && $diferencia != 0) {
                throw new \Exception(
                    "No se puede modificar la cantidad porque el PEP asociado ya fue recibido en almacén. "
                    . "Solo puede editar los datos de trabajador y fechas."
                );
            }

            if ($diferencia > 0) {
                $codigo_almacen_origen = $movimientosOrigen->first()->codigo_almacen ?? null;

                $query_disp = DB::table('inventario as i')
                    ->join('almacen as a', 'i.codigo_almacen', '=', 'a.codigo_almacen')
                    ->where('i.codigo_producto', $codigo_producto)
                    ->where('a.activo', 1)
                    ->where(function($q) { $q->where('i.estado', 1)->orWhereNull('i.estado'); });

                if ($codigo_almacen_origen) {
                    $query_disp->where('i.codigo_almacen', $codigo_almacen_origen);
                }

                $stockDisp = $query_disp->lockForUpdate()->sum('i.stock_actual') ?? 0;

                if ($stockDisp < $diferencia) {
                    $alm_msg = $codigo_almacen_origen ? " en el almacén original ($codigo_almacen_origen)" : " en almacén";
                    throw new \Exception(
                        "Stock insuficiente para aumentar la cantidad. "
                        . "Disponible: " . number_format($stockDisp, 2)
                        . ", Requerido adicional: " . number_format($diferencia, 2)
                        . $alm_msg
                    );
                }

                $cantidad_restante = $diferencia;
                $query_lotes = DB::table('inventario as i')
                    ->join('almacen as a', 'i.codigo_almacen', '=', 'a.codigo_almacen')
                    ->where('i.codigo_producto', $codigo_producto)
                    ->where('a.activo', 1)
                    ->where('i.stock_actual', '>', 0)
                    ->where(function($q) { $q->where('i.estado', 1)->orWhereNull('i.estado'); });

                if ($codigo_almacen_origen) {
                    $query_lotes->where('i.codigo_almacen', $codigo_almacen_origen);
                }

                $lotes = $query_lotes->select('i.id_inventario', 'i.stock_actual', 'i.lote', 'i.costo_promedio', 'i.codigo_almacen')
                    ->orderBy('i.fecha_vencimiento', 'asc')
                    ->orderBy('i.id_inventario', 'asc')
                    ->lockForUpdate()
                    ->get();

                $consumosResumen = [];
                foreach ($lotes as $lote) {
                    if ($cantidad_restante <= 0) break;
                    $consumo = min($lote->stock_actual, $cantidad_restante);

                    $movId = DB::table('movimientos_inventario')->insertGetId([
                        'codigo_almacen'          => $lote->codigo_almacen,
                        'codigo_producto'         => $codigo_producto,
                        'lote'                    => $lote->lote,
                        'tipo_movimiento'         => 'SALIDA',
                        'cantidad'                => $consumo,
                        'costo_unitario'          => $lote->costo_promedio,
                        'total'                   => $consumo * $lote->costo_promedio,
                        'documento_referencia'    => 'PRODUCCION',
                        'numero_referencia'       => $numero_referencia . '-AJ-' . $id_componente,
                        'idop'                    => $idop,
                        'componente_origen_id'    => $id_componente,
                        'observaciones'           => 'Ajuste por edición de componente #' . $id_componente,
                        'usuario_movimiento'      => $usuario_id,
                        'fecha_movimiento'        => now(),
                        'estado'                  => 1,
                        'tiene_kardex'            => true,
                    ]);

                    $key = $codigo_producto . '|' . $lote->codigo_almacen . '|' . $lote->lote;
                    if (!isset($consumosResumen[$key])) {
                        $consumosResumen[$key] = [
                            'producto' => $codigo_producto,
                            'almacen'  => $lote->codigo_almacen,
                            'lote'     => $lote->lote,
                            'cantidad' => 0,
                            'primer_mov_id' => $movId,
                        ];
                    }
                    $consumosResumen[$key]['cantidad'] += $consumo;

                    $nuevo_stock_lote = $lote->stock_actual - $consumo;
                    DB::table('inventario')
                        ->where('id_inventario', $lote->id_inventario)
                        ->update([
                            'stock_actual' => $nuevo_stock_lote,
                            'estado'       => ($nuevo_stock_lote > 0 ? 1 : 0),
                            'fecha_ultimo_movimiento' => now(),
                            'usuario_ultimo_movimiento' => $usuario_id
                        ]);

                    $cantidad_restante -= $consumo;
                }

                if ($cantidad_restante > 0) {
                    $desc_prod = DB::table('producto')->where('codigo', $codigo_producto)->value('descripcion') ?? $codigo_producto;
                    throw new \Exception(
                        "Stock insuficiente para el producto {$desc_prod}. "
                        . "Faltan " . number_format($cantidad_restante, 4) . " unidades."
                    );
                }

                foreach ($consumosResumen as $resumen) {
                    $stockActual = DB::table('inventario')
                        ->where('codigo_producto', $resumen['producto'])
                        ->where('codigo_almacen', $resumen['almacen'])
                        ->sum('stock_actual') ?? 0;

                    DB::table('kardex')->insert([
                        'codigo_almacen'              => $resumen['almacen'],
                        'codigo_producto'             => $resumen['producto'],
                        'lote'                        => $resumen['lote'],
                        'fecha_movimiento'            => now(),
                        'tipo_movimiento'             => 'SALIDA',
                        'documento'                   => 'PRODUCCION',
                        'numero_documento'            => $numero_referencia . '-AJ-' . $id_componente,
                        'cantidad_entrada'            => 0,
                        'cantidad_salida'             => $resumen['cantidad'],
                        'cantidad_saldo'              => $stockActual,
                        'codigo_referencia_movimiento' => $resumen['primer_mov_id'],
                        'observaciones'               => 'Ajuste por edición de componente #' . $id_componente,
                        'usuario_registro'            => $usuario_id,
                    ]);
                }
            }

            if ($diferencia < 0) {
                $devolver = abs($diferencia);

                foreach ($movimientosOrigen as $mov) {
                    if ($devolver <= 0) break;

                    $devolverLote = min($mov->cantidad, $devolver);

                    DB::table('inventario')
                        ->where('codigo_producto', $mov->codigo_producto)
                        ->where('lote', $mov->lote)
                        ->where('codigo_almacen', $mov->codigo_almacen)
                        ->update([
                            'stock_actual' => DB::raw("stock_actual + {$devolverLote}"),
                            'estado'       => 1,
                        ]);

                    $movDevId = DB::table('movimientos_inventario')->insertGetId([
                        'codigo_almacen'          => $mov->codigo_almacen,
                        'codigo_producto'         => $mov->codigo_producto,
                        'lote'                    => $mov->lote,
                        'tipo_movimiento'         => 'INGRESO',
                        'cantidad'                => $devolverLote,
                        'costo_unitario'          => $mov->costo_unitario,
                        'total'                   => $devolverLote * $mov->costo_unitario,
                        'documento_referencia'    => 'DEVOLUCION_EDIT',
                        'numero_referencia'       => $numero_referencia . '-AJ-' . $id_componente,
                        'idop'                    => $idop,
                        'componente_origen_id'    => $id_componente,
                        'observaciones'           => 'Devolución por edición de componente #' . $id_componente,
                        'usuario_movimiento'      => $usuario_id,
                        'fecha_movimiento'        => now(),
                        'estado'                  => 1,
                        'tiene_kardex'            => true,
                    ]);

                    $stockActual = DB::table('inventario')
                        ->where('codigo_producto', $mov->codigo_producto)
                        ->where('codigo_almacen', $mov->codigo_almacen)
                        ->sum('stock_actual') ?? 0;

                    DB::table('kardex')->insert([
                        'codigo_almacen'              => $mov->codigo_almacen,
                        'codigo_producto'             => $mov->codigo_producto,
                        'fecha_movimiento'            => now(),
                        'tipo_movimiento'             => 'INGRESO',
                        'documento'                   => 'DEVOLUCION_EDIT',
                        'numero_documento'            => $numero_referencia . '-AJ-' . $id_componente,
                        'cantidad_entrada'            => $devolverLote,
                        'cantidad_salida'             => 0,
                        'cantidad_saldo'              => $stockActual,
                        'codigo_referencia_movimiento' => $movDevId,
                        'observaciones'               => 'Devolución por edición de componente #' . $id_componente,
                        'usuario_registro'            => $usuario_id,
                    ]);

                    $devolver -= $devolverLote;
                }
            }

            if (!empty($componente->codigo_formula_produccion)) {
                $codigo_pep = $this->determinarCodigoPEP($componente->codigo_formula_produccion);
            } elseif ($componente->codigo_tipo_producto === 'PEP') {
                $codigo_pep = $this->determinarPEPdesdeProducto($componente->codigo_producto);
            } elseif ($componente->codigo_tipo_producto !== 'ACT') {
                $op = DB::table('orden_produccion_global')->where('idop', $idop)->first();
                if ($op && !empty($op->descripcion_producto_proceso)) {
                    $producto_pep = DB::table('producto')
                        ->where('descripcion', $op->descripcion_producto_proceso)
                        ->where('codigo_tipo_producto', 'PEP')
                        ->where('estado', 1)
                        ->first();
                    $codigo_pep = $producto_pep ? $producto_pep->codigo : null;
                } else {
                    $codigo_pep = null;
                }
            } else {
                $codigo_pep = null;
            }

            if ($codigo_pep && $diferencia != 0) {
                $pepRow = DB::table('produccion_ingresos_proceso')
                    ->where('id_proceso', $id)
                    ->where('codigo_producto_proceso', $codigo_pep)
                    ->first();
                if ($pepRow) {
                    if ($pepRow->estado === 'APROBADO' && $diferencia < 0) {
                        throw new \Exception("No se puede reducir la cantidad porque el PEP asociado ya fue recibido en almacén.");
                    }
                    $nuevaCantidadPEP = round(max(0, $pepRow->cantidad + $diferencia), 4);
                    if ($nuevaCantidadPEP <= 0.0001) {
                        DB::table('produccion_ingresos_proceso')->where('id', $pepRow->id)->delete();
                    } else {
                        DB::table('produccion_ingresos_proceso')->where('id', $pepRow->id)->update(['cantidad' => $nuevaCantidadPEP]);
                    }
                }
            }

            $componente->update([
                'codigo_trabajador' => $request->codigo_trabajador,
                'descripcion_trabajador' => $request->codigo_trabajador
                    ? DB::table('trabajador')->where('codigo', $request->codigo_trabajador)->value('nombre')
                    : $componente->descripcion_trabajador,
                'fecha_inicio' => $request->has('fecha_inicio') ? $request->input('fecha_inicio') : $componente->fecha_inicio,
                'fecha_fin'    => $request->has('fecha_fin') ? $request->input('fecha_fin') : $componente->fecha_fin,
                'hora_inicio'  => $request->has('hora_inicio') ? $request->input('hora_inicio') : $componente->hora_inicio,
                'hora_fin'     => $request->has('hora_fin') ? $request->input('hora_fin') : $componente->hora_fin,
                'fecha_inicio_maquina' => $request->has('fecha_inicio_maquina') ? $request->input('fecha_inicio_maquina') : $componente->fecha_inicio_maquina,
                'fecha_fin_maquina'    => $request->has('fecha_fin_maquina') ? $request->input('fecha_fin_maquina') : $componente->fecha_fin_maquina,
                'hora_inicio_maquina'  => $request->has('hora_inicio_maquina') ? $request->input('hora_inicio_maquina') : $componente->hora_inicio_maquina,
                'hora_fin_maquina'     => $request->has('hora_fin_maquina') ? $request->input('hora_fin_maquina') : $componente->hora_fin_maquina,
                'inyectada_inicial'    => $request->has('inyectada_inicial') ? ($request->filled('inyectada_inicial') ? intval($request->input('inyectada_inicial')) : null) : $componente->inyectada_inicial,
                'inyectada_final'      => $request->has('inyectada_final') ? ($request->filled('inyectada_final') ? intval($request->input('inyectada_final')) : null) : $componente->inyectada_final,
                'numero_ot'            => $request->has('numero_ot') ? (!empty($request->input('numero_ot')) ? trim($request->input('numero_ot')) : null) : $componente->numero_ot,
                'observacion'          => $request->has('observacion') ? $request->input('observacion') : $componente->observacion,
                'cantidad'     => $nuevaCantidad,
            ]);

            DB::commit();
            return back()->with('success', 'Componente #' . $id_componente . ' actualizado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar componente: ' . $e->getMessage());
        }
    }

    public function finalizar(Request $request, $idop, $id)
    {
        try {
            DB::beginTransaction();

            $proceso = OrdenProceso::where('id', $id)->lockForUpdate()->firstOrFail();
            if ($proceso->estado_avance === 'COMPLETADO') {
                throw new \Exception("El proceso ya está COMPLETADO.");
            }

            $count = ComponenteOrdenProduccion::where('id_proceso', $id)->where('estado', 1)->count();
            if ($count == 0) {
                DB::rollBack();
                return back()->with('error', 'Debe registrar al menos un material o actividad para finalizar.');
            }

            // 1. Calcular Costo Total de Materiales Consumidos
            $costo_materiales = DB::table('movimientos_inventario')
                ->join('componentes_orden_produccion_global as c', function($join) use ($id) {
                    $join->on('movimientos_inventario.componente_origen_id', '=', 'c.id_op_componentes')
                         ->where('c.id_proceso', '=', $id)
                         ->where('c.estado', '=', 1);
                })
                ->where('movimientos_inventario.documento_referencia', 'PRODUCCION')
                ->where('movimientos_inventario.tipo_movimiento', 'SALIDA')
                ->where('movimientos_inventario.estado', 1)
                ->sum('movimientos_inventario.total');

            // 2. Calcular Costo Mano de Obra y Máquina
            $costo_hora_hombre = DB::table('parametros_sistema')->where('codigo_parametro', 'COSTO_HORA_HOMBRE')->value('valor') ?? 0;
            $costo_hora_maquina = DB::table('parametros_sistema')->where('codigo_parametro', 'COSTO_HORA_MAQUINA')->value('valor') ?? 0;

            $componentes = DB::table('componentes_orden_produccion_global')
                ->where('id_proceso', $id)
                ->where('estado', 1)
                ->get();

            $costo_mano_obra = 0;
            $costo_maquina = 0;
            
            $horas_hombre_total = 0;
            $horas_maquina_total = 0;

            $min_inicio_maq = null;
            $max_fin_maq = null;

            foreach ($componentes as $comp) {
                if ($comp->fecha_inicio && $comp->hora_inicio && $comp->fecha_fin && $comp->hora_fin) {
                    $inicio = \Carbon\Carbon::parse($comp->fecha_inicio . ' ' . $comp->hora_inicio);
                    $fin = \Carbon\Carbon::parse($comp->fecha_fin . ' ' . $comp->hora_fin);
                    $horas = $inicio->diffInMinutes($fin) / 60;
                    if ($horas > 0) {
                        $horas_hombre_total += $horas;
                        $costo_mano_obra += ($horas * $costo_hora_hombre);
                    }
                }
                
                if ($comp->fecha_inicio_maquina && $comp->hora_inicio_maquina && $comp->fecha_fin_maquina && $comp->hora_fin_maquina) {
                    $inicio = \Carbon\Carbon::parse($comp->fecha_inicio_maquina . ' ' . $comp->hora_inicio_maquina);
                    $fin = \Carbon\Carbon::parse($comp->fecha_fin_maquina . ' ' . $comp->hora_fin_maquina);
                    
                    if (!$min_inicio_maq || $inicio < $min_inicio_maq) $min_inicio_maq = $inicio;
                    if (!$max_fin_maq || $fin > $max_fin_maq) $max_fin_maq = $fin;
                }
            }
            
            if ($min_inicio_maq && $max_fin_maq) {
                $horas_maquina_total = $min_inicio_maq->diffInMinutes($max_fin_maq) / 60;
                if ($horas_maquina_total > 0) {
                    $costo_maquina = $horas_maquina_total * $costo_hora_maquina;
                }
            }

            $costo_total = $costo_materiales + $costo_mano_obra + $costo_maquina;

            if ($costo_mano_obra > 0) {
                DB::table('produccion_costos')->insert([
                    'idop' => $idop,
                    'tipo_costo' => 'MANO_OBRA',
                    'descripcion' => 'Horas Hombre Calculadas',
                    'cantidad' => $horas_hombre_total,
                    'costo_unitario' => $costo_hora_hombre,
                    'costo_total' => $costo_mano_obra,
                    'moneda' => 'PEN',
                    'fecha_costo' => now()->toDateString(),
                    'usuario_registro' => auth()->id() ?? null
                ]);
            }
            
            if ($costo_maquina > 0) {
                DB::table('produccion_costos')->insert([
                    'idop' => $idop,
                    'tipo_costo' => 'EQUIPOS',
                    'descripcion' => 'Horas Máquina Calculadas',
                    'cantidad' => $horas_maquina_total,
                    'costo_unitario' => $costo_hora_maquina,
                    'costo_total' => $costo_maquina,
                    'moneda' => 'PEN',
                    'fecha_costo' => now()->toDateString(),
                    'usuario_registro' => auth()->id() ?? null
                ]);
            }


            // 3. Distribuir Costos y Actualizar Inventario
            $pep_movimientos = DB::table('movimientos_inventario')
                ->whereIn('documento_referencia', ['PRODUCCION_PEP', 'PRODUCCION_PEP_GLOBAL', 'PRODUCCION'])
                ->where(function($q) use ($idop, $id) {
                    $q->where('numero_referencia', "OP-{$idop}-PROC-{$id}")
                      ->orWhere('numero_referencia', "OP-{$idop}-PROC-{$id}-G");
                })
                ->where('tipo_movimiento', 'INGRESO')
                ->where('estado', 1)
                ->get();

            $cantidad_producida = $pep_movimientos->sum('cantidad');

            if ($cantidad_producida > 0) {
                $costo_unitario_real = round($costo_total / $cantidad_producida, 9);
                
                $productos_almacenes_afectados = [];

                foreach ($pep_movimientos as $mov) {
                    $nuevo_total = round($mov->cantidad * $costo_unitario_real, 2);

                    DB::table('movimientos_inventario')
                        ->where('id_movimiento', $mov->id_movimiento)
                        ->update([
                            'costo_unitario' => $costo_unitario_real,
                            'total' => $nuevo_total
                        ]);

                    DB::table('kardex')
                        ->where('codigo_referencia_movimiento', $mov->id_movimiento)
                        ->whereIn('documento', ['RECEPCION_PEP', 'RECEPCION_PEP_GLOBAL', 'PRODUCCION'])
                        ->update([
                            'costo_entrada' => $costo_unitario_real,
                            'total_entrada' => $nuevo_total
                        ]);
                    
                    $clave_recalculo = $mov->codigo_producto . '|' . $mov->codigo_almacen;
                    $productos_almacenes_afectados[$clave_recalculo] = [
                        'producto' => $mov->codigo_producto,
                        'almacen'  => $mov->codigo_almacen
                    ];
                }

                $kardexService = app(\App\Services\KardexService::class);
                foreach ($productos_almacenes_afectados as $afectado) {
                    $kardexService->recalcular($afectado['producto'], $afectado['almacen']);
                }
            }

            OrdenProceso::where('id', $id)->update(['estado_avance' => 'COMPLETADO', 'fecha_fin' => now()]);

            DB::commit();
            return back()->with('success', 'Proceso finalizado y cerrado correctamente. Costos de producción asignados y Kardex actualizado.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function destroyComponente($idop, $id, $id_componente)
    {
        try {
            DB::beginTransaction();

            $componente = ComponenteOrdenProduccion::where('id_op_componentes', $id_componente)
                ->where('id_proceso', $id)
                ->where('estado', 1)
                ->lockForUpdate()
                ->firstOrFail();

            $orden = OrdenProduccion::findOrFail($idop);
            if ($orden->estado === 'COMPLETADO' || $orden->activo == 0) {
                throw new \Exception("No se pueden eliminar componentes en una orden " . ($orden->activo == 0 ? 'ANULADA' : 'COMPLETADA') . ".");
            }

            $proceso = OrdenProceso::findOrFail($id);
            if ($proceso->estado_avance === 'COMPLETADO') {
                throw new \Exception("No se puede eliminar un componente de un proceso COMPLETADO.");
            }

            $usuario_id = Auth::id();

            if ($componente->codigo_tipo_producto !== 'ACT') {
                $movimientos = DB::table('movimientos_inventario')
                    ->where('componente_origen_id', $id_componente)
                    ->where('tipo_movimiento', 'SALIDA')
                    ->where('documento_referencia', 'PRODUCCION')
                    ->where('estado', 1)
                    ->get();

                $numero_referencia_ext = "OP-{$idop}-PROC-{$id}-COMP-{$id_componente}";

                foreach ($movimientos as $mov) {
                    DB::table('inventario')
                        ->where('codigo_producto', $mov->codigo_producto)
                        ->where('lote', $mov->lote)
                        ->where('codigo_almacen', $mov->codigo_almacen)
                        ->update([
                            'stock_actual' => DB::raw("stock_actual + {$mov->cantidad}"),
                            'fecha_ultimo_movimiento' => now()
                        ]);

                    DB::table('inventario')
                        ->where('codigo_producto', $mov->codigo_producto)
                        ->where('lote', $mov->lote)
                        ->where('codigo_almacen', $mov->codigo_almacen)
                        ->where('stock_actual', '>', 0)
                        ->where('estado', 0)
                        ->update([
                            'estado' => 1,
                            'fecha_ultimo_movimiento' => now(),
                            'usuario_ultimo_movimiento' => $usuario_id
                        ]);

                    $stockActual = DB::table('inventario')
                        ->where('codigo_producto', $mov->codigo_producto)
                        ->where('lote', $mov->lote)
                        ->where('codigo_almacen', $mov->codigo_almacen)
                        ->value('stock_actual') ?? $mov->cantidad;

                    $movExtId = DB::table('movimientos_inventario')->insertGetId([
                        'codigo_almacen' => $mov->codigo_almacen,
                        'codigo_producto' => $mov->codigo_producto,
                        'lote' => $mov->lote,
                        'tipo_movimiento' => 'INGRESO',
                        'cantidad' => $mov->cantidad,
                        'costo_unitario' => $mov->costo_unitario,
                        'total' => $mov->cantidad * $mov->costo_unitario,
                        'documento_referencia' => 'EXTORNO_CONS',
                        'numero_referencia' => $numero_referencia_ext,
                        'idop' => $idop,
                        'observaciones' => "Devolución por anulación de componente #{$id_componente}",
                        'usuario_movimiento' => $usuario_id,
                        'fecha_movimiento' => now(),
                        'estado' => 1,
                        'tiene_kardex' => true
                    ]);

                    DB::table('kardex')->insert([
                        'codigo_almacen'       => $mov->codigo_almacen,
                        'codigo_producto'      => $mov->codigo_producto,
                        'fecha_movimiento'     => now(),
                        'tipo_movimiento'      => 'EXTORNO',
                        'documento'            => 'EXTORNO_CONS',
                        'numero_documento'     => $numero_referencia_ext,
                        'cantidad_entrada'     => $mov->cantidad,
                        'cantidad_salida'      => 0,
                        'cantidad_saldo'       => $stockActual,
                        'codigo_referencia_movimiento' => $movExtId,
                        'observaciones'        => "Devolución por anulación de componente OP-{$idop}",
                        'usuario_registro'     => $usuario_id
                    ]);

                    // Marcar el original como anulado
                    DB::table('kardex')
                        ->where('numero_documento', $mov->numero_referencia)
                        ->where('tipo_movimiento', 'SALIDA')
                        ->where('documento', 'PRODUCCION')
                        ->where('codigo_producto', $mov->codigo_producto)
                        ->where('cantidad_salida', $mov->cantidad)
                        ->update(['observaciones' => DB::raw("CONCAT(COALESCE(observaciones, ''), ' [ANULADO]')")]);

                    DB::table('movimientos_inventario')
                        ->where('id_movimiento', $mov->id_movimiento)
                        ->update(['tiene_kardex' => true]);

                    // Reconstruir saldos del Kardex para mantener la consistencia al ocultar
                    app(\App\Services\KardexService::class)->recalcular($mov->codigo_producto, $mov->codigo_almacen);
                }
            }

            if (!empty($componente->codigo_formula_produccion)) {
                $codigo_pep = $this->determinarCodigoPEP($componente->codigo_formula_produccion);
            } elseif ($componente->codigo_tipo_producto === 'PEP') {
                $codigo_pep = $this->determinarPEPdesdeProducto($componente->codigo_producto);
            } elseif ($componente->codigo_tipo_producto !== 'ACT') {
                $op = DB::table('orden_produccion_global')->where('idop', $idop)->first();
                if ($op && !empty($op->descripcion_producto_proceso)) {
                    $producto_pep = DB::table('producto')
                        ->where('descripcion', $op->descripcion_producto_proceso)
                        ->where('codigo_tipo_producto', 'PEP')
                        ->where('estado', 1)
                        ->first();
                    $codigo_pep = $producto_pep ? $producto_pep->codigo : null;
                } else {
                    $codigo_pep = null;
                }
            } else {
                $codigo_pep = null;
            }

            if ($codigo_pep) {
                // Si el componente tiene un lote_produccion_pep vinculado (flujo directo)
                if (!empty($componente->lote_produccion_pep)) {
                    $pepRow = DB::table('produccion_ingresos_proceso')
                        ->where('id_proceso', $id)
                        ->where('lote_produccion', $componente->lote_produccion_pep)
                        ->first();
                    
                    if ($pepRow) {
                        $otrosComponentesLote = DB::table('componentes_orden_produccion_global')
                            ->where('id_proceso', $id)
                            ->where('lote_produccion_pep', $componente->lote_produccion_pep)
                            ->where('id_op_componentes', '!=', $id_componente)
                            ->where('estado', 1)
                            ->get();

                        $cantAReducirPEP = $otrosComponentesLote->isEmpty() ? $pepRow->cantidad : min($componente->cantidad, $pepRow->cantidad);

                        if ($cantAReducirPEP > 0) {
                            $invPep = DB::table('inventario')
                                ->where('codigo_producto', $pepRow->codigo_producto_proceso)
                                ->where('codigo_almacen', $pepRow->codigo_almacen)
                                ->where('lote', $pepRow->lote_produccion)
                                ->first();

                            if ($invPep) {
                                if ($invPep->stock_actual < ($cantAReducirPEP - 0.001)) {
                                    throw new \Exception("No se puede eliminar el componente: El producto resultante ({$pepRow->codigo_producto_proceso}, Lote: {$pepRow->lote_produccion}) ya ha sido transferido o consumido. Stock disponible: {$invPep->stock_actual} {$pepRow->codigo_unidad_medida}, Requerido para reversión: {$cantAReducirPEP} {$pepRow->codigo_unidad_medida}.");
                                }

                                $nuevoStockPep = round(max(0, $invPep->stock_actual - $cantAReducirPEP), 4);
                                DB::table('inventario')
                                    ->where('id_inventario', $invPep->id_inventario)
                                    ->update([
                                        'stock_actual' => $nuevoStockPep,
                                        'estado' => $nuevoStockPep > 0 ? 1 : 0,
                                        'fecha_ultimo_movimiento' => now(),
                                        'usuario_ultimo_movimiento' => $usuario_id
                                    ]);

                                $numero_referencia_ext_pep = "OP-{$idop}-PROC-{$id}-EXT-PEP-{$id_componente}";

                                $movExtPepId = DB::table('movimientos_inventario')->insertGetId([
                                    'codigo_almacen' => $pepRow->codigo_almacen,
                                    'codigo_producto' => $pepRow->codigo_producto_proceso,
                                    'lote' => $pepRow->lote_produccion,
                                    'tipo_movimiento' => 'SALIDA',
                                    'cantidad' => $cantAReducirPEP,
                                    'costo_unitario' => $invPep->costo_promedio,
                                    'total' => round($cantAReducirPEP * $invPep->costo_promedio, 2),
                                    'documento_referencia' => 'EXTORNO_PROD',
                                    'numero_referencia' => $numero_referencia_ext_pep,
                                    'idop' => $idop,
                                    'observaciones' => "Reversión de producción por anulación de componente #{$id_componente} [ANULADO]",
                                    'usuario_movimiento' => $usuario_id,
                                    'fecha_movimiento' => now(),
                                    'estado' => 0,
                                    'tiene_kardex' => true
                                ]);

                                DB::table('kardex')->insert([
                                    'codigo_almacen'       => $pepRow->codigo_almacen,
                                    'codigo_producto'      => $pepRow->codigo_producto_proceso,
                                    'lote'                 => $pepRow->lote_produccion,
                                    'fecha_movimiento'     => now(),
                                    'tipo_movimiento'      => 'EXTORNO',
                                    'documento'            => 'EXTORNO_PROD',
                                    'numero_documento'     => $numero_referencia_ext_pep,
                                    'cantidad_entrada'     => 0,
                                    'cantidad_salida'      => $cantAReducirPEP,
                                    'cantidad_saldo'       => $nuevoStockPep,
                                    'codigo_referencia_movimiento' => $movExtPepId,
                                    'observaciones'        => "Reversión de producción OP-{$idop} [ANULADO]",
                                    'usuario_registro'     => $usuario_id
                                ]);

                                $nuevaCantidadPEP = round(max(0, $pepRow->cantidad - $cantAReducirPEP), 4);
                                if ($nuevaCantidadPEP <= 0.0001 || $otrosComponentesLote->isEmpty()) {
                                    DB::table('kardex')
                                        ->where('codigo_almacen', $pepRow->codigo_almacen)
                                        ->where('codigo_producto', $pepRow->codigo_producto_proceso)
                                        ->where('lote', $pepRow->lote_produccion)
                                        ->where('tipo_movimiento', 'INGRESO')
                                        ->where('documento', 'RECEPCION_PEP')
                                        ->update([
                                            'observaciones' => DB::raw("CONCAT(COALESCE(observaciones, ''), ' [ANULADO]')"),
                                            'cantidad_saldo' => 0,
                                            'total_saldo' => 0
                                        ]);

                                    DB::table('movimientos_inventario')
                                        ->where('codigo_almacen', $pepRow->codigo_almacen)
                                        ->where('codigo_producto', $pepRow->codigo_producto_proceso)
                                        ->where('lote', $pepRow->lote_produccion)
                                        ->where('tipo_movimiento', 'INGRESO')
                                        ->where('documento_referencia', 'PRODUCCION_PEP')
                                        ->update([
                                            'estado' => 0,
                                            'observaciones' => DB::raw("CONCAT(COALESCE(observaciones, ''), ' [ANULADO]')")
                                        ]);
                                }

                                app(\App\Services\KardexService::class)->recalcular($pepRow->codigo_producto_proceso, $pepRow->codigo_almacen);
                            }

                            $nuevaCantidadPEP = round(max(0, $pepRow->cantidad - $cantAReducirPEP), 4);
                            if ($nuevaCantidadPEP <= 0.0001 || $otrosComponentesLote->isEmpty()) {
                                DB::table('produccion_ingresos_proceso')->where('id_ingreso', $pepRow->id_ingreso)->delete();
                            } else {
                                DB::table('produccion_ingresos_proceso')->where('id_ingreso', $pepRow->id_ingreso)->update(['cantidad' => $nuevaCantidadPEP]);
                            }
                        }
                    }
                } else {
                    // Flujo antiguo por si hay datos heredados
                    // RECALCULAR TOTAL ACTIVO
                    $totalActivoFormula = DB::table('componentes_orden_produccion_global')
                        ->where('id_proceso', $id)
                        ->where(function($q) use ($codigo_pep, $componente) {
                            if (!empty($componente->codigo_formula_produccion)) {
                                $q->where('codigo_formula_produccion', $componente->codigo_formula_produccion);
                            } else {
                                $q->where('codigo_tipo_producto', 'PEP')
                                  ->where('codigo_producto', $componente->codigo_producto);
                            }
                        })
                        ->where('estado', 1)
                        ->where('id_op_componentes', '!=', $id_componente) // Excluir el que estamos borrando
                        ->sum('cantidad') ?? 0;

                    // TOTAL EN PEP APROBADOS
                    $totalAprobado = DB::table('produccion_ingresos_proceso')
                        ->where('id_proceso', $id)
                        ->where('codigo_producto_proceso', $codigo_pep)
                        ->where('estado', 'APROBADO')
                        ->sum('cantidad') ?? 0;

                    // LO QUE DEBERIA QUEDAR PENDIENTE
                    $deberiaEstarPendiente = round(max(0, $totalActivoFormula - $totalAprobado), 4);

                    // ACTUALIZAR ROWS PENDIENTES
                    $pendientes = DB::table('produccion_ingresos_proceso')
                        ->where('id_proceso', $id)
                        ->where('codigo_producto_proceso', $codigo_pep)
                        ->where('estado', 'PENDIENTE')
                        ->orderBy('id_ingreso', 'desc')
                        ->get();

                    $restante = $deberiaEstarPendiente;
                    foreach ($pendientes as $p) {
                        if ($restante >= $p->cantidad) {
                            $restante -= $p->cantidad;
                        } elseif ($restante > 0) {
                            DB::table('produccion_ingresos_proceso')->where('id_ingreso', $p->id_ingreso)->update(['cantidad' => $restante]);
                            $restante = 0;
                        } else {
                            DB::table('produccion_ingresos_proceso')->where('id_ingreso', $p->id_ingreso)->delete();
                        }
                    }
                }
            }

            $componente->update(['estado' => 0]);

            DB::commit();
            return back()->with('success', 'Registro desactivado correctamente. Stock restaurado, extorno registrado en kardex y PEP ajustado.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al desactivar: ' . $e->getMessage());
        }
    }

    public function destroyCarga($idop, $id, Request $request)
    {
        try {
            DB::beginTransaction();

            $orden = OrdenProduccion::findOrFail($idop);
            if ($orden->estado === 'COMPLETADO' || $orden->activo == 0) {
                throw new \Exception("No se pueden eliminar cargas en una orden " . ($orden->activo == 0 ? 'ANULADA' : 'COMPLETADA') . ".");
            }

            $proceso = OrdenProceso::findOrFail($id);
            if ($proceso->estado_avance === 'COMPLETADO') {
                throw new \Exception("No se puede eliminar una carga de un proceso COMPLETADO.");
            }

            $ids = $request->input('ids', []);
            if (is_string($ids)) {
                $ids = json_decode($ids, true) ?: explode(',', $ids);
            }

            if (empty($ids)) {
                throw new \Exception("No se especificaron componentes para eliminar.");
            }

            $componentes = ComponenteOrdenProduccion::whereIn('id_op_componentes', $ids)
                ->where('id_proceso', $id)
                ->where('estado', 1)
                ->lockForUpdate()
                ->get();

            if ($componentes->isEmpty()) {
                throw new \Exception("No se encontraron registros activos para la carga seleccionada.");
            }

            $usuario_id = Auth::id();
            $lotesPepProcesados = [];

            foreach ($componentes as $componente) {
                $id_componente = $componente->id_op_componentes;

                // 1. Restaurar stock de insumos/componentes consumidos
                if ($componente->codigo_tipo_producto !== 'ACT') {
                    $movimientos = DB::table('movimientos_inventario')
                        ->where('componente_origen_id', $id_componente)
                        ->where('tipo_movimiento', 'SALIDA')
                        ->where('documento_referencia', 'PRODUCCION')
                        ->where('estado', 1)
                        ->get();

                    $numero_referencia_ext = "OP-{$idop}-PROC-{$id}-COMP-{$id_componente}";

                    foreach ($movimientos as $mov) {
                        DB::table('inventario')
                            ->where('codigo_producto', $mov->codigo_producto)
                            ->where('lote', $mov->lote)
                            ->where('codigo_almacen', $mov->codigo_almacen)
                            ->update([
                                'stock_actual' => DB::raw("stock_actual + {$mov->cantidad}"),
                                'fecha_ultimo_movimiento' => now()
                            ]);

                        DB::table('inventario')
                            ->where('codigo_producto', $mov->codigo_producto)
                            ->where('lote', $mov->lote)
                            ->where('codigo_almacen', $mov->codigo_almacen)
                            ->where('stock_actual', '>', 0)
                            ->where('estado', 0)
                            ->update([
                                'estado' => 1,
                                'fecha_ultimo_movimiento' => now(),
                                'usuario_ultimo_movimiento' => $usuario_id
                            ]);

                        $stockActual = DB::table('inventario')
                            ->where('codigo_producto', $mov->codigo_producto)
                            ->where('lote', $mov->lote)
                            ->where('codigo_almacen', $mov->codigo_almacen)
                            ->value('stock_actual') ?? $mov->cantidad;

                        $movExtId = DB::table('movimientos_inventario')->insertGetId([
                            'codigo_almacen' => $mov->codigo_almacen,
                            'codigo_producto' => $mov->codigo_producto,
                            'lote' => $mov->lote,
                            'tipo_movimiento' => 'INGRESO',
                            'cantidad' => $mov->cantidad,
                            'costo_unitario' => $mov->costo_unitario,
                            'total' => $mov->cantidad * $mov->costo_unitario,
                            'documento_referencia' => 'EXTORNO_CONS',
                            'numero_referencia' => $numero_referencia_ext,
                            'idop' => $idop,
                            'observaciones' => "Devolución por anulación de carga OP-{$idop} (Comp #{$id_componente})",
                            'usuario_movimiento' => $usuario_id,
                            'fecha_movimiento' => now(),
                            'estado' => 1,
                            'tiene_kardex' => true
                        ]);

                        DB::table('kardex')->insert([
                            'codigo_almacen'       => $mov->codigo_almacen,
                            'codigo_producto'      => $mov->codigo_producto,
                            'lote'                 => $mov->lote,
                            'fecha_movimiento'     => now(),
                            'tipo_movimiento'      => 'EXTORNO',
                            'documento'            => 'EXTORNO_CONS',
                            'numero_documento'     => $numero_referencia_ext,
                            'cantidad_entrada'     => $mov->cantidad,
                            'cantidad_salida'      => 0,
                            'cantidad_saldo'       => $stockActual,
                            'codigo_referencia_movimiento' => $movExtId,
                            'observaciones'        => "Devolución por anulación de carga OP-{$idop}",
                            'usuario_registro'     => $usuario_id
                        ]);

                        DB::table('kardex')
                            ->where('numero_documento', $mov->numero_referencia)
                            ->where('tipo_movimiento', 'SALIDA')
                            ->where('documento', 'PRODUCCION')
                            ->where('codigo_producto', $mov->codigo_producto)
                            ->where('cantidad_salida', $mov->cantidad)
                            ->update(['observaciones' => DB::raw("CONCAT(COALESCE(observaciones, ''), ' [ANULADO]')")]);

                        DB::table('movimientos_inventario')
                            ->where('id_movimiento', $mov->id_movimiento)
                            ->update(['tiene_kardex' => true]);

                        app(\App\Services\KardexService::class)->recalcular($mov->codigo_producto, $mov->codigo_almacen);
                    }
                }

                // 2. Revertir PEP si tiene lote vinculado
                if (!empty($componente->lote_produccion_pep) && !in_array($componente->lote_produccion_pep, $lotesPepProcesados)) {
                    $lotesPepProcesados[] = $componente->lote_produccion_pep;

                    $pepRow = DB::table('produccion_ingresos_proceso')
                        ->where('id_proceso', $id)
                        ->where('lote_produccion', $componente->lote_produccion_pep)
                        ->first();

                    if ($pepRow) {
                        $cantAReducirPEP = $pepRow->cantidad;

                        $invPep = DB::table('inventario')
                            ->where('codigo_producto', $pepRow->codigo_producto_proceso)
                            ->where('codigo_almacen', $pepRow->codigo_almacen)
                            ->where('lote', $pepRow->lote_produccion)
                            ->first();

                        if ($invPep) {
                            if ($invPep->stock_actual < ($cantAReducirPEP - 0.001)) {
                                throw new \Exception("No se puede anular la carga: El producto resultante ({$pepRow->codigo_producto_proceso}, Lote: {$pepRow->lote_produccion}) ya fue transferido o consumido. Stock disponible: {$invPep->stock_actual} {$pepRow->codigo_unidad_medida}, Requerido para reversión: {$cantAReducirPEP} {$pepRow->codigo_unidad_medida}.");
                            }

                            $nuevoStockPep = round(max(0, $invPep->stock_actual - $cantAReducirPEP), 4);
                            DB::table('inventario')
                                ->where('id_inventario', $invPep->id_inventario)
                                ->update([
                                    'stock_actual' => $nuevoStockPep,
                                    'estado' => $nuevoStockPep > 0 ? 1 : 0,
                                    'fecha_ultimo_movimiento' => now(),
                                    'usuario_ultimo_movimiento' => $usuario_id
                                ]);

                            $numero_referencia_ext_pep = "OP-{$idop}-PROC-{$id}-EXT-CARGA-" . substr(md5($componente->lote_produccion_pep), 0, 6);

                            $movExtPepId = DB::table('movimientos_inventario')->insertGetId([
                                'codigo_almacen' => $pepRow->codigo_almacen,
                                'codigo_producto' => $pepRow->codigo_producto_proceso,
                                'lote' => $pepRow->lote_produccion,
                                'tipo_movimiento' => 'SALIDA',
                                'cantidad' => $cantAReducirPEP,
                                'costo_unitario' => $invPep->costo_promedio,
                                'total' => round($cantAReducirPEP * $invPep->costo_promedio, 2),
                                'documento_referencia' => 'EXTORNO_PROD',
                                'numero_referencia' => $numero_referencia_ext_pep,
                                'idop' => $idop,
                                'observaciones' => "Reversión de producción por anulación de carga OP-{$idop} [ANULADO]",
                                'usuario_movimiento' => $usuario_id,
                                'fecha_movimiento' => now(),
                                'estado' => 0,
                                'tiene_kardex' => true
                            ]);

                            DB::table('kardex')->insert([
                                'codigo_almacen'       => $pepRow->codigo_almacen,
                                'codigo_producto'      => $pepRow->codigo_producto_proceso,
                                'lote'                 => $pepRow->lote_produccion,
                                'fecha_movimiento'     => now(),
                                'tipo_movimiento'      => 'EXTORNO',
                                'documento'            => 'EXTORNO_PROD',
                                'numero_documento'     => $numero_referencia_ext_pep,
                                'cantidad_entrada'     => 0,
                                'cantidad_salida'      => $cantAReducirPEP,
                                'cantidad_saldo'       => $nuevoStockPep,
                                'codigo_referencia_movimiento' => $movExtPepId,
                                'observaciones'        => "Reversión de ingreso PEP OP-{$idop} [ANULADO]",
                                'usuario_registro'     => $usuario_id
                            ]);

                            // Marcar el registro original de RECEPCION_PEP como [ANULADO]
                            DB::table('kardex')
                                ->where('codigo_almacen', $pepRow->codigo_almacen)
                                ->where('codigo_producto', $pepRow->codigo_producto_proceso)
                                ->where('lote', $pepRow->lote_produccion)
                                ->where('tipo_movimiento', 'INGRESO')
                                ->where('documento', 'RECEPCION_PEP')
                                ->update([
                                    'observaciones' => DB::raw("CONCAT(COALESCE(observaciones, ''), ' [ANULADO]')"),
                                    'cantidad_saldo' => 0,
                                    'total_saldo' => 0
                                ]);

                            DB::table('movimientos_inventario')
                                ->where('codigo_almacen', $pepRow->codigo_almacen)
                                ->where('codigo_producto', $pepRow->codigo_producto_proceso)
                                ->where('lote', $pepRow->lote_produccion)
                                ->where('tipo_movimiento', 'INGRESO')
                                ->where('documento_referencia', 'PRODUCCION_PEP')
                                ->update([
                                    'estado' => 0,
                                    'observaciones' => DB::raw("CONCAT(COALESCE(observaciones, ''), ' [ANULADO]')")
                                ]);

                            app(\App\Services\KardexService::class)->recalcular($pepRow->codigo_producto_proceso, $pepRow->codigo_almacen);
                        }

                        DB::table('produccion_ingresos_proceso')->where('id_ingreso', $pepRow->id_ingreso)->delete();
                    }
                }

                $componente->update(['estado' => 0]);
            }

            DB::commit();
            return back()->with('success', 'Carga eliminada correctamente. Materiales devueltos a inventario y producción revertida.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al eliminar carga: ' . $e->getMessage());
        }
    }

    private function determinarCodigoPEP($codigo_formula) {
        if (empty($codigo_formula)) return null;
        // Primero verificar si la fórmula tiene asignado explícitamente un producto resultante
        $res = DB::table('formula_produccion')->where('codigo', $codigo_formula)->value('codigo_producto_resultante');
        if ($res) return $res;
        // Si no, asumir que el código de la fórmula es el código del producto resultante (puede ser PEP, REC, PDT)
        $res = DB::table('producto')
            ->where('codigo', $codigo_formula)
            ->whereIn('codigo_tipo_producto', ['PEP', 'REC', 'PDT'])
            ->where('estado', 1)
            ->value('codigo');
        return $res ?: null;
    }

    private function determinarPEPdesdeProducto($codigo_producto)
    {
        // Si el producto existe como PEP, retornarlo directamente
        $producto = DB::table('producto')
            ->where('codigo', $codigo_producto)
            ->where('codigo_tipo_producto', 'PEP')
            ->where('estado', 1)
            ->first();
        if ($producto) return $producto->codigo;

        // Fallback original para MZ07-
        if (str_starts_with($codigo_producto, 'MZ07-')) {
            $codigo_inyectado = str_replace('MZ07-', 'CA07-', $codigo_producto);
            $existe = DB::table('producto')
                ->where('codigo', $codigo_inyectado)
                ->where('estado', 1)
                ->exists();
            if ($existe) return $codigo_inyectado;
        }
        return null;
    }

    private function determinarCodigoRecuperado($codigo_pep, $codigo_formula = null, $tipo_operacion = null)
    {
        if (empty($codigo_pep)) return null;

        // 1. Merma Empaque (Troquelado)
        if ($tipo_operacion === 'merma_empaque') {
            return 'MER-EMP-' . $codigo_pep;
        }

        // 2. Merma Clip (Troquelado)
        if ($tipo_operacion === 'merma_clip') {
            return 'REC-' . $codigo_pep;
        }

        // 3. Recuperado para Moler o Limpieza (Material en bruto para molienda) -> Siempre debe ir al producto REC-
        if ($tipo_operacion === 'recuperado_molido' || $tipo_operacion === 'limpieza') {
            return 'REC-' . $codigo_pep;
        }

        // 2. Molido de Máquina (Material ya triturado al pie de máquina) -> Debe ir al producto MO...
        if ($tipo_operacion === 'recuperado_maquina') {
            if (str_starts_with($codigo_pep, 'CA')) {
                $codigoMo = 'MO' . substr($codigo_pep, 2);
                if (DB::table('producto')->where('codigo', $codigoMo)->where('estado', 1)->exists()) {
                    return $codigoMo;
                }
            }

            if ($codigo_formula) {
                $recFormula = DB::table('formula_produccion')->where('codigo', $codigo_formula)->value('codigo_material_reciclado');
                if ($recFormula && DB::table('producto')->where('codigo', $recFormula)->where('estado', 1)->exists()) {
                    return $recFormula;
                }
            }

            if (str_starts_with($codigo_pep, 'CA')) {
                return 'MO' . substr($codigo_pep, 2);
            }
        }

        // 3. Fallback general:
        if (str_starts_with($codigo_pep, 'CA')) {
            $codigoMo = 'MO' . substr($codigo_pep, 2);
            if (DB::table('producto')->where('codigo', $codigoMo)->where('estado', 1)->exists()) {
                return $codigoMo;
            }
        }

        return 'REC-' . $codigo_pep;
    }

    private function generarCodigoPEP($codigo, $color, $proceso_id) {
        $base = $codigo . '-' . ($color ?: 'SC') . '-P' . $proceso_id . '-' . date('YmdHis');
        return substr(preg_replace('/[^A-Za-z0-9\-]/', '', $base), 0, 45);
    }

    public function storeEjecucionAgrupada(Request $request, $idop, $id)
    {
        try {
            DB::beginTransaction();

            $usuario_id = Auth::id();
            $batch_id = mt_rand(1000, 9999);

            $tipo_operacion = $request->input('tipo_operacion', 'inyectado'); 
            $codigo_formula = $request->input('codigo_formula');
            $cantidad_total = floatval($request->input('cantidad_total'));
            $codigo_centro_trabajo = $request->input('codigo_centro_trabajo');
            $codigo_molde = $request->input('codigo_molde');
            $codigo_trabajador = $request->input('codigo_trabajador');
            $fecha = $request->input('fecha');
            $hora_inicio = $request->input('hora_inicio');
            $hora_fin = $request->input('hora_fin');
            $codigo_almacen_consumo = $request->input('codigo_almacen_consumo');

            $orden = OrdenProduccion::findOrFail($idop);
            if ($orden->estado === 'COMPLETADO' || $orden->activo == 0) {
                throw new \Exception("No se pueden registrar componentes en una orden " . ($orden->activo == 0 ? 'ANULADA' : 'COMPLETADA') . ".");
            }

            $proceso = OrdenProceso::findOrFail($id);
            if ($proceso->estado_avance === 'COMPLETADO') {
                throw new \Exception("No se pueden registrar componentes en un proceso COMPLETADO.");
            }

            // Actualizar campos de cabecera en orden_proceso si vienen en el request
            $updateProcesoData = [];
            $valIni = null;
            $valFin = null;

            if ($request->has('inyectada_inicial')) {
                $rawIni = $request->input('inyectada_inicial');
                $valIni = ($rawIni !== null && $rawIni !== '') ? intval($rawIni) : null;
                $updateProcesoData['inyectada_inicial'] = $valIni;
            }
            if ($request->has('inyectada_final')) {
                $rawFin = $request->input('inyectada_final');
                $valFin = ($rawFin !== null && $rawFin !== '') ? intval($rawFin) : null;
                $updateProcesoData['inyectada_final'] = $valFin;
            }

            // Validación: inyectada_final no puede ser menor a inyectada_inicial
            $checkIni = $valIni ?? $proceso->inyectada_inicial;
            if ($checkIni !== null && $valFin !== null && $valFin < $checkIni) {
                throw new \Exception("La inyectada final ({$valFin}) no puede ser menor a la inyectada inicial ({$checkIni}).");
            }

            if ($request->has('tiempo_ciclo')) {
                $valCiclo = $request->input('tiempo_ciclo');
                $updateProcesoData['tiempo_ciclo'] = ($valCiclo !== null && $valCiclo !== '') ? intval($valCiclo) : null;
            }
            if (!empty($updateProcesoData)) {
                $proceso->update($updateProcesoData);
            }

            $componentes_a_insertar = [];
            
            $colores_seleccionados = $request->input('colores_seleccionados', []);
            $es_merma_directa = in_array($tipo_operacion, ['recuperado_clip', 'merma_cascara', 'merma_carton']) || ($tipo_operacion === 'merma_empaque' && $proceso->codigo_proceso != '17') || ($tipo_operacion === 'merma_clip' && $proceso->codigo_proceso != '17');

            if ($es_merma_directa) {
                // Registro directo de inventario (sin fórmula)
                $producto = DB::table('producto')->where('codigo', $codigo_formula)->first();
                if (!$producto) throw new \Exception("Producto no encontrado en la base de datos.");
                
                $componentes_a_insertar[] = [
                    'codigo_producto' => $producto->codigo,
                    'cantidad' => $cantidad_total,
                    'codigo_unidad_medida' => $producto->codigo_unidad_medida,
                    'codigo_molde' => null,
                    'codigo_tipo_producto' => $producto->codigo_tipo_producto
                ];
                
                $codigo_formula = null; // Important: Clear so it doesn't fail foreign key constraint on formula_produccion
                
                $extraCantReciclado = 0;
            } elseif ($tipo_operacion === 'encartonado') {
                if (!is_array($colores_seleccionados) || count($colores_seleccionados) !== 4) {
                    throw new \Exception("Debe seleccionar exactamente 4 colores para el proceso de encartonado.");
                }

                // Normalizar formato de colores_seleccionados: puede ser array de objetos [{idop, codigo_color}] o array de strings ['B004', ...]
                $items_colores = [];
                foreach ($colores_seleccionados as $item) {
                    if (is_array($item)) {
                        $items_colores[] = [
                            'idop' => intval($item['idop'] ?? 0),
                            'codigo_color' => $item['codigo_color'] ?? ''
                        ];
                    } else {
                        $items_colores[] = [
                            'idop' => intval($request->input('idop_origen', 0)),
                            'codigo_color' => strval($item)
                        ];
                    }
                }

                $request_formula = new Request();
                $request_formula->merge([
                    'codigo_formula' => $codigo_formula,
                    'codigo_molde' => $codigo_molde,
                    'codigo_almacen' => $codigo_almacen_consumo
                ]);
                $response = $this->getFormulaComponents($request_formula);
                $formula_data = json_decode($response->getContent(), true);

                if (!isset($formula_data['success']) || !$formula_data['success']) {
                    throw new \Exception("No se pudo obtener la fórmula de encartonado: " . ($formula_data['message'] ?? ''));
                }

                $componentes_formula = $formula_data['componentes'];
                $compGancho = null;
                $compCarton = null;

                foreach ($componentes_formula as $comp) {
                    $codigoProd = $comp['codigo_producto'];
                    $tipoProd = $comp['codigo_tipo_producto'] ?? '';
                    if ($tipoProd === 'PEP' || str_starts_with($codigoProd, 'EN') || str_starts_with($codigoProd, 'CA')) {
                        $compGancho = $comp;
                    } elseif ($tipoProd === 'AUX' || str_starts_with($codigoProd, '01.20.004') || str_contains(strtoupper($comp['descripcion_producto'] ?? ''), 'CARTON')) {
                        $compCarton = $comp;
                    }
                }

                if (!$compGancho) {
                    $compGancho = $componentes_formula[0] ?? null;
                }
                if (!$compCarton && count($componentes_formula) > 1) {
                    $compCarton = $componentes_formula[1] ?? null;
                }

                $pesoGanchoNominal = floatval($compGancho['cantidad_nominal'] ?? 139.44);
                $umGancho = strtoupper($compGancho['codigo_unidad_medida'] ?? 'GR');
                $pesoGanchoNominalKG = ($umGancho === 'GR') ? ($pesoGanchoNominal / 1000) : $pesoGanchoNominal;

                $pesoCartonNominal = floatval($compCarton['cantidad_nominal'] ?? 14.20);
                $umCarton = strtoupper($compCarton['codigo_unidad_medida'] ?? 'GR');
                $pesoCartonNominalKG = ($umCarton === 'GR') ? ($pesoCartonNominal / 1000) : $pesoCartonNominal;

                // En encartonado, $cantidad_total es la cantidad de SETs a producir (1 SET = 24 unidades = 6 unidades por cada uno de los 4 colores)
                $pesoPorColor = round($cantidad_total * $pesoGanchoNominalKG, 4);
                $pesoTotalGanchosKG = round($pesoPorColor * 4, 4);
                $pesoTotalCartonKG = round($cantidad_total * $pesoCartonNominalKG, 4);

                // Suffix del modelo de gancho (.A para paleta, .C para chupón, .B para animalito)
                $suffix = '.A';
                if (str_contains($codigo_formula, 'CHUPON') || (isset($compGancho['codigo_producto']) && str_ends_with($compGancho['codigo_producto'], '.C'))) {
                    $suffix = '.C';
                } elseif (str_contains($codigo_formula, 'ANIMAL') || (isset($compGancho['codigo_producto']) && str_ends_with($compGancho['codigo_producto'], '.B'))) {
                    $suffix = '.B';
                }

                foreach ($items_colores as $colItem) {
                    $colorCod = $colItem['codigo_color'];
                    $itemOpId = $colItem['idop'];

                    $codGanchoColor = "EN07-{$colorCod}{$suffix}";
                    $existeProd = DB::table('producto')->where('codigo', $codGanchoColor)->exists();
                    if (!$existeProd) {
                        $fallbackProd = DB::table('producto')->where('codigo', 'LIKE', "EN07-{$colorCod}%")->value('codigo');
                        if ($fallbackProd) {
                            $codGanchoColor = $fallbackProd;
                        }
                    }

                    // Validación de saldo de la OP seleccionada
                    if ($itemOpId > 0) {
                        $lotes_op = DB::table('produccion_ingresos_proceso')
                            ->where('idop', $itemOpId)
                            ->pluck('lote_produccion')
                            ->toArray();

                        if (!empty($lotes_op)) {
                            $stockDispColorOP = DB::table('inventario as i')
                                ->where('i.codigo_almacen', $codigo_almacen_consumo)
                                ->where('i.codigo_producto', 'LIKE', "EN07-{$colorCod}%")
                                ->where('i.stock_actual', '>', 0)
                                ->where(function($q) { $q->where('i.estado', 1)->orWhereNull('i.estado'); })
                                ->whereIn('i.lote', $lotes_op)
                                ->sum('i.stock_actual');

                            if ($stockDispColorOP < ($pesoPorColor - 0.001)) {
                                $descColor = DB::table('color')->where('codigo', $colorCod)->value('descripcion') ?? $colorCod;
                                $opCode = DB::table('orden_produccion_global')->where('idop', $itemOpId)->value('codigo_op') ?? "OP #{$itemOpId}";
                                $maxSetsPossible = $pesoGanchoNominalKG > 0 ? floor($stockDispColorOP / $pesoGanchoNominalKG) : 0;
                                throw new \Exception("Stock insuficiente para el color {$descColor} en la orden {$opCode}. Requerido: " . round($pesoPorColor, 2) . " KG (para {$cantidad_total} SETs), Disponible: " . round($stockDispColorOP, 2) . " KG (Máx: {$maxSetsPossible} SETs).");
                            }
                        }
                    }

                    $componentes_a_insertar[] = [
                        'codigo_producto' => $codGanchoColor,
                        'cantidad' => $pesoPorColor,
                        'codigo_unidad_medida' => 'KG',
                        'codigo_molde' => $compGancho['codigo_molde'] ?? $codigo_molde,
                        'codigo_tipo_producto' => 'PEP',
                        'idop_origen' => $itemOpId
                    ];
                }

                if ($compCarton) {
                    $componentes_a_insertar[] = [
                        'codigo_producto' => $compCarton['codigo_producto'],
                        'cantidad' => round($pesoTotalCartonKG, 4),
                        'codigo_unidad_medida' => 'KG',
                        'codigo_molde' => null,
                        'codigo_tipo_producto' => 'AUX'
                    ];
                }
            } elseif ($tipo_operacion === 'embolsado') {
                $codigo_bolsa = $request->input('codigo_bolsa');
                
                $request_formula = new Request();
                $request_formula->merge([
                    'codigo_formula' => $codigo_formula,
                    'codigo_molde' => $codigo_molde,
                    'codigo_almacen' => $codigo_almacen_consumo
                ]);
                $response = $this->getFormulaComponents($request_formula);
                $formula_data = json_decode($response->getContent(), true);

                if (isset($formula_data['success']) && $formula_data['success'] && !empty($formula_data['componentes'])) {
                    foreach ($formula_data['componentes'] as $comp) {
                        $cantNominal = floatval($comp['cantidad_nominal']);
                        $cantTotalComp = $cantidad_total * $cantNominal;
                        $componentes_a_insertar[] = [
                            'codigo_producto' => $comp['codigo_producto'],
                            'cantidad' => round($cantTotalComp, 4),
                            'codigo_unidad_medida' => $comp['codigo_unidad_medida'],
                            'codigo_molde' => $comp['codigo_molde'] ?? null,
                            'codigo_tipo_producto' => $comp['codigo_tipo_producto']
                        ];
                    }
                } else {
                    $productoBase = DB::table('producto')->where('codigo', $codigo_formula)->first();
                    if (!$productoBase) {
                        throw new \Exception("Producto a embolsar [{$codigo_formula}] no encontrado.");
                    }

                    $cantBasePorBolsa = floatval($request->input('cant_base_por_bolsa', 1));
                    $cantTotalBase = $cantidad_total * $cantBasePorBolsa;

                    $componentes_a_insertar[] = [
                        'codigo_producto' => $productoBase->codigo,
                        'cantidad' => round($cantTotalBase, 4),
                        'codigo_unidad_medida' => $productoBase->codigo_unidad_medida ?? 'SET',
                        'codigo_molde' => null,
                        'codigo_tipo_producto' => $productoBase->codigo_tipo_producto ?? 'PEP'
                    ];

                    if ($codigo_bolsa) {
                        $bolsaProd = DB::table('producto')->where('codigo', $codigo_bolsa)->first();
                        if ($bolsaProd) {
                            $componentes_a_insertar[] = [
                                'codigo_producto' => $bolsaProd->codigo,
                                'cantidad' => $cantidad_total,
                                'codigo_unidad_medida' => $bolsaProd->codigo_unidad_medida ?? 'UNI',
                                'codigo_molde' => null,
                                'codigo_tipo_producto' => $bolsaProd->codigo_tipo_producto ?? 'EMB'
                            ];
                        }
                    }
                }
            } else {
                // 1. Obtener los componentes de la fórmula
                $request_formula = new Request();
                $request_formula->merge([
                    'codigo_formula' => $codigo_formula,
                    'codigo_molde' => $codigo_molde,
                    'codigo_almacen' => $codigo_almacen_consumo
                ]);
                $response = $this->getFormulaComponents($request_formula);
                $formula_data = json_decode($response->getContent(), true);

                if (!isset($formula_data['success']) || !$formula_data['success']) {
                    throw new \Exception("No se pudo obtener la fórmula: " . ($formula_data['message'] ?? ''));
                }

                $componentes_formula = $formula_data['componentes'];
                
                // Si es Molido o Ensamblado o Limpieza / Purga, necesitamos el peso total para sacar proporción (KG)
                $pesoTotalFormula = 0;
                $es_ensamblado_molido = in_array($tipo_operacion, ['recuperado_molido', 'recuperado_maquina', 'ensamblado']) || ($proceso->codigo_proceso == '10'); // 10 = ensamblado
                $es_limpieza = in_array($tipo_operacion, ['limpieza', 'purga']);
                
                // Primer pase: Filtrar y calcular el peso total de los componentes que quedan
                $componentes_a_procesar = [];
                foreach ($componentes_formula as $comp) {
                    if ($es_limpieza) {
                        $desc = strtoupper($comp['descripcion_producto'] ?? '');
                        $esPigmento = str_contains($desc, 'COLOR') || str_contains($desc, 'MASTERBATCH') || str_contains($desc, 'PIGMENTO');
                        if ($esPigmento) {
                            continue; // Ignorar el colorante
                        }
                    }
                    $componentes_a_procesar[] = $comp;
                    if ($es_ensamblado_molido || $es_limpieza) {
                        $pesoTotalFormula += floatval($comp['cantidad_nominal']);
                    }
                }

                $usar_reciclado = $request->input('usar_reciclado', 0);
                $cantidad_reciclado = floatval($request->input('cantidad_reciclado', 0));
                $extraCantReciclado = ($usar_reciclado == 1) ? $cantidad_reciclado : 0;

                if ($extraCantReciclado > 0) {
                    if (empty($formula_data['codigo_material_reciclado'])) {
                        throw new \Exception("La fórmula seleccionada no tiene configurado un Material Reciclado en Tablas Maestras.");
                    }
                }

                $nueva_cantidad_total = max(0, $cantidad_total - $extraCantReciclado);

                // Segundo pase: Preparar los componentes a insertar con la distribucion correcta
                $pesoVirgin = 0;
                foreach ($componentes_a_procesar as $comp) {
                    $desc = strtoupper($comp['descripcion_producto'] ?? '');
                    $esPigmento = str_contains($desc, 'COLOR') || str_contains($desc, 'MASTERBATCH') || str_contains($desc, 'PIGMENTO') || $comp['codigo_tipo_producto'] === 'PIG';
                    if (!$esPigmento) {
                        $pesoVirgin += floatval($comp['cantidad_nominal']);
                    }
                }

                foreach ($componentes_a_procesar as $comp) {
                    $cant = floatval($comp['cantidad_nominal']);
                    $um = $comp['codigo_unidad_medida'];
                    $desc = strtoupper($comp['descripcion_producto'] ?? '');
                    $esPigmento = str_contains($desc, 'COLOR') || str_contains($desc, 'MASTERBATCH') || str_contains($desc, 'PIGMENTO') || $comp['codigo_tipo_producto'] === 'PIG';

                    if ($es_ensamblado_molido || $es_limpieza) {
                        if ($pesoTotalFormula > 0) {
                            $cant = $nueva_cantidad_total * ($cant / $pesoTotalFormula);
                        } else {
                            $cant = 0;
                        }
                        $um = 'KG';
                    } else if ($tipo_operacion === 'mezclado') {
                        if ($nueva_cantidad_total <= 1) {
                            if ($esPigmento) {
                                $cant = $nueva_cantidad_total; // 100% de la diferencia es pigmento
                            } else {
                                $cant = 0; // 0 resina
                            }
                        } else {
                            $cant = $nueva_cantidad_total * $cant;
                        }
                        if ($um === 'GR') {
                            $cant = $cant / 1000;
                            $um = 'KG';
                        }
                    } else {
                        // Es Inyectado
                        $cant = $nueva_cantidad_total * $cant;
                        if ($um === 'GR') {
                            $cant = $cant / 1000;
                            $um = 'KG';
                        }
                    }

                    if ($cant > 0) {
                        $componentes_a_insertar[] = [
                            'codigo_producto' => $comp['codigo_producto'],
                            'cantidad' => $cant,
                            'codigo_unidad_medida' => $um,
                            'codigo_molde' => $comp['codigo_molde'] ?? $codigo_molde,
                            'codigo_tipo_producto' => $comp['codigo_tipo_producto']
                        ];
                    }
                }

                // Si la cantidad total es 0 (ej. Limpieza, Purga, Ciclado en vacío), insertar registro representativo con 0 KG
                if ($cantidad_total == 0 && empty($componentes_a_insertar)) {
                    $prodCodigo = $codigo_formula;
                    $prodTipo = 'MTP';
                    $prodUM = 'KG';
                    if (!empty($componentes_formula) && isset($componentes_formula[0])) {
                        $prodCodigo = $componentes_formula[0]['codigo_producto'];
                        $prodTipo = $componentes_formula[0]['codigo_tipo_producto'] ?? 'MTP';
                        $prodUM = $componentes_formula[0]['codigo_unidad_medida'] ?? 'KG';
                    } elseif ($codigo_formula) {
                        $prodObj = DB::table('producto')->where('codigo', $codigo_formula)->first();
                        if ($prodObj) {
                            $prodCodigo = $prodObj->codigo;
                            $prodTipo = $prodObj->codigo_tipo_producto ?? 'MTP';
                            $prodUM = $prodObj->codigo_unidad_medida ?? 'KG';
                        }
                    }

                    $componentes_a_insertar[] = [
                        'codigo_producto' => $prodCodigo ?? $codigo_formula,
                        'cantidad' => 0,
                        'codigo_unidad_medida' => $prodUM,
                        'codigo_molde' => $codigo_molde,
                        'codigo_tipo_producto' => $prodTipo
                    ];
                }

                if ($extraCantReciclado > 0 && !empty($formula_data["codigo_material_reciclado"])) {
                    $componentes_a_insertar[] = [
                        "codigo_producto" => $formula_data["codigo_material_reciclado"],
                        "cantidad" => $extraCantReciclado,
                        "codigo_unidad_medida" => "KG",
                        "codigo_molde" => $codigo_molde,
                        "codigo_tipo_producto" => "REC"
                    ];
                }
            }

            if (empty($componentes_a_insertar)) {
                throw new \Exception("No hay componentes válidos para registrar.");
            }

            // 2. Construir el arreglo en formato de la función original
            $componentes_payload = [];
            foreach ($componentes_a_insertar as $comp) {
                $componentes_payload[] = [
                    'codigo_producto' => $comp['codigo_producto'],
                    'cantidad' => $comp['cantidad'],
                    'codigo_unidad_medida' => $comp['codigo_unidad_medida'],
                    'codigo_molde' => $comp['codigo_molde'],
                    'codigo_tipo_producto' => $comp['codigo_tipo_producto'],
                    'codigo_centro_trabajo' => $codigo_centro_trabajo,
                    'codigo_trabajador' => $codigo_trabajador,
                    'fecha_inicio' => $fecha,
                    'fecha_fin' => $fecha,
                    'hora_inicio' => $hora_inicio,
                    'hora_fin' => $hora_fin,
                    'fecha_inicio_maquina' => $fecha,
                    'hora_inicio_maquina' => $hora_inicio,
                    'fecha_fin_maquina' => $fecha,
                    'hora_fin_maquina' => $hora_fin,
                    'inyectada_inicial' => $valIni,
                    'inyectada_final' => $valFin,
                    'codigo_formula' => $codigo_formula, // Importante para la agrupación
                    'codigo_color' => $request->input('codigo_color'), // Color from UI
                    'idop_origen' => $comp['idop_origen'] ?? null
                ];
            }
            
            DB::commit();

            // Fake Request para delegar a storeComponentes
            $fakeRequest = new Request();
            $fakeRequest->merge([
                'componentes_json' => json_encode($componentes_payload),
                'codigo_almacen_consumo' => $codigo_almacen_consumo,
                'tipo_operacion_origen' => $tipo_operacion, // Pasamos el tipo de operación para distinguir Limpieza/Merma
                'destino_merma_clip' => $request->input('destino_merma_clip'),
                'usar_clip_recuperado' => $request->input('usar_clip_recuperado'),
                'idop_origen' => $request->input('idop_origen'),
                'cantidad_sets' => ($tipo_operacion === 'encartonado') ? $cantidad_total : null
            ]);
            $fakeRequest->headers->set('X-Requested-With', 'XMLHttpRequest'); // Force AJAX

            return $this->storeComponentes($fakeRequest, $idop, $id);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 422);
        }
    }
}
