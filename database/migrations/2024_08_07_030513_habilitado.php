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
        Schema::create('habilitado', function (Blueprint $table) {
            $table->increments('id_habilitado'); // ID de habilitado como autoincremental
            $table->tinyInteger('habilitado'); // Estado habilitado
            $table->string('observaciones_habilitado', 255)->nullable(); // Observaciones
            $table->timestamp('fecha_habilitado')->useCurrent();
            // Llave foranea 'id' de la tabla 'users'
            $table->unsignedBigInteger('id');
            $table->foreign('id')
                ->references('id') // Referencia al ID en la tabla 'users'
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Llave foranea 'id_persona' de la tabla 'persona'
            $table->uuid('id_persona');
            $table->foreign('id_persona')
                ->references('id_persona') // Referencia al ID en la tabla 'persona'
                ->on('persona')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Llave foranea 'id_lista' de la tabla 'lista'
            $table->unsignedInteger('id_gestion');
            $table->foreign('id_gestion')
                ->references('id_gestion') // Referencia al ID en la tabla 'lista'
                ->on('gestion')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Llave foranea 'id_lista' de la tabla 'lista'
            $table->unsignedInteger('id_mes');
            $table->foreign('id_mes')
                ->references('id_mes') // Referencia al ID en la tabla 'lista'
                ->on('mes')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Índices recomendados:
            $table->index('id_persona');          // ⭐ Para JOINs con persona
            $table->index('id_gestion');          // ⭐ Para filtrar por gestión
            $table->index('id_mes');          // ⭐ Para filtrar por mes
            $table->index('id');                  // Para JOINs con users
            $table->index('habilitado');          // ⭐ Para filtrar habilitados/no habilitados
            $table->index('fecha_habilitado');    // Para ordenar por fecha

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('habilitado');
    }
};
