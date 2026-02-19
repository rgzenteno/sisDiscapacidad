<?php

use App\Models\MensajesEnvio;
use App\Models\NotificacionTutor;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;
use App\Models\Notificaciones;
use App\Jobs\EnviarMensajesBienvenido;
use App\Jobs\EnviarMensajesCarnetVencido;
use App\Jobs\EnviarMensajesDiaDiscapacidad;
use App\Jobs\EnviarMensajesJuegosDeportivos;
use App\Jobs\EnviarMensajesSinCarnet;
use App\Models\Carnet;
use App\Models\Persona;
use App\Models\Tutor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Symfony\Component\Console\Output\BufferedOutput as OutputBuffer;

/* //Tarea Modificar estado de Tiempo a 0 si el mes es distinto
Schedule::call(function () {
    try {
        // Obtener la fecha actual
        $fechaHoy = Carbon::now('America/La_Paz')->format('Y-m-d');
        // Recuperar y actualizar los registros que cumplan con las condiciones
        MensajesEnvio::query()
            ->where('tiempo', 1) // Filtrar registros donde el tiempo sea 1
            ->whereDate('fecha_envio', '!=', $fechaHoy) // Verificar que la fecha de envío sea diferente a la fecha actual
            ->update(['tiempo' => 0]); // Cambiar el valor de 'tiempo' a 0

    } catch (\Exception $e) {
        Log::error("Error general en el proceso de cambiar el estado del tiempo " . $e->getMessage());
    }
})->everyMinute();

//Tarea Carnet Vencido
Schedule::call(function () {
    try {
        $output = new OutputBuffer();
        $fechaHoy = Carbon::now('America/La_Paz')->format('Y-m-d');
        $queueName = 'carnetvencido';

        // Primero verificamos si hay registros que cumplan las condiciones
        $carnetVencidosQuery = Carnet::query()
            ->join('persona', 'carnet.id_persona', '=', 'persona.id_persona')
            ->join('tutor', 'persona.id_tutor', '=', 'tutor.id_tutor')
            ->where('persona.estado', '!=', 'bajadefinitiva')
            ->where('carnet.fecha_vencimiento', '<', $fechaHoy)
            ->whereNotExists(function ($subquery) {
                $subquery->select(DB::raw(1))
                    ->from('mensajes_envio as me')
                    ->whereColumn('me.id_tutor', 'tutor.id_tutor')
                    ->where('me.plantilla', 'carnetvencido')
                    ->where('me.tiempo', 1);
            });

        $existenRegistros = (clone $carnetVencidosQuery)->exists();

        if ($existenRegistros) {
            // Crear la notificación general
            $notificacion = Notificaciones::create([
                'plantilla' => $queueName,
                'fecha_not' => $fechaHoy,
                'hora_not' => null,
                'estado_not' => 'pendiente',
            ]);

            // Procesar en chunks
            $carnetVencidosQuery
                ->select(
                    'tutor.id_tutor',
                    'tutor.nombre_tutor',
                    'tutor.apellido_tutor',
                    'tutor.telefono',
                    'tutor.whatsapp_not',
                    'tutor.sms_not',
                    'tutor.email_not',
                    'tutor.email',
                    'persona.nombre_persona',
                    'persona.apellido_persona'
                )
                ->chunk(100, function ($carnetVencidos) use ($notificacion, $queueName) {
                    try {
                        foreach ($carnetVencidos as $datos) {
                            // Capitalizar nombre y apellido de la persona
                            $nombrePersona = ucwords(strtolower("{$datos->nombre_persona} {$datos->apellido_persona}"));

                            // Generar el mensaje
                            $mensaje = sprintf(
                                'El carnet del beneficiario/a %s se encuentra vencido, por favor actualice su documentación para evitar inconvenientes.',
                                $nombrePersona
                            );

                            // Guardar notificación en la base de datos
                            NotificacionTutor::create([
                                'id_tutor' => $datos->id_tutor,
                                'mensaje' => $mensaje,
                                'estado' => '1',
                                'hora' => Carbon::now('America/La_Paz')->format('Y-m-d H:i:s'),
                            ]);
                        }

                        // Despachar el job para envío de mensajes
                        dispatch(new EnviarMensajesCarnetVencido(
                            $notificacion->id_not,
                            $carnetVencidos,
                            $queueName,
                            $queueName
                        ))->onQueue($queueName);

                        Log::info("Despachando job para notificación de carnets vencidos", [
                            'queue' => $queueName,
                            'cantidad' => $carnetVencidos->count(),
                            'notificacion_id' => $notificacion->id_not
                        ]);
                    } catch (\Exception $e) {
                        Log::error("Error procesando lote de carnets vencidos: " . $e->getMessage(), [
                            'cantidad' => $carnetVencidos->count(),
                            'notificacion_id' => $notificacion->id_not
                        ]);
                    }
                });

            // Procesar la cola
            Artisan::call('queue:work', [
                '--queue' => $queueName,
                '--max-jobs' => 10,
                '--stop-when-empty' => true
            ], $output);
        }
    } catch (\Exception $e) {
        Log::error("Error general en el proceso de carnets vencidos: " . $e->getMessage());
    }
})->everyMinute();

//Tarea estado Activo / Baja Temporal / Baja Definitiva
Schedule::call(function () {
    $output = new OutputBuffer();

    // Define los nombres de las colas que queremos procesar
    $estadosQueues = [
        'bajatemporal',
        'bajadefinitiva',
        'activo'
    ];

    // Busca jobs pendientes en la base de datos para estas colas
    $queues = DB::table('jobs')
        ->select('queue')
        ->whereIn('queue', $estadosQueues)
        ->distinct()
        ->get();

    if ($queues->isNotEmpty()) {
        Log::info("Se encontraron colas de estados para procesar", [
            'colas' => $queues->pluck('queue')->toArray()
        ]);

        foreach ($queues as $queue) {
            $queueName = $queue->queue;

            Log::info("Procesando jobs de estado: {$queueName}", [
                'cola' => $queueName,
                'timestamp' => Carbon::now('America/La_Paz')->format('Y-m-d H:i:s')
            ]);

            try {
                // Procesa los jobs de esta cola
                Artisan::call('queue:work', [
                    '--queue' => $queueName,
                    '--max-jobs' => 10,          // Procesa máximo 10 jobs por vez
                    '--stop-when-empty' => true, // Se detiene cuando la cola está vacía
                    '--tries' => 3               // Número de reintentos si falla
                ], $output);

                // Registra la salida del comando
                Log::info("Resultado del procesamiento de cola {$queueName}", [
                    'output' => $output->fetch()
                ]);
            } catch (\Exception $e) {
                Log::error("Error procesando cola {$queueName}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }
})->everyMinute();

//Tarea pagos Habilitado/Deshabilitado
Schedule::call(function () {
    $output = new OutputBuffer();

    $queues = DB::table('jobs')
        ->select('queue')
        ->whereIn('queue', ['pagosdisponible', 'pagodeshabilitado', 'pagoqr', 'pagotransferencia', 'bienvenida']) // Condición OR
        ->distinct()
        ->get();

    if ($queues->isNotEmpty()) {
        foreach ($queues as $queue) {
            $queueName = $queue->queue;

            // Ejecutar el job para cada plantilla
            Log::info("Ejecutando job para la notificación: {$queueName}", [
                'nombre' => $queueName,
            ]);

            Artisan::call('queue:work', [
                '--queue' => $queueName,
                '--max-jobs' => 10,
                '--stop-when-empty' => true
            ], $output);
        }
    }

    $fechaHoy = Carbon::now('America/La_Paz')->format('Y-m-d');

    $notificaciones = Notificaciones::where('fecha_not', $fechaHoy)
        ->whereIn('estado_not', ['pendiente', 'en_proceso'])
        ->select('id_not', 'plantilla', 'estado_not')
        ->get();

    // Recorremos las notificaciones para hacer las comprobaciones
    foreach ($notificaciones as $notificacion) {
        // Comprobamos la plantilla para ejecutar la cola correspondiente
        $queueName = "pagosdisponible_{$notificacion->id_not}";

        // Ejecutar el job para la notificación
        Log::info("Ejecutando job para la notificación: ", [
            'nombre' => $queueName,
        ]);

        Artisan::call('queue:work', [
            '--queue' => $queueName,
            '--max-jobs' => 50,
            '--stop-when-empty' => true
        ], $output);
    }
})->everyMinute();

//Tarea notificaciones Programadas
Schedule::call(function () {
    // Obtener los jobs pendientes actuales
    $pendingJobs = DB::table('jobs')
        ->select('payload')
        ->get()
        ->map(function ($job) {
            try {
                $payload = json_decode($job->payload, true);

                // Extraer el id_not del job
                if (isset($payload['data']['command'])) {
                    $command = unserialize($payload['data']['command']);

                    // Acceder a la propiedad usando ReflectionProperty para propiedades privadas
                    $reflection = new \ReflectionClass($command);
                    $property = $reflection->getProperty('id_not');
                    $property->setAccessible(true);
                    $id_not = $property->getValue($command);
                    return $id_not;
                }

                return null;
            } catch (\Exception $e) {
                Log::error('Error al procesar job:', [
                    'error' => $e->getMessage(),
                    'payload' => $job->payload
                ]);
                return null;
            }
        })
        ->filter()
        ->unique()
        ->values()  // Esto asegura que el array resultante tenga índices numéricos consecutivos
        ->toArray();

    $notificaciones = Notificaciones::where('estado_not', 'pendiente')
        ->whereNotIn('id_not', $pendingJobs)
        ->select('id_not', 'plantilla', 'total_mensajes')
        ->get();

    foreach ($notificaciones as $notificacion) {
        $id_not = $notificacion->id_not;
        $plantilla = strtolower($notificacion->plantilla);

        // Verificación adicional de seguridad
        if (in_array($id_not, $pendingJobs)) {
            Log::warning("Job ya existente para la notificación: {$id_not}, saltando creación");
            continue;
        }

        // Query base que se reutilizará
        $baseQuery = Tutor::join('persona', 'persona.id_tutor', '=', 'tutor.id_tutor')
            ->leftJoin('mensajes_envio', 'tutor.id_tutor', '=', 'mensajes_envio.id_tutor')
            ->where('persona.estado', '!=', 'bajaDefinitiva');

        switch ($plantilla) {
            case 'bienvenida':
                Log::info('Ejecutando case:', ['case' => $plantilla]);
                $query = clone $baseQuery;
                $query->whereNotExists(function ($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('mensajes_envio as me')
                        ->whereColumn('me.id_tutor', 'tutor.id_tutor')
                        ->where('me.plantilla', 'bienvenida')
                        ->where('me.tiempo', 0);
                })
                    ->select(
                        'tutor.id_tutor',
                        'tutor.nombre_tutor',
                        'tutor.apellido_tutor',
                        'tutor.telefono',
                        'tutor.whatsapp_not',
                        'tutor.sms_not',
                        'tutor.email_not',
                        'tutor.email',
                        DB::raw('COUNT(DISTINCT persona.id_persona) AS persona')
                    )
                    ->groupBy(
                        'tutor.id_tutor',
                        'tutor.nombre_tutor',
                        'tutor.apellido_tutor',
                        'tutor.telefono',
                        'tutor.whatsapp_not',
                        'tutor.sms_not',
                        'tutor.email_not',
                        'tutor.email',
                    );
                $query->chunk(100, function ($lote) use ($id_not, $plantilla) {
                    $queueName = "{$plantilla}_{$id_not}";
                    dispatch(new EnviarMensajesBienvenido($id_not, $plantilla, $lote))
                        ->onQueue($queueName);
                });
                break;

            case 'juegosdeportivos':
                Log::info('Ejecutando caso:', ['caso' => $plantilla]);
                $query = clone $baseQuery;
                $query->whereNotExists(function ($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('mensajes_envio as me')
                        ->whereColumn('me.id_tutor', 'tutor.id_tutor')
                        ->where('me.plantilla', 'juegosdeportivos')
                        ->where('me.tiempo', 0);
                })
                    ->select(
                        'tutor.id_tutor',
                        'tutor.nombre_tutor',
                        'tutor.apellido_tutor',
                        'tutor.telefono',
                        'tutor.whatsapp_not',
                        'tutor.sms_not',
                        'tutor.email_not',
                        'tutor.email',
                        DB::raw('COUNT(DISTINCT persona.id_persona) AS persona')
                    )
                    ->groupBy(
                        'tutor.id_tutor',
                        'tutor.nombre_tutor',
                        'tutor.apellido_tutor',
                        'tutor.telefono',
                        'tutor.whatsapp_not',
                        'tutor.sms_not',
                        'tutor.email_not',
                        'tutor.email',
                    );

                $query->chunk(100, function ($lote) use ($id_not, $plantilla) {
                    $queueName = "{$plantilla}_{$id_not}";
                    dispatch(new EnviarMensajesJuegosDeportivos($id_not, $plantilla, $lote))
                        ->onQueue($queueName);
                });
                break;

            case 'diadiscapacidad':
                Log::info('Ejecutando caso:', ['caso' => $plantilla]);
                $query = clone $baseQuery;
                $query->whereNotExists(function ($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('mensajes_envio as me')
                        ->whereColumn('me.id_tutor', 'tutor.id_tutor')
                        ->where('me.plantilla', 'diadiscapacidad')
                        ->where('me.tiempo', 0);
                })
                    ->select(
                        'tutor.id_tutor',
                        'tutor.nombre_tutor',
                        'tutor.apellido_tutor',
                        'tutor.telefono',
                        'tutor.whatsapp_not',
                        'tutor.sms_not',
                        'tutor.email_not',
                        'tutor.email',
                        DB::raw('COUNT(DISTINCT persona.id_persona) AS persona')
                    )
                    ->groupBy(
                        'tutor.id_tutor',
                        'tutor.nombre_tutor',
                        'tutor.apellido_tutor',
                        'tutor.telefono',
                        'tutor.whatsapp_not',
                        'tutor.sms_not',
                        'tutor.email_not',
                        'tutor.email'
                    );

                $query->chunk(100, function ($lote) use ($id_not, $plantilla) {
                    $queueName = "{$plantilla}_{$id_not}";
                    dispatch(new EnviarMensajesDiaDiscapacidad($id_not, $plantilla, $lote))
                        ->onQueue($queueName);
                });
                break;

            case 'sincarnet':
                Log::info('Ejecutando caso:', ['caso' => $plantilla]);
                Persona::query()
                    ->leftJoin('carnet', 'persona.id_persona', '=', 'carnet.id_persona')
                    ->join('tutor', 'persona.id_tutor', '=', 'tutor.id_tutor')
                    ->whereNull('carnet.id_persona')
                    ->where('persona.estado', 'activo')
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                        ->from('mensajes_envio as me')
                        ->whereColumn('me.id_tutor', 'tutor.id_tutor')
                        ->where('me.plantilla', 'sincarnet')
                        ->where('me.tiempo', 0);
                    })
                    ->select(
                        'tutor.id_tutor',
                        'tutor.nombre_tutor',
                        'tutor.apellido_tutor',
                        'tutor.telefono',
                        'tutor.whatsapp_not',
                        'tutor.sms_not',
                        'tutor.email_not',
                        'tutor.email',
                        'persona.nombre_persona',
                        'persona.apellido_persona'
                    )
                    ->chunk(100, function ($lote) use ($id_not, $plantilla) {
                        $queueName = "{$plantilla}_{$id_not}";
                        dispatch(new EnviarMensajesSinCarnet($id_not, $plantilla, $lote))
                            ->onQueue($queueName);
                    });
                break;
        }
    }
})->everyMinute();

//Tarea ejecutar colas de trabajo
Schedule::call(function () {
    $output = new OutputBuffer();

    $fechaHoy = Carbon::now('America/La_Paz')->format('Y-m-d');
    $horaHoy = Carbon::now('America/La_Paz')->format('H:i');

    $notificaciones = Notificaciones::where('fecha_not', $fechaHoy)
        ->where('hora_not', '<=', $horaHoy)
        ->whereIn('estado_not', ['pendiente', 'en_proceso'])
        ->select('id_not', 'plantilla', 'estado_not')
        ->get();

    // Recorremos las notificaciones para hacer las comprobaciones
    foreach ($notificaciones as $notificacion) {
        if ($notificacion->estado_not === 'pendiente') {
            $notificacion->update(['estado_not' => 'en_proceso']);
        }
        // Comprobamos la plantilla para ejecutar la cola correspondiente
        switch ($notificacion->plantilla) {
            case 'bienvenida':
                $queueName = "bienvenida_{$notificacion->id_not}";
                // Ejecutar el job para la plantilla 'bienvenido'
                Log::info("Ejecutando job de bienvenida para la notificación: {$notificacion->id_not}");
                Artisan::call('queue:work', [
                    '--queue' => $queueName,
                    '--max-jobs' => 50,
                    '--stop-when-empty' => true
                ], $output);
                break;
            case 'juegosdeportivos':
                $queueName = "juegosdeportivos_{$notificacion->id_not}";
                // Ejecutar el job para la plantilla 'juegosdeportivos'
                Log::info("Ejecutando job de juegos deportivos para la notificación: {$notificacion->id_not}");
                Artisan::call('queue:work', [
                    '--queue' => $queueName,
                    '--max-jobs' => 50,
                    '--stop-when-empty' => true
                ], $output);
                break;
            case 'diadiscapacidad':
                $queueName = "diadiscapacidad_{$notificacion->id_not}";
                // Ejecutar el job para la plantilla 'diadiscapacidad'
                Log::info("Ejecutando job de día discapacidad para la notificación: {$notificacion->id_not}");
                Artisan::call('queue:work', [
                    '--queue' => $queueName,
                    '--max-jobs' => 50,
                    '--stop-when-empty' => true
                ], $output);
                break;
            case 'sincarnet':
                $queueName = "sincarnet_{$notificacion->id_not}";
                // Ejecutar el job para la plantilla 'sincarnet'
                Log::info("Ejecutando job de sin carnet para la notificación: {$notificacion->id_not}");
                Artisan::call('queue:work', [
                    '--queue' => $queueName,
                    '--max-jobs' => 50,
                    '--stop-when-empty' => true
                ], $output);
                break;
            default:
                // En caso de que no se reconozca la plantilla
                Log::info("Plantilla no reconocida para la notificación: {$notificacion->id_not}");
        }
    }
})->everyMinute(); */
