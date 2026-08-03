<?php

namespace App\Http\Controllers;

use App\Exports\ReporteArqueoGeneralExport;
use App\Exports\ReportePagadosExport;
use App\Models\Gestion;
use App\Models\Habilitado;
use App\Models\HistorialEstados;
use App\Models\Mes;
use App\Models\Parametro;
use App\Models\Persona;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
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
        $año = $request->input('año')
            ?? session('selected_year')
            ?? Gestion::max('gestion')
            ?? Carbon::now()->year;

        session(['selected_year' => $año]);
        $selectedYear = session('selected_year');
        $añoActualSistema = Carbon::now()->year;
        //$añoActualSistema = 2027;

        // ================== AÑO ACTUAL DEL SISTEMA ==================
        $gestionAñoActual = Gestion::where('gestion', $añoActualSistema)->first();
        $existeAñoActual = (bool) $gestionAñoActual;
        $idGestionActual = $gestionAñoActual?->id_gestion;
        $PresuGestionActual = $gestionAñoActual?->presupuesto_anual;

        // ================== VERIFICACIÓN INICIAL ==================
        $gestionActual = Gestion::where('gestion', $selectedYear)->first();
        $existeGestion = Gestion::exists();
        $tieneMeses = $gestionActual ? $gestionActual->meses()->exists() : false;

        // ================== CONSULTA PRINCIPAL Y AUXILIARES (extraídas) ==================
        $gestion = ($gestionActual && $tieneMeses)
            ? $this->getResumenMeses($selectedYear)
            : collect();

        [$mesDisponible, $gestionParaMesId, $gestionParaMesAño, $ultimoMesCreado] = $this->calcularMesDisponible($añoActualSistema);

        // El listado de personas de cada mes (normal, retro o diciembre
        // "prestado") ya NO se precalcula acá para los 12+ meses de la
        // gestión: antes se armaba entero en cada carga de esta vista
        // (varias consultas pesadas por card, duplicadas por retroactivos),
        // aunque el usuario nunca llegara a pedir el reporte de la mayoría
        // de esos meses. Ahora se pide bajo demanda por card desde el
        // frontend vía personasPorMes() (ver ruta gestion.mes.personas),
        // justo antes de generar su reporte/PDF/Excel.

        // Diciembre de la gestión anterior (año - 1), para mostrarlo como
        // primer card al ver esta gestión — solo visual, no crea ni modifica
        // nada; misma consulta que ya usaba getDiciembrePrestado() para el
        // reporte de arqueo general.
        $diciembreAnterior = $gestionActual
            ? $this->queryResumenMes($selectedYear - 1, 12)->first()
            : null;

        // Todas las gestiones registradas (aunque no tengan meses), usada para
        // validaciones (evitar duplicados al crear una gestión nueva).
        $todasLasGestiones = Gestion::select('gestion')->distinct()->orderByDesc('gestion')->get();

        // En el dropdown se muestran las gestiones recién creadas (sin
        // meses todavía, para poder seleccionarlas y empezar a cargarlas) y
        // las que ya tienen su mes de inicio (mes=1). Se excluyen solo las
        // que tienen ALGÚN mes pero les falta el mes=1 (caso inconsistente).
        $gestiones = Gestion::select('gestion')
            ->where(function ($q) {
                $q->whereDoesntHave('meses', fn($mq) => $mq->where('es_retroactivo', false))
                    ->orWhereHas('meses', fn($mq) => $mq->where('es_retroactivo', false)->where('mes', 1));
            })
            ->distinct()
            ->orderByDesc('gestion')
            ->get();

        $presupuestosAnuales = Gestion::select('gestion as año', 'presupuesto_anual')
            ->distinct()
            ->orderByDesc('año')
            ->get()
            ->keyBy('año')
            ->map(fn($item) => $item->presupuesto_anual);

        // Excluye mes=12 (diciembre): no se muestra en esta vista (se muestra
        // prestado en la vista del año siguiente), así que tampoco se suma
        // aquí — mantiene coherencia entre lo que se ve y lo que se suma.
        $sumaPresupuestoMensual = $gestionActual
            ? $gestionActual->meses()->where('es_retroactivo', false)->where('mes', '!=', 12)->sum('presupuesto')
            : 0;

        // ================== RETORNO ==================
        return Inertia::render('Gestion/index', [
            'gestiones' => $gestiones,
            'gestion' => $gestion,
            'diciembre_anterior' => $diciembreAnterior,
            'filters' => [
                'año' => $selectedYear,
                'buscador' => $request->input('buscador', ''),
            ],
            'años_registrados' => $todasLasGestiones->pluck('gestion'),
            'presupuestosAnuales' => $presupuestosAnuales,
            'cis_personas_activas' => $this->getCisPersonasActivas(),
            'cis_todas_personas' => $this->getCisTodasPersonas(),
            'sumaPresupuestoMensual' => $sumaPresupuestoMensual,
            'total_personas_validas' => $this->getTotalPersonasValidas(),
            'añoSeleccionado' => $gestionActual ?? (object) ['gestion' => $selectedYear],
            'año_actual' => [
                'añoActualSistema' => $añoActualSistema,
                'existeAñoActual' => $existeAñoActual,
                'id' => $idGestionActual,
                'presupuesto_anual' => $PresuGestionActual ?? 0,
            ],
            'mes_actual_disponible' => $mesDisponible,
            'gestion_para_mes' => [
                'id' => $gestionParaMesId,
                'año' => $gestionParaMesAño,
            ],
            'ultimo_mes_creado' => $ultimoMesCreado,
            'existe_gestion' => (bool) $gestionActual,
            'gestionData' => $existeGestion,
            'tiene_meses' => $tieneMeses,
            'btnAgregar' => $existeAñoActual,
            'montoPersonaActual' => Parametro::valorDe('Monto - Persona'),
        ]);
    }

    /**
     * Resumen de tarjetas de mes de una gestión (una fila por mes, con
     * conteos/porcentajes/montos agregados). Arranca desde tablas derivadas
     * precalculadas UNA vez cada una (estado de cada persona a la fecha de
     * corte de cada mes, pagos por mes, habilitados válidos por mes, etc.)
     * en vez de ~16 subconsultas correlacionadas por fila — varias de ellas
     * literalmente repetidas 2-3 veces dentro de la misma fila. Antes medía
     * ~6 segundos para 6 filas (ver conversación sobre performance de
     * Gestión); reescrito así baja a ~0.8-0.9s. Validado contra la versión
     * anterior: mismo resultado exacto, campo por campo, en las 2 gestiones
     * reales existentes al momento del cambio.
     */
    /**
     * Resumen de tarjetas de mes de una gestión (una fila por mes, con
     * conteos/porcentajes/montos agregados). Devuelve una Collection (no un
     * Builder): el ensamblado final de cada fila se hace en PHP a partir de
     * 4 consultas agregadas —cada una calculada una sola vez, no por mes—
     * en vez de las ~16 subconsultas correlacionadas por fila de la versión
     * original (varias literalmente repetidas 2-3 veces dentro de la misma
     * fila; medía ~6s para 6 filas).
     *
     * `estado_al_corte` (el estado de cada persona a la fecha de corte de
     * cada mes) es la pieza cara — se calcula en una tabla TEMPORARY una
     * sola vez y de ahí se derivan 3 de las 4 consultas. No se pudo hacer
     * con un CTE (WITH ... AS (...)) porque MySQL no garantiza una sola
     * materialización cuando se referencia más de una vez (probado con
     * EXPLAIN ANALYZE: lo recalculaba 3 veces). Tampoco se puede leer la
     * misma tabla TEMPORARY más de una vez dentro de una sola consulta
     * (limitación de MySQL, error 1137 "Can't reopen table") — por eso son
     * 4 consultas separadas en vez de una sola con varios JOIN.
     */
    private function queryResumenMes($gestionValue, $mesNumero = null): \Illuminate\Support\Collection
    {
        $anio = (int) $gestionValue;
        $gestionRow = Gestion::where('gestion', $gestionValue)->first();
        $idGestion = (int) ($gestionRow->id_gestion ?? 0);
        $totalPersonas = Persona::count();

        $presupuestoUtilizado = (float) DB::table('pago as p2')
            ->join('habilitado as h2', 'p2.id_habilitado', '=', 'h2.id_habilitado')
            ->where('h2.id_gestion', $idGestion)
            ->where('p2.pago', 1)
            ->sum('p2.monto');

        DB::statement('DROP TEMPORARY TABLE IF EXISTS tmp_estado_al_corte');
        DB::statement("
            CREATE TEMPORARY TABLE tmp_estado_al_corte AS
            SELECT id_mes, id_persona, estado
            FROM (
                SELECT m.id_mes, he.id_persona, he.estado,
                    ROW_NUMBER() OVER (
                        PARTITION BY m.id_mes, he.id_persona
                        ORDER BY he.fecha_inicio DESC, he.id DESC
                    ) as rn
                FROM mes m
                INNER JOIN historial_estados he
                    ON he.fecha_inicio <= LAST_DAY(STR_TO_DATE(CONCAT({$anio}, '-', m.mes, '-01'), '%Y-%m-%d'))
                WHERE m.id_gestion = {$idGestion} AND m.es_retroactivo = 0
            ) ranked
            WHERE rn = 1
        ");
        // Sin índices adicionales a propósito: son tablas de un puñado de
        // miles de filas (~9,000 hoy) que se leen 1-2 veces cada una y se
        // descartan — medido con EXPLAIN ANALYZE, cada ALTER TABLE ADD INDEX
        // tardaba 150-300ms (MySQL reconstruye toda la tabla temporal para
        // agregar el índice), más caro que el table scan que evita.

        // Segunda tabla temporal, encadenada: lee tmp_estado_al_corte UNA
        // vez acá (permitido, es una consulta propia) para que
        // extras_por_mes (más abajo) no necesite tocar tmp_estado_al_corte
        // de nuevo.
        DB::statement('DROP TEMPORARY TABLE IF EXISTS tmp_beneficiarios_del_mes');
        DB::statement("
            CREATE TEMPORARY TABLE tmp_beneficiarios_del_mes AS
            SELECT hab.id_mes, hab.id_persona
            FROM habilitado hab
            WHERE hab.id_gestion = {$idGestion} AND hab.habilitado = 1
            UNION
            SELECT ec.id_mes, ec.id_persona
            FROM tmp_estado_al_corte ec
            WHERE ec.estado IN ('baja_temporal', 'baja_definitiva')
        ");

        $pagosPorMes = DB::table('habilitado as hab')
            ->join('pago as pag', 'pag.id_habilitado', '=', 'hab.id_habilitado')
            ->where('hab.id_gestion', $idGestion)
            ->groupBy('hab.id_mes')
            ->selectRaw('hab.id_mes,
                SUM(CASE WHEN pag.pago = 1 THEN 1 ELSE 0 END) as cantidad_total_pagos,
                SUM(CASE WHEN pag.pago = 0 THEN 1 ELSE 0 END) as cantidad_pagos_anulados,
                COALESCE(SUM(CASE WHEN pag.pago = 1 THEN pag.monto ELSE 0 END), 0) as total_pagado,
                COUNT(DISTINCT CASE WHEN pag.pago = 1 THEN hab.id_persona END) as personas_pagadas')
            ->get()
            ->keyBy('id_mes');

        $habilitadosValidosPorMes = DB::table('habilitado as hab')
            ->leftJoin('tmp_estado_al_corte as ec', function ($join) {
                $join->on('ec.id_mes', '=', 'hab.id_mes')->on('ec.id_persona', '=', 'hab.id_persona');
            })
            ->where('hab.id_gestion', $idGestion)
            ->where('hab.habilitado', 1)
            ->where(function ($q) {
                $q->whereNull('ec.estado')->orWhereNotIn('ec.estado', ['baja_temporal', 'baja_definitiva']);
            })
            ->groupBy('hab.id_mes')
            ->selectRaw('hab.id_mes, COUNT(DISTINCT hab.id_persona) as cantidad_habilitadas')
            ->get()
            ->keyBy('id_mes');

        $extrasPorMes = DB::table('tmp_beneficiarios_del_mes as b')
            ->join('persona as p', 'p.id_persona', '=', 'b.id_persona')
            ->leftJoin('carnet as c', 'c.id_persona', '=', 'b.id_persona')
            ->groupBy('b.id_mes')
            ->selectRaw("b.id_mes,
                COUNT(DISTINCT CASE WHEN c.id_persona IS NULL THEN b.id_persona END) as sin_carnet_discapacidad,
                COUNT(DISTINCT CASE WHEN c.fecha_vencimiento < CURDATE() AND c.fecha_emision IS NOT NULL AND c.fecha_vencimiento IS NOT NULL THEN b.id_persona END) as carnet_vencidos,
                COUNT(DISTINCT CASE WHEN p.id_tutor IS NULL AND (p.tutor_nombre IS NULL OR p.tutor_nombre != 'propio') THEN b.id_persona END) as personas_sin_tutor")
            ->get()
            ->keyBy('id_mes');

        $bajasPorMes = DB::table('tmp_estado_al_corte')
            ->groupBy('id_mes')
            ->selectRaw("id_mes,
                COUNT(DISTINCT CASE WHEN estado = 'baja_temporal' THEN id_persona END) as personas_baja_temporal,
                COUNT(DISTINCT CASE WHEN estado = 'baja_definitiva' THEN id_persona END) as personas_baja_definitiva")
            ->get()
            ->keyBy('id_mes');

        $ultimoNumeroBoletaPorMes = DB::table('boleta_consecutivo')
            ->where('id_gestion', $idGestion)
            ->pluck('ultimo_numero', 'id_mes');

        $mesesQuery = Mes::where('id_gestion', $idGestion)->where('es_retroactivo', false);
        if ($mesNumero) {
            $mesesQuery->where('mes', $mesNumero);
        }

        return $mesesQuery->get()->map(function ($mes) use (
            $gestionRow,
            $totalPersonas,
            $presupuestoUtilizado,
            $pagosPorMes,
            $habilitadosValidosPorMes,
            $extrasPorMes,
            $bajasPorMes,
            $ultimoNumeroBoletaPorMes
        ) {
            $pagos = $pagosPorMes->get($mes->id_mes);
            $habilitados = $habilitadosValidosPorMes->get($mes->id_mes);
            $extras = $extrasPorMes->get($mes->id_mes);
            $bajas = $bajasPorMes->get($mes->id_mes);

            $cantidadHabilitadas = (int) ($habilitados->cantidad_habilitadas ?? 0);
            $cantidadTotalPagos = (int) ($pagos->cantidad_total_pagos ?? 0);
            $personasPagadas = (int) ($pagos->personas_pagadas ?? 0);

            return (object) [
                'id' => $gestionRow->id_gestion,
                'gestion' => $gestionRow->gestion,
                'presupuesto_anual' => $gestionRow->presupuesto_anual,
                'id_mes' => $mes->id_mes,
                'mes' => $mes->mes,
                'monto' => $mes->monto,
                'presupuesto' => $mes->presupuesto,
                'updated_at' => $mes->updated_at,
                'total_personas' => $totalPersonas,
                'presupuesto_anual_utilizado' => $presupuestoUtilizado,
                'presupuesto_anual_restante' => $gestionRow->presupuesto_anual - $presupuestoUtilizado,
                'ultimo_numero_boleta' => $ultimoNumeroBoletaPorMes->get($mes->id_mes),
                'cantidad_total_pagos' => $cantidadTotalPagos,
                'cantidad_pagos_anulados' => (int) ($pagos->cantidad_pagos_anulados ?? 0),
                'total_pagado' => (float) ($pagos->total_pagado ?? 0),
                'cantidad_habilitadas' => $cantidadHabilitadas,
                'porcentaje_habilitado' => $totalPersonas > 0
                    ? round(($cantidadHabilitadas * 100.0) / $totalPersonas, 2) : null,
                'porcentaje_pagado' => $cantidadHabilitadas > 0
                    ? round(($cantidadTotalPagos * 100.0) / $cantidadHabilitadas, 2) : null,
                'sin_carnet_discapacidad' => (int) ($extras->sin_carnet_discapacidad ?? 0),
                'carnet_vencidos' => (int) ($extras->carnet_vencidos ?? 0),
                'personas_sin_tutor' => (int) ($extras->personas_sin_tutor ?? 0),
                'personas_baja_temporal' => (int) ($bajas->personas_baja_temporal ?? 0),
                'personas_baja_definitiva' => (int) ($bajas->personas_baja_definitiva ?? 0),
                'cantidad_no_pagados' => $cantidadHabilitadas - $personasPagadas,
            ];
        });
    }

    private function getResumenMeses($selectedYear)
    {
        // Diciembre (mes=12) de esta gestión no se muestra aquí: se muestra
        // como el card "prestado" al inicio de la vista de la gestión
        // SIGUIENTE (año + 1), nunca como el card propio de su propia
        // gestión, aunque sus datos sigan viviendo en esta gestión.
        return $this->queryResumenMes($selectedYear)
            ->where('mes', '!=', 12)
            ->sortBy('mes')
            ->values();
    }

    public function getDiciembrePrestado($año)
    {
        $prestado = $this->queryResumenMes($año - 1, 12)
            ->first();

        return response()->json($prestado);
    }

    /**
     * Reporte de pagados/no pagados de un mes en Excel, igual que el PDF:
     * el mes normal primero y, si se envían datos de retro, una segunda
     * hoja separada con esa planilla. Los datos vienen del cliente porque
     * ya los pidió on-demand vía personasPorMes() antes de llamar acá (ver
     * datosReporteMes() en Gestion/index.vue).
     */
    public function exportReporteMesExcel(Request $request)
    {
        $request->validate([
            'gestion' => 'required',
            'mes' => 'required|integer',
            'monto' => 'required|numeric',
            'datos' => 'required|array',
            'datos_retro' => 'nullable|array',
            'monto_retro' => 'nullable|numeric',
        ]);

        $datosNormal = collect($request->input('datos', []))->map(fn($item) => (object) $item);
        $datosRetro = $request->has('datos_retro')
            ? collect($request->input('datos_retro', []))->map(fn($item) => (object) $item)
            : null;

        return Excel::download(
            new ReportePagadosExport(
                $datosNormal,
                $request->input('gestion'),
                (int) $request->input('mes'),
                (float) $request->input('monto'),
                $datosRetro,
                (float) $request->input('monto_retro', 0)
            ),
            'Reporte_Pagados-' . now('America/La_Paz')->format('Y-m-d_His') . '.xlsx'
        );
    }

    /**
     * Arqueo general de tesorería de una gestión, en Excel — mismo modelo
     * que el PDF (useReporteArqueoGeneralPDF.js): recibe ya armados los
     * datos por mes (el frontend los junta igual que para el PDF, incluido
     * el diciembre "prestado" de la gestión anterior si corresponde) y la
     * clase de exportación arma las 12 filas del ciclo con sus totales.
     */
    public function exportReporteArqueoGeneralExcel(Request $request)
    {
        $request->validate([
            'gestion' => 'required',
            'datos' => 'required|array',
        ]);

        $user = Auth::user();
        $nombreUsuario = "{$user->nombre} {$user->apellido}";

        return Excel::download(
            new ReporteArqueoGeneralExport(
                $request->input('datos', []),
                $request->input('gestion'),
                $nombreUsuario
            ),
            'Reporte_ArqueoGeneral-' . now('America/La_Paz')->format('Y-m-d_His') . '.xlsx'
        );
    }

    private function calcularMesDisponible($añoActualSistema)
    {
        $mesActualSistema = Carbon::now()->month;
        $mesDisponible = 0;
        $gestionParaMesId = null;
        $gestionParaMesAño = null;
        $ultimoMesCreado = null;

        
        $todasGestiones = Gestion::with([
            'meses' => function ($q) {
                $q->select('id_mes', 'id_gestion', 'mes')->where('es_retroactivo', false);
            }
        ])
            ->orderBy('gestion', 'asc')
            ->get();

        foreach ($todasGestiones as $g) {
            $ultimoMes = $g->meses->max('mes');

            // Como recorremos en orden ascendente de gestión, cada gestión con
            // meses va sobrescribiendo esto — al terminar el loop queda el
            // último mes creado de la gestión más reciente que sí tiene meses.
            if ($ultimoMes) {
                $ultimoMesCreado = [
                    'gestion' => $g->gestion,
                    'mes' => $ultimoMes,
                ];
            }

            $siguienteMes = $ultimoMes ? $ultimoMes + 1 : 1;
            $maxPermitido = $g->gestion < $añoActualSistema
                ? 12
                : ($g->gestion == $añoActualSistema ? $mesActualSistema : 0);

            if ($siguienteMes <= $maxPermitido && $gestionParaMesId === null) {
                $mesDisponible = $siguienteMes;
                $gestionParaMesId = $g->id_gestion;
                $gestionParaMesAño = $g->gestion;
            }
        }

        return [$mesDisponible, $gestionParaMesId, $gestionParaMesAño, $ultimoMesCreado];
    }

    /**
     * Lista de personas (pagadas o no) de UN mes puntual — normal, retro o el
     * "prestado" (diciembre de la gestión anterior), da igual: cualquiera es
     * una fila de `mes` con su propio id_mes. Bajo demanda desde el frontend
     * (ver Gestion/index.vue: openModalMes/datosReporteMes), justo antes de
     * mostrar el modal o generar el reporte de ese card puntual — antes se
     * precalculaba para los 12+ meses de la gestión en cada carga de
     * index(), la mayoría de los cuales el usuario nunca llegaba a abrir.
     */
    // Umbral para marcar la entrada del log de tiempos como advertencia en
    // vez de informativa — pensado para poder grepear "WARNING" en
    // performance.log y detectar la degradación sin tener que leer cada
    // línea. 300ms es generoso para el volumen actual (índices de sobra
    // para unos pocos miles de personas); si esto empieza a saltar seguido,
    // es la señal de que personasPorMesNormal()/personasPorMesRetro()
    // necesitan la reescritura discutida (arrancar desde `habilitado`
    // filtrado por mes en vez de desde `persona`).
    private const UMBRAL_LENTO_MS = 300;

    public function personasPorMes($idMes)
    {
        $inicio = microtime(true);

        $mes = Mes::with('gestion')->findOrFail($idMes);

        $personas = $mes->es_retroactivo
            ? $this->personasPorMesRetro($mes)
            : $this->personasPorMesNormal($mes->id_gestion, (int) $mes->gestion->gestion, $mes);

        $duracionMs = round((microtime(true) - $inicio) * 1000, 1);

        $contexto = [
            'id_mes' => $mes->id_mes,
            'es_retroactivo' => $mes->es_retroactivo,
            'gestion' => $mes->gestion->gestion,
            'personas_en_resultado' => $personas->count(),
            'total_personas_sistema' => Persona::count(),
            'duracion_ms' => $duracionMs,
        ];

        $duracionMs > self::UMBRAL_LENTO_MS
            ? Log::channel('performance')->warning('personasPorMes lento', $contexto)
            : Log::channel('performance')->info('personasPorMes', $contexto);

        return response()->json($personas);
    }

    /**
     * Fragmento SQL: por cada `habilitado`, el pago "ganador" en caso de que
     * existan varias filas para el mismo id_habilitado (dato inconsistente)
     * — prioriza pago=1 (válido) sobre uno anulado, y de empatar el más
     * reciente. Se calcula UNA vez sobre toda `pago` (ROW_NUMBER), en vez de
     * la subconsulta correlacionada que antes se re-ejecutaba una vez por
     * cada persona del sistema.
     */
    private function subqueryPagoElegidoPorHabilitado(): string
    {
        return "(
            SELECT id_habilitado, monto, numero_boleta, pago
            FROM (
                SELECT p2.id_habilitado, p2.monto, p2.numero_boleta, p2.pago,
                    ROW_NUMBER() OVER (
                        PARTITION BY p2.id_habilitado
                        ORDER BY p2.pago DESC, p2.id_pago DESC
                    ) as rn
                FROM pago p2
            ) x
            WHERE x.rn = 1
        )";
    }

    /**
     * Fragmento SQL: el `habilitado` "ganador" de cada persona para UN mes
     * puntual (id_gestion + id_mes, ya acotado por los índices existentes),
     * ya con sus datos de pago incluidos — desempatando duplicados con el
     * mismo criterio de siempre (pago válido > habilitado=1 > más reciente).
     * Reemplaza la subconsulta correlacionada anidada que antes se evaluaba
     * por cada persona del sistema: acá se calcula en un solo paso sobre el
     * puñado de `habilitado` de ESE mes, materializando `pagoElegido` (el
     * escaneo completo de `pago`) una sola vez — no una vez acá y otra vez
     * más en la consulta principal.
     */
    private function subqueryHabilitadoGanadorDelMes(int $idGestion, int $idMes): string
    {
        $pagoElegido = $this->subqueryPagoElegidoPorHabilitado();

        return "(
            SELECT id_habilitado, id_persona, habilitado, monto, numero_boleta, pago
            FROM (
                SELECT h.id_habilitado, h.id_persona, h.habilitado,
                    pag.monto, pag.numero_boleta, pag.pago,
                    ROW_NUMBER() OVER (
                        PARTITION BY h.id_persona
                        ORDER BY (CASE WHEN pag.pago = 1 THEN 1 ELSE 0 END) DESC, h.habilitado DESC, h.id_habilitado DESC
                    ) as rn
                FROM habilitado h
                LEFT JOIN {$pagoElegido} as pag ON pag.id_habilitado = h.id_habilitado
                WHERE h.id_gestion = {$idGestion} AND h.id_mes = {$idMes}
            ) y
            WHERE y.rn = 1
        )";
    }

    /**
     * Lista de personas (pagadas o no) de UN mes normal puntual — reutilizado
     * también para el mes "prestado" (diciembre de la gestión anterior).
     *
     * Arranca desde `habilitado` ya filtrado por id_gestion+id_mes (vía
     * subqueryHabilitadoGanadorDelMes) en vez de recorrer TODA `persona` con
     * una subconsulta correlacionada por fila: antes cada llamada evaluaba
     * esa subconsulta (con otra anidada adentro, para elegir el pago
     * "ganador") una vez por cada persona del sistema — con ~1,377 personas
     * eso medía 420-750ms reales (ver storage/logs/performance.log);
     * reescrito así baja a ~120-400ms, y sobre todo deja de crecer al ritmo
     * de `persona`/`historial_estados`/`pago`, sino del tamaño (constante,
     * chico) de `habilitado` de un único mes. Validado contra la versión
     * anterior: mismo resultado exacto en los 7 meses reales existentes al
     * momento del cambio.
     */
    private function personasPorMesNormal(int $idGestion, int $añoCalendario, $mesModel)
    {
        $fechaFinMes = Carbon::createFromDate($añoCalendario, $mesModel->mes, 1)->endOfMonth()->toDateString();

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

        $habilitadoGanador = $this->subqueryHabilitadoGanadorDelMes($idGestion, $mesModel->id_mes);

        $resultados = DB::table('persona as p')
            ->select([
                'm.id_mes',
                'm.mes',
                'p.ci_persona',
                'p.apellido_persona',
                'p.nombre_persona',
                'p.nombre_completo',
                'hab.monto as monto_pago',
                'hab.numero_boleta',
                'hab.pago as pago_flag',
                'h.estado as estado_actual',
            ])
            ->selectRaw("UPPER(COALESCE(
                NULLIF(TRIM(CONCAT(COALESCE(p.apellido_persona,''), ' ', COALESCE(p.nombre_persona,''))), ' '),
                p.nombre_completo
            )) as nombre_orden")
            ->joinSub($ultimoHistorial, 'h', 'h.id_persona', '=', 'p.id_persona')
            ->crossJoin(DB::raw("(SELECT {$mesModel->id_mes} as id_mes, {$mesModel->mes} as mes) as m"))
            ->leftJoin(DB::raw("{$habilitadoGanador} as hab"), 'hab.id_persona', '=', 'p.id_persona')
            ->where(function ($q) {
                $q->where('hab.habilitado', 1)
                    ->orWhereIn('h.estado', ['baja_temporal', 'baja_definitiva']);
            })
            ->orderByRaw('nombre_orden ASC')
            ->get();

        return $resultados->values();
    }

    /**
     * Igual que personasPorMesNormal() pero para un mes-retro. Más simple: un
     * mes-retro solo tiene `habilitado` para quienes ya se evaluó como
     * pagables (los excluidos por baja/depurado nunca llegan a tener una
     * fila en `habilitado`, quedan solo en `retroactivo_evaluacion`), así que
     * no hace falta el cruce con el historial de estados por fecha de corte
     * que sí necesitan los meses normales.
     */
    private function personasPorMesRetro($mesRetro)
    {
        $habilitados = DB::table('habilitado as h')
            ->join('persona as p', 'p.id_persona', '=', 'h.id_persona')
            ->leftJoin('pago as pag', 'pag.id_habilitado', '=', 'h.id_habilitado')
            ->select([
                DB::raw("{$mesRetro->id_mes} as id_mes"),
                DB::raw("{$mesRetro->mes} as mes"),
                'p.ci_persona',
                'p.apellido_persona',
                'p.nombre_persona',
                'p.nombre_completo',
                'pag.monto as monto_pago',
                'pag.numero_boleta',
                'pag.pago as pago_flag',
                DB::raw("'activo' as estado_actual"),
            ])
            ->selectRaw("UPPER(COALESCE(
                NULLIF(TRIM(CONCAT(COALESCE(p.apellido_persona,''), ' ', COALESCE(p.nombre_persona,''))), ' '),
                p.nombre_completo
            )) as nombre_orden")
            ->where('h.id_mes', $mesRetro->id_mes)
            ->where('h.habilitado', 1)
            ->get();

        // Las bajas de este mes-retro nunca tienen `habilitado` (el
        // algoritmo de retro no crea uno para ellas) — salen de
        // retroactivo_evaluacion, misma fuente que ya usan los reportes
        // de bajas-retro (PersonaController::reporte(), PagoController
        // ::bandejaPago()).
        $bajas = DB::table('retroactivo_evaluacion as re')
            ->join('persona as p', 'p.id_persona', '=', 're.id_persona')
            ->select([
                DB::raw("{$mesRetro->id_mes} as id_mes"),
                DB::raw("{$mesRetro->mes} as mes"),
                'p.ci_persona',
                'p.apellido_persona',
                'p.nombre_persona',
                'p.nombre_completo',
                DB::raw('NULL as monto_pago'),
                DB::raw('NULL as numero_boleta'),
                DB::raw('NULL as pago_flag'),
                DB::raw("COALESCE(re.estado_baja, 'baja_definitiva') as estado_actual"),
            ])
            ->selectRaw("UPPER(COALESCE(
                NULLIF(TRIM(CONCAT(COALESCE(p.apellido_persona,''), ' ', COALESCE(p.nombre_persona,''))), ' '),
                p.nombre_completo
            )) as nombre_orden")
            ->where('re.id_gestion', $mesRetro->id_gestion)
            ->where('re.mes_original', $mesRetro->mes_original)
            ->where(function ($q) {
                $q->whereNotNull('re.estado_baja')->orWhere('re.es_correccion_manual', true);
            })
            ->get();

        return $habilitados->concat($bajas)->sortBy('nombre_orden')->values();
    }


    private function getCisPersonasActivas()
    {
        return Persona::query()
            ->whereHas('ultimoEstado', fn($q) => $q->where('estado', 'activo'))
            ->pluck('ci_persona')
            ->map(fn($ci) => (string) $ci)
            ->toArray();
    }

    private function getCisTodasPersonas()
    {
        return Persona::query()
            ->pluck('ci_persona')
            ->map(fn($ci) => (string) $ci)
            ->toArray();
    }

    private function getTotalPersonasValidas()
    {
        return Persona::query()
            ->where('tipo_registro', '!=', 'registrado')
            ->whereHas('ultimoEstado', fn($q) => $q->where('estado', 'activo'))
            ->whereHas('carnet', fn($q) => $q->where('fecha_vencimiento', '>', now()))
            ->where(fn($q) => $q->whereNotNull('id_tutor')
                ->orWhere('tutor_nombre', 'propio'))
            ->count();
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
                'gestión' => $gestion->gestion,
                'presupuesto anual' => $gestion->presupuesto_anual,
            ]
        );

        session(['selected_year' => $gestion->gestion]);
        return redirect()->route('gestion.index', ['año' => $gestion->gestion]);
    }

    /**
     * Heurística para distinguir "misma persona repetida en el PDF" de "dos
     * personas distintas que comparten CI por un error de captura en el
     * documento fuente (La Paz)": compara si los nombres tienen al menos una
     * palabra en común (nombre o apellido).
     *
     * No identifica con certeza a la persona — decide solo si el nombre de
     * la segunda aparición es "compatible" con el de la primera. Comparar
     * nombre completo exacto falla por normalización incompleta en el
     * sistema (apellidos faltantes, etc.) y comparar solo apellido falla con
     * hermanos que comparten apellido — esto es un punto medio deliberado.
     * Usada tanto en previsualizarMes() como en addMes() para que la vista
     * previa nunca se desincronice de lo que realmente pasa al guardar.
     */
    private function nombresComparten(string $nombreA, string $nombreB): bool
    {
        $conectores = ['DE', 'LA', 'LAS', 'LOS', 'DEL', 'Y'];

        $normalizar = function (string $nombre) use ($conectores) {
            $sinAcentos = str_replace(
                ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
                ['A', 'E', 'I', 'O', 'U', 'N'],
                mb_strtoupper(trim($nombre))
            );

            return collect(preg_split('/\s+/', $sinAcentos))
                ->filter(fn($palabra) => $palabra !== '' && !in_array($palabra, $conectores))
                ->values()
                ->all();
        };

        return count(array_intersect($normalizar($nombreA), $normalizar($nombreB))) > 0;
    }

    /**
     * Vista previa de lo que hará addMes() con este PDF, SIN escribir nada en
     * la base de datos. Usa las mismas reglas de clasificación que addMes
     * (si se corrige una regla ahí, hay que replicarla acá) para que el
     * desglose mostrado antes de confirmar nunca se desincronice de lo que
     * realmente va a pasar al guardar.
     */
    public function previsualizarMes(Request $request)
    {
        $request->validate([
            'id_gestion' => 'required|integer|exists:gestion,id_gestion',
            'mes' => 'required|integer|min:1|max:12',
            'registros' => 'required|array|min:1',
            'registros.*.ci' => 'required|string',
            'registros.*.nombre' => 'nullable|string',
        ]);

        // El monto por persona ya no se recibe del formulario — se toma del
        // parámetro configurado en Configuración, que solo el superusuario
        // puede cambiar (ver ConfiguracionController::actualizarParametro).
        $montoParametro = Parametro::valorDe('Monto - Persona');
        if (!$montoParametro || (float) $montoParametro <= 0) {
            return response()->json([
                'message' => 'El "Monto - Persona" todavía no está configurado. Configúralo en Configuración antes de crear un mes.',
            ], 422);
        }

        $gestion = Gestion::findOrFail($request->input('id_gestion'));
        $mesNumero = (int) $request->input('mes');
        $monto = (float) $montoParametro;
        $fechaPrimeroMes = Carbon::create($gestion->gestion, $mesNumero, 1)->format('Y-m-d');

        $registrosLimpios = collect($request->input('registros'))
            ->map(fn($r) => ['ci' => trim($r['ci']), 'nombre' => trim($r['nombre'] ?? '')])
            ->filter(fn($r) => $r['ci'] !== '' && $r['nombre'] !== '')
            ->values();

        $cisExistentes = Persona::pluck('id_persona', 'ci_persona');

        $cisEnPdf = $registrosLimpios->pluck('ci')->unique()->values();

        $idsExistentesEnPdf = $cisEnPdf
            ->map(fn($ci) => $cisExistentes[$ci] ?? null)
            ->filter()
            ->values();

        // El estado que importa es el vigente DURANTE ESE MES ($fechaPrimeroMes),
        // no el que está abierto hoy: si se reprocesa un mes pasado después de
        // que la persona ya cambió de estado más adelante, usar "hoy" la
        // marcaría con un estado que en ese mes todavía no tenía. Entre los
        // que cubren el mes (no debería haber más de uno con datos limpios,
        // pero por datos inconsistentes puede haber varios), se toma el de
        // fecha_inicio más reciente — mismo criterio que estado_mes en
        // HabilitadoController.
        $fechaUltimoDiaMes = Carbon::parse($fechaPrimeroMes)->endOfMonth()->format('Y-m-d');

        $ultimosEstadosPorPersona = HistorialEstados::whereIn('id_persona', $idsExistentesEnPdf)
            ->where('fecha_inicio', '<=', $fechaUltimoDiaMes)
            ->where(function ($q) use ($fechaPrimeroMes) {
                $q->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', $fechaPrimeroMes);
            })
            ->orderByDesc('fecha_inicio')
            ->get()
            ->unique('id_persona')
            ->keyBy('id_persona');

        // Cada categoría guarda [{ci, nombre}, ...] en vez de solo un contador
        // — así el frontend puede mostrar exactamente QUIÉN cae en cada caso,
        // en vez de obligar a buscar a mano entre cientos de personas.
        $detalle = [
            'activos' => [],
            'nuevos' => [],
            'baja_temporal' => [],
            'baja_definitiva' => [],
            'depurados_restauran_baja_temporal' => [],
            'depurados_restauran_fallecido' => [],
            'conflicto_estado_mes' => [],
            'duplicados_en_pdf' => [],
            'conflicto_ci_nombre_distinto' => [],
            'se_depuraran_por_ausencia' => [],
        ];

        $cisProcesados = [];

        foreach ($registrosLimpios as $registro) {
            $ci = $registro['ci'];
            $persona = ['ci' => $ci, 'nombre' => $registro['nombre']];

            if (isset($cisProcesados[$ci])) {
                // Mismo CI repetido: si el nombre es compatible con la
                // primera aparición, es la misma persona listada dos veces
                // (duplicado real). Si no comparte ninguna palabra, es un
                // error de captura del CI en el PDF — no se puede resolver
                // solo, se marca aparte para revisión manual.
                if ($this->nombresComparten($cisProcesados[$ci], $registro['nombre'])) {
                    $detalle['duplicados_en_pdf'][] = $persona;
                } else {
                    $detalle['conflicto_ci_nombre_distinto'][] = $persona;
                }
                continue;
            }
            $cisProcesados[$ci] = $registro['nombre'];

            if (!isset($cisExistentes[$ci])) {
                $detalle['nuevos'][] = $persona;
                continue;
            }

            $idPersona = $cisExistentes[$ci];
            $ultimoEstado = $ultimosEstadosPorPersona->get($idPersona);

            if (!$ultimoEstado) {
                // Persona sin historial (caso anómalo) — addMes también
                // fallaría acá; se cuenta como activo para no inflar omitidos.
                $detalle['activos'][] = $persona;
                continue;
            }

            if ($ultimoEstado->estado === 'baja_temporal') {
                $detalle['baja_temporal'][] = $persona;
                continue;
            }

            if ($ultimoEstado->estado === 'baja_definitiva') {
                $detalle['baja_definitiva'][] = $persona;
                continue;
            }

            if ($ultimoEstado->estado === 'depurado') {
                $yaTieneEstadoEsteMes = HistorialEstados::where('id_persona', $idPersona)
                    ->where('fecha_inicio', $fechaPrimeroMes)
                    ->exists();

                if ($yaTieneEstadoEsteMes) {
                    $detalle['conflicto_estado_mes'][] = $persona;
                    continue;
                }

                $estadoAnteriorADepurado = HistorialEstados::where('id_persona', $idPersona)
                    ->where('id', '!=', $ultimoEstado->id)
                    ->orderByDesc('fecha_inicio')
                    ->orderByDesc('id')
                    ->first();

                $estadoARestaurar = in_array($estadoAnteriorADepurado?->estado, ['baja_temporal', 'baja_definitiva'])
                    ? $estadoAnteriorADepurado->estado
                    : 'baja_definitiva';

                if ($estadoARestaurar === 'baja_definitiva') {
                    $detalle['depurados_restauran_fallecido'][] = $persona;
                } else {
                    $detalle['depurados_restauran_baja_temporal'][] = $persona;
                }
                continue;
            }

            // activo o pagos_suspendidos: se habilita igual que hoy
            $detalle['activos'][] = $persona;
        }

        // Depurados ausentes del PDF — mismo criterio que addMes: activos hoy
        // que no aparecen en esta lista se depurarán automáticamente. No
        // vienen del PDF, así que el nombre se saca de la propia Persona.
        $cisEnPdfArray = $cisEnPdf->toArray();
        if (!empty($cisEnPdfArray)) {
            $detalle['se_depuraran_por_ausencia'] = Persona::whereHas('ultimoEstado', function ($q) {
                $q->whereIn('estado', ['baja_temporal', 'baja_definitiva']);
            })
                ->whereNotIn('ci_persona', $cisEnPdfArray)
                ->get(['ci_persona', 'nombre_completo', 'nombre_persona', 'apellido_persona'])
                ->map(fn($p) => ['ci' => $p->ci_persona, 'nombre' => $p->nombre_completo])
                ->values()
                ->all();
        }

        $totalHabilitables = count($detalle['activos']) + count($detalle['nuevos']);

        return response()->json([
            'resumen' => [
                'total_cis_pdf' => $cisEnPdf->count(),
                'activos' => count($detalle['activos']),
                'nuevos' => count($detalle['nuevos']),
                'total_habilitables' => $totalHabilitables,
                'baja_temporal' => count($detalle['baja_temporal']),
                'baja_definitiva' => count($detalle['baja_definitiva']),
                'depurados_restauran_baja_temporal' => count($detalle['depurados_restauran_baja_temporal']),
                'depurados_restauran_fallecido' => count($detalle['depurados_restauran_fallecido']),
                'conflicto_estado_mes' => count($detalle['conflicto_estado_mes']),
                'duplicados_en_pdf' => count($detalle['duplicados_en_pdf']),
                'conflicto_ci_nombre_distinto' => count($detalle['conflicto_ci_nombre_distinto']),
                'se_depuraran_por_ausencia' => count($detalle['se_depuraran_por_ausencia']),
                'monto' => $monto,
            ],
            'detalle' => $detalle,
            'presupuesto_sugerido' => $totalHabilitables * $monto,
        ]);
    }

    public function addMes(Request $request)
    {
        $request->validate([
            'archivo_pdf' => 'required|file|mimes:pdf|max:5120',
            'id_gestion' => 'required|integer|exists:gestion,id_gestion',
            'mes' => 'required|integer|min:1|max:12',
        ]);

        $gestionDestino = Gestion::find($request->input('id_gestion'));

        if ($gestionDestino && $gestionDestino->estaCerrada()) {
            return redirect()->back()->withErrors(['gestion' => 'La caja de esta gestión ya está cerrada; no se pueden cargar meses nuevos.']);
        }

        // El monto por persona ya no se recibe del formulario — se toma del
        // parámetro configurado en Configuración (ver previsualizarMes()).
        $montoParametro = Parametro::valorDe('Monto - Persona');
        if (!$montoParametro || (float) $montoParametro <= 0) {
            return redirect()->back()->withErrors(['monto' => 'El "Monto - Persona" todavía no está configurado. Configúralo en Configuración antes de crear un mes.']);
        }

        $userId = Auth::id();
        $fechaHoy = Carbon::now('America/La_Paz');
        $fechaHoyString = $fechaHoy->format('Y-m-d');

        $registrosExtraidos = $request->input('registros_extraidos')
            ? json_decode($request->input('registros_extraidos'), true)
            : [];

        try {
            DB::beginTransaction();

            $mes = Mes::create([
                'mes' => $request->input('mes'),
                'monto' => $montoParametro,
                'presupuesto' => $request->input('presupuesto'),
                'id_gestion' => $request->input('id_gestion'),
            ]);

            DB::table('boleta_consecutivo')->insertOrIgnore([
                'id_mes' => $mes->id_mes,
                'id_gestion' => $mes->id_gestion,
                'ultimo_numero' => 0,
            ]);

            $insertados = [];
            $habilitados = [];
            $errores = [];
            $omitidos = [];
            $fallecidos = [];
            $observaciones = []; // ← NUEVO: avisos para revisión manual, no son errores de excepción

            $gestion = Gestion::find($request->input('id_gestion'));

            $fechaPrimeroMes = Carbon::create($gestion->gestion, $request->input('mes'), 1)
                ->format('Y-m-d');

            $user = Auth::user();
            $nombreCompleto = "{$user->nombre} {$user->apellido}";

            $cisExistentes = Persona::pluck('id_persona', 'ci_persona')->toArray();

            // CIs únicos presentes en este PDF (se reutiliza más abajo para
            // detectar depurados ausentes, evitando recalcularlo dos veces).
            $cisEnEstePdf = collect($registrosExtraidos)
                ->map(fn($r) => trim($r['ci'] ?? ''))
                ->filter()
                ->unique()
                ->values();

            // Precarga en UNA sola consulta el último estado de cada persona
            // que YA existe en el sistema y aparece en este PDF — evita el
            // N+1 que había antes (una consulta a historial_estados por cada
            // registro del PDF, que era el cuello de botella real al subir
            // listas grandes).
            $idsExistentesEnPdf = $cisEnEstePdf
                ->map(fn($ci) => $cisExistentes[$ci] ?? null)
                ->filter()
                ->values();

            // El estado que importa es el vigente DURANTE ESE MES ($fechaPrimeroMes),
            // no el que está abierto hoy: si se reprocesa un mes pasado
            // después de que la persona ya cambió de estado más adelante,
            // usar "hoy" la marcaría con un estado que en ese mes todavía no
            // tenía. Entre los que cubren el mes (no debería haber más de uno
            // con datos limpios, pero por datos inconsistentes puede haber
            // varios), se toma el de fecha_inicio más reciente — mismo
            // criterio que estado_mes en HabilitadoController.
            $fechaUltimoDiaMes = Carbon::parse($fechaPrimeroMes)->endOfMonth()->format('Y-m-d');

            $ultimosEstadosPorPersona = HistorialEstados::whereIn('id_persona', $idsExistentesEnPdf)
                ->where('fecha_inicio', '<=', $fechaUltimoDiaMes)
                ->where(function ($q) use ($fechaPrimeroMes) {
                    $q->whereNull('fecha_fin')
                        ->orWhere('fecha_fin', '>=', $fechaPrimeroMes);
                })
                ->orderByDesc('fecha_inicio')
                ->get()
                ->unique('id_persona')
                ->keyBy('id_persona');

            // CIs ya procesados en ESTE PDF — si SIGEP repite un beneficiario
            // dos veces en la misma lista, solo se procesa la primera
            // aparición (evita duplicar su `habilitado` de este mes).
            $cisProcesadosEnEstePdf = [];

            foreach ($registrosExtraidos as $registro) {
                $ci = trim($registro['ci']);
                $nombre = trim($registro['nombre']);

                if (empty($ci) || empty($nombre))
                    continue;

                if (isset($cisProcesadosEnEstePdf[$ci])) {
                    $nombreOriginal = $cisProcesadosEnEstePdf[$ci];

                    if ($this->nombresComparten($nombreOriginal, $nombre)) {
                        // Misma persona listada dos veces en el PDF: duplicado real.
                        $omitidos[] = ['ci' => $ci, 'nombre' => $nombre, 'motivo' => 'ci_duplicado_en_pdf'];
                    } else {
                        // Mismo CI, nombres sin relación: error de captura del
                        // CI en el PDF (fuente: La Paz), no un duplicado real.
                        // No se puede resolver solo — se deja documentado para
                        // revisión manual, sin habilitar a nadie a ciegas.
                        $observaciones[] = [
                            'ci' => $ci,
                            'nombre' => $nombre,
                            'motivo' => "El PDF repite el CI {$ci} para dos nombres distintos: \"{$nombreOriginal}\" y \"{$nombre}\". Es un error de captura en el documento fuente, no un duplicado real — verifique el CI correcto de \"{$nombre}\" y habilítelo manualmente si corresponde.",
                        ];
                        $omitidos[] = ['ci' => $ci, 'nombre' => $nombre, 'motivo' => 'conflicto_ci_nombre_distinto'];
                    }
                    continue;
                }
                $cisProcesadosEnEstePdf[$ci] = $nombre;

                try {
                    if (isset($cisExistentes[$ci])) {
                        $idPersona = $cisExistentes[$ci];

                        $ultimoEstado = $ultimosEstadosPorPersona->get($idPersona);

                        if ($ultimoEstado->estado === 'baja_temporal') {
                            $omitidos[] = ['ci' => $ci, 'nombre' => $nombre, 'motivo' => 'baja_temporal'];
                            continue;
                        }

                        if ($ultimoEstado->estado === 'baja_definitiva') {
                            $omitidos[] = ['ci' => $ci, 'nombre' => $nombre, 'motivo' => 'baja_definitiva'];
                            continue;
                        }

                        // depurado → reaparece en el PDF, hay que restaurar el estado que tenía
                        // ANTES de ser depurado (podía ser baja_temporal o baja_definitiva)
                        if ($ultimoEstado->estado === 'depurado') {

                            // ── Guardia: un mes solo puede tener un estado ──────────────
                            // Si ya existe un historial con fecha_inicio = este mes para esta
                            // persona (por ejemplo, alguien ya le cambió el estado manualmente
                            // este mes), no creamos un segundo registro. En vez de fallar en
                            // silencio como pasó en mayo, dejamos una observación explícita.
                            $yaTieneEstadoEsteMes = HistorialEstados::where('id_persona', $idPersona)
                                ->where('fecha_inicio', $fechaPrimeroMes)
                                ->exists();

                            if ($yaTieneEstadoEsteMes) {
                                $observaciones[] = [
                                    'ci' => $ci,
                                    'nombre' => $nombre,
                                    'motivo' => "Aparece en el PDF pero ya tiene un estado registrado para este mes. Revise manualmente el historial de estados de este beneficiario antes de continuar.",
                                ];
                                $omitidos[] = ['ci' => $ci, 'nombre' => $nombre, 'motivo' => 'conflicto_estado_mes'];
                                continue;
                            }

                            // Buscar el estado inmediatamente anterior al registro "depurado"
                            // (el segundo más reciente en el historial de esta persona)
                            $estadoAnteriorADepurado = HistorialEstados::where('id_persona', $idPersona)
                                ->where('id', '!=', $ultimoEstado->id)
                                ->orderByDesc('fecha_inicio')
                                ->orderByDesc('id')
                                ->first();

                            $estadoARestaurar = in_array($estadoAnteriorADepurado?->estado, ['baja_temporal', 'baja_definitiva'])
                                ? $estadoAnteriorADepurado->estado
                                : 'baja_definitiva'; // fallback de seguridad si no se encuentra un estado previo válido

                            // Si no se pudo determinar con certeza el estado previo, lo marcamos
                            // como observación también, para que se revise aunque el sistema
                            // haya podido continuar con el fallback.
                            if (!in_array($estadoAnteriorADepurado?->estado, ['baja_temporal', 'baja_definitiva'])) {
                                $observaciones[] = [
                                    'ci' => $ci,
                                    'nombre' => $nombre,
                                    'motivo' => "Estaba depurado pero no se encontró un estado previo válido (baja_temporal o baja_definitiva). Se restauró como baja_definitiva por defecto, revise su historial.",
                                ];
                            }

                            // Cerrar estado depurado anterior
                            $ultimoEstado->update(['fecha_fin' => $fechaPrimeroMes]);

                            // Crear el estado restaurado
                            HistorialEstados::create([
                                'id_persona' => $idPersona,
                                'estado' => $estadoARestaurar,
                                'fecha_inicio' => $fechaPrimeroMes,
                                'fecha_fin' => null,
                                'fecha_registro' => $fechaPrimeroMes,
                                'usuario_modificacion' => $nombreCompleto,
                                'observaciones' => $estadoARestaurar === 'baja_definitiva'
                                    ? 'FALLECIDO'
                                    : 'Reaparece en PDF tras estar depurado, restaurado como baja_temporal.',
                            ]);

                            if ($estadoARestaurar === 'baja_definitiva') {
                                $fallecidos[] = ['ci' => $ci, 'nombre' => $nombre];
                            } else {
                                $omitidos[] = ['ci' => $ci, 'nombre' => $nombre, 'motivo' => 'baja_temporal'];
                            }

                            continue;
                        }

                        if ($ultimoEstado->estado !== 'activo' && $ultimoEstado->estado !== 'pagos_suspendidos') {
                            $omitidos[] = ['ci' => $ci, 'nombre' => $nombre, 'motivo' => $ultimoEstado->estado];
                            continue;
                        }
                    } else {
                        $idPersona = (string) Str::uuid();

                        Persona::create([
                            'id_persona' => $idPersona,
                            'nombre_completo' => $nombre,
                            'ci_persona' => $ci,
                            'tipo_registro' => 'pendiente',
                            'fecha_registro' => $fechaPrimeroMes,
                        ]);

                        HistorialEstados::create([
                            'id_persona' => $idPersona,
                            'estado' => 'activo',
                            'fecha_inicio' => $fechaPrimeroMes,
                            'fecha_fin' => null,
                            'fecha_registro' => $fechaPrimeroMes,
                            'usuario_modificacion' => $nombreCompleto,
                            'observaciones' => '',
                        ]);

                        $cisExistentes[$ci] = $idPersona;
                        $insertados[] = ['ci' => $ci, 'nombre' => $nombre];
                    }

                    Habilitado::create([
                        'habilitado' => 1,
                        'id' => $userId,
                        'id_persona' => $idPersona,
                        'id_gestion' => $mes->id_gestion,
                        'id_mes' => $mes->id_mes,
                        'fecha_habilitado' => $fechaHoy,
                    ]);

                    $habilitados[] = ['ci' => $ci, 'nombre' => $nombre];
                } catch (Exception $e) {
                    Log::error('Error al procesar registro PDF', [
                        'ci' => $ci,
                        'nombre' => $nombre,
                        'error' => $e->getMessage(),
                        'linea' => $e->getLine(),
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
            // Reutiliza $cisEnEstePdf calculado antes del loop principal.
            $cisEnPdf = $cisEnEstePdf->toArray();

            if (empty($cisEnPdf)) {
                $ultimosHistoriales = collect();
            } else {
                // ── Ahora incluye baja_temporal además de baja_definitiva ───────────
                // Antes solo se depuraba a quienes tenían baja_definitiva. Pero
                // baja_temporal tiene el mismo problema: el PDF de La Paz puede
                // dejar de incluirlos (carnet vencido, inconsistencia de datos) y
                // luego volver a incluirlos meses después. Sin depurar también a
                // los temporales, se corría el riesgo de crear un registro duplicado
                // de baja_temporal al reaparecer.
                $ultimosHistoriales = Persona::with('ultimoEstado')
                    ->whereHas('ultimoEstado', function ($q) {
                        $q->whereIn('estado', ['baja_temporal', 'baja_definitiva']);
                    })
                    ->whereNotIn('ci_persona', $cisEnPdf)
                    ->select('id_persona', 'ci_persona')
                    ->get()
                    ->map(fn($persona) => [
                        'historial_id' => $persona->ultimoEstado->id,
                        'id_persona' => $persona->id_persona,
                        'ci_persona' => $persona->ci_persona,
                    ]);
            }

            $depurados = [];

            foreach ($ultimosHistoriales as $historial) {

                // ── Misma guardia: un mes, un solo estado ────────────────────────
                $yaTieneEstadoEsteMes = HistorialEstados::where('id_persona', $historial['id_persona'])
                    ->where('fecha_inicio', $fechaPrimeroMes)
                    ->exists();

                if ($yaTieneEstadoEsteMes) {
                    $observaciones[] = [
                        'ci' => $historial['ci_persona'],
                        'nombre' => null,
                        'motivo' => "No apareció en el PDF y correspondía depurarlo, pero ya tiene un estado registrado para este mes. Revise manualmente su historial de estados.",
                    ];
                    continue;
                }

                DB::table('historial_estados')
                    ->where('id', $historial['historial_id'])
                    ->update(['fecha_fin' => $fechaPrimeroMes]);

                HistorialEstados::create([
                    'id_persona' => $historial['id_persona'],
                    'estado' => 'depurado',
                    'fecha_inicio' => $fechaPrimeroMes,
                    'fecha_fin' => null,
                    'fecha_registro' => $fechaHoyString,
                    'usuario_modificacion' => $nombreCompleto,
                    'observaciones' => 'Cambio automático: DEPURADO.',
                ]);

                $depurados[] = ['ci' => $historial['ci_persona']];
            }

            DB::commit();

            $this->logService->logCreation(
                'Gestion',
                $mes,
                "Se registró el mes {$mesNombre} en el sistema.",
                null,
                [
                    'gestión' => $gestion->gestion,
                    'mes' => $mesNombre,
                    'monto Bs.' => $mes->monto,
                    'presupuesto Bs.' => $mes->presupuesto,
                ]
            );

            $this->logService->logHabilitacionMasiva(
                'Gestion',
                $mes,
                [
                    'mes' => $mesNombre,
                    'monto' => $request->input('monto'),
                    'presupuesto' => $request->input('presupuesto'),
                    'beneficiarios_habilitados' => count($habilitados),
                    'bajas_temporales_omitidas' => count(array_filter($omitidos, fn($o) => $o['motivo'] === 'baja_temporal')),
                    'bajas_definitivas_omitidas' => count(array_filter($omitidos, fn($o) => $o['motivo'] === 'baja_definitiva')),
                    'registros_depurados' => count($depurados),
                    'fallecidos_detectados' => count($fallecidos),
                    'observaciones_generadas' => count($observaciones),
                    'total' => count($habilitados) + count($errores),
                    'successful' => count($habilitados),
                    'failed' => count($errores),
                    'filename' => $request->file('archivo_pdf')->getClientOriginalName(),
                    'errors' => array_map(fn($e) => "CI: {$e['ci']} - {$e['error']}", $errores),
                    'fallecidos' => array_map(fn($f) => "CI: {$f['ci']} - {$f['nombre']}", $fallecidos),
                    // Lista completa (no solo el conteo) — queda en la Bitácora
                    // para revisar cualquier momento después, sin depender de
                    // que alguien la haya copiado al momento de la carga.
                    'observaciones' => $observaciones,
                ]
            );

            return redirect()->back()->with('resultadosMes', [
                'mes' => $request->input('mes'),
                'insertados' => $insertados,
                'habilitados' => $habilitados,
                'errores' => $errores,
                'bajas_temporales' => array_values(array_filter($omitidos, fn($o) => $o['motivo'] === 'baja_temporal')),
                'bajas_definitivas' => array_values(array_filter($omitidos, fn($o) => $o['motivo'] === 'baja_definitiva')),
                'omitidos' => $omitidos,
                'depurados' => $depurados,
                'fallecidos' => $fallecidos,
                'observaciones' => $observaciones, // ← NUEVO: para mostrar avisos en el frontend
                'total_procesado' => count($habilitados) + count($omitidos),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error general en addMes PDF', [
                'error' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
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

        $resultados = collect([]);
        $resultadoDatos = collect([]);

        if (!empty($year)) {

            // ── Fecha de corte: 31 de diciembre del año filtrado ────────────
            $fechaFinAnio = Carbon::createFromDate($year, 12, 31, 'America/La_Paz')
                ->endOfDay()
                ->toDateString();

            // ── Último historial por persona, vigente a esa fecha de corte ──
            $ultimoHistorial = DB::table('historial_estados as he')
                ->select('he.id_persona', 'he.estado')
                ->where('he.fecha_inicio', '<=', $fechaFinAnio)
                ->whereNotExists(function ($sub) use ($fechaFinAnio) {
                    $sub->select(DB::raw(1))
                        ->from('historial_estados as he2')
                        ->whereColumn('he2.id_persona', 'he.id_persona')
                        ->where('he2.fecha_inicio', '<=', $fechaFinAnio)
                        ->where(function ($q) {
                            $q->whereColumn('he2.fecha_inicio', '>', 'he.fecha_inicio')
                                ->orWhere(function ($q2) {
                                    $q2->whereColumn('he2.fecha_inicio', '=', 'he.fecha_inicio')
                                        ->whereColumn('he2.id', '>', 'he.id');
                                });
                        });
                })
                ->where('he.estado', '!=', 'depurado');

            // ── Subquery reutilizable: personas + su último estado + carnet, para ESTA gestión ──
            // (uno por persona, sin fan-out de meses/pagos)
            $basePersonasAnio = function () use ($ultimoHistorial) {
                return DB::table('persona as p')
                    ->joinSub($ultimoHistorial, 'uh', 'uh.id_persona', '=', 'p.id_persona')
                    ->leftJoin('carnet as c', 'c.id_persona', '=', 'p.id_persona')
                    ->whereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('habilitado as hab')
                            ->whereColumn('hab.id_persona', 'p.id_persona')
                            ->whereColumn('hab.id_gestion', 'gestion.id_gestion');
                    });
            };

            $query = Gestion::query()
                ->select([
                    'gestion.id_gestion as id',
                    'gestion.gestion as gestion',
                    'gestion.presupuesto_anual as presupuesto',
                ])

                // ── Total de personas relacionadas a la gestión (cualquier mes) ──
                ->selectSub(function ($q) {
                    $q->selectRaw('COUNT(DISTINCT hab.id_persona)')
                        ->from('habilitado as hab')
                        ->whereColumn('hab.id_gestion', 'gestion.id_gestion');
                }, 'total_personas')

                // ── Habilitadas (habilitado = 1, cualquier mes) ──────────────────
                ->selectSub(function ($q) {
                    $q->selectRaw('COUNT(DISTINCT hab.id_persona)')
                        ->from('habilitado as hab')
                        ->whereColumn('hab.id_gestion', 'gestion.id_gestion')
                        ->where('hab.habilitado', 1);
                }, 'cantidad_habilitadas')

                // ── No habilitadas = total - habilitadas ─────────────────────────
                ->selectSub(function ($q) {
                    $q->selectRaw('
                    (SELECT COUNT(DISTINCT hab.id_persona) FROM habilitado as hab
                        WHERE hab.id_gestion = gestion.id_gestion)
                    -
                    (SELECT COUNT(DISTINCT hab2.id_persona) FROM habilitado as hab2
                        WHERE hab2.id_gestion = gestion.id_gestion AND hab2.habilitado = 1)
                ');
                }, 'cantidad_no_habilitadas')

                // ── Inhabilitado: sin carnet vigente, o baja, o carnet vencido ───
                ->selectSub(function ($q) use ($basePersonasAnio) {
                    $sub = $basePersonasAnio()
                        ->selectRaw('COUNT(DISTINCT p.id_persona)')
                        ->where(function ($w) {
                            $w->whereNull('c.id_persona')
                                ->orWhereIn('uh.estado', ['baja_temporal', 'baja_definitiva'])
                                ->orWhere('c.fecha_vencimiento', '<', DB::raw('CURDATE()'));
                        });
                    $q->fromSub($sub, 'sub_inhabilitado');
                }, 'inhabilitado')

                // ── Personas activas según historial ─────────────────────────────
                ->selectSub(function ($q) use ($basePersonasAnio) {
                    $sub = $basePersonasAnio()
                        ->selectRaw('COUNT(DISTINCT p.id_persona)')
                        ->where('uh.estado', 'activo');
                    $q->fromSub($sub, 'sub_activos');
                }, 'personas_activos')

                // ── Pagos válidos (pago.pago = 1), sin fan-out por mes ───────────
                ->selectSub(function ($q) {
                    $q->selectRaw('COUNT(pg.id_pago)')
                        ->from('pago as pg')
                        ->join('habilitado as h2', 'h2.id_habilitado', '=', 'pg.id_habilitado')
                        ->whereColumn('h2.id_gestion', 'gestion.id_gestion')
                        ->where('pg.pago', 1);
                }, 'cantidad_total_pagos')

                ->selectSub(function ($q) {
                    $q->selectRaw('COALESCE(SUM(pg.monto), 0)')
                        ->from('pago as pg')
                        ->join('habilitado as h2', 'h2.id_habilitado', '=', 'pg.id_habilitado')
                        ->whereColumn('h2.id_gestion', 'gestion.id_gestion')
                        ->where('pg.pago', 1);
                }, 'total_pagado_contexto')

                // ── No pagados = habilitadas - pagos válidos ─────────────────────
                ->selectSub(function ($q) {
                    $q->selectRaw('
                    (SELECT COUNT(DISTINCT hab.id_persona) FROM habilitado as hab
                        WHERE hab.id_gestion = gestion.id_gestion AND hab.habilitado = 1)
                    -
                    (SELECT COUNT(pg.id_pago) FROM pago as pg
                        INNER JOIN habilitado as h2 ON h2.id_habilitado = pg.id_habilitado
                        WHERE h2.id_gestion = gestion.id_gestion AND pg.pago = 1)
                ');
                }, 'cantidad_no_pagados')

                ->selectSub(function ($q) {
                    $q->selectRaw('gestion.presupuesto_anual - (
                    SELECT COALESCE(SUM(pg.monto), 0) FROM pago as pg
                        INNER JOIN habilitado as h2 ON h2.id_habilitado = pg.id_habilitado
                        WHERE h2.id_gestion = gestion.id_gestion AND pg.pago = 1
                )');
                }, 'presupuesto_restante')

                ->whereYear('gestion.gestion', $year)
                ->orderBy('gestion.id_gestion', 'asc');

            $resultados = $query->paginate(15)->appends($request->query());
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

        if ($gestion->estaCerrada()) {
            return redirect()->back()->withErrors(['gestion' => 'La caja de esta gestión ya está cerrada; no se puede modificar.']);
        }

        $oldData = $gestion->getOriginal();
        $fieldsToUpdate = $request->all();

        $mapaLabels = [
            'gestion' => 'gestión',
            'presupuesto_anual' => 'presupuesto anual',
        ];

        $camposModificados = [];
        $valoresAnteriores = [];
        $valoresNuevos = [];

        foreach ($fieldsToUpdate as $campo => $nuevoValor) {
            if (!array_key_exists($campo, $mapaLabels))
                continue;

            $valorAnterior = $oldData[$campo] ?? null;
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

        $gestion->update($fieldsToUpdate);

        $this->logService->logUpdate(
            'Gestion',
            $gestion,
            [
                'campos_modificados' => $camposModificados,
                'valores_anteriores' => $valoresAnteriores,
                'valores_nuevos' => $valoresNuevos,
            ],
            "Se actualizó el registro de la gestión {$gestion->gestion} en el sistema."
        );
    }

    /**
     * Cierre de caja de una gestión completa (meses normales + retroactivos
     * juntos, sin fases separadas). A partir de acá, ningún controlador
     * permite pagar, anular, habilitar/deshabilitar, cargar retroactivos ni
     * editar meses/presupuesto de esta gestión — ver Gestion::estaCerrada().
     * El cambio de estado de un beneficiario sigue permitido (es una verdad
     * histórica independiente de la gestión), pero su cascada ya no toca los
     * `habilitado` de una gestión cerrada (HistorialEstadoService).
     */
    public function cerrarCaja(Request $request, string $id)
    {
        $gestion = Gestion::findOrFail($id);

        if ($gestion->estaCerrada()) {
            return redirect()->back()->withErrors(['gestion' => 'Esta gestión ya tiene la caja cerrada.']);
        }

        $user = Auth::user();

        $gestion->update([
            'caja_cerrada' => true,
            'fecha_cierre_caja' => now(),
        ]);

        $this->logService->logUpdate(
            'Gestion',
            $gestion,
            [
                'campos_modificados' => ['caja' => 'Cerrada'],
                'valores_anteriores' => ['caja' => 'Abierta'],
                'valores_nuevos' => ['caja' => 'Cerrada'],
            ],
            "Se cerró la caja de la gestión {$gestion->gestion}, registrado por " . ($user ? "{$user->nombre} {$user->apellido}" : 'sistema') . '.'
        );

        return redirect()->back()->with('success', "Caja de la gestión {$gestion->gestion} cerrada correctamente.");
    }

    public function updateMes(Request $request, string $id)
    {
        $mes = Mes::findOrFail($id);

        if ($mes->gestion && $mes->gestion->estaCerrada()) {
            return redirect()->back()->withErrors(['gestion' => 'La caja de esta gestión ya está cerrada; no se pueden modificar sus meses.']);
        }

        $oldData = $mes->getOriginal();
        $fieldsToUpdate = $request->only(['monto', 'presupuesto']);

        $mapaLabels = [
            'monto' => 'monto Bs.',
            'presupuesto' => 'presupuesto Bs.',
        ];

        $camposModificados = [];
        $valoresAnteriores = [];
        $valoresNuevos = [];

        foreach ($fieldsToUpdate as $campo => $nuevoValor) {
            if (!array_key_exists($campo, $mapaLabels))
                continue;

            $valorAnterior = $oldData[$campo] ?? null;
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
                'valores_nuevos' => $valoresNuevos,
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