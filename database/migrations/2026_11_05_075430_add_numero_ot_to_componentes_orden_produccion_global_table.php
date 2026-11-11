<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('componentes_orden_produccion_global', function (Blueprint $table) {
            if (!Schema::hasColumn('componentes_orden_produccion_global', 'numero_ot')) {
                $table->string('numero_ot', 50)->nullable()->after('hora_fin_maquina');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('componentes_orden_produccion_global', function (Blueprint $table) {
            if (Schema::hasColumn('componentes_orden_produccion_global', 'numero_ot')) {
                $table->dropColumn('numero_ot');
            }
        });
    }
};
