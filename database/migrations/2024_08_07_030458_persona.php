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
        Schema::create('persona', function (Blueprint $table) {
            $table->uuid('id_persona')->primary();
            $table->string('nombre_persona', 50)->nullable();
            $table->string('apellido_persona', 50)->nullable();
            $table->string('nombre_completo', 150)->nullable();
            $table->string('distrito', 50)->nullable();
            $table->string('ci_persona', 10)->unique();
            $table->string('complemento', 2)->nullable();
            $table->string('fecha_nacimiento', 45)->nullable();
            $table->string('observacion_persona', 255)->nullable();
            $table->string('tutor_nombre', 50)->nullable();
            $table->string('documento_respaldo', 200)->nullable();
            $table->string('tipo_registro', 100)->nullable();
            $table->date('fecha_registro');
            $table->unsignedInteger('id_tutor')->nullable(); // Permitir que sea NULL
            $table->foreign('id_tutor')
                ->references('id_tutor')
                ->on('tutor')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Índices recomendados:
            $table->index('id_tutor');            // ⭐ Para JOINs con tutor
            $table->index('distrito');            // Para filtrar por distrito
            $table->index('nombre_persona');      // Para búsquedas por nombre
            $table->index('apellido_persona');    // Para búsquedas por apellido
            $table->index(['nombre_persona', 'apellido_persona']); // Búsquedas completas

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('persona');
    }
};
