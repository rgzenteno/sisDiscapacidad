<?php

namespace App\Http\Controllers;

use App\Models\Gestion;
use App\Models\Habilitado;
use App\Models\HistorialEstados;
use App\Models\Mes;
use App\Models\Persona;
use App\Services\LogService;
use Illuminate\Http\Request;
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
        //
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

        $data = $request->all();
        $data['habilitado'] = 1;
        $data['id'] = $userId;

        $habilitado = Habilitado::create($data);

        $id_persona = $request->input('id_persona');
        $id_gestion = $request->input('id_gestion');

        // Usar Carbon para formatear la fecha y obtener el nombre del mes
        $mes = Gestion::where('id_gestion', $id_gestion)->first();

        // Obtener el nombre del mes
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

        $persona = Persona::find($id_persona);
        $nombrePersona = ucwords(strtolower("{$persona->nombre_persona} {$persona->apellido_persona}"));

        $mes = Mes::where('id_mes', $request->input('id_mes'))->first();
        $gestion = Gestion::where('id_gestion', $request->input('id_gestion'))->first();

        $nombreMes = $meses[$mes->mes];
        $nombreMes = ucwords(strtolower($nombreMes));

        $habilitado->nombre = $nombrePersona;

        $this->logService->log(
            'habilitado',
            'Habilitar',
            "Se habilitó el pago del mes de {$nombreMes} {$gestion->gestion} para el beneficiario {$nombrePersona}.",
            $habilitado,
            [
                'detalles' => [
                    'beneficiario' => $nombrePersona,
                    'mes'          => "{$nombreMes} {$gestion->gestion}",
                    'estado'       => 'Habilitado',
                ]
            ]
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // ──────────────────────────────────────────────
        // 1. RESOLUCIÓN DEL ID DE PERSONA
        // Prioridad: parámetro URL > sesión > error
        // ──────────────────────────────────────────────
        if (!$id) {
            return redirect()->route('persona.index')
                ->with('error', 'Debe seleccionar una persona primero.');
        }

        // Limpia cualquier año habilitado guardado previamente
        session()->forget('habilitado_year');

        // ──────────────────────────────────────────────
        // 2. RESOLUCIÓN DEL AÑO SELECCIONADO
        // Prioridad: parámetro GET 'año' > última gestión registrada
        // Validación implícita: si no hay gestiones en BD, $selectedYear = null
        // ──────────────────────────────────────────────
        $selectedYear = request()->input('año')
            ?? Gestion::orderBy('gestion', 'desc')->value('gestion');

        // ──────────────────────────────────────────────
        // 3. CARGA DE ENTIDADES PRINCIPALES
        // findOrFail lanza 404 automáticamente si la persona no existe
        // Se cargan relaciones eager para evitar N+1 queries
        // ──────────────────────────────────────────────
        $persona = Persona::with(['carnet', 'ultimoEstado'])->findOrFail($id);

        // Busca la gestión que coincide exactamente con el año seleccionado (puede ser null)
        $gestionActual = Gestion::where('gestion', $selectedYear)->first();

        // Verifica si la gestión tiene meses configurados (requisito para habilitar)
        $tieneMeses = $gestionActual
            ? Mes::where('id_gestion', $gestionActual->id_gestion)->exists()
            : false;

        // Lista de años disponibles para el selector del frontend
        $gestiones = Gestion::select('gestion')
            ->orderBy('gestion', 'desc')
            ->distinct()
            ->get();

        // ──────────────────────────────────────────────
        // 4. CONSTRUCCIÓN DE DATOS DE HABILITACIÓN POR MES
        // Solo se ejecuta si existe una gestión válida para el año seleccionado
        // ──────────────────────────────────────────────
        $datosHabilitado = collect();

        if ($gestionActual) {

            // ── 4a. MESES VÁLIDOS DE LA GESTIÓN ──────────────────────────────────
            // Filtra meses cuyo periodo (YYYY-MM) sea >= al mes de registro de la persona
            // Evita mostrar meses anteriores a cuando la persona fue dada de alta
            // Incluye eager loading del habilitado y su pago asociado para evitar N+1
            $mesesGestion = Mes::where('id_gestion', $gestionActual->id_gestion)
                ->whereRaw(
                    "CONCAT(?, '-', LPAD(mes, 2, '0')) >= DATE_FORMAT(?, '%Y-%m')",
                    [$selectedYear, $persona->fecha_registro]
                )
                ->with([
                    'habilitados' => fn($q) => $q->where('id_persona', $id)->with('pago')
                ])
                ->orderBy('mes')
                ->get();

            // ── 4b. HISTORIAL DE ESTADOS DEL AÑO ─────────────────────────────────
            // Trae todos los estados activos en algún punto del año seleccionado
            // Condición 1: el estado inició antes o durante el 31/12 del año
            // Condición 2: el estado no ha terminado (fecha_fin null) o terminó
            //              después del 01/01 del año → cubre estados que abarcan
            //              parcialmente el año (ej: inició en año anterior)
            $estadosHistoricos = HistorialEstados::where('id_persona', $id)
                ->where('fecha_inicio', '<=', Carbon::create($selectedYear, 12, 31))
                ->where(function ($q) use ($selectedYear) {
                    $q->whereNull('fecha_fin')
                        ->orWhere('fecha_fin', '>=', Carbon::create($selectedYear, 1, 1));
                })
                ->orderBy('fecha_inicio')
                ->get();

            // ── 4c. MAPEO MES A MES ───────────────────────────────────────────────
            // Construye un objeto plano por mes combinando:
            // datos del mes, datos del habilitado (si existe) y estado vigente en ese mes
            $datosHabilitado = $mesesGestion->map(function ($mes) use (
                $persona,
                $gestionActual,
                $selectedYear,
                $estadosHistoricos
            ) {
                // Habilitado registrado para este mes (null si aún no fue procesado)
                $habilitado = $mes->habilitados->first();

                $primerDiaMes = Carbon::create($selectedYear, $mes->mes, 1)->startOfMonth();
                $ultimoDiaMes  = Carbon::create($selectedYear, $mes->mes, 1)->endOfMonth();

                $estadoMes = $estadosHistoricos
                    ->filter(function ($estado) use ($primerDiaMes, $ultimoDiaMes) {
                        $inicio = Carbon::parse($estado->fecha_inicio);
                        $fin    = $estado->fecha_fin ? Carbon::parse($estado->fecha_fin) : null;

                        return $inicio->lte($ultimoDiaMes)
                            && ($fin === null || $fin->gte($primerDiaMes));
                    })
                    ->sortByDesc('fecha_inicio')
                    ->first()
                    ?->estado;

                return (object) [
                    'id_persona'               => $persona->id_persona,
                    'fecha_registro'           => $persona->fecha_registro,
                    'id_carnet'                => $persona->carnet?->id_carnet,
                    'discapacidad'             => $persona->carnet?->discapacidad,
                    'fecha_emicion'            => $persona->carnet?->fecha_emicion,
                    'fecha_vencimiento'        => $persona->carnet?->fecha_vencimiento,
                    'id_gestion'               => $gestionActual->id_gestion,
                    'gestion'                  => $gestionActual->gestion,
                    'id_mes'                   => $mes->id_mes,
                    'mes'                      => $mes->mes,
                    'monto'                    => $mes->monto,
                    'id_habilitado'            => $habilitado?->id_habilitado,
                    'fecha_habilitado'         => $habilitado?->fecha_habilitado,
                    'observaciones_habilitado' => $habilitado?->observaciones_habilitado,
                    'habilitado'               => $habilitado?->habilitado,
                    'created_at'               => $habilitado?->created_at,
                    'estado_mes'               => $estadoMes,
                    'pagado'                   => $habilitado?->pago ? 1 : 0,
                    'boleta'                   => $habilitado?->pago?->numero_boleta,
                    'fecha_pago'               => $habilitado?->pago?->fecha_pago,
                ];
            });
        }

        // ──────────────────────────────────────────────
        // 5. DATOS RESUMIDOS DE LA PERSONA
        // Se envía como array de un elemento para mantener
        // compatibilidad con el componente Vue que itera la lista
        // ──────────────────────────────────────────────
        $datosPersona = [(object) [
            'id_persona'       => $persona->id_persona,
            'nombre_persona'   => $persona->nombre_persona,
            'apellido_persona' => $persona->apellido_persona,
            'nombre_completo'  => $persona->nombre_completo,
            'ci_persona'       => $persona->ci_persona,
            'distrito'         => $persona->distrito,
            'fecha_registro'   => $persona->fecha_registro,
            'estado'           => $persona->ultimoEstado?->estado,
            'fecha_inicio'     => $persona->ultimoEstado?->fecha_inicio,
            'observaciones'    => $persona->ultimoEstado?->observaciones,
        ]];

        // ──────────────────────────────────────────────
        // 6. RESPUESTA INERTIA
        // ──────────────────────────────────────────────
        return Inertia::render('Habilitados/habilitar', [
            'idPersona'      => $id,
            'persona'        => $persona,
            'datosPersona'   => $datosPersona,
            'datosHabilitado' => $datosHabilitado,
            'gestiones'      => $gestiones,
            'año_actual'     => [
                'añoActualSistema' => $selectedYear,
                'existeAñoActual'  => (bool) $gestionActual,
                'id'               => $gestionActual?->id_gestion,
            ],
            'existe_gestion'  => (bool) $gestionActual,
            'añoSeleccionado' => $gestionActual ?? (object) ['gestion' => $selectedYear],
            'tiene_meses'     => $tieneMeses,
            'filters'         => [
                'año'       => $selectedYear,
                'buscador'  => request()->input('buscador', ''),
            ],
        ]);
    }


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
        $selectedYear = request()->input('año')
            ?? Gestion::orderBy('gestion', 'desc')->value('gestion');

        $gestionActual = Gestion::where('gestion', $selectedYear)->first();

        $persona = Persona::with(['tutor', 'carnet', 'ultimoEstado'])->findOrFail($id);

        $estadosHistoricos = HistorialEstados::where('id_persona', $id)
            ->orderBy('fecha_inicio')
            ->get();

        $bajaDefinitiva = $estadosHistoricos
            ->where('estado', 'baja_definitiva')
            ->sortBy('fecha_inicio')
            ->first();

        $coleccion = Mes::with([
            'gestion',
            'habilitados' => fn($q) => $q->where('id_persona', $id)->with('pago.user')
        ])
            ->whereHas('gestion', fn($q) => $q->where('gestion', $selectedYear))
            ->whereRaw(
                "CONCAT((SELECT g.gestion FROM gestion g WHERE g.id_gestion = mes.id_gestion), '-', LPAD(mes, 2, '0')) >= DATE_FORMAT(?, '%Y-%m')",
                [$persona->fecha_registro]
            )
            ->orderBy('id_gestion', 'desc')
            ->orderBy('mes', 'desc')
            ->get()
            ->map(function ($mes) {
                $habilitado = $mes->habilitados->first();
                $mes->id_habilitado            = $habilitado?->id_habilitado;
                $mes->habilitado               = $habilitado?->habilitado;
                $mes->fecha_habilitado         = $habilitado?->fecha_habilitado;
                $mes->observaciones_habilitado = $habilitado?->observaciones_habilitado;
                $mes->pago                     = $habilitado?->pago;
                return $mes;
            });

        if ($bajaDefinitiva) {
            $fechaBaja = Carbon::parse($bajaDefinitiva->fecha_inicio)->startOfMonth();
            $coleccion = $coleccion->filter(function ($item) use ($fechaBaja) {
                $anioMes = Carbon::create($item->gestion->gestion, $item->mes, 1)->startOfMonth();
                return $anioMes->lte($fechaBaja);
            })->values();
        }

        $pagoIds = $coleccion->pluck('pago.id_pago')->filter()->values();

        $boletasDescargadas = \Spatie\Activitylog\Models\Activity::inLog('boleta_descarga')
            ->where('subject_type', \App\Models\Pago::class)
            ->whereIn('subject_id', $pagoIds)
            ->where(function ($q) {
                $q->whereNull('properties->anulado')
                    ->orWhere('properties->anulado', false);
            })
            ->pluck('subject_id')
            ->flip();

        $pagosConHistorial = \Spatie\Activitylog\Models\Activity::inLog('boleta_descarga')
            ->where('subject_type', \App\Models\Pago::class)
            ->whereIn('subject_id', $pagoIds)
            ->pluck('subject_id')
            ->flip();

        $fechaImplementacion = Carbon::parse('2026-04-15')->startOfDay();

        $coleccion = $coleccion->map(function ($item) use ($estadosHistoricos, $boletasDescargadas, $pagosConHistorial, $fechaImplementacion) {
            $primerDiaMes = Carbon::create($item->gestion->gestion, $item->mes, 1)->startOfMonth();
            $ultimoDiaMes = Carbon::create($item->gestion->gestion, $item->mes, 1)->endOfMonth();

            $item->estado_mes = $estadosHistoricos
                ->filter(function ($estado) use ($primerDiaMes, $ultimoDiaMes) {
                    $inicio = Carbon::parse($estado->fecha_inicio);
                    $fin    = $estado->fecha_fin ? Carbon::parse($estado->fecha_fin) : null;
                    return $inicio->lte($ultimoDiaMes)
                        && ($fin === null || $fin->gte($primerDiaMes));
                })
                ->sortByDesc('fecha_inicio')
                ->first()
                ?->estado;

            $pagoId        = $item->pago?->getKey();
            $tieneLogValido = isset($boletasDescargadas[$pagoId]);
            $tieneHistorial = isset($pagosConHistorial[$pagoId]);
            $esPagoAntiguo  = $item->pago && Carbon::parse($item->pago->fecha_pago)->lt($fechaImplementacion);
            $pagoReseteado  = $item->pago && $item->pago->pago == 0;

            $item->boleta_descargada = $item->pago && (
                $tieneLogValido || ($esPagoAntiguo && !$tieneHistorial && !$pagoReseteado)
            );

            return $item;
        });

        $gestiones = Gestion::select('gestion')
            ->orderBy('gestion', 'desc')
            ->distinct()
            ->get();

        return Inertia::render('Persona/listaHabilitados', [
            'datosHabilitado' => $coleccion->values(),
            'datosRecibo'     => $persona,
            'gestiones'       => $gestiones,
            'añoSeleccionado' => $gestionActual ?? (object)['gestion' => $selectedYear],
            'filters'         => ['año' => $selectedYear],
        ]);
    }




    public function edit(Request $request, $id)
    {
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

        $id_persona = $habilitadoActualizado->id_persona;
        $id_gestion = $habilitadoActualizado->id_gestion;

        // Usar Carbon para formatear la fecha y obtener el nombre del mes
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

        $persona = Persona::find($id_persona);
        $nombrePersona = ucwords(strtolower("{$persona->nombre_persona} {$persona->apellido_persona}"));

        $mes = Mes::where('id_mes', $habilitadoActualizado->id_mes)->first();
        $gestion = Gestion::where('id_gestion', $id_gestion)->first();

        $nombreMes = $meses[$mes->mes];
        $nombreMes = ucwords(strtolower($nombreMes));
        $action = $request->input('habilitado') == 1 ? 'habilitado' : 'deshabilitado';

        $habilitadoActualizado->nombre = $nombrePersona;

        $this->logService->log(
            $action,
            'Habilitar',
            $request->input('habilitado') == 1
                ? "Se habilitó el pago del mes de {$nombreMes} {$gestion->gestion} para el beneficiario {$nombrePersona}."
                : "Se deshabilitó el pago del mes de {$nombreMes} {$gestion->gestion} para el beneficiario {$nombrePersona}.",
            $habilitadoActualizado,
            [
                'detalles' => [
                    'beneficiario' => $nombrePersona,
                    'mes'          => "{$nombreMes} {$gestion->gestion}",
                    'estado'       => $request->input('habilitado') == 1 ? 'Habilitado' : 'Deshabilitado',
                    'observacion'  => $request->input('observaciones_habilitado'),
                ]
            ]
        );
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
