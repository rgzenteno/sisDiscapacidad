<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mes', function (Blueprint $table) {
            // Distingue un mes-retro creado por confirmarSinRetroactivo() (SIGEP
            // no envió lista ese mes) de uno cargado normalmente. Necesario
            // porque un mes-retro real también puede terminar con 0 habilitados
            // y 0 bajas (ej. todos los CI de la lista ya estaban pagados/habilitados
            // en el mes normal), así que no alcanza con inferirlo de los conteos.
            $table->boolean('sin_retroactivo')->default(false)->after('mes_original');
        });
    }

    public function down(): void
    {
        Schema::table('mes', function (Blueprint $table) {
            $table->dropColumn('sin_retroactivo');
        });
    }
};
