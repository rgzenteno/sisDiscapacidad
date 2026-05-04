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
            $table->date('fecha_emision')->nullable();
            $table->date('fecha_vencimiento');
            $table->uuid('id_persona');
            $table->foreign('id_persona')
                ->references('id_persona')
                ->on('persona')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->timestamps();

            // Índices:
            $table->index('id_persona');
            $table->index('discapacidad');
            $table->index('fecha_emision');
            $table->unique('id_persona');
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