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
        Schema::create('mes', function (Blueprint $table) {
            $table->increments('id_mes');
            $table->tinyInteger('mes')->unsigned(); 
            $table->integer('monto');
            $table->unsignedBigInteger('presupuesto');
            $table->unsignedInteger('id_gestion'); // Permitir que sea NULL
            $table->foreign('id_gestion')
                ->references('id_gestion')
                ->on('gestion')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->timestamps();

            // Índices recomendados:
            $table->index('id_gestion');           // ⭐ Para JOIN con tabla gestion
            $table->index('mes');                  // ⭐ Para filtrar por fecha/mes
            $table->unique(['mes', 'id_gestion']); // ⭐ Evitar duplicados mes-año
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mes');
    }
};
