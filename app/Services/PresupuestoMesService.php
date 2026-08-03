<?php

namespace App\Services;

use App\Models\Mes;
use Illuminate\Support\Facades\DB;

/**
 * Mantiene `mes.presupuesto` sincronizado con los toggles individuales de
 * `habilitado.habilitado`. El presupuesto de un mes se calcula una única vez
 * como (cantidad_habilitados_iniciales × mes.monto) al crear el mes (ver
 * GestionController::previsualizarMes/addMes) — este servicio lo ajusta cada
 * vez que UN habilitado cambia de estado después de eso, sumando o restando
 * `mes.monto` (no un monto global fijo: cada mes conserva el "Monto -
 * Persona" vigente cuando se creó, así que meses distintos pueden tener
 * montos distintos).
 *
 * Puntos que llaman a este servicio (los 5 lugares donde se togglea
 * habilitado.habilitado):
 * - HabilitadoController::store()  (Habilitados/habilitar.vue)
 * - HabilitadoController::edit()   (Persona/index.vue y Habilitados/habilitar.vue)
 * - PagoController::anular()       (BandejaPagos/index.vue)
 * - PagoController::reactivar()    (BandejaPagos/index.vue)
 * - HistorialEstadoService::aplicarCascadaHabilitado() (cambios de Estado en Persona/index.vue)
 */
class PresupuestoMesService
{
    /**
     * @param Mes $mes Mes al que pertenece el habilitado que cambió.
     * @param bool $anterior Valor de habilitado.habilitado antes del cambio.
     * @param bool $nuevo Valor de habilitado.habilitado después del cambio.
     * @return array{anterior: int, nuevo: int}|null Valores de presupuesto
     *   antes/después del ajuste (para mostrarlos en la Bitácora), o null si
     *   no hubo cambio real.
     */
    public function ajustarPorHabilitado(?Mes $mes, bool $anterior, bool $nuevo): ?array
    {
        if (!$mes || $anterior === $nuevo) {
            return null;
        }

        $delta = $nuevo ? $mes->monto : -$mes->monto;
        $presupuestoAnterior = $mes->presupuesto;
        $presupuestoNuevo = $presupuestoAnterior + $delta;

        // Atómico (increment a nivel SQL): evita condición de carrera si dos
        // acciones tocan el mismo mes al mismo tiempo (ej. BandejaPagos y
        // Persona abiertos en paralelo).
        DB::table('mes')->where('id_mes', $mes->id_mes)->increment('presupuesto', $delta);

        return [
            'anterior' => $presupuestoAnterior,
            'nuevo' => $presupuestoNuevo,
        ];
    }
}
