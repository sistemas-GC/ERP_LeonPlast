@extends('layouts.app')

@section('title', 'Nueva Orden de Producción')

@section('content')
<div class="container mx-auto max-w-3xl pb-8 md:pb-10">
    <div class="flex justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Nueva Orden de Producción</h1>
            <p class="text-xs sm:text-sm text-slate-600">Complete los campos para registrar una nueva orden</p>
        </div>
        <a href="{{ route('produccion.ordenes.index') }}" class="shrink-0 flex items-center justify-center bg-slate-500 hover:bg-slate-600 text-white font-semibold py-2 px-3 sm:px-4 rounded-lg shadow transition">
            <i class="fas fa-arrow-left"></i>
            <span class="hidden sm:inline ml-2">Volver</span>
        </a>
    </div>

    @if($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 md:p-4 mb-6 rounded shadow-sm text-sm md:text-base">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-md p-6 border-t-4 border-primary">
        <form action="{{ route('produccion.ordenes.store') }}" method="POST">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Nro OP --}}
                    <div class="col-span-1 md:col-span-2">
                        <label for="codigo_op" class="block text-sm font-medium text-slate-700 mb-1">
                            📌 Número de OP <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="codigo_op" id="codigo_op" required placeholder="Ej: OP-2024-001" value="{{ old('codigo_op', $codigo_op_sugerido ?? '') }}"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-primary focus:border-primary">
                        <p class="mt-1 text-xs text-slate-500">Identificador único de la orden</p>
                    </div>

                    {{-- Fecha --}}
                    <div>
                        <label for="fecha" class="block text-sm font-medium text-slate-700 mb-1">
                            📅 Fecha <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="fecha" id="fecha" required value="{{ old('fecha', date('Y-m-d')) }}"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>

                    {{-- Hora Inicio --}}
                    <div>
                        <label for="hora_inicio" class="block text-sm font-medium text-slate-700 mb-1">
                            ⏰ Hora de OP
                        </label>
                        <input type="time" name="hora_inicio" id="hora_inicio" value="{{ old('hora_inicio', date('H:i')) }}"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-primary focus:border-primary">
                        <p class="mt-1 text-xs text-slate-500">Hora estimada de inicio</p>
                    </div>

                    {{-- Producto --}}
                    <div>
                        <label for="codigo_producto_proceso" class="block text-sm font-medium text-slate-700 mb-1">
                            🏭 Producto <span class="text-red-500">*</span>
                        </label>
                        <select name="codigo_producto_proceso" id="codigo_producto_proceso" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-primary focus:border-primary">
                            <option value="">Seleccionar producto...</option>
                            @foreach($productos_proceso as $producto)
                                <option value="{{ $producto->codigo }}" {{ old('codigo_producto_proceso') == $producto->codigo ? 'selected' : '' }}>
                                    {{ $producto->descripcion }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Cantidad / Peso Inicial --}}
                    <div id="container_cantidad" style="display: none;">
                        <label for="cantidad" class="block text-sm font-medium text-slate-700 mb-1">
                            <i class="fas fa-weight-hanging text-slate-500 mr-1"></i> Peso del Rollo (KG)
                        </label>
                        <input type="number" step="0.01" name="cantidad" id="cantidad" value="{{ old('cantidad') }}"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="Ej: 50.50">
                        <p class="mt-1 text-xs text-slate-500">Peso total del rollo a procesar.</p>
                    </div>

                    {{-- Tara / Peso Empaque --}}
                    <div id="container_tara" style="display: none;">
                        <label for="tara" class="block text-sm font-medium text-slate-700 mb-1">
                            <i class="fas fa-box text-slate-500 mr-1"></i> Tara / Peso Empaque (KG)
                        </label>
                        <input type="number" step="0.01" name="tara" id="tara" value="{{ old('tara') }}"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="Ej: 1.20">
                        <p class="mt-1 text-xs text-slate-500">Opcional. Se descontará del Peso del Rollo.</p>
                    </div>

                    {{-- Observaciones --}}
                    <div class="col-span-1 md:col-span-2">
                        <label for="texto_obs" class="block text-sm font-medium text-slate-700 mb-1">
                            📝 Observaciones
                        </label>
                        <textarea name="texto_obs" id="texto_obs" rows="3" placeholder="Información adicional relevante para esta orden..."
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-primary focus:border-primary">{{ old('texto_obs') }}</textarea>
                    </div>
                </div>

                <div class="mt-8 pt-5 border-t border-slate-200 flex justify-end space-x-3">
                    <a href="{{ route('produccion.ordenes.index') }}" class="bg-slate-500 hover:bg-slate-600 text-white font-semibold py-2 px-4 rounded-lg shadow transition">
                        Cancelar
                    </a>
                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-semibold py-2 px-4 rounded-lg shadow transition">
                        <i class="fas fa-save mr-2"></i> Crear Orden
                    </button>
                </div>
            </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectProducto = document.getElementById('codigo_producto_proceso');
        const containerCantidad = document.getElementById('container_cantidad');
        const containerTara = document.getElementById('container_tara');
        
        function toggleCantidad() {
            if (!selectProducto) return;
            const selectedOption = selectProducto.options[selectProducto.selectedIndex];
            if (!selectedOption || !selectedOption.value) {
                if (containerCantidad) containerCantidad.style.display = 'none';
                if (containerTara) containerTara.style.display = 'none';
                return;
            }
            
            const texto = selectedOption.text.toUpperCase();
            // Solo mostrar si es Troquelado (ej: CLIP GRANDE TROQUELADO, CLIP CHICO TROQUELADO)
            if (texto.includes('TROQUELADO')) {
                containerCantidad.style.display = 'block';
                containerTara.style.display = 'block';
            } else {
                containerCantidad.style.display = 'none';
                containerTara.style.display = 'none';
                const inputCantidad = document.getElementById('cantidad');
                const inputTara = document.getElementById('tara');
                if (inputCantidad) inputCantidad.value = '';
                if (inputTara) inputTara.value = '';
            }
        }
        
        selectProducto.addEventListener('change', toggleCantidad);
        toggleCantidad();
    });
</script>
@endsection
