<?php

namespace App\Http\Controllers;

use App\Models\Carnet;
use App\Models\Gestion;
use Illuminate\Support\Facades\Log;
use App\Models\Habilitado;
use App\Models\Mes;
use App\Models\Pago;
use App\Models\PagoAnulacion;
use App\Models\PagoSuspendido;
use App\Models\Persona;
use Exception;
use App\Models\User;
use App\Services\LogService;
use App\Services\PresupuestoMesService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Exports\NoPagadosExport;
use App\Exports\BajasExport;
use App\Exports\PagosIndividualExport;
use App\Exports\PagosTodosExport;
use App\Exports\ResumenGeneralExport;
use App\Exports\ArqueoCajaExport;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;

class PagoController extends Controller
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
        // Obtener el término de búsqueda desde la solicitud
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
        $id_habilitado = $request->id_habilitado;
        $user = Auth::user();
        $tipo = $request->tipo;

        try {
            DB::transaction(function () use ($request, $id_habilitado, $user, $tipo) {

                $habilitado = Habilitado::query()
                    ->join('gestion', 'habilitado.id_gestion', 'gestion.id_gestion')
                    ->join('mes', 'habilitado.id_mes', 'mes.id_mes')
                    ->join('persona', 'habilitado.id_persona', 'persona.id_persona')
                    ->leftJoin('tutor', 'persona.id_tutor', 'tutor.id_tutor')
                    ->where('habilitado.id_habilitado', '=', $id_habilitado)
                    ->select(
                        'persona.id_persona',
                        'persona.nombre_persona',
                        'persona.apellido_persona',
                        'persona.ci_persona',
                        'tutor.id_tutor',
                        'mes.monto',
                        'mes.mes',
                        'mes.es_retroactivo',
                        'mes.mes_original',
                        'gestion.gestion',
                        'gestion.caja_cerrada',
                        'habilitado.id_habilitado',
                        'habilitado.id_mes',
                        'habilitado.id_gestion'
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$habilitado) {
                    return response()->json(['error' => 'Habilitado no encontrado'], 404);
                }

                if ($habilitado->caja_cerrada) {
                    throw new Exception('La caja de esta gestión ya está cerrada; no se pueden registrar pagos.');
                }

                $pagoExistente = Pago::where('id_habilitado', $id_habilitado)->exists();

                if ($pagoExistente) {
                    throw new Exception('Este pago ya fue registrado.');
                }

                // Defensa en profundidad: el botón de cobro ya se oculta en
                // el frontend si el mes tiene una suspensión de pagos
                // vigente, pero se revalida acá por si el estado quedó
                // desactualizado en el navegador.
                $numeroMesReal = $habilitado->es_retroactivo ? $habilitado->mes_original : $habilitado->mes;
                $anioReal = ($habilitado->es_retroactivo && $numeroMesReal == 12)
                    ? $habilitado->gestion - 1
                    : $habilitado->gestion;

                $pagoSuspendido = PagoSuspendido::where('id_persona', $habilitado->id_persona)
                    ->vigenteEn(Carbon::create($anioReal, $numeroMesReal, 1))
                    ->exists();

                if ($pagoSuspendido) {
                    throw new Exception('Los pagos de este beneficiario están suspendidos; no se puede registrar el pago.');
                }

                $numeroBoleta = $this->generarNumeroBoleta(
                    $habilitado->id_mes,
                    $habilitado->id_gestion
                );

                $data = $request->all();
                $data['fecha_pago'] = now();
                $data['pago'] = '1';
                $data['tipo_pago'] = $tipo;
                $data['monto'] = $habilitado->monto;
                $data['numero_boleta'] = $numeroBoleta;
                $data['id'] = $user->id;

                $nombrePersona = ucwords(strtolower(
                    "{$habilitado->nombre_persona} {$habilitado->apellido_persona}"
                ));

                $pagoCreado = Pago::create($data);

                $periodo = $this->periodoLegible(
                    $habilitado->mes,
                    $habilitado->mes_original,
                    $habilitado->es_retroactivo,
                    $habilitado->gestion
                );

                $descripcion = "Pago de {$periodo} realizado a {$nombrePersona}.";

                // ─── Log compacto para alto volumen ──────────────────────────────
                $this->logService->logPayment(
                    $pagoCreado,
                    (object) [
                        'id' => $habilitado->id_habilitado,
                        'nombre' => $nombrePersona,
                        'monto' => $habilitado->monto,
                        'periodo' => $periodo,
                        'tipo_bono' => 'Efectivo',
                        'numero_boleta' => $numeroBoleta,
                        'ci' => $habilitado->ci_persona
                    ],
                    $descripcion
                );
            });
        } catch (Exception $e) {
            return back()->withErrors(['pago' => $e->getMessage()]);
        }
    }

    /**
     * Resuelve "NombreMes - Año" a partir de los datos crudos de `mes`,
     * manejando el caso de meses-retro (mes.mes es un código interno, no un
     * mes calendario — hay que usar mes_original) y el caso especial de
     * diciembre-retro (pertenece a la gestión ANTERIOR a la que se cierra).
     */
    private function periodoLegible($mes, $mesOriginal, $esRetroactivo, $gestion): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $numeroMesReal = $esRetroactivo ? $mesOriginal : $mes;
        $anioReal = ($esRetroactivo && $numeroMesReal == 12) ? $gestion - 1 : $gestion;
        $nombreMes = $meses[$numeroMesReal] ?? "Mes {$numeroMesReal}";

        return "{$nombreMes} - {$anioReal}";
    }

    private function generarNumeroBoleta(int $idMes, int $idGestion)
    {
        $abreviaciones = [
            1 => 'ENE',
            2 => 'FEB',
            3 => 'MAR',
            4 => 'ABR',
            5 => 'MAY',
            6 => 'JUN',
            7 => 'JUL',
            8 => 'AGO',
            9 => 'SEP',
            10 => 'OCT',
            11 => 'NOV',
            12 => 'DIC'
        ];

        $datos = DB::table('boleta_consecutivo')
            ->join('mes', 'boleta_consecutivo.id_mes', '=', 'mes.id_mes')
            ->join('gestion', 'boleta_consecutivo.id_gestion', '=', 'gestion.id_gestion')
            ->where('boleta_consecutivo.id_mes', $idMes)
            ->where('boleta_consecutivo.id_gestion', $idGestion)
            ->select(
                'mes.mes as numero_mes',
                'mes.es_retroactivo',
                'mes.mes_original',
                'gestion.gestion as anio',
                'boleta_consecutivo.ultimo_numero'
            )
            ->lockForUpdate()
            ->first();

        if (!$datos) {
            throw new Exception("No existe consecutivo para id_mes={$idMes} / id_gestion={$idGestion}.");
        }

        // Meses-retro: `mes.mes` es un código interno (ej. 112), no un mes
        // calendario — hay que usar `mes_original` para la abreviación, y si
        // es diciembre, el año real es el de la gestión ANTERIOR (diciembre
        // no pertenece a la gestión que se cierra).
        if ($datos->es_retroactivo) {
            $numeroMesReal = $datos->mes_original;
            $anioReal = $numeroMesReal == 12 ? $datos->anio - 1 : $datos->anio;
        } else {
            $numeroMesReal = $datos->numero_mes;
            $anioReal = $datos->anio;
        }

        if (!isset($abreviaciones[$numeroMesReal])) {
            throw new Exception("Mes inválido: {$numeroMesReal}");
        }

        $nuevoCorrelativo = (int) $datos->ultimo_numero + 1;

        DB::table('boleta_consecutivo')
            ->where('id_mes', $idMes)
            ->where('id_gestion', $idGestion)
            ->update(['ultimo_numero' => $nuevoCorrelativo]);

        $correlativo = str_pad($nuevoCorrelativo, 6, '0', STR_PAD_LEFT);
        // "RE" (no "RETRO") a propósito: el formato final del número de
        // boleta retro todavía no está confirmado (ver sección 8 del
        // diseño), y "RETRO" no cabe en pago.numero_boleta (VARCHAR 20).
        // Cuando se confirme el formato definitivo, ajustar aquí y evaluar
        // si hace falta ampliar la columna.
        $sufijoRetro = $datos->es_retroactivo ? '-RE' : '';

        return "{$abreviaciones[$numeroMesReal]}{$sufijoRetro}-{$anioReal}-{$correlativo}";
    }

    public function anular(Request $request, Pago $pago)
    {
        if ((int) $pago->pago === 0) {
            return response()->json(['message' => 'Este pago ya está anulado.'], 422);
        }

        $validated = $request->validate([
            'observaciones' => ['required', 'string', 'max:1000'],
        ], [
            'observaciones.required' => 'Debes indicar el motivo de la anulación.',
        ]);

        // Recuperar datos antes de anular (para el log)
        $habilitado = DB::table('habilitado')
            ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
            ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
            ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
            ->where('habilitado.id_habilitado', $pago->id_habilitado)
            ->select(
                'persona.nombre_persona',
                'persona.apellido_persona',
                'persona.ci_persona',
                'mes.mes',
                'mes.es_retroactivo',
                'mes.mes_original',
                'gestion.gestion',
                'gestion.caja_cerrada'
            )
            ->first();

        if ($habilitado && $habilitado->caja_cerrada) {
            return response()->json(['message' => 'La caja de esta gestión ya está cerrada; no se pueden anular pagos.'], 422);
        }

        $ajustePresupuesto = null;

        DB::transaction(function () use ($pago, $validated, &$ajustePresupuesto) {
            // 1. Anular el pago (el monto queda intacto para auditoría).
            $pago->update(['pago' => 0]);

            // 2. Volver a habilitar al beneficiario para ese mes
            $habilitadoRow = DB::table('habilitado')
                ->where('id_habilitado', $pago->id_habilitado)
                ->first();

            DB::table('habilitado')
                ->where('id_habilitado', $pago->id_habilitado)
                ->update(['habilitado' => 0]);

            // 2b. Ajustar el presupuesto del mes (resta monto por persona si
            // el habilitado realmente estaba activo antes de anular).
            if ($habilitadoRow) {
                $ajustePresupuesto = $this->presupuestoMesService->ajustarPorHabilitado(
                    Mes::find($habilitadoRow->id_mes),
                    (bool) $habilitadoRow->habilitado,
                    false
                );
            }

            // 3. Registrar el motivo de la anulación
            // Nota: User no tiene atributo/columna "name" (usa nombre +
            // apellido por separado, ver app/Models/User.php) — con ->name
            // esto guardaba siempre NULL en usuario_modificacion. Corregido
            // para que "quién anuló" sea visible en el frontend (Tarea 3).
            $usuarioActual = Auth::user();
            PagoAnulacion::create([
                'id_pago' => $pago->id_pago,
                'observaciones' => $validated['observaciones'],
                'usuario_modificacion' => $usuarioActual
                    ? trim("{$usuarioActual->nombre} {$usuarioActual->apellido}")
                    : null,
            ]);
        });

        $nombrePersona = $habilitado
            ? ucwords(strtolower("{$habilitado->nombre_persona} {$habilitado->apellido_persona}"))
            : '—';
        $periodo = $habilitado
            ? $this->periodoLegible($habilitado->mes, $habilitado->mes_original, $habilitado->es_retroactivo, $habilitado->gestion)
            : '—';

        $this->logService->log(
            'pago anulado',
            'BandejaPago',
            "Pago anulado del Beneficiario: {$nombrePersona}, N° Boleta: {$pago->numero_boleta}.",
            $pago,
            [
                'detalles' => [
                    'beneficiario' => $nombrePersona,
                    'mes' => $periodo,
                    'monto' => $pago->monto, // se conserva el monto real pagado
                    'numero_boleta' => $pago->numero_boleta,
                    'tipo_pago' => 'Anulación',
                    'ci' => $habilitado->ci_persona ?? '—',
                    'observaciones' => $validated['observaciones'],
                    'presupuesto_anterior' => $ajustePresupuesto['anterior'] ?? null,
                    'presupuesto_nuevo' => $ajustePresupuesto['nuevo'] ?? null,
                ],
            ]
        );

        return response()->json(['message' => 'Pago anulado correctamente.']);
    }

    /**
     * Revierte una anulación: repone pago=1 y habilitado=1, y elimina el
     * motivo guardado en pago_anulacion (ya no aplica).
     */
    public function reactivar(Pago $pago)
    {
        if ((int) $pago->pago === 1) {
            return response()->json(['message' => 'Este pago no está anulado.'], 422);
        }

        $habilitado = DB::table('habilitado')
            ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
            ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
            ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
            ->where('habilitado.id_habilitado', $pago->id_habilitado)
            ->select(
                'persona.nombre_persona',
                'persona.apellido_persona',
                'persona.ci_persona',
                'mes.mes',
                'mes.es_retroactivo',
                'mes.mes_original',
                'gestion.gestion',
                'gestion.caja_cerrada'
            )
            ->first();

        if ($habilitado && $habilitado->caja_cerrada) {
            return response()->json(['message' => 'La caja de esta gestión ya está cerrada; no se pueden reactivar pagos.'], 422);
        }

        $ajustePresupuesto = null;

        DB::transaction(function () use ($pago, &$ajustePresupuesto) {
            $pago->update(['pago' => 1]);

            $habilitadoRow = DB::table('habilitado')
                ->where('id_habilitado', $pago->id_habilitado)
                ->first();

            DB::table('habilitado')
                ->where('id_habilitado', $pago->id_habilitado)
                ->update(['habilitado' => 1]);

            if ($habilitadoRow) {
                $ajustePresupuesto = $this->presupuestoMesService->ajustarPorHabilitado(
                    Mes::find($habilitadoRow->id_mes),
                    (bool) $habilitadoRow->habilitado,
                    true
                );
            }

            PagoAnulacion::where('id_pago', $pago->id_pago)->delete();
        });

        $nombrePersona = $habilitado
            ? ucwords(strtolower("{$habilitado->nombre_persona} {$habilitado->apellido_persona}"))
            : '—';
        $periodo = $habilitado
            ? $this->periodoLegible($habilitado->mes, $habilitado->mes_original, $habilitado->es_retroactivo, $habilitado->gestion)
            : '—';

        $this->logService->log(
            'pago reactivado',
            'BandejaPago',
            "Pago reactivado del Beneficiario: {$nombrePersona}, N° Boleta: {$pago->numero_boleta}.",
            $pago,
            [
                'detalles' => [
                    'beneficiario' => $nombrePersona,
                    'mes' => $periodo,
                    'monto' => $pago->monto,
                    'numero_boleta' => $pago->numero_boleta,
                    'tipo_pago' => 'Reactivación',
                    'ci' => $habilitado->ci_persona ?? '—',
                    'presupuesto_anterior' => $ajustePresupuesto['anterior'] ?? null,
                    'presupuesto_nuevo' => $ajustePresupuesto['nuevo'] ?? null,
                ],
            ]
        );

        return response()->json(['message' => 'Pago reactivado correctamente.']);
    }

    public function descargarBoleta(Request $request)
    {
        Log::info("entra al controlador");
        $idPago = $request->input('id_pago');
        $gestion = $request->input('gestion');
        $mes = $request->input('mes');

        $pago = Pago::with('habilitado.persona')->findOrFail($idPago);

        /* $yaDescargada = \Spatie\Activitylog\Models\Activity::inLog('boleta_descarga')
            ->where('subject_type', Pago::class)
            ->where('subject_id', $idPago)
            ->where(function ($q) {
                $q->whereNull('properties->anulado')
                    ->orWhere('properties->anulado', false);
            })
            ->exists();

        if ($yaDescargada) {
            return back()->with('error', 'La boleta ya fue descargada anteriormente.');
        } */

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

        $nombreMes = $meses[(int) $mes] ?? 'Mes desconocido';

        $persona = $pago->habilitado->persona;
        $nombre = ucwords(strtolower("{$persona->nombre_persona} {$persona->apellido_persona}"));

        if ($pago->pago == 0) {
            $pago->pago = 1;
            $pago->save();
        }

        $this->logService->logBoletaDescarga(
            $pago,
            $idPago,
            $gestion,
            $nombreMes,
            $nombre
        );
    }


    public function imprimirBoleta(Request $request)
    {
        Log::info("entra al controlador Imprimir");
        $idPago = $request->input('id_pago');
        $gestion = $request->input('gestion');
        $mes = $request->input('mes');

        $pago = Pago::with('habilitado.persona')->findOrFail($idPago);

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

        $nombreMes = $meses[(int) $mes] ?? 'Mes desconocido';
        $persona = $pago->habilitado->persona;
        $nombre = ucwords(strtolower("{$persona->nombre_persona} {$persona->apellido_persona}"));

        if ($pago->pago == 0) {
            $pago->pago = 1;
            $pago->save();
        }

        $this->logService->logBoletaImpresion($pago, $idPago, $gestion, $nombreMes, $nombre);

        return response()->json(['ok' => true]);
    }

    public function resetearDescarga(int $idPago)
    {
        $pago = Pago::findOrFail($idPago);

        $logsActivos = \Spatie\Activitylog\Models\Activity::inLog('boleta_descarga')
            ->where('subject_type', Pago::class)
            ->where('subject_id', $idPago)
            ->where(function ($q) {
                $q->whereNull('properties->anulado')
                    ->orWhere('properties->anulado', false);
            })
            ->get();

        $fechaImplementacion = Carbon::parse('2026-04-15')->startOfDay();
        $esPagoAntiguo = Carbon::parse($pago->fecha_pago)->lt($fechaImplementacion);

        if ($logsActivos->isNotEmpty()) {
            // Caso normal: tiene log activo → anularlo
            foreach ($logsActivos as $log) {
                $properties = $log->properties->toArray();
                $properties['anulado'] = true;
                $properties['anulado_por'] = Auth::id();
                $properties['anulado_en'] = now()->toDateTimeString();
                $log->properties = $properties;
                $log->save();
            }

            return response()->json(['ok' => true, 'modo' => 'log_anulado']);
        }

        if ($esPagoAntiguo) {
            // Caso especial: pago antiguo sin log → poner pago = 0
            // para que el frontend lo trate como "no descargado"
            $pago->pago = 0;
            $pago->save();

            return response()->json(['ok' => true, 'modo' => 'pago_reseteado']);
        }

        return response()->json(['ok' => true, 'modo' => 'sin_cambios']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $encrypted_id)
    {
        // Desencriptar el ID
        try {
            $id = base64_decode($encrypted_id);

            if (!is_numeric($id)) {
                abort(404);
            }
        } catch (Exception $e) {
            abort(404);
        }

        $id_persona = $id;

        // Validar que existe la persona
        $persona = Persona::findOrFail($id_persona);

        // Obtener datos del tutor con relaciones
        $tutor = Persona::query()
            ->leftJoin('tutor', 'persona.id_tutor', '=', 'tutor.id_tutor')
            ->join('carnet', 'persona.id_persona', '=', 'carnet.id_persona')
            ->where('persona.id_persona', $id_persona)
            ->select([
                'tutor.id_tutor',
                'tutor.nombre_tutor',
                'tutor.apellido_tutor',
                'tutor.ci_tutor',
                'tutor.telefono',
                'tutor.direccion',
                'persona.nombre_persona',
                'persona.apellido_persona',
                'persona.ci_persona',
                'persona.distrito',
                'carnet.discapacidad'
            ])
            ->firstOrFail();

        // Query base para habilitados
        $habilitadoQuery = Habilitado::where('habilitado.id_persona', $id_persona)
            ->where('habilitado.habilitado', 1)
            ->join('gestion', 'habilitado.id_gestion', '=', 'gestion.id_gestion')
            ->leftjoin('pago', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
            ->join('mes', 'habilitado.id_mes', '=', 'mes.id_mes')
            ->select(
                'habilitado.*',
                'gestion.gestion',
                'gestion.id_gestion',
                'mes.mes',
                'mes.monto',
                'mes.id_mes',
                'pago.fecha_pago',
            )
            ->latest('habilitado.id_habilitado');

        // Obtener habilitados paginados (para la tabla)
        $habilitadoPaginado = $habilitadoQuery->clone()->paginate(15)->withQueryString();

        // Obtener habilitados sin paginar (para reportes/exportar)
        $habilitadoCompleto = $habilitadoQuery->clone()->get();

        // Renderizar vista con datos
        return Inertia::render('Pago/pagar', [
            'tutorTabla' => $tutor,
            'carnet' => Carnet::all(),
            'personaReporte' => Persona::all(),
            'habilitadoReporte' => Habilitado::all(),
            'gestion' => Gestion::all(),
            'pago' => Pago::all(),
            'id_persona' => $id_persona,
            'habilitado' => $habilitadoPaginado,
            'habilitadoCompleto' => $habilitadoCompleto,
            'persona' => $persona,
        ]);
    }

    public function reporteLog(Request $request)
    {
        $tipo = $request->query('tipo');
        $gestion = $request->query('gestion');

        match ($tipo) {
            'arqueo_general' => $this->logService->logReportPrint(
                'Reporte',
                'Reporte de Arqueo General',
                [
                    'gestión' => $gestion ?? '—',
                    'total meses' => $request->query('total_meses') ?? '—',
                ],
                null,
                "Se imprimió el reporte de arqueo general de la gestión {$gestion}.",
                'PDF'
            ),

            'arqueo_caja' => $this->logService->logReportPrint(
                'Reporte',
                'Reporte de Arqueo de Caja Mensual',
                [
                    'gestión' => $gestion ?? '—',
                    'mes' => $request->query('mes') ?? '—',
                    'total pagados' => $request->query('total_pagados') ?? '—',
                    'total no pagados' => $request->query('total_no_pagados') ?? '—',
                    'monto asignado' => 'Bs. ' . $request->query('monto_asignado') ?? '—',
                ],
                null,
                "Se imprimió el reporte de arqueo de caja del mes de {$request->query('mes')} de la gestión {$gestion}.",
                'PDF'
            ),

            default => null
        };
    }

    public function comp(Request $request)
    {
        $request->validate([
            'comprobante' => 'required|file|mimes:jpg,png,pdf|max:5120',
            'id_pago' => 'required|exists:pago,id_pago'
        ]);

        if ($request->hasFile('comprobante')) {
            $file = $request->file('comprobante');
            $extension = $file->getClientOriginalExtension();

            $fileName = 'comprobante-' . $request->id_pago . '-' . time() . '.' . $extension;
            $filePath = $file->storeAs('comprobantes', $fileName, 'public');
            $nombreArchivo = $fileName;

            $pago = Pago::findOrFail($request->id_pago);
            $pago->update([
                'comprobante' => $nombreArchivo,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comprobante subido correctamente',
                'file_name' => $nombreArchivo,
                'file_url' => asset('storage/comprobantes/' . $nombreArchivo)
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se encontró el archivo'
        ], 400);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function bandejaPago(Request $request)
    {
        $user = Auth::user();
        $idUser = "{$user->id}";

        $year = $request->input('gestion_gestion');
        $mes = $request->input('mes');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');
        $distrito = $request->input('distrito');
        $estadoBaja = $request->input('estado_baja');
        // Switch "Anulados" del tab Total Pagados: por defecto oculta los
        // pagos anulados tanto de la tabla como del reporte PDF/Excel
        // (mismo dataset, ver $pagosTodos / $pagosTodosExport más abajo).
        $incluirAnulados = $request->boolean('incluir_anulados');

        $resumenGeneral = collect([]);
        $totalesResumen = ['total_beneficiarios' => 0, 'total_monto' => 0];
        $mesesNumeros = collect([]);
        $misPagos = collect([]);
        $pagosTodos = collect([]);
        $misPagosTodos = collect([]);
        $montoTotalMisPagos = 0;
        $misPresupuestoAsignado = 0;
        $noPagados = collect([]);
        $cantidadNoPagados = 0;
        $bajas = collect([]);
        $cantidadBajas = 0;
        $noPagadosExport = collect([]);
        $bajasExport = collect([]);
        $montoNoPagados = 0;
        $montoBajas = 0;

        $gestiones = Gestion::select('gestion as anio', 'retroactivos_habilitado')
            ->distinct()
            ->orderBy('gestion', 'desc')
            ->get();

        if ($year) {
            $gestion = Gestion::where('gestion', $year)->first();
            if ($gestion) {
                $mesesNumeros = Mes::where('id_gestion', $gestion->id_gestion)
                    ->where('es_retroactivo', false)
                    ->pluck('mes')->sort()->values();
            }
        }

        $usuarioTienePagos = false;
        $verRetro = $request->boolean('ver_retro');
        $mesRetroInfo = null;

        // Subquery reutilizable: último estado de cada persona hasta una fecha de corte
        $ultimoHistorial = function ($fechaCorte) {
            return DB::table('historial_estados as he')
                ->select('he.id_persona', 'he.estado', 'he.observaciones')
                ->where('he.fecha_inicio', '<=', $fechaCorte)
                ->whereNotExists(function ($sub) use ($fechaCorte) {
                    $sub->select(DB::raw(1))
                        ->from('historial_estados as he2')
                        ->whereColumn('he2.id_persona', 'he.id_persona')
                        ->where('he2.fecha_inicio', '<=', $fechaCorte)
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

        // Filtro reutilizable: solo el registro "correcto" de habilitado por persona+mes
        // (prioriza el que tiene pago válido, luego el habilitado=1, luego el más reciente)
        $habilitadoUnico = "habilitado.id_habilitado = (
            SELECT h2.id_habilitado
            FROM habilitado h2
            WHERE h2.id_persona = habilitado.id_persona
            AND h2.id_mes = habilitado.id_mes
            ORDER BY
                (SELECT COUNT(*) FROM pago pg WHERE pg.id_habilitado = h2.id_habilitado AND pg.pago = 1) DESC,
                h2.habilitado DESC,
                h2.id_habilitado DESC
            LIMIT 1
        )";

        // ── Búsqueda por Gestión + Mes ────────────────────────────────────────
        if ($year && $mes) {
            $gestion = Gestion::where('gestion', $year)->first();
            if ($gestion) {
                // "Ver retro": en vez del mes normal, filtrar por el mes-retro
                // (mes.mes es un código interno como 101-112) cuyo
                // mes_original coincide con el mes elegido. $mes sigue
                // representando el mes calendario real (1-12) elegido en el
                // selector — se usa tal cual para fecha_fin_mes/labels;
                // $mesFiltro es el valor real de `mes.mes` a buscar en las
                // consultas (retro o normal según corresponda).
                $mesFiltro = $mes;

                if ($verRetro) {
                    $mesRetroInfo = Mes::where('id_gestion', $gestion->id_gestion)
                        ->where('es_retroactivo', true)
                        ->where('mes_original', $mes)
                        ->first();

                    // Si todavía no se cargó el retro de este mes, no hay
                    // nada que mostrar — se usa un valor de mes imposible
                    // para que todas las consultas de abajo devuelvan vacío
                    // sin necesidad de tocar cada una por separado.
                    $mesFiltro = $mesRetroInfo->mes ?? -1;
                }

                $usuarios = User::select(
                    'users.id',
                    'users.nombre',
                    'users.apellido',
                    'users.cargo',
                    DB::raw('COUNT(pago.id_pago) as cantidad_pagos'),
                    DB::raw('MAX(pago.monto) as monto'),
                    DB::raw('SUM(pago.monto) as monto_total'),
                    DB::raw('COALESCE(presupuesto_usuario.monto_asignado, 0) as presupuesto_asignado')
                )
                    ->join('pago', 'users.id', '=', 'pago.id')
                    ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                    ->join('mes', function ($join) use ($gestion, $mesFiltro) {
                        $join->on('mes.id_mes', '=', 'habilitado.id_mes')
                            ->where('mes.mes', '=', $mesFiltro)
                            ->where('mes.id_gestion', '=', $gestion->id_gestion);
                    })
                    // Presupuesto asignado a este cajero para este mes+gestión
                    // (tabla nueva `presupuesto_usuario`, informativo — ver
                    // PresupuestoController). 1:1 por el único compuesto de esa
                    // tabla, no genera fan-out en el groupBy de abajo.
                    ->leftJoin('presupuesto_usuario', function ($join) use ($gestion) {
                        $join->on('presupuesto_usuario.id_usuario', '=', 'users.id')
                            ->on('presupuesto_usuario.id_mes', '=', 'mes.id_mes')
                            ->where('presupuesto_usuario.id_gestion', '=', $gestion->id_gestion);
                    })
                    ->where('pago.pago', 1)
                    ->groupBy('users.id', 'users.nombre', 'users.apellido', 'users.cargo', 'presupuesto_usuario.monto_asignado')
                    ->orderBy('cantidad_pagos', 'desc')
                    ->paginate(30, ['*'], 'page_resumen')->appends($request->query());

                $resumenGeneral = $usuarios;
                $totalesResumen = [
                    'total_beneficiarios' => $usuarios->sum('cantidad_pagos'),
                    'total_monto' => $usuarios->sum('monto_total'),
                ];

                $pagosPorUsuario = DB::table('pago')
                    ->select(
                        'pago.id as user_id',
                        'persona.nombre_persona',
                        'persona.apellido_persona',
                        'persona.ci_persona',
                        'persona.distrito',
                        'mes.mes',
                        'gestion.gestion',
                        'pago.monto',
                        'pago.pago',
                        'pago.numero_boleta',
                        DB::raw('DATE(pago.fecha_pago) as fecha_pago'),
                    )
                    ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                    ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
                    ->join('mes', function ($join) use ($gestion, $mesFiltro) {
                        $join->on('mes.id_mes', '=', 'habilitado.id_mes')
                            ->where('mes.mes', '=', $mesFiltro)
                            ->where('mes.id_gestion', '=', $gestion->id_gestion);
                    })
                    ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                    ->orderBy('pago.fecha_pago', 'asc')
                    ->get()
                    ->groupBy('user_id');

                $montoTotalMisPagos = DB::table('pago')
                    ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                    ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                    ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                    ->where('pago.id', $idUser)
                    ->where('mes.mes', $mesFiltro)
                    ->where('mes.id_gestion', $gestion->id_gestion)
                    ->where('pago.pago', 1)
                    ->sum('pago.monto');

                // Presupuesto asignado a este cajero para este mes+gestión
                // (tabla `presupuesto_usuario`, informativo — mismo dato que
                // ya se usa en el resumen general de todos los cajeros).
                $misPresupuestoAsignado = DB::table('presupuesto_usuario')
                    ->join('mes', 'mes.id_mes', '=', 'presupuesto_usuario.id_mes')
                    ->where('presupuesto_usuario.id_usuario', $idUser)
                    ->where('mes.mes', $mesFiltro)
                    ->where('presupuesto_usuario.id_gestion', $gestion->id_gestion)
                    ->value('presupuesto_usuario.monto_asignado') ?? 0;

                $misPagos = DB::table('pago')
                    ->select(
                        'persona.nombre_persona',
                        'persona.apellido_persona',
                        'persona.ci_persona',
                        'persona.distrito',
                        'mes.mes',
                        'gestion.gestion',
                        'pago.monto',
                        'pago.pago',
                        DB::raw('DATE(pago.fecha_pago) as fecha_pago'),
                        'pago.numero_boleta'
                    )
                    ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                    ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
                    ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                    ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                    ->where('pago.id', $idUser)
                    ->where('mes.mes', $mesFiltro)
                    ->where('mes.id_gestion', $gestion->id_gestion)
                    ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                    ->orderBy('pago.fecha_pago', 'asc')
                    ->paginate(150, ['*'], 'page_mis_pagos')->appends($request->query());

                $misPagosTodos = DB::table('pago')
                    ->select(
                        'persona.nombre_persona',
                        'persona.apellido_persona',
                        'persona.ci_persona',
                        'persona.distrito',
                        'mes.mes',
                        'pago.id_pago',
                        'gestion.gestion',
                        'pago.monto',
                        'pago.pago',
                        DB::raw('DATE(pago.fecha_pago) as fecha_pago'),
                        'pago.numero_boleta'
                    )
                    ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                    ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
                    ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                    ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                    ->where('pago.id', $idUser)
                    ->where('mes.mes', $mesFiltro)
                    ->where('mes.id_gestion', $gestion->id_gestion)
                    ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                    ->orderBy('pago.fecha_pago', 'asc')
                    ->get();

                $pagosTodos = DB::table('pago')
                    ->select(
                        'persona.nombre_persona',
                        'persona.apellido_persona',
                        'persona.ci_persona',
                        'persona.distrito',
                        'mes.mes',
                        'mes.es_retroactivo',
                        'mes.mes_original',
                        'gestion.gestion',
                        'pago.id_pago',
                        'pago.monto',
                        'pago.pago',
                        'pago.numero_boleta',
                        DB::raw('DATE(pago.fecha_pago) as fecha_pago'),
                        DB::raw("CONCAT(users.nombre, ' ', users.apellido) as usuario_pagador"),
                        // Solo relevante para pagos normales anulados: si existe un
                        // pago-retro válido para la misma persona/mes real, se
                        // muestra como informativo (badge "Pagado retroactivo").
                        DB::raw("(CASE WHEN mes.es_retroactivo = 1 THEN 0 ELSE (SELECT EXISTS (
                            SELECT 1 FROM habilitado h2
                            JOIN mes m2 ON m2.id_mes = h2.id_mes
                            JOIN gestion g2 ON g2.id_gestion = m2.id_gestion
                            JOIN pago p2 ON p2.id_habilitado = h2.id_habilitado
                            WHERE h2.id_persona = persona.id_persona
                            AND m2.es_retroactivo = 1
                            AND m2.mes_original = mes.mes
                            AND p2.pago = 1
                            AND (CASE WHEN m2.mes_original = 12 THEN g2.gestion - 1 ELSE g2.gestion END) = gestion.gestion
                        )) END) as tiene_retro_valido")
                    )
                    ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                    ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
                    ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                    ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                    ->join('users', 'users.id', '=', 'pago.id')
                    ->where('mes.mes', $mesFiltro)
                    ->where('mes.id_gestion', $gestion->id_gestion)
                    ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                    ->when(!$incluirAnulados, fn($q) => $q->where('pago.pago', 1))
                    ->orderBy('pago.numero_boleta', 'desc')
                    ->paginate(150, ['*'], 'page_todos')->appends($request->query());

                $pagosTodosExport = DB::table('pago')
                    ->select(
                        'persona.nombre_persona',
                        'persona.apellido_persona',
                        'persona.ci_persona',
                        'persona.distrito',
                        'mes.mes',
                        'gestion.gestion',
                        'pago.id_pago',
                        'pago.monto',
                        'pago.pago',
                        'pago.numero_boleta',
                        DB::raw('DATE(pago.fecha_pago) as fecha_pago'),
                        DB::raw("CONCAT(users.nombre, ' ', users.apellido) as usuario_pagador")
                    )
                    ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                    ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
                    ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                    ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                    ->join('users', 'users.id', '=', 'pago.id')
                    ->where('mes.mes', $mesFiltro)
                    ->where('mes.id_gestion', $gestion->id_gestion)
                    ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                    ->when(!$incluirAnulados, fn($q) => $q->where('pago.pago', 1))
                    ->orderBy('pago.numero_boleta', 'asc')
                    ->get();

                $montoTotalPagosTodos = DB::table('pago')
                    ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                    ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
                    ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                    ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                    ->where('mes.mes', $mesFiltro)
                    ->where('mes.id_gestion', $gestion->id_gestion)
                    ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                    ->where('pago.pago', 1)
                    ->sum('pago.monto');

                // NUEVO: conteo de pagos válidos (excluye anulados)
                $cantidadPagosTodosValidos = DB::table('pago')
                    ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                    ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
                    ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                    ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                    ->where('mes.mes', $mesFiltro)
                    ->where('mes.id_gestion', $gestion->id_gestion)
                    ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                    ->where('pago.pago', 1)
                    ->count();

                // Diciembre-retro representa diciembre del año ANTERIOR a la
                // gestión que se cierra (el mes-retro de diciembre no vive
                // en esta gestión) — el resto de los meses usa el año de la
                // gestión seleccionada tal cual.
                $anioRealMes = ($verRetro && $mesRetroInfo && $mes == 12) ? $gestion->gestion - 1 : $gestion->gestion;
                $fechaFinMes = date('Y-m-t', strtotime($anioRealMes . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '-01'));
                $montoMes = Mes::where('id_gestion', $gestion->id_gestion)->where('mes', $mesFiltro)->value('monto') ?? 0;

                // Para el tab "Bajas" en retro, la baja no se deriva del historial
                // general (eso arrastraría bajas de cualquier momento, sin relación
                // con el Excel de retro cargado ese mes) sino de retroactivo_evaluacion,
                // que solo guarda exclusiones para ESTE mes-retro (automáticas o
                // correcciones manuales) — mismo criterio que
                // PersonaController::reporte(). Se le da la misma forma
                // (id_persona, estado, observaciones) que $ultimoHistorial() para
                // reusar las consultas de abajo sin duplicarlas.
                $bajaSource = $verRetro
                    ? DB::table('retroactivo_evaluacion as re')
                        ->select(
                            're.id_persona',
                            DB::raw("COALESCE(re.estado_baja, 'baja_definitiva') as estado"),
                            DB::raw("CASE COALESCE(re.estado_baja, 'baja_definitiva')
                                WHEN 'baja_temporal' THEN 'PADRE FUNCIONARIO TRABAJANDO CON ITEM'
                                ELSE 'FALLECIDO'
                            END as observaciones")
                        )
                        ->where('re.id_gestion', $gestion->id_gestion)
                        ->where('re.mes_original', $mes)
                        ->where(function ($q) {
                            $q->whereNotNull('re.estado_baja')->orWhere('re.es_correccion_manual', true);
                        })
                    : $ultimoHistorial($fechaFinMes);

                // ── NUEVO: No Pagados ───────────────────────────────────────
                $noPagados = DB::table('habilitado')
                    ->select(
                        'persona.nombre_persona',
                        'persona.apellido_persona',
                        'persona.nombre_completo',
                        'persona.ci_persona',
                        'persona.distrito',
                        'habilitado.id_habilitado',
                        'habilitado.observaciones_habilitado',
                        DB::raw('DATE(habilitado.fecha_habilitado) as fecha_habilitado'),
                        DB::raw("UPPER(REGEXP_REPLACE(
                            COALESCE(
                                CONCAT(TRIM(persona.apellido_persona), ' ', TRIM(persona.nombre_persona)),
                                persona.nombre_completo
                            ),
                            ' +', ' '
                        )) as nombre_orden"),
                    )
                    ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
                    ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                    ->leftJoin('pago', function ($join) {
                        $join->on('pago.id_habilitado', '=', 'habilitado.id_habilitado')
                            ->where('pago.pago', 1);
                    })
                    ->leftJoinSub($ultimoHistorial($fechaFinMes), 'ultimo_estado', function ($join) {
                        $join->on('persona.id_persona', '=', 'ultimo_estado.id_persona');
                    })
                    ->whereRaw($habilitadoUnico)
                    ->where('mes.mes', $mesFiltro)
                    ->where('mes.id_gestion', $gestion->id_gestion)
                    ->whereNull('pago.id_pago')
                    ->where('habilitado.habilitado', 1)
                    ->whereRaw("(ultimo_estado.estado IS NULL OR ultimo_estado.estado NOT IN ('baja_temporal', 'baja_definitiva'))")
                    ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                    ->orderByRaw('nombre_orden COLLATE utf8mb4_spanish_ci ASC')
                    ->paginate(150, ['*'], 'page_no_pagados')->appends($request->query());

                $cantidadNoPagados = DB::table('habilitado')
                    ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                    ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
                    ->leftJoin('pago', function ($join) {
                        $join->on('pago.id_habilitado', '=', 'habilitado.id_habilitado')
                            ->where('pago.pago', 1);
                    })
                    ->leftJoinSub($ultimoHistorial($fechaFinMes), 'ultimo_estado', function ($join) {
                        $join->on('persona.id_persona', '=', 'ultimo_estado.id_persona');
                    })
                    ->whereRaw($habilitadoUnico)
                    ->where('mes.mes', $mesFiltro)
                    ->where('mes.id_gestion', $gestion->id_gestion)
                    ->whereNull('pago.id_pago')
                    ->where('habilitado.habilitado', 1)
                    ->whereRaw("(ultimo_estado.estado IS NULL OR ultimo_estado.estado NOT IN ('baja_temporal', 'baja_definitiva'))")
                    ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                    ->count();

                $montoNoPagados = $cantidadNoPagados * $montoMes;

                // ── Bajas (temporal + definitiva) ────────────────────
                $bajas = DB::table('persona')
                    ->joinSub($bajaSource, 'ultimo_estado', function ($join) {
                        $join->on('persona.id_persona', '=', 'ultimo_estado.id_persona');
                    })
                    ->whereIn('ultimo_estado.estado', ['baja_temporal', 'baja_definitiva'])
                    ->when($estadoBaja, fn($q) => $q->where('ultimo_estado.estado', $estadoBaja)) // NUEVO
                    ->select(
                        'persona.nombre_persona',
                        'persona.apellido_persona',
                        'persona.nombre_completo',
                        'persona.ci_persona',
                        'persona.distrito',
                        'ultimo_estado.estado',
                        'ultimo_estado.observaciones',
                        DB::raw((float) $montoMes . " as monto"),
                        DB::raw("UPPER(REGEXP_REPLACE(
                        COALESCE(
                            CONCAT(TRIM(persona.apellido_persona), ' ', TRIM(persona.nombre_persona)),
                            persona.nombre_completo
                            ),
                            ' +', ' '
                        )) as nombre_orden"),
                    )
                    ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                    ->orderByRaw('nombre_orden COLLATE utf8mb4_spanish_ci ASC')
                    ->paginate(150, ['*'], 'page_bajas')->appends($request->query());

                $cantidadBajas = DB::table('persona')
                    ->joinSub($bajaSource, 'ultimo_estado', function ($join) {
                        $join->on('persona.id_persona', '=', 'ultimo_estado.id_persona');
                    })
                    ->whereIn('ultimo_estado.estado', ['baja_temporal', 'baja_definitiva'])
                    ->when($estadoBaja, fn($q) => $q->where('ultimo_estado.estado', $estadoBaja)) // NUEVO
                    ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                    ->count();

                $noPagadosExport = DB::table('habilitado')
                    ->select(
                        'persona.nombre_persona',
                        'persona.apellido_persona',
                        'persona.nombre_completo',
                        'persona.ci_persona',
                        'persona.distrito',
                        'habilitado.observaciones_habilitado',
                        'mes.monto',
                        DB::raw("UPPER(REGEXP_REPLACE(
                        COALESCE(
                            CONCAT(TRIM(persona.apellido_persona), ' ', TRIM(persona.nombre_persona)),
                            persona.nombre_completo
                            ),
                            ' +', ' '
                        )) as nombre_orden"),
                        DB::raw('DATE(habilitado.fecha_habilitado) as fecha_habilitado')
                    )
                    ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
                    ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                    ->leftJoin('pago', function ($join) {
                        $join->on('pago.id_habilitado', '=', 'habilitado.id_habilitado')
                            ->where('pago.pago', 1);
                    })
                    ->leftJoinSub($ultimoHistorial($fechaFinMes), 'ultimo_estado', fn($j) => $j->on('persona.id_persona', '=', 'ultimo_estado.id_persona'))
                    ->whereRaw($habilitadoUnico)
                    ->where('mes.mes', $mesFiltro)->where('mes.id_gestion', $gestion->id_gestion)
                    ->whereNull('pago.id_pago')->where('habilitado.habilitado', 1)
                    ->whereRaw("(ultimo_estado.estado IS NULL OR ultimo_estado.estado NOT IN ('baja_temporal', 'baja_definitiva'))")
                    ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                    ->orderByRaw('nombre_orden COLLATE utf8mb4_spanish_ci ASC')->get();

                $bajasExport = DB::table('persona')
                    ->joinSub($bajaSource, 'ultimo_estado', fn($j) => $j->on('persona.id_persona', '=', 'ultimo_estado.id_persona'))
                    ->whereIn('ultimo_estado.estado', ['baja_temporal', 'baja_definitiva'])
                    ->when($estadoBaja, fn($q) => $q->where('ultimo_estado.estado', $estadoBaja)) // NUEVO
                    ->select(
                        'persona.nombre_persona',
                        'persona.apellido_persona',
                        'persona.nombre_completo',
                        'persona.ci_persona',
                        'persona.distrito',
                        'ultimo_estado.estado',
                        'ultimo_estado.observaciones',
                        DB::raw((float) $montoMes . " as monto"),
                        DB::raw("UPPER(REGEXP_REPLACE(
            COALESCE(
                CONCAT(TRIM(persona.apellido_persona), ' ', TRIM(persona.nombre_persona)),
                persona.nombre_completo
                ),
                ' +', ' '
            )) as nombre_orden")
                    )
                    ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                    ->orderByRaw('nombre_orden COLLATE utf8mb4_spanish_ci ASC')->get();

                $montoBajas = $cantidadBajas * $montoMes;
            }

            $usuarioTienePagos = DB::table('pago')
                ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                ->where('pago.id', $idUser)
                ->where('mes.mes', $mesFiltro)
                ->where('mes.id_gestion', $gestion->id_gestion)
                ->where('pago.pago', 1)
                ->exists();
        }

        // ── Búsqueda por Rango de Meses ───────────────────────────────────────
        elseif ($fechaDesde && $fechaHasta) {

            $desdeYM = date('Ym', strtotime($fechaDesde));
            $hastaYM = date('Ym', strtotime($fechaHasta));

            $usuarios = User::select(
                'users.id',
                'users.nombre',
                'users.apellido',
                'users.cargo',
                DB::raw('COUNT(pago.id_pago) as cantidad_pagos'),
                DB::raw('MAX(pago.monto) as monto'),
                DB::raw('SUM(pago.monto) as monto_total')
            )
                ->join('pago', 'users.id', '=', 'pago.id')
                ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                ->whereRaw(
                    "CONCAT(gestion.gestion, LPAD(mes.mes, 2, '0')) BETWEEN ? AND ?",
                    [$desdeYM, $hastaYM]
                )
                ->where('pago.pago', 1)
                ->groupBy('users.id', 'users.nombre', 'users.apellido', 'users.cargo')
                ->orderBy('cantidad_pagos', 'desc')
                ->paginate(50, ['*'], 'page_resumen')->appends($request->query());

            $resumenGeneral = $usuarios;
            $totalesResumen = [
                'total_beneficiarios' => $usuarios->sum('cantidad_pagos'),
                'total_monto' => $usuarios->sum('monto_total'),
            ];

            $pagosPorUsuario = DB::table('pago')
                ->select(
                    'pago.id as user_id',
                    'persona.nombre_persona',
                    'persona.apellido_persona',
                    'persona.ci_persona',
                    'persona.distrito',
                    'mes.mes',
                    'gestion.gestion',
                    'pago.monto',
                    'pago.pago',
                    'pago.numero_boleta',
                    DB::raw('DATE(pago.fecha_pago) as fecha_pago'),
                )
                ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
                ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                ->whereRaw(
                    "CONCAT(gestion.gestion, LPAD(mes.mes, 2, '0')) BETWEEN ? AND ?",
                    [$desdeYM, $hastaYM]
                )
                ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                ->orderBy('pago.fecha_pago', 'asc')
                ->get()
                ->groupBy('user_id');

            $montoTotalMisPagos = DB::table('pago')
                ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                ->where('pago.id', $idUser)
                ->whereRaw(
                    "CONCAT(gestion.gestion, LPAD(mes.mes, 2, '0')) BETWEEN ? AND ?",
                    [$desdeYM, $hastaYM]
                )
                ->where('pago.pago', 1)
                ->sum('pago.monto');

            $misPagos = DB::table('pago')
                ->select(
                    'persona.nombre_persona',
                    'persona.apellido_persona',
                    'persona.ci_persona',
                    'persona.distrito',
                    'mes.mes',
                    'gestion.gestion',
                    'pago.monto',
                    'pago.pago',
                    'pago.numero_boleta',
                    DB::raw('DATE(pago.fecha_pago) as fecha_pago'),
                )
                ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
                ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                ->where('pago.id', $idUser)
                ->whereRaw(
                    "CONCAT(gestion.gestion, LPAD(mes.mes, 2, '0')) BETWEEN ? AND ?",
                    [$desdeYM, $hastaYM]
                )
                ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                ->orderBy('pago.fecha_pago', 'asc')
                ->paginate(50, ['*'], 'page_mis_pagos')->appends($request->query());

            $misPagosTodos = DB::table('pago')
                ->select(
                    'persona.nombre_persona',
                    'persona.apellido_persona',
                    'persona.ci_persona',
                    'persona.distrito',
                    'mes.mes',
                    'gestion.gestion',
                    'pago.id_pago',
                    'pago.monto',
                    'pago.pago',
                    'pago.numero_boleta',
                    DB::raw('DATE(pago.fecha_pago) as fecha_pago'),
                )
                ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
                ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                ->where('pago.id', $idUser)
                ->whereRaw(
                    "CONCAT(gestion.gestion, LPAD(mes.mes, 2, '0')) BETWEEN ? AND ?",
                    [$desdeYM, $hastaYM]
                )
                ->orderBy('pago.numero_boleta', 'asc')
                ->get();

            $pagosTodos = DB::table('pago')
                ->select(
                    'persona.nombre_persona',
                    'persona.apellido_persona',
                    'persona.ci_persona',
                    'persona.distrito',
                    'mes.mes',
                    'gestion.gestion',
                    'pago.id_pago',
                    'pago.monto',
                    'pago.pago',
                    'pago.numero_boleta',
                    DB::raw('DATE(pago.fecha_pago) as fecha_pago'),
                    DB::raw("CONCAT(users.nombre, ' ', users.apellido) as usuario_pagador"),
                    // Solo relevante para pagos normales anulados: si existe un
                    // pago-retro válido para la misma persona/mes real, se
                    // muestra como informativo (badge "Pagado retroactivo").
                    DB::raw("(CASE WHEN mes.es_retroactivo = 1 THEN 0 ELSE (SELECT EXISTS (
                        SELECT 1 FROM habilitado h2
                        JOIN mes m2 ON m2.id_mes = h2.id_mes
                        JOIN gestion g2 ON g2.id_gestion = m2.id_gestion
                        JOIN pago p2 ON p2.id_habilitado = h2.id_habilitado
                        WHERE h2.id_persona = persona.id_persona
                        AND m2.es_retroactivo = 1
                        AND m2.mes_original = mes.mes
                        AND p2.pago = 1
                        AND (CASE WHEN m2.mes_original = 12 THEN g2.gestion - 1 ELSE g2.gestion END) = gestion.gestion
                    )) END) as tiene_retro_valido")
                )
                ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
                ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                ->join('users', 'users.id', '=', 'pago.id')
                ->whereRaw(
                    "CONCAT(gestion.gestion, LPAD(mes.mes, 2, '0')) BETWEEN ? AND ?",
                    [$desdeYM, $hastaYM]
                )
                ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                ->when(!$incluirAnulados, fn($q) => $q->where('pago.pago', 1))
                ->orderBy('pago.numero_boleta', 'desc')
                ->paginate(150, ['*'], 'page_todos')->appends($request->query());

            $pagosTodosExport = DB::table('pago')
                ->select(
                    'persona.nombre_persona',
                    'persona.apellido_persona',
                    'persona.ci_persona',
                    'persona.distrito',
                    'mes.mes',
                    'gestion.gestion',
                    'pago.id_pago',
                    'pago.monto',
                    'pago.pago',
                    'pago.numero_boleta',
                    DB::raw('DATE(pago.fecha_pago) as fecha_pago'),
                    DB::raw("CONCAT(users.nombre, ' ', users.apellido) as usuario_pagador")
                )
                ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
                ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                ->join('users', 'users.id', '=', 'pago.id')
                ->whereRaw(
                    "CONCAT(gestion.gestion, LPAD(mes.mes, 2, '0')) BETWEEN ? AND ?",
                    [$desdeYM, $hastaYM]
                )
                ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                ->when(!$incluirAnulados, fn($q) => $q->where('pago.pago', 1))
                ->orderBy('pago.numero_boleta', 'asc')
                ->get();

            $montoTotalPagosTodos = DB::table('pago')
                ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
                ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                ->whereRaw(
                    "CONCAT(gestion.gestion, LPAD(mes.mes, 2, '0')) BETWEEN ? AND ?",
                    [$desdeYM, $hastaYM]
                )
                ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                ->where('pago.pago', 1)
                ->sum('pago.monto');

            // NUEVO: conteo de pagos válidos (excluye anulados)
            $cantidadPagosTodosValidos = DB::table('pago')
                ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
                ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                ->whereRaw(
                    "CONCAT(gestion.gestion, LPAD(mes.mes, 2, '0')) BETWEEN ? AND ?",
                    [$desdeYM, $hastaYM]
                )
                ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                ->where('pago.pago', 1)
                ->count();

            // ── NUEVO: No Pagados (modo rango) ───────────────────────────
            $noPagados = DB::table('habilitado')
                ->select(
                    'persona.nombre_persona',
                    'persona.apellido_persona',
                    'persona.nombre_completo',
                    'persona.ci_persona',
                    'persona.distrito',
                    'habilitado.id_habilitado',
                    'habilitado.observaciones_habilitado',
                    DB::raw('DATE(habilitado.fecha_habilitado) as fecha_habilitado'),
                    DB::raw("UPPER(REGEXP_REPLACE(
                        COALESCE(
                            CONCAT(TRIM(persona.apellido_persona), ' ', TRIM(persona.nombre_persona)),
                            persona.nombre_completo
                        ),
                        ' +', ' '
                    )) as nombre_orden"),
                )
                ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
                ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                ->leftJoin('pago', function ($join) {
                    $join->on('pago.id_habilitado', '=', 'habilitado.id_habilitado')
                        ->where('pago.pago', 1);
                })
                ->leftJoinSub($ultimoHistorial($fechaHasta), 'ultimo_estado', function ($join) {
                    $join->on('persona.id_persona', '=', 'ultimo_estado.id_persona');
                })
                ->whereRaw($habilitadoUnico)
                ->whereRaw("CONCAT(gestion.gestion, LPAD(mes.mes, 2, '0')) BETWEEN ? AND ?", [$desdeYM, $hastaYM])
                ->whereNull('pago.id_pago')
                ->where('habilitado.habilitado', 1)
                ->whereRaw("(ultimo_estado.estado IS NULL OR ultimo_estado.estado NOT IN ('baja_temporal', 'baja_definitiva'))")
                ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                ->orderByRaw('nombre_orden COLLATE utf8mb4_spanish_ci ASC')
                ->paginate(150, ['*'], 'page_no_pagados')->appends($request->query());

            $cantidadNoPagados = DB::table('habilitado')
                ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
                ->leftJoin('pago', function ($join) {
                    $join->on('pago.id_habilitado', '=', 'habilitado.id_habilitado')
                        ->where('pago.pago', 1);
                })
                ->joinSub($ultimoHistorial($fechaHasta), 'ultimo_estado', function ($join) {
                    $join->on('persona.id_persona', '=', 'ultimo_estado.id_persona');
                })
                ->whereRaw($habilitadoUnico)
                ->whereRaw(
                    "CONCAT(gestion.gestion, LPAD(mes.mes, 2, '0')) BETWEEN ? AND ?",
                    [$desdeYM, $hastaYM]
                )
                ->whereNull('pago.id_pago')
                ->where('habilitado.habilitado', 1)
                ->where('ultimo_estado.estado', 'activo')
                ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                ->count();

            $noPagadosExport = DB::table('habilitado')
                ->select(
                    'persona.nombre_persona',
                    'persona.apellido_persona',
                    'persona.nombre_completo',
                    'persona.ci_persona',
                    'persona.distrito',
                    'habilitado.observaciones_habilitado',
                    'mes.monto',
                    DB::raw("UPPER(REGEXP_REPLACE(
                    COALESCE(
                        CONCAT(TRIM(persona.apellido_persona), ' ', TRIM(persona.nombre_persona)),
                        persona.nombre_completo
                        ),
                        ' +', ' '
                    )) as nombre_orden"),
                    DB::raw('DATE(habilitado.fecha_habilitado) as fecha_habilitado')
                )
                ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
                ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                ->leftJoin('pago', function ($join) {
                    $join->on('pago.id_habilitado', '=', 'habilitado.id_habilitado')
                        ->where('pago.pago', 1);
                })
                ->joinSub($ultimoHistorial($fechaHasta), 'ultimo_estado', fn($j) => $j->on('persona.id_persona', '=', 'ultimo_estado.id_persona'))
                ->whereRaw($habilitadoUnico)
                ->whereRaw("CONCAT(gestion.gestion, LPAD(mes.mes, 2, '0')) BETWEEN ? AND ?", [$desdeYM, $hastaYM])
                ->whereNull('pago.id_pago')->where('habilitado.habilitado', 1)->where('ultimo_estado.estado', 'activo')
                ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                ->orderByRaw('nombre_orden COLLATE utf8mb4_spanish_ci ASC')->get();

            $bajasExport = collect([]); // tab Bajas no existe en modo rango

            $montoNoPagados = DB::table('habilitado')
                ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                ->join('persona', 'persona.id_persona', '=', 'habilitado.id_persona')
                ->leftJoin('pago', function ($join) {
                    $join->on('pago.id_habilitado', '=', 'habilitado.id_habilitado')
                        ->where('pago.pago', 1);
                })
                ->joinSub($ultimoHistorial($fechaHasta), 'ultimo_estado', function ($join) {
                    $join->on('persona.id_persona', '=', 'ultimo_estado.id_persona');
                })
                ->whereRaw($habilitadoUnico)
                ->whereRaw(
                    "CONCAT(gestion.gestion, LPAD(mes.mes, 2, '0')) BETWEEN ? AND ?",
                    [$desdeYM, $hastaYM]
                )
                ->whereNull('pago.id_pago')
                ->where('habilitado.habilitado', 1)
                ->where('ultimo_estado.estado', 'activo')
                ->when($distrito, fn($q) => $q->where('persona.distrito', $distrito))
                ->sum('mes.monto');

            $usuarioTienePagos = DB::table('pago')
                ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                ->join('mes', 'mes.id_mes', '=', 'habilitado.id_mes')
                ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                ->where('pago.id', $idUser)
                ->where('pago.pago', 1)
                ->whereRaw(
                    "CONCAT(gestion.gestion, LPAD(mes.mes, 2, '0')) BETWEEN ? AND ?",
                    [$desdeYM, $hastaYM]
                )
                ->exists();
        }

        return Inertia::render('BandejaPagos/index', [
            'resumenGeneral' => $resumenGeneral,
            'totalesResumen' => $totalesResumen,
            'gestiones' => $gestiones,
            'mesesNumeros' => $mesesNumeros,
            'pagosTodos' => $pagosTodos,
            'pagosTodosExport' => $pagosTodosExport ?? collect([]),
            'montoTotalPagosTodos' => $montoTotalPagosTodos ?? 0,
            'cantidadPagosTodosValidos' => $cantidadPagosTodosValidos ?? 0,
            'usuarioTienePagos' => $usuarioTienePagos,
            'misPagos' => $misPagos,
            'misPagosTodos' => $misPagosTodos ?? collect([]),
            'montoTotalMisPagos' => $montoTotalMisPagos,
            'misPresupuestoAsignado' => $misPresupuestoAsignado,
            // Monto por beneficiario del mes filtrado — usado para el Arqueo
            // de Caja (deriva "no pagados" del presupuesto asignado), más
            // robusto que sacarlo del primer pago del cajero porque no
            // depende de que ya haya pagado a alguien este mes.
            'montoMes' => $montoMes ?? 0,
            'cantidadMisPagos' => $misPagosTodos->count(),
            'pagosPorUsuario' => $pagosPorUsuario ?? collect([]),
            'noPagados' => $noPagados,
            'cantidadNoPagados' => $cantidadNoPagados,
            'noPagadosExport' => $noPagadosExport ?? collect([]),
            'bajasExport' => $bajasExport ?? collect([]),
            'montoNoPagados' => $montoNoPagados ?? 0,
            'montoBajas' => $montoBajas ?? 0,
            'bajas' => $bajas,
            'cantidadBajas' => $cantidadBajas,
            'distrito' => $distrito,
            'distritos' => ['D-1', 'D-2', 'D-3', 'D-4', 'D-5', 'D-6', 'D-7', 'AGUIRRE', 'CHIÑATA', 'LAVA LAVA', 'UCUCHI', 'PALCA'],
            'filters' => [
                'gestion' => $year,
                'mes' => $mes,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'tab' => $request->tab,
                'distrito' => $distrito,
                'estado_baja' => $estadoBaja,
                'ver_retro' => $verRetro,
                'incluir_anulados' => $incluirAnulados,
            ],
            'mesRetroDisponible' => (bool) $mesRetroInfo,
        ]);
    }

    public function exportNoPagadosExcel(Request $request)
    {
        $datos = collect($request->input('datos', []))->map(fn($item) => (object) $item);
        return Excel::download(
            new NoPagadosExport(
                $datos,
                $request->input('gestion'),
                $request->input('mes'),
                $request->input('fecha_desde'),
                $request->input('fecha_hasta'),
                $request->input('distrito'),
                $request->boolean('ver_retro'),
            ),
            // Mismo patrón de nombre que el PDF (useReporteNoPagadosPDF.js).
            'Reporte_NoPagados-' . now('America/La_Paz')->format('Y-m-d_His') . '.xlsx'
        );
    }

    public function exportBajasExcel(Request $request)
    {
        $datos = collect($request->input('datos', []))->map(fn($item) => (object) $item);
        return Excel::download(
            new BajasExport(
                $datos,
                $request->input('gestion'),
                $request->input('mes'),
                $request->input('estado_baja'),
                $request->input('distrito'),
                $request->boolean('ver_retro'),
            ),
            // Mismo patrón de nombre que el PDF (useReporteBajasPDF.js).
            'Reporte_Bajas-' . now('America/La_Paz')->format('Y-m-d_His') . '.xlsx'
        );
    }

    /**
     * Reporte individual de pagos (tab "Mis Pagos" y el modal de detalle por
     * usuario en Resumen General), en Excel — mismo modelo que
     * useReportePagosIndividualPDF.js.
     */
    public function exportPagosIndividualExcel(Request $request)
    {
        $request->validate([
            'datos' => 'required|array',
            'usuario' => 'required|string',
        ]);

        $datos = collect($request->input('datos', []));

        return Excel::download(
            new PagosIndividualExport(
                $datos,
                $request->input('gestion'),
                $request->input('mes'),
                $request->input('usuario'),
                $request->input('fecha_desde'),
                $request->input('fecha_hasta'),
            ),
            'Reporte_MisPagos-' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    /**
     * Reporte "Total Pagados" (tab general de Bandeja de Pagos), en Excel —
     * mismo modelo que useReportePagosTodosPDF.js.
     */
    public function exportPagosTodosExcel(Request $request)
    {
        $request->validate([
            'datos' => 'required|array',
        ]);

        $datos = collect($request->input('datos', []));

        return Excel::download(
            new PagosTodosExport(
                $datos,
                $request->input('gestion'),
                $request->input('mes'),
                $request->input('fecha_desde'),
                $request->input('fecha_hasta'),
                $request->input('distrito'),
                $request->boolean('ver_retro'),
            ),
            'Reporte_TotalPagados-' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    /**
     * Reporte "Resumen General" (tab resumen, solo admin), en Excel — mismo
     * modelo que useReporteResumenPDF.js.
     */
    public function exportResumenGeneralExcel(Request $request)
    {
        $request->validate([
            'datos' => 'required|array',
            'usuario' => 'required|string',
        ]);

        $datos = collect($request->input('datos', []));

        return Excel::download(
            new ResumenGeneralExport(
                $datos,
                $request->input('gestion'),
                $request->input('mes'),
                $request->input('usuario'),
                $request->input('fecha_desde'),
                $request->input('fecha_hasta'),
            ),
            // Mismo patrón de nombre que el PDF (fechaArchivo() en useReporteResumenPDF.js).
            'Reporte_ResumenGeneral-' . now('America/La_Paz')->format('Y-m-d_His') . '.xlsx'
        );
    }

    /**
     * Reporte de Arqueo de Caja (tab "Mis Pagos" → botón "Cerrar Caja"), en
     * Excel — mismo modelo que useReporteArqueoCajaPDF.js.
     */
    public function exportArqueoCajaExcel(Request $request)
    {
        $request->validate([
            'datos' => 'required|array',
            'usuario' => 'required|string',
            'cargo' => 'nullable|string',
        ]);

        return Excel::download(
            new ArqueoCajaExport($request->input('datos'), $request->input('usuario'), $request->input('cargo')),
            'Arqueo_Caja-' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    public function bandejaReporteLog(Request $request)
    {
        $tipo = $request->query('tipo');
        $gestion = $request->query('gestion');
        $mes = $request->query('mes');
        $fechaDesde = $request->query('fecha_desde');
        $fechaHasta = $request->query('fecha_hasta');

        match ($tipo) {
            'resumen' => $this->logService->logReportPrint(
                'BandejaPago',
                'Reporte Resumen General de Pagos',
                array_filter([
                    'gestión' => $gestion ?: null,
                    'mes' => $mes ?: null,
                    'fecha desde' => $fechaDesde ?: null,
                    'fecha hasta' => $fechaHasta ?: null,
                    'total beneficiarios' => $request->query('total_beneficiarios') ?? '—',
                    'monto total' => 'Bs. ' . ($request->query('total_monto') ?? '—'),
                ]),
                null,
                $fechaDesde
                ? "Se imprimió el reporte resumen de pagos del {$fechaDesde} al {$fechaHasta}."
                : "Se imprimió el reporte resumen de pagos de {$mes}/{$gestion}.",
                'PDF'
            ),

            'individual' => $this->logService->logReportPrint(
                'BandejaPago',
                'Reporte Individual de Pagos',
                array_filter([
                    'gestión' => $gestion ?: null,
                    'mes' => $mes ?: null,
                    'fecha desde' => $fechaDesde ?: null,
                    'fecha hasta' => $fechaHasta ?: null,
                    'usuario' => $request->query('usuario') ?? '—',
                    'total pagos' => $request->query('total_pagos') ?? '—',
                ]),
                null,
                $fechaDesde
                ? "Se imprimió el reporte individual de pagos del {$fechaDesde} al {$fechaHasta}."
                : "Se imprimió el reporte individual de pagos de {$mes}/{$gestion}.",
                'PDF'
            ),

            default => null
        };
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