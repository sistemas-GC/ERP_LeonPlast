<?php

namespace App\Http\Controllers;

use App\Models\Color;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = Color::activos();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'LIKE', "%$search%")
                  ->orWhere('descripcion', 'LIKE', "%$search%");
            });
        }

        $colores = $query->orderBy('descripcion', 'asc')->paginate(10)->appends($request->all());

        return view('tablas_maestras.colores.index', compact('colores', 'search'));
    }

    public function create()
    {
        return view('tablas_maestras.colores.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|max:20|unique:color,codigo',
            'descripcion' => 'required|string|max:150',
        ], [
            'codigo.unique' => 'Este código de color ya existe en el sistema.',
            'codigo.required' => 'El código es obligatorio.',
            'descripcion.required' => 'La descripción es obligatoria.'
        ]);

        Color::create([
            'codigo' => strtoupper($request->codigo),
            'descripcion' => $request->descripcion,
            'activo' => 1
        ]);

        return redirect()->route('colores.index')->with('success', 'Color registrado correctamente.');
    }

    public function edit($codigo)
    {
        $color = Color::findOrFail($codigo);
        return view('tablas_maestras.colores.edit', compact('color'));
    }

    public function update(Request $request, $codigo)
    {
        $color = Color::findOrFail($codigo);

        $request->validate([
            'descripcion' => 'required|string|max:150',
        ]);

        $color->update([
            'descripcion' => $request->descripcion
        ]);

        return redirect()->route('colores.index')->with('success', 'Color actualizado correctamente.');
    }

    public function destroy($codigo)
    {
        $color = Color::findOrFail($codigo);
        // Desactivación lógica
        $color->update(['activo' => 0]);

        return redirect()->route('colores.index')->with('success', 'Color desactivado correctamente.');
    }
}