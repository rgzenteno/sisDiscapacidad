<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('historial_estados', function (Blueprint $table) {
            // Texto libre que escribe el usuario: la razón por la que se
            // está cambiando el estado. Requerido en el formulario.
            $table->string('motivo', 255)->nullable()->after('estado');
        });

        // `observaciones` (ya existente) se queda con su rol original: el
        // texto fijo según el estado (ej. "PADRE FUNCIONARIO TRABAJANDO CON
        // ITEM", "FALLECIDO"), para no tener que migrar los datos históricos
        // que ya la usan así. Se achica de `text` a 50 caracteres porque de
        // ahora en más solo va a contener esas frases cortas y fijas, nunca
        // texto libre.
        DB::statement('ALTER TABLE historial_estados MODIFY observaciones VARCHAR(50) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE historial_estados MODIFY observaciones TEXT NULL');

        Schema::table('historial_estados', function (Blueprint $table) {
            $table->dropColumn('motivo');
        });
    }
};
