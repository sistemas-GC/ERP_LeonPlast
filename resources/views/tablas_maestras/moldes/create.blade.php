@extends('layouts.app')
@section('title', 'Nuevo Molde')

@section('content')
<div class="container mx-auto max-w-2xl">
    <x-page-header title="Registrar Molde" subtitle="Añada un nuevo molde al catálogo." />

    <x-card class="p-6 md:p-8">
        <form action="{{ route('moldes.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <x-form-group label="Código del Molde" required :error="$errors->first('codigo')">
                <input type="text" name="codigo" id="codigo" value="{{ old('codigo') }}" class="input-field uppercase @error('codigo') border-red-500 @enderror" placeholder="Ej: MLD-001" required>
                @error('codigo') <p class="text-red-500 text-xs mt-2 font-medium">{{ $message }}</p> @enderror
            </x-form-group>

            <x-form-group label="Descripción" required :error="$errors->first('descripcion')">
                <input type="text" name="descripcion" id="descripcion" value="{{ old('descripcion') }}" class="input-field @error('descripcion') border-red-500 @enderror" placeholder="Ej: Molde para Base Plástica" required>
                @error('descripcion') <p class="text-red-500 text-xs mt-2 font-medium">{{ $message }}</p> @enderror
            </x-form-group>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-group label="Cantidad de Cavidades" :error="$errors->first('cavidades')">
                    <input type="number" name="cavidades" id="cavidades" value="{{ old('cavidades') }}" min="1" step="1" class="input-field @error('cavidades') border-red-500 @enderror" placeholder="Ej: 4, 8, 16, 20, 32...">
                    @error('cavidades') <p class="text-red-500 text-xs mt-2 font-medium">{{ $message }}</p> @enderror
                </x-form-group>

                <x-form-group label="Peso por Cavidad / Unidad (gr)" :error="$errors->first('peso_unitario_gr')">
                    <input type="number" name="peso_unitario_gr" id="peso_unitario_gr" value="{{ old('peso_unitario_gr') }}" min="0.01" step="0.01" class="input-field @error('peso_unitario_gr') border-red-500 @enderror" placeholder="Ej: 1.99">
                    <p class="text-[11px] text-slate-400 mt-1">Peso estándar en gramos de 1 pieza inyectada.</p>
                    @error('peso_unitario_gr') <p class="text-red-500 text-xs mt-2 font-medium">{{ $message }}</p> @enderror
                </x-form-group>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('moldes.index') }}" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i> Guardar Molde</button>
            </div>
        </form>
    </x-card>
</div>
@endsection