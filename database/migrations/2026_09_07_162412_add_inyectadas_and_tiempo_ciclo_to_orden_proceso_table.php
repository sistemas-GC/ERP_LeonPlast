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
        Schema::table('orden_proceso', function (Blueprint $table) {
            $table->bigInteger('inyectada_inicial')->nullable()->after('estado_avance');
            $table->bigInteger('inyectada_final')->nullable()->after('inyectada_inicial');
            $table->integer('tiempo_ciclo')->nullable()->after('inyectada_final');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden_proceso', function (Blueprint $table) {
            $table->dropColumn(['inyectada_inicial', 'inyectada_final', 'tiempo_ciclo']);
        });
    }
};
