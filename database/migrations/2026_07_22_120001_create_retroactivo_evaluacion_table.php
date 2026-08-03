<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla estrictamente para exclusiones de retroactivo (nunca pagables:
        // un `habilitado`-retro creado ya es fuente de verdad suficiente para
        // eso). Una fila acá significa "esta persona no cobra por este
        // mes-retro", ya sea por una baja real (estado_baja) o por una
        // corrección manual de quien cargó el Excel (es_correccion_manual).
        Schema::create('retroactivo_evaluacion', function (Blueprint $table) {
            $table->id();
            $table->uuid('id_persona');
            $table->unsignedInteger('id_gestion');
            $table->tinyInteger('mes_original'); // 1-12
            $table->string('motivo', 255)->nullable();
            $table->string('estado_baja', 20)->nullable(); // baja_temporal | baja_definitiva
            $table->boolean('es_correccion_manual')->default(false);
            $table->string('usuario_correccion')->nullable();
            $table->timestamp('fecha_evaluacion')->useCurrent();
            $table->timestamps();

            $table->foreign('id_persona')->references('id_persona')->on('persona')->onDelete('cascade');
            $table->foreign('id_gestion')->references('id_gestion')->on('gestion')->onDelete('cascade');

            $table->index(['id_gestion', 'mes_original']);
            $table->unique(['id_persona', 'id_gestion', 'mes_original']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retroactivo_evaluacion');
    }
};
