<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Producto, TipoProducto, UnidadMedida, User, Color};
use Carbon\Carbon;

class ProductoController extends Controller
{
    /**
     * Listado de productos activos con sus relaciones cargadas, búsqueda y filtros.
     */
    public function index(Request $request)
    {
        // Limpiar filtro si el usuario lo solicita explícitamente
        if ($request->has('clear_filter')) {
            session()->forget(['producto_search', 'producto_tipo']);
            return redirect()->route('productos.index');
        }

        // Si hay una búsqueda o filtro enviada desde el formulario, se guarda en sesión
        if ($request->has('search') || $request->has('codigo_tipo_producto')) {
            session(['producto_search' => $request->input('search')]);
            session(['producto_tipo' => $request->input('codigo_tipo_producto')]);
        }

        // Leer los valores desde la sesión (o serán nulos si están limpios)
        $search = session('producto_search');
        $tipoFiltro = session('producto_tipo');

        // Inicializamos la consulta base
        $query = Producto::with(['tipo', 'unidad'])->where('estado', 1);

        // Filtro por búsqueda predictiva (Código o Descripción)
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'LIKE', "%{$search}%")
                  ->orWhere('descripcion', 'LIKE', "%{$search}%");
            });
        }

        // Filtro por tipo de producto
        if (!empty($tipoFiltro)) {
            $query->where('codigo_tipo_producto', $tipoFiltro);
        }

        // Ordenamiento dinámico
        $sort = $request->input('sort', 'descripcion'); // Por defecto ordena por descripción
        $order = $request->input('order', 'asc');
        
        $validSortColumns = ['codigo', 'descripcion', 'codigo_tipo_producto'];
        if (!in_array($sort, $validSortColumns)) {
            $sort = 'descripcion';
        }
        $order = strtolower($order) === 'desc' ? 'desc' : 'asc';

        // Ejecutamos la paginación
        $productos = $query->orderBy($sort, $order)->paginate(10);
        
        // Aseguramos que los enlaces de paginación conserven los filtros actuales
        $productos->appends([
            'search' => $search, 
            'codigo_tipo_producto' => $tipoFiltro,
            'sort' => $sort,
            'order' => $order
        ]);

        // Cargamos los tipos para el select del filtro
        $tipos = TipoProducto::where('estado', 1)->orderBy('descripcion', 'asc')->get();

        return view('tablas_maestras.producto.index', compact('productos', 'tipos', 'search', 'tipoFiltro'));
    }

    /**
     * Mostrar formulario de creación.
     */
    public function create()
    {
        $tipos = TipoProducto::where('estado', 1)->orderBy('descripcion', 'asc')->get();
        $unidades = UnidadMedida::where('estado', 1)->orderBy('descripcion', 'asc')->get();
        $colores = Color::where('activo', 1)->orderBy('descripcion', 'asc')->get();
        
        return view('tablas_maestras.producto.create', compact('tipos', 'unidades', 'colores'));
    }

    /**
     * Guardar el nuevo producto en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|max:15|unique:producto,codigo',
            'codigo_tipo_producto' => 'required|exists:tipo_producto,codigo',
            'descripcion' => 'nullable|string|max:150',
            'codigo_unidad_medida' => 'nullable|exists:unidad_medida,codigo',
            'codigo_color' => 'nullable|exists:color,codigo',
            'es_producto_proceso' => 'nullable|integer',
        ], [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.unique' => 'Este código de producto ya está registrado.',
            'codigo_tipo_producto.required' => 'Debe seleccionar un tipo de producto.',
            'codigo_tipo_producto.exists' => 'El tipo de producto seleccionado no es válido.',
        ]);

        Producto::create([
            'codigo' => strtoupper($request->codigo),
            'codigo_tipo_producto' => $request->codigo_tipo_producto,
            'descripcion' => $request->descripcion,
            'codigo_unidad_medida' => $request->codigo_unidad_medida,
            'codigo_color' => $request->codigo_color,
            'es_producto_proceso' => $request->es_producto_proceso ?? 0, 
            'estado' => 1,
            'fecha_creacion' => Carbon::now(),
        ]);

        return redirect()->route('productos.index')
            ->with('success', 'Producto registrado exitosamente.');
    }

    /**
     * Mostrar formulario de edición.
     */
    public function edit($codigo)
    {
        $producto = Producto::where('codigo', $codigo)->firstOrFail();
        
        if ($producto->estado == 0) {
            return redirect()->route('productos.index')->with('error', 'No se puede editar un producto anulado.');
        }

        $tipos = TipoProducto::where('estado', 1)->orderBy('descripcion', 'asc')->get();
        $unidades = UnidadMedida::where('estado', 1)->orderBy('descripcion', 'asc')->get();
        $colores = Color::where('activo', 1)->orderBy('descripcion', 'asc')->get();

        return view('tablas_maestras.producto.edit', compact('producto', 'tipos', 'unidades', 'colores'));
    }

    /**
     * Actualizar los datos del producto.
     */
    public function update(Request $request, $codigo)
    {
        $producto = Producto::where('codigo', $codigo)->firstOrFail();

        $request->validate([
            'codigo_tipo_producto' => 'required|exists:tipo_producto,codigo',
            'descripcion' => 'nullable|string|max:150',
            'codigo_unidad_medida' => 'nullable|exists:unidad_medida,codigo',
            'codigo_color' => 'nullable|exists:color,codigo',
            'es_producto_proceso' => 'nullable|integer',
        ], [
            'codigo_tipo_producto.required' => 'El tipo de producto es obligatorio.',
        ]);

        $producto->update([
            'codigo_tipo_producto' => $request->codigo_tipo_producto,
            'descripcion' => $request->descripcion,
            'codigo_unidad_medida' => $request->codigo_unidad_medida,
            'codigo_color' => $request->codigo_color,
            'es_producto_proceso' => $request->es_producto_proceso ?? 0,
        ]);

        return redirect()->route('productos.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * Búsqueda AJAX para Select2 (código + descripción + tipo + unidad).
     */
    public function searchAjax(Request $request)
    {
        $search = $request->input('q', '');
        $tipo = $request->input('tipo', '');

        if ($tipo === 'ACT') {
            $actividades = \App\Models\ActividadProduccion::activos()
                ->where(function ($q) use ($search) {
                    $q->where('codigo', 'LIKE', "%{$search}%")
                      ->orWhere('descripcion', 'LIKE', "%{$search}%");
                })
                ->orderBy('descripcion', 'asc')
                ->limit(30)
                ->get();

            if ($actividades->isNotEmpty()) {
                return response()->json(
                    $actividades->map(fn($a) => [
                        'id'                     => $a->codigo,
                        'text'                   => "[{$a->codigo}] {$a->descripcion}",
                        'codigo_tipo_producto'   => 'ACT',
                        'descripcion_tipo_producto' => 'ACTIVIDADES PRODUCCION',
                        'codigo_unidad_medida'   => 'UNI',
                        'unidad_medida'          => 'UNI',
                    ])
                );
            }
        }

        $sinActividades = $request->boolean('sin_actividades') || $request->has('sin_actividades') || $request->has('compras');
        $almacenFiltro = $request->input('almacen', $request->input('codigo_almacen', ''));
        $soloMateriasPrimas = $request->boolean('solo_materias_primas') || $request->has('requerimiento') || $request->has('materias_primas');

        $query = Producto::with(['tipo', 'unidad'])
            ->where('estado', 1)
            ->where(function ($q) use ($search) {
                $q->where('codigo', 'LIKE', "%{$search}%")
                  ->orWhere('descripcion', 'LIKE', "%{$search}%");
            });

        if ($tipo) {
            $query->where('codigo_tipo_producto', $tipo);
        } elseif ($soloMateriasPrimas) {
            // Requerimientos de Planta solo solicitan Materias Primas e Insumos
            $query->whereIn('codigo_tipo_producto', ['MTP', 'INS']);
        } elseif ($sinActividades || true) {
            // Por defecto, la búsqueda general de productos no debe incluir actividades operativas
            $query->where('codigo_tipo_producto', '!=', 'ACT')
                  ->where('codigo', 'NOT LIKE', 'ACT-%');
        }

        // Si se solicita filtrar por almacén específico (ej: ALM03 Materias Primas)
        if ($almacenFiltro) {
            $query->whereExists(function ($sub) use ($almacenFiltro) {
                $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('inventario')
                    ->whereColumn('inventario.codigo_producto', 'producto.codigo')
                    ->where('inventario.codigo_almacen', $almacenFiltro);
            });
        }

        // Subquery para obtener el stock disponible en almacén de materias primas
        $almacenParaStock = $almacenFiltro ?: ($soloMateriasPrimas ? 'ALM03' : null);
        if ($almacenParaStock) {
            $query->select('producto.*')->selectSub(function ($sub) use ($almacenParaStock) {
                $sub->from('inventario')
                    ->selectRaw('COALESCE(SUM(stock_actual), 0)')
                    ->whereColumn('inventario.codigo_producto', 'producto.codigo')
                    ->where('inventario.codigo_almacen', $almacenParaStock);
            }, 'stock_almacen');
        }

        $productos = $query->orderBy('descripcion', 'asc')
            ->limit(35)
            ->get();

        return response()->json(
            $productos->map(function ($p) use ($almacenParaStock) {
                $stock = isset($p->stock_almacen) ? floatval($p->stock_almacen) : null;
                $um = $p->unidad?->codigo ?? $p->codigo_unidad_medida ?? '';
                $tipoDesc = $p->tipo?->descripcion ?? $p->codigo_tipo_producto ?? '';
                
                $labelDesc = "[{$p->codigo}] {$p->descripcion}";
                if ($stock !== null) {
                    $labelDesc .= " — Stock: " . number_format($stock, 2) . " " . $um;
                }

                return [
                    'id'                        => $p->codigo,
                    'text'                      => $labelDesc,
                    'descripcion'               => $p->descripcion,
                    'codigo_tipo_producto'      => $p->codigo_tipo_producto,
                    'descripcion_tipo_producto' => $tipoDesc,
                    'codigo_unidad_medida'      => $p->codigo_unidad_medida,
                    'unidad_medida'             => $um,
                    'stock'                     => $stock,
                ];
            })
        );
    }

    /**
     * Anulación lógica del registro.
     */
    public function destroy($codigo)
    {
        $producto = Producto::where('codigo', $codigo)->firstOrFail();
        
        $producto->estado = 0;
        $producto->save();

        return redirect()->route('productos.index')
            ->with('success', 'Registro anulado correctamente.');
    }
}