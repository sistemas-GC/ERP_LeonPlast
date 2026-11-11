<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('molde', function (Blueprint $table) {
            $table->string('descripcion', 255)->change();
        });

        if (Schema::hasTable('componentes_orden_produccion_global') && Schema::hasColumn('componentes_orden_produccion_global', 'descripcion_molde')) {
            Schema::table('componentes_orden_produccion_global', function (Blueprint $table) {
                $table->string('descripcion_molde', 255)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('molde', function (Blueprint $table) {
            $table->string('descripcion', 100)->change();
        });

        if (Schema::hasTable('componentes_orden_produccion_global') && Schema::hasColumn('componentes_orden_produccion_global', 'descripcion_molde')) {
            Schema::table('componentes_orden_produccion_global', function (Blueprint $table) {
                $table->string('descripcion_molde', 100)->nullable()->change();
            });
        }
    }
};
