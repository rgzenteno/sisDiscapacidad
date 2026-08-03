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
        Schema::create('pago_anulacion', function (Blueprint $table) {
            $table->id();

            // Pago que fue anulado. unique: un pago solo puede tener una
            // anulación activa a la vez — al reactivar (PagoController::reactivar)
            // esta fila se elimina, así que el pago queda libre para
            // anularse de nuevo más adelante si hace falta.
            $table->unsignedInteger('id_pago')->unique();
            $table->foreign('id_pago')
                ->references('id_pago')
                ->on('pago')
                ->onDelete('cascade');

            $table->text('observaciones');
            $table->string('usuario_modificacion')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pago_anulacion');
    }
};
