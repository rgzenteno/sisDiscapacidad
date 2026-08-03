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
        Schema::create('presupuesto_usuario', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_usuario')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->unsignedInteger('id_mes');
            $table->foreign('id_mes')
                ->references('id_mes')
                ->on('mes')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->unsignedInteger('id_gestion');
            $table->foreign('id_gestion')
                ->references('id_gestion')
                ->on('gestion')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->unsignedBigInteger('monto_asignado');

            $table->timestamps();

            // Un cajero solo puede tener un presupuesto asignado por mes/gestión.
            $table->unique(['id_usuario', 'id_mes', 'id_gestion']);
            $table->index('id_usuario');
            $table->index('id_mes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presupuesto_usuario');
    }
};
