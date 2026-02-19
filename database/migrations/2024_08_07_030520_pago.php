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
        Schema::create('pago', function (Blueprint $table) {
            // Crea una columna autoincremental para el ID del pago
            $table->increments('id_pago'); // ID de pago como autoincremental

            // Almacena la fecha en que se realizó el pago, con un límite de 50 caracteres
            $table->timestamp('fecha_pago'); // Fecha de pago realizado

            // Estado del pago (por ejemplo, 0 o 1 para indicar si fue realizado)
            $table->integer('pago');
            $table->integer('monto');
            $table->string('comprobante', 255)->nullable();
            $table->string('numero_boleta', 20)->nullable();
            $table->string('tipo_pago', 255);

            // Columna para almacenar el ID del habilitado asociado a este pago
            $table->unsignedInteger('id_habilitado');
            $table->foreign('id_habilitado') // Definición de la clave foránea
                ->references('id_habilitado') // Hace referencia al 'id_habilitado' en la tabla 'habilitado'
                ->on('habilitado')
                ->onDelete('cascade') // Elimina este pago si se elimina el habilitado
                ->onUpdate('cascade'); // Actualiza esta referencia si se actualiza el habilitado

            // Índices recomendados:
            $table->index('id_habilitado');       // ⭐ Para JOINs con habilitado
            $table->index('fecha_pago');          // ⭐ Para filtrar por fechas
            $table->index('pago');                // Para filtrar pagos realizados/pendientes
            $table->index('tipo_pago');           // Para reportes por tipo de pago
            $table->index('numero_boleta');       // Para búsquedas por número de boleta
            $table->index(['fecha_pago', 'pago']); // Para reportes de pagos por fecha


            // Agrega marcas de tiempo para el pago (created_at y updated_at)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pago');
    }
};
