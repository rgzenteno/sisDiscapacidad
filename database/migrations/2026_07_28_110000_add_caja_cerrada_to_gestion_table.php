<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gestion', function (Blueprint $table) {
            $table->boolean('caja_cerrada')->default(false)->after('retroactivos_habilitado');
            $table->timestamp('fecha_cierre_caja')->nullable()->after('caja_cerrada');
        });
    }

    public function down(): void
    {
        Schema::table('gestion', function (Blueprint $table) {
            $table->dropColumn(['caja_cerrada', 'fecha_cierre_caja']);
        });
    }
};
