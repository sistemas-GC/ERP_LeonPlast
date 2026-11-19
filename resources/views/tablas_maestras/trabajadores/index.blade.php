@extends('layouts.app')
@section('title', 'Trabajadores')

@section('content')
<div class="container mx-auto pb-8 md:pb-10">
    <x-page-header title="Trabajadores" subtitle="Gestión de personal de planta y contratistas">
        <x-slot:actions>
            <a href="{{ route('trabajadores.create') }}" class="btn-primary">
                <i class="fas fa-plus"></i>
                <span class="hidden sm:inline ml-2">Nuevo</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="bg-white p-3 md:p-4 rounded-xl shadow-md border border-slate-200/80 mb-5">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <i class="fas fa-search text-slate-400 text-sm"></i>
            </div>
            <input type="text" id="searchInput" value="{{ $search ?? '' }}" class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm text-slate-800 outline-none transition shadow-xs" placeholder="Buscar por código, nombre o empresa...">
        </div>
    </div>

    <div id="table-container" class="transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-md border border-slate-200/80 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead>
                        <tr class="bg-slate-800 text-white text-[11px] uppercase tracking-wider font-bold">
                            <th class="py-3 px-4 md:px-6 w-32">Código</th>
                            <th class="py-3 px-4 md:px-6">Nombre Completo</th>
                            <th class="py-3 px-4 md:px-6">Empresa</th>
                            <th class="py-3 px-4 md:px-6 text-right">Sueldo Base</th>
                            <th class="py-3 px-4 md:px-6 text-center w-36">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($trabajadores as $trabajador)
                            <tr class="hover:bg-slate-50/80 transition duration-150">
                                <td class="px-4 md:px-6 py-2.5 font-bold text-xs md:text-sm text-slate-900 whitespace-nowrap">{{ $trabajador->codigo }}</td>
                                <td class="px-4 md:px-6 py-2.5 text-xs md:text-sm font-medium text-slate-800 uppercase">{{ $trabajador->nombre }}</td>
                                <td class="px-4 md:px-6 py-2.5 text-xs text-slate-600 uppercase font-medium">{{ $trabajador->empresa ?? '-' }}</td>
                                <td class="px-4 md:px-6 py-2.5 text-xs md:text-sm font-semibold text-slate-900 text-right whitespace-nowrap">
                                    {{ $trabajador->sueldo ? 'S/ ' . number_format($trabajador->sueldo, 2) : 'No asignado' }}
                                </td>
                                <td class="px-4 md:px-6 py-2.5 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5 md:gap-2">
                                        <a href="{{ route('trabajadores.edit', $trabajador->codigo) }}" class="inline-flex items-center justify-center w-8 h-8 text-primary bg-primary-50 hover:bg-primary hover:text-white rounded-lg transition-all shadow-2xs" title="Editar">
                                            <i class="fas fa-edit text-xs md:text-sm"></i>
                                        </a>
                                        <form action="{{ route('trabajadores.destroy', $trabajador->codigo) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Está seguro de dar de baja a este trabajador?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center w-8 h-8 text-red-600 bg-red-50 hover:bg-red-600 hover:text-white rounded-lg transition-all shadow-2xs" title="Dar de baja">
                                                 <i class="fas fa-trash-alt text-xs md:text-sm"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10">
                                    <x-empty-state icon="fa-users" message="No se encontraron trabajadores registrados." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($trabajadores->hasPages())
                <div class="px-4 md:px-6 py-3.5 border-t border-slate-100 bg-slate-50">
                    {{ $trabajadores->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const tableContainer = document.getElementById('table-container');
        let timeout = null;

        function fetchResults(customUrl = null) {
            const url = customUrl || new URL(window.location.href);
            if (!customUrl) {
                const val = searchInput.value.trim();
                if (val) {
                    url.searchParams.set('search', val);
                } else {
                    url.searchParams.delete('search');
                }
                url.searchParams.delete('page');
            }
            
            tableContainer.classList.add('opacity-50', 'pointer-events-none');

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.text())
                .then(html => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const newTable = doc.getElementById('table-container');
                    if (newTable) {
                        tableContainer.innerHTML = newTable.innerHTML;
                        window.history.replaceState({}, '', url);
                    }
                })
                .catch(err => console.error('Error al buscar:', err))
                .finally(() => {
                    tableContainer.classList.remove('opacity-50', 'pointer-events-none');
                });
        }

        searchInput.addEventListener('input', () => {
            clearTimeout(timeout);
            timeout = setTimeout(() => fetchResults(), 350);
        });

        tableContainer.addEventListener('click', function(e) {
            const aTag = e.target.closest('nav[role="navigation"] a');
            if (aTag) {
                e.preventDefault();
                fetchResults(new URL(aTag.href));
            }
        });
    });
</script>
@endsection