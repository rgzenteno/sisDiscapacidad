<?php
namespace Tests\Feature;

use App\Models\User;
use App\Models\Persona;
use App\Models\Gestion;
use App\Models\Habilitado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HabilitadoTest extends TestCase
{
    use RefreshDatabase; // Esto asegura que se limpie la base de datos entre pruebas.

    /**
     * Una prueba para verificar que los registros de habilitado se guardan correctamente.
     *
     * @return void
     */
    public function test_habilitado_can_be_created()
    {
        // Crear un usuario de prueba
        $user = User::factory()->create();

        // Crear una persona de prueba
        $persona = Persona::factory()->create();

        // Crear una gestion de prueba
        $gestion = Gestion::factory()->create();

        // Crear un habilitado de prueba
        $habilitado = Habilitado::create([
            'habilitado' => '1',
            'observaciones_habilitado' => 'Observación de prueba',
            'id' => $user->id,
            'id_persona' => $persona->id_persona,
            'id_gestion' => $gestion->id_gestion,
        ]);

        // Verificar que el habilitado se ha guardado correctamente
        $this->assertDatabaseHas('habilitado', [
            'habilitado' => '1',
            'observaciones_habilitado' => 'Observación de prueba',
            'id_persona' => $persona->id_persona,
            'id_gestion' => $gestion->id_gestion,
        ]);
    }

    /**
     * Una prueba para verificar que las relaciones de Habilitado funcionan correctamente.
     *
     * @return void
     */
    public function test_habilitado_relations()
    {
        // Crear un habilitado de prueba junto con sus relaciones
        $user = User::factory()->create();
        $persona = Persona::factory()->create();
        $gestion = Gestion::factory()->create();

        $habilitado = Habilitado::create([
            'habilitado' => '1',
            'observaciones_habilitado' => 'Observación de prueba',
            'id' => $user->id,
            'id_persona' => $persona->id_persona,
            'id_gestion' => $gestion->id_gestion,
        ]);

        // Verificar la relación con el usuario
        $this->assertEquals($user->id, $habilitado->user->id);

        // Verificar la relación con la persona
        $this->assertEquals($persona->id_persona, $habilitado->persona->id_persona);

        // Verificar la relación con la gestion
        $this->assertEquals($gestion->id_gestion, $habilitado->gestion->id_gestion);
    }
}
