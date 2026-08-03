<?php

namespace App\Http\Controllers;

use App\Models\Carnet;
use App\Models\Discapacidad;
use App\Models\Distrito;
use App\Models\Gestion;
use App\Models\Habilitado;
use App\Models\HistorialEstados;
use App\Models\Mes;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\Tutor;
use App\Exceptions\HistorialEstadoException;
use App\Exports\ReporteBeneficiarioExport;
use App\Services\HistorialEstadoService;
use App\Services\LogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Inertia\Inertia;

class PersonaController extends Controller
{
    protected $logService;
    protected $historialEstadoService;

    public function __construct(LogService $logService, HistorialEstadoService $historialEstadoService)
    {
        $this->logService = $logService;
        $this->historialEstadoService = $historialEstadoService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $selectedTutorId = session('selected_tutor_id');
        $selectedTutorName = '';

        if ($selectedTutorId) {
            $selectedTutor = Tutor::findOrFail($selectedTutorId);
            $selectedTutorName = "{$selectedTutor->nombre_tutor} {$selectedTutor->apellido_tutor}";
        }

        $search = $request->input('buscador');

        $personas = Persona::conRelacionesCompletas()
            ->with([
                'historialEstados' => fn($q) => $q->orderBy('fecha_inicio', 'desc'),
                'habilitados' => fn($q) => $q->whereHas('pago')->with(['gestion', 'mes', 'pago']),
            ])
            ->beneficiarios()
            ->busquedaGlobal($search)
            ->selectRaw('persona.*,
                UPPER(COALESCE(
                    CONCAT(TRIM(apellido_persona), " ", TRIM(nombre_persona)),
                    nombre_completo
                )) as nombre_orden')
            ->orderByRaw('nombre_orden ASC')
            ->paginate(50)
            ->withQueryString();

        // Se valida por rol/jerarquía, no por el permiso 'superusuario': ese
        // permiso puede estar asignado a otros roles por configuración
        // (ej. 'encargado'), y esta validación tiene que reflejar quién es
        // realmente el superusuario del sistema.
        $esSuperusuario = Auth::user()->hasRole('superUsuario');

        // Conteos de tutorados precalculados en 2 consultas agrupadas para
        // todos los tutores de esta página, en vez de 2 consultas por cada
        // persona-con-tutor (podían ser hasta ~100 queries sueltas por carga
        // de página).
        $tutorIdsPagina = $personas->getCollection()
            ->pluck('tutor.id_tutor')
            ->filter()
            ->unique()
            ->values();

        $totalTutoradosPorTutor = Persona::whereIn('id_tutor', $tutorIdsPagina)
            ->beneficiarios()
            ->selectRaw('id_tutor, COUNT(*) as total')
            ->groupBy('id_tutor')
            ->pluck('total', 'id_tutor');

        $tutoradosActivosPorTutor = Persona::whereIn('id_tutor', $tutorIdsPagina)
            ->beneficiarios()
            ->activos()
            ->selectRaw('id_tutor, COUNT(*) as total')
            ->groupBy('id_tutor')
            ->pluck('total', 'id_tutor');

        $personas->getCollection()->transform(function ($persona) use ($esSuperusuario, $totalTutoradosPorTutor, $tutoradosActivosPorTutor) {
            // Orden cronológico (fecha_inicio), no de inserción: con estados
            // intermedios insertados retroactivamente, el registro más nuevo
            // en la base de datos no es necesariamente el más reciente en el
            // tiempo. Ya viene ordenado así desde el eager load de arriba.
            $persona->historial_completo = $persona->historialEstados
                ->map(function ($h) use ($esSuperusuario) {
                    // El frontend no debe reimplementar la regla de "mes
                    // vigente": el backend manda ya resuelto si este registro
                    // puntual se puede editar/eliminar/usar como base para
                    // insertar un estado intermedio.
                    $h->puede_gestionar = $esSuperusuario || $this->historialEstadoService->esMesVigente(Carbon::parse($h->fecha_inicio));
                    return $h;
                });

            $estadoActual = $persona->ultimoEstado;

            if ($estadoActual) {
                $persona->id_estado = $estadoActual->id;
                $persona->estado_actual = $estadoActual->estado;
                $persona->fecha_inicio = $estadoActual->fecha_inicio;
                $persona->fecha_fin = $estadoActual->fecha_fin;
                $persona->fecha_registro_estado = $estadoActual->fecha_registro;
                $persona->usuario_modificacion = $estadoActual->usuario_modificacion;
                $persona->observaciones = $estadoActual->observaciones;
            }

            $persona->makeHidden(['historialEstados', 'ultimoEstado', 'habilitados']);

            $persona->carnet_vigente = $persona->carnet &&
                $persona->carnet->fecha_vencimiento >= now() ? 1 : 0;

            $persona->meses_pagados = $persona->habilitados
                ->map(function ($h) {
                    $esRetro = (bool) $h->mes->es_retroactivo;
                    $anio = $esRetro && $h->mes->mes_original == 12
                        ? $h->gestion->gestion - 1
                        : $h->gestion->gestion;
                    $mes = $esRetro ? $h->mes->mes_original : $h->mes->mes;

                    return [
                        'gestion' => $anio,
                        'mes' => $mes,
                        'anulado' => (int) $h->pago->pago === 0,
                        'es_retroactivo' => $esRetro,
                    ];
                });

            // Adjuntar conteos del tutor al objeto persona (precalculados
            // arriba en 2 consultas agrupadas, no una consulta por persona).
            if ($persona->tutor) {
                $tutorId = $persona->tutor->id_tutor;

                $persona->tutor->total_tutorados = (int) ($totalTutoradosPorTutor[$tutorId] ?? 0);
                $persona->tutor->tutorados_activos = (int) ($tutoradosActivosPorTutor[$tutorId] ?? 0);
                $persona->tutor->fecha_registro = $persona->tutor->created_at?->format('Y-m-d H:i:s');
            }

            return $persona;
        });

        // Obtener todos los meses reales registrados con su gestión — los
        // meses-retro (es_retroactivo=true, offset +100 en `mes`) quedan
        // fuera: son correcciones sobre periodos ya cerrados, no meses
        // "disponibles" para gestionar el estado de un beneficiario. Mismo
        // filtro que usa HistorialEstadoService::ultimoMesVigente().
        $mesesDisponibles = Mes::with('gestion')
            ->where('es_retroactivo', false)
            ->get()
            ->map(fn($m) => [
                'gestion' => $m->gestion->gestion,
                'mes' => str_pad($m->mes, 2, '0', STR_PAD_LEFT),
            ]);

        // Tutores con conteos precalculados para otros usos (ej: listados, filtros)
        $tutores = Tutor::conConteoTutorados()
            ->orderBy('apellido_tutor')
            ->get()
            ->map(fn($t) => [
                'id_tutor' => $t->id_tutor,
                'nombre_tutor' => $t->nombre_tutor,
                'apellido_tutor' => $t->apellido_tutor,
                'ci_tutor' => $t->ci_tutor,
                'telefono' => $t->telefono,
                'email' => $t->email,
                'direccion' => $t->direccion,
                'total_tutorados' => $t->total_tutorados,
                'tutorados_activos' => $t->tutorados_activos,
                'fecha_registro' => $t->created_at,
            ]);

        return Inertia::render('Persona/index', [
            'persona' => $personas,
            'personas' => $personas,
            'distrito' => Distrito::all(),
            'discapacidad' => Discapacidad::all(),
            'carnet' => Carnet::all(),
            'tutor' => $tutores,
            'selectedTutorName' => $selectedTutorName,
            'mesesDisponibles' => $mesesDisponibles,
            'filters' => [
                'buscador' => $search
            ]
        ]);
    }

    public function reporte(Request $request)
    {
        $year = $request->input('gestion_gestion');
        $mes = $request->input('mes');
        $verRetro = $request->boolean('ver_retro');

        $mesesNumeros = collect([]);
        $resultados = collect([]);
        $resultadosReporte = collect([]);

        $gestion = $year ? Gestion::where('gestion', $year)->first() : null;

        if ($gestion) {
            $mesesNumeros = $gestion
                ->meses()
                ->where('es_retroactivo', false)
                ->orderBy('mes')
                ->pluck('mes');
        }

        // "Ver retro": en vez del mes normal, filtrar por el mes-retro (mes.mes
        // es un código interno como 101-112) cuyo mes_original coincide con el
        // mes elegido. $mes sigue representando el mes calendario real (1-12)
        // elegido en el selector; $mesFiltro es el valor real de `mes.mes` a
        // buscar (retro o normal según corresponda). Mismo patrón que
        // PagoController::bandejaPago().
        $mesFiltro = $mes;
        $mesRetroInfo = null;

        if ($gestion && $mes && $verRetro) {
            $mesRetroInfo = Mes::where('id_gestion', $gestion->id_gestion)
                ->where('es_retroactivo', true)
                ->where('mes_original', $mes)
                ->first();

            // Si todavía no se cargó el retro de este mes, se usa un valor de
            // mes imposible para que la query de abajo devuelva vacío sin
            // lanzar error.
            $mesFiltro = $mesRetroInfo->mes ?? -1;
        }

        $mesModel = ($gestion && $mes) ? $gestion->meses()->where('mes', $mesFiltro)->first() : null;

        if ($gestion && $mes && $mesModel) {

            // Diciembre-retro representa diciembre del año ANTERIOR a la
            // gestión que se cierra (el mes-retro de diciembre no vive en esta
            // gestión) — el resto de los meses usa el año de la gestión
            // seleccionada tal cual.
            $anioRealMes = ($verRetro && $mesRetroInfo && $mes == 12) ? $year - 1 : $year;

            // ── Subquery: último historial por persona ──────────────────────
            $fechaFinMes = Carbon::createFromDate($anioRealMes, $mes, 1, 'America/La_Paz')->endOfMonth()->toDateString();

            $ultimoHistorial = DB::table('historial_estados as he')
                ->select('he.id_persona', 'he.estado', 'he.observaciones')
                ->where('he.fecha_inicio', '<=', $fechaFinMes)
                ->whereNotExists(function ($sub) use ($fechaFinMes) {
                    $sub->select(DB::raw(1))
                        ->from('historial_estados as he2')
                        ->whereColumn('he2.id_persona', 'he.id_persona')
                        ->where('he2.fecha_inicio', '<=', $fechaFinMes)
                        ->where(function ($q) {
                            $q->whereColumn('he2.fecha_inicio', '>', 'he.fecha_inicio')
                                ->orWhere(function ($q2) {
                                    $q2->whereColumn('he2.fecha_inicio', '=', 'he.fecha_inicio')
                                        ->whereColumn('he2.id', '>', 'he.id');
                                });
                        });
                })
                ->where('he.estado', '!=', 'depurado');

            // Para retro, "quién es baja" no se deriva del historial general
            // (eso arrastra bajas de CUALQUIER momento, sin relación con el
            // Excel de retro cargado ese mes) sino de retroactivo_evaluacion,
            // que ahora solo guarda exclusiones para ESTE mes-retro (nunca
            // pagables) — ya sea automática (estado_baja con la baja real) o
            // manual (es_correccion_manual, el estado real sigue diciendo
            // activo pero se decidió no pagar por retro). Se le da la misma
            // forma (id_persona, estado, observaciones) que $ultimoHistorial
            // para poder reusar el resto de la consulta sin duplicarla. Como
            // no todas las personas habilitadas-retro tienen fila acá, el
            // join tiene que ser LEFT, no INNER.
            $estadoSource = $verRetro
                ? DB::table('retroactivo_evaluacion as re')
                    ->select(
                        're.id_persona',
                        DB::raw("COALESCE(re.estado_baja, 'baja_definitiva') as estado"),
                        // Motivo real de la exclusión (ej. "Excluido de retroactivo:
                        // baja_temporal vigente desde 2025-02-10" o "Excluido
                        // manualmente: ..."), no una etiqueta fija por estado.
                        're.motivo as observaciones'
                    )
                    ->where('re.id_gestion', $gestion->id_gestion)
                    ->where('re.mes_original', $mes)
                    ->where(function ($q) {
                        $q->whereNotNull('re.estado_baja')->orWhere('re.es_correccion_manual', true);
                    })
                : $ultimoHistorial;

            $joinMetodo = $verRetro ? 'leftJoinSub' : 'joinSub';

            // ── Base de personas válidas (reutilizada en los conteos) ───────
            $basePersonas = Persona::query()
                ->{$joinMetodo}($estadoSource, 'h', 'h.id_persona', '=', 'persona.id_persona')
                ->where('persona.tipo_registro', '!=', 'registrado')
                ->where(function ($q) {
                    $q->whereNotNull('hab.id_habilitado')
                        ->orWhereIn('h.estado', ['baja_temporal', 'baja_definitiva']);
                })
                ->leftJoin('habilitado as hab', function ($join) use ($gestion, $mesModel) {
                    $join->on('hab.id_persona', '=', 'persona.id_persona')
                        ->where('hab.id_gestion', $gestion->id_gestion)
                        ->where('hab.id_mes', $mesModel->id_mes)
                        ->where('hab.habilitado', 1);
                });

            // ── Totales calculados por separado (reemplazan los OVER) ───────
            $totalPersonas = (clone $basePersonas)->distinct()->count('persona.id_persona');
            $cantidadBajas = (clone $basePersonas)
                ->whereIn('h.estado', ['baja_temporal', 'baja_definitiva'])
                ->distinct()
                ->count('persona.id_persona');
            $cantidadActivos = $totalPersonas - $cantidadBajas;

            $montoTotal = $totalPersonas * $mesModel->monto;
            $montoBajas = $cantidadBajas * $mesModel->monto;
            $montoActivos = $cantidadActivos * $mesModel->monto;

            // ── Consulta principal ──────────────────────────────────────────
            $query = Persona::query()
                ->without(['ultimoEstado', 'historialEstados'])
                ->select([
                    'persona.id_persona',
                    'persona.ci_persona       as ci',
                    'persona.complemento',
                    'persona.apellido_persona as apellido',
                    'persona.nombre_persona   as nombre',
                    'persona.nombre_completo',
                    // En retro, un habilitado-retro pagable no tiene fila en
                    // retroactivo_evaluacion (esa tabla es solo para exclusiones),
                    // así que el LEFT JOIN deja h.estado en null — hay que mostrarlo
                    // como 'activo' para que se vea igual que un habilitado normal
                    // (mismo estilo de fila/negrita en el reporte).
                    $verRetro
                        ? DB::raw("COALESCE(h.estado, 'activo') as estado_periodo")
                        : 'h.estado as estado_periodo',
                    'h.observaciones',
                    DB::raw("{$mes}             as mes"),
                    DB::raw("{$mesModel->monto} as monto"),
                    DB::raw("{$year}            as gestion"),
                    DB::raw("{$totalPersonas}   as total_personas"),
                    DB::raw("{$montoTotal}      as monto_total"),
                    DB::raw("{$cantidadBajas}   as cantidad_bajas"),
                    DB::raw("{$montoBajas}      as monto_bajas"),
                    DB::raw("{$cantidadActivos} as cantidad_activos"),
                    DB::raw("{$montoActivos}    as monto_activos"),
                    DB::raw("UPPER(REGEXP_REPLACE(
                        COALESCE(
                            CONCAT(TRIM(persona.apellido_persona), ' ', TRIM(persona.nombre_persona)),
                            persona.nombre_completo
                        ),
                        ' +', ' '
                    )) as nombre_orden"),
                ])
                ->{$joinMetodo}($estadoSource, 'h', 'h.id_persona', '=', 'persona.id_persona')
                ->where('persona.tipo_registro', '!=', 'registrado')
                ->where(function ($q) {
                    $q->whereNotNull('hab.id_habilitado')
                        ->orWhereIn('h.estado', ['baja_temporal', 'baja_definitiva']);
                })
                ->leftJoin('habilitado as hab', function ($join) use ($gestion, $mesModel) {
                    $join->on('hab.id_persona', '=', 'persona.id_persona')
                        ->where('hab.id_gestion', $gestion->id_gestion)
                        ->where('hab.id_mes', $mesModel->id_mes)
                        ->where('hab.habilitado', 1);
                })
                ->groupBy([
                    'persona.id_persona',
                    'persona.ci_persona',
                    'persona.complemento',
                    'persona.apellido_persona',
                    'persona.nombre_persona',
                    'persona.nombre_completo',
                    'h.estado',
                    'h.observaciones',
                ])
                ->orderByRaw('nombre_orden COLLATE utf8mb4_spanish_ci ASC');

            $resultadosReporte = (clone $query)->get()->makeHidden(['estado_actual', 'ultimo_estado']);

            $resultados = $query->paginate(1000)->appends($request->query());
        }

        $gestiones = Gestion::select('gestion as anio', 'retroactivos_habilitado')
            ->distinct()
            ->orderByDesc('gestion')
            ->get();

        return Inertia::render('Persona/reporteBeneficiario', [
            'resultados' => $resultados,
            'resultadosReporte' => $resultadosReporte,
            'gestiones' => $gestiones,
            'mesesNumeros' => $mesesNumeros,
            'filters' => ['gestion' => $year, 'mes' => $mes, 'ver_retro' => $verRetro],
            'mesRetroDisponible' => (bool) $mesRetroInfo,
        ]);
    }

    /**
     * Igual que reporte(), pero en Excel — mismo modelo que
     * useReporteBeneficiarioPDF.js (columnas, colores de baja y totales).
     */
    public function exportReporteBeneficiarioExcel(Request $request)
    {
        $request->validate([
            'datos' => 'required|array',
            'gestion' => 'required',
            'mes' => 'required',
        ]);

        $esRetro = $request->boolean('es_retro');
        $meses = ['', 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
        $mesNombre = $meses[(int) $request->input('mes')] ?? 'MES';
        $gestion = $request->input('gestion');
        // Mismo patrón de nombre que el PDF de referencia (useReporteBeneficiarioPDF.js).
        $nombreArchivo = "0 PAGO DE BONO MES DE {$mesNombre} {$gestion}" . ($esRetro ? ' RETROACTIVO' : '') . '.xlsx';

        return Excel::download(
            new ReporteBeneficiarioExport(
                $request->input('datos'),
                $gestion,
                $request->input('mes'),
                $esRetro,
            ),
            $nombreArchivo
        );
    }

    public function clearTutorSession(Request $request)
    {
        $request->session()->forget('selected_tutor_id');
        return response()->noContent();
    }

    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:10240'
        ]);

        DB::beginTransaction();

        try {
            $archivo = $request->file('archivo');
            $spreadsheet = IOFactory::load($archivo->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $insertados = 0;
            $actualizados = 0;
            $duplicados = 0;
            $errores = 0;

            // ✅ Obtener usuario autenticado
            $user = Auth::user();
            $nombreCompleto = "{$user->nombre} {$user->apellido}";

            foreach ($rows as $index => $row) {
                // ✅ SALTAR ENCABEZADO
                if ($index === 0 || empty($row[0])) {
                    continue;
                }

                try {
                    // ✅ Extraer datos básicos (5 columnas)
                    $nombre = $this->limpiarTexto($row[0] ?? null);
                    $apellido = $this->limpiarTexto($row[1] ?? null);
                    $distrito = $this->limpiarTexto($row[2] ?? null);
                    $ciCompleto = trim($row[3] ?? '');
                    $observacionRaw = $row[4] ?? null;

                    // ✅ Procesar observación: si es "NINGUNO" o vacío -> NULL
                    $observacion = $this->procesarObservacion($observacionRaw);

                    // ✅ Validar CI obligatorio
                    if (empty($ciCompleto)) {
                        Log::warning("Fila $index: CI vacío, saltando");
                        $errores++;
                        continue;
                    }

                    // ✅ Separar CI y complemento
                    $datosCi = $this->separarCIyComplemento($ciCompleto);
                    $ci = $datosCi['ci'];
                    $complemento = $datosCi['complemento'];

                    // ✅ Validar que CI sea numérico y tenga formato válido
                    if (!is_numeric($ci) || strlen($ci) < 5 || strlen($ci) > 10) {
                        Log::warning("Fila $index: CI inválido '$ciCompleto' (extraído: '$ci'), saltando");
                        $errores++;
                        continue;
                    }

                    // ✅ Validar que no sea una fecha mal interpretada
                    if (strlen($ci) > 8 && substr($ci, 0, 2) === '19') {
                        Log::warning("Fila $index: CI parece ser una fecha '$ci', saltando");
                        $errores++;
                        continue;
                    }

                    // ✅ VERIFICAR SI YA EXISTE EL CI (SOLO POR CI) usando Eloquent
                    $personaExistente = Persona::where('ci_persona', $ci)->first();

                    if ($personaExistente) {

                        // ✅ VERIFICAR QUÉ CAMPOS NECESITAN ACTUALIZARSE
                        $datosActualizar = [];

                        if (empty($personaExistente->nombre_persona) && !empty($nombre)) {
                            $datosActualizar['nombre_persona'] = $nombre;
                        }

                        if (empty($personaExistente->apellido_persona) && !empty($apellido)) {
                            $datosActualizar['apellido_persona'] = $apellido;
                        }

                        if (empty($personaExistente->distrito) && !empty($distrito)) {
                            $datosActualizar['distrito'] = $distrito;
                        }

                        // ✅ Solo actualizar observación si está vacía en BD Y hay dato válido en Excel
                        if (empty($personaExistente->observacion_persona) && !empty($observacion)) {
                            $datosActualizar['observacion_persona'] = $observacion;
                        }

                        if (empty($personaExistente->complemento) && !empty($complemento)) {
                            $datosActualizar['complemento'] = $complemento;
                        }

                        // ✅ Si hay campos para actualizar
                        if (count($datosActualizar) > 0) {
                            $datosActualizar['tipo_registro'] = 'beneficiario';

                            $personaExistente->update($datosActualizar);

                            // ✅ Crear registro en HistorialEstados solo si no existe uno activo usando Eloquent
                            $tieneEstadoActivo = HistorialEstados::where('id_persona', $personaExistente->id_persona)
                                ->where('estado', 'activo')
                                ->whereNull('fecha_fin')
                                ->exists();

                            if (!$tieneEstadoActivo) {
                                HistorialEstados::create([
                                    'id_persona' => $personaExistente->id_persona,
                                    'estado' => 'activo',
                                    'fecha_inicio' => $personaExistente->fecha_registro ?? Carbon::now()->format('Y-m-d'),
                                    'fecha_fin' => null,
                                    'usuario_modificacion' => $nombreCompleto,
                                    'observaciones' => ''
                                ]);
                            }

                            $actualizados++;
                        }
                        continue;
                    }

                    $fechaRegistro = Carbon::now()->format('Y-m-d');

                    // ✅ Agregar mensaje de fecha de nacimiento a la observación
                    if (!empty($observacion)) {
                        $observacion .= ', Fecha de nacimiento no proporcionada';
                    } else {
                        $observacion = 'Fecha de nacimiento no proporcionada';
                    }

                    // ✅ Crear nueva persona usando Eloquent
                    $nuevaPersona = Persona::create([
                        'ci_persona' => $ci,
                        'complemento' => $complemento,
                        'nombre_persona' => $nombre,
                        'apellido_persona' => $apellido,
                        'distrito' => $distrito,
                        'fecha_nacimiento' => null,
                        'observacion_persona' => $observacion,
                        'tipo_registro' => 'beneficiario',
                        'fecha_registro' => $fechaRegistro,
                    ]);

                    // ✅ Crear registro en HistorialEstados para nueva persona usando Eloquent
                    HistorialEstados::create([
                        'id_persona' => $nuevaPersona->id_persona,
                        'estado' => 'activo',
                        'fecha_inicio' => $fechaRegistro,
                        'fecha_fin' => null,
                        'usuario_modificacion' => $nombreCompleto,
                        'observaciones' => ''
                    ]);

                    $insertados++;
                } catch (\Exception $e) {
                    Log::error("Error procesando fila $index:", [
                        'error' => $e->getMessage(),
                        'ci' => $ci ?? 'desconocido',
                        'trace' => $e->getTraceAsString()
                    ]);
                    $errores++;
                }
            }

            DB::commit();

            return back()->with('importResults', [
                'type' => ($insertados > 0 || $actualizados > 0) ? 'success' : 'warning',
                'insertados' => $insertados,
                'actualizados' => $actualizados,
                'duplicados' => $duplicados,
                'errores' => $errores,
                'total_procesado' => $insertados + $actualizados + $duplicados + $errores,
                'message' => $this->generarMensajeResumen($insertados, $actualizados, $duplicados, $errores)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en importación:', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
            return back()->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }

    /**
     * Procesa observación: convierte "NINGUNO" o vacío a NULL
     */
    private function procesarObservacion(string $valor): ?string
    {
        if (empty($valor)) {
            return null;
        }

        $texto = trim($valor);

        // Si es vacío o "NINGUNO" (case insensitive) o "activo" -> retornar NULL
        if (
            $texto === '' ||
            strtoupper($texto) === 'NINGUNO' ||
            strtoupper($texto) === 'NINGUNA' ||
            strtolower($texto) === 'activo'
        ) {
            return null;
        }

        // Limpiar y capitalizar
        return ucfirst(strtolower($texto));
    }

    /**
     * Separa CI y complemento si tiene guion
     */
    private function separarCIyComplemento(string $ciCompleto): array
    {
        $ci = $ciCompleto;
        $complemento = null;

        if (strpos($ciCompleto, '-') !== false) {
            $partes = explode('-', $ciCompleto, 2);
            $ci = trim($partes[0]);
            $complemento = strtoupper(trim($partes[1]));
        }

        $ci = str_replace(' ', '', $ci);

        if ($complemento) {
            $complemento = str_replace(' ', '', $complemento);
            if (empty($complemento)) {
                $complemento = null;
            }
        }

        return ['ci' => $ci, 'complemento' => $complemento];
    }

    /**
     * Limpia texto: trim, capitaliza primera letra de cada palabra
     */
    private function limpiarTexto(string $valor): ?string
    {
        if (empty($valor)) {
            return null;
        }

        $texto = trim($valor);
        if ($texto === '') {
            return null;
        }

        return ucwords(strtolower($texto));
    }

    /**
     * Genera mensaje de resumen
     */
    private function generarMensajeResumen(int $insertados, int $actualizados, int $duplicados, int $errores): string
    {
        $mensaje = "Importación completada: {$insertados} nuevos registros.";

        if ($actualizados > 0) {
            $mensaje .= " {$actualizados} registros actualizados.";
        }

        if ($duplicados > 0) {
            $mensaje .= " {$duplicados} registros ya completos (ignorados).";
        }

        if ($errores > 0) {
            $mensaje .= " {$errores} errores.";
        }

        return $mensaje;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Verificar si existe la sesión y obtener el ID del tutor
        $tutor = null;
        $esPropioTutor = session('tutor_propio', false);

        if (!$esPropioTutor && session('selected_tutor_id')) {
            $id_tutor = session('selected_tutor_id');
            $tutor = Tutor::find($id_tutor);
        }

        // ✅ Definir fechaBase ANTES de preparar $data
        $fechaBase = $request->filled('fecha_registro')
            ? Carbon::parse($request->fecha_registro, 'America/La_Paz')->startOfMonth()->format('Y-m-d')
            : Carbon::now('America/La_Paz')->startOfMonth()->format('Y-m-d');

        // Preparar los datos para la creación
        $data = $request->all();

        // Verificar si ya existe una persona con ese CI
        $existePersona = Persona::where('ci_persona', $data['ci_persona'])->exists();

        if ($existePersona) {
            $persona = Persona::where('ci_persona', $data['ci_persona'])->first();
            return back()
                ->withErrors(['ci_persona' => 'Ya existe una persona con esta cédula'])
                ->with('persona_existente', [
                    'tipo_registro' => $persona->tipo_registro
                ]);
        }

        // Agregar ID del tutor si existe
        if (isset($id_tutor)) {
            $data['id_tutor'] = $id_tutor;
        }

        // Si es propio tutor, guardar en tutor_nombre
        if ($esPropioTutor) {
            $data['tutor_nombre'] = 'propio';
        }

        // ✅ Asignar fechaBase
        $data['fecha_registro'] = $fechaBase;
        $data['tipo_registro'] = 'beneficiario';

        // Construir observación
        $observaciones = [];

        if (!isset($id_tutor) && !$esPropioTutor) {
            $observaciones[] = 'Tutor no proporcionado';
        }

        $observaciones[] = 'Carnet de discapacidad no proporcionado';

        $data['observacion_persona'] = implode(', ', $observaciones);

        // Crear el registro en la base de datos
        $persona = Persona::create($data);

        $user = Auth::user();

        $nombreCompleto = "{$user->nombre} {$user->apellido}";

        $dataEstado = [
            'id_persona' => $persona->id_persona,
            'estado' => 'activo',
            'fecha_inicio' => $fechaBase,
            'fecha_fin' => null,
            'usuario_modificacion' => $nombreCompleto,
            'observaciones' => ''
        ];

        HistorialEstados::create($dataEstado);

        // Registra la creación
        $nombre = ucwords(strtolower("{$persona->nombre_persona} {$persona->apellido_persona}"));

        $this->logService->logCreation(
            'Beneficiario',
            $persona,
            "Se registró al beneficiario {$nombre} en el sistema.",
            null,
            [
                'beneficiario' => $nombre,
                'c.i.' => $persona->ci_persona,
                'distrito' => $persona->distrito,
                'fecha nacimiento' => $persona->fecha_nacimiento,
                'tutor asignado' => $esPropioTutor
                    ? 'propio'
                    : ($tutor ? ucwords(strtolower("{$tutor->nombre_tutor} {$tutor->apellido_tutor}")) : null),
                'c.i. tutor' => $esPropioTutor ? null : ($tutor->ci_tutor ?? null),
            ]
        );

        // Eliminar la sesión del tutor seleccionado
        session()->forget('selected_tutor_id');
        session()->forget('tutor_propio');

        return redirect()->back();
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $persona = Persona::findOrFail($id);

        $fieldsToUpdate = $request->all();
        unset($fieldsToUpdate['id_persona']);
        // fecha_registro no se edita desde aquí: la única vía válida es mover
        // la fecha_inicio del primer estado (HistorialEstadoService::moverLimite),
        // que ya sabe cuándo corresponde arrastrar fecha_registro con ella.
        // Editarla directo en este endpoint podía pisar fecha_inicio de TODO
        // el historial_estados de la persona (ver bug reportado en CI 12556166).
        unset($fieldsToUpdate['fecha_registro']);

        if ($persona->tipo_registro === 'pendiente') {
            $fieldsToUpdate['tipo_registro'] = 'beneficiario';
        }

        // Texto a limpiar de observacion
        $observacionRaw = \array_key_exists('observacion_persona', $fieldsToUpdate)
            ? $fieldsToUpdate['observacion_persona']
            : $persona->observacion_persona;

        if (!empty($observacionRaw)) {
            $observacionRaw = str_ireplace('Fecha de nacimiento no proporcionada', '', $observacionRaw);
            $observacionRaw = preg_replace('/\s*,\s*,\s*/', ', ', $observacionRaw);
            $observacionRaw = preg_replace('/^\s*,\s*/', '', $observacionRaw);
            $observacionRaw = preg_replace('/\s*,\s*$/', '', $observacionRaw);
            $observacionRaw = trim($observacionRaw);
        }
        $fieldsToUpdate['observacion_persona'] = empty($observacionRaw) ? null : $observacionRaw;

        $mapaLabels = [
            'nombre_persona' => 'nombre',
            'apellido_persona' => 'apellido',
            'ci_persona' => 'c.i.',
            'fecha_nacimiento' => 'fecha nacimiento',
            'distrito' => 'distrito',
            'observacion_persona' => 'observación',
            'tipo_registro' => 'tipo registro',
        ];

        $camposModificados = [];
        $valoresAnteriores = [];
        $valoresNuevos = [];

        foreach ($fieldsToUpdate as $campo => $nuevoValor) {
            if (!\array_key_exists($campo, $mapaLabels))
                continue;

            $valorAnterior = $persona->$campo;
            $label = $mapaLabels[$campo];

            if ($valorAnterior != $nuevoValor) {
                $camposModificados[$label] = $nuevoValor;
                $valoresAnteriores[$label] = $valorAnterior;
                $valoresNuevos[$label] = $nuevoValor;
            }
        }

        if (empty($camposModificados)) {
            return;
        }

        $persona->update($fieldsToUpdate);

        $nombre = ucwords(strtolower("{$persona->nombre_persona} {$persona->apellido_persona}"));

        $this->logService->logUpdate(
            'Beneficiario',
            $persona,
            [
                'campos_modificados' => $camposModificados,
                'valores_anteriores' => $valoresAnteriores,
                'valores_nuevos' => $valoresNuevos,
            ],
            "Se actualizó el registro de {$nombre} en el sistema."
        );
    }

    public function estado(Request $request)
    {
        $request->validate([
            // Al agregar un estado nuevo no se exige motivo: la observación
            // (autocompletada según el estado, ej. "FALLECIO 12/01/2026") ya
            // documenta el porqué — pedir motivo también era redundante.
            'motivo' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:50',
        ]);

        $user = Auth::user();
        $nombreCompleto = "{$user->nombre} {$user->apellido}";

        // Persona.fecha_registro solo debe seguir a fecha_inicio cuando este
        // es el primer estado que se registra para la persona (mismo
        // criterio que HistorialEstadoService::moverLimite). `id_estado` no
        // sirve para distinguir esto: el formulario de "Agregar estado"
        // nunca lo envía, así que llegaba null en cada cambio de estado (no
        // solo en el primero) y pisaba fecha_registro cada vez.
        $esPrimerEstado = !HistorialEstados::where('id_persona', $request->id_persona)->exists();

        try {
            $this->historialEstadoService->agregar(
                $request->id_persona,
                $request->estado,
                Carbon::parse($request->fecha_inicio),
                $request->motivo,
                $nombreCompleto,
                $request->input('observaciones')
            );

            if ($esPrimerEstado) {
                Persona::where('id_persona', $request->id_persona)
                    ->update(['fecha_registro' => Carbon::parse($request->fecha_inicio)->format('Y-m-d')]);
            }
        } catch (HistorialEstadoException $e) {
            return back()->withErrors([$e->campo => $e->getMessage()]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateEstado(Request $request, string $id)
    {
        $request->validate([
            'motivo' => 'required|string|max:255',
            'observaciones' => 'nullable|string|max:50',
        ]);

        $historial = HistorialEstados::findOrFail($id);

        $user = Auth::user();
        $nombreCompleto = "{$user->nombre} {$user->apellido}";
        $esSuperusuario = $user->hasRole('superUsuario');

        $nuevoEstado = $request->input('estado');
        $motivo = $request->input('motivo');
        $observaciones = $request->input('observaciones');
        $nuevaFechaInicio = $request->filled('fecha_inicio') ? Carbon::parse($request->input('fecha_inicio')) : null;

        $cambiaFecha = $nuevaFechaInicio && !$nuevaFechaInicio->equalTo(Carbon::parse($historial->fecha_inicio));
        $cambiaEstado = $nuevoEstado && $nuevoEstado !== $historial->estado;
        // Editar solo observaciones/motivo (sin tocar estado ni fecha) es un
        // caso válido — antes se descartaba en silencio porque solo se
        // guardaba cuando cambiaba el estado.
        $cambiaObservaciones = $observaciones !== null && $observaciones !== $historial->observaciones;
        $cambiaMotivo = $motivo !== null && $motivo !== $historial->motivo;

        if (!$cambiaFecha && !$cambiaEstado && !$cambiaObservaciones && !$cambiaMotivo) {
            return;
        }

        try {
            if ($cambiaFecha) {
                // Persona.fecha_registro se actualiza dentro del servicio,
                // y solo cuando corresponde (únicamente si el estado movido
                // es el primer estado de la persona, sin vecino anterior).
                $this->historialEstadoService->moverLimite($historial, $nuevaFechaInicio, $nombreCompleto, $esSuperusuario);
            }

            if ($cambiaEstado || $cambiaObservaciones || $cambiaMotivo) {
                $this->historialEstadoService->editarSoloEstado($historial->fresh(), $nuevoEstado ?: $historial->estado, $motivo, $nombreCompleto, $esSuperusuario, $observaciones);
            }
        } catch (HistorialEstadoException $e) {
            return back()->withErrors([$e->campo => $e->getMessage()]);
        }
    }

    /**
     * Edita únicamente la observación de un estado histórico (sin tocar
     * estado, fecha ni motivo). Es el único punto de edición disponible
     * sobre un estado que ya no es "gestionable" (ver puede_gestionar más
     * arriba) para un no-superusuario.
     */
    public function updateObservacionEstado(Request $request, string $id)
    {
        $request->validate([
            'observaciones' => 'required|string|max:50',
        ]);

        $historial = HistorialEstados::findOrFail($id);

        $user = Auth::user();
        $nombreCompleto = "{$user->nombre} {$user->apellido}";

        $this->historialEstadoService->editarObservacion($historial, $request->input('observaciones'), $nombreCompleto);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroyEstado(int $id)
    {
        $historial = HistorialEstados::findOrFail($id);

        try {
            $this->historialEstadoService->eliminar($historial, Auth::user()->hasRole('superUsuario'));
        } catch (HistorialEstadoException $e) {
            return back()->withErrors([$e->campo => $e->getMessage()]);
        }
    }

    /**
     * Inserta un estado nuevo en medio de un segmento existente de la línea
     * de tiempo, partiéndolo en hasta 3 registros (antes/nuevo/después).
     */
    public function insertarEstadoIntermedio(Request $request)
    {
        $request->validate([
            // Mismo criterio que en estado(): insertar es una creación, no
            // una edición — la observación ya documenta el motivo.
            'motivo' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:50',
        ]);

        $base = HistorialEstados::findOrFail($request->input('id_estado_base'));

        $user = Auth::user();
        $nombreCompleto = "{$user->nombre} {$user->apellido}";

        try {
            $this->historialEstadoService->insertarIntermedio(
                $base,
                $request->input('estado'),
                Carbon::parse($request->input('mes_inicio')),
                $request->input('motivo'),
                $nombreCompleto,
                $user->hasRole('superUsuario'),
                $request->input('observaciones')
            );
        } catch (HistorialEstadoException $e) {
            return back()->withErrors([$e->campo => $e->getMessage()]);
        }
    }
}
