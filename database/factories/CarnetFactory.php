<?php

namespace Database\Factories;

use App\Models\Carnet;
use App\Models\Persona;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class CarnetFactory extends Factory
{
    protected $model = Carnet::class;

    public function definition(): array
    {
        return [
            'discapacidad' => $this->faker->randomElement([
                'intelectual',
                'fisica-motora',
                'auditiva',
                'mental-psiquica',
                'multiple'
            ]),
            'fecha_emision' => '2025-01-01',
            'fecha_vencimiento' => '2029-05-01',
            'id_persona' => Persona::factory(),
            'doc' => 'TEMP', // Se reemplaza después
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Carnet $carnet) {
            $persona = $carnet->persona;

            // Fecha nacimiento en formato Ymd
            $fecha = Carbon::parse($persona->fecha_nacimiento)->format('Ymd');

            // Iniciales de nombre + apellido
            $iniciales =
                strtoupper(substr($persona->nombre_persona, 0, 1)) .
                strtoupper(substr($persona->apellido_persona, 0, 1));

            // Formato final
            $doc = "03-$fecha$iniciales";

            // Guardar
            $carnet->update(['doc' => $doc]);
        });
    }
}
