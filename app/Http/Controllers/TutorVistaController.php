<?php

namespace App\Http\Controllers;

use App\Models\Tutor;
use Illuminate\Http\Request;
use App\Models\Persona;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TutorVistaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('TutorVista/index');
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

    public function show(Request $request)
    {
        $tutorIdFromSession = $request->session()->get('id_tutor');
        $fechaHoy = Carbon::now('America/La_Paz')->format('Y-m-d');
        if ($tutorIdFromSession) {
            $tutorados = DB::table('persona')
                ->select(
                    'persona.id_persona',
                    'tutor.id_tutor',
                    'tutor.nombre_tutor',
                    'tutor.apellido_tutor',
                    'carnet.id_carnet',
                    'carnet.fecha_vencimiento',
                    DB::raw('MAX(habilitado.id_habilitado) as id_habilitado'),
                    'persona.nombre_persona',
                    'persona.apellido_persona',
                    'persona.distrito',
                    'persona.ci_persona',
                    'persona.estado',
                    'persona.observacion_persona',
                    'persona.created_at',
                    'persona.fecha_nacimiento'
                )
                ->leftJoin('carnet', 'persona.id_persona', '=', 'carnet.id_persona')
                ->join('tutor', 'persona.id_tutor', '=', 'tutor.id_tutor')
                ->leftJoin('habilitado', 'persona.id_persona', '=', 'habilitado.id_persona')
                ->where('tutor.id_tutor',  $tutorIdFromSession)
                ->groupBy(
                    'persona.id_persona',
                    'tutor.id_tutor',
                    'tutor.nombre_tutor',
                    'tutor.apellido_tutor',
                    'carnet.id_carnet',
                    'carnet.fecha_vencimiento',
                    'persona.nombre_persona',
                    'persona.apellido_persona',
                    'persona.distrito',
                    'persona.ci_persona',
                    'persona.estado',
                    'persona.observacion_persona',
                    'persona.created_at',
                    'persona.fecha_nacimiento',
                )
                ->get();

                if ($tutorados->isNotEmpty()) {
                    $vencimiento = $tutorados->first()->fecha_vencimiento;
                
                    // Convertir la fecha de vencimiento a un objeto Carbon si es necesario
                    $fechaVencimiento = Carbon::parse($vencimiento);
                
                    // Verificar si está vencido
                    $esVencido = $fechaVencimiento->lt($fechaHoy); // true si es menor, false si no lo es
                } else {
                    $esVencido = null; // O false, depende del comportamiento deseado
                }

            // Obtener también la información del tutor
            $tutor = Tutor::find($tutorIdFromSession);

            return Inertia::render('TutorVista/vista', [
                'tutorados' => $tutorados,
                'esVencido' => $esVencido,
                'personas' => $tutor,
            ]);
        } else {
            // Validar el request
            $validatedData = $request->validate([
                'carnet' => 'required|string',
                'fecha_nacimiento' => 'required|date'
            ]);

            // Buscar el tutor que coincida con el CI y la fecha de nacimiento
            $tutor = Tutor::where('ci_tutor', $validatedData['carnet'])
                ->where('fecha_nacimiento', $validatedData['fecha_nacimiento'])
                ->first();

            // Verificar si se encontró el tutor
            if ($tutor) {
                // Determinar el ID del tutor
                $tutorId = $tutor->id_tutor;

                // Almacenar información en la sesión
                $request->session()->put('id_tutor', $tutorId);

                $tutorados = DB::table('persona')
                    ->select(
                        'persona.id_persona',
                        'tutor.id_tutor',
                        'tutor.nombre_tutor',
                        'tutor.apellido_tutor',
                        'carnet.id_carnet',
                        'carnet.fecha_vencimiento',
                        DB::raw('MAX(habilitado.id_habilitado) as id_habilitado'),
                        'persona.nombre_persona',
                        'persona.apellido_persona',
                        'persona.distrito',
                        'persona.ci_persona',
                        'persona.estado',
                        'persona.observacion_persona',
                        'persona.created_at',
                        'persona.fecha_nacimiento'
                    )
                    ->leftJoin('carnet', 'persona.id_persona', '=', 'carnet.id_persona')
                    ->join('tutor', 'persona.id_tutor', '=', 'tutor.id_tutor')
                    ->leftJoin('habilitado', 'persona.id_persona', '=', 'habilitado.id_persona')
                    ->where('tutor.id_tutor',  $tutorId)
                    ->groupBy(
                        'persona.id_persona',
                        'tutor.id_tutor',
                        'tutor.nombre_tutor',
                        'tutor.apellido_tutor',
                        'carnet.id_carnet',
                        'carnet.fecha_vencimiento',
                        'persona.nombre_persona',
                        'persona.apellido_persona',
                        'persona.distrito',
                        'persona.ci_persona',
                        'persona.estado',
                        'persona.observacion_persona',
                        'persona.created_at',
                        'persona.fecha_nacimiento'
                    )
                    ->get();
                    if ($tutorados->isNotEmpty()) {
                        $vencimiento = $tutorados->first()->fecha_vencimiento;
                    
                        // Convertir la fecha de vencimiento a un objeto Carbon si es necesario
                        $fechaVencimiento = Carbon::parse($vencimiento);
                    
                        // Verificar si está vencido
                        $esVencido = $fechaVencimiento->lt($fechaHoy); // true si es menor, false si no lo es
                    } else {
                        $esVencido = null; // O false, depende del comportamiento deseado
                    }
                return Inertia::render('TutorVista/vista', [
                    'esVencido' => $esVencido,
                    'tutorados' => $tutorados,
                    'personas' => $tutor,
                ]);
            } else {
                // Manejar el caso cuando no se encuentra el tutor
                return redirect()->back()->with('error', 'Tutor no encontrado');
            }
        }
    }

    public function cobros(Request $request)
    {
        //dd();
        $id_persona = $request->id_persona;
        // Obtener el ID del tutor de la sesión
        $id_tutor = $request->session()->get('id_tutor');
        // Verificar si existe un ID de tutor en la sesión
        if ($id_tutor) {
            $tutorados = DB::table('persona')
                ->leftJoin('carnet', 'persona.id_persona', '=', 'carnet.id_persona')
                ->join('tutor', 'persona.id_tutor', '=', 'tutor.id_tutor')
                ->leftJoin('habilitado', 'persona.id_persona', '=', 'habilitado.id_persona')
                ->leftJoin('gestion', 'habilitado.id_gestion', '=', 'gestion.id_gestion')
                ->leftJoin('historial_habilitado', 'habilitado.id_habilitado', '=', 'historial_habilitado.id_historial')
                ->leftJoin('pago', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                ->where('tutor.id_tutor', $id_tutor)
                ->where('persona.id_persona', $id_persona)
                ->select(
                    'tutor.id_tutor',
                    'tutor.nombre_tutor',
                    'tutor.apellido_tutor',
                    'carnet.id_carnet',
                    'habilitado.id_habilitado',
                    'habilitado.habilitado',
                    'gestion.id_gestion',
                    'gestion.gestion',
                    'historial_habilitado.id_historial',
                    'pago.id_pago',
                    'pago.fecha_pago',
                    'persona.id_persona',
                    'persona.nombre_persona',
                    'persona.apellido_persona',
                    'persona.distrito',
                    'persona.ci_persona',
                    'persona.observacion_persona',
                )
                ->get();

            return Inertia::render('TutorVista/cobros', [
                'tutorados' => $tutorados,
                'habilitado' => $tutorados->pluck('id_habilitado'),
                'encontrado' => true,                
                'mensaje' => 'Tutor encontrado exitosamente'
            ]);
        }

        // Si no hay ID de tutor en la sesión, redirigir
        return $this->exit($request);
    }

    public function exit(Request $request)
    {
        // Limpiar sesión de tutor
        $request->session()->flush();
        return Inertia::render('TutorVista/index');
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $id)
    {
        // Actualizar 'estado'
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(string $id)
    {
        $datos = Persona::query()
            ->join('tutor', 'persona.id_tutor', 'tutor.id_tutor')
            ->where('persona.id_persona', '=', $id)
            ->select(
                'tutor.id_tutor',
                'persona.id_persona',
                'persona.nombre_persona',
                'persona.apellido_persona',
                'persona.ci_persona'
            )->first(); // Cambiado de get() a first()

        if (!$datos) {
            return response()->json(['message' => 'Persona no encontrada'], 404);
        }

        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
