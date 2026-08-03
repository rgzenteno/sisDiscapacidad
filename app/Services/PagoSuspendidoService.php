<?php

namespace App\Services;

use App\Exceptions\PagoSuspendidoException;
use App\Models\PagoSuspendido;
use App\Models\Persona;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Suspensión de pagos: decisión interna de UMADIS sobre si soltar la plata
 * de un beneficiario ese mes o no. A propósito NO pasa por
 * HistorialEstadoService ni sus restricciones de mes vigente/superusuario —
 * nunca representó la realidad que le importa a SIGEP (el beneficiario sigue
 * "activo" para ellos), así que cualquier usuario con permiso puede
 * suspender o reactivar cualquier mes, pasado o futuro, sin alterar el
 * reporte oficial.
 */
class PagoSuspendidoService
{
    public function __construct(private LogService $logService)
    {
    }

    /**
     * Abre una suspensión indefinida desde el mes indicado. Solo puede haber
     * una suspensión abierta a la vez por persona.
     */
    public function suspender(string $idPersona, Carbon $fechaInicio, string $observaciones, string $usuario): PagoSuspendido
    {
        $abierta = PagoSuspendido::where('id_persona', $idPersona)->abierta()->first();

        if ($abierta) {
            throw new PagoSuspendidoException(
                'Este beneficiario ya tiene una suspensión de pagos activa desde ' . Carbon::parse($abierta->fecha_inicio)->translatedFormat('F Y') . '; reactive esa antes de crear otra.'
            );
        }

        return DB::transaction(function () use ($idPersona, $fechaInicio, $observaciones, $usuario) {
            $suspension = PagoSuspendido::create([
                'id_persona' => $idPersona,
                'fecha_inicio' => $fechaInicio->toDateString(),
                'fecha_fin' => null,
                'observaciones' => $observaciones,
                'usuario_modificacion' => $usuario,
            ]);

            $nombrePersona = $this->nombrePersona($idPersona);

            // La descripción no repite quién hizo el cambio: eso ya queda
            // registrado por causedBy() en LogService, ponerlo acá sería
            // redundante. En cambio sí necesita el nombre del beneficiario
            // afectado, que causedBy() no captura.
            $this->logService->logCreation(
                'PagoSuspendido',
                $suspension,
                "Se suspendieron los pagos de {$nombrePersona} desde {$fechaInicio->translatedFormat('F Y')}.",
                null,
                [
                    'fecha_inicio' => $fechaInicio->toDateString(),
                    'observaciones' => $observaciones,
                ]
            );

            return $suspension;
        });
    }

    /**
     * Cierra la suspensión: todos los meses que cubría (pasados sin pago y
     * futuros) vuelven a estar disponibles para cobro.
     */
    public function reactivar(PagoSuspendido $suspension, string $usuario): void
    {
        DB::transaction(function () use ($suspension, $usuario) {
            $nombrePersona = $this->nombrePersona($suspension->id_persona);

            $this->logService->logDeletion(
                'PagoSuspendido',
                $suspension,
                "Se reactivaron los pagos de {$nombrePersona} (se levantó la suspensión iniciada en "
                    . Carbon::parse($suspension->fecha_inicio)->translatedFormat('F Y') . ').',
                [
                    'fecha_inicio' => $suspension->fecha_inicio->toDateString(),
                    'observaciones' => $suspension->observaciones,
                ]
            );

            $suspension->delete();
        });
    }

    private function nombrePersona(string $idPersona): string
    {
        $persona = Persona::find($idPersona);

        return $persona
            ? ucwords(strtolower("{$persona->nombre_persona} {$persona->apellido_persona}"))
            : 'un beneficiario';
    }
}
