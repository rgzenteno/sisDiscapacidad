<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('distrito', function (Blueprint $table) {
            $table->id('id_distrito');
            $table->string('distrito', 10);
            $table->timestamps();
        });

        // Insertar datos iniciales
        DB::table('distrito')->insert([
            ['distrito' => 'D-1', 'created_at' => now(), 'updated_at' => now()],
            ['distrito' => 'D-2', 'created_at' => now(), 'updated_at' => now()],
            ['distrito' => 'D-3', 'created_at' => now(), 'updated_at' => now()],
            ['distrito' => 'D-4', 'created_at' => now(), 'updated_at' => now()],
            ['distrito' => 'D-5', 'created_at' => now(), 'updated_at' => now()],
            ['distrito' => 'D-6', 'created_at' => now(), 'updated_at' => now()],
            ['distrito' => 'D-7', 'created_at' => now(), 'updated_at' => now()],
            ['distrito' => 'AGUIRRE', 'created_at' => now(), 'updated_at' => now()],
            ['distrito' => 'CHIÑATA', 'created_at' => now(), 'updated_at' => now()],
            ['distrito' => 'LAVA LAVA', 'created_at' => now(), 'updated_at' => now()],
            ['distrito' => 'UCUCHI', 'created_at' => now(), 'updated_at' => now()],
            ['distrito' => 'PALCA', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distrito');
    }
};