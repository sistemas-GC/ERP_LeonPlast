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
        // Drop foreign keys first if column exists
        if (Schema::hasColumn('orden_produccion_global', 'codigo_insumo')) {
            try {
                Schema::table('orden_produccion_global', function (Blueprint $table) {
                    $table->dropForeign('fk_orden_produccion_global_insumo');
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table('orden_produccion_global', function (Blueprint $table) {
                    $table->dropColumn('codigo_insumo');
                });
            } catch (\Throwable $e) {}
        }

        // Drop tables in correct order to avoid FK issues between them
        Schema::dropIfExists('operaciones_ingreso_orden_produccion_global');
        Schema::dropIfExists('sub_operacion_produccion');
        
        Schema::dropIfExists('insumo');
        Schema::dropIfExists('produccion_consumos');
        Schema::dropIfExists('produccion_productos_proceso');
        Schema::dropIfExists('produccion_valorizacion');
        
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('user_logins');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
