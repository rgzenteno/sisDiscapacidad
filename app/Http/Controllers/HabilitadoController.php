<?php

namespace App\Http\Controllers;

use App\Models\Gestion;
use App\Models\Habilitado;
use App\Models\HistorialEstados;
use App\Models\Mes;
use App\Models\PagoSuspendido;
use App\Models\Persona;
use App\Services\LogService;
use App\Services\PresupuestoMesService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\select;

class HabilitadoController extends Controller
{

    protected $logService;
    protected $presupuestoMesService;

    public function __construct(LogService $logService, PresupuestoMesService $presupuestoMesService)
    {
        $this->logService = $logService;
        $this->presupuestoMesService = $presupuestoMesService;
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
        $request->validate([
            'observaciones_habilitado' => 'required|string',
            'id_persona' => 'required',
            'id_mes' => 'required',
        ]);

        $mes = Mes::findOrFail($request->input('id_mes'));

        if ($mes->gestion && $mes->gestion->estaCerrada()) {
            throw ValidationException::withMessages([
                'habilitado' => 'La caja de esta gestión ya está cerrada; no se pueden habilitar pagos.',
            ]);
        }

        $this->verificarNoHabilitadoEnMesHermano($mes, $request->input('id_persona'));

        $userId = Auth::id();

        // Si ya existe un habilitado para esta persona/mes (quedó
        // desactivado antes, o es un duplicado del bug histórico de doble
        // clic — ver resolverHabilitadoDePersonaEnMes), se reutiliza y se
        // reactiva en vez de crear un registro nuevo.
        $habilitado = $this->resolverHabilitadoDePersonaEnMes($request->input('id_persona'), $mes->id_mes);

        if ($habilitado) {
            $valorAnterior = (bool) $habilitado->habilitado;
            $habilitado->habilitado = 1;
            $habilitado->observaciones_habilitado = $request->input('observaciones_habilitado');
            $habilitado->save();
        } else {
            $valorAnterior = false;
            $data = $request->all();
            $data['habilitado'] = 1;
            $data['id'] = $userId;

            $habilitado = Habilitado::create($data);
        }

        $ajustePresupuesto = $this->presupuestoMesService->ajustarPorHabilitado($mes, $valorAnterior, true);

        $id_persona = $request->input('id_persona');

        $persona = Persona::find($id_persona);
        $nombrePersona = ucwords(strtolower("{$persona->nombre_persona} {$persona->apellido_persona}"));

        $mes = Mes::where('id_mes', $request->input('id_mes'))->first();
        $gestion = Gestion::where('id_gestion', $request->input('id_gestion'))->first();

        $nombreMes = $this->nombreMesReal($mes);

        $habilitado->nombre = $nombrePersona;

        $this->logService->log(
            'habilitado',
            'Habilitar',
            "Se habilitó el pago del mes de {$nombreMes} {$gestion->gestion} para el beneficiario {$nombrePersona}.",
            $habilitado,
            [
                'detalles' => [
                    'beneficiario' => $nombrePersona,
                    'mes' => "{$nombreMes} {$gestion->gestion}",
                    'estado' => 'Habilitado',
                    'presupuesto_anterior' => $ajustePresupuesto['anterior'] ?? null,
                    'presupuesto_nuevo' => $ajustePresupuesto['nuevo'] ?? null,
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

        // Lista de años disponibles para el selector del frontend. Se excluyen
        // solo las gestiones que tienen ALGÚN mes pero les falta el mes=1
        // (caso inconsistente); una gestión recién creada sin meses todavía
        // sí se muestra, igual que en Gestion/index.vue.
        $gestiones = Gestion::select('gestion')
            ->where(function ($q) {
                $q->whereDoesntHave('meses', fn($mq) => $mq->where('es_retroactivo', false))
                    ->orWhereHas('meses', fn($mq) => $mq->where('es_retroactivo', false)->where('mes', 1));
            })
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
                ->where(function ($q) use ($id, $selectedYear, $persona) {
                    // Meses-retro: solo se muestran si esta persona tiene un
                    // habilitado ACTIVO ahí (habilitado=true) — si el retro
                    // está desactivado, no debe tapar al mes normal (que es
                    // el que realmente está vigente). Meses normales: se
                    // muestran todos los que caen desde su fecha de registro,
                    // tenga habilitado o no.
                    $q->where(function ($q2) use ($id) {
                        $q2->where('es_retroactivo', true)
                            ->whereHas('habilitados', fn($q3) => $q3->where('id_persona', $id)->where('habilitado', true));
                    })->orWhere(function ($q2) use ($selectedYear, $persona) {
                        $q2->where('es_retroactivo', false)
                            ->whereRaw(
                                "CONCAT(?, '-', LPAD(mes, 2, '0')) >= DATE_FORMAT(?, '%Y-%m')",
                                [$selectedYear, $persona->fecha_registro]
                            );
                    });
                })
                ->with([
                    'gestion',
                    'habilitados' => fn($q) => $q->where('id_persona', $id)->with('pago.anulacion')
                ])
                ->orderBy('mes')
                ->get();

            // Diciembre de la gestión anterior — mismo patrón que en
            // Gestion/index.vue: se muestra prestado, solo visual, no crea ni
            // modifica nada (sigue asociado a su propia gestión real).
            $mesDiciembreAnterior = Mes::where('es_retroactivo', false)
                ->where('mes', 12)
                ->whereHas('gestion', fn($q) => $q->where('gestion', $selectedYear - 1))
                ->with([
                    'gestion',
                    'habilitados' => fn($q) => $q->where('id_persona', $id)->with('pago.anulacion')
                ])
                ->first();

            if ($mesDiciembreAnterior) {
                $mesesGestion = collect([$mesDiciembreAnterior])->merge($mesesGestion);
            }

            // Si esta persona tiene un retro para un mes real (cualquiera, no
            // solo diciembre), ese retro reemplaza al mes normal
            // correspondiente a ese mismo mes real — mostrar ambos sería
            // redundante, ya que el retro ya cuenta la historia completa.
            $mesesCubiertosPorRetro = $mesesGestion
                ->filter(fn($mes) => $mes->es_retroactivo)
                ->map(function ($mes) {
                    [$año, $mesReal] = $this->fechaRealDeMes($mes);
                    return "{$año}-{$mesReal}";
                });

            $mesesGestion = $mesesGestion->reject(
                fn($mes) => !$mes->es_retroactivo && $mesesCubiertosPorRetro->contains("{$mes->gestion->gestion}-{$mes->mes}")
            )->values();

            // ── 4b. HISTORIAL DE ESTADOS DEL AÑO ─────────────────────────────────
            // Trae todos los estados activos en algún punto del año seleccionado
            // (o de diciembre del año anterior, para el mes prestado). Condición 1:
            // el estado inició antes o durante el 31/12 del año. Condición 2: el
            // estado no ha terminado (fecha_fin null) o terminó después del 01/01
            // del año ANTERIOR → cubre estados que abarcan parcialmente diciembre
            // del año anterior.
            $estadosHistoricos = HistorialEstados::where('id_persona', $id)
                ->where('fecha_inicio', '<=', Carbon::create($selectedYear, 12, 31))
                ->where(function ($q) use ($selectedYear) {
                    $q->whereNull('fecha_fin')
                        ->orWhere('fecha_fin', '>=', Carbon::create($selectedYear - 1, 1, 1));
                })
                ->orderBy('fecha_inicio')
                ->get();

            // ── 4c. MAPEO MES A MES ───────────────────────────────────────────────
            // Construye un objeto plano por mes combinando:
            // datos del mes, datos del habilitado (si existe) y estado vigente en ese mes

            $bajaDefinitiva = in_array($persona->ultimoEstado?->estado, ['baja_definitiva', 'depurado'])
                ? $estadosHistoricos
                    ->whereIn('estado', ['baja_definitiva', 'depurado'])
                    ->sortBy('fecha_inicio')
                    ->first()
                : null;

            $fechaBaja = $bajaDefinitiva
                ? Carbon::parse($bajaDefinitiva->fecha_inicio)->startOfMonth()
                : null;

            // Meses-retro YA creados en esta gestión (vía la carga masiva de
            // Excel en Gestión), keyed por su mes_original — permite saber,
            // para cada mes normal, si ya existe un mes-retro al cual asociar
            // un habilitado manual desde acá (switch "Retroactivo" del modal).
            $retrosCreadosPorMesOriginal = Mes::where('id_gestion', $gestionActual->id_gestion)
                ->where('es_retroactivo', true)
                ->get(['id_mes', 'mes_original', 'monto'])
                ->keyBy('mes_original');

            $datosHabilitado = $mesesGestion
                ->filter(function ($mes) use ($fechaBaja) {
                    if (!$fechaBaja)
                        return true;
                    [$año, $mesReal] = $this->fechaRealDeMes($mes);
                    $anioMes = Carbon::create($año, $mesReal, 1)->startOfMonth();
                    return $anioMes->lte($fechaBaja);
                })
                ->map(function ($mes) use ($persona, $estadosHistoricos, $gestionActual, $retrosCreadosPorMesOriginal) {
                    // Habilitado registrado para este mes (null si aún no fue procesado)
                    $habilitado = $mes->habilitados->first();

                    [$año, $mesReal] = $this->fechaRealDeMes($mes);
                    $primerDiaMes = Carbon::create($año, $mesReal, 1)->startOfMonth();
                    $ultimoDiaMes = Carbon::create($año, $mesReal, 1)->endOfMonth();

                    $estadoMes = $estadosHistoricos
                        ->filter(function ($estado) use ($primerDiaMes, $ultimoDiaMes) {
                            $inicio = Carbon::parse($estado->fecha_inicio);
                            $fin = $estado->fecha_fin ? Carbon::parse($estado->fecha_fin) : null;

                            return $inicio->lte($ultimoDiaMes)
                                && ($fin === null || $fin->gte($primerDiaMes));
                        })
                        ->sortByDesc('fecha_inicio')
                        ->first()
                            ?->estado;

                    return (object) [
                        'id_persona' => $persona->id_persona,
                        'fecha_registro' => $persona->fecha_registro,
                        'id_carnet' => $persona->carnet?->id_carnet,
                        'discapacidad' => $persona->carnet?->discapacidad,
                        'fecha_emision' => $persona->carnet?->fecha_emision,
                        'fecha_vencimiento' => $persona->carnet?->fecha_vencimiento,
                        'id_gestion' => $mes->gestion->id_gestion,
                        'gestion' => $mes->gestion->gestion,
                        'id_mes' => $mes->id_mes,
                        'mes' => $mes->mes,
                        'es_retroactivo' => $mes->es_retroactivo,
                        'mes_original' => $mes->mes_original,
                        'monto' => $mes->monto,
                        'id_habilitado' => $habilitado?->id_habilitado,
                        'fecha_habilitado' => $habilitado?->fecha_habilitado,
                        'observaciones_habilitado' => $habilitado?->observaciones_habilitado,
                        'habilitado' => $habilitado?->habilitado,
                        'created_at' => $habilitado?->created_at,
                        'estado_mes' => $estadoMes,
                        'pagado' => $habilitado?->pago
                            ? ($habilitado->pago->pago ? 1 : 0)
                            : null,
                        'boleta' => $habilitado?->pago?->numero_boleta,
                        'fecha_pago' => $habilitado?->pago?->fecha_pago,
                        // Datos para el switch "Retroactivo" del modal (solo
                        // aplica a meses normales, enero-octubre): si la
                        // gestión tiene el apartado habilitado y ya existe el
                        // mes-retro de este mes específico, se puede
                        // gestionar el retroactivo de esta persona desde acá.
                        'retro_habilitado_gestion' => (bool) $gestionActual->retroactivos_habilitado,
                        'retro_mes_cargado' => $retrosCreadosPorMesOriginal->has($mes->mes),
                        'retro_id_mes' => $retrosCreadosPorMesOriginal->get($mes->mes)?->id_mes,
                        'retro_monto' => $retrosCreadosPorMesOriginal->get($mes->mes)?->monto,
                        // Si ESTA tarjeta ya es un retroactivo, datos de su mes
                        // normal correspondiente — permite, si el retro está
                        // deshabilitado, ofrecer un switch para volver a
                        // gestionar el mes normal (ver ModalHabilitar.vue).
                        ...($mes->es_retroactivo
                            ? (function () use ($gestionActual, $mes, $persona) {
                                $datos = $this->datosMesNormalParaPersona(
                                    $gestionActual->id_gestion,
                                    $mes->mes_original,
                                    $persona->id_persona
                                );

                                return [
                                    'normal_id_mes' => $datos?->id_mes,
                                    'normal_monto' => $datos?->monto,
                                    'normal_id_habilitado' => $datos?->id_habilitado,
                                    'normal_habilitado' => $datos?->habilitado,
                                    'normal_observaciones_habilitado' => $datos?->observaciones_habilitado,
                                    'normal_fecha_habilitado' => $datos?->fecha_habilitado,
                                    'normal_pagado' => $datos?->pagado,
                                    'normal_boleta' => $datos?->boleta,
                                    'normal_fecha_pago' => $datos?->fecha_pago,
                                ];
                            })()
                            : []),
                    ];
                })
                // Orden cronológico real: diciembre del año anterior primero,
                // luego enero..noviembre — no por el valor crudo de `mes`
                // (el retro usa códigos como 112 que ordenarían al final).
                ->sortBy(function ($item) {
                    if ($item->es_retroactivo) {
                        $año = $item->mes_original == 12 ? $item->gestion - 1 : $item->gestion;
                        return sprintf('%04d-%02d', $año, $item->mes_original);
                    }
                    return sprintf('%04d-%02d', $item->gestion, $item->mes);
                })
                ->values();
        }

        // ──────────────────────────────────────────────
        // 5. DATOS RESUMIDOS DE LA PERSONA
        // Se envía como array de un elemento para mantener
        // compatibilidad con el componente Vue que itera la lista
        // ──────────────────────────────────────────────
        $datosPersona = [
            (object) [
                'id_persona' => $persona->id_persona,
                'nombre_persona' => $persona->nombre_persona,
                'apellido_persona' => $persona->apellido_persona,
                'nombre_completo' => $persona->nombre_completo,
                'ci_persona' => $persona->ci_persona,
                'distrito' => $persona->distrito,
                'fecha_registro' => $persona->fecha_registro,
                'estado' => $persona->ultimoEstado?->estado,
                'fecha_inicio' => $persona->ultimoEstado?->fecha_inicio,
                'observaciones' => $persona->ultimoEstado?->observaciones,
            ]
        ];

        // ──────────────────────────────────────────────
        // 6. RESPUESTA INERTIA
        // ──────────────────────────────────────────────
        return Inertia::render('Habilitados/habilitar', [
            'idPersona' => $id,
            'persona' => $persona,
            'datosPersona' => $datosPersona,
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
                'buscador' => request()->input('buscador', ''),
            ],
        ]);
    }


    public function clearHabilitadoSession()
    {
        session()->forget('habilitado_year');
        session()->forget('selected_person_id');
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

        // Suspensión de pagos: timeline independiente de historial_estados
        // (ver PagoSuspendidoService) — decisión interna de UMADIS, no un
        // estado del beneficiario.
        $suspensionesPago = PagoSuspendido::where('id_persona', $id)
            ->orderBy('fecha_inicio')
            ->get();

        $suspensionPagoActual = $suspensionesPago->firstWhere('fecha_fin', null);

        $bajaDefinitiva = in_array($persona->ultimoEstado?->estado, ['baja_definitiva', 'depurado'])
            ? $estadosHistoricos
                ->whereIn('estado', ['baja_definitiva', 'depurado'])
                ->sortBy('fecha_inicio')
                ->first()
            : null;

        $coleccion = Mes::with([
            'gestion',
            'habilitados' => fn($q) => $q->where('id_persona', $id)->with(['pago.user', 'pago.anulacion'])
        ])
            ->whereHas('gestion', fn($q) => $q->where('gestion', $selectedYear))
            ->where(function ($q) use ($id, $persona) {
                // Meses-retro: solo se muestran si esta persona tiene un
                // habilitado ACTIVO ahí (habilitado=true) — si el retro está
                // desactivado, no debe tapar al mes normal (que es el que
                // realmente está vigente y donde se hace el pago). Meses
                // normales: se muestran todos los que caen desde su fecha de
                // registro, tenga habilitado o no.
                $q->where(function ($q2) use ($id) {
                    $q2->where('es_retroactivo', true)
                        ->whereHas('habilitados', fn($q3) => $q3->where('id_persona', $id)->where('habilitado', true));
                })->orWhere(function ($q2) use ($persona) {
                    $q2->where('es_retroactivo', false)
                        ->whereRaw(
                            "CONCAT((SELECT g.gestion FROM gestion g WHERE g.id_gestion = mes.id_gestion), '-', LPAD(mes, 2, '0')) >= DATE_FORMAT(?, '%Y-%m')",
                            [$persona->fecha_registro]
                        );
                });
            })
            ->orderBy('id_gestion', 'desc')
            ->orderBy('mes', 'desc')
            ->get()
            ->map(function ($mes) {
                $habilitado = $mes->habilitados->first();
                $mes->id_habilitado = $habilitado?->id_habilitado;
                $mes->habilitado = $habilitado?->habilitado;
                $mes->fecha_habilitado = $habilitado?->fecha_habilitado;
                $mes->observaciones_habilitado = $habilitado?->observaciones_habilitado;
                $mes->pago = $habilitado?->pago;
                return $mes;
            });

        // Diciembre de la gestión anterior — mismo patrón que en
        // Gestion/index.vue: se muestra prestado, solo visual, no crea ni
        // modifica nada (sigue asociado a su propia gestión real). Se agrega
        // aquí, antes de los filtros/cálculos de estado, para que pase por el
        // mismo pipeline que el resto de los meses.
        $mesDiciembreAnterior = Mes::with([
            'gestion',
            'habilitados' => fn($q) => $q->where('id_persona', $id)->with(['pago.user', 'pago.anulacion'])
        ])
            ->where('es_retroactivo', false)
            ->where('mes', 12)
            ->whereHas('gestion', fn($q) => $q->where('gestion', $selectedYear - 1))
            ->first();

        if ($mesDiciembreAnterior) {
            $habilitadoAnterior = $mesDiciembreAnterior->habilitados->first();
            $mesDiciembreAnterior->id_habilitado = $habilitadoAnterior?->id_habilitado;
            $mesDiciembreAnterior->habilitado = $habilitadoAnterior?->habilitado;
            $mesDiciembreAnterior->fecha_habilitado = $habilitadoAnterior?->fecha_habilitado;
            $mesDiciembreAnterior->observaciones_habilitado = $habilitadoAnterior?->observaciones_habilitado;
            $mesDiciembreAnterior->pago = $habilitadoAnterior?->pago;
            $coleccion = $coleccion->prepend($mesDiciembreAnterior);
        }

        // Si esta persona tiene un retro para un mes real (cualquiera, no
        // solo diciembre), ese retro reemplaza al mes normal correspondiente
        // a ese mismo mes real — mostrar ambos sería redundante, ya que el
        // retro ya cuenta la historia completa de ese mes.
        $mesesCubiertosPorRetro = $coleccion
            ->filter(fn($mes) => $mes->es_retroactivo)
            ->map(function ($mes) {
                [$año, $mesReal] = $this->fechaRealDeMes($mes);
                return "{$año}-{$mesReal}";
            });

        $coleccion = $coleccion->reject(
            fn($mes) => !$mes->es_retroactivo && $mesesCubiertosPorRetro->contains("{$mes->gestion->gestion}-{$mes->mes}")
        )->values();

        // Orden cronológico real (más reciente primero, como ya venía esta
        // vista): diciembre del año anterior es el mes más antiguo del
        // listado, así que va al final — no por el valor crudo de `mes`
        // (el retro usa códigos como 112 que ordenarían al inicio).
        $coleccion = $coleccion->sortByDesc(function ($mes) {
            [$año, $mesReal] = $this->fechaRealDeMes($mes);
            return sprintf('%04d-%02d', $año, $mesReal);
        })->values();

        if ($bajaDefinitiva) {
            $fechaBaja = Carbon::parse($bajaDefinitiva->fecha_inicio)->startOfMonth();
            $coleccion = $coleccion->filter(function ($item) use ($fechaBaja) {
                [$año, $mes] = $this->fechaRealDeMes($item);
                $anioMes = Carbon::create($año, $mes, 1)->startOfMonth();
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

        $coleccion = $coleccion->map(function ($item) use ($estadosHistoricos, $suspensionesPago, $boletasDescargadas, $pagosConHistorial, $fechaImplementacion) {
            [$año, $mes] = $this->fechaRealDeMes($item);
            $primerDiaMes = Carbon::create($año, $mes, 1)->startOfMonth();
            $ultimoDiaMes = Carbon::create($año, $mes, 1)->endOfMonth();

            $item->estado_mes = $estadosHistoricos
                ->filter(function ($estado) use ($primerDiaMes, $ultimoDiaMes) {
                    $inicio = Carbon::parse($estado->fecha_inicio);
                    $fin = $estado->fecha_fin ? Carbon::parse($estado->fecha_fin) : null;
                    return $inicio->lte($ultimoDiaMes)
                        && ($fin === null || $fin->gte($primerDiaMes));
                })
                ->sortByDesc('fecha_inicio')
                ->first()
                    ?->estado;

            // Independiente de estado_mes: si algún rango de pago_suspendido
            // cubre este mes, bloquea el cobro sin tocar el estado oficial.
            $item->pago_suspendido = $suspensionesPago->contains(function ($suspension) use ($primerDiaMes, $ultimoDiaMes) {
                $inicio = Carbon::parse($suspension->fecha_inicio);
                $fin = $suspension->fecha_fin ? Carbon::parse($suspension->fecha_fin) : null;
                return $inicio->lte($ultimoDiaMes)
                    && ($fin === null || $fin->gte($primerDiaMes));
            });

            $pagoId = $item->pago?->getKey();
            $tieneLogValido = isset($boletasDescargadas[$pagoId]);
            $tieneHistorial = isset($pagosConHistorial[$pagoId]);
            $esPagoAntiguo = $item->pago && Carbon::parse($item->pago->fecha_pago)->lt($fechaImplementacion);
            $pagoReseteado = $item->pago && $item->pago->pago == 0;

            $item->boleta_descargada = $item->pago && (
                $tieneLogValido || ($esPagoAntiguo && !$tieneHistorial && !$pagoReseteado)
            );

            return $item;
        });

        // Mismo criterio que en Gestion/index.vue: se excluyen solo las
        // gestiones que tienen algún mes pero les falta el mes=1.
        $gestiones = Gestion::select('gestion')
            ->where(function ($q) {
                $q->whereDoesntHave('meses', fn($mq) => $mq->where('es_retroactivo', false))
                    ->orWhereHas('meses', fn($mq) => $mq->where('es_retroactivo', false)->where('mes', 1));
            })
            ->orderBy('gestion', 'desc')
            ->distinct()
            ->get();

        // Meses que el picker de suspensión de pagos puede ofrecer: solo los
        // ya registrados (con PDF cargado) en la tabla `mes`, nunca un mes
        // arbitrario futuro.
        $mesesDisponiblesSuspension = Mes::with('gestion')
            ->where('es_retroactivo', false)
            ->get()
            ->map(fn($m) => ['gestion' => (int) $m->gestion->gestion, 'mes' => (int) $m->mes])
            ->values();

        // Meses que este beneficiario ya cobró — el picker los bloquea
        // (suspender un mes ya pagado no tiene efecto y confunde).
        $mesesPagadosSuspension = Habilitado::where('id_persona', $id)
            ->whereHas('pago', fn($q) => $q->where('pago', 1))
            ->with(['mes', 'gestion'])
            ->get()
            ->map(function ($h) {
                $esRetro = (bool) $h->mes->es_retroactivo;
                $mesReal = $esRetro ? $h->mes->mes_original : $h->mes->mes;
                $anioReal = ($esRetro && $mesReal == 12) ? $h->gestion->gestion - 1 : $h->gestion->gestion;
                return ['gestion' => (int) $anioReal, 'mes' => (int) $mesReal];
            })
            ->unique(fn($m) => "{$m['gestion']}-{$m['mes']}")
            ->values();

        return Inertia::render('Persona/listaHabilitados', [
            'datosHabilitado' => $coleccion->values(),
            'datosRecibo' => $persona,
            'gestiones' => $gestiones,
            'añoSeleccionado' => $gestionActual ?? (object) ['gestion' => $selectedYear],
            'filters' => ['año' => $selectedYear],
            'suspensionPagoActual' => $suspensionPagoActual,
            'mesesDisponiblesSuspension' => $mesesDisponiblesSuspension,
            'mesesPagadosSuspension' => $mesesPagadosSuspension,
        ]);
    }

    /**
     * Año y mes calendario real de una fila `mes`. Para meses normales es
     * directamente (gestión, mes). Para meses-retro, `mes` es el código
     * interno (ej. 112) — hay que usar `mes_original` (el mes calendario
     * real) y, si es diciembre, el año es el de la gestión ANTERIOR (el
     * mes-retro de diciembre siempre pertenece a la gestión anterior a la
     * que se cierra).
     */
    private function fechaRealDeMes($item): array
    {
        if ($item->es_retroactivo) {
            $año = $item->mes_original == 12 ? $item->gestion->gestion - 1 : $item->gestion->gestion;
            return [$año, $item->mes_original];
        }

        return [$item->gestion->gestion, $item->mes];
    }

    /**
     * Datos del habilitado NORMAL de esta persona para el mes real
     * correspondiente a un mes-retro — permite, desde la tarjeta de un
     * retroactivo ya deshabilitado, ofrecer un switch para volver a
     * gestionar el mes normal (que puede o no tener ya su propio habilitado).
     */
    private function datosMesNormalParaPersona(int $idGestion, int $mesOriginal, string $idPersona): ?object
    {
        $mesNormal = Mes::where('id_gestion', $idGestion)
            ->where('es_retroactivo', false)
            ->where('mes', $mesOriginal)
            ->first();

        if (!$mesNormal) {
            return null;
        }

        $habilitado = Habilitado::where('id_mes', $mesNormal->id_mes)
            ->where('id_persona', $idPersona)
            ->with('pago')
            ->first();

        return (object) [
            'id_mes' => $mesNormal->id_mes,
            'monto' => $mesNormal->monto,
            'id_habilitado' => $habilitado?->id_habilitado,
            'habilitado' => $habilitado?->habilitado,
            'observaciones_habilitado' => $habilitado?->observaciones_habilitado,
            'fecha_habilitado' => $habilitado?->fecha_habilitado,
            'pagado' => $habilitado?->pago ? ($habilitado->pago->pago ? 1 : 0) : null,
            'boleta' => $habilitado?->pago?->numero_boleta,
            'fecha_pago' => $habilitado?->pago?->fecha_pago,
        ];
    }

    /**
     * Mes "hermano" de uno dado: si es un mes-retro, su correspondiente mes
     * normal (misma gestión, mismo mes real); si es normal, su mes-retro
     * (si ya fue cargado). Null si el hermano todavía no existe.
     */
    private function mesHermano(Mes $mes): ?Mes
    {
        if ($mes->es_retroactivo) {
            return Mes::where('id_gestion', $mes->id_gestion)
                ->where('es_retroactivo', false)
                ->where('mes', $mes->mes_original)
                ->first();
        }

        return Mes::where('id_gestion', $mes->id_gestion)
            ->where('es_retroactivo', true)
            ->where('mes_original', $mes->mes)
            ->first();
    }

    /**
     * Habilitado "real" de una persona en un mes dado. Por un bug histórico
     * (doble clic antes de que el botón se deshabilitara durante el envío)
     * pueden existir varios registros duplicados para la misma persona/mes;
     * en ese caso se prioriza el que ya tiene un pago válido asociado,
     * porque ese es el que efectivamente se usó para cobrar y no puede
     * ignorarse en la verificación.
     */
    private function resolverHabilitadoDePersonaEnMes(string $idPersona, int $idMes): ?Habilitado
    {
        $habilitados = Habilitado::where('id_persona', $idPersona)
            ->where('id_mes', $idMes)
            ->with('pago')
            ->get();

        if ($habilitados->count() <= 1) {
            return $habilitados->first();
        }

        return $habilitados->first(fn($h) => $h->pago?->pago == 1)
            ?? $habilitados->sortByDesc('id_habilitado')->first();
    }

    /**
     * Un beneficiario nunca puede tener el mes normal y su retroactivo
     * habilitados (habilitado=1) al mismo tiempo — ver docs/diseno-retroactivos.md.
     * Lanza una excepción de validación (mismo mecanismo que $request->validate())
     * si el mes hermano ya está habilitado, para que se muestre por el sistema
     * de mensajes del frontend en vez de crear un estado inconsistente.
     */
    private function verificarNoHabilitadoEnMesHermano(Mes $mes, string $idPersona): void
    {
        $mesHermano = $this->mesHermano($mes);

        if (!$mesHermano) {
            return;
        }

        $habilitadoHermano = $this->resolverHabilitadoDePersonaEnMes($idPersona, $mesHermano->id_mes);

        if ($habilitadoHermano && $habilitadoHermano->habilitado) {
            throw ValidationException::withMessages([
                'habilitado' => $mes->es_retroactivo
                    ? 'No se puede habilitar el retroactivo: el mes normal de este beneficiario ya está habilitado. Debe deshabilitarlo primero.'
                    : 'No se puede habilitar el mes normal: ya existe un retroactivo habilitado para este beneficiario en este mes. Debe deshabilitarlo primero.',
            ]);
        }
    }

    /**
     * Nombre en español del mes real de una fila `mes` — usa `mes_original`
     * cuando es un mes-retro, porque `mes.mes` ahí es un código interno
     * (101-112), no un mes calendario.
     */
    private function nombreMesReal(Mes $mes): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $numeroMesReal = $mes->es_retroactivo ? $mes->mes_original : $mes->mes;

        return ucwords(strtolower($meses[$numeroMesReal] ?? 'Mes inválido'));
    }

    public function edit(Request $request, int $id)
    {
        $registro = Habilitado::findOrFail($id);

        // Validación básica
        $request->validate([
            'habilitado' => 'required|boolean',
        ]);

        if ($registro->gestion && $registro->gestion->estaCerrada()) {
            throw ValidationException::withMessages([
                'habilitado' => 'La caja de esta gestión ya está cerrada; no se pueden habilitar ni deshabilitar pagos.',
            ]);
        }

        if ($request->boolean('habilitado')) {
            $this->verificarNoHabilitadoEnMesHermano($registro->mes, $registro->id_persona);
        }

        // Asignar valores
        $valorAnterior = (bool) $registro->habilitado;
        $registro->habilitado = $request->input('habilitado');
        $registro->observaciones_habilitado = $request->input('observaciones_habilitado');

        // Guardar los cambios
        $registro->save();

        // Recuperar el registro completo recién actualizado
        $habilitadoActualizado = Habilitado::where('id_habilitado', $id)->first();

        $id_persona = $habilitadoActualizado->id_persona;
        $id_gestion = $habilitadoActualizado->id_gestion;

        $persona = Persona::find($id_persona);
        $nombrePersona = ucwords(strtolower("{$persona->nombre_persona} {$persona->apellido_persona}"));

        $mes = Mes::where('id_mes', $habilitadoActualizado->id_mes)->first();
        $gestion = Gestion::where('id_gestion', $id_gestion)->first();

        $ajustePresupuesto = $this->presupuestoMesService->ajustarPorHabilitado($mes, $valorAnterior, $request->boolean('habilitado'));

        $nombreMes = $this->nombreMesReal($mes);
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
                    'mes' => "{$nombreMes} {$gestion->gestion}",
                    'estado' => $request->input('habilitado') == 1 ? 'Habilitado' : 'Deshabilitado',
                    'observacion' => $request->input('observaciones_habilitado'),
                    'presupuesto_anterior' => $ajustePresupuesto['anterior'] ?? null,
                    'presupuesto_nuevo' => $ajustePresupuesto['nuevo'] ?? null,
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
    public function destroy(string $id)
    {
    }
}