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
        Schema::create('gestion', function (Blueprint $table) {
            $table->increments('id_gestion');
            $table->integer('gestion');
            $table->unsignedBigInteger('presupuesto_anual');
            $table->timestamps();

            // Índices recomendados:
            $table->index('gestion');  // Para búsquedas rápidas por año
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gestion');
    }
};
