<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('molde') && !Schema::hasColumn('molde', 'peso_unitario_gr')) {
            Schema::table('molde', function (Blueprint $table) {
                $table->decimal('peso_unitario_gr', 10, 4)->nullable()->after('cavidades')->comment('Peso estandar en gramos por cavidad/pieza');
            });
        }

        if (Schema::hasTable('producto') && !Schema::hasColumn('producto', 'peso_unitario_gr')) {
            Schema::table('producto', function (Blueprint $table) {
                $table->decimal('peso_unitario_gr', 10, 4)->nullable()->after('codigo_unidad_medida')->comment('Peso estandar en gramos por unidad para conversion KG -> UND');
            });
        }

        if (Schema::hasTable('produccion_ingresos_proceso') && !Schema::hasColumn('produccion_ingresos_proceso', 'peso_unitario_gr')) {
            Schema::table('produccion_ingresos_proceso', function (Blueprint $table) {
                $table->decimal('peso_unitario_gr', 10, 4)->nullable()->after('descripcion_molde')->comment('Peso real en gramos por unidad con el que se inyecto este lote');
            });
        }

        if (Schema::hasTable('componentes_orden_produccion_global') && !Schema::hasColumn('componentes_orden_produccion_global', 'peso_unitario_gr')) {
            Schema::table('componentes_orden_produccion_global', function (Blueprint $table) {
                $table->decimal('peso_unitario_gr', 10, 4)->nullable()->comment('Peso unitario en gramos registrado en la carga');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('molde') && Schema::hasColumn('molde', 'peso_unitario_gr')) {
            Schema::table('molde', function (Blueprint $table) {
                $table->dropColumn('peso_unitario_gr');
            });
        }

        if (Schema::hasTable('producto') && Schema::hasColumn('producto', 'peso_unitario_gr')) {
            Schema::table('producto', function (Blueprint $table) {
                $table->dropColumn('peso_unitario_gr');
            });
        }

        if (Schema::hasTable('produccion_ingresos_proceso') && Schema::hasColumn('produccion_ingresos_proceso', 'peso_unitario_gr')) {
            Schema::table('produccion_ingresos_proceso', function (Blueprint $table) {
                $table->dropColumn('peso_unitario_gr');
            });
        }

        if (Schema::hasTable('componentes_orden_produccion_global') && Schema::hasColumn('componentes_orden_produccion_global', 'peso_unitario_gr')) {
            Schema::table('componentes_orden_produccion_global', function (Blueprint $table) {
                $table->dropColumn('peso_unitario_gr');
            });
        }
    }
};
