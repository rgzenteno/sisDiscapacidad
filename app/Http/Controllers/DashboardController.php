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
        $now = Carbon::now('America/La_Paz');

        // ============================================================
        // PARÁMETROS
        // ============================================================
        $gestionAnio = (int) $request->input('gestion', $now->year);
        $mes         = $request->input('mes', (string) $now->month);

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
                'totalBajas'               => 0,
                'montoPagado'              => 0,
                'personasPagadas'          => 0,
                'totalPersonasRegistradas' => 0,
                'gestion'                  => Gestion::all(),
                'conteoEstados'            => ['activos' => 0, 'baja_temporal' => 0, 'baja_definitiva' => 0],
                'conteoDiscapacidad'       => null,
                'distribucionRegional'     => null,
                'discapacidadPorDistrito'  => collect(),
                'registros'                => collect(),
            ]);
        }

        // id_mes solo si se filtró por mes específico
        $idMes = null;
        if ($mes !== 'all') {
            $idMes = DB::table('mes')
                ->where('mes', (int) $mes)
                ->where('id_gestion', $idGestion)
                ->value('id_mes');
        }

        // ============================================================
        // FECHAS DE CORTE para historial_estados
        // ============================================================
        if ($mes !== 'all') {
            $fechaInicio = Carbon::createFromDate($gestionAnio, (int) $mes, 1, 'America/La_Paz')->startOfMonth();
            $fechaCorte  = Carbon::createFromDate($gestionAnio, (int) $mes, 1, 'America/La_Paz')->endOfMonth();
        } else {
            $fechaInicio = Carbon::createFromDate($gestionAnio, 1, 1, 'America/La_Paz')->startOfMonth();
            $fechaCorte  = Carbon::createFromDate($gestionAnio, 12, 31, 'America/La_Paz')->endOfDay();
        }

        // ============================================================
        // KPI 1: HABILITADOS
        // ============================================================
        $queryHabilitados = DB::table('habilitado')
            ->where('id_gestion', $idGestion);

        if ($idMes) {
            $queryHabilitados->where('id_mes', $idMes);
        }

        $totalHabilitados = $queryHabilitados->count();

        // ============================================================
        // KPI 2: TOTAL BAJAS (personas ÚNICAS con baja vigente en el período)
        // Lógica: fecha_inicio del estado <= fin del período
        //         Y (fecha_fin IS NULL OR fecha_fin >= inicio del período)
        // Solo el último estado de cada persona que sea baja
        // ============================================================
        $totalBajas = DB::table('historial_estados as he')
            ->whereIn('he.estado', ['baja_temporal', 'baja_definitiva'])
            ->where('he.fecha_inicio', '<=', $fechaCorte->toDateString())
            ->where(function ($q) use ($fechaInicio) {
                $q->whereNull('he.fecha_fin')
                    ->orWhere('he.fecha_fin', '>=', $fechaInicio->toDateString());
            })
            // Solo contar si este registro es el último estado de esa persona
            // hasta la fecha de corte (para no contar bajas que luego se reactivaron)
            ->whereNotExists(function ($q) use ($fechaCorte) {
                $q->select(DB::raw(1))
                    ->from('historial_estados as he2')
                    ->whereColumn('he2.id_persona', 'he.id_persona')
                    ->where('he2.fecha_inicio', '>', DB::raw('he.fecha_inicio'))
                    ->where('he2.fecha_inicio', '<=', $fechaCorte->toDateString())
                    ->whereNotIn('he2.estado', ['baja_temporal', 'baja_definitiva']);
            })
            ->distinct('id_persona')
            ->count('id_persona');

        // ============================================================
        // KPI 3 y 4: PAGOS (solo registros que existen = pago realizado)
        // JOIN directo (no LEFT JOIN) para asegurar que el pago existe
        // ============================================================
        $queryPagos = DB::table('pago as p')
            ->join('habilitado as h', 'h.id_habilitado', '=', 'p.id_habilitado')
            ->where('h.id_gestion', $idGestion);

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

        if ($mes !== 'all') {
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

        // ============================================================
        // TABLA DETALLE MENSUAL
        // ============================================================
        $registros = DB::table('gestion as g')
            ->leftJoin('mes as m', 'm.id_gestion', '=', 'g.id_gestion')
            ->leftJoin('habilitado as h', 'h.id_mes', '=', 'm.id_mes')
            ->leftJoin('pago as p', 'p.id_habilitado', '=', 'h.id_habilitado')
            ->select([
                'g.gestion AS GESTION',
                'm.mes AS MES',
                'm.monto AS MONTO',
                DB::raw('COUNT(DISTINCT h.id_habilitado) AS CANTIDAD_HABILITADOS'),
                DB::raw('COUNT(p.id_pago) AS CANTIDAD_PAGOS'),
                DB::raw('COUNT(p.id_pago) * m.monto AS TOTAL'),
            ])
            ->groupBy('g.id_gestion', 'g.gestion', 'm.id_mes', 'm.mes', 'm.monto')
            ->orderBy('g.id_gestion')
            ->orderBy('m.id_mes')
            ->get();

        // ============================================================
        // RENDER
        // ============================================================
        return Inertia::render('Dashboard/index', [
            'gestionActual'            => $gestionAnio,
            'mesActual'                => $mes,

            // KPIs dinámicos
            'totalHabilitados'         => $totalHabilitados,
            'totalBajas'               => $totalBajas,
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
