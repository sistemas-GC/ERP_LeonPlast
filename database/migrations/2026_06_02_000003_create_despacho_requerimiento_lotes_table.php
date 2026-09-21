<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('despacho_requerimiento_lotes')) {
            Schema::create('despacho_requerimiento_lotes', function (Blueprint $table) {
                $table->bigIncrements('id_despacho_lote');
                $table->unsignedBigInteger('id_detalle');
                $table->unsignedBigInteger('id_requerimiento');
                $table->string('lote', 50);
                $table->decimal('cantidad', 12, 2);
                $table->timestamp('fecha_despacho')->useCurrent();

                $table->foreign('id_detalle')->references('id_detalle')->on('detalle_requerimientos_materiales');
                $table->foreign('id_requerimiento')->references('id_requerimiento')->on('requerimientos_materiales');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('despacho_requerimiento_lotes');
    }
};
