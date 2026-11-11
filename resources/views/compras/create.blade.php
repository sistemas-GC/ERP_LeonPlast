@extends('layouts.app')
@section('title', 'Registrar Nueva Compra')

@section('content')
<link href="/vendor/select2/select2.min.css" rel="stylesheet" />
<div class="container mx-auto px-2 sm:px-4 py-6 max-w-[1650px]">

    <x-page-header title="Registrar Nueva Compra" subtitle="Complete los datos del comprobante y asigne el almacén por cada insumo.">
        <x-slot:actions>
            <a href="{{ route('compras.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver al Listado
            </a>
        </x-slot:actions>
    </x-page-header>

    <form action="{{ route('compras.store') }}" method="POST" id="formCompra">
        @csrf

        @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg shadow-sm">
            <div class="flex items-start">
                <div class="shrink-0">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-bold text-red-800">No se pudo registrar la compra debido a los siguientes errores:</h3>
                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 xl:gap-6">

            <div class="lg:col-span-9 xl:col-span-9 space-y-6">
                <!-- SECCIÓN 1: DATOS DEL COMPROBANTE -->
                <div class="bg-white rounded-xl shadow-md border border-slate-200/80 p-5 md:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3.5 mb-5">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-file-invoice text-emerald-600 text-base"></i>
                            <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide">1. Datos del Comprobante</h2>
                        </div>
                        
                        <!-- Selector de Guía de Remisión -->
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">¿Vincular Guía?</label>
                            <div class="w-64 sm:w-80">
                                <select name="ids_guias[]" id="selectGuia" multiple="multiple">
                                    @foreach($guiasPendientes as $guia)
                                        <option value="{{ $guia->id_guia }}" data-ruc="{{ $guia->ruc_proveedor }}">
                                            Guía {{ $guia->numero_guia }} - {{ Str::limit($guia->proveedor, 20) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Tipo Doc. <span class="text-red-500">*</span></label>
                                <select name="tipo_documento" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" required>
                                    @foreach($tipos_comprobante as $tc)
                                        <option value="{{ $tc->valor }}">{{ $tc->descripcion }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Serie <span class="text-red-500">*</span></label>
                                <input type="text" name="serie_documento" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-800 uppercase focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" placeholder="F001" required>
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">N° Documento <span class="text-red-500">*</span></label>
                                <input type="text" name="numero_documento" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-800 font-mono focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" placeholder="00000001" required>
                            </div>

                            <div class="md:col-span-3">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Fecha Emisión <span class="text-red-500">*</span></label>
                                <input type="date" name="fecha_compra" value="{{ date('Y-m-d') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" required max="{{ date('Y-m-d') }}">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Moneda <span class="text-red-500">*</span></label>
                                <select name="moneda" id="selectMoneda" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" required>
                                    <option value="PEN">Soles (PEN)</option>
                                    <option value="USD">Dólares (USD)</option>
                                </select>
                            </div>

                            <div class="md:col-span-6">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Proveedor <span class="text-red-500">*</span></label>
                                <div class="flex gap-2">
                                    <select name="ruc_proveedor" id="selectProveedor" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" required>
                                        <option value="">Seleccione Proveedor...</option>
                                        @foreach($proveedores as $p)
                                            <option value="{{ $p->ruc }}">{{ $p->ruc }} - {{ $p->razon_social }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" onclick="abrirModal('modalProveedor')" class="bg-slate-800 text-white px-3.5 rounded-lg hover:bg-slate-700 transition shadow-xs text-xs flex items-center justify-center shrink-0" title="Registrar Nuevo Proveedor">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="md:col-span-3">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Condición Pago <span class="text-red-500">*</span></label>
                                <select name="condicion_pago" id="selectCondicionPago" class="w-full rounded-lg border border-slate-300 px-3 py-2 font-semibold text-xs text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" required>
                                    <option value="CONTADO">Contado</option>
                                    <option value="CREDITO">Crédito</option>
                                </select>
                            </div>

                            <div class="md:col-span-3" id="groupFechaVencimiento">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Fecha Vencimiento</label>
                                <input type="date" name="fecha_vencimiento" id="inputFechaVencimiento" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" value="{{ date('Y-m-d') }}">
                            </div>

                            <div class="md:col-span-3" id="groupTipoCambio" style="display: none;">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Tipo de Cambio</label>
                                <input type="number" name="tipo_cambio" id="inputTipoCambio" step="0.001" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" placeholder="Ej. 3.800" value="1.000">
                            </div>
                        </div>

                        <!-- Sección de Cuotas al Crédito -->
                        <div id="seccionCuotasCredito" class="mt-6 pt-5 border-t border-slate-200" style="display: none;">
                            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3 mb-3">
                                <div>
                                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                        <i class="fas fa-calendar-alt text-amber-500"></i> Cuotas de la Factura (Crédito)
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Ingrese las cuotas con su fecha de vencimiento y monto acordado.</p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <!-- Controles Rápidos de Generación Automática (Inline) -->
                                    <div class="flex items-center gap-1.5 bg-amber-50/80 border border-amber-200/90 px-2.5 py-1 rounded-lg shadow-2xs">
                                        <label for="quickNumCuotas" class="text-[11px] font-bold text-amber-900 uppercase">Cuotas:</label>
                                        <input type="number" id="quickNumCuotas" min="1" max="48" value="2" class="w-14 text-center rounded-md border border-amber-300 bg-white py-1 text-xs font-bold text-slate-800 focus:ring-1 focus:ring-amber-500 shadow-2xs" title="Número de cuotas">
                                        
                                        <label for="quickDiasIntervalo" class="text-[11px] font-bold text-amber-900 uppercase ml-1">Días:</label>
                                        <input type="number" id="quickDiasIntervalo" min="1" max="365" value="30" class="w-16 text-center rounded-md border border-amber-300 bg-white py-1 text-xs font-bold text-slate-800 focus:ring-1 focus:ring-amber-500 shadow-2xs" title="Cantidad de días entre cada cuota">
                                        
                                        <button type="button" id="btnProcesarCuotasQuick" class="inline-flex items-center gap-1 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold py-1 px-3 rounded-md shadow-2xs transition active:scale-95" title="Generar cuotas automáticamente">
                                            <i class="fas fa-cogs"></i>
                                            <span>Procesar</span>
                                        </button>
                                    </div>

                                    <button type="button" id="btnAbrirModalCuotas" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold hover:bg-indigo-100 transition shadow-2xs" title="Abrir asistente detallado">
                                        <i class="fas fa-calculator"></i>
                                        <span>Asistente</span>
                                    </button>

                                    <button type="button" id="btnAgregarCuota" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white text-slate-700 border border-slate-300 rounded-lg text-xs font-bold hover:bg-slate-50 transition shadow-2xs">
                                        <i class="fas fa-plus text-amber-500"></i>
                                        <span>Agregar Cuota</span>
                                    </button>
                                </div>
                            </div>

                            <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-xs">
                                <table class="w-full text-xs text-left" id="tablaCuotas">
                                    <thead class="bg-slate-50 text-slate-600 font-bold uppercase border-b border-slate-200 text-[11px]">
                                        <tr>
                                            <th class="py-2.5 px-3 w-16 text-center">N°</th>
                                            <th class="py-2.5 px-3">Fecha de Vencimiento <span class="text-red-500">*</span></th>
                                            <th class="py-2.5 px-3 w-56 text-right">Monto de la Cuota <span class="text-red-500">*</span></th>
                                            <th class="py-2.5 px-3 w-12 text-center"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyCuotas" class="divide-y divide-slate-100">
                                        <!-- Filas dinámicas de cuotas -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Barra de Cuadratura en tiempo real -->
                            <div class="mt-3.5 p-3 rounded-lg border flex flex-col sm:flex-row items-center justify-between gap-3 text-xs" id="barraCuadraturaCuotas">
                                <div class="flex flex-wrap items-center gap-4 text-slate-600">
                                    <span>Total Factura: <strong id="lblTotalFacturaCuotas" class="text-slate-900 font-bold">S/ 0.00</strong></span>
                                    <span>Total Cuotas: <strong id="lblTotalCuotas" class="text-slate-900 font-bold">S/ 0.00</strong></span>
                                    <span>Diferencia: <strong id="lblDiferenciaCuotas" class="font-bold">S/ 0.00</strong></span>
                                </div>
                                <div id="badgeEstadoCuotas">
                                    <span class="inline-flex items-center gap-1.5 bg-emerald-100 text-emerald-800 px-3 py-1 rounded-full font-bold">
                                        <i class="fas fa-check-circle"></i> Cuotas Cuadradas
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 2: DETALLE DE RECEPCIÓN Y PRODUCTOS -->
                <div class="bg-white rounded-xl shadow-md border border-slate-200/80 p-5 md:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3.5 mb-5">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-boxes text-emerald-600 text-base"></i>
                            <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide">2. Detalle de Recepción y Productos</h2>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <label class="inline-flex items-center cursor-pointer bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-lg shadow-2xs hover:bg-amber-100/60 transition">
                                <input type="checkbox" name="inafecto_igv" id="checkInafecto" class="form-checkbox rounded text-amber-600 h-4 w-4 focus:ring-amber-500 border-amber-300 transition-colors" onchange="recalcularTotales()">
                                <span class="ml-2 text-[11px] text-amber-800 font-bold tracking-wide uppercase">No afecto al IGV</span>
                            </label>
                            <label class="inline-flex items-center cursor-pointer bg-indigo-50 border border-indigo-200 px-3 py-1.5 rounded-lg shadow-2xs hover:bg-indigo-100/60 transition">
                                <input type="checkbox" name="igv_incluido" id="checkIgv" class="form-checkbox rounded text-indigo-600 h-4 w-4 focus:ring-indigo-500 border-indigo-300 transition-colors" onchange="recalcularTotales()">
                                <span class="ml-2 text-[11px] text-indigo-800 font-bold tracking-wide uppercase">Precios Incluyen IGV</span>
                            </label>
                        </div>
                    </div>

                    <!-- Buscador Inline Integrado (Clean & Industrial) -->
                    <div class="flex flex-col sm:flex-row sm:items-end gap-3 mb-4 bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/80">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                                <i class="fas fa-search text-emerald-600 mr-1"></i> Buscar Producto o Materia Prima
                            </label>
                            <select id="selectProductoInline" class="w-full" style="width:100%"></select>
                        </div>
                        <button type="button" id="btnAgregarProductoInline" class="btn-primary h-10 px-5 whitespace-nowrap shadow-xs">
                            <i class="fas fa-plus mr-1"></i>
                            <span>Agregar</span>
                        </button>
                    </div>

                    <div class="rounded-xl border border-slate-200 mb-3 overflow-x-auto shadow-xs scrollbar-thin">
                        <table class="w-full text-left border-collapse min-w-[1150px]" id="tablaProductos">
                            <thead>
                                <tr class="bg-slate-800 text-[11px] uppercase text-white tracking-wider font-bold">
                                    <th class="py-2.5 px-3 text-center border-r border-slate-700/60 w-12">#</th>
                                    <th class="py-2.5 px-3 border-r border-slate-700/60 min-w-[280px]">Producto / Descripción</th>
                                    <th class="py-2.5 px-3 border-r border-slate-700/60 w-48 min-w-[180px]">Almacén Destino</th>
                                    <th class="py-2.5 px-3 text-center border-r border-slate-700/60 w-28 min-w-[90px]">Cant.</th>
                                    <th class="py-2.5 px-3 text-center border-r border-slate-700/60 w-24 min-w-[80px]">U.M.</th>
                                    <th class="py-2.5 px-3 text-center border-r border-slate-700/60 w-40 min-w-[145px]">Lote</th>
                                    <th class="py-2.5 px-3 text-center border-r border-slate-700/60 w-36 min-w-[135px]">F. Venc.</th>
                                    <th class="py-2.5 px-3 text-right border-r border-slate-700/60 w-32 min-w-[100px]">P. Unit.</th>
                                    <th class="py-2.5 px-3 text-right border-r border-slate-700/60 w-32 min-w-[110px]">Subtotal</th>
                                    <th class="py-2.5 px-3 text-center w-12">Acc.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white" id="tbodyProductos">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-3 xl:col-span-3">
                <div class="bg-slate-800 rounded-2xl shadow-lg border border-slate-700 sticky top-6 overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                            <i class="fas fa-calculator text-primary"></i> Resumen
                        </h2>
                        <div class="space-y-4">
                            <div class="flex justify-between text-slate-300 text-sm">
                                <span>Subtotal:</span>
                                <input type="hidden" name="total_subtotal" id="h_sub" value="0.00">
                                <span id="txt_sub" class="font-medium text-white">S/ 0.00</span>
                            </div>
                            <div class="flex justify-between text-slate-300 text-sm">
                                <span>IGV (18%):</span>
                                <input type="hidden" name="total_impuestos" id="h_igv" value="0.00">
                                <span id="txt_igv" class="font-medium text-white">S/ 0.00</span>
                            </div>
                            <div class="pt-4 mt-4 border-t border-slate-600 flex justify-between items-center">
                                <span class="text-slate-200 font-bold">TOTAL:</span>
                                <input type="hidden" name="total_general" id="h_total" value="0.00">
                                <span id="txt_total" class="text-2xl font-black text-primary">S/ 0.00</span>
                            </div>
                        </div>
                        <button type="submit" class="w-full mt-8 btn-primary py-3 rounded-xl font-bold text-base flex justify-center gap-2">
                            <i class="fas fa-save"></i> Registrar Compra
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<x-modal id="modalProveedor" title="Nuevo Proveedor">
    <form id="form-modalProveedor" class="space-y-5">
        <x-form-group label="RUC" required>
            <input type="text" name="ruc" id="ruc" class="input-field" required maxlength="11" placeholder="Ej. 20123456789">
        </x-form-group>
        <x-form-group label="Razón Social" required>
            <input type="text" name="razon_social" id="razon_social" class="input-field" required placeholder="Nombre de la empresa">
        </x-form-group>
        <x-slot:footer>
            <button type="submit" form="form-modalProveedor" class="btn-primary w-full">
                <i class="fas fa-save"></i> Guardar y Seleccionar
            </button>
        </x-slot:footer>
    </form>
</x-modal>

<x-modal id="modalGenerarCuotas" title="Generador de Cuotas al Crédito" size="md">
    <div class="space-y-4">
        <!-- Resumen de Total a Financiar -->
        <div class="bg-slate-800 rounded-xl p-4 text-white flex items-center justify-between shadow-xs border border-slate-700">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-300 block">Total a Financiar:</span>
                <span id="modalLblTotalFactura" class="text-2xl font-black text-emerald-400">S/ 0.00</span>
            </div>
            <div class="text-right">
                <span class="text-[10px] text-slate-400 block uppercase font-bold tracking-wider">Condición</span>
                <span class="inline-flex items-center gap-1 bg-amber-500/20 text-amber-300 border border-amber-500/40 text-xs px-2.5 py-0.5 rounded-full font-bold">
                    <i class="fas fa-credit-card text-[10px]"></i> Crédito
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                    N° de Cuotas <span class="text-red-500">*</span>
                </label>
                <input type="number" id="modalInputCuotas" min="1" max="60" value="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 text-center font-bold focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" placeholder="Ej. 3">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                    Frecuencia (Días) <span class="text-red-500">*</span>
                </label>
                <input type="number" id="modalInputDias" min="1" max="365" value="30" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 text-center font-bold focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" placeholder="Ej. 30">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                Fecha Base de Inicio
            </label>
            <input type="date" id="modalInputFechaBase" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition bg-white shadow-xs" value="{{ date('Y-m-d') }}">
            <p class="text-[11px] text-slate-500 mt-1">Las fechas de pago se calcularán sumando los días sucesivamente a partir de esta fecha.</p>
        </div>

        <!-- Caja de Vista Previa en tiempo real -->
        <div class="bg-emerald-50/80 border border-emerald-200/90 rounded-xl p-3.5 text-xs text-emerald-950 shadow-2xs">
            <div class="flex items-center gap-1.5 font-bold mb-1 text-emerald-800">
                <i class="fas fa-magic text-emerald-600"></i>
                <span>Vista Previa del Plan de Pagos:</span>
            </div>
            <div id="modalTextoPreview" class="text-xs text-slate-700 pl-4 font-medium leading-relaxed">
                Generando 2 cuotas de S/ 0.00 cada 30 días.
            </div>
        </div>
    </div>

    <x-slot:footer>
        <button type="button" onclick="cerrarModal('modalGenerarCuotas')" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition shadow-2xs">
            Cancelar
        </button>
        <button type="button" id="btnModalProcesar" class="btn-primary text-xs py-2 px-5 flex items-center gap-2 shadow-xs">
            <i class="fas fa-cogs"></i>
            <span>Procesar y Generar Cuotas</span>
        </button>
    </x-slot:footer>
</x-modal>

<script src="/vendor/jquery/jquery.min.js"></script>
<script src="/vendor/select2/select2.min.js"></script>
<script>
    window.cerrarModal = function(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            const form = document.getElementById('form-' + id);
            if (form) form.reset();
        }
    };
    window.abrirModal = function(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    };

    let filaIdx = 0;
    let tablaBody;
    let searchUrl;
    let almacenesData;
    let unidadesData;
    function getProductName(text) {
        const m = text.match(/\]\s*(.*)/);
        return m ? m[1] : text;
    }

    function obtenerLoteFacturaActual() {
        const s = ($('input[name="serie_documento"]').val() || '').trim().toUpperCase();
        const n = ($('input[name="numero_documento"]').val() || '').trim();
        if (s && n) return `${s}-${n}`;
        if (s) return s;
        if (n) return n;
        return '';
    }

    function obtenerFechaEmisionActual() {
        return ($('input[name="fecha_compra"]').val() || '').trim();
    }

    function actualizarLotesFilasAutomaticos() {
        const idsGuias = $('#selectGuia').val();
        if (idsGuias && idsGuias.length > 0) {
            return;
        }
        const nuevoLote = obtenerLoteFacturaActual();
        document.querySelectorAll('#tbodyProductos .fila-producto').forEach(fila => {
            const inputLote = fila.querySelector('input[name*="[lote]"]');
            if (inputLote) {
                const esManual = inputLote.getAttribute('data-manual') === '1';
                if (!esManual) {
                    inputLote.value = nuevoLote;
                }
            }
        });
    }

    function actualizarFechasFilasAutomaticos() {
        const idsGuias = $('#selectGuia').val();
        if (idsGuias && idsGuias.length > 0) {
            return;
        }
        const nuevaFecha = obtenerFechaEmisionActual();
        if (!nuevaFecha) return;
        document.querySelectorAll('#tbodyProductos .fila-producto').forEach(fila => {
            const inputVenc = fila.querySelector('input[name*="[fecha_vencimiento]"]');
            if (inputVenc) {
                const esManual = inputVenc.getAttribute('data-manual') === '1';
                if (!esManual) {
                    inputVenc.value = nuevaFecha;
                }
            }
        });
    }

    function generarTemplateHTML(idx, codigo, nombre, um, loteCustom = null, vencCustom = null) {
        let opcionesAlmacen = almacenesData.map(a => `<option value="${a.codigo}" ${a.codigo === 'ALM04' ? 'selected' : ''}>${a.descripcion}</option>`).join('');
        let opcionesUM = unidadesData.map(u => `<option value="${u.codigo}" ${u.codigo === um ? 'selected' : ''}>${u.codigo}</option>`).join('');
        const valorLote = loteCustom !== null ? loteCustom : obtenerLoteFacturaActual();
        const valorVenc = vencCustom !== null ? vencCustom : obtenerFechaEmisionActual();
        return `
        <tr class="fila-producto hover:bg-slate-50/70 transition-colors">
            <td class="py-2.5 px-3 text-center">
                <span class="text-xs font-bold text-slate-500 row-item-number">${idx + 1}</span>
            </td>
            <td class="py-2.5 px-3">
                <div class="leading-tight">
                    <span class="texto-prod text-xs font-bold text-slate-900 block" title="${nombre}">${nombre}</span>
                    <span class="text-[10px] text-slate-500 font-mono mt-0.5 block">${codigo}</span>
                </div>
                <input type="hidden" class="input-cod" name="productos[${idx}][codigo]" value="${codigo}">
            </td>
            <td class="py-2.5 px-3">
                <select class="w-full border border-slate-300 bg-white rounded-lg text-xs px-2.5 py-1.5 select-alm focus:border-primary focus:ring-1 focus:ring-primary shadow-xs" name="productos[${idx}][codigo_almacen]">
                    ${opcionesAlmacen}
                </select>
            </td>
            <td class="py-2.5 px-3">
                <input type="number" step="0.01" min="0.01" class="w-full border border-slate-300 bg-white text-center rounded-lg text-xs font-semibold px-2 py-1.5 input-cant focus:border-primary focus:ring-1 focus:ring-primary shadow-xs" name="productos[${idx}][cantidad]" placeholder="0.00">
            </td>
            <td class="py-2.5 px-3 text-center">
                <select class="w-full border border-slate-300 bg-white rounded-lg text-xs text-center px-1.5 py-1.5 select-um focus:border-primary focus:ring-1 focus:ring-primary shadow-xs" name="productos[${idx}][codigo_unidad_medida]">
                    ${opcionesUM}
                </select>
            </td>
            <td class="py-2.5 px-3">
                <input type="text" class="w-full border border-slate-300 bg-white rounded-lg text-xs text-center uppercase px-2 py-1.5 focus:border-primary focus:ring-1 focus:ring-primary shadow-xs" placeholder="Lote..." name="productos[${idx}][lote]" value="${valorLote}" oninput="this.setAttribute('data-manual', this.value.trim() ? '1' : '0')">
            </td>
            <td class="py-2.5 px-3">
                <input type="date" class="w-full border border-slate-300 bg-white rounded-lg text-xs text-center px-1.5 py-1.5 focus:border-primary focus:ring-1 focus:ring-primary shadow-xs" name="productos[${idx}][fecha_vencimiento]" value="${valorVenc}" oninput="this.setAttribute('data-manual', this.value.trim() ? '1' : '0')">
            </td>
            <td class="py-2.5 px-3">
                <input type="number" step="any" min="0" class="w-full border border-slate-300 bg-white text-right rounded-lg text-xs text-primary font-bold px-2 py-1.5 input-prec focus:border-primary focus:ring-1 focus:ring-primary shadow-xs" name="productos[${idx}][precio]" placeholder="0.00">
            </td>
            <td class="py-2.5 px-3">
                <input type="number" step="0.01" min="0" class="w-full border border-slate-300 bg-slate-50 text-right rounded-lg text-xs font-black text-slate-900 px-2 py-1.5 input-sub focus:border-primary focus:ring-1 focus:ring-primary shadow-xs" name="productos[${idx}][subtotal]" value="0.00" placeholder="0.00">
            </td>
            <td class="py-2.5 px-3 text-center">
                <button type="button" class="text-slate-400 hover:text-red-500 hover:bg-red-50 w-7 h-7 rounded-md inline-flex items-center justify-center btn-del transition text-xs" title="Eliminar fila">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>
    `;
    }

    function agregarFila(producto) {
        const codigo = producto.id;
        
        // Verificar si el producto ya existe en la tabla
        let existe = false;
        document.querySelectorAll('.input-cod').forEach(input => {
            if (input.value === codigo) {
                existe = true;
                // Incrementar cantidad si ya existe
                const fila = input.closest('tr');
                const cantInput = fila.querySelector('.input-cant');
                if (cantInput) {
                    cantInput.value = (parseFloat(cantInput.value) + 1).toFixed(2);
                    // Disparar evento input para recalcular subtotales
                    cantInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
        });

        if (existe) {
            window.toast('El producto ya estaba en la lista. Se aumentó la cantidad.', 'info');
            return;
        }

        const idx = filaIdx++;
        const nombre = getProductName(producto.text);
        const um = producto.codigo_unidad_medida || 'CAJ';
        const html = generarTemplateHTML(idx, codigo, nombre, um);
        tablaBody.insertAdjacentHTML('beforeend', html);
    }

    function recalcularTotales() {
        let st = 0;
        document.querySelectorAll('.input-sub').forEach(el => st += parseFloat(el.value) || 0);
        
        let checkIgv = document.getElementById('checkIgv');
        let checkInafecto = document.getElementById('checkInafecto');
        
        if (checkInafecto && checkInafecto.checked) {
            if (checkIgv) {
                checkIgv.checked = false;
                checkIgv.disabled = true;
            }
        } else {
            if (checkIgv) {
                checkIgv.disabled = false;
            }
        }

        let isIgvIncluido = checkIgv && checkIgv.checked;
        let isInafecto = checkInafecto && checkInafecto.checked;
        
        let subtotal = 0;
        let igv = 0;
        let total = 0;
        
        if (isInafecto) {
            subtotal = st;
            igv = 0;
            total = st;
        } else if (isIgvIncluido) {
            total = st; // The sum is the Total
            subtotal = total / 1.18;
            igv = total - subtotal;
        } else {
            subtotal = st; // The sum is the Subtotal
            igv = subtotal * 0.18;
            total = subtotal + igv;
        }

        let currencySymbol = document.getElementById('selectMoneda').value === 'USD' ? '$ ' : 'S/ ';
        document.getElementById('txt_sub').innerText = currencySymbol + subtotal.toFixed(2);
        document.getElementById('txt_igv').innerText = currencySymbol + igv.toFixed(2);
        document.getElementById('txt_total').innerText = currencySymbol + total.toFixed(2);
        document.getElementById('h_sub').value = subtotal.toFixed(2);
        document.getElementById('h_igv').value = igv.toFixed(2);
        document.getElementById('h_total').value = total.toFixed(2);

        recalcularCuadraturaCuotas();
    }

    let cuotaIdx = 0;

    function recalcularCuadraturaCuotas() {
        const condicion = document.getElementById('selectCondicionPago') ? document.getElementById('selectCondicionPago').value : 'CONTADO';
        const seccionCuotas = document.getElementById('seccionCuotasCredito');
        if (condicion !== 'CREDITO' || !seccionCuotas) return;

        let totalFactura = parseFloat(document.getElementById('h_total').value) || 0;
        let totalCuotas = 0;
        let numCuotas = 0;

        document.querySelectorAll('.input-cuota-monto').forEach(input => {
            totalCuotas += parseFloat(input.value) || 0;
            numCuotas++;
        });

        let diff = totalCuotas - totalFactura;
        let symbol = document.getElementById('selectMoneda').value === 'USD' ? '$ ' : 'S/ ';

        const lblFactura = document.getElementById('lblTotalFacturaCuotas');
        const lblCuotas = document.getElementById('lblTotalCuotas');
        const lblDiferencia = document.getElementById('lblDiferenciaCuotas');
        const barra = document.getElementById('barraCuadraturaCuotas');
        const badge = document.getElementById('badgeEstadoCuotas');

        if (lblFactura) lblFactura.textContent = symbol + totalFactura.toFixed(2);
        if (lblCuotas) lblCuotas.textContent = symbol + totalCuotas.toFixed(2);
        if (lblDiferencia) {
            lblDiferencia.textContent = symbol + Math.abs(diff).toFixed(2);
            if (Math.abs(diff) <= 0.05) {
                lblDiferencia.className = 'font-bold text-emerald-600';
            } else {
                lblDiferencia.className = 'font-bold text-red-600';
            }
        }

        if (Math.abs(diff) <= 0.05 && totalFactura > 0 && numCuotas > 0) {
            if (barra) {
                barra.className = 'mt-3.5 p-3 rounded-lg border flex flex-col sm:flex-row items-center justify-between gap-3 text-xs border-emerald-200 bg-emerald-50/60';
            }
            if (badge) {
                badge.innerHTML = `<span class="inline-flex items-center gap-1.5 bg-emerald-100 text-emerald-800 px-3 py-1 rounded-full font-bold shadow-xs">
                    <i class="fas fa-check-circle text-emerald-600"></i> Cuotas Cuadradas
                </span>`;
            }
        } else {
            if (barra) {
                barra.className = 'mt-3.5 p-3 rounded-lg border flex flex-col sm:flex-row items-center justify-between gap-3 text-xs border-amber-300 bg-amber-50/60';
            }
            let msg = numCuotas === 0 ? 'Sin cuotas registradas' : (diff < 0 ? `Falta asignar ${symbol}${Math.abs(diff).toFixed(2)}` : `Exceso de ${symbol}${diff.toFixed(2)}`);
            if (badge) {
                badge.innerHTML = `<span class="inline-flex items-center gap-1.5 bg-amber-100 text-amber-900 px-3 py-1 rounded-full font-bold shadow-xs">
                    <i class="fas fa-exclamation-circle text-amber-600"></i> ${msg}
                </span>`;
            }
        }
    }

    function generarTemplateFilaCuota(idx, numero, fechaVenc, monto) {
        const isCredito = $('#selectCondicionPago').val() === 'CREDITO';
        const disabledAttr = isCredito ? '' : 'disabled';
        return `
        <tr class="fila-cuota hover:bg-slate-50/50 transition-colors">
            <td class="py-2 px-3 text-center">
                <span class="cuota-numero-badge inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-700 font-bold text-xs">${numero}</span>
                <input type="hidden" name="cuotas[${idx}][numero_cuota]" class="input-cuota-numero" value="${numero}" ${disabledAttr}>
            </td>
            <td class="py-2 px-3">
                <input type="date" name="cuotas[${idx}][fecha_vencimiento]" value="${fechaVenc}" class="w-full border border-slate-300 rounded-md text-xs px-2.5 py-1.5 focus:ring-primary focus:border-primary input-cuota-fecha" ${disabledAttr}>
            </td>
            <td class="py-2 px-3 text-right">
                <input type="number" step="0.01" min="0.01" name="cuotas[${idx}][monto]" value="${monto}" placeholder="0.00" class="w-full border border-slate-300 rounded-md text-xs px-2.5 py-1.5 text-right font-bold text-slate-800 focus:ring-primary focus:border-primary input-cuota-monto" ${disabledAttr}>
            </td>
            <td class="py-2 px-3 text-center">
                <button type="button" class="text-slate-400 hover:text-red-500 btn-del-cuota transition-colors p-1" title="Eliminar cuota">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>
        `;
    }

    function renumerarCuotas() {
        let rows = document.querySelectorAll('.fila-cuota');
        rows.forEach((row, i) => {
            let num = i + 1;
            let badge = row.querySelector('.cuota-numero-badge');
            if (badge) badge.textContent = num;
            let inputNum = row.querySelector('.input-cuota-numero');
            if (inputNum) inputNum.value = num;
        });
    }

    function agregarFilaCuota(fecha = null, monto = null) {
        const tbody = document.getElementById('tbodyCuotas');
        if (!tbody) return;

        let existingRows = document.querySelectorAll('.fila-cuota');
        let numero = existingRows.length + 1;

        if (!fecha) {
            if (existingRows.length > 0) {
                let lastFechaInput = existingRows[existingRows.length - 1].querySelector('.input-cuota-fecha');
                if (lastFechaInput && lastFechaInput.value) {
                    let d = new Date(lastFechaInput.value + 'T00:00:00');
                    d.setDate(d.getDate() + 30);
                    fecha = d.toISOString().split('T')[0];
                }
            }
            if (!fecha) {
                let fechaCompra = document.querySelector('input[name="fecha_compra"]').value || new Date().toISOString().split('T')[0];
                let d = new Date(fechaCompra + 'T00:00:00');
                d.setDate(d.getDate() + 30);
                fecha = d.toISOString().split('T')[0];
            }
        }

        if (monto === null) {
            let totalFactura = parseFloat(document.getElementById('h_total').value) || 0;
            let sumaActual = 0;
            document.querySelectorAll('.input-cuota-monto').forEach(inp => {
                sumaActual += parseFloat(inp.value) || 0;
            });
            let restante = totalFactura - sumaActual;
            monto = restante > 0 ? restante.toFixed(2) : '';
        }

        let idx = cuotaIdx++;
        let html = generarTemplateFilaCuota(idx, numero, fecha, monto);
        tbody.insertAdjacentHTML('beforeend', html);
        renumerarCuotas();
        recalcularCuadraturaCuotas();
    }

    function procesarGeneracionCuotas(numCuotas, diasIntervalo, fechaBase) {
        numCuotas = parseInt(numCuotas) || 1;
        diasIntervalo = parseInt(diasIntervalo) || 30;
        
        if (numCuotas < 1) {
            if (typeof window.toast === 'function') window.toast('Ingrese al menos 1 cuota.', 'warning');
            else alert('Ingrese al menos 1 cuota.');
            return false;
        }

        if (!fechaBase) {
            fechaBase = obtenerFechaEmisionActual() || new Date().toISOString().split('T')[0];
        }

        const totalFactura = parseFloat(document.getElementById('h_total').value) || 0;
        const tbody = document.getElementById('tbodyCuotas');
        if (!tbody) return false;

        // Limpiar filas de cuotas previas
        tbody.innerHTML = '';
        cuotaIdx = 0;

        let montoBase = 0;
        let restoCentavos = 0;

        if (totalFactura > 0) {
            montoBase = Math.floor((totalFactura / numCuotas) * 100) / 100;
            restoCentavos = Math.round((totalFactura - (montoBase * numCuotas)) * 100) / 100;
        }

        let ultimaFecha = fechaBase;

        for (let i = 1; i <= numCuotas; i++) {
            // Calcular fecha de vencimiento: fechaBase + (i * diasIntervalo)
            const d = new Date(fechaBase + 'T00:00:00');
            d.setDate(d.getDate() + (i * diasIntervalo));
            const fechaStr = d.toISOString().split('T')[0];
            ultimaFecha = fechaStr;

            // Distribuir el remanente de centavos en la primera cuota para cuadratura 100% exacta
            let montoCuota = montoBase;
            if (i === 1) {
                montoCuota = Math.round((montoBase + restoCentavos) * 100) / 100;
            }
            const montoStr = totalFactura > 0 ? montoCuota.toFixed(2) : '';

            const idx = cuotaIdx++;
            const html = generarTemplateFilaCuota(idx, i, fechaStr, montoStr);
            tbody.insertAdjacentHTML('beforeend', html);
        }

        // Sincronizar fecha de vencimiento principal de la compra
        $('#inputFechaVencimiento').val(ultimaFecha);

        renumerarCuotas();
        recalcularCuadraturaCuotas();

        // Sincronizar inputs rápidos y modal
        $('#quickNumCuotas').val(numCuotas);
        $('#quickDiasIntervalo').val(diasIntervalo);
        $('#modalInputCuotas').val(numCuotas);
        $('#modalInputDias').val(diasIntervalo);
        $('#modalInputFechaBase').val(fechaBase);

        if (typeof window.toast === 'function') {
            window.toast(`Se generaron ${numCuotas} cuota(s) correctamente.`, 'success');
        }
        return true;
    }

    function actualizarPreviewModal() {
        const totalFactura = parseFloat(document.getElementById('h_total').value) || 0;
        const moneda = $('#selectMoneda').val() === 'USD' ? '$ ' : 'S/ ';
        $('#modalLblTotalFactura').text(moneda + totalFactura.toFixed(2));

        const numCuotas = parseInt($('#modalInputCuotas').val()) || 1;
        const dias = parseInt($('#modalInputDias').val()) || 30;
        let fechaBase = $('#modalInputFechaBase').val() || obtenerFechaEmisionActual() || new Date().toISOString().split('T')[0];

        let montoAprox = numCuotas > 0 ? (totalFactura / numCuotas).toFixed(2) : '0.00';

        const dUltima = new Date(fechaBase + 'T00:00:00');
        dUltima.setDate(dUltima.getDate() + (numCuotas * dias));
        const fechaUltimaStr = dUltima.toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });

        $('#modalTextoPreview').html(`
            Generando <strong class="text-slate-900">${numCuotas} cuota(s)</strong> de aprox. <strong class="text-emerald-700">${moneda}${montoAprox}</strong> cada <strong class="text-slate-900">${dias} días</strong>.<br>
            <span class="text-slate-500 text-[11px]">Última fecha de vencimiento: <strong>${fechaUltimaStr}</strong></span>
        `);
    }

    $(document).ready(function () {
        tablaBody = document.getElementById('tbodyProductos');
        searchUrl = '/productos/search-ajax?sin_actividades=1';
        almacenesData = {!! json_encode($almacenes->map(fn($a) => ['codigo' => $a->codigo_almacen, 'descripcion' => $a->descripcion])->all()) !!};
        unidadesData = {!! json_encode($unidades_medida->map(fn($u) => ['codigo' => $u->codigo, 'descripcion' => $u->descripcion])->all()) !!};

        // Inicializar estado de campos de cuotas según la condición seleccionada
        if ($('#selectCondicionPago').val() === 'CREDITO') {
            $('#seccionCuotasCredito :input').prop('disabled', false);
        } else {
            $('#seccionCuotasCredito :input').prop('disabled', true);
        }

        if (typeof $().select2 !== 'undefined') {
            const select2Config = {
                ajax: {
                    url: searchUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function(p) { return {q: p.term}; },
                    processResults: function(d) { return {results: d}; },
                    cache: true
                },
                minimumInputLength: 0,
                placeholder: 'Buscar producto por código o descripción...',
                width: '100%',
                templateResult: function(item) {
                    if (!item.id) return item.text;
                    const desc = item.text.replace(/\[.*?\]\s*/, '');
                    const tipo = item.descripcion_tipo_producto || item.codigo_tipo_producto || '';
                    const um = item.unidad_medida || item.codigo_unidad_medida || '';
                    return $(`
                        <div class="flex items-center justify-between py-1 px-0.5">
                            <div class="truncate mr-2">
                                <span class="font-bold text-slate-900">${item.id}</span>
                                <span class="text-slate-700 ml-1.5 font-medium">${desc}</span>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                ${tipo ? `<span class="text-[10px] uppercase font-bold px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded border border-slate-200">${tipo}</span>` : ''}
                                ${um ? `<span class="text-[10px] font-bold px-1.5 py-0.5 bg-emerald-50 text-emerald-800 rounded border border-emerald-200">${um}</span>` : ''}
                            </div>
                        </div>
                    `);
                },
                templateSelection: function(item) {
                    return item.text || item.id;
                }
            };

            // Inicializar Buscador Inline
            $('#selectProductoInline').select2(select2Config);
            $('#selectProductoInline').on('select2:select', function(e) {
                agregarFila(e.params.data);
                $('#selectProductoInline').val(null).trigger('change');
            });

            $('#btnAgregarProductoInline').on('click', function() {
                const data = $('#selectProductoInline').select2('data');
                if (data && data.length > 0 && data[0].id) {
                    agregarFila(data[0]);
                    $('#selectProductoInline').val(null).trigger('change');
                } else {
                    $('#selectProductoInline').select2('open');
                }
            });
        }

        let tipoCambioActual = "{{ \App\Models\ParametroSistema::where('codigo_parametro', 'TIPO_CAMBIO_USD')->value('valor') ?? '1.000' }}";
        
        $('#selectMoneda').on('change', function() {
            if ($(this).val() === 'USD') {
                $('#groupTipoCambio').show();
                $('#inputTipoCambio').prop('disabled', false).attr('required', true);
                if (parseFloat($('#inputTipoCambio').val()) === 1 || $('#inputTipoCambio').val() === '') {
                    $('#inputTipoCambio').val(tipoCambioActual);
                }
            } else {
                $('#groupTipoCambio').hide();
                $('#inputTipoCambio').prop('disabled', true).removeAttr('required');
                $('#inputTipoCambio').val('1.000');
            }
            recalcularTotales(); // Refrescar símbolos de la vista resumen
        });

        if (typeof $().select2 !== 'undefined') {
            $('#selectGuia').select2({
                placeholder: 'Seleccionar guías',
                allowClear: true
            });
        }

        // Formateo automático de N° Documento (8 dígitos con ceros) y Serie (mayúsculas)
        const inputNumDoc = document.querySelector('input[name="numero_documento"]');
        const inputSerieDoc = document.querySelector('input[name="serie_documento"]');

        const formatNumeroDoc = function () {
            if (!inputNumDoc) return;
            let val = inputNumDoc.value.trim();
            if (val && /^\d+$/.test(val)) {
                inputNumDoc.value = val.padStart(8, '0');
            }
        };

        const syncLotesYDoc = function () {
            formatNumeroDoc();
            actualizarLotesFilasAutomaticos();
        };

        if (inputNumDoc) {
            inputNumDoc.addEventListener('input', actualizarLotesFilasAutomaticos);
            inputNumDoc.addEventListener('blur', syncLotesYDoc);
            inputNumDoc.addEventListener('change', syncLotesYDoc);
        }

        if (inputSerieDoc) {
            inputSerieDoc.addEventListener('input', function () {
                this.value = this.value.toUpperCase();
                actualizarLotesFilasAutomaticos();
            });
            inputSerieDoc.addEventListener('blur', function () {
                this.value = this.value.trim().toUpperCase();
                actualizarLotesFilasAutomaticos();
            });
        }

        const inputFechaCompra = document.querySelector('input[name="fecha_compra"]');
        if (inputFechaCompra) {
            inputFechaCompra.addEventListener('input', actualizarFechasFilasAutomaticos);
            inputFechaCompra.addEventListener('change', actualizarFechasFilasAutomaticos);
            inputFechaCompra.addEventListener('blur', actualizarFechasFilasAutomaticos);
        }

        // Lógica de Condición de Pago y Cuotas al Crédito
        $('#selectCondicionPago').on('change', function() {
            const condicion = $(this).val();
            const seccionCuotas = $('#seccionCuotasCredito');
            
            if (condicion === 'CREDITO') {
                $('#seccionCuotasCredito :input').prop('disabled', false);
                seccionCuotas.slideDown(200);

                // Si aún no hay cuotas registradas, abrir el modal asistente para configurar cuotas
                if ($('#tbodyCuotas .fila-cuota').length === 0) {
                    $('#modalInputCuotas').val($('#quickNumCuotas').val() || 2);
                    $('#modalInputDias').val($('#quickDiasIntervalo').val() || 30);
                    $('#modalInputFechaBase').val($('input[name="fecha_compra"]').val() || '{{ date('Y-m-d') }}');
                    actualizarPreviewModal();
                    abrirModal('modalGenerarCuotas');
                    setTimeout(() => $('#modalInputCuotas').focus(), 250);
                } else {
                    recalcularCuadraturaCuotas();
                }
            } else {
                $('#seccionCuotasCredito :input').prop('disabled', true);
                seccionCuotas.slideUp(200);
                const fechaEmision = $('input[name="fecha_compra"]').val() || '{{ date('Y-m-d') }}';
                $('#inputFechaVencimiento').val(fechaEmision);
            }
        });

        // Botón Procesar Rápido (Inline)
        $('#btnProcesarCuotasQuick').on('click', function() {
            const numCuotas = $('#quickNumCuotas').val();
            const dias = $('#quickDiasIntervalo').val();
            const fechaBase = $('input[name="fecha_compra"]').val() || '{{ date('Y-m-d') }}';
            procesarGeneracionCuotas(numCuotas, dias, fechaBase);
        });

        // Botón Abrir Asistente Modal de Cuotas
        $('#btnAbrirModalCuotas').on('click', function() {
            $('#modalInputCuotas').val($('#quickNumCuotas').val() || 2);
            $('#modalInputDias').val($('#quickDiasIntervalo').val() || 30);
            $('#modalInputFechaBase').val($('input[name="fecha_compra"]').val() || '{{ date('Y-m-d') }}');
            actualizarPreviewModal();
            abrirModal('modalGenerarCuotas');
            setTimeout(() => $('#modalInputCuotas').focus(), 250);
        });

        // Botón Procesar en el Modal
        $('#btnModalProcesar').on('click', function() {
            const numCuotas = $('#modalInputCuotas').val();
            const dias = $('#modalInputDias').val();
            const fechaBase = $('#modalInputFechaBase').val();
            if (procesarGeneracionCuotas(numCuotas, dias, fechaBase)) {
                cerrarModal('modalGenerarCuotas');
            }
        });

        // Actualización dinámica de la vista previa en el modal
        $('#modalInputCuotas, #modalInputDias, #modalInputFechaBase').on('input change', actualizarPreviewModal);

        $('input[name="fecha_compra"]').on('change', function() {
            if ($('#selectCondicionPago').val() === 'CONTADO') {
                $('#inputFechaVencimiento').val($(this).val());
            }
        });

        $('#btnAgregarCuota').on('click', function() {
            agregarFilaCuota();
        });

        $('#tablaCuotas').on('input', '.input-cuota-monto', function() {
            recalcularCuadraturaCuotas();
        });

        $('#tablaCuotas').on('change', '.input-cuota-fecha', function() {
            const fechas = [];
            document.querySelectorAll('.input-cuota-fecha').forEach(f => {
                if (f.value) fechas.push(f.value);
            });
            if (fechas.length > 0) {
                fechas.sort();
                $('#inputFechaVencimiento').val(fechas[fechas.length - 1]);
            }
        });

        $('#tablaCuotas').on('click', '.btn-del-cuota', function() {
            const rows = document.querySelectorAll('.fila-cuota');
            if (rows.length <= 1) {
                if (typeof window.toast === 'function') {
                    window.toast('Debe mantener al menos una cuota para compras al crédito.', 'warning');
                } else {
                    alert('Debe mantener al menos una cuota para compras al crédito.');
                }
                return;
            }
            $(this).closest('tr').remove();
            renumerarCuotas();
            recalcularCuadraturaCuotas();
        });

        $('#formCompra').on('submit', function (e) {
            formatNumeroDoc();

            const condicion = $('#selectCondicionPago').val();
            if (condicion !== 'CREDITO') {
                $('#seccionCuotasCredito :input').prop('disabled', true);
                return true;
            }

            const cuotas = document.querySelectorAll('.fila-cuota');
            if (cuotas.length === 0) {
                e.preventDefault();
                if (typeof window.toast === 'function') {
                    window.toast('Debe registrar al menos una cuota para compras al crédito.', 'error');
                } else {
                    alert('Debe registrar al menos una cuota para compras al crédito.');
                }
                return false;
            }

            let totalFactura = parseFloat($('#h_total').val()) || 0;
            let totalCuotas = 0;
            let tieneCamposVacios = false;

            document.querySelectorAll('.fila-cuota').forEach(row => {
                const fecha = row.querySelector('.input-cuota-fecha').value;
                const monto = parseFloat(row.querySelector('.input-cuota-monto').value) || 0;
                if (!fecha || monto <= 0) {
                    tieneCamposVacios = true;
                }
                totalCuotas += monto;
            });

            if (tieneCamposVacios) {
                e.preventDefault();
                if (typeof window.toast === 'function') {
                    window.toast('Todas las cuotas deben tener fecha de vencimiento y monto mayor a cero.', 'error');
                } else {
                    alert('Todas las cuotas deben tener fecha de vencimiento y monto mayor a cero.');
                }
                return false;
            }

            let diff = Math.abs(totalCuotas - totalFactura);
            if (diff > 0.05) {
                e.preventDefault();
                let symbol = $('#selectMoneda').val() === 'USD' ? '$ ' : 'S/ ';
                let msg = `El total de las cuotas (${symbol}${totalCuotas.toFixed(2)}) no coincide con el total de la factura (${symbol}${totalFactura.toFixed(2)}). Diferencia: ${symbol}${diff.toFixed(2)}`;
                if (typeof window.toast === 'function') {
                    window.toast(msg, 'error');
                } else {
                    alert(msg);
                }
                return false;
            }
        });

        // Lógica para autocompletar desde Guía de Remisión Múltiples
        $('#selectGuia').on('change', async function() {
            const idsGuias = $(this).val();
            
            // Validar proveedor único
            let valid = true;
            let currentRuc = null;
            $('#selectGuia option:selected').each(function() {
                let ruc = $(this).data('ruc');
                if (currentRuc === null) {
                    currentRuc = ruc;
                } else if (currentRuc !== ruc) {
                    valid = false;
                }
            });

            if (!valid) {
                alert('Las guías seleccionadas deben pertenecer al mismo proveedor.');
                // Quitar la última selección (la que rompió la regla)
                let selected = $(this).val();
                selected.pop();
                $(this).val(selected).trigger('change');
                return;
            }

            if(!idsGuias || idsGuias.length === 0) {
                $('#tbodyProductos').empty();
                recalcularTotales();
                return;
            }

            try {
                // Hacer POST con el array de IDs
                const response = await fetch(`/admin/compras/api/guias-multi`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ ids: idsGuias })
                });
                
                const dataArray = await response.json();
                
                // Setear proveedor con el primero encontrado
                if(dataArray.length > 0) {
                    $('#selectProveedor').val(dataArray[0].ruc_proveedor).trigger('change');
                }
                
                // Limpiar tabla actual
                $('#tbodyProductos').empty();
                filaIdx = 0;

                // Llenar detalles iterando sobre cada guía y sus detalles
                dataArray.forEach(data => {
                    let fechaGuiaStr = '';
                    if (data.fecha_emision) {
                        const fPart = data.fecha_emision.split('T')[0];
                        const p = fPart.split('-');
                        if (p.length === 3) {
                            fechaGuiaStr = `${p[2]}${p[1]}${p[0]}`; // DDMMYYYY ej. 13062026
                        }
                    }

                    data.detalles.forEach(d => {
                        const idx = filaIdx++;
                        const nombre = d.producto ? d.producto.descripcion : d.descripcion_producto;
                        let opcionesAlmacen = almacenesData.map(a => `<option value="${a.codigo}" ${a.codigo === d.codigo_almacen ? 'selected' : ''}>${a.descripcion}</option>`).join('');
                        let opcionesUM = unidadesData.map(u => `<option value="${u.codigo}" ${u.codigo === d.codigo_unidad_medida ? 'selected' : ''}>${u.codigo}</option>`).join('');
                        
                        let loteGuia = '';
                        if (d.lote && d.lote.trim() !== '') {
                            loteGuia = d.lote.trim();
                        } else {
                            const numGuia = (data.numero_guia || '').trim().toUpperCase();
                            if (numGuia && fechaGuiaStr) {
                                loteGuia = `${numGuia}-${fechaGuiaStr}`;
                            } else if (numGuia) {
                                loteGuia = numGuia;
                            }
                        }
                        
                        let fechaVencGuia = '';
                        if (d.fecha_vencimiento) {
                            fechaVencGuia = d.fecha_vencimiento.split('T')[0];
                        } else if (data.fecha_emision) {
                            fechaVencGuia = data.fecha_emision.split('T')[0];
                        } else {
                            fechaVencGuia = obtenerFechaEmisionActual();
                        }
                        
                        const html = `
                        <tr class="fila-producto bg-yellow-50/30">
                            <td class="p-1 text-center">
                                <span class="text-xs font-bold text-slate-500 row-item-number">${idx + 1}</span>
                            </td>
                            <td class="p-1">
                                <span class="texto-prod text-xs font-medium text-slate-800 truncate block" title="${nombre}">${d.codigo_producto} - ${nombre}</span>
                                <input type="hidden" class="input-cod" name="productos[${idx}][codigo]" value="${d.codigo_producto}">
                            </td>
                            <td class="p-1">
                                <select class="w-full border border-slate-200 bg-slate-100 rounded-md text-xs select-alm pointer-events-none" style="height:28px" name="productos[${idx}][codigo_almacen]" readonly tabindex="-1">
                                    ${opcionesAlmacen}
                                </select>
                            </td>
                            <td class="p-1">
                                <input type="number" step="0.01" min="0.01" class="w-full border border-slate-200 bg-slate-100 text-center rounded-md text-xs input-cant font-bold" style="height:28px" name="productos[${idx}][cantidad]" value="${d.cantidad}" readonly tabindex="-1">
                            </td>
                            <td class="p-1">
                                <select class="w-full border border-slate-200 bg-slate-100 rounded-md text-xs select-um pointer-events-none" style="height:28px" name="productos[${idx}][codigo_unidad_medida]" readonly tabindex="-1">
                                    ${opcionesUM}
                                </select>
                            </td>
                            <td class="p-1">
                                <input type="text" class="w-full border border-slate-200 bg-slate-100 rounded-md text-xs text-center uppercase" style="height:28px" name="productos[${idx}][lote]" value="${loteGuia}" readonly tabindex="-1">
                            </td>
                            <td class="p-1">
                                <input type="date" class="w-full border border-slate-200 bg-slate-100 rounded-md text-xs text-center" style="height:28px" name="productos[${idx}][fecha_vencimiento]" value="${fechaVencGuia}" readonly tabindex="-1">
                            </td>
                        <td class="p-1">
                            <input type="number" step="any" min="0" class="w-full border border-primary bg-white text-right rounded-md text-xs text-primary font-bold input-prec focus:ring-primary shadow-inner" style="height:28px" name="productos[${idx}][precio]" placeholder="0.00" required autofocus>
                        </td>
                        <td class="p-1">
                            <input type="number" step="0.01" min="0" class="w-full border border-primary bg-white text-right rounded-md text-xs text-primary font-bold input-sub focus:ring-primary shadow-inner" style="height:28px" name="productos[${idx}][subtotal]" placeholder="0.00" value="0.00">
                        </td>
                        <td class="p-1 text-center">
                            <!-- No se puede eliminar items de una guia -->
                            <i class="fas fa-lock text-slate-300 text-xs" title="Vinculado a Guía"></i>
                        </td>
                    </tr>`;
                    tablaBody.insertAdjacentHTML('beforeend', html);
                });
            });
            recalcularTotales();
            window.toast('Datos de las guías cargados. Por favor ingrese los precios unitarios o subtotales.', 'success');
                
                // Focus el primer precio
                setTimeout(() => {
                    const firstPrice = document.querySelector('.input-prec');
                    if(firstPrice) firstPrice.focus();
                }, 100);

            } catch (err) {
                console.error(err);
                window.toast('Error al cargar la guía.', 'error');
            }
        });

        $('#tablaProductos').on('input', function (e) {
            const fila = e.target.closest('tr');
            if (!fila) return;
            const cantInput = fila.querySelector('.input-cant');
            const precInput = fila.querySelector('.input-prec');
            const subInput = fila.querySelector('.input-sub');
            const cant = parseFloat(cantInput ? cantInput.value : 0) || 0;

            if ($(e.target).hasClass('input-cant') || $(e.target).hasClass('input-prec')) {
                const prec = parseFloat(precInput ? precInput.value : 0) || 0;
                if (subInput && cant > 0 && prec >= 0) {
                    subInput.value = (cant * prec).toFixed(2);
                }
                recalcularTotales();
            } else if ($(e.target).hasClass('input-sub')) {
                const sub = parseFloat(subInput ? subInput.value : 0) || 0;
                if (precInput && cant > 0 && sub >= 0) {
                    precInput.value = (sub / cant).toFixed(6);
                }
                recalcularTotales();
            }
        });

        $('#tablaProductos').on('click', function (e) {
            if ($(e.target).closest('.btn-del').length) {
                $(e.target).closest('tr').remove();
                recalcularTotales();
                
                // Reenumerar filas
                document.querySelectorAll('.fila-producto').forEach((fila, index) => {
                    const itemNumber = fila.querySelector('.row-item-number');
                    if (itemNumber) {
                        itemNumber.textContent = index + 1;
                    }
                });
            }
        });

        $('#form-modalProveedor').on('submit', async function (e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);

            try {
                const response = await fetch('/admin/proveedores/ajax', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    const select = document.getElementById('selectProveedor');
                    select.add(new Option(data.proveedor.ruc + ' - ' + data.proveedor.razon_social, data.proveedor.ruc, true, true));
                    cerrarModal('modalProveedor');
                } else {
                    window.toast('Error: ' + data.message, 'error');
                }
            } catch (error) {
                window.toast('Ocurrió un error en la conexión.', 'error');
            }
        });
    });
</script>
@endsection
