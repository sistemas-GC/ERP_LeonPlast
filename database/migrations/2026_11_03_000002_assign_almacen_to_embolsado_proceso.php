<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('proceso_produccion')
            ->where('codigo', 5)
            ->update(['codigo_almacen' => 'PS5F']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('proceso_produccion')
            ->where('codigo', 5)
            ->update(['codigo_almacen' => null]);
    }
};
