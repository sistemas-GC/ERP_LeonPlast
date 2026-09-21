<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductoProceso;
use App\Models\Molde;
use Illuminate\Support\Facades\DB;

class ProductoProcesoController extends Controller
{
    public function index()
    {
        $productos_proceso = ProductoProceso::where('estado', 1)
            ->withCount('moldes')
            ->orderBy('codigo', 'asc')
            ->get();

        return view('tablas_maestras.productos_proceso.index', compact('productos_proceso'));
    }

    public function create()
    {
        $moldes = Molde::activos()->orderBy('descripcion', 'asc')->get();
        $siguiente_codigo = (ProductoProceso::max('codigo') ?? 0) + 1;

        return view('tablas_maestras.productos_proceso.create', compact('moldes', 'siguiente_codigo'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo' => 'nullable|integer|min:1|unique:producto_proceso,codigo',
            'descripcion' => 'required|string|max:100',
            'moldes' => 'nullable|array',
            'moldes.*' => 'string|exists:molde,codigo',
        ], [
            'codigo.integer' => 'El código debe ser un número entero (ej: 19).',
            'codigo.min' => 'El código numérico debe ser mayor a 0.',
            'codigo.unique' => 'Este código numérico ya existe en el sistema.',
            'descripcion.required' => 'La descripción es obligatoria.',
        ]);

        try {
            DB::beginTransaction();

            $codigo = $request->filled('codigo')
                ? (int) $request->codigo
                : ((ProductoProceso::max('codigo') ?? 0) + 1);

            // Verificar si el código auto-calculado ya existe
            if (!$request->filled('codigo') && ProductoProceso::where('codigo', $codigo)->exists()) {
                $codigo = (ProductoProceso::max('codigo') ?? 0) + 1;
            }

            $producto = ProductoProceso::create([
                'codigo' => $codigo,
                'descripcion' => trim($request->descripcion),
                'estado' => 1,
            ]);

            // Guardar moldes seleccionados
            if ($request->filled('moldes')) {
                $inserts = [];
                foreach ($request->moldes as $codigoMolde) {
                    $inserts[] = [
                        'codigo_producto_proceso' => $producto->codigo,
                        'codigo_molde' => $codigoMolde,
                    ];
                }
                DB::table('producto_molde')->insert($inserts);
            }

            DB::commit();

            return redirect()->route('productos_proceso.index')
                ->with('success', 'Producto de proceso creado correctamente con código #' . $producto->codigo . '.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al crear: ' . $e->getMessage());
        }
    }

    public function edit($codigo)
    {
        $producto = ProductoProceso::where('codigo', $codigo)->firstOrFail();
        
        if ($producto->estado == 0) {
            return redirect()->route('productos_proceso.index')
                ->with('error', 'No se puede editar un registro anulado.');
        }

        $moldes = Molde::activos()->orderBy('descripcion', 'asc')->get();
        $moldes_vinculados = DB::table('producto_molde')
            ->where('codigo_producto_proceso', $codigo)
            ->pluck('codigo_molde')
            ->toArray();

        return view('tablas_maestras.productos_proceso.edit', compact('producto', 'moldes', 'moldes_vinculados'));
    }

    public function update(Request $request, $codigo)
    {
        $producto = ProductoProceso::where('codigo', $codigo)->firstOrFail();

        $request->validate([
            'descripcion' => 'required|string|max:100',
            'moldes' => 'nullable|array',
            'moldes.*' => 'string|exists:molde,codigo',
        ], [
            'descripcion.required' => 'La descripción es obligatoria.',
        ]);

        try {
            DB::beginTransaction();

            $producto->update([
                'descripcion' => $request->descripcion,
            ]);

            // Sincronizar moldes: eliminar existentes y reinsertar
            DB::table('producto_molde')->where('codigo_producto_proceso', $codigo)->delete();

            if ($request->filled('moldes')) {
                $inserts = [];
                foreach ($request->moldes as $codigoMolde) {
                    $inserts[] = [
                        'codigo_producto_proceso' => $codigo,
                        'codigo_molde' => $codigoMolde,
                    ];
                }
                DB::table('producto_molde')->insert($inserts);
            }

            DB::commit();

            return redirect()->route('productos_proceso.index')
                ->with('success', 'Producto de proceso actualizado.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    public function destroy($codigo)
    {
        $producto = ProductoProceso::where('codigo', $codigo)->firstOrFail();
        
        $producto->estado = 0;
        $producto->save();

        return redirect()->route('productos_proceso.index')
            ->with('success', 'Registro anulado exitosamente.');
    }
}
