<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('componentes_orden_produccion_global', function (Blueprint $table) {
            if (!Schema::hasColumn('componentes_orden_produccion_global', 'inyectada_inicial')) {
                $table->bigInteger('inyectada_inicial')->nullable()->after('hora_fin_maquina');
            }
            if (!Schema::hasColumn('componentes_orden_produccion_global', 'inyectada_final')) {
                $table->bigInteger('inyectada_final')->nullable()->after('inyectada_inicial');
            }
        });

        // Backfill histórico desde orden_proceso si existen registros previos
        try {
            DB::statement("
                UPDATE componentes_orden_produccion_global c
                INNER JOIN orden_proceso p ON c.id_proceso = p.id
                SET c.inyectada_inicial = p.inyectada_inicial,
                    c.inyectada_final = p.inyectada_final
                WHERE (p.inyectada_inicial IS NOT NULL OR p.inyectada_final IS NOT NULL)
                  AND c.inyectada_inicial IS NULL
                  AND c.inyectada_final IS NULL
            ");
        } catch (\Exception $e) {
            // Ignorar si la tabla o relación está vacía
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('componentes_orden_produccion_global', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('componentes_orden_produccion_global', 'inyectada_inicial')) {
                $columnsToDrop[] = 'inyectada_inicial';
            }
            if (Schema::hasColumn('componentes_orden_produccion_global', 'inyectada_final')) {
                $columnsToDrop[] = 'inyectada_final';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
