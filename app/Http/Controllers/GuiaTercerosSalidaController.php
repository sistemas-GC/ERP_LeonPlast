<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\GuiaRemisionTercerosSalida;
use App\Models\GuiaRemisionTercerosSalidaDetalle;
use App\Services\KardexService;
use Carbon\Carbon;

class GuiaTercerosSalidaController extends Controller
{
    public function index()
    {
        $guias = GuiaRemisionTercerosSalida::orderBy('fecha_emision', 'desc')->paginate(20);
        return view('terceros.salidas.index', compact('guias'));
    }

    public function show($id)
    {
        $guia = GuiaRemisionTercerosSalida::findOrFail($id);
        $detalles = DB::table('guia_remision_terceros_salida_detalle')
            ->where('id_guia_salida', $id)
            ->get();
            
        $codigos = $detalles->pluck('codigo_producto')->toArray();
        
        $productos = DB::table('producto')
            ->whereIn('codigo', $codigos)
            ->get()
            ->keyBy('codigo');
            
        foreach ($detalles as $detalle) {
            $detalle->descripcion_producto = isset($productos[$detalle->codigo_producto]) 
                ? $productos[$detalle->codigo_producto]->descripcion 
                : 'Producto no encontrado';
        }
            
        return view('terceros.salidas.show', compact('guia', 'detalles'));
    }

    public function create()
    {
        $almacenes = DB::table('almacen')->where('activo', 1)->get();
        $proveedores = DB::table('proveedores')->where('activo', 1)->get();
        // Listar todos los productos PEP activos
        $productos = DB::table('producto')
            ->where('codigo_tipo_producto', 'PEP')
            ->where('estado', 1)
            ->get();
            
        return view('terceros.salidas.create', compact('almacenes', 'proveedores', 'productos'));
    }

    public function store(Request $request, KardexService $kardexService)
    {
        $request->validate([
            'numero_guia' => 'required|string|max:50|unique:guia_remision_terceros_salida,numero_guia',
            'fecha_emision' => 'required|date',
            'proveedor_destino' => 'required|string',
            'codigo_almacen_origen' => 'required|string',
            'productos' => 'required|array|min:1',
            'productos.*.codigo' => 'required|string',
            'productos.*.cantidad' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();
            
            $prov = DB::table('proveedores')->where('razon_social', $request->proveedor_destino)->first();
            $ruc = $prov ? $prov->ruc : null;

            $guia = GuiaRemisionTercerosSalida::create([
                'numero_guia' => $request->numero_guia,
                'fecha_emision' => $request->fecha_emision,
                'codigo_almacen_origen' => $request->codigo_almacen_origen,
                'proveedor_destino' => $request->proveedor_destino,
                'ruc_proveedor' => $ruc,
                'motivo_traslado' => 'SERVICIOS DE TERCEROS',
                'observaciones' => $request->observaciones,
                'estado_guia' => 'EMITIDA',
                'usuario_registro' => Auth::id(),
            ]);

            foreach ($request->productos as $item) {
                // 1. Verificar Stock
                $stock = DB::table('inventario')
                    ->where('codigo_almacen', $request->codigo_almacen_origen)
                    ->where('codigo_producto', $item['codigo'])
                    ->sum('stock_actual');
                    
                if ($stock < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para el producto {$item['codigo']} en el almacén seleccionado. Disponible: {$stock}");
                }

                // 2. Registrar Detalle de Guía
                $detalle = GuiaRemisionTercerosSalidaDetalle::create([
                    'id_guia_salida' => $guia->id_guia_salida,
                    'codigo_producto' => $item['codigo'],
                    'cantidad_enviada' => $item['cantidad'],
                    'cantidad_devuelta' => 0,
                    'cantidad_merma' => 0,
                    'estado_detalle' => 'PENDIENTE'
                ]);

                // 3. Generar SALIDA en Movimientos de Inventario (FIFO)
                $lotes = DB::table('inventario')
                    ->where('codigo_almacen', $request->codigo_almacen_origen)
                    ->where('codigo_producto', $item['codigo'])
                    ->where('stock_actual', '>', 0)
                    ->orderBy('id_inventario', 'asc')
                    ->get();

                $cantidadRestante = $item['cantidad'];
                
                $totalDescontado = 0;
                $totalCostoLotes = 0;
                $primerMovId = null;

                foreach ($lotes as $lote) {
                    if ($cantidadRestante <= 0) break;

                    $cantidadDescontar = min($lote->stock_actual, $cantidadRestante);
                    
                    // Actualizar Inventario
                    DB::table('inventario')
                        ->where('id_inventario', $lote->id_inventario)
                        ->update([
                            'stock_actual' => $lote->stock_actual - $cantidadDescontar,
                            'fecha_ultimo_movimiento' => now()
                        ]);

                    $totalSalidaLote = $cantidadDescontar * $lote->costo_promedio;
                    
                    // Movimiento Inventario
                    $idMov = DB::table('movimientos_inventario')->insertGetId([
                        'codigo_almacen' => $request->codigo_almacen_origen,
                        'codigo_producto' => $item['codigo'],
                        'codigo_unidad_medida' => $lote->codigo_unidad_medida,
                        'lote' => $lote->lote,
                        'tipo_movimiento' => 'SALIDA',
                        'cantidad' => $cantidadDescontar,
                        'costo_unitario' => $lote->costo_promedio,
                        'total' => $totalSalidaLote,
                        'documento_referencia' => 'GUIA_SALIDA_TERCEROS',
                        'numero_referencia' => $guia->numero_guia,
                        'observaciones' => 'Salida para Tercero: ' . $guia->proveedor_destino,
                        'usuario_movimiento' => Auth::id(),
                        'estado' => 1,
                        'fecha_movimiento' => Carbon::parse($request->fecha_emision)->setTime(now()->hour, now()->minute, now()->second)->format('Y-m-d H:i:s'),
                        'tiene_kardex' => 1
                    ]);

                    if (!$primerMovId) $primerMovId = $idMov;
                    $totalDescontado += $cantidadDescontar;
                    $totalCostoLotes += $totalSalidaLote;
                    $cantidadRestante -= $cantidadDescontar;
                }

                if ($totalDescontado > 0) {
                    $costoSalidaPromedio = $totalCostoLotes / $totalDescontado;
                    
                    // Kardex
                    DB::table('kardex')->insert([
                        'codigo_almacen' => $request->codigo_almacen_origen,
                        'codigo_producto' => $item['codigo'],
                        'codigo_unidad_medida' => $lote->codigo_unidad_medida ?? 'UND',
                        'codigo_referencia_movimiento' => $primerMovId,
                        'fecha_movimiento' => Carbon::parse($request->fecha_emision)->setTime(now()->hour, now()->minute, now()->second)->format('Y-m-d H:i:s'),
                        'tipo_movimiento' => 'SALIDA',
                        'documento' => 'GUIA_SALIDA_TERCEROS',
                        'numero_documento' => $guia->numero_guia,
                        'cantidad_entrada' => 0,
                        'costo_entrada' => 0,
                        'total_entrada' => 0,
                        'cantidad_salida' => $totalDescontado,
                        'costo_salida' => $costoSalidaPromedio,
                        'total_salida' => $totalCostoLotes,
                        'observaciones' => 'Envio a Tercero: ' . $guia->proveedor_destino,
                        'lote' => 'VARIOS LOTES',
                        'usuario_registro' => Auth::id()
                    ]);
                }
                
                // Recalcular el Kardex de este almacén para cuadrar saldos
                $kardexService->recalcular($item['codigo'], $request->codigo_almacen_origen);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Guía de Salida a Tercero registrada correctamente con ' . count($request->productos) . ' productos.']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getProductosConStock(Request $request)
    {
        $almacen = $request->query('almacen');
        if (!$almacen) {
            return response()->json([]);
        }

        $productos = DB::table('inventario')
            ->join('producto', 'inventario.codigo_producto', '=', 'producto.codigo')
            ->select('producto.codigo', 'producto.descripcion', DB::raw('SUM(inventario.stock_actual) as stock_total'))
            ->where('inventario.codigo_almacen', $almacen)
            ->where('producto.codigo_tipo_producto', 'PEP')
            ->where('producto.estado', 1)
            ->groupBy('producto.codigo', 'producto.descripcion')
            ->having('stock_total', '>', 0)
            ->get();

        return response()->json($productos);
    }
}
