<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'nombre' => 'Test User',   // Nombre del usuario
            'apellido' => 'Test',      // Apellido del usuario
            'rol' => 'user',           // Rol del usuario (puedes ajustar según tu necesidad)
            'cargo' => 'developer',    // Cargo del usuario (puedes ajustar según tu necesidad)
            'estado' => 1,             // Estado del usuario
            'email' => 'test@example.com',  // Correo electrónico
            'password' => 'password',  // Contraseña
            'password_confirmation' => 'password',  // Confirmación de la contraseña
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
