<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tutor;
use App\Models\Persona;
use App\Models\Carnet;
use App\Models\HistorialEstados;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        // Primero crea el usuario admin
        $this->call(AdminUserSeeder::class);

        // Luego crea el resto de datos
        $tutores = Tutor::factory(200)->create();

        Persona::factory(200)->create([
            'id_tutor' => fn() => $tutores->random()->id_tutor
        ])->each(function ($persona) {
            HistorialEstados::factory()->create([
                'id_persona' => $persona->id_persona
            ]);

            Carnet::factory()->create([
                'id_persona' => $persona->id_persona
            ]);
        });
    }
}
