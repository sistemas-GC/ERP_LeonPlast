<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModuloSeeder extends Seeder
{
    public function run(): void
    {
        $modulos = [
            // Tablas Maestras
            ['nombre' => 'Procesos de producción', 'slug' => 'procesos_produccion.index', 'grupo' => 'Tablas Maestras', 'icono' => 'fas fa-cogs'],
            ['nombre' => 'Fórmulas', 'slug' => 'formulas.index', 'grupo' => 'Tablas Maestras', 'icono' => 'fas fa-flask'],
            ['nombre' => 'Productos', 'slug' => 'productos.index', 'grupo' => 'Tablas Maestras', 'icono' => 'fas fa-box'],
            ['nombre' => 'Tipo de productos', 'slug' => 'tipos_producto.index', 'grupo' => 'Tablas Maestras', 'icono' => 'fas fa-tags'],
            ['nombre' => 'Unidades de medida', 'slug' => 'unidades_medida.index', 'grupo' => 'Tablas Maestras', 'icono' => 'fas fa-ruler'],
            ['nombre' => 'Operaciones', 'slug' => 'operaciones_produccion.index', 'grupo' => 'Tablas Maestras', 'icono' => 'fas fa-wrench'],
            ['nombre' => 'Centros de trabajo', 'slug' => 'centros_trabajo.index', 'grupo' => 'Tablas Maestras', 'icono' => 'fas fa-building'],
            ['nombre' => 'Trabajadores', 'slug' => 'trabajadores.index', 'grupo' => 'Tablas Maestras', 'icono' => 'fas fa-users'],
            ['nombre' => 'Actividades', 'slug' => 'actividades.index', 'grupo' => 'Tablas Maestras', 'icono' => 'fas fa-tasks'],
            ['nombre' => 'Moldes', 'slug' => 'moldes.index', 'grupo' => 'Tablas Maestras', 'icono' => 'fas fa-cube'],
            ['nombre' => 'Colores', 'slug' => 'colores.index', 'grupo' => 'Tablas Maestras', 'icono' => 'fas fa-palette'],
            ['nombre' => 'Mapeo a Terceros', 'slug' => 'mapeo-terceros.index', 'grupo' => 'Tablas Maestras', 'icono' => 'fas fa-network-wired'],

            // Cuentas por Pagar
            ['nombre' => 'Proveedores', 'slug' => 'proveedores.index', 'grupo' => 'Cuentas por Pagar', 'icono' => 'fas fa-truck'],
            ['nombre' => 'Compras', 'slug' => 'compras.index', 'grupo' => 'Cuentas por Pagar', 'icono' => 'fas fa-shopping-cart'],
            ['nombre' => 'Guías de Remisión Compras', 'slug' => 'guia_compras.index', 'grupo' => 'Cuentas por Pagar', 'icono' => 'fas fa-shipping-fast'],

            // Inventario
            ['nombre' => 'Existencias', 'slug' => 'inventario.index', 'grupo' => 'Inventario', 'icono' => 'fas fa-boxes'],
            ['nombre' => 'Despachos', 'slug' => 'inventario.despachos', 'grupo' => 'Inventario', 'icono' => 'fas fa-box-open'],
            ['nombre' => 'Alertas de Stock', 'slug' => 'inventario.alertas_stock', 'grupo' => 'Inventario', 'icono' => 'fas fa-exclamation-triangle'],
            ['nombre' => 'Recepciones Pendientes', 'slug' => 'inventario.recepciones', 'grupo' => 'Inventario', 'icono' => 'fas fa-clipboard-check'],
            ['nombre' => 'Ajustes de Inventario', 'slug' => 'inventario.ajuste', 'grupo' => 'Inventario', 'icono' => 'fas fa-sliders-h'],
            ['nombre' => 'Extornos y Auditoría', 'slug' => 'inventario.extornos', 'grupo' => 'Inventario', 'icono' => 'fas fa-undo-alt'],
            ['nombre' => 'Transferencias Almacén', 'slug' => 'inventario.transferencias.index', 'grupo' => 'Inventario', 'icono' => 'fas fa-exchange-alt'],

            // Contabilidad
            ['nombre' => 'Kardex de Movimientos', 'slug' => 'inventario.kardex', 'grupo' => 'Contabilidad', 'icono' => 'fas fa-book-open'],

            // Producción
            ['nombre' => 'Órdenes de Producción', 'slug' => 'produccion.ordenes.index', 'grupo' => 'Producción', 'icono' => 'fas fa-tasks'],
            ['nombre' => 'Transferencias Producción', 'slug' => 'produccion.transferencias.index', 'grupo' => 'Producción', 'icono' => 'fas fa-dolly-flatbed'],
            ['nombre' => 'Rutas de Producción', 'slug' => 'admin.rutas_produccion.index', 'grupo' => 'Producción', 'icono' => 'fas fa-route'],
            ['nombre' => 'Mermas y Recuperados', 'slug' => 'mermas.index', 'grupo' => 'Producción', 'icono' => 'fas fa-recycle'],
            ['nombre' => 'Ingresos de Producción', 'slug' => 'produccion.ingresos', 'grupo' => 'Producción', 'icono' => 'fas fa-industry'],
            ['nombre' => 'Requerimientos de Materiales', 'slug' => 'requerimientos_materiales.index', 'grupo' => 'Producción', 'icono' => 'fas fa-clipboard-list'],
            ['nombre' => 'Requerimientos - Crear', 'slug' => 'requerimientos_materiales.create', 'grupo' => 'Producción', 'icono' => 'fas fa-plus-circle'],
            ['nombre' => 'Requerimientos - Editar', 'slug' => 'requerimientos_materiales.edit', 'grupo' => 'Producción', 'icono' => 'fas fa-edit'],
            ['nombre' => 'Requerimientos - Ver', 'slug' => 'requerimientos_materiales.show', 'grupo' => 'Producción', 'icono' => 'fas fa-eye'],
            ['nombre' => 'Requerimientos - Enviar', 'slug' => 'requerimientos_materiales.enviar', 'grupo' => 'Producción', 'icono' => 'fas fa-paper-plane'],
            ['nombre' => 'Requerimientos - Aprobar', 'slug' => 'requerimientos_materiales.aprobar', 'grupo' => 'Producción', 'icono' => 'fas fa-check-circle'],
            ['nombre' => 'Requerimientos - Atender', 'slug' => 'requerimientos_materiales.atender', 'grupo' => 'Producción', 'icono' => 'fas fa-truck-loading'],

            // Almacén
            ['nombre' => 'Almacén', 'slug' => 'almacenes.index', 'grupo' => 'Almacén', 'icono' => 'fas fa-warehouse'],

            // Terceros
            ['nombre' => 'Terceros - Envíos', 'slug' => 'terceros.salidas.index', 'grupo' => 'Terceros', 'icono' => 'fas fa-truck-loading'],
            ['nombre' => 'Terceros - Liquidación', 'slug' => 'terceros.liquidacion.index', 'grupo' => 'Terceros', 'icono' => 'fas fa-balance-scale'],

            // Reportes
            ['nombre' => 'Reportes Generales', 'slug' => 'reportes.index', 'grupo' => 'Reportes', 'icono' => 'fas fa-file-invoice-dollar'],
            ['nombre' => 'Trazabilidad', 'slug' => 'reportes.trazabilidad', 'grupo' => 'Reportes', 'icono' => 'fas fa-project-diagram'],

            // Administración
            ['nombre' => 'Usuarios', 'slug' => 'usuarios.index', 'grupo' => 'Administración', 'icono' => 'fas fa-users-cog'],
            ['nombre' => 'Roles y Permisos', 'slug' => 'roles.index', 'grupo' => 'Administración', 'icono' => 'fas fa-user-shield'],
            ['nombre' => 'Parámetros del Sistema', 'slug' => 'parametros.index', 'grupo' => 'Administración', 'icono' => 'fas fa-cogs'],
        ];

        $adminRoleId = DB::table('roles')->where('nombre', 'Administrador')->value('id');

        foreach ($modulos as $mod) {
            $existing = DB::table('modulos')->where('slug', $mod['slug'])->first();
            if ($existing) {
                DB::table('modulos')->where('id', $existing->id)->update([
                    'nombre' => $mod['nombre'],
                    'grupo' => $mod['grupo'],
                    'icono' => $mod['icono'],
                    'updated_at' => now(),
                ]);
                $moduloId = $existing->id;
            } else {
                $mod['created_at'] = now();
                $mod['updated_at'] = now();
                $moduloId = DB::table('modulos')->insertGetId($mod);
            }

            if ($adminRoleId) {
                DB::table('rol_modulo')->updateOrInsert(
                    ['rol_id' => $adminRoleId, 'modulo_id' => $moduloId],
                    ['rol_id' => $adminRoleId, 'modulo_id' => $moduloId, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }
}
