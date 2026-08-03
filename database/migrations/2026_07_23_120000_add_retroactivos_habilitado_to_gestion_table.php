<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gestion', function (Blueprint $table) {
            $table->boolean('retroactivos_habilitado')->default(false)->after('presupuesto_anual');
        });
    }

    public function down(): void
    {
        Schema::table('gestion', function (Blueprint $table) {
            $table->dropColumn('retroactivos_habilitado');
        });
    }
};
