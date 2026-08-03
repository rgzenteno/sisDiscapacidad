<?php

namespace Tests\Feature;

use App\Exceptions\HistorialEstadoException;
use App\Models\Gestion;
use App\Models\HistorialEstados;
use App\Models\Mes;
use App\Models\Persona;
use App\Services\HistorialEstadoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistorialEstadoServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): HistorialEstadoService
    {
        return app(HistorialEstadoService::class);
    }

    private function estado(Persona $persona, string $estado, string $fechaInicio, ?string $fechaFin = null): HistorialEstados
    {
        return HistorialEstados::create([
            'id_persona' => $persona->id_persona,
            'estado' => $estado,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'usuario_modificacion' => 'Test',
        ]);
    }

    public function test_agregar_cierra_el_estado_anterior_y_crea_uno_nuevo()
    {
        $persona = Persona::factory()->create();
        $actual = $this->estado($persona, 'activo', '2025-01-01');

        $nuevo = $this->service()->agregar(
            $persona->id_persona,
            'baja_temporal',
            Carbon::parse('2025-03-01'),
            null,
            'Tester'
        );

        $this->assertSame('2025-02-28', Carbon::parse($actual->fresh()->fecha_fin)->toDateString());
        $this->assertNull($nuevo->fecha_fin);
        $this->assertSame('baja_temporal', $nuevo->estado);
    }

    public function test_agregar_completa_observacion_fija_solo_para_las_bajas()
    {
        $persona = Persona::factory()->create();
        $this->estado($persona, 'activo', '2025-01-01');

        $baja = $this->service()->agregar($persona->id_persona, 'baja_temporal', Carbon::parse('2025-02-01'), 'razón libre', 'Tester');
        $this->assertSame('PADRE FUNCIONARIO TRABAJANDO CON ITEM', $baja->observaciones);
        $this->assertSame('razón libre', $baja->motivo);

        $definitiva = $this->service()->agregar($persona->id_persona, 'baja_definitiva', Carbon::parse('2025-03-01'), 'otra razón', 'Tester');
        $this->assertSame('FALLECIDO', $definitiva->observaciones);

        $activo = $this->service()->agregar($persona->id_persona, 'activo', Carbon::parse('2025-04-01'), 'vuelve a activo', 'Tester');
        $this->assertNull($activo->observaciones);
    }

    public function test_agregar_rechaza_fecha_no_posterior_al_estado_actual()
    {
        $persona = Persona::factory()->create();
        $this->estado($persona, 'activo', '2025-03-01');

        $this->expectException(HistorialEstadoException::class);

        $this->service()->agregar($persona->id_persona, 'baja_temporal', Carbon::parse('2025-02-01'), null, 'Tester');
    }

    public function test_editar_solo_estado_no_modifica_fechas()
    {
        $persona = Persona::factory()->create();
        $primero = $this->estado($persona, 'activo', '2025-01-01', '2025-02-28');
        $this->estado($persona, 'baja_temporal', '2025-03-01');

        $actualizado = $this->service()->editarSoloEstado($primero, 'pagos_suspendidos', 'obs', 'Tester', true);

        $this->assertSame('pagos_suspendidos', $actualizado->estado);
        $this->assertSame('2025-01-01', Carbon::parse($actualizado->fecha_inicio)->toDateString());
        $this->assertSame('2025-02-28', Carbon::parse($actualizado->fecha_fin)->toDateString());
    }

    public function test_editar_solo_estado_consolida_con_el_siguiente_si_queda_igual()
    {
        $persona = Persona::factory()->create();
        // Dic=baja_temporal (a editar -> activo), Feb=activo (mismo estado tras el cambio), Abr=baja_definitiva
        $dic = $this->estado($persona, 'baja_temporal', '2025-12-01', '2026-01-31');
        $feb = $this->estado($persona, 'activo', '2026-02-01', '2026-03-31');
        $abr = $this->estado($persona, 'baja_definitiva', '2026-04-01');

        $actualizado = $this->service()->editarSoloEstado($dic, 'activo', null, 'Tester', true);

        $this->assertDatabaseMissing('historial_estados', ['id' => $feb->id]);
        $this->assertSame('activo', $actualizado->estado);
        $this->assertSame('2025-12-01', Carbon::parse($actualizado->fecha_inicio)->toDateString());
        // Absorbe hasta el fin de Febrero, sin tocar Abril.
        $this->assertSame('2026-03-31', Carbon::parse($actualizado->fecha_fin)->toDateString());
        $this->assertSame('2026-04-01', Carbon::parse($abr->fresh()->fecha_inicio)->toDateString());
    }

    public function test_mover_limite_ajusta_solo_al_vecino_anterior()
    {
        $persona = Persona::factory()->create();
        $a = $this->estado($persona, 'activo', '2025-01-01', '2025-01-31');
        $b = $this->estado($persona, 'baja_temporal', '2025-02-01', '2025-02-28');
        $c = $this->estado($persona, 'activo', '2025-03-01');

        $this->service()->moverLimite($b, Carbon::parse('2025-02-10'), 'Tester', true);

        $this->assertSame('2025-02-09', Carbon::parse($a->fresh()->fecha_fin)->toDateString());
        $this->assertSame('2025-02-10', Carbon::parse($b->fresh()->fecha_inicio)->toDateString());
        // El vecino siguiente no se toca.
        $this->assertSame('2025-03-01', Carbon::parse($c->fresh()->fecha_inicio)->toDateString());
        $this->assertNull($c->fresh()->fecha_fin);
    }

    public function test_mover_limite_rechaza_fecha_fuera_del_rango_permitido()
    {
        $persona = Persona::factory()->create();
        $this->estado($persona, 'activo', '2025-01-01', '2025-01-31');
        $b = $this->estado($persona, 'baja_temporal', '2025-02-01', '2025-02-28');
        $this->estado($persona, 'activo', '2025-03-01');

        $this->expectException(HistorialEstadoException::class);

        // Se pasa del inicio del siguiente estado (marzo).
        $this->service()->moverLimite($b, Carbon::parse('2025-03-15'), 'Tester', true);
    }

    public function test_eliminar_intermedio_fusiona_con_el_anterior_sin_tocar_al_siguiente()
    {
        $persona = Persona::factory()->create();
        $a = $this->estado($persona, 'activo', '2025-01-01', '2025-01-31');
        $b = $this->estado($persona, 'baja_temporal', '2025-02-01', '2025-02-28');
        $c = $this->estado($persona, 'activo', '2025-03-01');

        $this->service()->eliminar($b, true);

        $this->assertDatabaseMissing('historial_estados', ['id' => $b->id]);
        $this->assertSame('2025-02-28', Carbon::parse($a->fresh()->fecha_fin)->toDateString());
        $this->assertSame('2025-03-01', Carbon::parse($c->fresh()->fecha_inicio)->toDateString());
    }

    public function test_eliminar_intermedio_consolida_si_el_siguiente_queda_con_el_mismo_estado()
    {
        $persona = Persona::factory()->create();
        // Dic=baja_temporal, Ene=activo (a eliminar), Mar=baja_temporal (mismo estado que Dic), Abr=baja_definitiva
        $dic = $this->estado($persona, 'baja_temporal', '2025-12-01', '2025-12-31');
        $ene = $this->estado($persona, 'activo', '2026-01-01', '2026-02-28');
        $mar = $this->estado($persona, 'baja_temporal', '2026-03-01', '2026-03-31');
        $abr = $this->estado($persona, 'baja_definitiva', '2026-04-01');

        $this->service()->eliminar($ene, true);

        // Enero y Marzo desaparecen: Marzo se fusiona dentro de Diciembre
        // porque quedó con el mismo estado tras la fusión normal.
        $this->assertDatabaseMissing('historial_estados', ['id' => $ene->id]);
        $this->assertDatabaseMissing('historial_estados', ['id' => $mar->id]);

        $this->assertSame('baja_temporal', $dic->fresh()->estado);
        $this->assertSame('2025-12-01', Carbon::parse($dic->fresh()->fecha_inicio)->toDateString());
        $this->assertSame('2026-03-31', Carbon::parse($dic->fresh()->fecha_fin)->toDateString());

        $this->assertSame('2026-04-01', Carbon::parse($abr->fresh()->fecha_inicio)->toDateString());
    }

    public function test_eliminar_intermedio_consolida_hasta_null_si_no_queda_nada_despues()
    {
        $persona = Persona::factory()->create();
        // Dic=activo, Ene=baja_temporal (a eliminar), Mar=activo (mismo estado que Dic, y es el último)
        $dic = $this->estado($persona, 'activo', '2025-12-01', '2025-12-31');
        $ene = $this->estado($persona, 'baja_temporal', '2026-01-01', '2026-02-28');
        $mar = $this->estado($persona, 'activo', '2026-03-01');

        $this->service()->eliminar($ene, true);

        $this->assertDatabaseMissing('historial_estados', ['id' => $ene->id]);
        $this->assertDatabaseMissing('historial_estados', ['id' => $mar->id]);

        $this->assertSame('activo', $dic->fresh()->estado);
        $this->assertNull($dic->fresh()->fecha_fin);
    }

    public function test_no_se_puede_eliminar_el_primer_estado()
    {
        $persona = Persona::factory()->create();
        $unico = $this->estado($persona, 'activo', '2025-01-01');

        $this->expectException(HistorialEstadoException::class);

        $this->service()->eliminar($unico, true);
    }

    public function test_insertar_intermedio_absorbe_al_siguiente_si_queda_con_el_mismo_estado()
    {
        $persona = Persona::factory()->create();
        // Dic=activo (hasta mayo), Jun=baja_temporal (vigente, abierto)
        $dic = $this->estado($persona, 'activo', '2025-12-01', '2026-05-31');
        $jun = $this->estado($persona, 'baja_temporal', '2026-06-01');

        // Insertar Febrero=baja_temporal: "nuevo" hereda el fin original de
        // Dic (mayo), quedando pegado justo antes de Junio, mismo estado.
        $resultado = $this->service()->insertarIntermedio(
            $dic,
            'baja_temporal',
            Carbon::parse('2026-02-01'),
            null,
            'Tester',
            true
        );

        $this->assertDatabaseMissing('historial_estados', ['id' => $jun->id]);
        $this->assertSame('baja_temporal', $resultado['nuevo']->estado);
        $this->assertSame('2026-02-01', Carbon::parse($resultado['nuevo']->fecha_inicio)->toDateString());
        // Absorbió a Junio: queda abierto (fecha_fin null), no en mayo.
        $this->assertNull($resultado['nuevo']->fecha_fin);
    }

    public function test_insertar_intermedio_parte_el_segmento_en_dos_y_llega_hasta_el_fin_del_base()
    {
        $persona = Persona::factory()->create();
        $base = $this->estado($persona, 'activo', '2025-12-01', '2026-11-30');

        $resultado = $this->service()->insertarIntermedio(
            $base,
            'baja_temporal',
            Carbon::parse('2026-02-01'),
            null,
            'Tester',
            true
        );

        $this->assertSame('2025-12-01', Carbon::parse($resultado['antes']->fecha_inicio)->toDateString());
        $this->assertSame('2026-01-31', Carbon::parse($resultado['antes']->fecha_fin)->toDateString());

        $this->assertSame('baja_temporal', $resultado['nuevo']->estado);
        $this->assertSame('2026-02-01', Carbon::parse($resultado['nuevo']->fecha_inicio)->toDateString());
        // Llega hasta el fin original del base, no solo hasta fin de mes: no
        // hace falta indicar un mes final, se calcula solo.
        $this->assertSame('2026-11-30', Carbon::parse($resultado['nuevo']->fecha_fin)->toDateString());
    }

    public function test_insertar_intermedio_sin_segmento_antes_cuando_empieza_en_el_mismo_mes()
    {
        $persona = Persona::factory()->create();
        $base = $this->estado($persona, 'activo', '2025-12-01', '2026-11-30');

        $resultado = $this->service()->insertarIntermedio(
            $base,
            'baja_temporal',
            Carbon::parse('2025-12-01'),
            null,
            'Tester',
            true
        );

        $this->assertArrayNotHasKey('antes', $resultado);
        $this->assertSame('2025-12-01', Carbon::parse($resultado['nuevo']->fecha_inicio)->toDateString());
        $this->assertSame('2026-11-30', Carbon::parse($resultado['nuevo']->fecha_fin)->toDateString());
        $this->assertDatabaseMissing('historial_estados', ['id' => $base->id]);
    }

    public function test_insertar_intermedio_rechaza_mes_fuera_del_segmento_base()
    {
        $persona = Persona::factory()->create();
        $base = $this->estado($persona, 'activo', '2025-12-01', '2026-05-31');

        $this->expectException(HistorialEstadoException::class);

        $this->service()->insertarIntermedio(
            $base,
            'baja_temporal',
            Carbon::parse('2026-06-01'),
            null,
            'Tester',
            true
        );
    }

    public function test_insertar_intermedio_rechaza_si_el_base_no_tiene_estado_posterior()
    {
        $persona = Persona::factory()->create();
        $base = $this->estado($persona, 'activo', '2025-12-01');

        $this->expectException(HistorialEstadoException::class);

        $this->service()->insertarIntermedio(
            $base,
            'baja_temporal',
            Carbon::parse('2026-02-01'),
            null,
            'Tester',
            true
        );
    }

    public function test_no_superusuario_no_puede_gestionar_el_ultimo_mes_real_solo_el_mes_extra()
    {
        // Último mes real registrado en el sistema: mayo 2026. El mes extra
        // (el único gestionable por un no-superusuario) es junio 2026.
        $gestion = Gestion::factory()->create(['gestion' => 2026]);
        Mes::create(['mes' => 5, 'id_gestion' => $gestion->id_gestion, 'monto' => 0, 'presupuesto' => 0]);

        $persona = Persona::factory()->create();
        $mayo = $this->estado($persona, 'activo', '2026-01-01', '2026-04-30');
        $this->estado($persona, 'baja_temporal', '2026-05-01');

        $this->assertTrue($this->service()->esMesVigente(Carbon::parse('2026-06-01')));
        $this->assertFalse($this->service()->esMesVigente(Carbon::parse('2026-05-01')));
        $this->assertFalse($this->service()->esMesVigente(Carbon::parse('2026-01-01')));

        $this->expectException(HistorialEstadoException::class);

        // Mayo es el último mes real, pero ya NO es gestionable por un
        // no-superusuario (solo el mes extra, junio, lo es).
        $this->service()->editarSoloEstado($mayo, 'pagos_suspendidos', null, 'Tester', false);
    }

    public function test_no_superusuario_si_puede_gestionar_el_mes_extra()
    {
        $gestion = Gestion::factory()->create(['gestion' => 2026]);
        Mes::create(['mes' => 5, 'id_gestion' => $gestion->id_gestion, 'monto' => 0, 'presupuesto' => 0]);

        $persona = Persona::factory()->create();
        $this->estado($persona, 'activo', '2026-01-01', '2026-04-30');
        $this->estado($persona, 'baja_temporal', '2026-05-01', '2026-05-31');
        $mesExtra = $this->estado($persona, 'activo', '2026-06-01');

        $actualizado = $this->service()->editarSoloEstado($mesExtra, 'baja_definitiva', null, 'Tester', false);

        $this->assertSame('baja_definitiva', $actualizado->estado);
    }

    public function test_insertar_intermedio_rechaza_a_no_superusuario_aunque_sea_el_mes_extra()
    {
        $gestion = Gestion::factory()->create(['gestion' => 2026]);
        Mes::create(['mes' => 5, 'id_gestion' => $gestion->id_gestion, 'monto' => 0, 'presupuesto' => 0]);

        $persona = Persona::factory()->create();
        $base = $this->estado($persona, 'activo', '2025-12-01', '2026-11-30');

        $this->expectException(HistorialEstadoException::class);

        $this->service()->insertarIntermedio($base, 'baja_temporal', Carbon::parse('2026-06-01'), null, 'Tester', false);
    }
}
