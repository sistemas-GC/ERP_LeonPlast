<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('requerimientos_materiales')) {
            Schema::create('requerimientos_materiales', function (Blueprint $table) {
                $table->bigIncrements('id_requerimiento');
                $table->string('codigo', 20)->unique();
                $table->unsignedInteger('idop')->nullable();
                $table->string('motivo', 500)->nullable();
                $table->enum('estado', [
                    'BORRADOR', 'PENDIENTE', 'APROBADO', 'RECHAZADO',
                    'ATENDIDO_PARCIAL', 'ATENDIDO_TOTAL', 'ANULADO',
                ])->default('BORRADOR');
                $table->unsignedInteger('usuario_creacion');
                $table->unsignedInteger('usuario_aprobacion')->nullable();
                $table->timestamp('fecha_creacion')->useCurrent();
                $table->timestamp('fecha_aprobacion')->nullable();
                $table->text('observaciones')->nullable();

                $table->foreign('idop')->references('idop')->on('orden_produccion_global');
                $table->foreign('usuario_creacion')->references('id_usuario')->on('usuarios');
                $table->foreign('usuario_aprobacion')->references('id_usuario')->on('usuarios');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('requerimientos_materiales');
    }
};
