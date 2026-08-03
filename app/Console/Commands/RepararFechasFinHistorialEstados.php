<?php

namespace App\Console\Commands;

use App\Models\HistorialEstados;
use App\Services\LogService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepararFechasFinHistorialEstados extends Command
{
    protected $signature = 'historial:reparar-fechas-fin {--apply : Aplica los cambios detectados. Por defecto el comando solo reporta, sin escribir nada.}';

    protected $description = 'Recalcula fecha_fin en historial_estados a partir de la secuencia de fecha_inicio de cada persona, para corregir inconsistencias generadas por bugs ya corregidos en PersonaController (estado() comparaba contra fecha_registro en vez de fecha_inicio; destroyEstado() forzaba fecha_fin = null al fusionar).';

    public function handle(LogService $logService): int
    {
        $apply = (bool) $this->option('apply');

        $this->info($apply
            ? 'Modo APLICAR: los cambios se guardarán en la base de datos.'
            : 'Modo REPORTE (dry-run): no se escribe nada. Vuelve a ejecutar con --apply para aplicar.');
        $this->newLine();

        [$cambios, $conflictos] = $this->calcularCambios();

        if ($cambios->isNotEmpty()) {
            $this->warn('Filas con fecha_fin a corregir:');
            $this->table(
                ['id_persona', 'historial_id', 'estado', 'fecha_inicio', 'fin_actual', 'fin_correcto'],
                $cambios->map(fn($c) => [
                    $c['id_persona'],
                    $c['historial_id'],
                    $c['estado'],
                    $c['fecha_inicio'],
                    $c['fin_actual'] ?? 'Sin definir',
                    $c['fin_correcto'] ?? 'Sin definir',
                ])->all()
            );
        }

        if ($conflictos->isNotEmpty()) {
            $this->error('Personas con conflicto real (dos o más estados con fecha_inicio en el mismo mes) — requieren revisión manual, no se tocaron:');
            $this->table(
                ['id_persona', 'meses_en_conflicto', 'estados'],
                $conflictos->map(fn($c) => [$c['id_persona'], $c['meses'], $c['estados']])->all()
            );
        }

        $personasConCambios = $cambios->pluck('id_persona')->unique()->count();

        $this->newLine();
        $this->info("Resumen: {$personasConCambios} personas con fecha_fin a corregir ({$cambios->count()} filas), {$conflictos->count()} personas con conflicto real (sin tocar).");

        if (!$apply) {
            if ($cambios->isNotEmpty()) {
                $this->comment('Ejecuta con --apply para guardar estos cambios.');
            }

            return self::SUCCESS;
        }

        if ($cambios->isEmpty()) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($cambios, $logService) {
            foreach ($cambios as $cambio) {
                $historial = HistorialEstados::find($cambio['historial_id']);

                if (!$historial) {
                    continue;
                }

                $finAnterior = $cambio['fin_actual'];
                $finNuevo = $cambio['fin_correcto'];

                $historial->fecha_fin = $finNuevo;
                $historial->save();

                $logService->logUpdate(
                    'HistorialEstados',
                    $historial,
                    [
                        'campos_modificados' => ['fecha fin' => $finNuevo ?? 'Sin definir'],
                        'valores_anteriores' => ['fecha fin' => $finAnterior ?? 'Sin definir'],
                        'valores_nuevos'     => ['fecha fin' => $finNuevo ?? 'Sin definir'],
                    ],
                    'Corrección automática de fecha_fin (comando historial:reparar-fechas-fin) para mantener continuidad con el siguiente estado registrado.'
                );
            }
        });

        $this->info("Aplicado: {$cambios->count()} filas corregidas.");

        return self::SUCCESS;
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection}
     */
    private function calcularCambios(): array
    {
        $cambios = collect();
        $conflictos = collect();

        HistorialEstados::orderBy('id_persona')
            ->orderBy('fecha_inicio')
            ->orderBy('id')
            ->get()
            ->groupBy('id_persona')
            ->each(function ($registros, $idPersona) use (&$cambios, &$conflictos) {
                $ordenados = $registros->sortBy('fecha_inicio')->values();

                $meses = $ordenados->map(fn($h) => Carbon::parse($h->fecha_inicio)->format('Y-m'));

                if ($meses->duplicates()->isNotEmpty()) {
                    $conflictos->push([
                        'id_persona' => $idPersona,
                        'meses' => $meses->duplicates()->unique()->implode(', '),
                        'estados' => $ordenados->map(fn($h) => "#{$h->id} {$h->estado} ({$h->fecha_inicio})")->implode(' | '),
                    ]);
                    return;
                }

                foreach ($ordenados as $i => $historial) {
                    $siguiente = $ordenados->get($i + 1);
                    $finEsperado = $siguiente
                        ? Carbon::parse($siguiente->fecha_inicio)->subDay()->toDateString()
                        : null;

                    $finActual = $historial->fecha_fin
                        ? Carbon::parse($historial->fecha_fin)->toDateString()
                        : null;

                    if ($finActual !== $finEsperado) {
                        $cambios->push([
                            'id_persona' => $idPersona,
                            'historial_id' => $historial->id,
                            'estado' => $historial->estado,
                            'fecha_inicio' => Carbon::parse($historial->fecha_inicio)->toDateString(),
                            'fin_actual' => $finActual,
                            'fin_correcto' => $finEsperado,
                        ]);
                    }
                }
            });

        return [$cambios, $conflictos];
    }
}
