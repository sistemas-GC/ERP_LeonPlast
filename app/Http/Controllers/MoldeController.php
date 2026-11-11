<?php

namespace App\Http\Controllers;

use App\Models\Molde;
use Illuminate\Http\Request;

class MoldeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = Molde::activos();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'LIKE', "%$search%")
                  ->orWhere('descripcion', 'LIKE', "%$search%");
            });
        }

        $moldes = $query->orderBy('codigo', 'asc')->paginate(10)->appends($request->all());

        return view('tablas_maestras.moldes.index', compact('moldes', 'search'));
    }

    public function create()
    {
        return view('tablas_maestras.moldes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|max:20|unique:molde,codigo',
            'descripcion' => 'required|string|max:255',
        ], [
            'codigo.unique' => 'Este código de molde ya existe en el sistema.',
            'codigo.required' => 'El código es obligatorio.',
            'descripcion.required' => 'La descripción es obligatoria.'
        ]);

        Molde::create([
            'codigo' => strtoupper($request->codigo),
            'descripcion' => $request->descripcion,
            'activo' => 1
        ]);

        return redirect()->route('moldes.index')->with('success', 'Molde registrado correctamente.');
    }

    public function edit($codigo)
    {
        $molde = Molde::findOrFail($codigo);
        return view('tablas_maestras.moldes.edit', compact('molde'));
    }

    public function update(Request $request, $codigo)
    {
        $molde = Molde::findOrFail($codigo);

        $request->validate([
            'descripcion' => 'required|string|max:255',
        ]);

        $molde->update([
            'descripcion' => $request->descripcion
        ]);

        return redirect()->route('moldes.index')->with('success', 'Molde actualizado correctamente.');
    }

    public function destroy($codigo)
    {
        $molde = Molde::findOrFail($codigo);
        // Desactivación lógica usando el campo 'activo'
        $molde->update(['activo' => 0]);

        return redirect()->route('moldes.index')->with('success', 'Molde desactivado correctamente.');
    }
}