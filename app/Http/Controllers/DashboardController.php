<?php

namespace App\Http\Controllers;


use App\Jobs\EnviarMensajesWhatsApp;
use App\Models\Gestion;
use App\Models\Habilitado;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\Tutor;
use App\Traits\PagosDisponiblesTrait;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $totalMes = 0;


        $fechaHoy = Carbon::now('America/La_Paz')->format('Y-m-d');
        $gestion = DB::table('gestion')
            ->whereRaw("DATE_FORMAT(gestion, '%Y-%m') = DATE_FORMAT(?, '%Y-%m')", [$fechaHoy])
            ->value('gestion');


        if ($gestion) {
            // Obtiene la fecha completa (gestion) donde el mes y año coincidan con $fechaHoy
            // Ejemplo: Si $fechaHoy es '2023-05-15', busca registros con mes mayo y año 2023
            $fechaMes = DB::table('gestion')
                ->whereRaw("DATE_FORMAT(gestion, '%Y-%m') = DATE_FORMAT(?, '%Y-%m')", [$fechaHoy])
                ->value('gestion'); // Solo obtiene el valor del campo 'gestion'

            // Calcula la suma total de pagos para el mes y año obtenido
            $totalMes = Pago::query()
                ->whereYear('fecha_pago', Carbon::parse($fechaMes)->year)  // Filtra por año
                ->whereMonth('fecha_pago', Carbon::parse($fechaMes)->month) // Filtra por mes
                ->sum('monto'); // Suma todos los montos
        }

        $conteoEstados = DB::table('historial_estados')
            ->select(
                DB::raw('COUNT(CASE WHEN estado = "activo" THEN 1 END) as activos'),
                DB::raw('COUNT(CASE WHEN estado = "baja_temporal" THEN 1 END) as baja_temporal'),
                DB::raw('COUNT(CASE WHEN estado = "baja_definitiva" THEN 1 END) as baja_definitiva')
            )
            ->first();

        $conteoDiscapacidad = DB::table('carnet')
            ->select(
                DB::raw('COUNT(CASE WHEN discapacidad = "FISICA-MOTORA" THEN 1 END) as fisica'),
                DB::raw('COUNT(CASE WHEN discapacidad = "AUDITIVA" THEN 1 END) as auditiva'),
                DB::raw('COUNT(CASE WHEN discapacidad = "INTELECTUAL" THEN 1 END) as intelectual'),
                DB::raw('COUNT(CASE WHEN discapacidad = "MENTAL-PSIQUICA" THEN 1 END) as mental_psiquica')
            )
            ->first();

        $distribucionRegional = DB::table('persona as p')
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
                DB::raw('COUNT(CASE WHEN distrito = "LAVA LAVA" THEN 1 END) lava_lava'),
                DB::raw('COUNT(CASE WHEN distrito = "UCUCHI" THEN 1 END) as ucuchi'),
                DB::raw('COUNT(CASE WHEN distrito = "PALCA" THEN 1 END) as palca')
            )->first();

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

        $personasSinCarnet = DB::table('persona')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('carnet')
                    ->whereRaw('carnet.id_persona = persona.id_persona');
            })
            ->count();

        $gestiones = DB::table('gestion as g')
            ->leftJoin('habilitado as h', 'g.id_gestion', '=', 'h.id_gestion')
            ->leftJoin('pago as pa', 'h.id_habilitado', '=', 'pa.id_habilitado')
            ->select(
                'g.gestion AS GESTION',
                DB::raw('COUNT(DISTINCT h.id_persona) AS CANTIDAD_PERSONAS'),
                DB::raw('COALESCE(SUM(pa.monto), 0) AS TOTAL_PAGADO'),
                DB::raw('CASE
            WHEN COUNT(DISTINCT h.id_persona) = COUNT(DISTINCT pa.id_habilitado) THEN 1
            WHEN DATE_FORMAT(g.gestion, "%Y-%m") = DATE_FORMAT("' . $fechaHoy . '", "%Y-%m") THEN 2
            ELSE 3
        END AS ESTADO')
            )
            ->groupBy('g.id_gestion', 'g.gestion')
            ->orderBy('g.gestion', 'DESC')
            ->get();


        return Inertia::render('Dashboard/index', [
            'totalMes' => $totalMes,
            'gestion' => Gestion::all(),
            'totalPersonas' => Persona::count(),
            'totalPago' => Pago::sum('monto'),
            'totalTutores' => Tutor::count(),
            'totalHabilitado' => Habilitado::distinct('id_persona')->count(),
            'conteoEstados' => $conteoEstados,
            'conteoDiscapacidad' => $conteoDiscapacidad,
            'distribucionRegional' => $distribucionRegional,
            'personasSinCarnet' => $personasSinCarnet,
            'gestiones' => $gestiones,
            'discapacidadPorDistrito' => $discapacidadPorDistrito,


            'monthlyPayments' => [
                'labels' => ['Enero', 'Febrero', 'Marzo', 'Abril'],
                'data' => [250, 0, 0, 0]
            ],
            'paymentStatus' => [
                'approved' => 85,
                'pending' => 10,
                'rejected' => 5
            ]
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
