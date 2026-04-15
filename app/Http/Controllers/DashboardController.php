<?php

namespace App\Http\Controllers;


use App\Jobs\EnviarMensajesWhatsApp;
use App\Models\Carnet;
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

        $totalBeneficiarios = Persona::whereNot('tipo_registro', 'registrado')->count();

        $totalBajaTemp = Persona::whereHas('historialEstados', function ($query) {
            $query->where('estado', 'baja_temporal');
        })->count();


        $totalBajaDef = Persona::whereHas('historialEstados', function ($query) {
            $query->where('estado', 'baja_definitiva');
        })->count();

        $carnetVencido = Carnet::where('fecha_vencimiento', '<', today())->count();

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
            ->groupBy(
                'g.id_gestion',
                'g.gestion',
                'm.id_mes',
                'm.mes',
                'm.monto'
            )
            ->orderBy('g.id_gestion', 'ASC')
            ->orderBy('m.id_mes', 'ASC')
            ->get();

        $anioActual = Carbon::now('America/La_Paz')->year; // 2026

        $gestionActual = DB::table('gestion')
            ->where('gestion', $anioActual)
            ->value('gestion');


        return Inertia::render('Dashboard/index', [
            'totalMes' => $totalMes,
            'gestion' => Gestion::all(),
            'totalBeneficiarios' => $totalBeneficiarios,
            'totalPersonas' => Persona::count(),
            'totalBajaTemp' => $totalBajaTemp,
            'totalBajaDef' => $totalBajaDef,
            'carnetVencido' => $carnetVencido,
            'totalPago' => Pago::sum('monto'),
            'totalTutores' => Tutor::count(),
            'totalHabilitado' => Habilitado::distinct('id_persona')->count(),
            'conteoEstados' => $conteoEstados,
            'conteoDiscapacidad' => $conteoDiscapacidad,
            'distribucionRegional' => $distribucionRegional,
            'personasSinCarnet' => $personasSinCarnet,
            'registros' => $registros,
            'gestionActual' => (int) $gestionActual,
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
