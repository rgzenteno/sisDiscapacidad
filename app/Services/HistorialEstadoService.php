<?php

namespace App\Services;

use App\Exceptions\HistorialEstadoException;
use App\Models\Habilitado;
use App\Models\HistorialEstados;
use App\Models\Mes;
use App\Models\Pago;
use App\Models\Persona;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Única puerta de escritura para historial_estados. fecha_fin nunca se
 * recibe como input: siempre se deriva de la fecha_inicio del vecino, para
 * que la línea de tiempo de una persona nunca tenga huecos ni solapes.
 */
class HistorialEstadoService
{
    private const ESTADOS_QUE_HABILITAN = ['activo', 'pagos_suspendidos'];
    private const ESTADOS_QUE_DESHABILITAN = ['baja_temporal', 'baja_definitiva', 'depurado'];
    private const ESTADOS_QUE_PERMITEN_DEPURAR = ['baja_temporal', 'baja_definitiva'];

    /**
     * Cache del "último mes" (ver ultimoMesVigente()) durante la vida de esta
     * instancia — normalmente la duración de un solo request, porque el
     * controller la recibe inyectada una sola vez. Evita releer toda la
     * tabla `mes` en cada llamada a esMesVigente(), que en listados que
     * llaman esto por cada fila de historial de cada persona puede ejecutarse
     * cientos de veces en una sola carga de página.
     */
    private ?Mes $ultimoMesVigenteCache = null;
    private bool $ultimoMesVigenteCacheCargado = false;

    public function __construct(private LogService $logService, private PresupuestoMesService $presupuestoMesService)
    {
    }

    /**
     * Agrega un nuevo estado al final de la línea de tiempo de la persona,
     * cerrando el estado abierto actual (si existe).
     */
    public function agregar(string $idPersona, string $estado, Carbon $fechaInicio, ?string $motivo, string $usuario, ?string $observaciones = null): HistorialEstados
    {
        if ($this->tienePagoValido($idPersona, $fechaInicio->year, $fechaInicio->month)) {
            throw new HistorialEstadoException('Ese mes ya tiene un pago registrado; no se puede cambiar el estado.');
        }

        return DB::transaction(function () use ($idPersona, $estado, $fechaInicio, $motivo, $usuario, $observaciones) {
            $estadoActual = HistorialEstados::where('id_persona', $idPersona)
                ->whereNull('fecha_fin')
                ->first();

            $this->assertDepuradoValido($estado, $estadoActual?->estado);

            if ($estadoActual) {
                $inicioActual = Carbon::parse($estadoActual->fecha_inicio);

                if (!$fechaInicio->greaterThan($inicioActual)) {
                    throw new HistorialEstadoException(
                        'La fecha de inicio debe ser posterior al mes en que empezó el estado actual (' . $inicioActual->translatedFormat('F Y') . ').'
                    );
                }

                $estadoActual->fecha_fin = $fechaInicio->copy()->subDay()->toDateString();
                $estadoActual->save();
            }

            // Si el mes elegido tenía el pago anulado pero más adelante ya hay
            // un mes con pago vigente registrado (no necesariamente el mes
            // inmediato siguiente: puede haber meses sin pago en el medio),
            // el nuevo estado no puede quedar abierto indefinidamente
            // (taparía ese pago ya hecho): se cierra justo antes del primer
            // mes ya pagado y se encadena un 'activo' automático desde ahí,
            // igual que si el usuario lo hubiera agregado a mano. Los meses
            // sin pago que quedan en el medio sí se deshabilitan (son los
            // únicos realmente afectados por la baja).
            $primerMesPagado = in_array($estado, self::ESTADOS_QUE_DESHABILITAN, true)
                ? $this->primerMesConPagoValido($idPersona, $fechaInicio->copy()->addMonthNoOverflow()->startOfMonth(), null)
                : null;
            $requiereReconciliacion = $primerMesPagado !== null;
            $finNuevo = $requiereReconciliacion ? $primerMesPagado->copy()->subMonthNoOverflow()->endOfMonth() : null;

            $nuevo = HistorialEstados::create([
                'id_persona' => $idPersona,
                'estado' => $estado,
                // El usuario puede editar el texto fijo (ej. agregarle la
                // fecha de fallecimiento) — si no manda nada, se usa el
                // texto por defecto según el estado.
                'observaciones' => $observaciones ? mb_strtoupper($observaciones) : $this->observacionFija($estado),
                'fecha_inicio' => $fechaInicio->toDateString(),
                'fecha_fin' => $finNuevo?->toDateString(),
                'usuario_modificacion' => $usuario,
                'motivo' => $motivo,
            ]);

            $ajustesPresupuesto = $this->aplicarCascadaHabilitado($idPersona, $fechaInicio, $finNuevo, $estado);

            $this->logService->logUpdate(
                'Beneficiario',
                Persona::find($idPersona),
                [
                    'campos_modificados' => ['estado' => $this->formatearEstado($estado)],
                    'valores_anteriores' => ['estado' => $estadoActual ? $this->formatearEstado($estadoActual->estado) : null],
                    'valores_nuevos' => [
                        'estado' => $this->formatearEstado($estado),
                        'fecha_inicio' => $fechaInicio->toDateString(),
                        'motivo' => $nuevo->motivo,
                        'registrado_por' => $usuario,
                    ],
                ],
                "Se cambió al estado '{$this->formatearEstado($estado)}' del beneficiario.",
                $ajustesPresupuesto
            );

            if ($requiereReconciliacion) {
                $activo = HistorialEstados::create([
                    'id_persona' => $idPersona,
                    'estado' => 'activo',
                    'observaciones' => $this->observacionFija('activo'),
                    'fecha_inicio' => $primerMesPagado->toDateString(),
                    'fecha_fin' => null,
                    'usuario_modificacion' => $usuario,
                    'motivo' => 'Generado automáticamente: ya existía un pago vigente registrado a partir de "' . $primerMesPagado->translatedFormat('F Y') . '".',
                ]);

                $ajustesPresupuestoReconciliacion = $this->aplicarCascadaHabilitado($idPersona, $primerMesPagado, null, 'activo');

                $this->logService->logCreation(
                    'HistorialEstados',
                    $activo,
                    "Se generó automáticamente el estado 'Activo' desde {$primerMesPagado->translatedFormat('F Y')} porque ya existía un pago vigente registrado en ese mes, registrado por {$usuario}.",
                    null,
                    [
                        'estado' => 'Activo',
                        'desde' => $primerMesPagado->toDateString(),
                    ],
                    $ajustesPresupuestoReconciliacion
                );
            }

            return $nuevo;
        });
    }

    /**
     * Cambia únicamente el tipo de estado de un registro, sin tocar fechas.
     * Es la operación más segura: al no mover límites, no puede romper la
     * continuidad de la línea de tiempo. Válida en cualquier posición.
     */
    public function editarSoloEstado(HistorialEstados $historial, string $nuevoEstado, ?string $motivo, string $usuario, bool $esSuperusuario = false, ?string $observaciones = null): HistorialEstados
    {
        $desde = Carbon::parse($historial->fecha_inicio);
        $hasta = $historial->fecha_fin ? Carbon::parse($historial->fecha_fin) : null;

        $this->assertPuedeGestionar($desde, $esSuperusuario);
        $this->assertSinPagosValidos($historial->id_persona, $desde, $hasta);
        $this->assertDepuradoValido($nuevoEstado, $this->vecinoAnterior($historial)?->estado);

        return DB::transaction(function () use ($historial, $nuevoEstado, $motivo, $usuario, $desde, $hasta, $observaciones) {
            $estadoAnterior = $historial->estado;
            $observacionesAnteriores = $historial->observaciones;
            $cambioDeEstado = $nuevoEstado !== $estadoAnterior;

            $historial->estado = $nuevoEstado;
            $historial->observaciones = $observaciones
                ? mb_strtoupper($observaciones)
                : $this->observacionFija($nuevoEstado);
            $historial->motivo = $motivo;
            $historial->save();

            $ajustesPresupuesto = $this->aplicarCascadaHabilitado($historial->id_persona, $desde, $hasta, $nuevoEstado);

            // El mensaje de auditoría refleja qué cambió realmente: este
            // método también se usa ahora para editar solo observaciones/
            // motivo sin tocar el estado (ver PersonaController::updateEstado).
            $this->logService->logUpdate(
                'HistorialEstado',
                $historial,
                $cambioDeEstado
                ? [
                    'campos_modificados' => ['estado' => $this->formatearEstado($nuevoEstado)],
                    'valores_anteriores' => ['estado' => $this->formatearEstado($estadoAnterior)],
                    'valores_nuevos' => ['estado' => $this->formatearEstado($nuevoEstado)],
                ]
                : [
                    'campos_modificados' => ['observaciones' => $historial->observaciones],
                    'valores_anteriores' => ['observaciones' => $observacionesAnteriores],
                    'valores_nuevos' => ['observaciones' => $historial->observaciones],
                ],
                $cambioDeEstado
                ? "Se cambió el tipo de estado (sin mover fechas) del beneficiario, registrado por {$usuario}."
                : "Se editaron las observaciones/motivo del estado del beneficiario, registrado por {$usuario}.",
                $ajustesPresupuesto
            );

            if ($cambioDeEstado) {
                // Si el estado que sigue quedó con el mismo tipo, se absorbe
                // (y así en cadena) — mismo criterio que al eliminar.
                $this->consolidarConSiguientesIguales($historial);
            }

            return $historial;
        });
    }

    /**
     * Edita únicamente el texto de observaciones de un estado, sin tocar
     * estado, fechas ni motivo. A diferencia de editarSoloEstado, no exige
     * mes vigente ni superusuario (assertPuedeGestionar): no mueve
     * presupuesto ni afecta la cascada de habilitado, así que es segura
     * incluso sobre un estado antiguo ya cerrado/reportado — es la única
     * edición permitida sobre un estado histórico para un no-superusuario.
     */
    public function editarObservacion(HistorialEstados $historial, string $observaciones, string $usuario): HistorialEstados
    {
        return DB::transaction(function () use ($historial, $observaciones, $usuario) {
            $observacionesAnteriores = $historial->observaciones;
            $observacionesNuevas = mb_strtoupper($observaciones);

            if ($observacionesNuevas === $observacionesAnteriores) {
                return $historial;
            }

            $historial->observaciones = $observacionesNuevas;
            $historial->save();

            $this->logService->logUpdate(
                'HistorialEstado',
                $historial,
                [
                    'campos_modificados' => ['observaciones' => $observacionesNuevas],
                    'valores_anteriores' => ['observaciones' => $observacionesAnteriores],
                    'valores_nuevos' => ['observaciones' => $observacionesNuevas],
                ],
                "Se editó la observación de un estado histórico del beneficiario, registrado por {$usuario}."
            );

            return $historial;
        });
    }

    /**
     * Mueve la fecha_inicio de un estado, ajustando únicamente el fecha_fin
     * del vecino inmediatamente anterior. Nunca toca nada más allá de ese
     * vecino.
     */
    public function moverLimite(HistorialEstados $historial, Carbon $nuevaFechaInicio, string $usuario, bool $esSuperusuario = false): HistorialEstados
    {
        $idPersona = $historial->id_persona;

        $this->assertPuedeGestionar(Carbon::parse($historial->fecha_inicio), $esSuperusuario);

        $anterior = $this->vecinoAnterior($historial);
        $siguiente = $this->vecinoSiguiente($historial);

        $limiteInferior = $anterior ? Carbon::parse($anterior->fecha_inicio) : null;
        $limiteSuperior = $siguiente ? Carbon::parse($siguiente->fecha_inicio) : null;

        if ($limiteInferior && !$nuevaFechaInicio->greaterThan($limiteInferior)) {
            throw new HistorialEstadoException('La fecha debe ser posterior al inicio del estado anterior (' . $limiteInferior->translatedFormat('F Y') . ').');
        }

        if ($limiteSuperior && !$nuevaFechaInicio->lessThan($limiteSuperior)) {
            throw new HistorialEstadoException('La fecha debe ser anterior al inicio del siguiente estado (' . $limiteSuperior->translatedFormat('F Y') . ').');
        }

        $inicioOriginal = Carbon::parse($historial->fecha_inicio);

        if ($nuevaFechaInicio->equalTo($inicioOriginal)) {
            return $historial;
        }

        if (!$anterior && $nuevaFechaInicio->greaterThan($inicioOriginal)) {
            throw new HistorialEstadoException('No se puede dejar un hueco antes del primer estado de la persona.');
        }

        // El rango que cambia de dueño entre el vecino anterior y este
        // estado es el único que necesita recalcular Habilitado; el resto
        // de la línea de tiempo no se mueve.
        if ($nuevaFechaInicio->greaterThan($inicioOriginal)) {
            $rangoDesde = $inicioOriginal;
            $rangoHasta = $nuevaFechaInicio->copy()->subDay();
            $estadoQueAplica = $anterior->estado;
        } else {
            $rangoDesde = $nuevaFechaInicio;
            $rangoHasta = $inicioOriginal->copy()->subDay();
            $estadoQueAplica = $historial->estado;
        }

        $this->assertSinPagosValidos($idPersona, $rangoDesde, $rangoHasta);

        return DB::transaction(function () use ($historial, $anterior, $nuevaFechaInicio, $usuario, $rangoDesde, $rangoHasta, $estadoQueAplica) {
            $fechaInicioAnterior = $historial->fecha_inicio;

            if ($anterior) {
                $anterior->fecha_fin = $nuevaFechaInicio->copy()->subDay()->toDateString();
                $anterior->save();
            } else {
                // Solo el primer estado de la persona (sin vecino anterior)
                // está ligado a Persona.fecha_registro — mover cualquier
                // otro estado nunca debe tocarla.
                Persona::where('id_persona', $historial->id_persona)
                    ->update(['fecha_registro' => $nuevaFechaInicio->format('Y-m-d')]);
            }

            $historial->fecha_inicio = $nuevaFechaInicio->toDateString();
            $historial->save();

            $ajustesPresupuesto = $this->aplicarCascadaHabilitado($historial->id_persona, $rangoDesde, $rangoHasta, $estadoQueAplica);

            $this->logService->logUpdate(
                'HistorialEstado',
                $historial,
                [
                    'campos_modificados' => ['fecha inicio' => $nuevaFechaInicio->toDateString()],
                    'valores_anteriores' => ['fecha inicio' => $fechaInicioAnterior],
                    'valores_nuevos' => ['fecha inicio' => $nuevaFechaInicio->toDateString()],
                ],
                "Se movió la fecha de inicio del estado del beneficiario, registrado por {$usuario}.",
                $ajustesPresupuesto
            );

            return $historial;
        });
    }

    /**
     * Elimina un estado (último o intermedio), fusionándolo con su vecino
     * anterior. No se puede eliminar el primer estado de una persona: toda
     * persona debe tener siempre un estado inicial.
     */
    public function eliminar(HistorialEstados $historial, bool $esSuperusuario = false): void
    {
        $anterior = $this->vecinoAnterior($historial);

        if (!$anterior) {
            throw new HistorialEstadoException('No se puede eliminar el primer estado registrado de la persona.', 'estado');
        }

        $desde = Carbon::parse($historial->fecha_inicio);
        $hasta = $historial->fecha_fin ? Carbon::parse($historial->fecha_fin) : null;

        $this->assertPuedeGestionar($desde, $esSuperusuario);
        $this->assertSinPagosValidos($historial->id_persona, $desde, $hasta);

        DB::transaction(function () use ($historial, $anterior, $desde, $hasta) {
            $idPersona = $historial->id_persona;

            // Si el anterior ya estaba sin cerrar (fecha_fin null) es una
            // inconsistencia heredada de datos antiguos (ej. estados
            // "pagos_suspendidos" legados con fechas mal cargadas): no se
            // debe pisar ese null con la fecha_fin -posiblemente también
            // rota- del registro que se borra. Se deja tal cual.
            if ($anterior->fecha_fin !== null) {
                $anterior->fecha_fin = $historial->fecha_fin;
                $anterior->save();
            }

            $historial->delete();

            $ajustesPresupuesto = $this->aplicarCascadaHabilitado($idPersona, $desde, $hasta, $anterior->estado);

            $this->logService->logDeletion(
                'HistorialEstados',
                $historial,
                "Se eliminó el estado '{$this->formatearEstado($historial->estado)}' del beneficiario.",
                [
                    'estado' => $this->formatearEstado($historial->estado),
                    'fecha inicio' => $desde->toDateString(),
                    'fecha fin' => $hasta?->toDateString(),
                    'observaciones' => $historial->observaciones,
                ],
                $ajustesPresupuesto
            );

            // Si el estado que queda inmediatamente después del fusionado es
            // del mismo tipo, no tiene sentido dejarlos como dos cards
            // separados: se absorbe también (y así sucesivamente) hasta
            // encontrar un estado distinto o quedarse sin más registros.
            $this->consolidarConSiguientesIguales($anterior);
        });
    }

    /**
     * Inserta un estado nuevo dentro de un segmento existente, a partir del
     * mes elegido. El nuevo estado siempre se extiende hasta justo antes del
     * siguiente estado ya existente (no hace falta indicar un mes final: se
     * calcula solo, igual que al agregar un estado normal). Parte el
     * registro base en hasta 2 filas (antes / nuevo).
     */
    public function insertarIntermedio(HistorialEstados $base, string $nuevoEstado, Carbon $mesInicio, ?string $motivo, string $usuario, bool $esSuperusuario = false, ?string $observaciones = null): array
    {
        $mesInicio = $mesInicio->copy()->startOfMonth();
        $inicioBase = Carbon::parse($base->fecha_inicio)->startOfMonth();

        if (!$base->fecha_fin) {
            throw new HistorialEstadoException('Este estado no tiene un estado posterior: use "Cambiar estado" para agregar uno nuevo al final.');
        }

        $finBase = Carbon::parse($base->fecha_fin);

        // Insertar un estado intermedio siempre altera un mes ya reportado
        // (por definición cae entre dos estados que ya existen) — a
        // diferencia de editar/eliminar, acá no hay excepción de "mes extra":
        // es una acción exclusiva de superusuario.
        if (!$esSuperusuario) {
            throw new HistorialEstadoException('Insertar un estado intermedio es una acción exclusiva de superusuario.', 'estado');
        }

        if ($mesInicio->lessThan($inicioBase) || $mesInicio->greaterThan($finBase->copy()->startOfMonth())) {
            throw new HistorialEstadoException('El mes debe estar dentro del estado seleccionado (entre ' . $inicioBase->translatedFormat('F Y') . ' y ' . $finBase->translatedFormat('F Y') . ').');
        }

        // Si más adelante dentro del rango ya hay un mes con pago vigente
        // registrado (no necesariamente el mes inmediato siguiente: puede
        // haber meses sin pago en el medio, típicamente porque el pago del
        // propio mes elegido fue anulado), el nuevo estado no puede ocupar
        // todo el rango hasta $finBase: se acorta justo antes del primer mes
        // ya pagado y el resto del rango se reconcilia como 'activo'
        // automático — en vez de bloquear el insert por completo.
        $primerMesPagado = in_array($nuevoEstado, self::ESTADOS_QUE_DESHABILITAN, true)
            ? $this->primerMesConPagoValido($base->id_persona, $mesInicio->copy()->addMonthNoOverflow()->startOfMonth(), $finBase)
            : null;
        $requiereReconciliacion = $primerMesPagado !== null;

        if ($requiereReconciliacion) {
            if ($this->tienePagoValido($base->id_persona, $mesInicio->year, $mesInicio->month)) {
                throw new HistorialEstadoException('Ese mes ya tiene un pago registrado; no se puede cambiar el estado.');
            }
        } else {
            $this->assertSinPagosValidos($base->id_persona, $mesInicio, $finBase);
        }

        // El estado que va a quedar inmediatamente antes del nuevo: si queda
        // un tramo "antes" del propio $base, es el mismo estado de $base; si
        // el nuevo reemplaza a $base por completo, es el vecino anterior a
        // $base en la línea de tiempo.
        $hayAntes = $mesInicio->greaterThan($inicioBase);
        $estadoPrevioAlNuevo = $hayAntes ? $base->estado : $this->vecinoAnterior($base)?->estado;
        $this->assertDepuradoValido($nuevoEstado, $estadoPrevioAlNuevo);

        return DB::transaction(function () use ($base, $nuevoEstado, $mesInicio, $motivo, $usuario, $inicioBase, $finBase, $observaciones, $primerMesPagado, $requiereReconciliacion) {
            $idPersona = $base->id_persona;
            $hayAntes = $mesInicio->greaterThan($inicioBase);

            $resultado = [];

            if ($hayAntes) {
                $base->fecha_fin = $mesInicio->copy()->subDay()->toDateString();
                $base->save();
                $resultado['antes'] = $base;
            }

            $finNuevo = $requiereReconciliacion ? $primerMesPagado->copy()->subMonthNoOverflow()->endOfMonth() : $finBase;

            $nuevo = HistorialEstados::create([
                'id_persona' => $idPersona,
                'estado' => $nuevoEstado,
                'observaciones' => $observaciones ? mb_strtoupper($observaciones) : $this->observacionFija($nuevoEstado),
                'fecha_inicio' => $mesInicio->toDateString(),
                'fecha_fin' => $finNuevo->toDateString(),
                'usuario_modificacion' => $usuario,
                'motivo' => $motivo,
            ]);
            $resultado['nuevo'] = $nuevo;

            if (!$hayAntes) {
                // El segmento "nuevo" reemplaza por completo al base original:
                // ya no queda rango "antes".
                $base->delete();
            }

            $ajustesPresupuesto = $this->aplicarCascadaHabilitado($idPersona, $mesInicio, $finNuevo, $nuevoEstado);

            $this->logService->logCreation(
                'HistorialEstados',
                $nuevo,
                "Se insertó el estado '{$this->formatearEstado($nuevoEstado)}' en medio de la línea de tiempo del beneficiario, registrado por {$usuario}.",
                null,
                [
                    'estado' => $this->formatearEstado($nuevoEstado),
                    'desde' => $mesInicio->toDateString(),
                    'hasta' => $finNuevo->toDateString(),
                ],
                $ajustesPresupuesto
            );

            if ($requiereReconciliacion) {
                $activo = HistorialEstados::create([
                    'id_persona' => $idPersona,
                    'estado' => 'activo',
                    'observaciones' => $this->observacionFija('activo'),
                    'fecha_inicio' => $primerMesPagado->toDateString(),
                    'fecha_fin' => $finBase->toDateString(),
                    'usuario_modificacion' => $usuario,
                    'motivo' => 'Generado automáticamente: ya existía un pago vigente registrado a partir de "' . $primerMesPagado->translatedFormat('F Y') . '".',
                ]);
                $resultado['activo_automatico'] = $activo;

                $ajustesPresupuestoReconciliacion = $this->aplicarCascadaHabilitado($idPersona, $primerMesPagado, $finBase, 'activo');

                $this->logService->logCreation(
                    'HistorialEstados',
                    $activo,
                    "Se generó automáticamente el estado 'Activo' desde {$primerMesPagado->translatedFormat('F Y')} porque ya existía un pago vigente registrado en ese mes, registrado por {$usuario}.",
                    null,
                    [
                        'estado' => 'Activo',
                        'desde' => $primerMesPagado->toDateString(),
                        'hasta' => $finBase->toDateString(),
                    ],
                    $ajustesPresupuestoReconciliacion
                );

                // El 'activo' automático es el que ahora linda con lo que
                // seguía después de $finBase: si coincide en tipo, se
                // absorbe igual que en el flujo normal.
                $this->consolidarConSiguientesIguales($activo);
            } else {
                // Si el estado que sigue (el que antes venía después del
                // base) quedó con el mismo tipo que el recién insertado, se
                // absorbe — mismo criterio que al eliminar/editar: el
                // anterior siempre absorbe al que sigue, nunca al revés.
                $this->consolidarConSiguientesIguales($nuevo);
            }

            return $resultado;
        });
    }

    // ─── Autorización por mes vigente ────────────────────────────────────

    /**
     * El "mes vigente" es el último (gestión, mes) registrado en el sistema.
     * Un no-superusuario solo puede gestionar el estado cuyo fecha_inicio
     * cae exactamente en ese mes; cualquier mes anterior queda reservado a
     * superusuario, para que los reportes ya cerrados no cambien por error.
     * El "mes extra" es el siguiente al último mes registrado (aún no tiene
     * fila en `mes`, todavía no se cargó su PDF). Es el único mes que un
     * no-superusuario puede gestionar — así puede marcar de antemano una
     * baja antes de que llegue el PDF de ese mes, sin darle acceso a meses
     * ya cerrados/reportados.
     */
    public function esMesVigente(Carbon $fecha): bool
    {
        $ultimoMes = $this->ultimoMesVigente();

        if (!$ultimoMes) {
            return true;
        }

        $mesExtra = Carbon::create((int) $ultimoMes->gestion->gestion, $this->numeroMesReal($ultimoMes), 1)
    ->addMonthNoOverflow();

        return $fecha->year === $mesExtra->year && $fecha->month === $mesExtra->month;
    }

    private function ultimoMesVigente(): ?Mes
    {
        if (!$this->ultimoMesVigenteCacheCargado) {
            $this->ultimoMesVigenteCache = Mes::where('es_retroactivo', false)->with('gestion')->get()
                ->sortBy(fn($m) => ((int) $m->gestion->gestion) * 100 + $this->numeroMesReal($m))
                ->last();
            $this->ultimoMesVigenteCacheCargado = true;
        }

        return $this->ultimoMesVigenteCache;
    }

    /**
     * "Depurado" solo tiene sentido como consecuencia de una baja real
     * (temporal o definitiva) que quedó sin PDF que la reporte — nunca se
     * puede depurar a alguien que estaba activo (o cualquier otro estado)
     * justo antes. El frontend ya filtra esta opción en el formulario, pero
     * el backend es la última garantía: si por una inconsistencia de datos
     * (ej. fechas mal encadenadas) el frontend llegara a ofrecer la opción
     * igual, esta validación es la que realmente lo bloquea.
     */
    private function assertDepuradoValido(string $nuevoEstado, ?string $estadoAnterior): void
    {
        if ($nuevoEstado !== 'depurado') {
            return;
        }

        if (!in_array($estadoAnterior, self::ESTADOS_QUE_PERMITEN_DEPURAR, true)) {
            throw new HistorialEstadoException(
                'No se puede depurar: el estado anterior debe ser baja temporal o baja definitiva.',
                'estado'
            );
        }
    }

    private function assertPuedeGestionar(Carbon $fechaAfectada, bool $esSuperusuario): void
    {
        if ($esSuperusuario) {
            return;
        }

        if (!$this->esMesVigente($fechaAfectada)) {
            throw new HistorialEstadoException(
                'Solo un superusuario puede modificar este estado; un usuario normal solo puede gestionar el mes siguiente al último registrado.',
                'estado'
            );
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    private function vecinoAnterior(HistorialEstados $historial): ?HistorialEstados
    {
        return HistorialEstados::where('id_persona', $historial->id_persona)
            ->where('fecha_inicio', '<', $historial->fecha_inicio)
            ->orderBy('fecha_inicio', 'desc')
            ->first();
    }

    private function vecinoSiguiente(HistorialEstados $historial): ?HistorialEstados
    {
        return HistorialEstados::where('id_persona', $historial->id_persona)
            ->where('fecha_inicio', '>', $historial->fecha_inicio)
            ->orderBy('fecha_inicio', 'asc')
            ->first();
    }

    /**
     * Absorbe en cadena los registros siguientes que tengan exactamente el
     * mismo estado que $anterior, extendiendo su fecha_fin cada vez. Se
     * detiene al primer estado distinto, o deja fecha_fin en null si no
     * queda ningún registro después (pasa a ser el estado vigente).
     *
     * El motivo/observaciones del registro absorbido no se mezcla con el
     * que sobrevive (mezclar textos de decisiones distintas en fusiones
     * sucesivas termina siendo ilegible) — en cambio, se deja completo en
     * la bitácora de auditoría, así queda recuperable aunque ya no
     * aparezca en la línea de tiempo viva.
     */
    private function consolidarConSiguientesIguales(HistorialEstados $anterior): void
    {
        while (true) {
            $siguiente = $this->vecinoSiguiente($anterior);

            if (!$siguiente || $siguiente->estado !== $anterior->estado) {
                break;
            }

            $finAbsorbido = $siguiente->fecha_fin;

            $this->logService->logDeletion(
                'HistorialEstados',
                $siguiente,
                "Se fusionó automáticamente con el estado anterior (mismo estado '{$this->formatearEstado($siguiente->estado)}') al eliminar un estado intermedio.",
                [
                    'estado' => $this->formatearEstado($siguiente->estado),
                    'fecha inicio' => Carbon::parse($siguiente->fecha_inicio)->toDateString(),
                    'fecha fin' => $finAbsorbido ? Carbon::parse($finAbsorbido)->toDateString() : null,
                    'motivo' => $siguiente->motivo,
                    'observaciones' => $siguiente->observaciones,
                ]
            );

            $siguiente->delete();

            $anterior->fecha_fin = $finAbsorbido;
            $anterior->save();
        }
    }

    private function tienePagoValido(string $idPersona, int $anio, int $mes): bool
    {
        return Pago::where('pago', 1)
            ->whereHas('habilitado', function ($q) use ($idPersona, $mes, $anio) {
                $q->where('id_persona', $idPersona)
                    ->whereHas('mes', fn($q) => $q->where('mes', $mes))
                    ->whereHas('gestion', fn($q) => $q->where('gestion', $anio));
            })->exists();
    }

    /**
     * Busca, entre los `Habilitado` ya registrados dentro de [$desde, $hasta],
     * el primer mes (cronológicamente) que ya tenga un pago vigente. No
     * asume que sea el mes inmediato siguiente a $desde: puede haber meses
     * sin pago en el medio. Se usa para decidir dónde cortar un estado
     * deshabilitante recién creado y encadenar un 'activo' automático desde
     * ahí, sin importar cuántos meses de diferencia haya.
     */
    private function primerMesConPagoValido(string $idPersona, Carbon $desde, ?Carbon $hasta): ?Carbon
    {
        $primero = $this->habilitadosEnRango($idPersona, $desde, $hasta)
    ->filter(fn($hab) => $hab->pago && (int) $hab->pago->pago === 1)
    ->sortBy(fn($hab) => ((int) $hab->gestion->gestion) * 100 + $this->numeroMesReal($hab->mes))
    ->first();

if (!$primero) {
    return null;
}

return Carbon::create((int) $primero->gestion->gestion, $this->numeroMesReal($primero->mes), 1);
    }

    private function assertSinPagosValidos(string $idPersona, Carbon $desde, ?Carbon $hasta): void
    {
        foreach ($this->habilitadosEnRango($idPersona, $desde, $hasta) as $hab) {
            if ($hab->pago && (int) $hab->pago->pago === 1) {
                throw new HistorialEstadoException(
    'Ya existe un pago registrado en ' . Carbon::create((int) $hab->gestion->gestion, $this->numeroMesReal($hab->mes), 1)->translatedFormat('F Y') . '; no se puede modificar ese rango.'
);
            }
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, Habilitado>
     */
    private function habilitadosEnRango(string $idPersona, Carbon $desde, ?Carbon $hasta)
    {
        $desde = $desde->copy()->startOfMonth();
        $hasta = $hasta?->copy()->startOfMonth();

        return Habilitado::where('id_persona', $idPersona)
            ->with(['mes', 'gestion', 'pago'])
            ->get()
            ->filter(function ($hab) use ($desde, $hasta) {
                if (!$hab->mes || !$hab->gestion || $hab->mes->es_retroactivo) {
                    return false;
                }

                $inicioMes = Carbon::create((int) $hab->gestion->gestion, $this->numeroMesReal($hab->mes), 1);

                if ($inicioMes->lessThan($desde)) {
                    return false;
                }

                return !$hasta || $inicioMes->lessThanOrEqualTo($hasta);
            });
    }

    /**
     * @return array<int, array{mes: string, anterior: int, nuevo: int}> Un
     *   ítem por cada mes cuyo presupuesto se ajustó (una baja/reactivación
     *   puede alcanzar varios meses de una sola vez) — se usa para mostrar
     *   la lista en la Bitácora (ver LogService::logUpdate/logCreation).
     */
    private function aplicarCascadaHabilitado(string $idPersona, Carbon $desde, ?Carbon $hasta, string $estado): array
    {
        $esBaja = in_array($estado, self::ESTADOS_QUE_DESHABILITAN, true);
        $esHabilitante = in_array($estado, self::ESTADOS_QUE_HABILITAN, true);
        $ajustesPresupuesto = [];

        foreach ($this->habilitadosEnRango($idPersona, $desde, $hasta) as $hab) {
            // La caja de esa gestión ya está cerrada: el estado se registra
            // igual (verdad histórica), pero no se toca el `habilitado` de
            // una gestión que ya quedó cuadrada.
            if ($hab->gestion && $hab->gestion->estaCerrada()) {
                continue;
            }

            $tienePagoValido = $hab->pago && (int) $hab->pago->pago === 1;

            if ($esBaja && $hab->habilitado && !$tienePagoValido) {
                $hab->habilitado = false;
                $hab->observaciones_habilitado = 'Deshabilitación automática';
                $hab->save();
                $ajuste = $this->presupuestoMesService->ajustarPorHabilitado($hab->mes, true, false);
                if ($ajuste) {
                    $ajustesPresupuesto[] = $this->formatearAjustePresupuesto($hab, $ajuste);
                }
                continue;
            }

            if ($esHabilitante && !$hab->habilitado && $hab->observaciones_habilitado === 'Deshabilitación automática') {
                $hab->habilitado = true;
                $hab->observaciones_habilitado = null;
                $hab->save();
                $ajuste = $this->presupuestoMesService->ajustarPorHabilitado($hab->mes, false, true);
                if ($ajuste) {
                    $ajustesPresupuesto[] = $this->formatearAjustePresupuesto($hab, $ajuste);
                }
            }
        }

        return $ajustesPresupuesto;
    }

    /**
     * @param array{anterior: int, nuevo: int} $ajuste
     * @return array{mes: string, anterior: int, nuevo: int}
     */
    private function formatearAjustePresupuesto(Habilitado $hab, array $ajuste): array
    {
        return [
            'mes' => $this->nombreMesConGestion($hab->mes, $hab->gestion),
            'anterior' => $ajuste['anterior'],
            'nuevo' => $ajuste['nuevo'],
        ];
    }

    /**
     * Nombre en español del mes real + gestión — usa `mes_original` cuando
     * es un mes-retro (mismo criterio que HabilitadoController::nombreMesReal).
     */
    private function nombreMesConGestion(?Mes $mes, $gestion): string
    {
        if (!$mes) {
            return '—';
        }

        $meses = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        $numeroMesReal = $mes->es_retroactivo ? $mes->mes_original : $mes->mes;
        $nombreMes = ucwords(strtolower($meses[$numeroMesReal] ?? 'Mes inválido'));

        return $gestion ? "{$nombreMes} {$gestion->gestion}" : $nombreMes;
    }

    /**
     * Número de mes real (1-12) para cálculos con Carbon. Los meses
     * retroactivos guardan 101-112 en `mes` para no chocar con el mes
     * normal ya usado en la misma gestión; el mes real vive en
     * mes_original.
     */
    private function numeroMesReal(Mes $mes): int
    {
        return $mes->es_retroactivo ? (int) $mes->mes_original : (int) $mes->mes;
    }

    /**
     * Texto fijo de `observaciones` según el estado — autogenerado, no lo
     * escribe el usuario (mantiene compatibilidad con los datos históricos,
     * que ya usan esta columna para esto). `motivo` es aparte: texto libre
     * con la razón del cambio que escribe el usuario.
     */
    private function observacionFija(string $estado): ?string
    {
        return match ($estado) {
            'baja_temporal' => 'PADRE FUNCIONARIO TRABAJANDO CON ITEM',
            'baja_definitiva' => 'FALLECIDO',
            default => null,
        };
    }

    private function formatearEstado(string $estado): string
    {
        return ucwords(strtolower(str_replace('_', ' ', $estado)));
    }
}