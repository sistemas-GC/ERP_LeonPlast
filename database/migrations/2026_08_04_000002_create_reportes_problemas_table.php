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
        Schema::dropIfExists('reportes_problemas');
        Schema::create('reportes_problemas', function (Blueprint $table) {
            $table->id('id_reporte');
            $table->unsignedBigInteger('id_usuario')->index();
            $table->string('titulo', 255);
            $table->enum('tipo_problema', [
                'ERROR_SISTEMA',
                'CONSULTA_DUDA',
                'MEJORA_SOLICITUD',
                'FALLO_DATOS',
                'OTRO'
            ])->default('ERROR_SISTEMA');
            $table->enum('prioridad', ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'])->default('MEDIA');
            $table->text('descripcion');
            $table->enum('estado', ['PENDIENTE', 'EN_REVISION', 'RESUELTO', 'RECHAZADO'])->default('PENDIENTE');
            $table->text('respuesta_admin')->nullable();
            $table->timestamps();

            $table->foreign('id_usuario')
                  ->references('id_usuario')
                  ->on('usuarios')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reportes_problemas');
    }
};
