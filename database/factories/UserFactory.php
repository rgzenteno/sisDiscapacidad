<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->firstName(), // Genera un nombre ficticio
            'apellido' => $this->faker->lastName(), // Genera un apellido ficticio
            'rol' => $this->faker->randomElement(['admin', 'user', 'moderator']), // Puedes personalizar los roles según tus necesidades
            'cargo' => $this->faker->jobTitle(), // Genera un cargo ficticio
            'estado' => 1, // Por defecto, el estado puede ser 1 (activo)
            'email' => $this->faker->unique()->safeEmail(), // Genera un email único
            'password' => Hash::make('password'), // Se crea con la contraseña predeterminada
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
