<?php

namespace Database\Factories;

use App\Models\HistorialEstados;
use App\Models\Persona;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class HistorialEstadosFactory extends Factory
{
    protected $model = HistorialEstados::class;

    public function definition(): array
    {
        // Fecha de inicio en el año actual

        return [
            'id_persona' => Persona::factory(), 

            'estado' => 'activo',
            'fecha_inicio' => now(),
            'fecha_fin' => null,
            'fecha_registro' => now(),
            'usuario_modificacion' => $this->faker->optional()->userName(),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
}
