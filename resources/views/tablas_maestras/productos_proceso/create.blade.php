@extends('layouts.app')

@section('title', 'Nuevo Producto de Proceso')

@section('content')
<div class="container mx-auto pb-8 md:pb-10 max-w-2xl">
    <x-page-header title="Nuevo Producto de Proceso" subtitle="Registrar una nueva familia / línea de producto en proceso" />

    <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 overflow-hidden">
        <div class="p-6">
            <form action="{{ route('productos_proceso.store') }}" method="POST" class="space-y-6">
                @csrf
                
                <x-form-group label="Código Numérico" required :error="$errors->first('codigo')">
                    <input type="number" min="1" step="1" name="codigo" id="codigo" value="{{ old('codigo', $siguiente_codigo) }}" class="input-field font-semibold text-slate-800 @error('codigo') border-red-500 @enderror" placeholder="Ej: {{ $siguiente_codigo }}" required>
                    <p class="text-xs text-slate-500 mt-1">Identificador numérico de la línea de proceso (sugerido automáticamente: #{{ $siguiente_codigo }}).</p>
                    @error('codigo')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </x-form-group>

                <x-form-group label="Descripción" required :error="$errors->first('descripcion')">
                    <input type="text" name="descripcion" id="descripcion" value="{{ old('descripcion') }}" class="input-field @error('descripcion') border-red-500 @enderror" placeholder="Ej: ENCARTONADO GANCHO LEON PLAST" maxlength="100" required>
                    @error('descripcion')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </x-form-group>

                {{-- Sección de Moldes --}}
                <div class="pt-4 border-t border-slate-100">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="fas fa-cogs text-slate-500"></i>
                        <label class="text-sm font-semibold text-slate-700">Moldes Asignados</label>
                        <span class="text-xs text-slate-500">(opcional — seleccione los moldes para este producto)</span>
                    </div>

                    @if($moldes->count() > 0)
                        {{-- Buscador de moldes --}}
                        <div class="mb-3">
                            <input type="text" id="buscar_molde" placeholder="Buscar molde..." class="w-full border-slate-300 rounded-md shadow-sm text-sm focus:ring-primary focus:border-primary py-2 px-3">
                        </div>

                        <div class="max-h-64 overflow-y-auto border border-slate-200 rounded-lg bg-slate-50/50 p-3 space-y-1" id="lista_moldes">
                            @foreach($moldes as $molde)
                                <label class="flex items-center gap-3 p-2 rounded-md hover:bg-white cursor-pointer transition molde-item" data-desc="{{ strtolower($molde->codigo . ' ' . $molde->descripcion) }}">
                                    <input type="checkbox" name="moldes[]" value="{{ $molde->codigo }}"
                                        {{ in_array($molde->codigo, old('moldes', [])) ? 'checked' : '' }}
                                        class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4">
                                    <div class="flex-1 min-w-0">
                                        <span class="text-sm font-semibold text-slate-800">{{ $molde->codigo }}</span>
                                        <span class="text-sm text-slate-600 ml-1">— {{ $molde->descripcion }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <p class="text-xs text-slate-500 mt-2">
                            <span id="conteo_moldes">0</span> molde(s) seleccionado(s) de {{ $moldes->count() }} disponibles
                        </p>
                    @else
                        <div class="text-center py-6 text-slate-500 bg-slate-50 rounded-lg border border-dashed border-slate-300">
                            <i class="fas fa-inbox text-2xl mb-2"></i>
                            <p class="text-sm">No hay moldes activos registrados en el sistema.</p>
                        </div>
                    @endif
                </div>

                <div class="mt-8 flex items-center justify-end space-x-3 pt-6 border-t border-slate-100">
                    <a href="{{ route('productos_proceso.index') }}" class="btn-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save mr-2"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const buscar = document.getElementById('buscar_molde');
        const items = document.querySelectorAll('.molde-item');
        const conteo = document.getElementById('conteo_moldes');
        const checkboxes = document.querySelectorAll('input[name="moldes[]"]');

        // Filtrar moldes por búsqueda
        if (buscar) {
            buscar.addEventListener('input', function() {
                const texto = this.value.toLowerCase();
                items.forEach(function(item) {
                    const desc = item.getAttribute('data-desc');
                    item.style.display = desc.includes(texto) ? '' : 'none';
                });
            });
        }

        // Actualizar conteo al cambiar checkboxes
        checkboxes.forEach(function(cb) {
            cb.addEventListener('change', function() {
                const seleccionados = document.querySelectorAll('input[name="moldes[]"]:checked').length;
                if (conteo) conteo.textContent = seleccionados;
            });
        });

        // Inicializar conteo
        const inicial = document.querySelectorAll('input[name="moldes[]"]:checked').length;
        if (conteo) conteo.textContent = inicial;
    });
</script>
@endpush
@endsection
