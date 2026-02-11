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
        Schema::create('discapacidad', function (Blueprint $table) {
            $table->id('id_discapacidad');
            $table->string('discapacidad', 20);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
        });

        // Insertar datos iniciales
        DB::table('discapacidad')->insert([
            ['discapacidad' => 'INTELECTUAL', 'created_at' => now()],
            ['discapacidad' => 'FISICA-MOTORA', 'created_at' => now()],
            ['discapacidad' => 'AUDITIVA', 'created_at' => now()],
            ['discapacidad' => 'MENTAL-PSIQUICA', 'created_at' => now()],
            ['discapacidad' => 'MULTIPLE', 'created_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discapacidad');
    }
};