<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('detalle_requerimientos_materiales')) {
            Schema::create('detalle_requerimientos_materiales', function (Blueprint $table) {
                $table->bigIncrements('id_detalle');
                $table->unsignedBigInteger('id_requerimiento');
                $table->string('codigo_producto', 20);
                $table->string('codigo_almacen_origen', 10);
                $table->string('codigo_almacen_destino', 10);
                $table->decimal('cantidad_solicitada', 12, 2);
                $table->decimal('cantidad_atendida', 12, 2)->default(0);
                $table->string('lote_preferente', 50)->nullable();
                $table->text('observaciones')->nullable();

                $table->foreign('id_requerimiento')->references('id_requerimiento')->on('requerimientos_materiales')->onDelete('cascade');
                $table->index('codigo_producto');
                $table->index('codigo_almacen_origen');
                $table->index('codigo_almacen_destino');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_requerimientos_materiales');
    }
};
