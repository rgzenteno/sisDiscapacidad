<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('carnet', function (Blueprint $table) {
            $table->increments('id_carnet');
            $table->string('doc', 50);
            $table->string('discapacidad', 50);
            $table->date('fecha_emision')->nullable();        // Cambiado a date
            $table->date('fecha_vencimiento');    // Cambiado a date
            $table->uuid('id_persona');
            $table->foreign('id_persona')
                ->references('id_persona')
                ->on('persona')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->timestamps();

            // Agregamos el índice recomendado
            // Índices recomendados:
            $table->index('id_persona');          // ⭐ Para JOINs con persona
            $table->index('discapacidad');        // Para filtrar por tipo
            $table->index('fecha_emision');       // Para ordenar por fecha
// fecha_vencimiento ya lo tienes, perfecto
            $table->unique('id_persona');         // ⭐ Solo un carnet por persona
            $table->index('fecha_vencimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carnet');
    }
};
