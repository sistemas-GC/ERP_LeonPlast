<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('molde', function (Blueprint $table) {
            $table->unsignedInteger('cavidades')->nullable()->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('molde', function (Blueprint $table) {
            $table->dropColumn('cavidades');
        });
    }
};
