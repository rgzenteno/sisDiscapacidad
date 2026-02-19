<?php

namespace Database\Factories;

use App\Models\Persona;
use App\Models\Tutor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Persona>
 */
class PersonaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_persona' => $this->faker->firstName,
            'apellido_persona' => $this->faker->lastName,
            'distrito' => $this->faker->randomElement([
                'D-1',
                'D-2',
                'D-3',
                'D-4',
                'D-5',
                'D-6',
                'D-7',
                'AGUIRRE',
                'CHIÑATA',
                'LAVA LAVA',
                'UCUCHI',
                'PALCA',
            ]),
            'ci_persona' => $this->faker->unique()->randomNumber(8),
            'complemento' => $this->faker->boolean(50) // 60% tienen complemento
                ? strtoupper($this->faker->lexify('??'))
                : null,
            'fecha_nacimiento' => $this->faker->date(),
            'observacion_persona' => null,
            'tutor_nombre' => $this->faker->name(),
            'documento_respaldo' => $this->faker->randomElement([
                'Certificado Médico',
                'Informe Psicológico',
                'Evaluación Social',
                'Otro Documento',
            ]),
            'tipo_registro' => 'beneficiario',
            'fecha_registro' => now()->format('Y-m-d'),
            'id_tutor' => Tutor::factory(), // Asociar con un tutor generado
        ];
    }
}
