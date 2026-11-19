<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\GuiaRemisionTercerosSalidaDetalle;

class TercerosLiquidacionController extends Controller
{
    public function index(Request $request)
    {
        $isFiltered = $request->hasAny(['fecha_desde', 'fecha_hasta', 'proveedor', 'estado', 'search']);
        $fechaDesde = $isFiltered ? $request->input('fecha_desde') : now()->startOfMonth()->toDateString();
        $fechaHasta = $isFiltered ? $request->input('fecha_hasta') : now()->endOfMonth()->toDateString();

        $query = DB::table('guia_remision_terceros_salida_detalle as d')
            ->join('guia_remision_terceros_salida as c', 'd.id_guia_salida', '=', 'c.id_guia_salida')
            ->where('c.estado_guia', '!=', 'ANULADA')
            ->select(
                'd.id_detalle_salida',
                'd.id_guia_salida',
                'c.numero_guia',
                'c.fecha_emision',
                'c.codigo_almacen_origen',
                'c.proveedor_destino',
                'c.ruc_proveedor',
                'c.observaciones',
                'd.codigo_producto',
                'd.cantidad_enviada',
                'd.cantidad_devuelta',
                'd.cantidad_merma',
                'd.estado_detalle'
            );

        if (!empty($fechaDesde)) {
            $query->where('c.fecha_emision', '>=', $fechaDesde);
        }

        if (!empty($fechaHasta)) {
            $query->where('c.fecha_emision', '<=', $fechaHasta);
        }

        if ($request->filled('proveedor')) {
            $query->where('c.proveedor_destino', $request->proveedor);
        }

        if ($request->filled('estado')) {
            $query->where('d.estado_detalle', $request->estado);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $matchingCodigos = DB::table('producto')
                ->where('codigo', 'LIKE', "%{$s}%")
                ->orWhere('descripcion', 'LIKE', "%{$s}%")
                ->pluck('codigo')
                ->toArray();

            $query->where(function ($q) use ($s, $matchingCodigos) {
                $q->where('c.numero_guia', 'LIKE', "%{$s}%")
                  ->orWhere('d.codigo_producto', 'LIKE', "%{$s}%")
                  ->orWhere('c.proveedor_destino', 'LIKE', "%{$s}%");
                if (!empty($matchingCodigos)) {
                    $q->orWhereIn('d.codigo_producto', $matchingCodigos);
                }
            });
        }

        // Totales resumen según filtros aplicados
        $totalesQuery = clone $query;
        $totalEnviado = (float) (clone $totalesQuery)->sum('d.cantidad_enviada');
        $totalDevuelto = (float) (clone $totalesQuery)->sum('d.cantidad_devuelta');
        $totalMerma = (float) (clone $totalesQuery)->sum('d.cantidad_merma');

        $resumen = (object) [
            'total_enviado'   => $totalEnviado,
            'total_devuelto'  => $totalDevuelto,
            'total_merma'     => $totalMerma,
            'saldo_pendiente' => max(0, $totalEnviado - $totalDevuelto - $totalMerma),
            'total_registros' => (clone $totalesQuery)->count(),
        ];

        $detalles = $query->orderBy('c.fecha_emision', 'desc')
            ->orderBy('d.id_detalle_salida', 'desc')
            ->paginate(10)
            ->appends($request->query());

        // Mapear descripciones de productos sin conflictos de collation
        $codigosProducto = $detalles->pluck('codigo_producto')->unique()->toArray();
        $productosMap = !empty($codigosProducto)
            ? DB::table('producto')->whereIn('codigo', $codigosProducto)->get()->keyBy('codigo')
            : collect();

        foreach ($detalles as $det) {
            $prod = $productosMap->get($det->codigo_producto);
            $det->producto_descripcion = $prod ? $prod->descripcion : '';
            $det->codigo_unidad_medida = $prod ? $prod->codigo_unidad_medida : 'KG';
        }

        // Obtener las conciliaciones (guías de ingreso) para los detalles paginados
        $detalleIds = $detalles->pluck('id_detalle_salida')->toArray();
        $conciliacionesPorDetalle = collect();

        if (!empty($detalleIds)) {
            $conciliaciones = DB::table('conciliacion_terceros as ct')
                ->join('detalle_guia_compras as dgc', 'ct.id_detalle_compra', '=', 'dgc.id_detalle_guia')
                ->join('guia_remision_compras as grc', 'dgc.id_guia', '=', 'grc.id_guia')
                ->whereIn('ct.id_detalle_salida', $detalleIds)
                ->select(
                    'ct.id_conciliacion',
                    'ct.id_detalle_salida',
                    'ct.cantidad_amortizada',
                    'ct.fecha_conciliacion',
                    'grc.id_guia as id_guia_compra',
                    'grc.numero_guia as guia_retorno',
                    'grc.fecha_emision as fecha_retorno',
                    'dgc.codigo_producto as producto_retornado',
                    'dgc.descripcion_producto as producto_retornado_desc',
                    'dgc.lote',
                    'dgc.codigo_almacen as almacen_destino'
                )
                ->orderBy('grc.fecha_emision', 'asc')
                ->orderBy('ct.id_conciliacion', 'asc')
                ->get();

            $conciliacionesPorDetalle = $conciliaciones->groupBy('id_detalle_salida');
        }

        $proveedores = DB::table('guia_remision_terceros_salida')
            ->select('proveedor_destino')
            ->distinct()
            ->orderBy('proveedor_destino')
            ->pluck('proveedor_destino');

        return view('terceros.liquidacion.index', compact(
            'detalles',
            'conciliacionesPorDetalle',
            'resumen',
            'proveedores',
            'fechaDesde',
            'fechaHasta'
        ));
    }

    public function cerrarConMerma(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            
            $detalle = GuiaRemisionTercerosSalidaDetalle::findOrFail($id);
            
            if ($detalle->estado_detalle == 'CERRADO_CON_MERMA' || $detalle->estado_detalle == 'COMPLETADO') {
                throw new \Exception('El detalle ya se encuentra cerrado o completado.');
            }
            
            $saldoPendiente = $detalle->cantidad_enviada - $detalle->cantidad_devuelta - $detalle->cantidad_merma;
            
            if ($saldoPendiente <= 0) {
                throw new \Exception('No hay saldo pendiente para mandar a merma.');
            }
            
            // Enviar saldo a merma
            $detalle->update([
                'cantidad_merma' => $detalle->cantidad_merma + $saldoPendiente,
                'estado_detalle' => 'CERRADO_CON_MERMA'
            ]);
            
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Se ha enviado el saldo residual a merma y se cerró la guía exitosamente.']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
