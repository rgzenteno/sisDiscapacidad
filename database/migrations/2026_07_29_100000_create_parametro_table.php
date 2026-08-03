<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Tabla genérica de parámetros del sistema, gestionada solo por
     * superusuario desde Configuración. Mismo patrón que `distrito` /
     * `discapacidad`: no lleva llave foránea hacia nada — el código que
     * consume un valor (ej. Mes::monto) simplemente copia el texto/número
     * de acá al crear el registro, así que agregar un parámetro nuevo a
     * futuro es solo una fila nueva, sin tocar el esquema.
     */
    public function up(): void
    {
        Schema::create('parametro', function (Blueprint $table) {
            $table->id();
            $table->string('tipo')->unique();
            $table->string('valor')->nullable();
            $table->timestamps();
        });

        // Valores iniciales: 250 como monto por persona y #2E8E41 (RGB
        // 46, 142, 65) como color institucional por defecto. El
        // superusuario puede cambiarlos luego desde Configuración.
        DB::table('parametro')->insert([
            [
                'tipo' => 'Monto - Persona',
                'valor' => '250',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo' => 'Color - Sistema',
                'valor' => '#2E8E41',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('parametro');
    }
};