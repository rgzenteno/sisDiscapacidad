<?php

namespace App\Services;

use Spatie\Activitylog\Facades\Activity;
use Illuminate\Support\Facades\Auth;

class LogService
{
    /**
     * Registra una actividad en el sistema
     *
     * @param string $module      - Módulo del sistema
     * @param string $reportName  - Nombre del reporte
     * @param array  $parameters  - Parámetros utilizados para generar el reporte
     * @param mixed  $subject     - Modelo relacionado con el reporte (opcional)
     * @param string $description - Descripción personalizada (opcional)
     * @param mixed  $affectedUser - Usuario afectado por la acción (opcional)
     * @param string $format      - Formato del reporte (PDF, Excel, etc.)
     * @return mixed
     * */
    public function logReportPrint(
        string $module,
        string $reportName,
        array $parameters = [],
        $subject = null,
        string $description = null,
        string $format = 'PDF'
    ) {
        $identifier = $subject ? $this->getModelIdentifier($subject) : $reportName;

        return $this->log(
            'reporte imprimido',
            $module,
            $description ?? "Reporte '{$reportName}' impreso en formato {$format}",
            $subject,
            [
                'report_details' => [
                    'name' => $reportName,
                    'format' => $format,
                    'parameters' => $parameters,
                    'print_date' => now()->toDateTimeString(),
                    'related_entity' => $subject ? [
                        'id' => $subject->id,
                        'type' => class_basename($subject)
                    ] : null
                ]
            ]
        );
    }

    public function log(
        string $action,
        string $module,
        string $description,
        $subject = null,
        array $extraData = [],
        $affectedUser = null
    ) {
        $properties = array_merge([
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'action' => $action,
            'module' => $module,
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
        ], $extraData);

        // Agregar información del usuario afectado si existe
        if ($affectedUser) {
            $properties['affected_user'] = [
                'id' => $affectedUser->id,
                'nombre' => $affectedUser->nombre,
                'email' => $affectedUser->email ?? null,
                'type' => 'target'
            ];
        }

        // Si el subject es un modelo, agregar información relevante
        if ($subject && method_exists($subject, 'toArray')) {
            $properties['entity'] = [
                'id' => $subject->id,
                'type' => class_basename($subject),
                'identifier' => $subject->nombre ?? $subject->id
            ];
        }

        // Obtener el usuario autenticado
        $user = Auth::user();

        // Crear la actividad y asignar el usuario autenticado como `causer`
        $activity = Activity::withProperties($properties)
            ->causedBy($user); // 🔹 Asigna el usuario autenticado como `causer`

        if ($subject) {
            $activity->performedOn($subject);
        }

        $activity->log($description);

        return $activity;
    }


    /**
     * Registra una acción de creación
     */
    public function logCreation(string $module, $subject, string $description = null, $affectedUser = null)
    {
        $identifier = $this->getModelIdentifier($subject);
        return $this->log(
            'crear',
            $module,
            $description ?? "Nuevo registro creado en {$module}: {$identifier}",
            $subject,
            [
                'details' => [
                    'before' => null,
                    'after' => $subject->toArray()
                ]
            ],
            $affectedUser
        );
    }

    public function logUpdate($module, $model, $changes = null, $description)
    {
        $extraData = [
            'environment' => app()->environment(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ];

        // Si se proporcionan cambios, agregarlos
        if ($changes && is_array($changes)) {
            $extraData['details'] = [
                'before' => $changes['valores_anteriores'] ?? null,
                'after' => $changes['valores_nuevos'] ?? null,
                'modified' => $changes['campos_modificados'] ?? null
            ];
        }

        $this->log('actualizar', $module, $description, $model, $extraData);
    }

    public function logDeletion($module, $model, $description)
    {
        $this->log('eliminar', $module, $description, $model, [
            'details' => [
                'before' => $model->toArray(),  // Datos que se eliminaron
            ],
            'environment' => app()->environment(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    public function logImport($module, $summary, $description = null)
    {
        $this->log('importar', $module, $description ?? "Importación masiva en {$module}", null, [
            'details' => [
                'total_records' => $summary['total'] ?? 0,
                'successful' => $summary['successful'] ?? 0,
                'failed' => $summary['failed'] ?? 0,
                'filename' => $summary['filename'] ?? null,
                'errors' => $summary['errors'] ?? [],
                'import_date' => now()->toDateTimeString()
            ]
        ]);
    }

    /**
     * Registra una acción de visualización
     */
    public function logView(string $module, $subject, string $description = null, $affectedUser = null)
    {
        $identifier = $this->getModelIdentifier($subject);
        return $this->log(
            'ver',
            $module,
            $description ?? "Registro visualizado en {$module}: {$identifier}",
            $subject,
            [],
            $affectedUser
        );
    }

    public function logHabilitar(string $action, string $module, $subject, string $description = null, $affectedUser = null, array $extraData = [])
    {
        $identifier = $this->getModelIdentifier($subject);
        return $this->log(
            $action,
            $module,
            $description ?? "Se ha {$action} registro en {$module}: {$identifier}",
            $subject,
            $extraData, // Ahora pasamos los datos adicionales
            $affectedUser
        );
    }

    /**
     * Registra un error en el sistema
     */
    public function logError(string $module, string $error, $subject = null, array $context = [])
    {
        return $this->log(
            'error',
            $module,
            $error,
            $subject,
            array_merge([
                'error_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
                'error_context' => $context
            ], [
                'severity' => 'error',
                'file' => debug_backtrace()[0]['file'] ?? null,
                'line' => debug_backtrace()[0]['line'] ?? null
            ])
        );
    }

    /**
     * Obtiene un identificador legible para el modelo
     */
    protected function getModelIdentifier($model): string
    {
        return $model->name ??
            $model->nombre ??
            $model->title ??
            $model->email ??
            "ID: {$model->id}";
    }
}