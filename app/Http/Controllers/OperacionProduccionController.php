<?php

namespace App\Http\Controllers;

use App\Models\OperacionProduccion;
use Illuminate\Http\Request;

class OperacionProduccionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = OperacionProduccion::activos();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'LIKE', "%$search%")
                  ->orWhere('descripcion', 'LIKE', "%$search%");
            });
        }

        $operaciones = $query->orderBy('descripcion', 'asc')->paginate(10)->appends($request->all());

        return view('tablas_maestras.operacion_produccion.index', compact('operaciones', 'search'));
    }

    public function create()
    {
        return view('tablas_maestras.operacion_produccion.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|max:20|unique:operacion_produccion,codigo',
            'descripcion' => 'required|string|max:150',
        ]);

        OperacionProduccion::create([
            'codigo' => strtoupper($request->codigo),
            'descripcion' => $request->descripcion,
            'estado' => 1
        ]);

        return redirect()->route('operaciones_produccion.index')->with('success', 'Operación registrada correctamente.');
    }

    public function edit($codigo)
    {
        $operacion = OperacionProduccion::findOrFail($codigo);
        return view('tablas_maestras.operacion_produccion.edit', compact('operacion'));
    }

    public function update(Request $request, $codigo)
    {
        $operacion = OperacionProduccion::findOrFail($codigo);

        $request->validate([
            'descripcion' => 'required|string|max:150',
        ]);

        $operacion->update([
            'descripcion' => $request->descripcion
        ]);

        return redirect()->route('operaciones_produccion.index')->with('success', 'Operación actualizada correctamente.');
    }

    public function destroy($codigo)
    {
        $operacion = OperacionProduccion::findOrFail($codigo);
        // Soft Delete: Solo desactivamos el registro
        $operacion->update(['estado' => 0]);

        return redirect()->route('operaciones_produccion.index')->with('success', 'Operación anulada correctamente.');
    }
}