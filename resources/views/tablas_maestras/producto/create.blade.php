@extends('layouts.app')

@section('title', 'Nuevo Producto')

@section('content')
<div class="container mx-auto max-w-3xl">
    <x-page-header title="Registrar Producto" subtitle="Cree un nuevo producto en el catálogo." />

    <x-card class="p-6">
        <form action="{{ route('productos.store') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="col-span-1">
                    <x-form-group label="Código" required :error="$errors->first('codigo')">
                        <input type="text" name="codigo" id="codigo" value="{{ old('codigo') }}" class="input-field uppercase @error('codigo') border-red-500 @enderror" placeholder="Ej: PROD-001" maxlength="15" required>
                        @error('codigo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </x-form-group>
                </div>
                
                <div class="col-span-1">
                    <x-form-group label="Tipo de Producto" required :error="$errors->first('codigo_tipo_producto')">
                        <select name="codigo_tipo_producto" id="codigo_tipo_producto" class="input-field @error('codigo_tipo_producto') border-red-500 @enderror" required>
                            <option value="">Seleccione...</option>
                            @foreach($tipos as $tipo)
                                <option value="{{ $tipo->codigo }}" {{ old('codigo_tipo_producto') == $tipo->codigo ? 'selected' : '' }}>{{ $tipo->descripcion }}</option>
                            @endforeach
                        </select>
                        @error('codigo_tipo_producto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </x-form-group>
                </div>

                <div class="col-span-1 md:col-span-2">
                    <x-form-group label="Descripción">
                        <input type="text" name="descripcion" id="descripcion" value="{{ old('descripcion') }}" class="input-field @error('descripcion') border-red-500 @enderror" placeholder="Nombre o detalle del artículo" maxlength="150">
                        @error('descripcion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </x-form-group>
                </div>

                <div class="col-span-1">
                    <x-form-group label="Unidad de Medida">
                        <select name="codigo_unidad_medida" id="codigo_unidad_medida" class="input-field @error('codigo_unidad_medida') border-red-500 @enderror">
                            <option value="">Ninguna / No aplica</option>
                            @foreach($unidades as $um)
                                <option value="{{ $um->codigo }}" {{ old('codigo_unidad_medida') == $um->codigo ? 'selected' : '' }}>{{ $um->descripcion }} ({{ $um->codigo }})</option>
                            @endforeach
                        </select>
                        @error('codigo_unidad_medida') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </x-form-group>
                </div>

                <div class="col-span-1">
                    <x-form-group label="Color">
                        <select name="codigo_color" id="codigo_color" class="input-field @error('codigo_color') border-red-500 @enderror">
                            <option value="">Ninguno / No aplica</option>
                            @foreach($colores as $color)
                                <option value="{{ $color->codigo }}" {{ old('codigo_color') == $color->codigo ? 'selected' : '' }}>{{ $color->descripcion }}</option>
                            @endforeach
                        </select>
                        @error('codigo_color') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </x-form-group>
                </div>

                <div class="col-span-1 md:col-span-2">
                    <x-form-group label="Peso Estándar por Unidad en Gramos (Conversión KG → UND)" :error="$errors->first('peso_unitario_gr')">
                        <div class="flex flex-wrap items-center gap-3">
                            <input type="number" name="peso_unitario_gr" id="peso_unitario_gr" value="{{ old('peso_unitario_gr') }}" min="0.01" step="0.01" class="input-field sm:w-56 @error('peso_unitario_gr') border-red-500 @enderror" placeholder="Ej: 1.99 gr" oninput="calcularEquivalenciaProducto(this.value)">
                            <span id="badge_equivalencia_prod" class="hidden px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold"></span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Opcional. Complete si el producto se controla en KG pero requiere conversión a unidades (ej. Cáscaras CA o Coladores CO).</p>
                        @error('peso_unitario_gr') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </x-form-group>
                </div>

                <div class="col-span-1 md:col-span-2 flex items-center mt-2 bg-slate-50 p-4 rounded-lg border border-slate-200">
                    <input type="hidden" name="es_producto_proceso" value="0">
                    <input type="checkbox" name="es_producto_proceso" id="es_producto_proceso" value="1" {{ old('es_producto_proceso') == '1' ? 'checked' : '' }} class="w-5 h-5 text-primary bg-white border-slate-300 rounded focus:ring-primary focus:ring-2 cursor-pointer">
                    <label for="es_producto_proceso" class="ml-3 text-sm font-medium text-slate-800 cursor-pointer">
                        Es Producto en Proceso (PEP)
                    </label>
                    <span class="ml-2 text-xs text-slate-500">(Marcar si este producto es un intermedio en la producción)</span>
                </div>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <a href="{{ route('productos.index') }}" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn-primary">Guardar</button>
            </div>
        </form>
    </x-card>
</div>
<script>
function calcularEquivalenciaProducto(val) {
    const badge = document.getElementById('badge_equivalencia_prod');
    const gr = parseFloat(val);
    if (gr > 0) {
        const undPorKg = Math.round(1000 / gr);
        badge.textContent = `1 KG ≈ ${undPorKg.toLocaleString('es-PE')} UND (${gr} gr/und)`;
        badge.classList.remove('hidden');
    } else {
        badge.classList.add('hidden');
    }
}
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('peso_unitario_gr');
    if (el && el.value) calcularEquivalenciaProducto(el.value);
});
</script>
@endsection