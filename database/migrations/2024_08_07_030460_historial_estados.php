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
        Schema::create('historial_estados', function (Blueprint $table) {
            $table->id();

            // Relación con la tabla de beneficiarios
            $table->uuid('id_persona');

            // Estado del beneficiario
            $table->string('estado', 50);

            // Fechas de vigencia del estado
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable(); // NULL = estado actual

            // Campos de auditoría
            $table->timestamp('fecha_registro')->useCurrent();
            $table->string('usuario_modificacion')->nullable();

            // Observaciones del cambio
            $table->text('observaciones')->nullable();

            // Timestamps de Laravel
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index('id_persona');
            $table->index(['id_persona', 'fecha_inicio', 'fecha_fin']);
            $table->index('estado');

            // Foreign key constraint
            $table->foreign('id_persona')
                ->references('id_persona')
                ->on('persona')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_estados');
    }
};
