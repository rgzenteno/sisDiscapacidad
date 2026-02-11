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
        Schema::create('historial_habilitado', function (Blueprint $table) {
            // Crea una columna autoincremental para el ID del historial
            $table->increments('id_historial');

            // Estado habilitado en el historial (por ejemplo, 0 o 1)
            $table->tinyInteger('habilitado_historial');

            // Observaciones relacionadas con el historial, con un máximo de 255 caracteres
            $table->string('observacion_historial', 255)->nullable();

            // Llave foranea 'id' de la tabla 'users'
            $table->unsignedBigInteger('id');
            $table->foreign('id')
                ->references('id') // Referencia al ID en la tabla 'users'
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Columna para almacenar el ID de la persona asociada a este historial
            $table->uuid('id_persona');
            $table->foreign('id_persona') // Definición de la clave foránea
                ->references('id_persona') // Hace referencia al 'id_persona' en la tabla 'persona'
                ->on('persona')
                ->onDelete('cascade') // Elimina este historial si se elimina la persona
                ->onUpdate('cascade'); // Actualiza esta referencia si se actualiza la persona

            // Columna para almacenar el ID de la lista asociada a este historial
            $table->unsignedInteger('id_gestion');
            $table->foreign('id_gestion') // Definición de la clave foránea
                ->references('id_gestion') // Hace referencia al 'id_lista' en la tabla 'lista'
                ->on('gestion')
                ->onDelete('cascade') // Elimina este historial si se elimina la lista
                ->onUpdate('cascade'); // Actualiza esta referencia si se actualiza la lista

            // Llave foranea 'id_lista' de la tabla 'lista'
            $table->unsignedInteger('id_mes');
            $table->foreign('id_mes')
                ->references('id_mes') // Referencia al ID en la tabla 'lista'
                ->on('mes')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Columna para almacenar el ID de habilitado asociado a este historial
            $table->unsignedInteger('id_habilitado');
            $table->foreign('id_habilitado') // Definición de la clave foránea
                ->references('id_habilitado') // Hace referencia al 'id_habilitado' en la tabla 'habilitado'
                ->on('habilitado')
                ->onDelete('cascade') // Elimina este historial si se elimina el habilitado
                ->onUpdate('cascade'); // Actualiza esta referencia si se actualiza el habilitado

            // Índices recomendados:
            $table->index('id_persona');          // ⭐ Para ver historial de persona
            $table->index('id_habilitado');       // ⭐ Para JOINs con habilitado
            $table->index('id_gestion');          // Para filtrar por gestión
            $table->index('id');                  // Para JOINs con users
            $table->index('habilitado_historial'); // Para filtrar por estado
            $table->index(['id_persona', 'id_gestion']); // Para historial persona-gestión

            // Agrega marcas de tiempo para el historial (created_at y updated_at)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_habilitado');
    }
};
