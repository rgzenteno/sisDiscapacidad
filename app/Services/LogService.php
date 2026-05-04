<?php

namespace App\Services;

use Spatie\Activitylog\Facades\Activity;
use Illuminate\Support\Facades\Auth;

class LogService
{
    private const HIGH_VOLUME_ACTIONS = [
        'habilitar',
        'deshabilitar',
        'habilitar-indefinido',
    ];

    public function log(
        string $action,
        string $module,
        string $description,
        $subject = null,
        array $extraData = [],
        $affectedUser = null
    ) {
        if (in_array($action, self::HIGH_VOLUME_ACTIONS)) {
            return $this->logCompact($action, $module, $description, $subject, $extraData, $affectedUser);
        }

        return $this->logFull($action, $module, $description, $subject, $extraData, $affectedUser);
    }

    // ─── Agrega este método privado helper ────────────────────────────────
    private function isEloquentModel($subject): bool
    {
        return $subject instanceof \Illuminate\Database\Eloquent\Model;
    }

    // ─── Alto volumen: guarda solo lo esencial ────────────────────────────
    private function logCompact(
        string $action,
        string $module,
        string $description,
        $subject = null,
        array $extraData = [],
        $affectedUser = null
    ) {
        $properties = [
            'ip'      => request()->ip(),
            'action'  => $action,
            'module'  => $module,
            'summary' => $extraData['summary'] ?? [],
        ];

        if ($affectedUser) {
            $properties['affected_user_id'] = $affectedUser->id;
        }

        $activity = Activity::inLog('high_volume')  // ← log_name separado
            ->withProperties($properties)
            ->causedBy(Auth::user());

        if ($subject && $this->isEloquentModel($subject)) {
            $activity->performedOn($subject);
        }

        return $activity->log($description);
    }

    // ─── Volumen normal: igual que antes pero sin datos duplicados ────────
    private function logFull(
        string $action,
        string $module,
        string $description,
        $subject = null,
        array $extraData = [],
        $affectedUser = null,
        string $logName = 'default'
    ) {
        $properties = array_merge([
            'ip'          => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'action'      => $action,
            'module'      => $module,
            'timestamp'   => now()->toIso8601String(),
            'environment' => app()->environment(),
        ], $extraData);

        if ($affectedUser) {
            $properties['affected_user'] = [
                'id'     => $affectedUser->id,
                'nombre' => $affectedUser->nombre,
                'email'  => $affectedUser->email ?? null,
                'type'   => 'target',
            ];
        }

        if ($subject && method_exists($subject, 'toArray')) {
            $properties['entity'] = [
                'id'         => $subject->getKey(),
                'type'       => class_basename($subject),
                'identifier' => $subject->nombre ?? $subject->getKey(),
            ];
        }

        $activity = Activity::inLog($logName)  // ← cambiar esta línea
            ->withProperties($properties)
            ->causedBy(Auth::user());

        if ($subject && $this->isEloquentModel($subject)) {
            $activity->performedOn($subject);
        }

        return $activity->log($description);
    }

    // ─── Métodos públicos ─────────────────────────────────────────────────

    public function logAuth(string $action, $user = null, string $description = null, array $extraData = [])
    {
        $targetUser = $user ?? Auth::user();
        $nombre = $targetUser?->nombre ?? $targetUser?->email ?? 'Desconocido';

        if ($description === null) {
            if ($action === 'login') {
                $description = "El usuario {$nombre} inició sesión en el sistema.";
            } elseif ($action === 'logout') {
                $description = "El usuario {$nombre} cerró sesión en el sistema.";
            } elseif ($action === 'session-expired') {
                $description = "La sesión del usuario {$nombre} expiró automáticamente.";
            } else {
                $description = "El usuario {$nombre} realizó el evento '{$action}' en el sistema.";
            }
        }

        return $this->logFull(
            $action,
            'Autenticación',
            $description,
            $targetUser,
            array_merge([
                'auth_details' => [
                    'browser' => request()->userAgent(),
                    'ip'      => request()->ip(),
                ],
            ], $extraData)
        );
    }

    public function logPayment($subject, $data, string $description = null)
    {
        return $this->logFull(
            'pago realizado',
            'BandejaPago',
            $description ?? "Pago procesado",
            $subject,
            [
                'detalles' => [
                    'beneficiario'  => $data->nombre,
                    'mes'           => $data->periodo,
                    'monto'         => $data->monto,
                    'numero_boleta' => $data->numero_boleta,
                    'tipo_pago'     => $data->tipo_bono  ?? null,
                    'ci'            => $data->ci          ?? null,
                ],
            ]
        );
    }

    public function logBoletaDescarga($pago, int $idPago, string $gestion, string $mes, string $beneficiario = null, string $description = null)
    {
        return $this->logFull(
            'boleta_descargada',
            'BandejaPago',
            $description ?? "Boleta descargada del pago de {$mes} - {$gestion} a {$beneficiario} ",
            $pago,
            [
                'id_pago'      => $idPago,
                'gestion'      => $gestion,
                'mes'          => $mes,
                'beneficiario' => $beneficiario,
            ],
            null,
            'boleta_descarga'
        );
    }

    public function logBoletaImpresion($pago, int $idPago, string $gestion, string $mes, string $beneficiario = null, string $description = null)
    {
        return $this->logFull(
            'boleta_impresa',
            'BandejaPago',
            $description ?? "Boleta impresa del pago de {$mes} - {$gestion} a {$beneficiario}",
            $pago,
            [
                'id_pago'      => $idPago,
                'gestion'      => $gestion,
                'mes'          => $mes,
                'beneficiario' => $beneficiario,
            ],
            null,
            'boleta_impresion'
        );
    }

    public function logHabilitar(
        string $action,
        string $module,
        $subject,
        string $description = null,
        $affectedUser = null,
        array $extraData = []
    ) {
        return $this->log(
            $action,
            $module,
            $description ?? "Se ha {$action} el registro: {$this->getModelIdentifier($subject)}",
            $subject,
            [
                'summary' => [
                    'estado_anterior' => $extraData['antes']      ?? null,
                    'estado_nuevo'    => $extraData['despues']     ?? null,
                    'indefinido'      => $extraData['indefinido']  ?? false,
                ],
            ],
            $affectedUser
        );
    }

    public function logCreation(string $module, $subject, string $description = null, $affectedUser = null, array $campos = [])
    {
        return $this->logFull(
            'crear',
            $module,
            $description ?? "Nuevo registro creado en {$module}: {$this->getModelIdentifier($subject)}",
            $subject,
            [
                'details' => [
                    'before' => null,
                    'after'  => !empty($campos) ? $campos : $subject->toArray(),
                ],
            ],
            $affectedUser
        );
    }

    public function logHabilitacionMasiva(
        string $module,
        $subject,
        array $resumen,
        string $description = null
    ) {
        return $this->logFull(
            'importar',
            $module,
            $description ?? "Habilitación masiva — Mes: {$resumen['mes']}",
            $subject,
            [
                'details' => [
                    'mes'                       => $resumen['mes'],
                    'monto'                     => $resumen['monto'],
                    'presupuesto'               => $resumen['presupuesto'],
                    'beneficiarios_habilitados' => $resumen['beneficiarios_habilitados'],
                    'total_records'             => $resumen['total'],
                    'successful'                => $resumen['successful'],
                    'failed'                    => $resumen['failed'],
                    'filename'                  => $resumen['filename'],
                    'errors'                    => $resumen['errors'] ?? [],
                    'import_date'               => now()->toDateTimeString(),
                ],
            ]
        );
    }

    public function logUpdate($module, $model, $changes = null, $description)
    {
        $extraData = [];

        if ($changes && is_array($changes)) {
            $extraData['details'] = [
                'before'   => $changes['valores_anteriores'] ?? null,
                'after'    => $changes['valores_nuevos']     ?? null,
                'modified' => $changes['campos_modificados'] ?? null,
            ];
        }

        $this->logFull('actualizar', $module, $description, $model, $extraData);
    }

    public function logDeletion($module, $model, $description, array $campos = [])
    {
        $extraData = [];

        // Solo agrega details si explícitamente se pasaron campos
        if (!empty($campos)) {
            $extraData['details'] = [
                'before' => $campos,
            ];
        }

        $this->logFull('eliminar', $module, $description, $model, $extraData);
    }

    public function logPasswordReset($model, $description)
    {
        $this->logFull(
            'reset contraseña',
            'Usuario',
            $description,
            $model,
            []
        );
    }

    public function logImport($module, $summary, $description = null)
    {
        $this->logFull(
            'importar',
            $module,
            $description ?? "Importación masiva en {$module}",
            null,
            [
                'details' => [
                    'total_records' => $summary['total']     ?? 0,
                    'successful'    => $summary['successful'] ?? 0,
                    'failed'        => $summary['failed']     ?? 0,
                    'filename'      => $summary['filename']   ?? null,
                    'errors'        => $summary['errors']     ?? [],
                    'import_date'   => now()->toDateTimeString(),
                ],
            ]
        );
    }

    public function logView(string $module, $subject, string $description = null, $affectedUser = null)
    {
        return $this->logFull(
            'ver',
            $module,
            $description ?? "Registro visualizado en {$module}: {$this->getModelIdentifier($subject)}",
            $subject,
            [],
            $affectedUser
        );
    }

    public function logReportPrint(
        string $module,
        string $reportName,
        array $parameters = [],
        $subject = null,
        string $description = null,
        string $format = 'PDF'
    ) {
        return $this->logFull(
            'reporte imprimido',
            $module,
            $description ?? "Reporte '{$reportName}' impreso en formato {$format}",
            $subject,
            [
                'report_details' => [
                    'name'           => $reportName,
                    'format'         => $format,
                    'parameters'     => $parameters,
                    'print_date'     => now()->toDateTimeString(),
                    'related_entity' => $subject ? [
                        'id'   => $subject->getKey(),
                        'type' => class_basename($subject),
                    ] : null,
                ],
            ]
        );
    }

    public function logError(string $module, string $error, $subject = null, array $context = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        return $this->logFull(
            'error',
            $module,
            $error,
            $subject,
            [
                'severity'      => 'error',
                'file'          => $trace[0]['file'] ?? null,
                'line'          => $trace[0]['line'] ?? null,
                'error_context' => $context,
            ]
        );
    }

    protected function getModelIdentifier($model): string
    {
        return $model->name   ??
            $model->nombre ??
            $model->title  ??
            $model->email  ??
            "ID: {$model->getKey()}";
    }
}
