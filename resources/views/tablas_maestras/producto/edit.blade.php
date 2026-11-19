@extends('layouts.app')

@section('title', 'Editar Producto')

@section('content')
<div class="container mx-auto max-w-3xl">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-800">Editar Producto</h1>
        
    </div>

    <div class="bg-white rounded-xl shadow-md p-6 border-t-4 border-yellow-500">
        <form action="{{ route('productos.update', $producto->codigo) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Código</label>
                    <input type="text" value="{{ $producto->codigo }}" class="w-full px-4 py-2 border border-slate-300 bg-slate-100 text-slate-500 rounded-lg cursor-not-allowed" disabled readonly>
                    <p class="text-xs text-slate-500 mt-1"><i class="fas fa-info-circle"></i> El código no es editable.</p>
                </div>
                
                <div class="col-span-1">
                    <label for="codigo_tipo_producto" class="block text-sm font-medium text-slate-700 mb-1">
                        Tipo de Producto <span class="text-red-500">*</span>
                    </label>
                    <select name="codigo_tipo_producto" id="codigo_tipo_producto" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-yellow-500 focus:border-yellow-500 @error('codigo_tipo_producto') border-red-500 @enderror" required>
                        <option value="">Seleccione...</option>
                        @foreach($tipos as $tipo)
                            <option value="{{ $tipo->codigo }}" {{ old('codigo_tipo_producto', $producto->codigo_tipo_producto) == $tipo->codigo ? 'selected' : '' }}>
                                {{ $tipo->descripcion }}
                            </option>
                        @endforeach
                    </select>
                    @error('codigo_tipo_producto')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label for="descripcion" class="block text-sm font-medium text-slate-700 mb-1">
                        Descripción <span class="text-xs text-slate-400 font-normal">(Opcional)</span>
                    </label>
                    <input type="text" name="descripcion" id="descripcion" value="{{ old('descripcion', $producto->descripcion) }}" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-yellow-500 focus:border-yellow-500 @error('descripcion') border-red-500 @enderror" placeholder="Nombre o detalle del artículo" maxlength="150">
                    @error('descripcion')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-1">
                    <label for="codigo_unidad_medida" class="block text-sm font-medium text-slate-700 mb-1">
                        Unidad de Medida <span class="text-xs text-slate-400 font-normal">(Opcional)</span>
                    </label>
                    <select name="codigo_unidad_medida" id="codigo_unidad_medida" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-yellow-500 focus:border-yellow-500 @error('codigo_unidad_medida') border-red-500 @enderror">
                        <option value="">Ninguna / No aplica</option>
                        @foreach($unidades as $um)
                            <option value="{{ $um->codigo }}" {{ old('codigo_unidad_medida', $producto->codigo_unidad_medida) == $um->codigo ? 'selected' : '' }}>
                                {{ $um->descripcion }} ({{ $um->codigo }})
                            </option>
                        @endforeach
                    </select>
                    @error('codigo_unidad_medida')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-1">
                    <label for="codigo_color" class="block text-sm font-medium text-slate-700 mb-1">
                        Color <span class="text-xs text-slate-400 font-normal">(Opcional)</span>
                    </label>
                    <select name="codigo_color" id="codigo_color" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-yellow-500 focus:border-yellow-500 @error('codigo_color') border-red-500 @enderror">
                        <option value="">Ninguno / No aplica</option>
                        @foreach($colores as $color)
                            <option value="{{ $color->codigo }}" {{ old('codigo_color', $producto->codigo_color) == $color->codigo ? 'selected' : '' }}>
                                {{ $color->descripcion }}
                            </option>
                        @endforeach
                    </select>
                    @error('codigo_color')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label for="peso_unitario_gr" class="block text-sm font-medium text-slate-700 mb-1">
                        Peso Estándar por Unidad en Gramos (Conversión KG &rarr; UND) <span class="text-xs text-slate-400 font-normal">(Opcional)</span>
                    </label>
                    <div class="flex flex-wrap items-center gap-3">
                        <input type="number" name="peso_unitario_gr" id="peso_unitario_gr" value="{{ old('peso_unitario_gr', $producto->peso_unitario_gr ? rtrim(rtrim(number_format($producto->peso_unitario_gr, 4, '.', ''), '0'), '.') : '') }}" min="0.01" step="0.01" class="w-full sm:w-56 px-4 py-2 border border-slate-300 rounded-lg focus:ring-yellow-500 focus:border-yellow-500 @error('peso_unitario_gr') border-red-500 @enderror" placeholder="Ej: 1.99 gr" oninput="calcularEquivalenciaProducto(this.value)">
                        <span id="badge_equivalencia_prod" class="hidden px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold"></span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">Complete si el producto se controla en KG pero requiere conversión a unidades (ej. Cáscaras CA o Coladores CO).</p>
                    @error('peso_unitario_gr')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-1 md:col-span-2 flex items-center mt-2 bg-slate-50 p-4 rounded-lg border border-slate-200">
                    <input type="hidden" name="es_producto_proceso" value="0">
                    <input type="checkbox" name="es_producto_proceso" id="es_producto_proceso" value="1" {{ old('es_producto_proceso', $producto->es_producto_proceso) == '1' ? 'checked' : '' }} class="w-5 h-5 text-yellow-600 bg-white border-slate-300 rounded focus:ring-yellow-500 focus:ring-2 cursor-pointer">
                    <label for="es_producto_proceso" class="ml-3 text-sm font-medium text-slate-800 cursor-pointer">
                        Es Producto en Proceso (PEP)
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 mt-6 border-t border-slate-100">
                <a href="{{ route('productos.index') }}" class="px-6 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition">Cancelar</a>
                <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-2 px-8 rounded-lg shadow transition">
                    <i class="fas fa-sync-alt mr-2"></i> Actualizar Producto
                </button>
            </div>
        </form>
    </div>
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