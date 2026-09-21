<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    $tablas = [
        'compras',
        'detalle_compra',
        'compra_cuotas',
        'guia_remision_compras',
        'detalle_guia_compras',
        'requerimientos_materiales',
        'detalle_requerimientos_materiales',
        'despacho_requerimiento_lotes',
        'orden_produccion_global',
        'componentes_orden_produccion_global',
        'operaciones_ingreso_orden_produccion_global',
        'orden_proceso',
        'produccion_ingresos_proceso',
        'produccion_consumos',
        'produccion_costos',
        'produccion_valorizacion',
        'mermas',
        'transferencias_almacen',
        'transferencias_almacen_detalle',
        'guia_remision_terceros_salida',
        'guia_remision_terceros_salida_detalle',
        'conciliacion_terceros',
        'inventario',
        'movimientos_inventario',
        'kardex',
        'bitacora_sistema',
        'notificaciones',
        'reportes_problemas',
        'user_logins',
    ];

    foreach ($tablas as $tabla) {
        if (Schema::hasTable($tabla)) {
            DB::table($tabla)->truncate();
            echo "Tabla $tabla truncada exitosamente.\n";
        }
    }

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    echo "¡Limpieza completada con éxito!\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
}
