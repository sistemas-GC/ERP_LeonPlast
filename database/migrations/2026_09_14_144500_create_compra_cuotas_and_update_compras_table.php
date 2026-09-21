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
        // 1. Modificar tabla compras para agregar fecha_vencimiento y asegurar condicion_pago como varchar
        Schema::table('compras', function (Blueprint $table) {
            if (!Schema::hasColumn('compras', 'fecha_vencimiento')) {
                $table->date('fecha_vencimiento')->nullable()->after('fecha_compra');
            }
        });
        DB::statement("ALTER TABLE compras MODIFY COLUMN condicion_pago VARCHAR(50) NOT NULL DEFAULT 'CONTADO'");

        // 2. Crear tabla compra_cuotas para almacenar las cuotas de facturas al crédito
        Schema::dropIfExists('compra_cuotas');
        Schema::create('compra_cuotas', function (Blueprint $table) {
            $table->id();
            $table->integer('id_compra');
            $table->integer('numero_cuota')->default(1);
            $table->date('fecha_vencimiento');
            $table->decimal('monto', 15, 2);
            $table->timestamps();

            $table->foreign('id_compra')->references('id_compra')->on('compras')->onDelete('cascade');
            $table->index(['id_compra', 'numero_cuota']);
            $table->index('fecha_vencimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compra_cuotas');

        Schema::table('compras', function (Blueprint $table) {
            if (Schema::hasColumn('compras', 'fecha_vencimiento')) {
                $table->dropColumn('fecha_vencimiento');
            }
        });
    }
};
