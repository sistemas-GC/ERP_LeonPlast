<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Maneja la restricción de acceso por rol.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $usuarioRol = Auth::user()->rol;
        $rolesUpper = array_map('strtoupper', $roles);

        // Compatibilidad con rutas estáticas (ej. role:Supervisor,Produccion) y acceso total a Administrador
        if (strtoupper($usuarioRol) === 'ADMINISTRADOR' || (!empty($rolesUpper) && in_array(strtoupper($usuarioRol), $rolesUpper))) {
            return $next($request);
        }

        $routeName = $request->route()->getName();
        
        if ($routeName) {
            // 1. Primero intentar buscar el nombre exacto de la ruta en la base de datos
            $hasExactAccess = \Illuminate\Support\Facades\DB::table('roles')
                ->where('roles.nombre', $usuarioRol)
                ->join('rol_modulo', 'roles.id', '=', 'rol_modulo.rol_id')
                ->join('modulos', 'modulos.id', '=', 'rol_modulo.modulo_id')
                ->where('modulos.slug', $routeName)
                ->exists();

            if ($hasExactAccess) {
                return $next($request);
            }

            // 2. Si no es exacto, aplicar lógica de fallback para rutas anidadas (.create, .store, etc.)
            $parts = explode('.', $routeName);
            $slugToCheck = $parts[0] . '.index';
            
            // Lógica específica para el módulo de inventario que tiene submódulos
            if ($parts[0] === 'inventario' && isset($parts[1])) {
                if (in_array($parts[1], ['recepciones', 'procesar_recepcion'])) {
                    $slugToCheck = 'inventario.recepciones';
                } elseif (in_array($parts[1], ['ajuste', 'store_ajuste'])) {
                    $slugToCheck = 'inventario.ajuste';
                } elseif (in_array($parts[1], ['extornos', 'procesar_extorno'])) {
                    $slugToCheck = 'inventario.extornos';
                } elseif ($parts[1] === 'despachos') {
                    $slugToCheck = 'requerimientos_materiales.atender';
                } elseif ($parts[1] === 'transferencias') {
                    $slugToCheck = 'inventario.transferencias.index';
                } elseif ($parts[1] === 'alertas_stock') {
                    $slugToCheck = 'inventario.alertas_stock';
                } else {
                    $slugToCheck = 'inventario.' . $parts[1];
                }
            } elseif ($parts[0] === 'terceros' && isset($parts[1])) {
                $slugToCheck = 'terceros.' . $parts[1] . '.index';
            } elseif ($parts[0] === 'reportes' && isset($parts[1])) {
                if ($parts[1] === 'trazabilidad') {
                    $slugToCheck = 'reportes.trazabilidad';
                } else {
                    $slugToCheck = 'reportes.index';
                }
            } elseif ($parts[0] === 'admin' && isset($parts[1]) && $parts[1] === 'rutas_produccion') {
                $slugToCheck = 'admin.rutas_produccion.index';
            }

            // Validar acceso dinámico contra la base de datos con el fallback
            $hasAccess = \Illuminate\Support\Facades\DB::table('roles')
                ->where('roles.nombre', $usuarioRol)
                ->join('rol_modulo', 'roles.id', '=', 'rol_modulo.rol_id')
                ->join('modulos', 'modulos.id', '=', 'rol_modulo.modulo_id')
                ->where('modulos.slug', $slugToCheck)
                ->exists();

            if ($hasAccess) {
                return $next($request);
            }
        }

        return redirect()->route('dashboard')->with('error', 'No tienes permisos para acceder a este módulo.');
    }
}