<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('boleta_consecutivo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_mes');
            $table->unsignedBigInteger('id_gestion');
            $table->unsignedInteger('ultimo_numero')->default(0);
            $table->unique(['id_mes', 'id_gestion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boleta_consecutivo');
    }
};