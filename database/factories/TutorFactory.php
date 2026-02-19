<?php

namespace Database\Factories;

use App\Models\Tutor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tutor>
 */
class TutorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_tutor' => $this->faker->firstName,
            'apellido_tutor' => $this->faker->lastName,
            'ci_tutor' => $this->faker->unique()->randomNumber(8),
            'complemento_tutor' => $this->faker->boolean(50) // 60% tienen complemento
                ? strtoupper($this->faker->lexify('??'))
                : null,
            'fecha_nacimiento' => $this->faker->date(), // Asegúrate de generar una fecha
            'telefono' => '7792113',
            'email' => null,
            'direccion' => substr($this->faker->address, 0, 40),
        ];

    }
}
