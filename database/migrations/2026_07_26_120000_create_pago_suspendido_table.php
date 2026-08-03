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
        Schema::create('pago_suspendido', function (Blueprint $table) {
            $table->id();

            // Relación con el beneficiario
            $table->uuid('id_persona');

            // Rango de vigencia de la suspensión. fecha_fin null = suspensión
            // abierta (sigue vigente en todos los meses desde fecha_inicio en
            // adelante, incluidos los que todavía no existen en `mes`).
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();

            // Motivo de la suspensión (decisión interna de UMADIS, no un
            // estado del beneficiario ante SIGEP)
            $table->text('observaciones')->nullable();
            $table->string('usuario_modificacion')->nullable();

            $table->timestamps();

            $table->index('id_persona');
            $table->index(['id_persona', 'fecha_inicio', 'fecha_fin']);

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
        Schema::dropIfExists('pago_suspendido');
    }
};
