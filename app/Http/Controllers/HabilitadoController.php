<?php

namespace App\Http\Controllers;

use App\Models\Carnet;
use App\Models\Gestion;
use App\Models\Habilitado;
use App\Models\HistorialEstados;
use App\Models\HistorialHabilitado;
use App\Models\Mes;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\Tutor;
use App\Models\User;
use Exception;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use function Laravel\Prompts\select;

class HabilitadoController extends Controller
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
        /* // Consulta para la tabla 'personas'
        $query = Persona::query()
            ->select([
                // Campos de persona
                'persona.id_persona',
                'persona.nombre_persona',
                'persona.apellido_persona',
                'persona.distrito',
                'persona.ci_persona',
                'persona.complemento',
                'persona.observacion_persona',

                // Campos de tutor
                'tutor.id_tutor',
                'tutor.nombre_tutor',
                'tutor.apellido_tutor',
                'tutor.ci_tutor',
                'tutor.complemento_tutor',
                'tutor.telefono',
                'tutor.email',
                'tutor.direccion',

                // Campos de carnet
                'carnet.id_carnet',
                'carnet.doc',
                'carnet.discapacidad',
                'carnet.fecha_emision',
                'carnet.fecha_vencimiento',

                // Solo el estado más reciente del historial
                DB::raw('(SELECT id FROM historial_estados WHERE id_persona = persona.id_persona ORDER BY fecha_registro DESC LIMIT 1) as id_estado'),
                DB::raw('(SELECT estado FROM historial_estados WHERE id_persona = persona.id_persona ORDER BY fecha_registro DESC LIMIT 1) as estado_actual'),
                DB::raw('(SELECT fecha_inicio FROM historial_estados WHERE id_persona = persona.id_persona ORDER BY fecha_registro DESC LIMIT 1) as fecha_inicio'),
                DB::raw('(SELECT fecha_fin FROM historial_estados WHERE id_persona = persona.id_persona ORDER BY fecha_registro DESC LIMIT 1) as fecha_fin'),
                DB::raw('(SELECT fecha_registro FROM historial_estados WHERE id_persona = persona.id_persona ORDER BY fecha_registro DESC LIMIT 1) as fecha_registro'),
                DB::raw('(SELECT usuario_modificacion FROM historial_estados WHERE id_persona = persona.id_persona ORDER BY fecha_registro DESC LIMIT 1) as usuario_modificacion'),
                DB::raw('(SELECT observaciones FROM historial_estados WHERE id_persona = persona.id_persona ORDER BY fecha_registro DESC LIMIT 1) as observaciones'),
                DB::raw('(SELECT created_at FROM historial_estados WHERE id_persona = persona.id_persona ORDER BY fecha_registro DESC LIMIT 1) as created_at'),
                DB::raw('(SELECT updated_at FROM historial_estados WHERE id_persona = persona.id_persona ORDER BY fecha_registro DESC LIMIT 1) as updated_at'),

                // Verificación de vencimiento del carnet usando Carbon
                DB::raw('CASE WHEN carnet.fecha_vencimiento >= "' . Carbon::now()->format('Y-m-d') . '" THEN 1 ELSE 0 END as carnet_vigente')
            ])
            ->leftJoin('tutor', 'persona.id_tutor', '=', 'tutor.id_tutor')
            ->leftJoin('carnet', 'persona.id_persona', '=', 'carnet.id_persona')
            ->distinct(); // AGREGAR DISTINCT AQUÍ

        // Filtro por búsqueda si aplica
        $search = $request->input('buscador');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->Where('id_persona', 'like', "%{$search}%")
                    ->orWhere('nombre_persona', 'like', "%{$search}%")
                    ->orWhere('apellido_persona', 'like', "%{$search}%")
                    ->orWhere('ci_persona', 'like', "%{$search}%")
                    ->orWhere('distrito', 'like', "%{$search}%")
                    ->orWhere('observacion_persona', 'like', "%{$search}%");
            });
        }

        // Obtener datos paginados con filtros
        $personas = $query->orderBy('id_persona', 'desc')
            ->paginate(15);

        return Inertia::render('Habilitados/index', [
            'totalHabilitados' => Persona::distinct()->count(),
            'persona' => $personas,
            'personas' => $personas,
            'carnet' => Carnet::all(),
            'tutor' => Tutor::all(),
            'habilitado' => Habilitado::all(),
            'filters' => [
                'buscador' => $search
            ]
        ]); */
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
        $userId = Auth::id();

        $fechaActual = Carbon::now('America/La_Paz')->format('Y-m-d');
        $data = $request->all();
        $data['habilitado'] = 1;
        $data['id'] = $userId;



        $habilitado = Habilitado::create($data);

        // Crear el registro en historial_habilitado usando los mismos datos
        HistorialHabilitado::create([
            'habilitado_historial' => 1,
            'observacion_historial' => $request->input('observaciones_habilitado'),
            'id' => $userId,
            'id_persona' => $request->input('id_persona'),
            'id_gestion' => $request->input('id_gestion'),
            'id_mes' => $request->input('id_mes'),
            'id_habilitado' => $habilitado->id_habilitado
        ]);

        $id_persona = $request->input('id_persona');
        $id_gestion = $request->input('id_gestion');

        // Usar Carbon para formatear la fecha y obtener el nombre del mes
        $fechaHoy = Carbon::now('America/La_Paz');
        $fechaGuardar = $fechaHoy->format('Y-m-d H:i:s');
        $mes = Gestion::where('id_gestion', $id_gestion)->first();

        // Obtener el nombre del mes en español
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

        // Obtener el mes de la gestión como número
        $mesNumero = Carbon::parse($mes->gestion)->month;

        // Obtener el nombre del mes en español
        $nombreMes = $meses[$mesNumero];

        $datos = Tutor::query()
            ->join('persona', 'tutor.id_tutor', '=', 'persona.id_tutor')
            ->where('persona.id_persona', $id_persona)
            ->select(
                'tutor.nombre_tutor',
                'tutor.apellido_tutor',
            )
            ->first(); // Esto obtiene un solo registro

        if ($datos) {
            // Capitalizar nombre y apellido de la persona
            $nombrePersona = ucwords(strtolower("{$datos->nombre_persona} {$datos->apellido_persona}"));
            // Capitalizar el nombre del mes
            $nombreMes = ucwords(strtolower($nombreMes));

            // Construir la descripción según si está habilitado o no
            $descripcion = sprintf(
                'Se habilitó el pago para el beneficiario.'
            );

            $action = 'habilitado';

            // IMPORTANTE: Pasamos los datos extras en el formato correcto para la vista
            $extraData = [
                'detalles' => [
                    'mes' => $nombreMes,
                    'beneficiario' => $nombrePersona
                ]
            ];
            //dd($extraData);
            // Modificación - atributos necesarios al objeto antes de pasarlo
            $habilitado->nombre = $nombrePersona;

            // Registrar en el log con datos adicionales
            $this->logService->log(
                $action,  // 'habilitado' o 'deshabilitado'
                'Mes',
                $descripcion,
                $habilitado,
                $extraData  // Pasar los datos extras aquí
            );
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id = null)
    {
session()->forget('habilitado_year');
        // Si no viene ID, intentar obtenerlo de la sesión
        if (!$id) {
            $id = session('selected_person_id');

            // Si tampoco hay en sesión, redirigir o error
            if (!$id) {
                return redirect()->route('personas.index')
                    ->with('error', 'Debe seleccionar una persona primero.');
            }
        }

        // Guardar ID en sesión
        session(['selected_person_id' => $id]);

        // Manejar el año seleccionado
        $selectedYear = request()->input('año', function () {
            $ultimaGestion = Gestion::orderBy('gestion', 'desc')->first();
            return $ultimaGestion ? $ultimaGestion->gestion : null;
        });

        //session(['habilitado_year' => $selectedYear]);

        // Obtener la persona con sus relaciones
        $persona = Persona::with(['carnet', 'ultimoEstado'])
            ->findOrFail($id);

        // Obtener gestión actual
        $gestionActual = Gestion::where('gestion', $selectedYear)->first();

        // Verificar si existen meses para la gestión
        $tieneMeses = $gestionActual
            ? Mes::where('id_gestion', $gestionActual->id_gestion)->exists()
            : false;

        // Obtener todas las gestiones
        $gestiones = Gestion::select('gestion')
            ->distinct()
            ->orderBy('gestion', 'desc')
            ->get();

        // Obtener datos habilitados con Eloquent
        $datosHabilitado = collect();

        if ($gestionActual) {
            // Obtener todos los meses de la gestión con sus habilitados
            $mesesGestion = Mes::where('id_gestion', $gestionActual->id_gestion)
                ->whereRaw(
                    "CONCAT(?, '-', LPAD(mes, 2, '0')) >= DATE_FORMAT(?, '%Y-%m')",
                    [$selectedYear, $persona->fecha_registro]
                )
                ->with([
                    'habilitados' => function ($query) use ($id) {
                        $query->where('id_persona', $id);
                    }
                ])
                ->orderBy('mes')
                ->get();

            // Obtener todos los estados históricos del año para la persona
            $estadosHistoricos = HistorialEstados::where('id_persona', $id)
                ->where('fecha_inicio', '<=', Carbon::create($selectedYear, 12, 31))
                ->where(function ($q) use ($selectedYear) {
                    $q->whereNull('fecha_fin')
                        ->orWhere('fecha_fin', '>=', Carbon::create($selectedYear, 1, 1));
                })
                ->orderBy('fecha_inicio')
                ->get();

            // Mapear datos para cada mes
            $datosHabilitado = $mesesGestion->map(function ($mes) use ($persona, $gestionActual, $selectedYear, $estadosHistoricos) {
                $habilitado = $mes->habilitados->first();

                // Determinar el estado activo del mes (último día del mes)
                $ultimoDiaMes = Carbon::create($selectedYear, $mes->mes, 1)->endOfMonth();

                $estadoMes = $estadosHistoricos->first(function ($estado) use ($ultimoDiaMes) {
                    $inicio = Carbon::parse($estado->fecha_inicio);
                    $fin = $estado->fecha_fin ? Carbon::parse($estado->fecha_fin) : null;

                    return $inicio->lte($ultimoDiaMes) && ($fin === null || $fin->gte($ultimoDiaMes));
                })?->estado;

                return (object) [
                    'id_persona' => $persona->id_persona,
                    'fecha_registro' => $persona->fecha_registro,
                    'id_carnet' => $persona->carnet?->id_carnet,
                    'fecha_vencimiento' => $persona->carnet?->fecha_vencimiento,
                    'id_gestion' => $gestionActual->id_gestion,
                    'gestion' => $gestionActual->gestion,
                    'id_mes' => $mes->id_mes,
                    'mes' => $mes->mes,
                    'monto' => $mes->monto,
                    'id_habilitado' => $habilitado?->id_habilitado,
                    'fecha_habilitado' => $habilitado?->fecha_habilitado,
                    'observaciones_habilitado' => $habilitado?->observaciones_habilitado,
                    'habilitado' => $habilitado?->habilitado,
                    'created_at' => $habilitado?->created_at,
                    'estado_mes' => $estadoMes,
                ];
            });
        }

        // Datos de persona - CORREGIDO: devolver array con un solo elemento
        $datosPersona = [
            (object) [
                'id_persona' => $persona->id_persona,
                'nombre_persona' => $persona->nombre_persona,
                'apellido_persona' => $persona->apellido_persona,
                'fecha_registro' => $persona->fecha_registro,
                'estado' => $persona->ultimoEstado?->estado,
                'fecha_inicio' => $persona->ultimoEstado?->fecha_inicio,
                'observaciones' => $persona->ultimoEstado?->observaciones,
            ]
        ];

        return Inertia::render('Habilitados/habilitar', [
            'idPersona' => $id,
            'persona' => $persona,
            'datosPersona' => $datosPersona, // Ahora es un array con un solo elemento
            'datosHabilitado' => $datosHabilitado,
            'gestiones' => $gestiones,
            'año_actual' => [
                'añoActualSistema' => $selectedYear,
                'existeAñoActual' => (bool) $gestionActual,
                'id' => $gestionActual?->id_gestion,
            ],
            'existe_gestion' => (bool) $gestionActual,
            'añoSeleccionado' => $gestionActual ?? (object) ['gestion' => $selectedYear],
            'tiene_meses' => $tieneMeses,
            'filters' => [
                'año' => $selectedYear,
                'buscador' => request()->input('buscador', '')
            ]
        ]);
    }

    // En tu controlador o en un middleware
    public function clearHabilitadoSession()
    {
        session()->forget('habilitado_year');
        session()->forget('selected_person_id');

        /* $this->clearHabilitadoSession(); */
    }

    /**
     * Show the form for editing the specified resource.
     */

    public function showHabilitado(string $id)
    {
        $persona = Persona::with(['tutor', 'carnet', 'historialEstados'])->findOrFail($id);

        //dd($persona->toArray()); // ← Agrega esto para ver qué trae

        $habilitados = $persona->habilitados()
            ->with(['gestion', 'mes', 'pago'])
            ->activo() // Scope Personalizado
            ->desdeFechaRegistro($persona->fecha_registro)
            ->ordenadoPorGestionYMes() // Scope personalizado
            ->paginate(10);


        return Inertia::render('Persona/listaHabilitados', [
            'totalHabilitado' => $habilitados->total(),
            'datosHabilitado' => $habilitados,
            'datosRecibo' => $persona
        ]);
    }

    public function edit(Request $request, $id)
    {
        //dd($request->all());
        //dd($id);
        $registro = Habilitado::findOrFail($id);

        // Validación básica
        $request->validate([
            'habilitado' => 'required|boolean',
        ]);

        // Asignar valores
        $registro->habilitado = $request->input('habilitado');
        $registro->observaciones_habilitado = $request->input('observaciones_habilitado');

        // Guardar los cambios
        $registro->save();

        // Recuperar el registro completo recién actualizado
        $habilitadoActualizado = Habilitado::where('id_habilitado', $id)->first();

        // Crear el registro en historial_habilitado usando los datos recuperados
        HistorialHabilitado::create([
            'habilitado_historial' => $request->input('habilitado'),
            'observacion_historial' => $request->input('observaciones_habilitado'),
            'id' => $habilitadoActualizado->id,
            'id_persona' => $habilitadoActualizado->id_persona,
            'id_gestion' => $habilitadoActualizado->id_gestion,
            'id_mes' => $habilitadoActualizado->id_mes,
            'id_habilitado' => $habilitadoActualizado->id_habilitado
        ]);

        $id_persona = $habilitadoActualizado->id_persona;
        $id_gestion = $habilitadoActualizado->id_gestion;
        $observacion = $request->input('observaciones_habilitado');

        // Usar Carbon para formatear la fecha y obtener el nombre del mes
        $fechaGuardar = Carbon::now('America/La_Paz')->format('Y-m-d H:i:s');
        $mes = Gestion::where('id_gestion', $id_gestion)->first();

        // Obtener el nombre del mes en español
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

        // Obtener el mes de la gestión como número
        $mesNumero = Carbon::parse($mes->gestion)->month;

        // Obtener el nombre del mes
        $nombreMes = $meses[$mesNumero];

        $datos = Tutor::query()
            ->join('persona', 'tutor.id_tutor', '=', 'persona.id_tutor')
            ->where('persona.id_persona', $id_persona)
            ->select(
                'tutor.id_tutor',
                'tutor.nombre_tutor',
                'tutor.apellido_tutor',
                'tutor.telefono',
                'tutor.email',
                'persona.nombre_persona',
                'persona.apellido_persona'
            )
            ->first(); // Esto obtiene un solo registro

        if ($datos) {
            // Capitalizar nombre y apellido de la persona
            $nombrePersona = ucwords(strtolower("{$datos->nombre_persona} {$datos->apellido_persona}"));
            // Capitalizar el nombre del mes
            $nombreMes = ucwords(strtolower($nombreMes));

            // Construir la descripción según si está habilitado o no
            $descripcion = $request->input('habilitado') == 1
                ? sprintf(
                    'Se habilitó el pago al mes de %s para el beneficiario %s.',
                    $nombreMes,
                    $nombrePersona
                )
                : sprintf(
                    'Se deshabilito el pago al mes de %s para el beneficiario %s.',
                    $nombreMes,
                    $nombrePersona
                );

            $action = $request->input('habilitado') == 1 ? 'habilitado' : 'deshabilitado';

            // IMPORTANTE: Pasamos los datos extras en el formato correcto para la vista
            $extraData = [
                'detalles' => [
                    'mes' => $nombreMes,
                    'beneficiario' => $nombrePersona,
                    'estado' => $request->input('habilitado') == 1 ? 'Habilitado' : 'Deshabilitado',
                    'observacion' => $observacion
                ]
            ];
            //dd($extraData);
            // Modificación - atributos necesarios al objeto antes de pasarlo
            $habilitadoActualizado->nombre = $nombrePersona;

            // Registrar en el log con datos adicionales
            $this->logService->log(
                $action,  // 'habilitado' o 'deshabilitado'
                'Mes',
                $descripcion,
                $habilitadoActualizado,
                $extraData  // Pasar los datos extras aquí
            );
        }
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
    public function destroy(string $id) {}
}
