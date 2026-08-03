<?php

namespace App\Http\Controllers;

use App\Models\Carnet;
use App\Models\Gestion;
use App\Models\Habilitado;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //dd($request->all());
        $now = Carbon::now('America/La_Paz');

        // ============================================================
        // PARÁMETROS
        // ============================================================
        $gestionAnio = (int) $request->input('gestion', $now->year);
        $mes         = $request->input('mes', 'all');

        // id_gestion desde la tabla gestion (columna 'gestion' es entero)
        $idGestion = DB::table('gestion')
            ->where('gestion', $gestionAnio)
            ->value('id_gestion');

        // Si no existe la gestión en BD, devolver datos vacíos
        if (!$idGestion) {
            return Inertia::render('Dashboard/index', [
                'gestionActual'            => $gestionAnio,
                'mesActual'                => $mes,
                'totalHabilitados'         => 0,
                'totalNoPagados'           => 0,
                'montoPagado'              => 0,
                'personasPagadas'          => 0,
                'totalPersonasRegistradas' => 0,
                'gestion'                  => Gestion::all(),
                'conteoEstados'            => ['activos' => 0, 'baja_temporal' => 0, 'baja_definitiva' => 0],
                'conteoDiscapacidad'       => null,
                'distribucionRegional'     => null,
                'discapacidadPorDistrito'  => collect(),
                'registros'                => collect(),
                'retroactivosHabilitado'   => false,
                'retroactivo'              => null,
            ]);
        }

        // id_mes solo si se filtró por mes específico. Se resuelve siempre al
        // mes NORMAL (es_retroactivo = false): los meses-retro usan un código
        // interno 101-112 en la columna `mes`, así que nunca chocan con el
        // mes calendario (1-12) que llega en el filtro.
        $idMes = null;
        if ($mes !== 'all') {
            $idMes = DB::table('mes')
                ->where('mes', (int) $mes)
                ->where('id_gestion', $idGestion)
                ->where('es_retroactivo', false)
                ->value('id_mes');
        }

        // ============================================================
        // FECHA DE CORTE: fin del período (mes o gestión completa), usada
        // para resolver el último estado vigente de cada persona
        // ============================================================
        // Igual que $idMes arriba, un mes fuera de 1-12 (ej. un código retro
        // 101-112 llegado por query string manual) nunca es un mes calendario
        // real: se trata como "todos los meses" en vez de pasarlo crudo a
        // Carbon, que revienta con OutOfRangeException fuera de ese rango.
        $mesCalendario = ($mes !== 'all' && ctype_digit((string) $mes) && (int) $mes >= 1 && (int) $mes <= 12)
            ? (int) $mes
            : null;

        if ($mesCalendario !== null) {
            $fechaCorte = Carbon::createFromDate($gestionAnio, $mesCalendario, 1, 'America/La_Paz')->endOfMonth();
        } else {
            $fechaCorte = Carbon::createFromDate($gestionAnio, 12, 31, 'America/La_Paz')->endOfDay();
        }

        // ============================================================
        // Filtro reutilizable: solo el registro "correcto" de habilitado por
        // persona+mes (prioriza el que tiene pago válido, luego el que está
        // habilitado=1, luego el más reciente). Evita contar dos veces a una
        // persona cuando por error de carga quedaron dos registros de
        // habilitado para el mismo mes (mismo patrón que BandejaPagos/General).
        // ============================================================
        $habilitadoUnico = function (string $alias = 'habilitado') {
            return "{$alias}.id_habilitado = (
                SELECT h2.id_habilitado
                FROM habilitado h2
                WHERE h2.id_persona = {$alias}.id_persona
                AND h2.id_mes = {$alias}.id_mes
                ORDER BY
                    (SELECT COUNT(*) FROM pago pg WHERE pg.id_habilitado = h2.id_habilitado AND pg.pago = 1) DESC,
                    h2.habilitado DESC,
                    h2.id_habilitado DESC
                LIMIT 1
            )";
        };

        // Subquery reutilizable: último estado de cada persona hasta la
        // fecha de corte del período seleccionado (closure porque se usa
        // en más de un leftJoinSub y un Builder no se puede reutilizar).
        $ultimoHistorial = function () use ($fechaCorte) {
            return DB::table('historial_estados as he')
                ->select('he.id_persona', 'he.estado')
                ->where('he.fecha_inicio', '<=', $fechaCorte->toDateString())
                ->whereNotExists(function ($sub) use ($fechaCorte) {
                    $sub->select(DB::raw(1))
                        ->from('historial_estados as he2')
                        ->whereColumn('he2.id_persona', 'he.id_persona')
                        ->where('he2.fecha_inicio', '<=', $fechaCorte->toDateString())
                        ->where(function ($q) {
                            $q->whereColumn('he2.fecha_inicio', '>', 'he.fecha_inicio')
                                ->orWhere(function ($q2) {
                                    $q2->whereColumn('he2.fecha_inicio', '=', 'he.fecha_inicio')
                                        ->whereColumn('he2.id', '>', 'he.id');
                                });
                        });
                })
                ->where('he.estado', '!=', 'depurado');
        };

        // ============================================================
        // KPI 1: HABILITADOS (deduplicado —de los duplicados por
        // persona+mes, prioriza el que ya tiene pago— excluye meses-retro,
        // excluye los que quedaron con habilitado=0, y excluye personas
        // cuyo último estado a la fecha de corte ya es una baja —mismo
        // criterio que las cards de la vista de Gestión—).
        // ============================================================
        $queryHabilitados = DB::table('habilitado')
            ->join('mes as m_hab', 'm_hab.id_mes', '=', 'habilitado.id_mes')
            ->leftJoinSub($ultimoHistorial(), 'ultimo_estado_hab', function ($join) {
                $join->on('habilitado.id_persona', '=', 'ultimo_estado_hab.id_persona');
            })
            ->where('habilitado.id_gestion', $idGestion)
            ->where('m_hab.es_retroactivo', false)
            ->where('habilitado.habilitado', 1)
            ->whereRaw("(ultimo_estado_hab.estado IS NULL OR ultimo_estado_hab.estado NOT IN ('baja_temporal', 'baja_definitiva'))")
            ->whereRaw($habilitadoUnico('habilitado'));

        if ($idMes) {
            $queryHabilitados->where('habilitado.id_mes', $idMes);
        }

        $totalHabilitados = $queryHabilitados->count();

        // ============================================================
        // KPI 2: NO PAGADOS (mismo criterio que la Bandeja de Pagos:
        // habilitado.habilitado = 1, sin pago válido asociado (pago.pago=1),
        // y el último estado de la persona a la fecha de corte NO es una
        // baja — si ya está de baja, no cuenta como "pendiente de pago").
        // ============================================================
        $queryNoPagados = DB::table('habilitado as h')
            ->join('mes as m_np', 'm_np.id_mes', '=', 'h.id_mes')
            ->leftJoin('pago as p', function ($join) {
                $join->on('p.id_habilitado', '=', 'h.id_habilitado')
                    ->where('p.pago', 1);
            })
            ->leftJoinSub($ultimoHistorial(), 'ultimo_estado', function ($join) {
                $join->on('h.id_persona', '=', 'ultimo_estado.id_persona');
            })
            ->where('h.id_gestion', $idGestion)
            ->where('m_np.es_retroactivo', false)
            ->where('h.habilitado', 1)
            ->whereNull('p.id_pago')
            ->whereRaw("(ultimo_estado.estado IS NULL OR ultimo_estado.estado NOT IN ('baja_temporal', 'baja_definitiva'))")
            ->whereRaw($habilitadoUnico('h'));

        if ($idMes) {
            $queryNoPagados->where('h.id_mes', $idMes);
        }

        $totalNoPagados = $queryNoPagados->count();

        // ============================================================
        // KPI 3 y 4: PAGOS (excluye anulados con pago.pago=1, deduplicado,
        // excluye meses-retro). JOIN directo (no LEFT JOIN) para asegurar
        // que el pago existe.
        // ============================================================
        $queryPagos = DB::table('pago as p')
            ->join('habilitado as h', 'h.id_habilitado', '=', 'p.id_habilitado')
            ->join('mes as m_pago', 'm_pago.id_mes', '=', 'h.id_mes')
            ->where('h.id_gestion', $idGestion)
            ->where('m_pago.es_retroactivo', false)
            ->where('p.pago', 1)
            ->whereRaw($habilitadoUnico('h'));
        if ($idMes) {
            $queryPagos->where('h.id_mes', $idMes);
        }

        $montoPagado     = (clone $queryPagos)->sum('p.monto');
        $personasPagadas = (clone $queryPagos)->count('p.id_pago');

        // ============================================================
        // KPI 5: PERSONAS REGISTRADAS en ese mes/gestión
        // ============================================================
        $queryPersonas = DB::table('persona')
            ->whereYear('fecha_registro', $gestionAnio);

        if ($idMes) {
            $queryPersonas->whereMonth('fecha_registro', (int) $mes);
        }

        $totalPersonasRegistradas = $queryPersonas->count();

        // ============================================================
        // DATOS GLOBALES (estáticos)
        // ============================================================
        $conteoEstados = [
            'activos'         => Persona::activos()->count(),
            'baja_temporal'   => Persona::bajaTemporal()->count(),
            'baja_definitiva' => Persona::bajaDefinitiva()->count(),
        ];

        $conteoDiscapacidad = DB::table('carnet')
            ->select(
                DB::raw('COUNT(CASE WHEN discapacidad = "FISICA-MOTORA" THEN 1 END) as fisica'),
                DB::raw('COUNT(CASE WHEN discapacidad = "AUDITIVA" THEN 1 END) as auditiva'),
                DB::raw('COUNT(CASE WHEN discapacidad = "INTELECTUAL" THEN 1 END) as intelectual'),
                DB::raw('COUNT(CASE WHEN discapacidad = "MENTAL-PSIQUICA" THEN 1 END) as mental_psiquica')
            )
            ->first();

        $distribucionRegional = DB::table('persona')
            ->select(
                DB::raw('COUNT(CASE WHEN distrito = "D-1" THEN 1 END) as d1'),
                DB::raw('COUNT(CASE WHEN distrito = "D-2" THEN 1 END) as d2'),
                DB::raw('COUNT(CASE WHEN distrito = "D-3" THEN 1 END) as d3'),
                DB::raw('COUNT(CASE WHEN distrito = "D-4" THEN 1 END) as d4'),
                DB::raw('COUNT(CASE WHEN distrito = "D-5" THEN 1 END) as d5'),
                DB::raw('COUNT(CASE WHEN distrito = "D-6" THEN 1 END) as d6'),
                DB::raw('COUNT(CASE WHEN distrito = "D-7" THEN 1 END) as d7'),
                DB::raw('COUNT(CASE WHEN distrito = "AGUIRRE" THEN 1 END) as aguirre'),
                DB::raw('COUNT(CASE WHEN distrito = "CHIÑATA" THEN 1 END) as chinata'),
                DB::raw('COUNT(CASE WHEN distrito = "LAVA LAVA" THEN 1 END) as lava_lava'),
                DB::raw('COUNT(CASE WHEN distrito = "UCUCHI" THEN 1 END) as ucuchi'),
                DB::raw('COUNT(CASE WHEN distrito = "PALCA" THEN 1 END) as palca')
            )
            ->first();

        $discapacidadPorDistrito = DB::table('persona as p')
            ->join('carnet as c', 'p.id_persona', '=', 'c.id_persona')
            ->select(
                'p.distrito',
                DB::raw('COUNT(CASE WHEN c.discapacidad = "FISICA-MOTORA" THEN 1 END) as fisica'),
                DB::raw('COUNT(CASE WHEN c.discapacidad = "AUDITIVA" THEN 1 END) as auditiva'),
                DB::raw('COUNT(CASE WHEN c.discapacidad = "INTELECTUAL" THEN 1 END) as intelectual'),
                DB::raw('COUNT(CASE WHEN c.discapacidad = "MENTAL-PSIQUICA" THEN 1 END) as mental_psiquica')
            )
            ->groupBy('p.distrito')
            ->get();

        $resumenPorGestion = DB::table('pago as p')
            ->join('habilitado as h', 'h.id_habilitado', '=', 'p.id_habilitado')
            ->join('mes as m_rpg', 'm_rpg.id_mes', '=', 'h.id_mes')
            ->join('gestion as g', 'g.id_gestion', '=', 'h.id_gestion')
            ->where('p.pago', 1)
            ->where('m_rpg.es_retroactivo', false)
            ->whereRaw($habilitadoUnico('h'))
            ->select(
                'g.gestion AS GESTION',
                DB::raw('SUM(p.monto) AS TOTAL_PAGADO'),
                DB::raw('COUNT(p.id_pago) AS CANTIDAD_PAGOS')
            )
            ->groupBy('g.id_gestion', 'g.gestion')
            ->orderBy('g.gestion')
            ->get();

        // ============================================================
        // TABLA DETALLE MENSUAL (excluye meses-retro, deduplicado,
        // excluye pagos anulados)
        // ============================================================
        // Fragmento reutilizable: la persona del habilitado NO está de baja
        // a fin de ESE mes (fecha de corte calculada por fila, porque esta
        // tabla mezcla varias gestiones/meses a la vez).
        $personaNoDeBajaSql = "h.id_persona NOT IN (
            SELECT he.id_persona
            FROM historial_estados he
            WHERE he.id = (
                SELECT he2.id FROM historial_estados he2
                WHERE he2.id_persona = he.id_persona
                AND he2.fecha_inicio <= LAST_DAY(STR_TO_DATE(CONCAT(g.gestion, '-', m.mes, '-01'), '%Y-%m-%d'))
                ORDER BY he2.fecha_inicio DESC, he2.id DESC
                LIMIT 1
            )
            AND he.estado IN ('baja_temporal', 'baja_definitiva')
        )";

        $registros = DB::table('gestion as g')
            ->leftJoin('mes as m', function ($join) {
                $join->on('m.id_gestion', '=', 'g.id_gestion')
                    ->where('m.es_retroactivo', false);
            })
            ->leftJoin('habilitado as h', function ($join) use ($habilitadoUnico) {
                $join->on('h.id_mes', '=', 'm.id_mes')
                    ->whereRaw($habilitadoUnico('h'));
            })
            ->leftJoin('pago as p', function ($join) {
                $join->on('p.id_habilitado', '=', 'h.id_habilitado')
                    ->where('p.pago', 1);
            })
            ->select([
                'g.gestion AS GESTION',
                'm.mes AS MES',
                'm.monto AS MONTO',
                // Solo cuenta habilitados con habilitado=1 y cuyo último
                // estado a fin de ESE mes no sea una baja (mismo criterio
                // que las cards de la vista de Gestión); no restringe el
                // JOIN en sí para no perder pagos válidos ya registrados.
                DB::raw("COUNT(DISTINCT CASE WHEN h.habilitado = 1 AND {$personaNoDeBajaSql}
                    THEN h.id_habilitado END) AS CANTIDAD_HABILITADOS"),
                DB::raw('COUNT(p.id_pago) AS CANTIDAD_PAGOS'),
                DB::raw('SUM(COALESCE(p.monto, 0)) AS TOTAL'),
                // No pagados de ese mes: mismos habilitados válidos de arriba,
                // pero sin pago válido asociado (mismo criterio que el KPI
                // "No pagados").
                DB::raw("COUNT(DISTINCT CASE WHEN h.habilitado = 1 AND {$personaNoDeBajaSql} AND p.id_pago IS NULL
                    THEN h.id_habilitado END) AS NO_PAGADOS"),
            ])
            ->groupBy('g.id_gestion', 'g.gestion', 'm.id_mes', 'm.mes', 'm.monto')
            ->orderBy('g.id_gestion')
            ->orderBy('m.id_mes')
            ->get();

        // ============================================================
        // CARD RETROACTIVOS: solo si la gestión tiene retroactivos
        // habilitados y ya existe al menos un mes-retro cargado (para el
        // mes elegido, o todos los meses-retro de la gestión si es 'all')
        // ============================================================
        $retroactivosHabilitado = (bool) DB::table('gestion')
            ->where('id_gestion', $idGestion)
            ->value('retroactivos_habilitado');

        $retroactivo = null;

        if ($retroactivosHabilitado) {
            $mesesRetroQuery = DB::table('mes')
                ->where('id_gestion', $idGestion)
                ->where('es_retroactivo', true);

            if ($mes !== 'all') {
                $mesesRetroQuery->where('mes_original', (int) $mes);
            }

            $idsMesesRetro = $mesesRetroQuery->pluck('id_mes');

            if ($idsMesesRetro->isNotEmpty()) {
                $totalHabilitadosRetro = DB::table('habilitado')
                    ->whereIn('id_mes', $idsMesesRetro)
                    ->where('habilitado', 1)
                    ->whereRaw($habilitadoUnico('habilitado'))
                    ->count();

                $queryPagosRetro = DB::table('pago as p')
                    ->join('habilitado as h', 'h.id_habilitado', '=', 'p.id_habilitado')
                    ->whereIn('h.id_mes', $idsMesesRetro)
                    ->where('p.pago', 1)
                    ->whereRaw($habilitadoUnico('h'));

                $retroactivo = [
                    'activo'           => true,
                    'mesOriginal'      => $mes !== 'all' ? (int) $mes : null,
                    'totalHabilitados' => $totalHabilitadosRetro,
                    'personasPagadas'  => (clone $queryPagosRetro)->count('p.id_pago'),
                    'montoPagado'      => (clone $queryPagosRetro)->sum('p.monto'),
                ];
            }
        }

        //dd($registros);

        // ============================================================
        // RENDER
        // ============================================================
        return Inertia::render('Dashboard/index', [
            'gestionActual'            => $gestionAnio,
            'mesActual'                => $mes,

            // KPIs dinámicos
            'totalHabilitados'         => $totalHabilitados,
            'totalNoPagados'           => $totalNoPagados,
            'montoPagado'              => $montoPagado,
            'personasPagadas'          => $personasPagadas,
            'totalPersonasRegistradas' => $totalPersonasRegistradas,

            // Globales — estos faltaban
            'totalPersonas'            => Persona::count(),
            'totalTutores'             => Tutor::count(),
            'totalPago' => DB::table('pago')
                ->whereBetween('fecha_pago', [
                    Carbon::createFromDate($gestionAnio - 1, 12, 1, 'America/La_Paz')->startOfMonth()->toDateTimeString(),
                    Carbon::createFromDate($gestionAnio, 11, 30, 'America/La_Paz')->endOfMonth()->toDateTimeString()
                ])
                ->sum('monto'),
            'personasSinCarnet'        => Persona::activos()->sinCarnet()->count(),
            'resumenPorGestion' => $resumenPorGestion,
            'retroactivosHabilitado'   => $retroactivosHabilitado,
            'retroactivo'              => $retroactivo,

            // Gráficas y distribuciones
            'gestion'                  => Gestion::all(),
            'conteoEstados'            => $conteoEstados,
            'conteoDiscapacidad'       => $conteoDiscapacidad,
            'distribucionRegional'     => $distribucionRegional,
            'discapacidadPorDistrito'  => $discapacidadPorDistrito,
            'registros'                => $registros,
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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
