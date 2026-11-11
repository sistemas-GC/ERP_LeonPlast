<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $rango = $request->get('rango', 'mes');

        if ($rango === 'hoy') {
            $fecha_inicio = now()->startOfDay();
            $fecha_fin = now()->endOfDay();
        } elseif ($rango === 'semana') {
            $fecha_inicio = now()->startOfWeek();
            $fecha_fin = now()->endOfDay();
        } elseif ($rango === 'mes_anterior') {
            $fecha_inicio = now()->subMonth()->startOfMonth();
            $fecha_fin = now()->subMonth()->endOfMonth();
        } else {
            $fecha_inicio = now()->startOfMonth();
            $fecha_fin = now()->endOfDay();
        }

        // 1. Órdenes Activas
        $ordenesActivas = DB::table('orden_produccion_global')
            ->where('activo', 1)
            ->whereIn('estado', ['PENDIENTE', 'EN_PROCESO'])
            ->count();

        // 2. Mermas y %
        $totalProduccion = DB::table('produccion_ingresos_proceso')
            ->whereBetween('fecha_ingreso', [$fecha_inicio, $fecha_fin])
            ->where('estado', '!=', 'ANULADO')
            ->sum('cantidad');

        $totalMerma = DB::table('mermas')
            ->whereBetween('created_at', [$fecha_inicio, $fecha_fin])
            ->sum('cantidad');

        $porcentajeMerma = $totalProduccion > 0 ? round(($totalMerma / $totalProduccion) * 100, 2) : 0;

        // 3. Horas Hombre y Máquina
        $componentes = DB::table('componentes_orden_produccion_global')
            ->where('estado', 1)
            ->whereBetween('fecha_creacion', [$fecha_inicio, $fecha_fin])
            ->select('fecha_inicio', 'hora_inicio', 'fecha_fin', 'hora_fin', 
                     'fecha_inicio_maquina', 'hora_inicio_maquina', 'fecha_fin_maquina', 'hora_fin_maquina')
            ->get();

        $horasHombre = 0;
        $horasMaquina = 0;
        foreach ($componentes as $c) {
            if ($c->fecha_inicio && $c->hora_inicio && $c->fecha_fin && $c->hora_fin) {
                try {
                    $start = \Carbon\Carbon::parse($c->fecha_inicio . ' ' . $c->hora_inicio);
                    $end = \Carbon\Carbon::parse($c->fecha_fin . ' ' . $c->hora_fin);
                    if ($end->gt($start)) $horasHombre += $start->diffInMinutes($end) / 60;
                } catch (\Exception $e) {}
            }
            if ($c->fecha_inicio_maquina && $c->hora_inicio_maquina && $c->fecha_fin_maquina && $c->hora_fin_maquina) {
                try {
                    $startM = \Carbon\Carbon::parse($c->fecha_inicio_maquina . ' ' . $c->hora_inicio_maquina);
                    $endM = \Carbon\Carbon::parse($c->fecha_fin_maquina . ' ' . $c->hora_fin_maquina);
                    if ($endM->gt($startM)) $horasMaquina += $startM->diffInMinutes($endM) / 60;
                } catch (\Exception $e) {}
            }
        }

        $costoParam = DB::table('parametros_sistema')->where('codigo_parametro', 'COSTO_HORA_MAQUINA')->value('valor') ?? 15.5;
        $costoMaquinaTotal = $horasMaquina * $costoParam;

        // 4. Gráfico de Estados
        $estadosOP = DB::table('orden_produccion_global')
            ->select('estado', DB::raw('count(*) as total'))
            ->where('activo', 1)
            ->whereBetween('fecha_creacion', [$fecha_inicio, $fecha_fin])
            ->groupBy('estado')
            ->pluck('total', 'estado')->toArray();

        // 5. Tendencia de Mermas (30 días)
        $mermasTrend = DB::table('mermas')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(cantidad) as total'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // 6. Rendimiento por Centro
        $rendimientoCentros = DB::table('componentes_orden_produccion_global')
            ->select('descripcion_centro_trabajo as centro', DB::raw('SUM(cantidad) as total'))
            ->where('estado', 1)
            ->whereNotNull('codigo_centro_trabajo')
            ->whereBetween('fecha_creacion', [$fecha_inicio, $fecha_fin])
            ->groupBy('descripcion_centro_trabajo')
            ->orderByDesc('total')
            ->get();

        // 7. Órdenes Demoradas
        $ordenesDemoradas = DB::table('orden_produccion_global')
            ->select('codigo_op', 'descripcion_producto_proceso', 'fecha', 'estado')
            ->where('activo', 1)
            ->whereIn('estado', ['PENDIENTE', 'EN_PROCESO'])
            ->whereDate('fecha', '<', now()->toDateString())
            ->get();

        $datos = [
            'rango' => $rango,
            'stats' => [
                'ordenes_activas' => $ordenesActivas,
                'porcentaje_merma' => $porcentajeMerma,
                'total_merma' => $totalMerma,
                'horas_hombre' => round($horasHombre, 2),
                'horas_maquina' => round($horasMaquina, 2),
                'costo_maquina' => round($costoMaquinaTotal, 2)
            ],
            'chartEstados' => [
                'labels' => array_keys($estadosOP),
                'data' => array_values($estadosOP)
            ],
            'chartMermas' => [
                'labels' => $mermasTrend->pluck('date')->toArray(),
                'data' => $mermasTrend->pluck('total')->toArray()
            ],
            'chartCentros' => [
                'labels' => $rendimientoCentros->pluck('centro')->toArray(),
                'data' => $rendimientoCentros->pluck('total')->toArray()
            ],
            'ordenesDemoradas' => $ordenesDemoradas
        ];

        return view('admin.dashboard', $datos);
    }
}
