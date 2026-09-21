@extends('layouts.app')
@section('title', 'Registrar Nueva Compra')

@section('content')
<link href="/vendor/select2/select2.min.css" rel="stylesheet" />
<div class="container mx-auto px-4 py-6 max-w-7xl">

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

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            <div class="lg:col-span-8 xl:col-span-9 space-y-6">
                <x-card>
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                        <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                            <i class="fas fa-file-invoice text-primary"></i> Datos del Comprobante
                        </h2>
                        
                        <!-- Selector de Guía de Remisión -->
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-semibold text-slate-500">¿Vincular a Guía?</label>
                            <select name="ids_guias[]" id="selectGuia" multiple="multiple" class="border border-slate-300 rounded-lg text-sm px-2 py-1 bg-white focus:ring-primary focus:border-primary" style="width: 350px;">
                                @foreach($guiasPendientes as $guia)
                                    <option value="{{ $guia->id_guia }}" data-ruc="{{ $guia->ruc_proveedor }}">
                                        Guía {{ $guia->numero_guia }} - {{ Str::limit($guia->proveedor, 20) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <x-form-group class="md:col-span-2" label="Tipo Doc." required>
                                <select name="tipo_documento" class="input-field text-xs" required>
                                    @foreach($tipos_comprobante as $tc)
                                        <option value="{{ $tc->valor }}">{{ $tc->descripcion }}</option>
                                    @endforeach
                                </select>
                            </x-form-group>
                            <x-form-group class="md:col-span-2" label="Serie" required>
                                <input type="text" name="serie_documento" class="input-field uppercase text-xs" placeholder="F001" required>
                            </x-form-group>
                            <x-form-group class="md:col-span-3" label="N° Documento" required>
                                <input type="text" name="numero_documento" class="input-field text-xs font-mono" placeholder="00000001" required>
                            </x-form-group>

                            <x-form-group class="md:col-span-3" label="Fecha de Emisión" required>
                                <input type="date" name="fecha_compra" value="{{ date('Y-m-d') }}" class="input-field text-xs" required max="{{ date('Y-m-d') }}">
                            </x-form-group>

                            <x-form-group class="md:col-span-2" label="Moneda" required>
                                <select name="moneda" id="selectMoneda" class="input-field text-xs" required>
                                    <option value="PEN">Soles (PEN)</option>
                                    <option value="USD">Dólares (USD)</option>
                                </select>
                            </x-form-group>

                            <x-form-group class="md:col-span-6" label="Proveedor" required>
                                <div class="flex gap-2">
                                    <select name="ruc_proveedor" id="selectProveedor" class="input-field text-xs" required>
                                        <option value="">Seleccione Proveedor...</option>
                                        @foreach($proveedores as $p)
                                            <option value="{{ $p->ruc }}">{{ $p->ruc }} - {{ $p->razon_social }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" onclick="abrirModal('modalProveedor')" class="bg-slate-800 text-white px-3.5 rounded-lg hover:bg-slate-700 transition shadow-sm text-xs" title="Registrar Nuevo Proveedor">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </x-form-group>

                            <x-form-group class="md:col-span-3" label="Condición de Pago" required>
                                <select name="condicion_pago" id="selectCondicionPago" class="input-field font-semibold text-xs" required>
                                    <option value="CONTADO">Contado</option>
                                    <option value="CREDITO">Crédito</option>
                                </select>
                            </x-form-group>

                            <x-form-group class="md:col-span-3" label="Fecha de Vencimiento" id="groupFechaVencimiento">
                                <input type="date" name="fecha_vencimiento" id="inputFechaVencimiento" class="input-field text-xs" value="{{ date('Y-m-d') }}">
                            </x-form-group>

                            <x-form-group class="md:col-span-3" label="Tipo de Cambio" id="groupTipoCambio" style="display: none;">
                                <input type="number" name="tipo_cambio" id="inputTipoCambio" step="0.001" min="0" class="input-field text-xs" placeholder="Ej. 3.800" value="1.000">
                            </x-form-group>
                        </div>

                        <!-- Sección de Cuotas al Crédito -->
                        <div id="seccionCuotasCredito" class="mt-6 pt-5 border-t border-slate-200" style="display: none;">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                                <div>
                                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                        <i class="fas fa-calendar-alt text-amber-500"></i> Cuotas de la Factura (Crédito)
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Ingrese las cuotas impresas en la factura con su fecha de vencimiento y monto.</p>
                                </div>
                                <button type="button" id="btnAgregarCuota" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-amber-50 text-amber-700 border border-amber-300 rounded-lg text-xs font-bold hover:bg-amber-100 transition shadow-xs">
                                    <i class="fas fa-plus"></i> Agregar Cuota
                                </button>
                            </div>

                            <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-xs">
                                <table class="w-full text-xs text-left" id="tablaCuotas">
                                    <thead class="bg-slate-50 text-slate-600 font-bold uppercase border-b border-slate-200">
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
                </x-card>

                <x-card>
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                        <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                            <i class="fas fa-boxes text-primary"></i> Detalle de Recepción
                        </h2>
                        <div class="flex items-center gap-3">
                            <div class="bg-amber-50 border border-amber-200 px-4 py-2 rounded-lg shadow-sm">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="inafecto_igv" id="checkInafecto" class="form-checkbox rounded text-amber-600 h-5 w-5 focus:ring-amber-500 border-amber-300 transition-colors" onchange="recalcularTotales()">
                                    <span class="ml-2 text-xs text-amber-800 font-bold tracking-wide uppercase">No afecto al IGV</span>
                                </label>
                            </div>
                            <div class="bg-indigo-50 border border-indigo-200 px-4 py-2 rounded-lg shadow-sm">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="igv_incluido" id="checkIgv" class="form-checkbox rounded text-indigo-600 h-5 w-5 focus:ring-indigo-500 border-indigo-300 transition-colors" onchange="recalcularTotales()">
                                    <span class="ml-2 text-xs text-indigo-800 font-bold tracking-wide uppercase">Precios Incluyen IGV</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="rounded-lg border border-slate-200 mb-3 overflow-x-auto">
                            <table class="w-full text-left border-collapse table-fixed" id="tablaProductos">
                                <colgroup>
                                    <col class="w-[4%]">
                                    <col class="w-[22%]">
                                    <col class="w-[12%]">
                                    <col class="w-[8%]">
                                    <col class="w-[7%]">
                                    <col class="w-[10%]">
                                    <col class="w-[10%]">
                                    <col class="w-[11%]">
                                    <col class="w-[11%]">
                                    <col class="w-[5%]">
                                </colgroup>
                                <thead>
                                    <tr class="bg-slate-900 text-[11px] uppercase text-slate-200 tracking-wider font-bold">
                                        <th class="p-2.5 font-bold text-center border-r border-slate-800">#</th>
                                        <th class="p-2.5 font-bold border-r border-slate-800">Producto</th>
                                        <th class="p-2.5 font-bold border-r border-slate-800">Almacén</th>
                                        <th class="p-2.5 font-bold text-center border-r border-slate-800">Cant.</th>
                                        <th class="p-2.5 font-bold text-center border-r border-slate-800">U.M.</th>
                                        <th class="p-2.5 font-bold text-center border-r border-slate-800">Lote</th>
                                        <th class="p-2.5 font-bold text-center border-r border-slate-800">Venc.</th>
                                        <th class="p-2.5 font-bold text-right border-r border-slate-800">P. Unit.</th>
                                        <th class="p-2.5 font-bold text-right border-r border-slate-800">Subtotal</th>
                                        <th class="p-2.5 font-bold text-center">Acc.</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100" id="tbodyProductos">
                                </tbody>
                            </table>
                        </div>
                        <button type="button" id="btnAgregarFila" class="w-full py-2 border-2 border-dashed border-slate-300 rounded-lg text-xs text-slate-500 font-semibold hover:border-primary hover:text-primary hover:bg-primary-50 transition-all flex justify-center items-center gap-1">
                            <i class="fas fa-plus-circle"></i> Buscar y agregar producto
                        </button>
                    </div>
                </x-card>
            </div>

            <div class="lg:col-span-4 xl:col-span-3">
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

<x-modal id="modalProducto" title="Buscar Producto" size="lg">
    <div>
        <select id="selectProductoModal" class="w-full" style="width:100%"></select>
    </div>
    <p class="text-xs text-slate-500 text-center mt-4">Seleccione un producto para agregarlo automáticamente a la tabla.</p>
    <x-slot:footer>
        <button type="button" id="btnCerrarProducto" class="btn-secondary">
            <i class="fas fa-check"></i> Finalizar
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

    function generarTemplateHTML(idx, codigo, nombre, um) {
        let opcionesAlmacen = almacenesData.map(a => `<option value="${a.codigo}" ${a.codigo === 'ALM04' ? 'selected' : ''}>${a.descripcion}</option>`).join('');
        let opcionesUM = unidadesData.map(u => `<option value="${u.codigo}" ${u.codigo === um ? 'selected' : ''}>${u.codigo}</option>`).join('');
        return `
        <tr class="fila-producto">
            <td class="p-1 text-center">
                <span class="text-xs font-bold text-slate-500 row-item-number">${idx + 1}</span>
            </td>
            <td class="p-1">
                <span class="texto-prod text-xs font-medium text-slate-800 truncate block" title="${nombre}">${nombre}</span>
                <input type="hidden" class="input-cod" name="productos[${idx}][codigo]" value="${codigo}">
            </td>
            <td class="p-1">
                <select class="w-full border border-slate-200 bg-slate-50 rounded-md text-xs select-alm" style="height:28px" name="productos[${idx}][codigo_almacen]">
                    ${opcionesAlmacen}
                </select>
            </td>
            <td class="p-1">
                <input type="number" step="0.01" min="0.01" class="w-full border border-slate-200 bg-slate-50 text-center rounded-md text-xs input-cant" style="height:28px" name="productos[${idx}][cantidad]">
            </td>
            <td class="p-1">
                <select class="w-full border border-slate-200 bg-slate-50 rounded-md text-xs select-um" style="height:28px" name="productos[${idx}][codigo_unidad_medida]">
                    ${opcionesUM}
                </select>
            </td>
            <td class="p-1">
                <input type="text" class="w-full border border-slate-200 bg-slate-50 rounded-md text-xs text-center" style="height:28px" placeholder="Lote" name="productos[${idx}][lote]">
            </td>
            <td class="p-1">
                <input type="date" class="w-full border border-slate-200 bg-slate-50 rounded-md text-xs text-center" style="height:28px" name="productos[${idx}][fecha_vencimiento]" max="{{ date('Y-m-d') }}">
            </td>
            <td class="p-1">
                <input type="number" step="any" min="0" class="w-full border border-slate-200 bg-slate-50 text-right rounded-md text-xs text-primary font-semibold input-prec" style="height:28px" name="productos[${idx}][precio]" placeholder="0.00">
            </td>
            <td class="p-1">
                <input type="number" step="0.01" min="0" class="w-full border border-slate-200 bg-white text-right rounded-md text-xs font-bold text-slate-800 input-sub focus:ring-primary focus:border-primary shadow-xs" style="height:28px" name="productos[${idx}][subtotal]" value="0.00" placeholder="0.00">
            </td>
            <td class="p-1 text-center">
                <button type="button" class="text-slate-400 hover:text-red-500 btn-del text-xs" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
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

    function cerrarModalProducto() {
        cerrarModal('modalProducto');
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

    $(document).ready(function () {
        tablaBody = document.getElementById('tbodyProductos');
        searchUrl = '/productos/search-ajax';
        almacenesData = {!! json_encode($almacenes->map(fn($a) => ['codigo' => $a->codigo_almacen, 'descripcion' => $a->descripcion])->all()) !!};
        unidadesData = {!! json_encode($unidades_medida->map(fn($u) => ['codigo' => $u->codigo, 'descripcion' => $u->descripcion])->all()) !!};

        // Inicializar estado de campos de cuotas según la condición seleccionada
        if ($('#selectCondicionPago').val() === 'CREDITO') {
            $('#seccionCuotasCredito :input').prop('disabled', false);
        } else {
            $('#seccionCuotasCredito :input').prop('disabled', true);
        }

        if (typeof $().select2 !== 'undefined') {
            $('#selectProductoModal').select2({
                ajax: {
                    url: searchUrl,
                    dataType: 'json',
                    delay: 300,
                    data: function(p) { return {q: p.term}; },
                    processResults: function(d) { return {results: d}; },
                    cache: true
                },
                minimumInputLength: 0,
                placeholder: 'Buscar por código o nombre...',
                width: '100%',
                dropdownParent: $('#modalProducto')
            });

            $('#selectProductoModal').on('select2:select', function(e) {
                agregarFila(e.params.data);
                $('#selectProductoModal').val(null).trigger('change');
            });
        }

        $('#btnCerrarProducto').on('click', cerrarModalProducto);

        $('#btnAgregarFila').on('click', function () {
            $('#selectProductoModal').val(null).trigger('change');
            abrirModal('modalProducto');
            setTimeout(() => {
                if (typeof $('#selectProductoModal').select2 === 'function') {
                    $('#selectProductoModal').select2('open');
                }
            }, 250);
        });

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

        if (inputNumDoc) {
            inputNumDoc.addEventListener('blur', formatNumeroDoc);
            inputNumDoc.addEventListener('change', formatNumeroDoc);
        }

        if (inputSerieDoc) {
            inputSerieDoc.addEventListener('input', function () {
                this.value = this.value.toUpperCase();
            });
            inputSerieDoc.addEventListener('blur', function () {
                this.value = this.value.trim().toUpperCase();
            });
        }

        // Lógica de Condición de Pago y Cuotas al Crédito
        $('#selectCondicionPago').on('change', function() {
            const condicion = $(this).val();
            const seccionCuotas = $('#seccionCuotasCredito');
            
            if (condicion === 'CREDITO') {
                $('#seccionCuotasCredito :input').prop('disabled', false);
                seccionCuotas.slideDown(200);
                if ($('#tbodyCuotas .fila-cuota').length === 0) {
                    agregarFilaCuota();
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
                    data.detalles.forEach(d => {
                        const idx = filaIdx++;
                        const nombre = d.producto ? d.producto.descripcion : d.descripcion_producto;
                        let opcionesAlmacen = almacenesData.map(a => `<option value="${a.codigo}" ${a.codigo === d.codigo_almacen ? 'selected' : ''}>${a.descripcion}</option>`).join('');
                        let opcionesUM = unidadesData.map(u => `<option value="${u.codigo}" ${u.codigo === d.codigo_unidad_medida ? 'selected' : ''}>${u.codigo}</option>`).join('');
                        
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
                                <input type="text" class="w-full border border-slate-200 bg-slate-100 rounded-md text-xs text-center" style="height:28px" name="productos[${idx}][lote]" value="${d.lote || ''}" readonly tabindex="-1">
                            </td>
                            <td class="p-1">
                                <input type="date" class="w-full border border-slate-200 bg-slate-100 rounded-md text-xs text-center" style="height:28px" name="productos[${idx}][fecha_vencimiento]" value="${d.fecha_vencimiento ? d.fecha_vencimiento.split('T')[0] : ''}" readonly tabindex="-1" max="{{ date('Y-m-d') }}">
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
