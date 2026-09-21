@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<div class="container mx-auto pb-8 md:pb-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800 flex items-center">
                Ejecución: {{ strtoupper($proceso->proceso_desc) }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-600 mt-1">
                Orden #{{ $orden->idop }} - <span class="font-semibold">{{ $orden->descripcion_producto_proceso }}</span>
            </p>
        </div>
        <div class="mt-4 md:mt-0 flex flex-wrap items-center gap-3">
            <span class="px-3 py-1 text-xs font-semibold uppercase rounded-full text-white {{ $estado_proceso_actual == 'COMPLETADO' ? 'bg-green-600' : ($estado_proceso_actual == 'EN_PROCESO' ? 'bg-primary' : 'bg-slate-500') }}">
                ESTADO: {{ $estado_proceso_actual }}
            </span>
            <a href="{{ route('ordenes.procesos.index', $orden->idop) }}" class="shrink-0 flex items-center justify-center bg-slate-500 hover:bg-slate-600 text-white font-semibold py-2 px-3 sm:px-4 rounded-lg shadow transition">
                <i class="fas fa-arrow-left"></i>
                <span class="hidden sm:inline ml-2">Volver</span>
            </a>
        </div>
    </div>

    <!-- Cargador de Fórmulas -->
    @if(($es_mezclado || $es_inyectado || $es_ensamblado || $es_ensamblado_manual || $es_encartonado || $es_molido || $es_troquelado || $es_horneado) && $estado_proceso_actual !== 'COMPLETADO')
    <div class="bg-white rounded-xl shadow-md border-t-4 border-orange-500 mb-6 overflow-hidden">
        <div class="bg-slate-50 border-b border-slate-200">
            @if($es_inyectado || $es_troquelado || $es_horneado || $es_ensamblado || $es_ensamblado_manual || $es_encartonado)
            <ul class="flex flex-wrap text-sm font-medium text-center text-slate-500 border-b border-slate-200" id="op-tabs">
                @if($es_inyectado || $es_troquelado || $es_horneado)
                <li class="me-2">
                    <a href="#" onclick="switchOpTab('inyectado')" class="inline-block p-4 text-blue-600 bg-white border-t border-l border-r border-slate-200 rounded-t-lg active" id="tab-inyectado">
                        Producción ({{ $es_troquelado ? 'Troquelado' : ($es_horneado ? 'Horneado' : 'Inyectado') }})
                    </a>
                </li>
                @if($es_troquelado)
                <li class="me-2">
                    <a href="#" onclick="switchOpTab('merma_empaque')" class="inline-block p-4 border-b-0 hover:text-slate-600 hover:bg-slate-50 text-slate-500" id="tab-merma_empaque">Merma Empaque</a>
                </li>
                <li class="me-2">
                    <a href="#" onclick="switchOpTab('merma_clip')" class="inline-block p-4 border-b-0 hover:text-slate-600 hover:bg-slate-50 text-slate-500" id="tab-merma_clip">Merma Clip</a>
                </li>
                @endif
                @endif
                @if($es_ensamblado || $es_ensamblado_manual)
                <li class="me-2">
                    <a href="#" onclick="switchOpTab('ensamblado')" class="inline-block p-4 text-blue-600 bg-white border-t border-l border-r border-slate-200 rounded-t-lg active" id="tab-ensamblado">Producción ({{ $es_ensamblado_manual ? 'Ensamblado Manual' : 'Ensamblado' }})</a>
                </li>
                <li class="me-2">
                    <a href="#" onclick="switchOpTab('merma_clip')" class="inline-block p-4 border-b-0 hover:text-slate-600 hover:bg-slate-50 text-slate-500" id="tab-merma_clip">Merma Clip</a>
                </li>
                <li class="me-2">
                    <a href="#" onclick="switchOpTab('recuperado_clip')" class="inline-block p-4 border-b-0 hover:text-slate-600 hover:bg-slate-50 text-slate-500" id="tab-recuperado_clip">Clip Recuperado</a>
                </li>
                <li class="me-2">
                    <a href="#" onclick="switchOpTab('merma_cascara')" class="inline-block p-4 border-b-0 hover:text-slate-600 hover:bg-slate-50 text-slate-500" id="tab-merma_cascara">Cáscara para molido</a>
                </li>
                @endif
                @if($es_encartonado)
                <li class="me-2">
                    <a href="#" onclick="switchOpTab('encartonado')" class="inline-block p-4 text-blue-600 bg-white border-t border-l border-r border-slate-200 rounded-t-lg active" id="tab-encartonado">Producción (Encartonado)</a>
                </li>
                <li class="me-2">
                    <a href="#" onclick="switchOpTab('merma_clip')" class="inline-block p-4 border-b-0 hover:text-slate-600 hover:bg-slate-50 text-slate-500" id="tab-merma_clip">Merma Clip</a>
                </li>
                <li class="me-2">
                    <a href="#" onclick="switchOpTab('merma_cascara')" class="inline-block p-4 border-b-0 hover:text-slate-600 hover:bg-slate-50 text-slate-500" id="tab-merma_cascara">Cáscara para molido</a>
                </li>
                <li class="me-2">
                    <a href="#" onclick="switchOpTab('merma_carton')" class="inline-block p-4 border-b-0 hover:text-slate-600 hover:bg-slate-50 text-slate-500" id="tab-merma_carton">Merma Cartón</a>
                </li>
                @endif
                @if($es_inyectado)
                <li class="me-2">
                    <a href="#" onclick="switchOpTab('recuperado_molido')" class="inline-block p-4 border-b-0 hover:text-slate-600 hover:bg-slate-50 text-slate-500" id="tab-recuperado_molido">Colada / Cáscara</a>
                </li>
                <li class="me-2">
                    <a href="#" onclick="switchOpTab('purga')" class="inline-block p-4 border-b-0 hover:text-slate-600 hover:bg-slate-50 text-slate-500" id="tab-purga">Purga</a>
                </li>
                <li class="me-2">
                    <a href="#" onclick="switchOpTab('limpieza')" class="inline-block p-4 border-b-0 hover:text-slate-600 hover:bg-slate-50 text-slate-500" id="tab-limpieza">Limpieza</a>
                </li>
                <li class="me-2">
                    <a href="#" onclick="switchOpTab('recuperado_maquina')" class="inline-block p-4 border-b-0 hover:text-slate-600 hover:bg-slate-50 text-slate-500" id="tab-recuperado_maquina">Molido de Máquina</a>
                </li>
                @endif
            </ul>
            @else
            <div class="px-6 py-4 flex items-center">
                <h2 class="text-lg font-bold text-slate-800">
                    <i class="fas fa-flask mr-2 text-orange-500"></i>{{ $es_molido ? 'Cargar Fórmula de Molido' : 'Cargar Fórmula de Mezclado' }}
                </h2>
            </div>
            @endif

            @if($es_troquelado)
            <div class="px-6 py-3 bg-slate-100 border-t border-slate-200 flex flex-wrap items-center justify-between gap-4 text-xs">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-sm flex items-center gap-1.5">
                        <span class="text-slate-500 font-medium"><i class="fas fa-weight-hanging text-slate-400 mr-1"></i>Peso Bruto:</span>
                        <span class="font-bold text-slate-800">{{ number_format($peso_inicial ?? 0, 2) }} KG</span>
                    </div>
                    <div class="bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-sm flex items-center gap-1.5">
                        <span class="text-slate-500 font-medium"><i class="fas fa-box text-slate-400 mr-1"></i>Tara:</span>
                        <span class="font-bold text-slate-800">{{ number_format($tara ?? 0, 2) }} KG</span>
                    </div>
                    <div class="bg-white px-3 py-1.5 rounded-lg border border-blue-200 shadow-sm flex items-center gap-1.5">
                        <span class="text-blue-700 font-semibold"><i class="fas fa-balance-scale text-blue-500 mr-1"></i>Peso Neto:</span>
                        <span class="font-bold text-blue-700">{{ number_format($peso_neto ?? 0, 2) }} KG</span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="bg-white px-3 py-1.5 rounded-lg border border-amber-200 shadow-sm flex items-center gap-1.5">
                        <span class="text-amber-700 font-semibold"><i class="fas fa-arrow-circle-down text-amber-500 mr-1"></i>Consumido:</span>
                        <span class="font-bold text-amber-700">{{ number_format($peso_consumido ?? 0, 2) }} KG</span>
                    </div>
                    <div class="bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-sm flex items-center gap-1.5">
                        <span class="text-slate-600 font-semibold"><i class="fas fa-chart-pie text-slate-400 mr-1"></i>Saldo Rollo:</span>
                        <span class="font-bold {{ (($peso_neto ?? 0) - ($peso_consumido ?? 0)) < 0 ? 'text-red-600' : 'text-emerald-600' }}">
                            {{ number_format(($peso_neto ?? 0) - ($peso_consumido ?? 0), 2) }} KG
                        </span>
                    </div>
                </div>
            </div>
            @endif
        </div>
        <div class="p-6 bg-white">
            <input type="hidden" id="tipo_operacion" value="{{ $es_inyectado || $es_troquelado || $es_horneado ? 'inyectado' : ($es_mezclado ? 'mezclado' : ($es_ensamblado || $es_ensamblado_manual ? 'ensamblado' : ($es_encartonado ? 'encartonado' : ''))) }}">
            
            <div class="flex flex-wrap items-end gap-4">
                
                @if($es_inyectado || $es_ensamblado || $es_ensamblado_manual || $es_encartonado || $es_molido || $es_troquelado || $es_horneado || $es_mezclado)
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1" id="lbl_centro">{{ $es_troquelado ? 'Troqueladora (Centro)' : ($es_horneado ? 'Horno (Centro)' : ($es_molido ? 'Molino (Centro)' : ($es_ensamblado || $es_ensamblado_manual ? 'Ensambladora (Centro)' : ($es_encartonado ? 'Encartonadora (Centro)' : ($es_mezclado ? 'Mezcladora (Centro)' : 'Inyectora (Centro)'))))) }}</label>
                    <select id="centro_global" class="w-full border-slate-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm py-2 px-3">
                        <option value="">-- Seleccione --</option>
                        @foreach($centros_trabajo as $ct)
                            <option value="{{ $ct->codigo }}">{{ $ct->codigo }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                
                @if($es_inyectado)
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Molde a usar</label>
                    <select id="molde_global" class="w-full border-slate-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm py-2 px-3" onchange="vincularFormula()">
                        <option value="">-- Seleccione --</option>
                        @foreach($moldes as $m)
                            <option value="{{ $m->codigo }}" data-formula="{{ $m->codigo_formula ?? '' }}">
                                {{ $m->codigo }} - {{ $m->descripcion }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                
                <div class="flex-1 min-w-50" id="wrapper_formula">
                    <label class="block text-xs font-semibold text-slate-700 mb-1" id="lbl_formula">{{ $es_molido ? 'Producto a Moler' : ($es_ensamblado || $es_ensamblado_manual ? 'Producto a Ensamblar' : ($es_encartonado ? 'Fórmula Encartonado' : 'Fórmula/Color')) }}</label>
                    <select id="formula_selector" class="w-full border-slate-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm py-2 px-3">
                        <option value="">-- Seleccione --</option>
                        @foreach($formulas_disponibles as $fm)
                            <option value="{{ $fm->codigo }}">{{ $fm->codigo }} - {{ $fm->descripcion }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1 min-w-50 hidden" id="wrapper_inventario_clip">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Inventario Clip</label>
                    <select id="inventario_selector_clip" class="w-full border-slate-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm py-2 px-3">
                        <option value="">-- Seleccione --</option>
                        @foreach($inventario_merma_clip as $ic)
                            <option value="{{ $ic->codigo_producto }}">{{ $ic->codigo_producto }} - {{ $ic->descripcion }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1 min-w-50 hidden" id="wrapper_inventario_cascara">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Producto</label>
                    <select id="cascara_selector" class="w-full border-slate-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm py-2 px-3">
                        <option value="">-- Seleccione --</option>
                        @foreach($inventario_merma_cascara as $ic)
                            <option value="{{ $ic->codigo_producto }}">{{ $ic->codigo_producto }} - {{ $ic->descripcion }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1 min-w-50 hidden" id="wrapper_inventario_carton">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Producto Cartón</label>
                    <select id="carton_selector" class="w-full border-slate-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm py-2 px-3">
                        <option value="">-- Seleccione --</option>
                        @if(isset($inventario_merma_carton))
                            @foreach($inventario_merma_carton as $ic)
                                <option value="{{ $ic->codigo_producto }}">{{ $ic->codigo_producto }} - {{ $ic->descripcion }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cant. (KG)</label>
                    <input type="number" id="cantidad_global" class="w-24 border-slate-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm py-2 px-3" step="0.01">
                </div>
            </div>

            <div class="flex flex-wrap items-end gap-4 mt-2">
                @if($es_ensamblado_manual)
                <div class="flex items-center mt-2 border border-blue-200 bg-blue-50 rounded-lg p-2 gap-3" id="panel_clip_recuperado">
                    <label class="flex items-center text-sm font-semibold text-blue-800 cursor-pointer" title="Consume exclusivamente clips con estado rechazado y lote -REC">
                        <input type="checkbox" id="chk_usar_clip_recuperado" class="rounded text-blue-600 focus:ring-blue-500 mr-2">
                        Usar Clip Recuperado (Priorizar Lote -REC)
                    </label>
                </div>
                @endif
                @if($es_inyectado || $es_mezclado)
                <div class="flex items-center mt-2 border border-orange-200 bg-orange-50 rounded-lg p-2 gap-3" id="panel_reciclado">
                    <label class="flex items-center text-sm font-semibold text-orange-800 cursor-pointer">
                        <input type="checkbox" id="chk_usar_reciclado" class="rounded text-orange-600 focus:ring-orange-500 mr-2" onchange="togglePanelReciclado()">
                        {{ $es_inyectado ? 'Viene de Mezclado' : 'Utilizar Material Reciclado (Equivalente de la Fórmula)' }}
                    </label>
                    
                    <div id="container_cant_reciclado" class="flex items-center hidden ml-2">
                        <label class="text-xs text-orange-700 font-medium mr-2">{{ $es_inyectado ? 'Cant. (KG) :' : 'Cant. Reciclado (KG):' }}</label>
                        <input type="number" id="cant_reciclado_global" class="w-24 border-slate-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500 text-sm py-1.5 px-2" step="0.01">
                        @if(!$es_inyectado)
                        <button type="button" onclick="autocompletarMaximoReciclado()" class="ml-2 px-2 py-1 bg-orange-200 text-orange-800 text-xs font-semibold rounded hover:bg-orange-300 transition" title="Autocompletar el 100% equivalente a materia virgen">
                            Máx (100%)
                        </button>
                        @endif
                    </div>
                </div>
                
                <script>
                    function autocompletarMaximoReciclado() {
                        const f = document.getElementById('formula_selector').value;
                        const c = parseFloat(document.getElementById('cantidad_global').value || 0);
                        if (!f || c <= 0) return window.toast('Seleccione Fórmula y especifique Cant. Global.', 'warning');
                        
                        let url = `{{ $url_api_formula ?? '/api/formulas/composicion' }}?codigo_formula=${encodeURIComponent(f)}`;
                        fetch(url).then(r => r.json()).then(data => {
                            if (data.success) {
                                let pesoVirgin = 0;
                                data.componentes.forEach(comp => {
                                    const desc = (comp.descripcion_producto || '').toUpperCase();
                                    const esPigmento = desc.includes('COLOR') || desc.includes('MASTERBATCH') || desc.includes('PIGMENTO') || comp.codigo_tipo_producto === 'PIG';
                                    if (!esPigmento) {
                                        pesoVirgin += parseFloat(comp.cantidad_nominal) || 0;
                                    }
                                });
                                const esInyectado = document.getElementById('tipo_operacion') && document.getElementById('tipo_operacion').value === 'inyectado';
                                let max = c;
                                if (!esInyectado) {
                                    max = c * pesoVirgin;
                                }
                                document.getElementById('cant_reciclado_global').value = max.toFixed(2);
                            }
                        });
                    }

                    function togglePanelReciclado() {
                        const chk = document.getElementById('chk_usar_reciclado');
                        const container = document.getElementById('container_cant_reciclado');
                        const inputCant = document.getElementById('cant_reciclado_global');
                        if (chk.checked) {
                            container.classList.remove('hidden');
                            inputCant.focus();
                        } else {
                            container.classList.add('hidden');
                            inputCant.value = '';
                        }
                    }
                </script>
                @endif

                <div class="relative w-80 md:w-96 min-w-[320px]" id="wrapper_trabajador_search">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Trabajador</label>
                    <div class="relative">
                        <input type="text" id="trabajador_search_input" class="w-full border-slate-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm py-2 px-3 pr-8 truncate" placeholder="Buscar trabajador..." autocomplete="off">
                        <input type="hidden" id="trabajador_global" value="">
                        <button type="button" id="btn_clear_trabajador" onclick="limpiarTrabajadorGlobal()" class="hidden absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 text-xs">
                            <i class="fas fa-times"></i>
                        </button>
                        <div id="trabajador_options" class="hidden absolute left-0 right-0 bg-white border border-slate-200 max-h-48 overflow-y-auto z-50 shadow-xl rounded-md mt-1"></div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Fecha</label>
                    <input type="date" id="fecha_global" value="{{ $orden->fecha ?? date('Y-m-d') }}"
                        class="border-slate-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm py-2 px-3">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Hora Inicio</label>
                    <input type="time" id="hora_ini_global" value="08:00"
                        class="w-28 border-slate-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm py-2 px-3">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Hora Fin</label>
                    <input type="time" id="hora_fin_global" value="17:00"
                        class="w-28 border-slate-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm py-2 px-3">
                </div>

                <div class="flex-1"></div>

                <button type="button" onclick="cargarEjecucionAgrupada()" class="px-5 py-2 {{ $es_inyectado || $es_troquelado || $es_horneado ? 'bg-orange-600 hover:bg-orange-700' : 'bg-primary hover:bg-primary-dark' }} text-white font-medium rounded-md shadow-sm transition" id="btn_cargar">
                    <i class="fas fa-box-open mr-2"></i>Cargar {{ $es_troquelado ? 'Troquelado' : ($es_horneado ? 'Horneado' : ($es_mezclado ? 'Fórmula' : ($es_ensamblado || $es_ensamblado_manual ? 'Ensamblado' : ($es_encartonado ? 'Encartonado' : ($es_molido ? 'Molido' : 'Inyectado'))))) }}
                </button>
            </div>

            @if($es_encartonado)
            <div id="panel_colores_encartonado" class="mt-4 pt-4 border-t border-slate-200">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-2 gap-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center">
                            <i class="fas fa-palette text-indigo-500 mr-1.5"></i> Selección de 4 Colores (6 und / 25% c/u)
                        </span>
                        <span id="badge_contador_colores" class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-slate-100 text-slate-600 border border-slate-300">
                            <span id="contador_colores">0</span> / 4 seleccionados
                        </span>
                    </div>
                    <button type="button" onclick="limpiarColoresEncartonado()" class="text-xs text-slate-500 hover:text-red-600 font-medium transition self-start sm:self-auto">
                        <i class="fas fa-undo-alt mr-1"></i> Limpiar selección
                    </button>
                </div>
                <p class="text-xs text-slate-500 mb-3">Haga clic sobre los ganchos de color para seleccionarlos. El sistema consumirá automáticamente ganchos ensamblados (24 unidades totales) distribuidos equitativamente entre los 4 colores elegidos.</p>
                
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2.5">
                    @foreach($colores as $col)
                    @php
                        $swatch = '#94a3b8';
                        $cUpper = strtoupper($col->descripcion . ' ' . $col->codigo);
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
                    @endphp
                    <div id="card_color_{{ $col->codigo }}" 
                         onclick="toggleColorEncartonado('{{ $col->codigo }}')" 
                         class="color-chip-encartonado cursor-pointer rounded-lg border-2 border-slate-200 bg-white p-2.5 transition-all duration-200 hover:border-primary/50 hover:shadow-sm flex items-center justify-between select-none"
                         data-color="{{ $col->codigo }}">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-4 h-4 rounded-full border border-slate-300 shrink-0 shadow-xs" style="background-color: {{ $swatch }};"></span>
                            <div class="truncate">
                                <div class="text-xs font-semibold text-slate-800 truncate" title="{{ $col->descripcion }}">{{ $col->descripcion }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $col->codigo }}</div>
                            </div>
                        </div>
                        <div class="color-check-icon hidden text-primary ml-1 shrink-0">
                            <i class="fas fa-check-circle text-sm"></i>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="flex flex-wrap items-end gap-4 mt-3 pt-3 border-t border-slate-100">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Almacén de Consumo</label>
                    <select id="codigo_almacen_consumo" class="border-slate-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm py-2 px-3 w-64">
                        <option value="">-- Seleccione Almacén --</option>
                        @foreach($almacenes as $almacen)
                            <option value="{{ $almacen->codigo_almacen }}" {{ $proceso_produccion_almacen == $almacen->codigo_almacen ? 'selected' : '' }}>
                                {{ $almacen->descripcion }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($es_inyectado)
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Inyectada Inicial</label>
                    <input type="number" id="inyectada_inicial_global" value="{{ $inyectada_inicial_sugerida ?? '' }}" class="w-32 border-slate-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm py-2 px-3" placeholder="Ej. 1000" step="1">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Inyectada Final</label>
                    <input type="number" id="inyectada_final_global" value="" class="w-32 border-slate-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm py-2 px-3" placeholder="Ej. 1500" step="1">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tiempo Ciclo (seg)</label>
                    <input type="number" id="tiempo_ciclo_global" value="{{ $proceso->tiempo_ciclo ?? '' }}" class="w-32 border-slate-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm py-2 px-3" placeholder="Ej. 15" step="1">
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Stock Warning Banner -->
    <div id="stock-warning" class="hidden bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl mb-4">
        <div class="flex items-start">
            <i class="fas fa-exclamation-triangle text-red-600 mt-0.5 mr-3"></i>
            <div>
                <p class="text-sm font-semibold text-red-800">Stock insuficiente</p>
                <p id="stock-warning-text" class="text-sm text-red-700 mt-1"></p>
            </div>
        </div>
    </div>

    <!-- Detalle de Componentes -->
    <!-- Hidden input for stock check when the form block is hidden -->
    @if(!($es_mezclado || $es_inyectado || $es_ensamblado || $es_ensamblado_manual || $es_encartonado || $es_molido) || $estado_proceso_actual === 'COMPLETADO')
        <input type="hidden" id="codigo_almacen_consumo" value="{{ $proceso_produccion_almacen ?? '' }}">
    @endif

    <div class="bg-white rounded-xl shadow-md border-t-4 border-primary overflow-hidden mb-6">
        <div class="bg-slate-50 border-b border-slate-200 px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4">
            <h2 class="text-lg font-bold text-slate-800">
                <i class="fas fa-list-alt mr-2 text-primary"></i>Detalle de Materiales / Actividades
            </h2>
            <button type="button" onclick="agregarFilaManual()" class="shrink-0 flex items-center justify-center bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-3 sm:px-4 rounded-lg shadow transition {{ $estado_proceso_actual === 'COMPLETADO' ? 'opacity-50 cursor-not-allowed' : '' }}" {{ $estado_proceso_actual === 'COMPLETADO' ? 'disabled' : '' }}>
                <i class="fas fa-plus"></i><span class="ml-2">Fila Manual</span>
            </button>
        </div>
        
        <div class="p-6">
            <form id="form_masivo" action="{{ route('ordenes.procesos.componentes.store', [$orden->idop, $proceso->id]) }}" method="POST">
                @csrf
                <input type="hidden" name="componentes_json" id="componentes_json">
                <input type="hidden" name="codigo_almacen_consumo" id="codigo_almacen_consumo_hidden">
                <input type="hidden" name="productos_resultantes_json" id="productos_resultantes_json">
            </form>
                
            <div class="overflow-x-auto min-h-87.5 pb-10">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-800 text-white">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider">Tipo</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider">Material / Actividad</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider">Centro</th>
                                @if($es_inyectado) <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider">Molde</th> @endif
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider">Cant.</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider">U.M.</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider">Trabajador</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider">Fecha</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider">Hora In/Fin (Hombre)</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider">Hora In/Fin (Máquina)</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider">Observación</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_items" class="bg-white divide-y divide-slate-200">
                            @if(($es_inyectado || $es_mezclado || $es_troquelado || $es_horneado || $es_ensamblado || $es_ensamblado_manual || $es_encartonado || $es_molido) && isset($cargas_agrupadas) && $cargas_agrupadas->count() > 0)
                                @foreach($cargas_agrupadas as $key => $grupo)
                                    @php
                                        // Extraer datos comunes del primer componente del grupo
                                        $first = $grupo->first();
                                        $esActividad = ($first->codigo_tipo_producto === 'ACT' || $first->codigo_tipo_producto === 'MANUAL');
                                        $esManual = str_contains($key, 'MANUAL') || $esActividad;
                                        
                                        // Obtener nombre del color basado en el producto asociado a la formula
                                        $nombreColorDisplay = null;
                                        if(!$esManual && !empty($first->codigo_formula_produccion)) {
                                            $prodAsociado = \DB::table('producto')->where('codigo', $first->codigo_formula_produccion)->first();
                                            if($prodAsociado && !empty($prodAsociado->codigo_color)) {
                                                $colorDb = \DB::table('color')->where('codigo', $prodAsociado->codigo_color)->first();
                                                $nombreColorDisplay = $colorDb ? $colorDb->descripcion : $prodAsociado->codigo_color;
                                            }
                                        }

                                        // Total de KG sumando la cantidad base o nominal, es una aproximacion visual
                                        $totalKG = $grupo->sum('cantidad');
                                    @endphp
                                    <tr id="row_grupo_{{ $loop->index }}" class="bg-slate-50 border-l-4 border-l-primary main-row">
                                        <td class="px-3 py-3 whitespace-nowrap">
                                             <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $esActividad ? 'bg-teal-100 text-teal-800' : ($esManual ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800') }}">
                                                {{ $esActividad ? 'ACTIVIDAD' : ($esManual ? 'MANUAL' : 'CARGA') }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="col-span-12 md:col-span-3 lg:col-span-3 flex items-center justify-start h-full">
                                                @php
                                                    $mostrarProductoDirecto = $esActividad || in_array($first->tipo_operacion, ['merma_empaque', 'recuperado_clip', 'merma_cascara', 'merma_carton']);
                                                @endphp
                                                <div class="text-sm font-bold text-slate-800 break-words mb-1">
                                                    @if($mostrarProductoDirecto)
                                                        {{ $first->descripcion_producto }}
                                                    @else
                                                        {{ $nombreColorDisplay ?? ($first->descripcion_formula_produccion ?? ($first->descripcion_producto ?? 'Registro Manual')) }}
                                                    @endif
                                                </div>
                                                @if($mostrarProductoDirecto)
                                                    <div class="text-xs text-slate-500 break-words">{{ $first->codigo_producto }}</div>
                                                @else
                                                    <div class="text-xs text-slate-500 break-words">{{ $first->codigo_formula_produccion ?? $first->codigo_producto ?? 'N/A' }}</div>
                                                @endif
                                                @if($first->codigo_color)
                                                    <span class="ml-2 px-2 py-0.5 rounded text-[10px] bg-slate-200 text-slate-700">{{ $first->codigo_color }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap text-sm text-slate-500">{{ $first->codigo_centro_trabajo }}</td>
                                        @if($es_inyectado)
                                        <td class="px-3 py-3 whitespace-nowrap text-sm text-slate-500">{{ $first->codigo_molde ?? 'N/A' }}</td>
                                        @endif
                                        <td class="px-3 py-3 whitespace-nowrap text-sm font-bold text-slate-900">{{ number_format($totalKG, 2) }}</td>
                                        <td class="px-3 py-3 whitespace-nowrap text-sm text-slate-500">KG</td>
                                        <td class="px-3 py-3 whitespace-nowrap text-sm text-slate-500">{{ $first->codigo_trabajador }}</td>
                                        <td class="px-3 py-3 whitespace-nowrap text-xs text-slate-500">{{ $first->fecha_inicio ?? $first->fecha ?? 'N/A' }}</td>
                                        <td class="px-3 py-3 whitespace-nowrap text-xs text-slate-500">{{ $first->hora_inicio }} - {{ $first->hora_fin }}</td>
                                        <td class="px-3 py-3 whitespace-nowrap text-xs text-slate-500">{{ $first->hora_inicio_maquina }} - {{ $first->hora_fin_maquina }}</td>
                                        @php
                                            $bgLabel = 'bg-blue-100 text-blue-800';
                                            $textLabel = 'PRODUCCIÓN';
                                            if($first->codigo_tipo_producto === 'ACT' || $first->codigo_tipo_producto === 'MANUAL') { $bgLabel = 'bg-teal-100 text-teal-800'; $textLabel = 'ACTIVIDAD'; }
                                            elseif($first->tipo_operacion === 'purga') { $bgLabel = 'bg-red-100 text-red-800'; $textLabel = 'PURGA'; }
                                            elseif($first->tipo_operacion === 'limpieza') { $bgLabel = 'bg-amber-100 text-amber-800'; $textLabel = 'LIMPIEZA'; }
                                            elseif($first->tipo_operacion === 'merma_empaque') { $bgLabel = 'bg-amber-100 text-amber-800'; $textLabel = 'MERMA EMPAQUE'; }
                                            elseif($first->tipo_operacion === 'merma_clip') { $bgLabel = 'bg-orange-100 text-orange-800'; $textLabel = 'MERMA CLIP'; }
                                            elseif($first->tipo_operacion === 'merma_carton') { $bgLabel = 'bg-amber-100 text-amber-800'; $textLabel = 'MERMA CARTÓN'; }
                                            elseif(str_contains($first->tipo_operacion ?? '', 'molido') || str_contains($first->tipo_operacion ?? '', 'maquina') || $first->tipo_operacion === 'recuperado_clip' || $first->tipo_operacion === 'merma_cascara') { $bgLabel = 'bg-purple-100 text-purple-800'; $textLabel = 'RECICLADO'; }
                                            elseif(str_contains($first->tipo_operacion ?? '', 'merma')) { $bgLabel = 'bg-orange-100 text-orange-800'; $textLabel = 'MERMA'; }
                                        @endphp
                                        <td class="px-3 py-3 text-center whitespace-nowrap">
                                            <span class="px-2 py-1 text-[10px] font-semibold rounded-full {{ $bgLabel }}">{{ $textLabel }}</span>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap text-center text-sm font-medium">
                                            <button type="button" onclick="toggleDetallesCarga('{{ $loop->index }}')" class="text-slate-500 hover:text-primary mr-2" title="Ver Detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <!-- Filas de detalle ocultas por defecto -->
                                    @foreach($grupo as $r)
                                    <tr id="row_display_{{ $r->id_op_componentes }}" class="bg-white detalle-carga-{{ $loop->parent->index }} hidden" style="background-color: #f8fafc;">
                                        <td class="px-3 py-2 whitespace-nowrap pl-8 border-l-4 border-l-slate-300">
                                            <span class="px-2 py-1 inline-flex text-[10px] leading-5 font-semibold rounded-full bg-slate-200 text-slate-800">
                                                {{ $r->codigo_tipo_producto }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="text-xs text-slate-900 font-medium">{{ $r->descripcion_producto }}</div>
                                            <div class="text-[9px] text-slate-500">{{ $r->codigo_producto }}</div>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-500">{{ $r->codigo_centro_trabajo }}</td>
                                        @if($es_inyectado)
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-500">{{ $r->codigo_molde }}</td>
                                        @endif
                                        <td class="px-3 py-2 whitespace-nowrap text-xs font-semibold text-slate-700">{{ number_format($r->cantidad, 2) }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-500">{{ $r->codigo_unidad_medida }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-500">{{ $r->codigo_trabajador }}</td>
                                        <td colspan="2" class="px-3 py-2 text-xs text-slate-400">Detalle Interno</td>
                                        <td class="px-3 py-2 text-xs text-slate-500 max-w-[150px] truncate" title="{{ $r->observacion }}">{{ $r->observacion ?? '-' }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-center text-xs font-medium">
                                            @if($estado_proceso_actual !== 'COMPLETADO')
                                            <button type="button" onclick="editarRegistrado({{ $r->id_op_componentes }})" class="text-primary hover:text-primary mr-2" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" onclick="eliminarRegistrado({{ $r->id_op_componentes }})" class="text-red-500 hover:text-red-700" title="Desactivar">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                @endforeach
                            @else
                            @foreach($registrados as $r)
                            <tr id="row_display_{{ $r->id_op_componentes }}" class="bg-slate-50 main-row">
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-200 text-slate-800">
                                        {{ $r->codigo_tipo_producto }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="text-sm text-slate-900 font-bold">{{ $r->descripcion_producto }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $r->codigo_producto }}</div>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500">{{ $r->codigo_centro_trabajo }}</td>
                                @if($es_inyectado) <td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500">{{ $r->codigo_molde }}</td> @endif
                                <td class="px-3 py-2 whitespace-nowrap text-sm font-bold text-slate-900">{{ number_format($r->cantidad, 2) }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500">{{ $r->codigo_unidad_medida }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500">{{ $r->codigo_trabajador }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-500">{{ $r->fecha_inicio ?? 'N/A' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-500">{{ $r->hora_inicio }} - {{ $r->hora_fin }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-500">{{ $r->hora_inicio_maquina }} - {{ $r->hora_fin_maquina }}</td>
                                @php
                                    $bgLabelR = 'bg-blue-100 text-blue-800';
                                    $textLabelR = 'PRODUCCIÓN';
                                    if($r->codigo_tipo_producto === 'ACT' || $r->codigo_tipo_producto === 'MANUAL') { $bgLabelR = 'bg-teal-100 text-teal-800'; $textLabelR = 'ACTIVIDAD'; }
                                    elseif($r->tipo_operacion === 'limpieza') { $bgLabelR = 'bg-red-100 text-red-800'; $textLabelR = 'LIMPIEZA'; }
                                    elseif(str_contains($r->tipo_operacion ?? '', 'merma')) { $bgLabelR = 'bg-orange-100 text-orange-800'; $textLabelR = 'MERMA'; }
                                    elseif(str_contains($r->tipo_operacion ?? '', 'molido') || str_contains($r->tipo_operacion ?? '', 'maquina')) { $bgLabelR = 'bg-purple-100 text-purple-800'; $textLabelR = 'RECICLADO'; }
                                @endphp
                                <td class="px-3 py-2 text-center whitespace-nowrap">
                                    <span class="px-2 py-1 text-[10px] font-semibold rounded-full {{ $bgLabelR }}">{{ $textLabelR }}</span>
                                </td>
                                <td class="px-3 py-2 text-xs text-slate-500 max-w-[150px] truncate" title="{{ $r->observacion }}">{{ $r->observacion ?? '-' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-center text-sm font-medium">
                                    @if($estado_proceso_actual !== 'COMPLETADO')
                                    <button type="button" onclick="editarRegistrado({{ $r->id_op_componentes }})" class="text-primary hover:text-primary mr-2" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" onclick="eliminarRegistrado({{ $r->id_op_componentes }})" class="text-red-500 hover:text-red-700" title="Desactivar">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    @endif
                                </td>
                            </tr>

                            <tr id="row_edit_{{ $r->id_op_componentes }}" class="hidden">
                                <td colspan="11" class="p-0 border-b border-slate-200">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-4 p-4 bg-primary-50 rounded-b-lg component-edit-container" data-id="{{ $r->id_op_componentes }}">
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-600 mb-1">Cantidad</label>
                                            <input type="number" name="cantidad" step="0.01" min="0.01"
                                                value="{{ $r->cantidad }}"
                                                class="w-full border-slate-300 rounded-md text-sm py-1.5 px-2 focus:ring-primary focus:border-primary"
                                                {{ $r->codigo_tipo_producto === 'ACT' ? 'readonly' : '' }}>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-slate-600 mb-1">Trabajador</label>
                                            <select name="codigo_trabajador"
                                                class="w-full border-slate-300 rounded-md text-sm py-1.5 px-2 focus:ring-primary focus:border-primary">
                                                <option value="">--</option>
                                                @foreach($trabajadores as $t)
                                                    <option value="{{ $t->codigo }}" {{ $r->codigo_trabajador == $t->codigo ? 'selected' : '' }}>
                                                        {{ $t->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">F. In/Fin (Hombre)</label>
                                            <div class="flex gap-1">
                                                <input type="date" name="fecha_inicio" value="{{ $r->fecha_inicio ?? '' }}"
                                                    class="w-1/2 border-slate-300 rounded-md text-sm py-1.5 px-1 focus:ring-primary focus:border-primary">
                                                <input type="date" name="fecha_fin" value="{{ $r->fecha_fin ?? '' }}"
                                                    class="w-1/2 border-slate-300 rounded-md text-sm py-1.5 px-1 focus:ring-primary focus:border-primary">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">H. In/Fin (Hombre)</label>
                                            <div class="flex gap-1">
                                                <input type="time" name="hora_inicio" value="{{ $r->hora_inicio ?? '' }}"
                                                    class="w-1/2 border-slate-300 rounded-md text-sm py-1.5 px-1 focus:ring-primary focus:border-primary">
                                                <input type="time" name="hora_fin" value="{{ $r->hora_fin ?? '' }}"
                                                    class="w-1/2 border-slate-300 rounded-md text-sm py-1.5 px-1 focus:ring-primary focus:border-primary">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">F. In/Fin (Máquina)</label>
                                            <div class="flex gap-1">
                                                <input type="date" name="fecha_inicio_maquina" value="{{ $r->fecha_inicio_maquina ?? '' }}"
                                                    class="w-1/2 border-slate-300 rounded-md text-sm py-1.5 px-1 focus:ring-primary focus:border-primary">
                                                <input type="date" name="fecha_fin_maquina" value="{{ $r->fecha_fin_maquina ?? '' }}"
                                                    class="w-1/2 border-slate-300 rounded-md text-sm py-1.5 px-1 focus:ring-primary focus:border-primary">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">H. In/Fin (Máquina)</label>
                                            <div class="flex gap-1">
                                                <input type="time" name="hora_inicio_maquina" value="{{ $r->hora_inicio_maquina ?? '' }}"
                                                    class="w-1/2 border-slate-300 rounded-md text-sm py-1.5 px-1 focus:ring-primary focus:border-primary">
                                                <input type="time" name="hora_fin_maquina" value="{{ $r->hora_fin_maquina ?? '' }}"
                                                    class="w-1/2 border-slate-300 rounded-md text-sm py-1.5 px-1 focus:ring-primary focus:border-primary">
                                            </div>
                                        </div>

                                        <div class="flex gap-2 items-end justify-start xl:justify-end pb-0.5 sm:col-span-2 md:col-span-3 lg:col-span-4 xl:col-span-1">
                                            <button type="button" onclick="submitUpdateComponente({{ $r->id_op_componentes }}, this)"
                                                class="px-4 py-1.5 bg-primary hover:bg-primary-dark text-white text-sm font-semibold rounded-lg shadow transition">
                                                Guardar Cambios
                                            </button>
                                            <button type="button" onclick="cancelarEdicion({{ $r->id_op_componentes }})"
                                                class="px-4 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-semibold rounded-lg transition">
                                                Cancelar
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Controls -->
                <div id="pagination_controls" class="flex justify-between items-center mt-4 border-t border-slate-200 pt-4">
                    <div class="text-sm text-slate-700">
                        Mostrando <span id="page_start_info" class="font-bold">0</span> a <span id="page_end_info" class="font-bold">0</span> de <span id="page_total_info" class="font-bold">0</span> registros
                    </div>
                    <div class="flex space-x-1" id="pagination_buttons">
                        <!-- Buttons injected via JS -->
                    </div>
                </div>

                @if($estado_proceso_actual !== 'COMPLETADO' && !$es_actividad)
                <!-- Productos Resultantes -->
                <div class="mt-6 pt-4 border-t border-slate-200">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-base font-bold text-slate-800">
                            <i class="fas fa-arrow-right text-primary mr-2"></i>Productos Resultantes
                        </h3>
                        <button type="button" onclick="agregarFilaProductoResultante()" class="text-sm bg-primary text-white px-3 py-1.5 rounded-lg hover:bg-primary-dark transition shadow-sm hover:shadow">
                            <i class="fas fa-plus mr-1"></i>Agregar Producto
                        </button>
                    </div>
                    <div class="overflow-x-auto pb-48">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-700 text-white">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase">Producto</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase">Cantidad</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase">U.M.</th>
                                    <th class="px-3 py-2 text-center text-xs font-semibold uppercase w-16">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tbody_resultantes" class="bg-white divide-y divide-slate-200"></tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Footer del Formulario -->
                <div class="mt-6 pt-6 border-t border-slate-200 flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                    <div>
                        @if($estado_proceso_actual !== 'COMPLETADO')
                            <button type="button" onclick="confirmarCierre()" class="px-6 py-3 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition shadow-sm {{ !$tiene_componentes ? 'opacity-50 cursor-not-allowed' : '' }}" {{ !$tiene_componentes ? 'disabled' : '' }}>
                                🏁 Finalizar y Cerrar Proceso
                            </button>
                        @endif
                    </div>
                    
                    <div class="flex flex-wrap items-center justify-end gap-4">
                        @if($estado_proceso_actual !== 'COMPLETADO')
                        <button type="button" id="btnGuardar" onclick="enviarGuardado()" class="px-6 py-3 bg-primary text-white rounded-lg font-medium hover:bg-primary-dark transition shadow-sm transform hover:-translate-y-0.5">
                            💾 Guardar Componentes
                        </button>
                        @endif
                    </div>
                </div>

        </div>
    </div>
</div>

<form id="form_finalizar" action="{{ route('ordenes.procesos.finalizar', [$orden->idop, $proceso->id]) }}" method="POST">
    @csrf
</form>

<form id="form_delete" action="" method="POST">
    @csrf
    @method('DELETE')
</form>

<form id="form_update_componente" action="" method="POST">
    @csrf
    @method('PUT')
    <input type="hidden" name="cantidad" id="upd_cantidad">
    <input type="hidden" name="codigo_trabajador" id="upd_trabajador">
    <input type="hidden" name="fecha_inicio" id="upd_fecha_inicio">
    <input type="hidden" name="fecha_fin" id="upd_fecha_fin">
    <input type="hidden" name="hora_inicio" id="upd_hora_inicio">
    <input type="hidden" name="hora_fin" id="upd_hora_fin">
    <input type="hidden" name="fecha_inicio_maquina" id="upd_fecha_inicio_maquina">
    <input type="hidden" name="fecha_fin_maquina" id="upd_fecha_fin_maquina">
    <input type="hidden" name="hora_inicio_maquina" id="upd_hora_inicio_maquina">
    <input type="hidden" name="hora_fin_maquina" id="upd_hora_fin_maquina">
    <input type="hidden" name="observacion" id="upd_observacion">
</form>

<script>
    function submitUpdateComponente(id, btn) {
        const container = btn.closest('.component-edit-container');
        
        document.getElementById('upd_cantidad').value = container.querySelector('input[name="cantidad"]').value;
        document.getElementById('upd_trabajador').value = container.querySelector('select[name="codigo_trabajador"]').value;
        document.getElementById('upd_fecha_inicio').value = container.querySelector('input[name="fecha_inicio"]').value;
        document.getElementById('upd_fecha_fin').value = container.querySelector('input[name="fecha_fin"]').value;
        document.getElementById('upd_hora_inicio').value = container.querySelector('input[name="hora_inicio"]').value;
        document.getElementById('upd_hora_fin').value = container.querySelector('input[name="hora_fin"]').value;
        document.getElementById('upd_fecha_inicio_maquina').value = container.querySelector('input[name="fecha_inicio_maquina"]').value;
        document.getElementById('upd_fecha_fin_maquina').value = container.querySelector('input[name="fecha_fin_maquina"]').value;
        document.getElementById('upd_hora_inicio_maquina').value = container.querySelector('input[name="hora_inicio_maquina"]').value;
        document.getElementById('upd_hora_fin_maquina').value = container.querySelector('input[name="hora_fin_maquina"]').value;
        const obsInput = container.querySelector('input[name="observacion"]');
        if (obsInput) {
            document.getElementById('upd_observacion').value = obsInput.value;
        } else {
            document.getElementById('upd_observacion').value = '';
        }
        
        const form = document.getElementById('form_update_componente');
        form.action = `/produccion/ordenes/{{ $orden->idop }}/procesos/{{ $proceso->id }}/componentes/${id}`;
        form.submit();
    }

    const urlApiFormula = '/produccion/api/formulas/composicion';
    const urlVerificarStock = '/produccion/api/verificar-stock';
    const urlDeleteBase = "{{ url("produccion/ordenes/{$orden->idop}/procesos/{$proceso->id}/componentes") }}";
    
    const centros = @json($centros_trabajo);
    const trabajadores = @json($trabajadores);
    const tiposData = @json($tipos_producto);
    const unidadesData = @json($unidades);
    const moldesData = @json($moldes);
    const formulasData = @json($formulas_disponibles);
    const esInyectado = {{ $es_inyectado ? 'true' : 'false' }};
    const esTroquelado = {{ $es_troquelado ? 'true' : 'false' }};
    const esHorneado = {{ $es_horneado ? 'true' : 'false' }};
    const esEnsamblado = {{ $es_ensamblado ? 'true' : 'false' }};
    const esEncartonado = {{ $es_encartonado ? 'true' : 'false' }};
    const esMolido = {{ (isset($es_molido) && $es_molido) ? 'true' : 'false' }};
    const pesoNeto = {{ $peso_neto ?? 0 }};
    const pesoConsumido = {{ $peso_consumido ?? 0 }};

    let selectedColorsEncartonado = [];

    function toggleColorEncartonado(colorCode) {
        const idx = selectedColorsEncartonado.indexOf(colorCode);
        if (idx > -1) {
            selectedColorsEncartonado.splice(idx, 1);
        } else {
            if (selectedColorsEncartonado.length >= 4) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Máximo 4 Colores',
                    text: 'Solo puede seleccionar exactamente 4 colores para el encartonado.'
                });
                return;
            }
            selectedColorsEncartonado.push(colorCode);
        }
        actualizarVistaColoresEncartonado();
    }

    function limpiarColoresEncartonado() {
        selectedColorsEncartonado = [];
        actualizarVistaColoresEncartonado();
    }

    function actualizarVistaColoresEncartonado() {
        document.querySelectorAll('.color-chip-encartonado').forEach(el => {
            const code = el.getAttribute('data-color');
            const icon = el.querySelector('.color-check-icon');
            if (selectedColorsEncartonado.includes(code)) {
                el.classList.remove('border-slate-200', 'bg-white');
                el.classList.add('border-primary', 'bg-primary/5', 'shadow-xs');
                if (icon) icon.classList.remove('hidden');
            } else {
                el.classList.add('border-slate-200', 'bg-white');
                el.classList.remove('border-primary', 'bg-primary/5', 'shadow-xs');
                if (icon) icon.classList.add('hidden');
            }
        });

        const contador = document.getElementById('contador_colores');
        const badge = document.getElementById('badge_contador_colores');
        if (contador) contador.textContent = selectedColorsEncartonado.length;
        if (badge) {
            if (selectedColorsEncartonado.length === 4) {
                badge.className = 'px-2.5 py-0.5 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300';
            } else {
                badge.className = 'px-2.5 py-0.5 text-xs font-bold rounded-full bg-slate-100 text-slate-600 border border-slate-300';
            }
        }
    }

    function vincularFormula() {
        const sm = document.getElementById('molde_global');
        const sf = document.getElementById('formula_selector');
        if (sm && sm.selectedIndex > 0 && sm.options[sm.selectedIndex].getAttribute('data-formula')) {
            sf.value = sm.options[sm.selectedIndex].getAttribute('data-formula');
        }
    }

    function verificarStock() {
        const almacenEl = document.getElementById('codigo_almacen_consumo');
        if (!almacenEl) return;
        const almacen = almacenEl.value;

        // Limpiar estado visual de las filas de tipo ACT (Actividad)
        document.querySelectorAll('#tbody_items .nueva-fila').forEach(row => {
            const tipo = row.querySelector('.c-tipo')?.value;
            if (tipo === 'ACT') {
                row.classList.remove('bg-red-50');
                const cell = row.querySelector('.stock-cell');
                if (cell) {
                    cell.textContent = '-';
                    cell.classList.remove('text-green-600', 'text-red-600');
                }
            }
        });

        // Filtrar únicamente los productos que NO sean actividades
        const filasValidables = [...document.querySelectorAll('#tbody_items .nueva-fila')]
            .filter(row => {
                const tipo = row.querySelector('.c-tipo')?.value;
                return tipo !== 'ACT';
            });

        const productos = filasValidables
            .map(row => row.querySelector('.c-prod')?.value)
            .filter(Boolean);

        const warning = document.getElementById('stock-warning');
        const text = document.getElementById('stock-warning-text');
        const btn = document.getElementById('btnGuardar');

        if (productos.length === 0 || !almacen) {
            if (warning) warning.classList.add('hidden');
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            return;
        }

        const params = new URLSearchParams();
        params.set('codigo_almacen', almacen);
        productos.forEach(p => params.append('productos[]', p));

        fetch(urlVerificarStock + '?' + params.toString())
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                let faltantes = [];
                const filas = document.querySelectorAll('#tbody_items .nueva-fila');
                filas.forEach(row => {
                    const tipo = row.querySelector('.c-tipo')?.value;
                    if (tipo === 'ACT') {
                        row.classList.remove('bg-red-50');
                        return;
                    }
                    const prod = row.querySelector('.c-prod')?.value;
                    const req = parseFloat(row.querySelector('.c-cant')?.value) || 0;
                    const stock = (prod && data.data[prod] !== undefined) ? data.data[prod] : 0;
                    const cell = row.querySelector('.stock-cell');

                    if (cell) {
                        cell.textContent = stock.toFixed(2);
                        cell.classList.remove('text-green-600', 'text-red-600');
                    }
                    if (stock >= req) {
                        if (cell) cell.classList.add('text-green-600');
                        row.classList.remove('bg-red-50');
                    } else {
                        if (cell) cell.classList.add('text-red-600');
                        row.classList.add('bg-red-50');
                        const descInput = row.querySelector('.c-prod-search');
                        const prodName = (descInput && descInput.value) ? descInput.value : prod;
                        faltantes.push(prodName + ' (req: ' + req.toFixed(2) + ', disp: ' + stock.toFixed(2) + ')');
                    }
                });

                if (faltantes.length > 0) {
                    if (warning) warning.classList.remove('hidden');
                    const almacenEl = document.getElementById('codigo_almacen_consumo');
                    const almacenNombre = almacenEl.options[almacenEl.selectedIndex]?.text || almacen;
                    if (text) text.textContent = 'Falta stock en "' + almacenNombre + '" para: ' + faltantes.join('; ') + '. Seleccione otro almacén o abastezca primero.';
                    if (btn) {
                        btn.disabled = true;
                        btn.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                } else {
                    if (warning) warning.classList.add('hidden');
                    if (btn) {
                        btn.disabled = false;
                        btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                }
            })
            .catch(err => console.error('Error al verificar stock:', err));
    }

    function guardarEstadoCabecera() {
        try {
            const centro = document.getElementById('centro_global') ? document.getElementById('centro_global').value : '';
            const molde = document.getElementById('molde_global') ? document.getElementById('molde_global').value : '';
            const trabajadorCod = document.getElementById('trabajador_global') ? document.getElementById('trabajador_global').value : '';
            const trabajadorNom = document.getElementById('trabajador_search_input') ? document.getElementById('trabajador_search_input').value : '';
            const fecha = document.getElementById('fecha_global') ? document.getElementById('fecha_global').value : '';
            const horaIni = document.getElementById('hora_ini_global') ? document.getElementById('hora_ini_global').value : '';
            const horaFin = document.getElementById('hora_fin_global') ? document.getElementById('hora_fin_global').value : '';
            const almacen = document.getElementById('codigo_almacen_consumo') ? document.getElementById('codigo_almacen_consumo').value : '';
            const tipoOp = document.getElementById('tipo_operacion') ? document.getElementById('tipo_operacion').value : '';
            const inyIni = document.getElementById('inyectada_inicial_global') ? document.getElementById('inyectada_inicial_global').value : '';
            const inyFinal = document.getElementById('inyectada_final_global') ? document.getElementById('inyectada_final_global').value : '';
            const tiempoCiclo = document.getElementById('tiempo_ciclo_global') ? document.getElementById('tiempo_ciclo_global').value : '';

            const estado = {
                centro: centro,
                molde: molde,
                trabajador_codigo: trabajadorCod,
                trabajador_nombre: trabajadorNom,
                fecha: fecha,
                hora_inicio: horaIni,
                hora_fin: horaFin,
                codigo_almacen_consumo: almacen,
                tipo_operacion: tipoOp,
                inyectada_siguiente_inicial: (inyFinal !== null && inyFinal !== '') ? inyFinal : inyIni,
                tiempo_ciclo: tiempoCiclo
            };

            sessionStorage.setItem('ejecucion_cabecera_{{ $orden->idop }}_{{ $proceso->id }}', JSON.stringify(estado));
        } catch (e) {
            console.warn('Error al guardar estado de cabecera:', e);
        }
    }

    function restaurarEstadoCabecera() {
        try {
            const key = 'ejecucion_cabecera_{{ $orden->idop }}_{{ $proceso->id }}';
            const raw = sessionStorage.getItem(key);
            if (!raw) return;

            const estado = JSON.parse(raw);
            if (!estado) return;

            // 1. Centro de Trabajo (Inyectora, Mezcladora, etc.)
            const elCentro = document.getElementById('centro_global');
            if (elCentro && estado.centro) {
                elCentro.value = estado.centro;
            }

            // 2. Molde a usar
            const elMolde = document.getElementById('molde_global');
            if (elMolde && estado.molde) {
                elMolde.value = estado.molde;
            }

            // 3. Trabajador Predictivo
            const elTrabNom = document.getElementById('trabajador_search_input');
            const elTrabCod = document.getElementById('trabajador_global');
            const btnClear = document.getElementById('btn_clear_trabajador');
            if (elTrabNom && elTrabCod && estado.trabajador_codigo) {
                elTrabCod.value = estado.trabajador_codigo;
                elTrabNom.value = estado.trabajador_nombre || estado.trabajador_codigo;
                if (btnClear) btnClear.classList.remove('hidden');
            }

            // 4. Fecha y Horas
            const elFecha = document.getElementById('fecha_global');
            if (elFecha && estado.fecha) {
                elFecha.value = estado.fecha;
            }
            const elHoraIni = document.getElementById('hora_ini_global');
            if (elHoraIni && estado.hora_inicio) {
                elHoraIni.value = estado.hora_inicio;
            }
            const elHoraFin = document.getElementById('hora_fin_global');
            if (elHoraFin && estado.hora_fin) {
                elHoraFin.value = estado.hora_fin;
            }

            // 5. Almacén de Consumo
            const elAlm = document.getElementById('codigo_almacen_consumo');
            if (elAlm && estado.codigo_almacen_consumo) {
                elAlm.value = estado.codigo_almacen_consumo;
            }

            // 6. Encadenamiento de Inyectadas: la inicial toma la última final
            const elInyIni = document.getElementById('inyectada_inicial_global');
            const elInyFin = document.getElementById('inyectada_final_global');
            if (elInyIni && estado.inyectada_siguiente_inicial !== undefined && estado.inyectada_siguiente_inicial !== '') {
                elInyIni.value = estado.inyectada_siguiente_inicial;
            }
            if (elInyFin) {
                elInyFin.value = ''; // En blanco para la nueva lectura
            }

            // 7. Tiempo de Ciclo
            const elCiclo = document.getElementById('tiempo_ciclo_global');
            if (elCiclo && estado.tiempo_ciclo) {
                elCiclo.value = estado.tiempo_ciclo;
            }

            // 8. Restaurar Pestaña / Tab Activa
            if (estado.tipo_operacion && typeof switchOpTab === 'function') {
                switchOpTab(estado.tipo_operacion);
            }

            // 9. Resetear explícitamente Fórmula y Cantidad
            const elFormula = document.getElementById('formula_selector');
            if (elFormula) elFormula.value = '';
            const elClip = document.getElementById('inventario_selector_clip');
            if (elClip) elClip.value = '';
            const elCascara = document.getElementById('cascara_selector');
            if (elCascara) elCascara.value = '';
            const elCarton = document.getElementById('carton_selector');
            if (elCarton) elCarton.value = '';
            const elCant = document.getElementById('cantidad_global');
            if (elCant) elCant.value = '';
            const elRec = document.getElementById('cant_reciclado_global');
            if (elRec) elRec.value = '';
            if (typeof limpiarColoresEncartonado === 'function') {
                limpiarColoresEncartonado();
            }

        } catch (e) {
            console.warn('Error al restaurar cabecera:', e);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        setupTrabajadorPredictivo();
        restaurarEstadoCabecera();

        const wrapFormula = document.getElementById('wrapper_formula');
        const wrapClip = document.getElementById('wrapper_inventario_clip');
        const wrapCascara = document.getElementById('wrapper_inventario_cascara');
        const wrapCarton = document.getElementById('wrapper_inventario_carton');
        const panelClipRecuperado = document.getElementById('panel_clip_recuperado');
        
        if (wrapClip) wrapClip.classList.add('hidden');
        if (wrapCascara) wrapCascara.classList.add('hidden');
        if (wrapCarton) wrapCarton.classList.add('hidden');
        
        const almacenSelect = document.getElementById('codigo_almacen_consumo');
        if (almacenSelect) {
            almacenSelect.addEventListener('change', verificarStock);
        }
        verificarStock();
        updatePagination();

        // Auto-add suggested resulting product
        const sugeridoCodigo = @json($producto_sugerido->codigo ?? null);
        const sugeridoDesc = @json($producto_sugerido->descripcion ?? null);
        const esCompletado = '{{ $estado_proceso_actual }}' === 'COMPLETADO';
        
        if (sugeridoCodigo && !esCompletado && typeof agregarFilaProductoResultante === 'function') {
            const resultantesTable = document.getElementById('tbody_resultantes');
            if (resultantesTable) {
                agregarFilaProductoResultante(sugeridoCodigo, sugeridoDesc);
            }
        }
    });

    function setupSearchableDropdown(rowId) {
        const row = document.getElementById(rowId);
        const searchInput = row.querySelector('.c-prod-search');
        const hiddenInput = row.querySelector('.c-prod');
        const optionsContainer = row.querySelector('.custom-options');
        const tipoSelect = row.querySelector('.c-tipo');

        let searchTimeout;

        if (tipoSelect) {
            tipoSelect.onchange = () => {
                searchInput.value = '';
                hiddenInput.value = '';
                optionsContainer.style.display = 'none';
            };
        }

        searchInput.onfocus = () => {
            buscarProductos(searchInput.value);
            optionsContainer.style.display = 'block';
        };

        searchInput.oninput = () => {
            hiddenInput.value = '';
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => buscarProductos(searchInput.value), 300);
            optionsContainer.style.display = 'block';
        };

        function buscarProductos(filter) {
            const isResultado = row.classList.contains('nueva-fila-resultado');
            let tipo = tipoSelect ? tipoSelect.value : '';
            if (isResultado) {
                if (esMolido) {
                    tipo = 'REC';
                } else if (esEnsamblado) {
                    tipo = '';
                } else {
                    tipo = 'PEP';
                }
            }
            
            let url = `/productos/search-ajax?q=${encodeURIComponent(filter)}`;
            if (tipo) url += `&tipo=${encodeURIComponent(tipo)}`;

            optionsContainer.innerHTML = '<div class="p-2 text-sm text-slate-400">Buscando...</div>';

            fetch(url)
                .then(r => r.json())
                .then(data => {
                    optionsContainer.innerHTML = '';
                    if (data.length === 0) {
                        optionsContainer.innerHTML = '<div class="p-2 text-sm text-slate-500">Sin resultados</div>';
                        return;
                    }
                    data.forEach(p => {
                        const div = document.createElement('div');
                        div.className = 'p-2 cursor-pointer border-b border-slate-100 text-xs hover:bg-primary-50 text-slate-700';
                        div.textContent = `${p.id} - ${getProdName(p.text)}`;
                        div.onclick = () => {
                            searchInput.value = getProdName(p.text);
                            hiddenInput.value = p.id;
                            if (tipoSelect && p.codigo_tipo_producto) tipoSelect.value = p.codigo_tipo_producto;
                            optionsContainer.style.display = 'none';
                            verificarStock();
                        };
                        optionsContainer.appendChild(div);
                    });
                })
                .catch(() => {
                    optionsContainer.innerHTML = '<div class="p-2 text-sm text-red-500">Error al buscar</div>';
                });
        }

        function getProdName(text) {
            const m = text.match(/\]\s*(.*)/);
            return m ? m[1] : text;
        }

        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !optionsContainer.contains(e.target)) {
                optionsContainer.style.display = 'none';
            }
        });
    }

    function recalcularFormulaDesdeREC() {
        const globalInput = document.getElementById('cantidad_global');
        if (!globalInput) return;
        const cantGlobal = parseFloat(globalInput.value) || 0;

        let sumRec = 0;
        const filas = document.querySelectorAll('#tbody_items .nueva-fila');
        filas.forEach(row => {
            const tipo = row.querySelector('.c-tipo');
            if (tipo && tipo.value === 'REC') {
                sumRec += parseFloat(row.querySelector('.c-cant').value) || 0;
            }
        });

        let cantEfectiva = Math.max(0, cantGlobal - sumRec);
        const esMezcladoOp = document.getElementById('tipo_operacion') && document.getElementById('tipo_operacion').value === 'mezclado';

        filas.forEach(row => {
            const nominalInput = row.querySelector('.c-nominal');
            if (nominalInput && nominalInput.value) {
                const nominal = parseFloat(nominalInput.value);
                const cantInput = row.querySelector('.c-cant');
                let cantFinal = cantEfectiva * nominal;
                
                if (esMezcladoOp && cantEfectiva <= 1) {
                    const descInput = row.querySelector('.c-prod-search');
                    if (descInput) {
                        const desc = descInput.value.toUpperCase();
                        const esPigmento = desc.includes('COLOR') || desc.includes('MASTERBATCH') || desc.includes('PIGMENTO');
                        if (esPigmento) {
                            cantFinal = cantEfectiva; // 100% de la diferencia es pigmento
                        } else {
                            cantFinal = 0; // 0 resina
                        }
                    }
                }
                
                cantInput.value = cantFinal.toFixed(2);
            }
        });
    }

    function cargarComponentes() {
        const f = document.getElementById('formula_selector').value;
        const c = parseFloat(document.getElementById('cantidad_global').value || 0);
        let centro = '', molde = '';
        
        let url = `${urlApiFormula}?codigo_formula=${encodeURIComponent(f)}`;
        if (esInyectado) {
            centro = document.getElementById('centro_global').value;
            molde = document.getElementById('molde_global').value;
            if (!centro || !molde) return window.toast('Seleccione Inyectora y Molde.', 'warning');
            url += `&codigo_molde=${encodeURIComponent(molde)}`;
        } else if (esEnsamblado || esMolido || (typeof esMezclado !== 'undefined' && esMezclado) || document.getElementById('tipo_operacion')?.value === 'mezclado' || document.getElementById('centro_global')) {
            centro = document.getElementById('centro_global') ? document.getElementById('centro_global').value : '';
            if (!centro) return window.toast(`Seleccione ${esMolido ? 'Molino' : 'Centro de Trabajo'}.`, 'warning');
            const almacenConsumo = document.getElementById('codigo_almacen_consumo')?.value;
            if (almacenConsumo) {
                url += `&codigo_almacen=${encodeURIComponent(almacenConsumo)}`;
            }
        }
        if(!f || c <= 0) return window.toast('Seleccione Fórmula y especifique una cantidad mayor a 0.', 'warning');
        
        const trabajador = document.getElementById('trabajador_global').value;
        const fechaGlobal = document.getElementById('fecha_global') ? document.getElementById('fecha_global').value : '';
        const horaIni    = document.getElementById('hora_ini_global').value;
        const horaFin    = document.getElementById('hora_fin_global').value;
        
        const chkReciclado = document.getElementById('chk_usar_reciclado');
        const inputCantReciclado = document.getElementById('cant_reciclado_global');
        const usar_reciclado = chkReciclado && chkReciclado.checked ? 1 : 0;
        const extraCantReciclado = usar_reciclado ? (parseFloat(inputCantReciclado.value) || 0) : 0;

        // Calcular si hay REC previos
        let sumRec = 0;
        document.querySelectorAll('#tbody_items .nueva-fila').forEach(row => {
            const tipo = row.querySelector('.c-tipo');
            if (tipo && tipo.value === 'REC') {
                sumRec += parseFloat(row.querySelector('.c-cant').value) || 0;
            }
        });
        const cantEfectiva = Math.max(0, c - sumRec);

        fetch(url).then(r => r.json()).then(data => {
            if (data.success) {
                let pesoVirgin = 0;
                let pesoTotalFormula = 0;
                data.componentes.forEach(comp => {
                    pesoTotalFormula += parseFloat(comp.cantidad_nominal) || 0;
                    const desc = (comp.descripcion_producto || '').toUpperCase();
                    const esPigmento = desc.includes('COLOR') || desc.includes('MASTERBATCH') || desc.includes('PIGMENTO') || comp.codigo_tipo_producto === 'PIG';
                    if (!esPigmento) {
                        pesoVirgin += parseFloat(comp.cantidad_nominal) || 0;
                    }
                });

                // nuevaCantEfectiva represents remaining Virgen material overall
                let nuevaCantEfectiva = Math.max(0, cantEfectiva - extraCantReciclado);

                data.componentes.forEach(comp => {
                    const desc = (comp.descripcion_producto || '').toUpperCase();
                    const esPigmento = desc.includes('COLOR') || desc.includes('MASTERBATCH') || desc.includes('PIGMENTO') || comp.codigo_tipo_producto === 'PIG';
                    
                    let cant = 0;
                    let um = comp.codigo_unidad_medida;
                    
                    if (esEnsamblado || esMolido) {
                        if (pesoTotalFormula > 0) {
                            cant = nuevaCantEfectiva * (parseFloat(comp.cantidad_nominal) / pesoTotalFormula);
                        }
                        comp.codigo_unidad_medida = 'KG';
                    } else if (document.getElementById('tipo_operacion') && document.getElementById('tipo_operacion').value === 'mezclado') { 
                        // Es Mezclado
                        if (nuevaCantEfectiva <= 1) {
                            if (esPigmento) {
                                cant = nuevaCantEfectiva; // Usa la diferencia total (ej: 1kg completo)
                            } else {
                                cant = 0;
                            }
                        } else {
                            cant = nuevaCantEfectiva * parseFloat(comp.cantidad_nominal);
                        }
                        if (um === 'GR') {
                            cant = cant / 1000;
                            comp.codigo_unidad_medida = 'KG';
                        }
                    } else {
                        // Es Inyectado
                        cant = nuevaCantEfectiva * parseFloat(comp.cantidad_nominal);
                        if (um === 'GR') {
                            cant = cant / 1000;
                            comp.codigo_unidad_medida = 'KG';
                        }
                    }

                    if (cant > 0) {
                        // Forzar a mostrar 4 decimales para mayor precisión en KG
                        agregarFila({ ...comp, cantidad: cant.toFixed(4), formula: f, centro, molde,
                                     codigo_trabajador: trabajador, hora_ini: horaIni, hora_fin: horaFin, fecha: fechaGlobal });
                    }
                });
                
                if (extraCantReciclado > 0 && data.codigo_material_reciclado) {
                    agregarFila({ 
                        codigo_producto: data.codigo_material_reciclado,
                        descripcion_producto: data.descripcion_material_reciclado || 'MATERIAL RECICLADO / MEZCLADO',
                        codigo_tipo_producto: 'REC', 
                        descripcion_tipo_producto: 'RECICLADO',
                        cantidad: extraCantReciclado.toFixed(4), 
                        codigo_unidad_medida: 'KG',
                        formula: f, centro, molde,
                        codigo_trabajador: trabajador, hora_ini: horaIni, hora_fin: horaFin, fecha: fechaGlobal 
                    });
                }
                verificarStock();
            } else {
                window.toast(data.message, 'error');
            }
        }).catch(err => {
            window.toast('Error al comunicarse con el servidor.', 'error');
            console.error(err);
        });
    }

    function agregarFilaManual() { 
        const fechaGlobal = document.getElementById('fecha_global') ? document.getElementById('fecha_global').value : '';
        const horaIni = document.getElementById('hora_ini_global') ? document.getElementById('hora_ini_global').value : '';
        const horaFin = document.getElementById('hora_fin_global') ? document.getElementById('hora_fin_global').value : '';
        const trabajador = document.getElementById('trabajador_global') ? document.getElementById('trabajador_global').value : '';

        agregarFila({
            fecha: fechaGlobal,
            hora_ini: horaIni,
            hora_fin: horaFin,
            codigo_trabajador: trabajador,
            codigo_tipo_producto: 'ACT',
            es_manual: true,
            formula: ''
        }); 
    }

    function agregarFilaProductoResultante(codigo_inicial = '', desc_inicial = '') {
        const tbody = document.getElementById('tbody_resultantes');
        if(!tbody) return;
        const rowId = 'resultado_' + Date.now() + Math.floor(Math.random()*1000);
        
        let unitsHtml = unidadesData.map(u=>`<option value="${u.codigo}" ${u.codigo=='KG'?'selected':''}>${u.codigo}</option>`).join('');
        
        const displayVal = desc_inicial ? desc_inicial : (codigo_inicial ? codigo_inicial : '');
        
        let html = `<tr id="${rowId}" class="nueva-fila-resultado bg-white hover:bg-slate-50 transition-colors">
            <td class="px-2 py-2 align-middle relative">
                <input type="text" class="text-xs py-1.5 px-3 border border-slate-300 rounded focus:ring-primary focus:border-primary c-prod-search w-full min-w-[280px]" placeholder="Buscar Producto Resultante..." value="${displayVal}">
                <input type="hidden" class="c-prod" value="${codigo_inicial}">
                <div class="custom-options hidden absolute bg-white border border-slate-200 max-h-48 overflow-y-auto w-full min-w-[300px] z-50 shadow-xl rounded-md mt-1"></div>
            </td>
            
            <td class="px-2 py-2 align-middle">
                <input type="number" class="text-xs py-1.5 px-2 border border-slate-300 rounded focus:ring-primary focus:border-primary c-cant w-full min-w-[100px]" value="" step="0.01" placeholder="Ej. 100">
            </td>
            
            <td class="px-2 py-2 align-middle">
                <select class="text-xs py-1.5 px-2 border border-slate-300 rounded focus:ring-primary focus:border-primary w-full min-w-[80px] c-um">${unitsHtml}</select>
            </td>
            
            <td class="px-2 py-2 align-middle text-center">
                <button type="button" onclick="document.getElementById('${rowId}').remove()" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-1.5 rounded transition-colors" title="Eliminar fila">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>`;
        
        tbody.insertAdjacentHTML('afterbegin', html);
        setupSearchableDropdown(rowId);
    }

    function agregarFila(item = {}) {
        const tbody = document.getElementById('tbody_items');
        const rowId = 'row_' + Date.now() + Math.floor(Math.random()*1000);
        const today = item.fecha || new Date().toISOString().split('T')[0];
        
        let tiposHtml = tiposData.map(t=>`<option value="${t.codigo}" ${(item.codigo_tipo_producto ? item.codigo_tipo_producto==t.codigo : (item.es_manual ? t.codigo=='ACT' : false))?'selected':''}>${t.codigo}</option>`).join('');
        let centrosHtml = '<option value="">--</option>' + centros.map(c=>`<option value="${c.codigo}" ${item.centro==c.codigo?'selected':''}>${c.codigo}</option>`).join('');
        let moldesHtml = esInyectado ? `<select class="text-xs py-1.5 px-2 border border-slate-300 rounded focus:ring-primary focus:border-primary w-full min-w-[100px] c-molde">${moldesData.map(m=>`<option value="${m.codigo}" ${item.molde==m.codigo?'selected':''}>${m.codigo}</option>`).join('')}</select>` : '';
        let unitsHtml = unidadesData.map(u=>`<option value="${u.codigo}" ${u.codigo=='KG'?'selected':''}>${u.codigo}</option>`).join('');
        let trabsHtml = '<option value="">--</option>' + trabajadores.map(t=>
            `<option value="${t.codigo}" ${item.codigo_trabajador==t.codigo?'selected':''}>${t.nombre}</option>`
        ).join('');

        const formulaVal = (item.es_manual || item.codigo_tipo_producto === 'ACT') ? '' : (item.formula || '');

        let html = `<tr id="${rowId}" class="nueva-fila bg-white hover:bg-slate-50 transition-colors">
            <td class="px-2 py-2 align-middle">
                <select class="text-xs py-1.5 px-2 border border-slate-300 rounded focus:ring-primary focus:border-primary w-full min-w-[80px] c-tipo" onchange="recalcularFormulaDesdeREC(); if(this.value==='ACT'){ const r=this.closest('tr'); if(r){ const f=r.querySelector('.c-formula'); if(f) f.value=''; } } verificarStock();">${tiposHtml}</select>
            </td>
            
            <td class="px-2 py-2 align-middle relative">
                <input type="text" class="text-xs py-1.5 px-3 border border-slate-300 rounded focus:ring-primary focus:border-primary c-prod-search w-full min-w-[200px]" placeholder="Buscar Material/Actividad..." value="${item.descripcion_producto ? item.descripcion_producto : (item.codigo_producto ? item.codigo_producto : '')}">
                <input type="hidden" class="c-prod" value="${item.codigo_producto||''}">
                <div class="custom-options hidden absolute bg-white border border-slate-200 max-h-48 overflow-y-auto w-full min-w-[300px] z-50 shadow-xl rounded-md mt-1"></div>
            </td>
            
            <td class="px-2 py-2 align-middle">
                <select class="text-xs py-1.5 px-2 border border-slate-300 rounded focus:ring-primary focus:border-primary w-full min-w-[90px] c-centro">${centrosHtml}</select>
            </td>
            
            ${moldesHtml ? `<td class="px-2 py-2 align-middle">${moldesHtml}</td>` : ''}
            
            <td class="px-2 py-2 align-middle">
                <input type="number" class="text-xs py-1.5 px-2 border border-slate-300 rounded focus:ring-primary focus:border-primary c-cant w-full min-w-[80px]" value="${item.cantidad||''}" step="0.01" oninput="recalcularFormulaDesdeREC(); verificarStock();">
                <span class="stock-cell hidden"></span>
            </td>
            
            <td class="px-2 py-2 align-middle">
                <select class="text-xs py-1.5 px-2 border border-slate-300 rounded focus:ring-primary focus:border-primary w-full min-w-[70px] c-um">${unitsHtml}</select>
            </td>
            
            <td class="px-2 py-2 align-middle">
                <select class="text-xs py-1.5 px-2 border border-slate-300 rounded focus:ring-primary focus:border-primary w-full min-w-[160px] c-trab">${trabsHtml}</select>
            </td>

            <td class="px-2 py-2 align-middle">
                <input type="date" class="text-xs py-1.5 px-2 border border-slate-300 rounded focus:ring-primary focus:border-primary c-fecha_ini w-[110px]" value="${today}" oninput="syncFechaMaq(this)">
            </td>
            
            <td style="display:none;">
                <input type="hidden" class="c-formula" value="${formulaVal}">
                <input type="hidden" class="c-nominal" value="${item.cantidad_nominal||''}">
                <input type="date" class="c-fecha_fin" value="${today}" oninput="syncFechaMaq(this)">
                <input type="date" class="c-fecha_ini_maq" value="${today}"><input type="date" class="c-fecha_fin_maq" value="${today}">
            </td>
            
            <td class="px-2 py-2 align-middle">
                <div class="flex items-center space-x-1.5 w-max">
                    <input type="time" class="text-xs py-1.5 px-2 border border-slate-300 rounded focus:ring-primary focus:border-primary c-hora_ini w-[90px]" value="${item.hora_ini||'08:00'}" oninput="syncHoraMaq(this)">
                    <span class="text-slate-400 font-bold">-</span>
                    <input type="time" class="text-xs py-1.5 px-2 border border-slate-300 rounded focus:ring-primary focus:border-primary c-hora_fin w-[90px]" value="${item.hora_fin||'17:00'}" oninput="syncHoraMaq(this)">
                </div>
            </td>

            <td class="px-2 py-2 align-middle">
                <div class="flex items-center space-x-1.5 w-max">
                    <input type="time" class="text-xs py-1.5 px-2 border border-slate-300 rounded focus:ring-primary focus:border-primary c-hora_ini_maq w-[90px]" value="${item.hora_ini||'08:00'}">
                    <span class="text-slate-400 font-bold">-</span>
                    <input type="time" class="text-xs py-1.5 px-2 border border-slate-300 rounded focus:ring-primary focus:border-primary c-hora_fin_maq w-[90px]" value="${item.hora_fin||'17:00'}">
                </div>
            </td>
            
            <td class="px-2 py-2 align-middle">
                  <input type="text" class="text-xs py-1.5 px-2 border border-slate-300 rounded focus:ring-primary focus:border-primary c-obs w-full min-w-[150px]" placeholder="Observación...">
              </td>
            
            <td class="px-2 py-2 align-middle text-center">
                <button type="button" onclick="const r = document.getElementById('${rowId}'); r.remove(); recalcularFormulaDesdeREC(); verificarStock();" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-1.5 rounded transition-colors" title="Eliminar fila">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>`;
        
        tbody.insertAdjacentHTML('afterbegin', html);
        setupSearchableDropdown(rowId);
    }

    function syncHoraMaq(el) {
        const row = el.closest('tr');
        if(el.classList.contains('c-hora_ini')) row.querySelector('.c-hora_ini_maq').value = el.value;
        if(el.classList.contains('c-hora_fin')) row.querySelector('.c-hora_fin_maq').value = el.value;
    }

    function syncFechaMaq(el) {
        const row = el.closest('tr');
        if(el.classList.contains('c-fecha_ini')) row.querySelector('.c-fecha_ini_maq').value = el.value;
        if(el.classList.contains('c-fecha_fin')) row.querySelector('.c-fecha_fin_maq').value = el.value;
    }

    function enviarGuardado() {
        const almacenSelect = document.getElementById('codigo_almacen_consumo');
        if (!almacenSelect.value) {
            return window.toast("Por favor seleccione el Almacén de Consumo antes de guardar.", 'warning');
        }
        document.getElementById('codigo_almacen_consumo_hidden').value = almacenSelect.value;

        const filas = document.querySelectorAll('.nueva-fila');
        if (filas.length === 0) return window.toast("No hay datos nuevos para guardar.", 'warning');
        
        let data = [];
        let error = false;
        let sumCantidad = 0;
        
        filas.forEach(r => {
            const tipo = r.querySelector('.c-tipo').value;
            const prod = r.querySelector('.c-prod').value;
            const cant = parseFloat(r.querySelector('.c-cant').value) || 0;
            if (!prod) error = true;
            if (tipo !== 'ACT' && cant <= 0) error = true;
            if (tipo !== 'ACT') {
                sumCantidad += cant;
            }
            
            data.push({
                codigo_tipo_producto: tipo,
                codigo_producto: prod,
                codigo_centro_trabajo: r.querySelector('.c-centro').value,
                codigo_molde: r.querySelector('.c-molde')?.value || null,
                cantidad: cant,
                codigo_unidad_medida: r.querySelector('.c-um').value,
                codigo_color: null,
                codigo_formula: (tipo === 'ACT') ? null : (r.querySelector('.c-formula').value || null),
                codigo_trabajador: r.querySelector('.c-trab').value,
                fecha_inicio: r.querySelector('.c-fecha_ini').value,
                hora_inicio: r.querySelector('.c-hora_ini').value,
                fecha_fin: r.querySelector('.c-fecha_fin').value,
                hora_fin: r.querySelector('.c-hora_fin').value,
                fecha_inicio_maquina: r.querySelector('.c-fecha_ini_maq').value,
                hora_inicio_maquina: r.querySelector('.c-hora_ini_maq').value,
                fecha_fin_maquina: r.querySelector('.c-fecha_fin_maq').value,
                hora_fin_maquina: r.querySelector('.c-hora_fin_maq').value,
                observacion: r.querySelector('.c-obs') ? r.querySelector('.c-obs').value : null
            });
        });
        
        if (error) return window.toast("Por favor complete los campos requeridos en todas las filas (cantidad es obligatoria excepto para Actividades).", 'warning');

        if (esTroquelado && pesoNeto > 0 && (pesoConsumido + sumCantidad) > (pesoNeto + 0.001)) {
            const saldoActual = Math.max(0, pesoNeto - pesoConsumido);
            Swal.fire({
                icon: 'error',
                title: 'Saldo de Rollo Insuficiente',
                html: `El consumo total (${(pesoConsumido + sumCantidad).toFixed(2)} KG) excede el Peso Neto del Rollo (${pesoNeto.toFixed(2)} KG).<br><br>` +
                      `<b>Saldo Disponible:</b> <span class="text-emerald-600 font-bold">${saldoActual.toFixed(2)} KG</span>`
            });
            return;
        }
        
        // Serializar productos resultantes
        const resultados = document.querySelectorAll('.nueva-fila-resultado');
        let resultadosData = [];
        let resultadosError = false;
        let sumaConsumoKG = 0;
        let sumaResultanteKG = 0;

        // Sumar consumos en KG solo de materiales (excluyendo actividades)
        data.forEach(item => {
            if (item.codigo_tipo_producto !== 'ACT' && item.codigo_unidad_medida === 'KG') {
                sumaConsumoKG += parseFloat(item.cantidad) || 0;
            }
        });
        
        resultados.forEach(r => {
            const prod = r.querySelector('.c-prod').value;
            const cant = r.querySelector('.c-cant').value;
            const um = r.querySelector('.c-um').value;
            
            if (!prod || !cant) resultadosError = true;
            if (um === 'KG') {
                sumaResultanteKG += parseFloat(cant) || 0;
            }
            
            resultadosData.push({
                codigo_producto: prod,
                cantidad: cant,
                codigo_unidad_medida: um
            });
        });
        
        if (resultadosError) return window.toast("Por favor seleccione un producto y especifique una cantidad en todas las filas de productos resultantes.", 'warning');
        
        if (sumaResultanteKG > sumaConsumoKG && sumaConsumoKG > 0) {
            if (!confirm(`La cantidad de productos resultantes en KG (${sumaResultanteKG} KG) es mayor a la cantidad de materiales consumidos en KG (${sumaConsumoKG} KG).\n\n¿Está seguro de que desea guardar con estos valores?`)) {
                return;
            }
        }

        document.getElementById('componentes_json').value = JSON.stringify(data);
        document.getElementById('productos_resultantes_json').value = JSON.stringify(resultadosData);
        document.getElementById('form_masivo').submit();
    }

    function eliminarRegistrado(id) {
        if (confirm('¿Está seguro de anular este registro?')) {
            const form = document.getElementById('form_delete');
            form.action = `${urlDeleteBase}/${id}`;
            form.submit();
        }
    }

    function editarRegistrado(id) {
        document.getElementById('row_display_' + id).classList.add('hidden');
        document.getElementById('row_edit_' + id).classList.remove('hidden');
    }

    function cancelarEdicion(id) {
        document.getElementById('row_edit_' + id).classList.add('hidden');
        document.getElementById('row_display_' + id).classList.remove('hidden');
    }
    
    function confirmarCierre() { 
        if(confirm('¿Finalizar proceso? Ya no podrá agregar más materiales y cambiará a estado COMPLETADO.')) {
            document.getElementById('form_finalizar').submit(); 
        }
    }

    function toggleDetallesCarga(index) {
        const rows = document.querySelectorAll('.detalle-carga-' + index);
        rows.forEach(r => r.classList.toggle('hidden'));
    }

    function switchOpTab(tabName) {
        document.getElementById('tipo_operacion').value = tabName;
        const tabs = ['inyectado', 'ensamblado', 'encartonado', 'merma_pura', 'recuperado_molido', 'purga', 'limpieza', 'recuperado_maquina', 'merma_clip', 'recuperado_clip', 'merma_cascara', 'merma_carton', 'merma_empaque'];
        tabs.forEach(t => {
            const el = document.getElementById('tab-' + t);
            if (el) {
                if (t === tabName) {
                    el.className = 'inline-block p-4 text-blue-600 bg-white border-t border-l border-r border-slate-200 rounded-t-lg active';
                } else {
                    el.className = 'inline-block p-4 border-b-0 hover:text-slate-600 hover:bg-slate-50 text-slate-500';
                }
            }
        });

        const wrapFormula = document.getElementById('wrapper_formula');
        const wrapClip = document.getElementById('wrapper_inventario_clip');
        const wrapCascara = document.getElementById('wrapper_inventario_cascara');
        const wrapCarton = document.getElementById('wrapper_inventario_carton');
        const panelClipRecuperado = document.getElementById('panel_clip_recuperado');
        const panelColores = document.getElementById('panel_colores_encartonado');
        
        if (wrapFormula) wrapFormula.classList.add('hidden');
        if (wrapClip) wrapClip.classList.add('hidden');
        if (wrapCascara) wrapCascara.classList.add('hidden');
        if (wrapCarton) wrapCarton.classList.add('hidden');
        if (panelClipRecuperado) panelClipRecuperado.classList.add('hidden');
        if (panelColores) panelColores.classList.add('hidden');

        const btn = document.getElementById('btn_cargar');
        const lblFormula = document.getElementById('lbl_formula');
        
        if (tabName === 'inyectado') {
            if (wrapFormula) wrapFormula.classList.remove('hidden');
            btn.innerHTML = `<i class="fas fa-box-open mr-2"></i>Cargar ${esTroquelado ? 'Troquelado' : 'Inyectado'}`;
            btn.className = 'px-5 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-md shadow-sm transition';
        } else if (tabName === 'ensamblado') {
            if (wrapFormula) wrapFormula.classList.remove('hidden');
            if (panelClipRecuperado) panelClipRecuperado.classList.remove('hidden');
            btn.innerHTML = `<i class="fas fa-box-open mr-2"></i>Cargar ${typeof esEnsambladoManual !== 'undefined' && esEnsambladoManual ? 'Ensamblado Manual' : 'Ensamblado'}`;
            btn.className = 'px-5 py-2 bg-primary hover:bg-primary-dark text-white font-medium rounded-md shadow-sm transition';
        } else if (tabName === 'encartonado') {
            if (wrapFormula) wrapFormula.classList.remove('hidden');
            if (panelColores) panelColores.classList.remove('hidden');
            btn.innerHTML = '<i class="fas fa-box-open mr-2"></i>Cargar Encartonado';
            btn.className = 'px-5 py-2 bg-primary hover:bg-primary-dark text-white font-medium rounded-md shadow-sm transition';
            if (lblFormula) lblFormula.innerText = 'Fórmula Encartonado';
        } else if (tabName === 'merma_empaque') {
            if (wrapFormula) wrapFormula.classList.remove('hidden');
            btn.innerHTML = '<i class="fas fa-box mr-2"></i>Registrar Merma Empaque';
            btn.className = 'px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-md shadow-sm transition';
            if (lblFormula) lblFormula.innerText = 'Fórmula / Producto';
        } else if (tabName === 'merma_clip') {
            if (esTroquelado) {
                if (wrapFormula) wrapFormula.classList.remove('hidden');
                btn.innerHTML = '<i class="fas fa-cut mr-2"></i>Registrar Merma Clip';
                btn.className = 'px-5 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-md shadow-sm transition';
                if (lblFormula) lblFormula.innerText = 'Fórmula / Producto';
            } else {
                if (wrapClip) wrapClip.classList.remove('hidden');
                btn.innerHTML = '<i class="fas fa-trash-alt mr-2"></i>Registrar Merma Clip';
                btn.className = 'px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-md shadow-sm transition';
            }
        } else if (tabName === 'recuperado_clip') {
            if (wrapClip) wrapClip.classList.remove('hidden');
            btn.innerHTML = '<i class="fas fa-recycle mr-2"></i>Registrar Clip Recuperado';
            btn.className = 'px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md shadow-sm transition';
        } else if (tabName === 'merma_cascara') {
            if (wrapCascara) wrapCascara.classList.remove('hidden');
            btn.innerHTML = '<i class="fas fa-recycle mr-2"></i>Registrar Cáscara para molido';
            btn.className = 'px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-md shadow-sm transition';
            if (lblFormula) lblFormula.innerText = 'Color de Cáscara Original';
        } else if (tabName === 'merma_carton') {
            if (wrapCarton) wrapCarton.classList.remove('hidden');
            btn.innerHTML = '<i class="fas fa-layer-group mr-2"></i>Registrar Merma Cartón';
            btn.className = 'px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-md shadow-sm transition';
        } else if (tabName === 'recuperado_molido') {
            if (wrapFormula) wrapFormula.classList.remove('hidden');
            btn.innerHTML = '<i class="fas fa-recycle mr-2"></i>Registrar Colada / Cáscara';
            btn.className = 'px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-md shadow-sm transition';
            if (lblFormula) lblFormula.innerText = 'Fórmula Original';
        } else if (tabName === 'purga') {
            if (wrapFormula) wrapFormula.classList.remove('hidden');
            btn.innerHTML = '<i class="fas fa-trash-alt mr-2"></i>Registrar Purga';
            btn.className = 'px-5 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-medium rounded-md shadow-sm transition';
            if (lblFormula) lblFormula.innerText = 'Color Purgado';
        } else if (tabName === 'limpieza') {
            if (wrapFormula) wrapFormula.classList.remove('hidden');
            btn.innerHTML = '<i class="fas fa-broom mr-2"></i>Registrar Limpieza';
            btn.className = 'px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white font-medium rounded-md shadow-sm transition';
            if (lblFormula) lblFormula.innerText = 'Color de Limpieza';
        } else if (tabName === 'recuperado_maquina') {
            if (wrapFormula) wrapFormula.classList.remove('hidden');
            btn.innerHTML = '<i class="fas fa-cogs mr-2"></i>Reg. Rec. Máquina';
            btn.className = 'px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-md shadow-sm transition';
            if (lblFormula) lblFormula.innerText = 'Fórmula Original';
        }
    }

    function setupTrabajadorPredictivo() {
        const input = document.getElementById('trabajador_search_input');
        const hidden = document.getElementById('trabajador_global');
        const options = document.getElementById('trabajador_options');
        const btnClear = document.getElementById('btn_clear_trabajador');
        const trabajadoresList = @json($trabajadores);

        if (!input || !options) return;

        function renderOptions(filter = '') {
            const query = filter.toLowerCase().trim();
            const matches = trabajadoresList.filter(t => 
                (t.nombre && t.nombre.toLowerCase().includes(query)) || 
                (t.codigo && t.codigo.toLowerCase().includes(query))
            );

            options.innerHTML = '';
            if (matches.length === 0) {
                options.innerHTML = '<div class="p-2 text-xs text-slate-400">No se encontraron trabajadores</div>';
            } else {
                matches.forEach(t => {
                    const div = document.createElement('div');
                    div.className = 'p-2 cursor-pointer border-b border-slate-100 text-xs hover:bg-primary-50 text-slate-700 transition flex justify-between items-center';
                    div.innerHTML = `<span class="font-medium">${t.nombre}</span><span class="text-[10px] text-slate-400 ml-2 font-mono">${t.codigo}</span>`;
                    div.onclick = () => {
                        input.value = t.nombre;
                        hidden.value = t.codigo;
                        options.classList.add('hidden');
                        if (btnClear) btnClear.classList.remove('hidden');
                    };
                    options.appendChild(div);
                });
            }
            options.classList.remove('hidden');
        }

        input.addEventListener('focus', () => renderOptions(input.value));
        input.addEventListener('input', () => {
            hidden.value = '';
            if (btnClear) {
                if (input.value) btnClear.classList.remove('hidden');
                else btnClear.classList.add('hidden');
            }
            renderOptions(input.value);
        });

        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !options.contains(e.target)) {
                options.classList.add('hidden');
            }
        });
    }

    function limpiarTrabajadorGlobal() {
        const input = document.getElementById('trabajador_search_input');
        const hidden = document.getElementById('trabajador_global');
        const btnClear = document.getElementById('btn_clear_trabajador');
        if (input) input.value = '';
        if (hidden) hidden.value = '';
        if (btnClear) btnClear.classList.add('hidden');
    }

    function cargarEjecucionAgrupada() {
        const inputTipo = document.getElementById('tipo_operacion');
        if (!inputTipo || !inputTipo.value) {
            cargarComponentes();
            return;
        }
        
        const tipo_operacion = inputTipo.value;
        let operacion_para_backend = tipo_operacion;
        let destino_merma_clip = 'desecho';

        let formula = document.getElementById('formula_selector').value;
        
        if (tipo_operacion === 'encartonado') {
            if (selectedColorsEncartonado.length !== 4) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Selección de Colores Incompleta',
                    text: 'Debe seleccionar exactamente 4 colores para el proceso de encartonado (Actualmente seleccionados: ' + selectedColorsEncartonado.length + ').'
                });
                return;
            }
            formula = document.getElementById('formula_selector').value;
        } else if (tipo_operacion === 'merma_clip') {
            if (esTroquelado) {
                formula = document.getElementById('formula_selector').value;
            } else {
                formula = document.getElementById('inventario_selector_clip').value;
            }
            destino_merma_clip = 'desecho';
        } else if (tipo_operacion === 'merma_empaque') {
            formula = document.getElementById('formula_selector').value;
        } else if (tipo_operacion === 'recuperado_clip') {
            formula = document.getElementById('inventario_selector_clip').value;
            operacion_para_backend = 'recuperado_clip';
            destino_merma_clip = 'recuperado';
        } else if (tipo_operacion === 'merma_cascara') {
            formula = document.getElementById('cascara_selector').value;
        } else if (tipo_operacion === 'merma_carton') {
            formula = document.getElementById('carton_selector').value;
        }

        const cantidad = document.getElementById('cantidad_global').value;
        const molde = document.getElementById('molde_global') ? document.getElementById('molde_global').value : '';
        const centro = document.getElementById('centro_global') ? document.getElementById('centro_global').value : '';
        const trabajador = document.getElementById('trabajador_global').value;
        const fecha = document.getElementById('fecha_global').value;
        const hora_ini = document.getElementById('hora_ini_global').value;
        const hora_fin = document.getElementById('hora_fin_global').value;
        const almacen = document.getElementById('codigo_almacen_consumo') ? document.getElementById('codigo_almacen_consumo').value : '';
        const color = '';

        const inyectada_inicial = document.getElementById('inyectada_inicial_global') ? document.getElementById('inyectada_inicial_global').value : null;
        const inyectada_final = document.getElementById('inyectada_final_global') ? document.getElementById('inyectada_final_global').value : null;
        const tiempo_ciclo = document.getElementById('tiempo_ciclo_global') ? document.getElementById('tiempo_ciclo_global').value : null;

        if (!formula || cantidad === '' || cantidad === null || !trabajador || !centro) {
            Swal.fire('Atención', 'Seleccione fórmula, centro, trabajador e ingrese la cantidad.', 'warning');
            return;
        }

        const cantIngresada = parseFloat(cantidad) || 0;
        if (cantIngresada < 0) {
            Swal.fire('Atención', 'La cantidad no puede ser un valor negativo.', 'warning');
            return;
        }

        if (esTroquelado && pesoNeto > 0 && (pesoConsumido + cantIngresada) > (pesoNeto + 0.001)) {
            const saldoActual = Math.max(0, pesoNeto - pesoConsumido);
            Swal.fire({
                icon: 'error',
                title: 'Saldo de Rollo Insuficiente',
                html: `El consumo ingresado (<b>${cantIngresada.toFixed(2)} KG</b>) excede el saldo disponible del rollo.<br><br>` +
                      `<b>Peso Neto Total:</b> ${pesoNeto.toFixed(2)} KG<br>` +
                      `<b>Consumido Actual:</b> ${pesoConsumido.toFixed(2)} KG<br>` +
                      `<b>Saldo Disponible:</b> <span class="text-emerald-600 font-bold">${saldoActual.toFixed(2)} KG</span><br><br>` +
                      `No se puede registrar un consumo que supere el peso neto del rollo.`
            });
            return;
        }

        const chkReciclado = document.getElementById('chk_usar_reciclado');
        const inputCantReciclado = document.getElementById('cant_reciclado_global');
        const usar_reciclado = chkReciclado && chkReciclado.checked ? 1 : 0;
        const cantidad_reciclado = usar_reciclado ? (parseFloat(inputCantReciclado.value) || 0) : 0;

        const chkClipRecuperado = document.getElementById('chk_usar_clip_recuperado');
        const usar_clip_recuperado = chkClipRecuperado && chkClipRecuperado.checked ? 1 : 0;

        const payload = {
            _token: '{{ csrf_token() }}',
            tipo_operacion: operacion_para_backend,
            codigo_formula: formula,
            colores_seleccionados: selectedColorsEncartonado,
            cantidad_total: cantidad,
            codigo_molde: molde,
            codigo_centro_trabajo: centro,
            codigo_trabajador: trabajador,
            fecha: fecha,
            hora_inicio: hora_ini,
            hora_fin: hora_fin,
            codigo_almacen_consumo: almacen,
            codigo_color: color,
            usar_reciclado: usar_reciclado,
            cantidad_reciclado: cantidad_reciclado,
            inyectada_inicial: inyectada_inicial,
            inyectada_final: inyectada_final,
            tiempo_ciclo: tiempo_ciclo
        };

        function ejecutarEnvio() {
            guardarEstadoCabecera();

            Swal.fire({
                title: 'Registrando...',
                text: 'Validando stock y registrando transacción',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch(`{{ route('ordenes.procesos.ejecucion_agrupada.store', [$orden->idop, $proceso->id]) }}`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    guardarEstadoCabecera();
                    Swal.fire('Éxito', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'Error al guardar.', 'error');
                }
            })
            .catch(e => {
                console.error(e);
                Swal.fire('Error', 'Ocurrió un error en el servidor.', 'error');
            });
        }

        if (cantIngresada === 0) {
            const nombreOp = tipo_operacion.toUpperCase().replace('_', ' ');
            const inyInfo = (inyectada_inicial && inyectada_final) 
                ? `<div class="mt-1.5">• <b>Inyectadas Máquina:</b> ${inyectada_inicial} a ${inyectada_final} (${Math.max(0, parseInt(inyectada_final) - parseInt(inyectada_inicial))} inyectadas)</div>` 
                : '';
            const horasInfo = (hora_ini && hora_fin) 
                ? `<div class="mt-1.5">• <b>Horario:</b> ${hora_ini} - ${hora_fin}</div>` 
                : '';

            Swal.fire({
                title: '¿Registrar con Cantidad 0 KG?',
                html: `<div class="text-left text-sm text-slate-700 mb-2">` +
                      `Ha ingresado <b>0.00 KG</b> de insumos para la operación de <b class="text-primary">${nombreOp}</b>.` +
                      `</div>` +
                      `<div class="text-left bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-900">` +
                      `<b><i class="fas fa-info-circle mr-1 text-amber-600"></i>¿Qué registrará el sistema?</b>` +
                      `${horasInfo}` +
                      `${inyInfo}` +
                      `<div class="mt-1.5">• <b>Inventario / Kardex:</b> <u>No se descontará</u> material de ningún almacén.</div>` +
                      `</div><br>` +
                      `<span class="text-xs text-slate-500">¿Desea confirmar el registro o prefiere corregir la cantidad?</span>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d9488',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fas fa-check mr-1.5"></i> Sí, registrar con 0 KG',
                cancelButtonText: '<i class="fas fa-undo-alt mr-1.5"></i> Cancelar / Modificar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    ejecutarEnvio();
                }
            });
            return;
        }

        ejecutarEnvio();
    }

    // --- Pagination Logic ---
    let currentPage = 1;
    const itemsPerPage = 10;

    function updatePagination() {
        const tbody = document.getElementById('tbody_items');
        if(!tbody) return;
        
        const mainRows = Array.from(tbody.querySelectorAll('.main-row'));
        const totalItems = mainRows.length;
        const totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
        
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIdx = (currentPage - 1) * itemsPerPage;
        const endIdx = startIdx + itemsPerPage;

        // Hide all rows (including details and edits)
        Array.from(tbody.children).forEach(row => {
            if(!row.classList.contains('main-row')) {
                // If it's a detail or edit row, hide it when page changes
                if(!row.classList.contains('hidden') && row.id.includes('row_edit')) {
                    cancelarEdicion(row.id.replace('row_edit_', ''));
                }
                row.classList.add('hidden');
            }
        });

        mainRows.forEach((row, index) => {
            if (index >= startIdx && index < endIdx) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });

        // Update info text
        document.getElementById('page_total_info').textContent = totalItems;
        document.getElementById('page_start_info').textContent = totalItems > 0 ? startIdx + 1 : 0;
        document.getElementById('page_end_info').textContent = Math.min(endIdx, totalItems);

        // Render buttons
        const paginationContainer = document.getElementById('pagination_buttons');
        let html = '';
        
        html += `<button type="button" onclick="goToPage(${currentPage - 1})" class="px-3 py-1 border border-slate-300 rounded-md bg-white text-slate-500 hover:bg-slate-50 disabled:opacity-50" ${currentPage === 1 ? 'disabled' : ''}>Anterior</button>`;
        
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                const isActive = i === currentPage ? 'bg-primary text-white border-primary' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50';
                html += `<button type="button" onclick="goToPage(${i})" class="px-3 py-1 border rounded-md ${isActive}">${i}</button>`;
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                html += `<span class="px-2 py-1 text-slate-500">...</span>`;
            }
        }
        
        html += `<button type="button" onclick="goToPage(${currentPage + 1})" class="px-3 py-1 border border-slate-300 rounded-md bg-white text-slate-500 hover:bg-slate-50 disabled:opacity-50" ${currentPage === totalPages ? 'disabled' : ''}>Siguiente</button>`;

        paginationContainer.innerHTML = html;
    }

    function goToPage(page) {
        currentPage = page;
        updatePagination();
    }

    document.addEventListener('DOMContentLoaded', () => {
        updatePagination();
    });
</script>
@endsection
