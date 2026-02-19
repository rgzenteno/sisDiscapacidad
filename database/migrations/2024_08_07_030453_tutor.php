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
        Schema::create('tutor', function (Blueprint $table) {
            $table->increments('id_tutor');
            $table->string('nombre_tutor', 45);
            $table->string('apellido_tutor', 45);
            $table->string('ci_tutor', 45)->nullable();
            $table->string('complemento_tutor', 2)->nullable();
            $table->string('fecha_nacimiento', 45)->nullable();
            $table->string('telefono', 15)->nullable();
            $table->string('email', 50)->nullable();
            $table->string('direccion', 50)->nullable();

            // Índices recomendados:
            $table->unique('ci_tutor');           // ⭐ Evitar CIs duplicados
            $table->index('nombre_tutor');        // Para búsquedas por nombre
            $table->index('apellido_tutor');      // Para búsquedas por apellido
            $table->index(['nombre_tutor', 'apellido_tutor']); // Para búsquedas completas

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutor');
    }
};
