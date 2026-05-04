<?php

namespace App\Http\Controllers;

use App\Models\Gestion;
use App\Models\Habilitado;
use App\Models\HistorialEstados;
use App\Models\Mes;
use App\Models\Persona;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Str;

class GestionController extends Controller
{
    protected $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $personasPagadasPorMes = [];

        $año = $request->input('año')
            ?? session('selected_year')
            ?? Gestion::max('gestion')
            ?? Carbon::now()->year;

        session(['selected_year' => $año]);
        $selectedYear     = session('selected_year');
        $añoActualSistema = Carbon::now()->year;
        //$añoActualSistema = 2026;

        // ================== AÑO ACTUAL DEL SISTEMA ==================
        // ✅ Una sola query reutilizada en lugar de dos iguales
        $gestionAñoActual   = Gestion::where('gestion', $añoActualSistema)->first();
        $existeAñoActual    = (bool) $gestionAñoActual;
        $idGestionActual    = $gestionAñoActual?->id_gestion;
        $PresuGestionActual = $gestionAñoActual?->presupuesto_anual;

        // ================== VERIFICACIÓN INICIAL ==================
        $gestionActual = Gestion::where('gestion', $selectedYear)->first();
        $existeGestion = Gestion::exists();

        $tieneMeses = $gestionActual
            ? $gestionActual->meses()->exists()   // ✅ Eloquent via relación
            : false;

        // ================== CONSULTA PRINCIPAL (solo si hay gestión y meses) ==================
        $gestion = collect();

        if ($gestionActual && $tieneMeses) {
            $gestion = Gestion::query()
                ->select([
                    'gestion.id_gestion as id',
                    'gestion.gestion as gestion',
                    'gestion.presupuesto_anual as presupuesto_anual',
                    'mes.id_mes as id_mes',
                    'mes.mes as mes',
                    'mes.monto as monto',
                    'mes.presupuesto as presupuesto',

                    // ── Habilitados ──────────────────────────────────────────────
                    DB::raw('(SELECT COUNT(DISTINCT h.id_persona)
                        FROM habilitado h
                        WHERE h.id_mes = mes.id_mes
                    ) as cantidad_habilitadas'),

                    DB::raw('(SELECT COUNT(DISTINCT p.id_persona)
                        FROM persona p
                    ) as total_personas'),

                    // ── Carnets ──────────────────────────────────────────────────
                    DB::raw('(SELECT COUNT(DISTINCT p.id_persona)
                        FROM persona p
                        LEFT JOIN carnet c ON p.id_persona = c.id_persona
                        JOIN historial_estados he ON p.id_persona = he.id_persona
                        WHERE c.id_persona IS NULL
                        AND he.estado = \'activo\'
                    ) as sin_carnet_discapacidad'),

                    DB::raw('(SELECT COUNT(DISTINCT p.id_persona)
                        FROM persona p
                        INNER JOIN carnet c ON p.id_persona = c.id_persona
                        JOIN historial_estados he ON p.id_persona = he.id_persona
                        WHERE c.fecha_vencimiento < CURDATE()
                        AND c.fecha_emision IS NOT NULL
                        AND c.fecha_vencimiento IS NOT NULL
                        AND he.estado = \'activo\'
                    ) as carnet_vencidos'),

                    // ── Estados (último registro por persona) ────────────────────
                    DB::raw("(SELECT COUNT(DISTINCT p.id_persona)
                        FROM persona p
                        INNER JOIN historial_estados he ON he.id = (
                            SELECT MAX(id)
                            FROM historial_estados
                            WHERE id_persona = p.id_persona
                        )
                        WHERE he.estado = 'activo'
                        AND he.id = (
                            SELECT MAX(he2.id) FROM historial_estados he2
                            WHERE he2.id_persona = he.id_persona
                        )
                    ) as personas_activos"),

                    DB::raw("(SELECT COUNT(DISTINCT p.id_persona)
                        FROM persona p
                        INNER JOIN historial_estados he ON he.id = (
                            SELECT MAX(id)
                            FROM historial_estados
                            WHERE id_persona = p.id_persona
                        )
                        WHERE he.estado = 'baja_temporal'
                        AND he.id = (
                            SELECT MAX(he2.id) FROM historial_estados he2
                            WHERE he2.id_persona = he.id_persona
                        )
                    ) as personas_baja_temporal"),

                    DB::raw("(SELECT COUNT(DISTINCT p.id_persona)
                        FROM persona p
                        INNER JOIN historial_estados he ON he.id = (
                            SELECT MAX(id)
                            FROM historial_estados
                            WHERE id_persona = p.id_persona
                        )
                        WHERE he.estado = 'baja_definitiva'
                        AND he.id = (
                            SELECT MAX(he2.id) FROM historial_estados he2
                            WHERE he2.id_persona = he.id_persona)
                        ) as personas_baja_definitiva"),

                    DB::raw('(SELECT COUNT(DISTINCT p.id_persona)
                        FROM persona p
                        INNER JOIN historial_estados he ON he.id = (
                            SELECT MAX(id)
                            FROM historial_estados
                            WHERE id_persona = p.id_persona
                        )
                        WHERE he.estado = \'activo\'
                        AND p.id_tutor IS NULL
                        AND (p.tutor_nombre IS NULL OR p.tutor_nombre != \'propio\')
                    ) AS personas_sin_tutor'),

                    // ── Pagos del mes ────────────────────────────────────────────
                    DB::raw('(SELECT COUNT(pag.id_pago)
                        FROM pago pag
                        INNER JOIN habilitado hab ON pag.id_habilitado = hab.id_habilitado
                        WHERE hab.id_gestion = gestion.id_gestion
                        AND hab.id_mes = mes.id_mes
                    ) as cantidad_total_pagos'),

                    DB::raw('((SELECT COUNT(DISTINCT h.id_persona)
                        FROM habilitado h WHERE h.id_mes = mes.id_mes) -
                        (SELECT COUNT(pag.id_pago)
                        FROM pago pag
                        INNER JOIN habilitado hab ON pag.id_habilitado = hab.id_habilitado
                        WHERE hab.id_gestion = gestion.id_gestion
                        AND hab.id_mes = mes.id_mes)
                    ) as cantidad_no_pagados'),

                    // ✅ Un solo campo unificado, usado por modal y PDF
                    DB::raw('(SELECT COALESCE(SUM(pag.monto), 0)
                        FROM pago pag
                        INNER JOIN habilitado hab ON pag.id_habilitado = hab.id_habilitado
                        WHERE hab.id_gestion = gestion.id_gestion
                        AND hab.id_mes = mes.id_mes
                    ) as total_pagado'),

                    // ── Presupuesto anual ────────────────────────────────────────
                    DB::raw('(SELECT COALESCE(SUM(p2.monto), 0)
                        FROM pago p2
                        INNER JOIN habilitado h2 ON p2.id_habilitado = h2.id_habilitado
                        WHERE h2.id_gestion = gestion.id_gestion
                    ) as presupuesto_anual_utilizado'),

                    DB::raw('(gestion.presupuesto_anual -
                        (SELECT COALESCE(SUM(p2.monto), 0)
                        FROM pago p2
                        INNER JOIN habilitado h2 ON p2.id_habilitado = h2.id_habilitado
                        WHERE h2.id_gestion = gestion.id_gestion)
                    ) as presupuesto_anual_restante'),

                    // ── Porcentajes ──────────────────────────────────────────────
                    DB::raw('ROUND(((SELECT COUNT(DISTINCT h.id_persona)
                        FROM habilitado h WHERE h.id_gestion = gestion.id_gestion) * 100.0) /
                        NULLIF((SELECT COUNT(DISTINCT p.id_persona) FROM persona p), 0)
                    , 2) as porcentaje_habilitado'),

                    DB::raw('ROUND(((SELECT COUNT(pag.id_pago)
                        FROM pago pag
                        INNER JOIN habilitado hab ON pag.id_habilitado = hab.id_habilitado
                        WHERE hab.id_gestion = gestion.id_gestion
                        AND hab.id_mes = mes.id_mes) * 100.0) /
                        NULLIF((SELECT COUNT(DISTINCT h.id_persona)
                        FROM habilitado h WHERE h.id_mes = mes.id_mes), 0)
                    , 2) as porcentaje_pagado'),

                    // ── ✅ NUEVO: Último número de boleta del consecutivo ─────────
                    DB::raw('(SELECT bc.ultimo_numero
                        FROM boleta_consecutivo bc
                        WHERE bc.id_mes     = mes.id_mes
                        AND   bc.id_gestion = gestion.id_gestion
                        LIMIT 1
                    ) as ultimo_numero_boleta'),
                ])
                ->from('gestion')
                ->leftJoin('mes', 'mes.id_gestion', '=', 'gestion.id_gestion')
                ->where('gestion.gestion', '=', $selectedYear)
                ->groupBy([
                    'gestion.id_gestion',
                    'gestion.gestion',
                    'gestion.presupuesto_anual',
                    'mes.id_mes',
                    'mes.mes',
                    'mes.monto',
                    'mes.presupuesto',
                ])
                ->orderBy('mes.mes', 'asc')
                ->get();
        }

        // ================== MES DISPONIBLE CONF ==================
        //$mesActual    = Carbon::now()->month;
        $mesActual    = 4;
        $mesDisponible = 0;

        // ✅ Reutiliza $gestionAñoActual en lugar de volver a consultar
        if ($gestionAñoActual) {
            $mesYaExiste = $gestionAñoActual->meses()   // ✅ Eloquent via relación
                ->where('mes', $mesActual)
                ->exists();
            if (!$mesYaExiste) {
                $mesDisponible = $mesActual;
            }
        }

        // ================== CONSULTAS AUXILIARES ==================
        $gestiones = Gestion::select('gestion')   // ✅ Eloquent
            ->distinct()
            ->orderByDesc('gestion')
            ->get();

        $presupuestosAnuales = Gestion::select('gestion as año', 'presupuesto_anual')   // ✅ Eloquent
            ->distinct()
            ->orderByDesc('año')
            ->get()
            ->keyBy('año')
            ->map(fn($item) => $item->presupuesto_anual);

        $cisPersonasActivas = Persona::query()
            ->whereHas('ultimoEstado', fn($q) => $q->where('estado', 'activo'))
            ->pluck('ci_persona')
            ->map(fn($ci) => (string) $ci)
            ->toArray();

        $cisTodasPersonas = Persona::query()
            ->pluck('ci_persona')
            ->map(fn($ci) => (string) $ci)
            ->toArray();

        $sumaPresupuestoMensual = $gestionActual
            ? $gestionActual->meses()->sum('presupuesto')   // ✅ Eloquent via relación
            : 0;

        $total_personas_validas = Persona::query()
            ->where('tipo_registro', '!=', 'registrado')
            ->whereHas('ultimoEstado', fn($q) => $q->where('estado', 'activo'))
            ->whereHas('carnet', fn($q) => $q->where('fecha_vencimiento', '>', now()))
            ->where(fn($q) => $q->whereNotNull('id_tutor')
                ->orWhere('tutor_nombre', 'propio'))
            ->count();

        $personasPagadasPorMes = [];

        if ($gestionActual && $tieneMeses) {

            foreach ($gestionActual->meses as $mesModel) {
                $fechaFinMes = Carbon::createFromDate($gestionActual->gestion, $mesModel->mes, 1)->endOfMonth()->toDateString();

                $ultimoHistorial = DB::table('historial_estados as he')
                    ->select('he.id_persona', 'he.estado')
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

                $resultados = DB::table('persona as p')
                    ->select([
                        'm.id_mes',
                        'm.mes',
                        'p.ci_persona',
                        'p.apellido_persona',
                        'p.nombre_persona',
                        'p.nombre_completo',
                        'pag.monto as monto_pago',
                        'pag.numero_boleta',
                        'h.estado as estado_actual',
                    ])
                    ->selectRaw("UPPER(COALESCE(
                NULLIF(TRIM(CONCAT(COALESCE(p.apellido_persona,''), ' ', COALESCE(p.nombre_persona,''))), ' '),
                p.nombre_completo
            )) as nombre_orden")
                    ->joinSub($ultimoHistorial, 'h', 'h.id_persona', '=', 'p.id_persona')
                    ->crossJoin(DB::raw("(SELECT {$mesModel->id_mes} as id_mes, {$mesModel->mes} as mes) as m"))
                    ->leftJoin('habilitado as hab', function ($join) use ($gestionActual, $mesModel) {
                        $join->on('hab.id_persona', '=', 'p.id_persona')
                            ->where('hab.id_gestion', $gestionActual->id_gestion)
                            ->where('hab.id_mes', $mesModel->id_mes);
                    })
                    ->leftJoin('pago as pag', 'pag.id_habilitado', '=', 'hab.id_habilitado')
                    ->where(function ($q) {
                        $q->whereNotNull('hab.id_habilitado')
                            ->orWhereIn('h.estado', ['baja_temporal', 'baja_definitiva']);
                    })
                    ->orderByRaw('nombre_orden ASC')
                    ->get();

                $personasPagadasPorMes[$mesModel->id_mes] = $resultados->values();
            }
        }

        // ================== RETORNO ==================
        return Inertia::render('Gestion/index', [
            'gestiones'              => $gestiones,
            'gestion'                => $gestion,
            'filters'                => [
                'año'      => $selectedYear,
                'buscador' => $request->input('buscador', ''),
            ],
            'años_registrados'       => $gestiones->pluck('gestion'),
            'presupuestosAnuales'    => $presupuestosAnuales,
            'cis_personas_activas'   => $cisPersonasActivas,
            'cis_todas_personas'     => $cisTodasPersonas,
            'sumaPresupuestoMensual' => $sumaPresupuestoMensual,
            'personasPagadasPorMes' => $personasPagadasPorMes,
            'total_personas_validas' => $total_personas_validas,
            'añoSeleccionado'        => $gestionActual ?? (object) ['gestion' => $selectedYear],
            'año_actual'             => [
                'añoActualSistema'  => $añoActualSistema,
                'existeAñoActual'   => $existeAñoActual,
                'id'                => $idGestionActual,
                'presupuesto_anual' => $PresuGestionActual ?? 0,
            ],
            'mes_actual_disponible'  => $mesDisponible,
            'existe_gestion'         => (bool) $gestionActual,
            'gestionData'            => $existeGestion,
            'tiene_meses'            => $tieneMeses,
            'btnAgregar'             => $existeAñoActual,
        ]);
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
        $data = $request->all();
        $gestion = Gestion::create($data);

        $this->logService->logCreation(
            'Gestion',
            $gestion,
            "Se registró la gestión {$gestion->gestion} en el sistema.",
            null,
            [
                'gestión'           => $gestion->gestion,
                'presupuesto anual' => $gestion->presupuesto_anual,
            ]
        );

        session(['selected_year' => $gestion->gestion]);
        return redirect()->route('gestion.index', ['año' => $gestion->gestion]);
    }


    public function addMes(Request $request)
    {
        $request->validate([
            'archivo_pdf' => 'required|file|mimes:pdf|max:5120',
        ]);

        $userId = Auth::id();
        $fechaHoy = Carbon::now('America/La_Paz');
        $fechaHoyString = $fechaHoy->format('Y-m-d');

        $registrosExtraidos = $request->input('registros_extraidos')
            ? json_decode($request->input('registros_extraidos'), true)
            : [];

        try {
            DB::beginTransaction();

            $mes = Mes::create([
                'mes'         => $request->input('mes'),
                'monto'       => $request->input('monto'),
                'presupuesto' => $request->input('presupuesto'),
                'id_gestion'  => $request->input('id_gestion'),
            ]);

            DB::table('boleta_consecutivo')->insertOrIgnore([
                'id_mes'        => $mes->id_mes,
                'id_gestion'    => $mes->id_gestion,
                'ultimo_numero' => 0,
            ]);

            $insertados  = [];
            $habilitados = [];
            $errores     = [];
            $omitidos    = [];
            $fallecidos  = []; // ✅ NUEVO

            $gestion = Gestion::find($request->input('id_gestion'));

            $fechaPrimeroMes = Carbon::create($gestion->gestion, $request->input('mes'), 1)
                ->format('Y-m-d');

            $user = Auth::user();
            $nombreCompleto = "{$user->nombre} {$user->apellido}";

            $cisExistentes = Persona::pluck('id_persona', 'ci_persona')->toArray();

            foreach ($registrosExtraidos as $registro) {
                $ci     = trim($registro['ci']);
                $nombre = trim($registro['nombre']);

                if (empty($ci) || empty($nombre)) continue;

                try {
                    if (isset($cisExistentes[$ci])) {
                        $idPersona = $cisExistentes[$ci];

                        $ultimoEstado = HistorialEstados::where('id_persona', $idPersona)
                            ->orderByDesc('id')
                            ->first(); // ← objeto completo

                        if ($ultimoEstado->estado === 'baja_temporal') {
                            $omitidos[] = ['ci' => $ci, 'nombre' => $nombre, 'motivo' => 'baja_temporal'];
                            continue;
                        }

                        if ($ultimoEstado->estado === 'baja_definitiva') {
                            $omitidos[] = ['ci' => $ci, 'nombre' => $nombre, 'motivo' => 'baja_definitiva'];
                            continue;
                        }

                        // ✅ NUEVO: depurado → baja_definitiva (FALLECIDO)
                        if ($ultimoEstado->estado === 'depurado') {
                            // Cerrar estado depurado anterior
                            $ultimoEstado->update(['fecha_fin' => $fechaPrimeroMes]);

                            // Crear baja_definitiva
                            HistorialEstados::create([
                                'id_persona'           => $idPersona,
                                'estado'               => 'baja_definitiva',
                                'fecha_inicio'         => $fechaPrimeroMes,
                                'fecha_fin'            => null,
                                'fecha_registro'       => $fechaPrimeroMes,
                                'usuario_modificacion' => $nombreCompleto,
                                'observaciones'        => 'FALLECIDO',
                            ]);

                            $fallecidos[] = ['ci' => $ci, 'nombre' => $nombre]; // ✅
                            continue;
                        }

                        if ($ultimoEstado->estado !== 'activo') {
                            $omitidos[] = ['ci' => $ci, 'nombre' => $nombre, 'motivo' => $ultimoEstado->estado];
                            continue;
                        }
                    } else {
                        $idPersona = (string) Str::uuid();

                        Persona::create([
                            'id_persona'      => $idPersona,
                            'nombre_completo' => $nombre,
                            'ci_persona'      => $ci,
                            'tipo_registro'   => 'pendiente',
                            'fecha_registro'  => $fechaPrimeroMes,
                        ]);

                        HistorialEstados::create([
                            'id_persona'           => $idPersona,
                            'estado'               => 'activo',
                            'fecha_inicio'         => $fechaPrimeroMes,
                            'fecha_fin'            => null,
                            'fecha_registro'       => $fechaPrimeroMes,
                            'usuario_modificacion' => $nombreCompleto,
                            'observaciones'        => '',
                        ]);

                        $cisExistentes[$ci] = $idPersona;
                        $insertados[] = ['ci' => $ci, 'nombre' => $nombre];
                    }

                    Habilitado::create([
                        'habilitado'       => 1,
                        'id'               => $userId,
                        'id_persona'       => $idPersona,
                        'id_gestion'       => $mes->id_gestion,
                        'id_mes'           => $mes->id_mes,
                        'fecha_habilitado' => $fechaHoy,
                    ]);

                    $habilitados[] = ['ci' => $ci, 'nombre' => $nombre];
                } catch (Exception $e) {
                    Log::error('Error al procesar registro PDF', [
                        'ci'      => $ci,
                        'nombre'  => $nombre,
                        'error'   => $e->getMessage(),
                        'linea'   => $e->getLine(),
                        'archivo' => $e->getFile(),
                    ]);
                    $errores[] = ['ci' => $ci, 'nombre' => $nombre, 'error' => $e->getMessage()];
                }
            }

            $nombreMes = [
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
                12 => 'Diciembre'
            ];

            $mesNumero = $request->input('mes');
            $mesNombre = $nombreMes[$mesNumero] ?? $mesNumero;

            // ─── Depurados ausentes del PDF ──────────────────────────────────────────
            $cisEnPdf = collect($registrosExtraidos)
                ->map(fn($r) => trim($r['ci']))
                ->filter()
                ->values()
                ->toArray();

            if (empty($cisEnPdf)) {
                $ultimosHistoriales = collect();
            } else {
                $ultimosHistoriales = Persona::with('ultimoEstado')
                    ->bajaDefinitiva()
                    ->whereNotIn('ci_persona', $cisEnPdf)
                    ->select('id_persona', 'ci_persona')
                    ->get()
                    ->map(fn($persona) => [
                        'historial_id' => $persona->ultimoEstado->id,
                        'id_persona'   => $persona->id_persona,
                        'ci_persona'   => $persona->ci_persona,
                    ]);
            }

            $depurados = [];

            foreach ($ultimosHistoriales as $historial) {
                DB::table('historial_estados')
                    ->where('id', $historial['historial_id'])
                    ->update(['fecha_fin' => $fechaPrimeroMes]);

                HistorialEstados::create([
                    'id_persona'           => $historial['id_persona'],
                    'estado'               => 'depurado',
                    'fecha_inicio'         => $fechaPrimeroMes,
                    'fecha_fin'            => null,
                    'fecha_registro'       => $fechaHoyString,
                    'usuario_modificacion' => $nombreCompleto,
                    'observaciones'        => 'Cambio automático: DEPURADO.',
                ]);

                $depurados[] = ['ci' => $historial['ci_persona']];
            }
            // ─────────────────────────────────────────────────────────────────────────

            DB::commit();

            $this->logService->logCreation(
                'Gestion',
                $mes,
                "Se registró el mes {$mesNombre} en el sistema.",
                null,
                [
                    'gestión'         => $gestion->gestion,
                    'mes'             => $mesNombre,
                    'monto Bs.'       => $mes->monto,
                    'presupuesto Bs.' => $mes->presupuesto,
                ]
            );

            $this->logService->logHabilitacionMasiva(
                'Gestion',
                $mes,
                [
                    'mes'                          => $mesNombre,
                    'monto'                        => $request->input('monto'),
                    'presupuesto'                  => $request->input('presupuesto'),
                    'beneficiarios_habilitados'    => count($habilitados),
                    'bajas_temporales_omitidas'    => count(array_filter($omitidos, fn($o) => $o['motivo'] === 'baja_temporal')),
                    'bajas_definitivas_omitidas'   => count(array_filter($omitidos, fn($o) => $o['motivo'] === 'baja_definitiva')),
                    'registros_depurados'          => count($depurados),
                    'fallecidos_detectados'        => count($fallecidos), // ✅
                    'total'                        => count($habilitados) + count($errores),
                    'successful'                   => count($habilitados),
                    'failed'                       => count($errores),
                    'filename'                     => $request->file('archivo_pdf')->getClientOriginalName(),
                    'errors'                       => array_map(fn($e) => "CI: {$e['ci']} - {$e['error']}", $errores),
                    'fallecidos'                   => array_map(fn($f) => "CI: {$f['ci']} - {$f['nombre']}", $fallecidos), // ✅
                ]
            );

            return redirect()->back()->with('resultadosMes', [
                'mes'             => $request->input('mes'),
                'insertados'      => $insertados,
                'habilitados'     => $habilitados,
                'errores'         => $errores,
                'bajas_temporales' => array_values(array_filter($omitidos, fn($o) => $o['motivo'] === 'baja_temporal')),
                'bajas_definitivas' => array_values(array_filter($omitidos, fn($o) => $o['motivo'] === 'baja_definitiva')),
                'omitidos'        => $omitidos,
                'depurados'       => $depurados,
                'fallecidos'      => $fallecidos,
                'total_procesado' => count($habilitados) + count($omitidos),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error general en addMes PDF', [
                'error'   => $e->getMessage(),
                'linea'   => $e->getLine(),
                'archivo' => $e->getFile(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Error al procesar: ' . $e->getMessage());
        }
    }

    public function reporte(Request $request)
    {
        $gestiones = Gestion::selectRaw('YEAR(gestion) as año')
            ->distinct()
            ->orderBy('año', 'desc')
            ->pluck('año');

        $year = $request->input('gestion');

        $request->session()->put([
            'gestion' => $request->input('gestion')
        ]);

        // Inicializar resultados como una colección vacía
        $resultados = collect([]);
        // Inicializar resultadoDatos como una colección vacía
        $resultadoDatos = collect([]);

        // Solo ejecutar la consulta si se proporciona al menos un filtro
        if (!empty($year)) {
            $query = Gestion::query()
                ->select([
                    'gestion.id_gestion as id',
                    'gestion.gestion as gestion',
                    'gestion.presupuesto_anual as presupuesto',
                    'mes.monto as monto',

                    // Conteos de personas
                    DB::raw('COUNT(DISTINCT habilitado.id_persona) as cantidad_habilitadas'),
                    DB::raw('COUNT(DISTINCT persona.id_persona) as total_personas'),
                    DB::raw('COUNT(DISTINCT persona.id_persona) - COUNT(DISTINCT habilitado.id_persona) as cantidad_no_habilitadas'),

                    // ✅ CORREGIDO: Usar historial_estados en lugar de persona.estado
                    DB::raw('COUNT(DISTINCT CASE
                                    WHEN carnet.id_persona IS NULL
                                        OR historial_estados.estado IN ("baja_temporal", "baja_definitiva")
                                        OR carnet.fecha_vencimiento < CURDATE()
                                    THEN persona.id_persona
                                    END) as inhabilitado'),

                    // ✅ CORREGIDO: Usar historial_estados para estado activo
                    DB::raw("COUNT(DISTINCT CASE WHEN historial_estados.estado = 'activo' THEN persona.id_persona END) as personas_activos"),

                    // Pagos
                    DB::raw('COUNT(pago.id_pago) as cantidad_total_pagos'),
                    DB::raw('COALESCE(SUM(pago.monto), 0) as total_pagado_contexto'),
                    DB::raw('COUNT(DISTINCT habilitado.id_persona) - COUNT(pago.id_pago) as cantidad_no_pagados'),
                    DB::raw('gestion.presupuesto_anual - COALESCE(SUM(pago.monto), 0) as presupuesto_restante'),

                    // ✅ SIMPLIFICADO: Una sola subconsulta para todos los cálculos anuales
                    DB::raw('(SELECT COALESCE(SUM(p2.monto), 0)
                                    FROM pago p2
                                    INNER JOIN habilitado h2 ON p2.id_habilitado = h2.id_habilitado
                                    INNER JOIN gestion g2 ON h2.id_gestion = g2.id_gestion
                                    WHERE YEAR(g2.gestion) = YEAR(gestion.gestion)
                                    ) as total_pagado_anual'),

                    // ✅ REUTILIZAR: Usar el cálculo anterior
                    DB::raw('(SELECT presupuesto_anual
                            FROM gestion g2
                            WHERE YEAR(g2.gestion) = YEAR(gestion.gestion)
                                    AND g2.presupuesto_anual IS NOT NULL
                            LIMIT 1) as presupuesto_anual'),

                    // ✅ CALCULADO: Restar en la aplicación o usar expresión simple
                    DB::raw('(SELECT COALESCE(g2.presupuesto_anual, 0) - COALESCE(pagos.total, 0)
                            FROM gestion g2
                            LEFT JOIN (
                                SELECT YEAR(g3.gestion) as year, SUM(p2.monto) as total
                                FROM pago p2
                                INNER JOIN habilitado h2 ON p2.id_habilitado = h2.id_habilitado
                                INNER JOIN gestion g3 ON h2.id_gestion = g3.id_gestion
                                GROUP BY YEAR(g3.gestion)
                            ) pagos ON YEAR(g2.gestion) = pagos.year
                            WHERE YEAR(g2.gestion) = YEAR(gestion.gestion)
                                    AND g2.presupuesto_anual IS NOT NULL
                            LIMIT 1) as presupuesto_anual_restante'),
                ])

                // ✅ MEJORADO: Usar JOIN más eficiente que CROSS JOIN
                ->from('gestion')
                ->join('persona', function ($join) {
                    // Solo personas relacionadas con gestiones
                    $join->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('habilitado')
                            ->whereColumn('id_persona', 'persona.id_persona');
                    });
                })

                // ✅ NUEVO: JOIN con historial_estados para obtener el último registro por persona
                /* ->join('historial_estados', function ($join) {
                    $join->on('historial_estados.id_persona', '=', 'persona.id_persona')
                        ->where('historial_estados.id', '=', function ($query) {
                            $query->select(DB::raw('MAX(he2.id)'))
                                ->from('historial_estados as he2')
                                ->whereColumn('he2.id_persona', 'historial_estados.id_persona');
                        });
                }) */

                ->leftJoin('habilitado', function ($join) {
                    $join->on('gestion.id_gestion', '=', 'habilitado.id_gestion')
                        ->on('habilitado.id_persona', '=', 'persona.id_persona');
                })
                ->leftJoin('mes', 'mes.id_gestion', '=', 'gestion.id_gestion')
                ->leftJoin('carnet', 'carnet.id_persona', '=', 'persona.id_persona')
                ->leftJoin('pago', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')

                ->groupBy('gestion.id_gestion', 'gestion.gestion', 'gestion.presupuesto_anual', 'mes.monto')
                ->orderBy('gestion.gestion', 'asc');

            // Aplicar filtro
            if (!empty($year)) {
                $query->whereYear('gestion.gestion', $year);
            }

            $resultados = $query->orderBy('gestion.id_gestion', 'asc')
                ->paginate(15)
                ->appends($request->query());

            // Agregar esto después de la consulta para ver el SQL generado
            /* dd($query->toSql(), $query->getBindings()); */
        }

        return Inertia::render('Gestion/reporteGestion', [
            'resultados' => $resultados,
            'gestiones' => $gestiones
        ]);
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $gestion = Gestion::findOrFail($id);

        $oldData        = $gestion->getOriginal();
        $fieldsToUpdate = $request->all();

        $mapaLabels = [
            'gestion'           => 'gestión',
            'presupuesto_anual' => 'presupuesto anual',
        ];

        $camposModificados = [];
        $valoresAnteriores = [];
        $valoresNuevos     = [];

        foreach ($fieldsToUpdate as $campo => $nuevoValor) {
            if (!array_key_exists($campo, $mapaLabels)) continue;

            $valorAnterior = $oldData[$campo] ?? null;
            $label         = $mapaLabels[$campo];

            if ($valorAnterior != $nuevoValor) {
                $camposModificados[$label] = $nuevoValor;
                $valoresAnteriores[$label] = $valorAnterior;
                $valoresNuevos[$label]     = $nuevoValor;
            }
        }

        if (empty($camposModificados)) {
            return;
        }

        $gestion->update($fieldsToUpdate);

        $this->logService->logUpdate(
            'Gestion',
            $gestion,
            [
                'campos_modificados' => $camposModificados,
                'valores_anteriores' => $valoresAnteriores,
                'valores_nuevos'     => $valoresNuevos,
            ],
            "Se actualizó el registro de la gestión {$gestion->gestion} en el sistema."
        );
    }

    public function updateMes(Request $request, string $id)
    {
        $mes = Mes::findOrFail($id);

        $oldData        = $mes->getOriginal();
        $fieldsToUpdate = $request->only(['monto', 'presupuesto']);

        $mapaLabels = [
            'monto'       => 'monto Bs.',
            'presupuesto' => 'presupuesto Bs.',
        ];

        $camposModificados = [];
        $valoresAnteriores = [];
        $valoresNuevos     = [];

        foreach ($fieldsToUpdate as $campo => $nuevoValor) {
            if (!array_key_exists($campo, $mapaLabels)) continue;

            $valorAnterior = $oldData[$campo] ?? null;
            $label         = $mapaLabels[$campo];

            if ($valorAnterior != $nuevoValor) {
                $camposModificados[$label] = $nuevoValor;
                $valoresAnteriores[$label] = $valorAnterior;
                $valoresNuevos[$label]     = $nuevoValor;
            }
        }

        if (empty($camposModificados)) {
            return;
        }

        $mes->update($fieldsToUpdate);

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
            12 => 'Diciembre'
        ];

        $nombreMes = $meses[$mes->mes] ?? 'Desconocido';

        $this->logService->logUpdate(
            'Mes',
            $mes,
            [
                'campos_modificados' => $camposModificados,
                'valores_anteriores' => $valoresAnteriores,
                'valores_nuevos'     => $valoresNuevos,
            ],
            "Se actualizó el registro del mes {$nombreMes} en el sistema."
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //

    }
}
