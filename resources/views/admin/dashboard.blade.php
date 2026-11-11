@extends('layouts.app')

@section('title', 'Dashboard de Producción')

@section('content')
<div class="max-w-7xl mx-auto space-y-8">

    <!-- Encabezado de Dashboard -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white/80 backdrop-blur-md p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="h-10 w-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center text-xl font-bold">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-800">Dashboard de Producción</h1>
                    <p class="text-slate-500 text-xs sm:text-sm font-medium mt-0.5">Indicadores Clave de Rendimiento (KPIs) y monitoreo de planta en tiempo real.</p>
                </div>
            </div>
        </div>
        
        <div class="shrink-0">
            <form id="filtroRangoForm" method="GET" action="{{ route('dashboard') }}" class="flex items-center space-x-3 bg-slate-50 p-1.5 rounded-xl border border-slate-200">
                <label for="rango" class="text-xs font-bold text-slate-600 uppercase tracking-wider pl-2 flex items-center gap-1.5">
                    <i class="fas fa-calendar-alt text-slate-400"></i>
                    <span>Período:</span>
                </label>
                <select name="rango" id="rango" onchange="document.getElementById('filtroRangoForm').submit()" class="bg-white border-0 text-slate-800 text-xs font-bold rounded-lg px-3 py-2 shadow-xs focus:ring-2 focus:ring-emerald-500 outline-none cursor-pointer">
                    <option value="hoy" {{ $rango == 'hoy' ? 'selected' : '' }}>Hoy</option>
                    <option value="semana" {{ $rango == 'semana' ? 'selected' : '' }}>Esta Semana</option>
                    <option value="mes" {{ $rango == 'mes' ? 'selected' : '' }}>Este Mes</option>
                    <option value="mes_anterior" {{ $rango == 'mes_anterior' ? 'selected' : '' }}>Mes Anterior</option>
                </select>
            </form>
        </div>
    </div>

    <!-- Widgets / Tarjetas KPI -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Órdenes Activas -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex flex-col justify-between relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-blue-500/5 rounded-full group-hover:scale-110 transition-transform"></div>
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Órdenes Activas</span>
                    <h3 class="text-3xl font-black text-slate-800 mt-2 tracking-tight">{{ $stats['ordenes_activas'] }}</h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                    <i class="fas fa-tasks"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md">En ejecución</span>
                <span class="text-slate-400 font-medium">Planta</span>
            </div>
        </div>

        <!-- Nivel de Merma -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex flex-col justify-between relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-rose-500/5 rounded-full group-hover:scale-110 transition-transform"></div>
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Nivel de Merma</span>
                    <h3 class="text-3xl font-black tracking-tight mt-2 {{ $stats['porcentaje_merma'] > 5 ? 'text-rose-600' : 'text-slate-800' }}">
                        {{ $stats['porcentaje_merma'] }}%
                    </h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shrink-0 group-hover:bg-rose-600 group-hover:text-white transition-colors">
                    <i class="fas fa-trash-alt"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="font-medium text-slate-500">Total acumulado:</span>
                <span class="font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md">{{ number_format($stats['total_merma'], 2) }} kg</span>
            </div>
        </div>

        <!-- Horas Hombre -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex flex-col justify-between relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-emerald-500/5 rounded-full group-hover:scale-110 transition-transform"></div>
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Horas Hombre</span>
                    <h3 class="text-3xl font-black text-slate-800 mt-2 tracking-tight">{{ $stats['horas_hombre'] }} <span class="text-lg font-bold text-slate-400">h</span></h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                    <i class="fas fa-user-clock"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">Registradas</span>
                <span class="text-slate-400 font-medium">Personal</span>
            </div>
        </div>

        <!-- Horas Máquina -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex flex-col justify-between relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-purple-500/5 rounded-full group-hover:scale-110 transition-transform"></div>
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Horas Máquina</span>
                    <h3 class="text-3xl font-black text-slate-800 mt-2 tracking-tight">{{ $stats['horas_maquina'] }} <span class="text-lg font-bold text-slate-400">h</span></h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shrink-0 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                    <i class="fas fa-cogs"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="font-medium text-slate-500">Costo Operativo:</span>
                <span class="font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded-md">S/ {{ number_format($stats['costo_maquina'], 2) }}</span>
            </div>
        </div>

    </div>

    <!-- Gráficos Principales (Sección 1) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Gráfico Dona: Estados OP -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wider flex items-center gap-2">
                    <i class="fas fa-chart-pie text-blue-500"></i>
                    Estado de Producción
                </h3>
            </div>
            <div class="relative h-64 my-auto">
                <canvas id="chartEstados"></canvas>
            </div>
            @if(empty($chartEstados['data']))
                <p class="text-center text-slate-400 text-xs mt-4 font-medium">No hay órdenes registradas en este período.</p>
            @endif
        </div>

        <!-- Gráfico Barras Horizontal: Centros de Trabajo -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm lg:col-span-2 flex flex-col justify-between">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wider flex items-center gap-2">
                    <i class="fas fa-industry text-purple-500"></i>
                    Volumen Producido por Centro de Trabajo
                </h3>
            </div>
            <div class="relative h-64 my-auto">
                <canvas id="chartCentros"></canvas>
            </div>
            @if(empty($chartCentros['data']))
                <p class="text-center text-slate-400 text-xs mt-4 font-medium">No hay volumen reportado en este período.</p>
            @endif
        </div>
    </div>

    <!-- Gráficos Principales y Alertas (Sección 2) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Gráfico Líneas: Tendencia Mermas -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm lg:col-span-2 flex flex-col justify-between">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wider flex items-center gap-2">
                    <i class="fas fa-chart-line text-rose-500"></i>
                    Tendencia de Merma (Últimos 30 días)
                </h3>
            </div>
            <div class="relative h-64 my-auto">
                <canvas id="chartMermas"></canvas>
            </div>
            @if(empty($chartMermas['data']))
                <p class="text-center text-slate-400 text-xs mt-4 font-medium">No hay registros de mermas en los últimos 30 días.</p>
            @endif
        </div>

        <!-- Tabla: OPs Demoradas / Alertas -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col justify-between">
            <div class="px-6 py-4 border-b border-slate-100 bg-rose-50/50 flex justify-between items-center">
                <h3 class="font-bold text-rose-700 uppercase text-xs tracking-wider flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle text-rose-500"></i>
                    Alertas de Retraso
                </h3>
                <span class="text-[10px] font-extrabold px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full">
                    {{ $ordenesDemoradas->count() }} PENDIENTES
                </span>
            </div>

            <div class="p-0 flex-1 overflow-y-auto max-h-72 divide-y divide-slate-100">
                @if($ordenesDemoradas->count() > 0)
                    @foreach($ordenesDemoradas as $od)
                        <div class="p-4 hover:bg-slate-50/80 transition-colors flex items-center justify-between gap-3">
                            <div class="space-y-0.5">
                                <span class="font-extrabold text-slate-900 text-sm hover:text-rose-600 transition-colors block">
                                    {{ $od->codigo_op }}
                                </span>
                                <p class="text-xs text-slate-500 line-clamp-1 font-medium">
                                    {{ $od->descripcion_producto_proceso }}
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-[11px] font-bold text-slate-600 block">
                                    {{ \Carbon\Carbon::parse($od->fecha)->format('d/m/Y') }}
                                </span>
                                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold rounded-md bg-rose-100 text-rose-700 mt-1">
                                    {{ $od->estado }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="flex flex-col items-center justify-center p-8 text-center my-auto space-y-2">
                        <div class="h-12 w-12 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-700">Sin órdenes demoradas</p>
                        <p class="text-xs text-slate-400 max-w-xs">Todas las órdenes de producción están marchando al día según programación.</p>
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    Chart.defaults.font.family = "'Plus Jakarta Sans', 'Inter', sans-serif";
    Chart.defaults.color = '#94a3b8';
    
    // 1. Gráfico Dona: Estados
    const ctxEstados = document.getElementById('chartEstados');
    if (ctxEstados && @json(!empty($chartEstados['data']))) {
        new Chart(ctxEstados, {
            type: 'doughnut',
            data: {
                labels: @json($chartEstados['labels']),
                datasets: [{
                    data: @json($chartEstados['data']),
                    backgroundColor: [
                        '#3b82f6', // blue
                        '#10b981', // green
                        '#f59e0b', // amber
                        '#ef4444', // red
                        '#8b5cf6'  // violet
                    ],
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom', 
                        labels: { 
                            usePointStyle: true, 
                            padding: 16,
                            font: { size: 11, weight: '600' }
                        } 
                    }
                },
                cutout: '72%'
            }
        });
    }

    // 2. Gráfico Barras: Centros de Trabajo
    const ctxCentros = document.getElementById('chartCentros');
    if (ctxCentros && @json(!empty($chartCentros['data']))) {
        new Chart(ctxCentros, {
            type: 'bar',
            data: {
                labels: @json($chartCentros['labels']),
                datasets: [{
                    label: 'Volumen (Kg/Unid)',
                    data: @json($chartCentros['data']),
                    backgroundColor: [
                        '#8b5cf6', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', 
                        '#06b6d4', '#ec4899', '#14b8a6', '#f97316', '#6366f1'
                    ],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 11, weight: '600' } } }
                }
            }
        });
    }

    // 3. Gráfico Líneas: Tendencia Mermas
    const ctxMermas = document.getElementById('chartMermas');
    if (ctxMermas && @json(!empty($chartMermas['data']))) {
        new Chart(ctxMermas, {
            type: 'line',
            data: {
                labels: @json($chartMermas['labels']),
                datasets: [{
                    label: 'Merma (Kg)',
                    data: @json($chartMermas['data']),
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.08)',
                    borderWidth: 2.5,
                    tension: 0.35,
                    fill: true,
                    pointBackgroundColor: '#ef4444',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { grid: { color: '#f1f5f9' }, beginAtZero: true }
                }
            }
        });
    }
});
</script>
@endsection