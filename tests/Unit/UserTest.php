<?php
namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    /**
     * Una prueba para verificar el inicio de sesión de un usuario.
     *
     * @return void
     */
    public function test_user_can_login()
    {
        // Crear un usuario de prueba
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'password' => bcrypt('password123'), // Asegúrate de que la contraseña esté cifrada
        ]);

        // Hacer una solicitud POST al endpoint de login
        $response = $this->post('/login', [
            'email' => 'testuser@example.com',
            'password' => 'password123',
        ]);

        // Verificar que la respuesta redirige a la ruta deseada después de iniciar sesión
        $response->assertRedirect('/dashboard'); // Redirige a /dashboard o donde sea que tu aplicación redirija después del login

        // Verificar que el usuario esté autenticado
        $this->assertAuthenticatedAs($user);
    }
}
