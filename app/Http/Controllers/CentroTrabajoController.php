<?php

namespace App\Http\Controllers;

use App\Models\CentroTrabajo;
use Illuminate\Http\Request;

class CentroTrabajoController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = CentroTrabajo::activos();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'LIKE', "%$search%")
                  ->orWhere('descripcion', 'LIKE', "%$search%");
            });
        }

        $centros = $query->orderBy('descripcion', 'asc')->paginate(10)->appends($request->all());

        return view('tablas_maestras.centros_trabajo.index', compact('centros', 'search'));
    }

    public function create()
    {
        return view('tablas_maestras.centros_trabajo.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|max:20|unique:centro_trabajo_produccion,codigo',
            'descripcion' => 'required|string|max:150',
        ]);

        CentroTrabajo::create([
            'codigo' => strtoupper($request->codigo),
            'descripcion' => $request->descripcion,
            'estado' => 1
        ]);

        return redirect()->route('centros_trabajo.index')->with('success', 'Centro de trabajo registrado.');
    }

    public function edit($codigo)
    {
        $centro = CentroTrabajo::findOrFail($codigo);
        return view('tablas_maestras.centros_trabajo.edit', compact('centro'));
    }

    public function update(Request $request, $codigo)
    {
        $centro = CentroTrabajo::findOrFail($codigo);

        $request->validate([
            'descripcion' => 'required|string|max:150',
        ]);

        $centro->update([
            'descripcion' => $request->descripcion
        ]);

        return redirect()->route('centros_trabajo.index')->with('success', 'Centro de trabajo actualizado.');
    }

    public function destroy($codigo)
    {
        $centro = CentroTrabajo::findOrFail($codigo);
        // Desactivación lógica
        $centro->update(['estado' => 0]);

        return redirect()->route('centros_trabajo.index')->with('success', 'Centro de trabajo anulado.');
    }
}