@extends('layouts.app')
@section('title', 'Actividades de Producción')

@section('content')
<div class="container mx-auto pb-8 md:pb-10">
    <x-page-header title="Actividades de Producción" subtitle="Catálogo de tareas específicas de planta">
        <x-slot:actions>
            <a href="{{ route('actividades.create') }}" class="btn-primary">
                <i class="fas fa-plus"></i>
                <span class="hidden sm:inline ml-2">Nueva Actividad</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="bg-white p-3 md:p-4 rounded-xl shadow-md border border-slate-200/80 mb-5">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <i class="fas fa-search text-slate-400 text-sm"></i>
            </div>
            <input type="text" id="searchInput" value="{{ $search ?? '' }}" class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm text-slate-800 outline-none transition shadow-xs" placeholder="Buscar por código o descripción...">
        </div>
    </div>

    <div id="table-container" class="transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-md border border-slate-200/80 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[600px]">
                    <thead>
                        <tr class="bg-slate-800 text-white text-[11px] uppercase tracking-wider font-bold">
                            <th class="py-3 px-4 md:px-6 w-32">Código</th>
                            <th class="py-3 px-4 md:px-6">Descripción</th>
                            <th class="py-3 px-4 md:px-6 text-center w-36">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($actividades as $act)
                            <tr class="hover:bg-slate-50/80 transition duration-150">
                                <td class="px-4 md:px-6 py-2.5 font-bold text-xs md:text-sm text-slate-900 whitespace-nowrap">{{ $act->codigo }}</td>
                                <td class="px-4 md:px-6 py-2.5 text-xs md:text-sm font-medium text-slate-800 uppercase">{{ $act->descripcion }}</td>
                                <td class="px-4 md:px-6 py-2.5 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5 md:gap-2">
                                        <a href="{{ route('actividades.edit', $act->codigo) }}" class="inline-flex items-center justify-center w-8 h-8 text-primary bg-primary-50 hover:bg-primary hover:text-white rounded-lg transition-all shadow-2xs" title="Editar">
                                            <i class="fas fa-edit text-xs md:text-sm"></i>
                                        </a>
                                        <form action="{{ route('actividades.destroy', $act->codigo) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Está seguro de anular esta actividad?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center w-8 h-8 text-red-600 bg-red-50 hover:bg-red-600 hover:text-white rounded-lg transition-all shadow-2xs" title="Anular">
                                                <i class="fas fa-trash-alt text-xs md:text-sm"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-10">
                                    <x-empty-state icon="fa-tasks" message="No se encontraron actividades." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($actividades->hasPages())
                <div class="px-4 md:px-6 py-3.5 border-t border-slate-100 bg-slate-50">
                    {{ $actividades->links() }}
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