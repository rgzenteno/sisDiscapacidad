<?php

namespace App\Http\Controllers;

use App\Models\Carnet;
use App\Models\Gestion;
use Illuminate\Support\Facades\Log;
use App\Models\Habilitado;
use App\Models\Mes;
use App\Models\Pago;
use App\Models\Persona;
use Exception;
use App\Models\User;
use App\Services\LogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PagoController extends Controller
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
        // Obtener el término de búsqueda desde la solicitud
        $search = $request->input('buscador');

        // Consulta base con join y filtros
        $query = DB::table('persona')
            ->join('habilitado', 'persona.id_persona', '=', 'habilitado.id_persona')
            ->where('habilitado.habilitado', 1)
            ->select(
                'persona.id_persona',
                'persona.nombre_persona',
                'persona.apellido_persona',
                'persona.distrito',
                'persona.ci_persona',
                'persona.observacion_persona'
            )
            ->groupBy(
                'persona.id_persona',
                'persona.nombre_persona',
                'persona.apellido_persona',
                'persona.distrito',
                'persona.ci_persona',
                'persona.observacion_persona'
            );

        // Aplicar búsqueda si existe
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('persona.nombre_persona', 'like', "%{$search}%")
                    ->orWhere('persona.apellido_persona', 'like', "%{$search}%")
                    ->orWhere('persona.distrito', 'like', "%{$search}%")
                    ->orWhere('persona.ci_persona', 'like', "%{$search}%");
            });
        }

        // Obtener datos paginados con los filtros aplicados
        $persona = $query->orderBy('persona.id_persona', 'desc')
            ->paginate(15)
            ->appends($request->query());

        // Retornar la vista con los datos paginados
        return Inertia::render('Pago/index', [
            'carnet' => Carnet::all(),
            'persona' => $persona,
            'personas' => $persona,
            'habilitado' => Habilitado::all(),
            'filters' => [
                'buscador' => $search,
            ],
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
        $id_habilitado = $request->id_habilitado;
        $user = Auth::user();
        $tipo = $request->tipo;

        DB::transaction(function () use ($request, $id_habilitado, $user, $tipo) {

            $habilitado = Habilitado::query()
                ->join('gestion', 'habilitado.id_gestion', 'gestion.id_gestion')
                ->join('mes', 'habilitado.id_mes', 'mes.id_mes')
                ->join('persona', 'habilitado.id_persona', 'persona.id_persona')
                ->leftJoin('tutor', 'persona.id_tutor', 'tutor.id_tutor')
                ->where('habilitado.id_habilitado', '=', $id_habilitado)
                ->select(
                    'persona.nombre_persona',
                    'persona.apellido_persona',
                    'persona.ci_persona',
                    'tutor.id_tutor',
                    'mes.monto',
                    'mes.mes',
                    'gestion.gestion',
                    'habilitado.id_habilitado',
                    'habilitado.id_mes',
                    'habilitado.id_gestion'
                )
                ->lockForUpdate()
                ->first();

            if (!$habilitado) {
                return response()->json(['error' => 'Habilitado no encontrado'], 404);
            }

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

            $nombreMes = ucwords(strtolower($meses[$habilitado->mes]));

            $numeroBoleta = $this->generarNumeroBoleta(
                $id_habilitado,
                $habilitado->id_mes,
                $habilitado->id_gestion
            );

            $data = $request->all();
            $data['fecha_pago']    = now();
            $data['pago']          = '1';
            $data['tipo_pago']     = $tipo;
            $data['monto']         = $habilitado->monto;
            $data['numero_boleta'] = $numeroBoleta;
            $data['id']            = $user->id;

            $nombrePersona = ucwords(strtolower(
                "{$habilitado->nombre_persona} {$habilitado->apellido_persona}"
            ));

            $pagoCreado = Pago::create($data);

            $periodo = "{$nombreMes} - {$habilitado->gestion}";

            $descripcion = "Pago de {$periodo} realizado a {$nombrePersona}.";

            // ─── Log compacto para alto volumen ──────────────────────────────
            $this->logService->logPayment(
                $pagoCreado,
                (object) [
                    'id'             => $habilitado->id_habilitado,
                    'nombre'         => $nombrePersona,
                    'monto'          => $habilitado->monto,
                    'periodo'        => $periodo,
                    'tipo_bono'      => 'Efectivo',
                    'numero_boleta'  => $numeroBoleta,
                    'ci'             => $habilitado->ci_persona
                ],
                $descripcion
            );
        });
    }

    private function generarNumeroBoleta($idHabilitado, $idMes, $idGestion)
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

        $datos = DB::table('habilitado')
            ->join('mes',     'habilitado.id_mes',     '=', 'mes.id_mes')
            ->join('gestion', 'habilitado.id_gestion', '=', 'gestion.id_gestion')
            ->where('habilitado.id_habilitado', $idHabilitado)
            ->select('mes.mes as numero_mes', 'gestion.gestion as anio')
            ->first();

        if (!$datos) return null;

        // 1. Bloquear fila del consecutivo
        $fila = DB::table('boleta_consecutivo')
            ->where('id_mes',     $idMes)
            ->where('id_gestion', $idGestion)
            ->lockForUpdate()
            ->first();

        if (!$fila) {
            throw new Exception("No existe consecutivo para id_mes={$idMes} / id_gestion={$idGestion}.");
        }

        // 2. Calcular siguiente número
        $nuevoCorrelativo = (int) $fila->ultimo_numero + 1;

        // 3. Actualizar el contador
        DB::table('boleta_consecutivo')
            ->where('id_mes',     $idMes)
            ->where('id_gestion', $idGestion)
            ->update(['ultimo_numero' => $nuevoCorrelativo]);

        // 4. Retornar número formateado
        $correlativo = str_pad($nuevoCorrelativo, 6, '0', STR_PAD_LEFT);
        return "{$abreviaciones[$datos->numero_mes]}-{$datos->anio}-{$correlativo}";
    }

    public function descargarBoleta(Request $request)
    {
        Log::info("entra al controlador");
        $idPago  = $request->input('id_pago');
        $gestion = $request->input('gestion');
        $mes     = $request->input('mes');

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
        $idPago  = $request->input('id_pago');
        $gestion = $request->input('gestion');
        $mes     = $request->input('mes');

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
                $properties['anulado']     = true;
                $properties['anulado_por'] = Auth::id();
                $properties['anulado_en']  = now()->toDateTimeString();
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
            'habilitado' => $habilitadoPaginado,      // Datos paginados para la tabla
            'habilitadoCompleto' => $habilitadoCompleto, // Todos los datos sin paginar
            'persona' => $persona,
        ]);
    }

    public function reporteLog(Request $request)
    {
        $tipo    = $request->query('tipo');
        $gestion = $request->query('gestion');

        match ($tipo) {
            'arqueo_general' => $this->logService->logReportPrint(
                'Reporte',
                'Reporte de Arqueo General',
                [
                    'gestión'     => $gestion                        ?? '—',
                    'total meses' => $request->query('total_meses')  ?? '—',
                ],
                null,
                "Se imprimió el reporte de arqueo general de la gestión {$gestion}.",
                'PDF'
            ),

            'arqueo_caja' => $this->logService->logReportPrint(
                'Reporte',
                'Reporte de Arqueo de Caja Mensual',
                [
                    'gestión'          => $gestion                                ?? '—',
                    'mes'              => $request->query('mes')                  ?? '—',
                    'total pagados'    => $request->query('total_pagados')        ?? '—',
                    'total no pagados' => $request->query('total_no_pagados')     ?? '—',
                    'monto asignado'   => 'Bs. ' . $request->query('monto_asignado') ?? '—',
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
            'comprobante' => 'required|file|mimes:jpg,png,pdf|max:5120', // 5MB
            'id_pago' => 'required|exists:pago,id_pago'
        ]);

        if ($request->hasFile('comprobante')) {
            $file = $request->file('comprobante');
            $extension = $file->getClientOriginalExtension();

            // Generar un nombre único para el archivo
            $fileName = 'comprobante-' . $request->id_pago . '-' . time() . '.' . $extension;

            // Guardar el archivo en storage/public/comprobantes
            $filePath = $file->storeAs('comprobantes', $fileName, 'public');

            // Obtener solo el nombre del archivo para guardar en la BD
            $nombreArchivo = $fileName; // O usar basename($filePath) si prefieres

            // Actualizar el pago con el nombre del archivo
            $pago = Pago::findOrFail($request->id_pago);
            $pago->update([
                'comprobante' => $nombreArchivo, // Guardamos solo el nombre del archivo
                /* 'comprobante_url' => 'storage/comprobantes/' . $nombreArchivo, // Ruta completa para acceso
                'estado' => 'En revisión',
                'fecha_subida' => now(), */
            ]);

            // Opcional: Registrar en logs
            $descripcion = "Comprobante subido: " . $nombreArchivo;
            //$this->logService->logUpdate('Pago', $pago, $descripcion);

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
        $user   = Auth::user();
        $idUser = "{$user->id}";

        $year       = $request->input('gestion_gestion');
        $mes        = $request->input('mes');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        $resumenGeneral     = collect([]);
        $totalesResumen     = ['total_beneficiarios' => 0, 'total_monto' => 0];
        $mesesNumeros       = collect([]);
        $misPagos           = collect([]);
        $pagosTodos = collect([]);
        $misPagosTodos      = collect([]);
        $montoTotalMisPagos = 0;

        $gestiones = Gestion::select('gestion as anio')
            ->distinct()
            ->orderBy('gestion', 'desc')
            ->get();

        if ($year) {
            $gestion = Gestion::where('gestion', $year)->first();
            if ($gestion) {
                $mesesNumeros = Mes::where('id_gestion', $gestion->id_gestion)
                    ->pluck('mes')->sort()->values();
            }
        }

        // ── Búsqueda por Gestión + Mes ────────────────────────────────────────
        if ($year && $mes) {
            $gestion = Gestion::where('gestion', $year)->first();
            if ($gestion) {
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
                    ->join('mes', function ($join) use ($gestion, $mes) {
                        $join->on('mes.id_mes', '=', 'habilitado.id_mes')
                            ->where('mes.mes', '=', $mes)
                            ->where('mes.id_gestion', '=', $gestion->id_gestion);
                    })
                    ->groupBy('users.id', 'users.nombre', 'users.apellido', 'users.cargo')
                    ->orderBy('cantidad_pagos', 'desc')
                    ->paginate(150)->appends($request->query());

                $resumenGeneral = $usuarios;
                $totalesResumen = [
                    'total_beneficiarios' => $usuarios->sum('cantidad_pagos'),
                    'total_monto'         => $usuarios->sum('monto_total'),
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
                        'pago.numero_boleta',
                        'pago.fecha_pago'
                    )
                    ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                    ->join('persona',    'persona.id_persona',       '=', 'habilitado.id_persona')
                    ->join('mes', function ($join) use ($gestion, $mes) {
                        $join->on('mes.id_mes', '=', 'habilitado.id_mes')
                            ->where('mes.mes', '=', $mes)
                            ->where('mes.id_gestion', '=', $gestion->id_gestion);
                    })
                    ->join('gestion', 'gestion.id_gestion', '=', 'mes.id_gestion')
                    ->orderBy('pago.fecha_pago', 'asc')
                    ->get()
                    ->groupBy('user_id');

                // Monto total (todos los registros, no solo la página)
                $montoTotalMisPagos = DB::table('pago')
                    ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                    ->join('mes',        'mes.id_mes',               '=', 'habilitado.id_mes')
                    ->join('gestion',    'gestion.id_gestion',       '=', 'mes.id_gestion')
                    ->where('pago.id',        $idUser)
                    ->where('mes.mes',        $mes)
                    ->where('mes.id_gestion', $gestion->id_gestion)
                    ->sum('pago.monto');

                // Mis Pagos paginado
                $misPagos = DB::table('pago')
                    ->select(
                        'persona.nombre_persona',
                        'persona.apellido_persona',
                        'persona.ci_persona',
                        'persona.distrito',
                        'mes.mes',
                        'gestion.gestion',
                        'pago.monto',
                        'pago.fecha_pago',
                        'pago.numero_boleta'
                    )
                    ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                    ->join('persona',    'persona.id_persona',       '=', 'habilitado.id_persona')
                    ->join('mes',        'mes.id_mes',               '=', 'habilitado.id_mes')
                    ->join('gestion',    'gestion.id_gestion',       '=', 'mes.id_gestion')
                    ->where('pago.id',        $idUser)
                    ->where('mes.mes',        $mes)
                    ->where('mes.id_gestion', $gestion->id_gestion)
                    ->orderBy('pago.fecha_pago', 'asc')
                    ->paginate(50)->appends($request->query());

                $misPagosTodos = DB::table('pago')
                    ->select(
                        'persona.nombre_persona',
                        'persona.apellido_persona',
                        'persona.ci_persona',
                        'persona.distrito',
                        'mes.mes',
                        'gestion.gestion',
                        'pago.monto',
                        'pago.fecha_pago',
                        'pago.numero_boleta'
                    )
                    ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                    ->join('persona',    'persona.id_persona',       '=', 'habilitado.id_persona')
                    ->join('mes',        'mes.id_mes',               '=', 'habilitado.id_mes')
                    ->join('gestion',    'gestion.id_gestion',       '=', 'mes.id_gestion')
                    ->where('pago.id',        $idUser)
                    ->where('mes.mes',        $mes)
                    ->where('mes.id_gestion', $gestion->id_gestion)
                    ->orderBy('pago.fecha_pago', 'asc')
                    ->get();

                $pagosTodos = DB::table('pago')
                    ->select(
                        'persona.nombre_persona',
                        'persona.apellido_persona',
                        'persona.ci_persona',
                        'persona.distrito',
                        'mes.mes',
                        'gestion.gestion',
                        'pago.monto',
                        'pago.numero_boleta',
                        'pago.fecha_pago',
                        DB::raw("CONCAT(users.nombre, ' ', users.apellido) as usuario_pagador")
                    )
                    ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                    ->join('persona',    'persona.id_persona',       '=', 'habilitado.id_persona')
                    ->join('mes',        'mes.id_mes',               '=', 'habilitado.id_mes')
                    ->join('gestion',    'gestion.id_gestion',       '=', 'mes.id_gestion')
                    ->join('users',      'users.id',                 '=', 'pago.id')
                    ->where('mes.mes',        $mes)
                    ->where('mes.id_gestion', $gestion->id_gestion)
                    ->orderBy('pago.numero_boleta', 'desc')
                    ->paginate(150)->appends($request->query());
            }
        }

        // ── Búsqueda por Rango de Meses ───────────────────────────────────────
        elseif ($fechaDesde && $fechaHasta) {

            $desdeYM = date('Ym', strtotime($fechaDesde)); // ej: "202512"
            $hastaYM = date('Ym', strtotime($fechaHasta)); // ej: "202601"

            $usuarios = User::select(
                'users.id',
                'users.nombre',
                'users.apellido',
                'users.cargo',
                DB::raw('COUNT(pago.id_pago) as cantidad_pagos'),
                DB::raw('MAX(pago.monto) as monto'),
                DB::raw('SUM(pago.monto) as monto_total')
            )
                ->join('pago',       'users.id',                 '=', 'pago.id')
                ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                ->join('mes',        'mes.id_mes',               '=', 'habilitado.id_mes')
                ->join('gestion',    'gestion.id_gestion',       '=', 'mes.id_gestion')
                ->whereRaw(
                    "CONCAT(gestion.gestion, LPAD(mes.mes, 2, '0')) BETWEEN ? AND ?",
                    [$desdeYM, $hastaYM]
                )
                ->groupBy('users.id', 'users.nombre', 'users.apellido', 'users.cargo')
                ->orderBy('cantidad_pagos', 'desc')
                ->paginate(150)->appends($request->query());

            $resumenGeneral = $usuarios;
            $totalesResumen = [
                'total_beneficiarios' => $usuarios->sum('cantidad_pagos'),
                'total_monto'         => $usuarios->sum('monto_total'),
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
                    'pago.numero_boleta',
                    'pago.fecha_pago'
                )
                ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                ->join('persona',    'persona.id_persona',       '=', 'habilitado.id_persona')
                ->join('mes',        'mes.id_mes',               '=', 'habilitado.id_mes')
                ->join('gestion',    'gestion.id_gestion',       '=', 'mes.id_gestion')
                ->whereRaw(
                    "CONCAT(gestion.gestion, LPAD(mes.mes, 2, '0')) BETWEEN ? AND ?",
                    [$desdeYM, $hastaYM]
                )
                ->orderBy('pago.fecha_pago', 'asc')
                ->get()
                ->groupBy('user_id');

            // Monto total (todos los registros, no solo la página)
            $montoTotalMisPagos = DB::table('pago')
                ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                ->join('mes',        'mes.id_mes',               '=', 'habilitado.id_mes')
                ->join('gestion',    'gestion.id_gestion',       '=', 'mes.id_gestion')
                ->where('pago.id', $idUser)
                ->whereRaw(
                    "CONCAT(gestion.gestion, LPAD(mes.mes, 2, '0')) BETWEEN ? AND ?",
                    [$desdeYM, $hastaYM]
                )
                ->sum('pago.monto');

            // Mis Pagos paginado
            $misPagos = DB::table('pago')
                ->select(
                    'persona.nombre_persona',
                    'persona.apellido_persona',
                    'persona.ci_persona',
                    'persona.distrito',
                    'mes.mes',
                    'gestion.gestion',
                    'pago.monto',
                    'pago.numero_boleta',
                    'pago.fecha_pago'
                )
                ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                ->join('persona',    'persona.id_persona',       '=', 'habilitado.id_persona')
                ->join('mes',        'mes.id_mes',               '=', 'habilitado.id_mes')
                ->join('gestion',    'gestion.id_gestion',       '=', 'mes.id_gestion')
                ->where('pago.id', $idUser)
                ->whereRaw(
                    "CONCAT(gestion.gestion, LPAD(mes.mes, 2, '0')) BETWEEN ? AND ?",
                    [$desdeYM, $hastaYM]
                )
                ->orderBy('pago.fecha_pago', 'asc')
                ->paginate(50)->appends($request->query());

            $misPagosTodos = DB::table('pago')
                ->select(
                    'persona.nombre_persona',
                    'persona.apellido_persona',
                    'persona.ci_persona',
                    'persona.distrito',
                    'mes.mes',
                    'gestion.gestion',
                    'pago.monto',
                    'pago.numero_boleta',
                    'pago.fecha_pago'
                )
                ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                ->join('persona',    'persona.id_persona',       '=', 'habilitado.id_persona')
                ->join('mes',        'mes.id_mes',               '=', 'habilitado.id_mes')
                ->join('gestion',    'gestion.id_gestion',       '=', 'mes.id_gestion')
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
                    'pago.monto',
                    'pago.numero_boleta',
                    'pago.fecha_pago',
                    DB::raw("CONCAT(users.nombre, ' ', users.apellido) as usuario_pagador")
                )
                ->join('habilitado', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                ->join('persona',    'persona.id_persona',       '=', 'habilitado.id_persona')
                ->join('mes',        'mes.id_mes',               '=', 'habilitado.id_mes')
                ->join('gestion',    'gestion.id_gestion',       '=', 'mes.id_gestion')
                ->join('users',      'users.id',                 '=', 'pago.id')
                ->whereRaw(
                    "CONCAT(gestion.gestion, LPAD(mes.mes, 2, '0')) BETWEEN ? AND ?",
                    [$desdeYM, $hastaYM]
                )
                ->orderBy('pago.numero_boleta', 'desc')
                ->paginate(150)->appends($request->query());
        }

        $usuarioTienePagos = DB::table('pago')->where('id', $idUser)->exists();

        return Inertia::render('BandejaPagos/index', [
            'resumenGeneral'     => $resumenGeneral,
            'totalesResumen'     => $totalesResumen,
            'gestiones'          => $gestiones,
            'mesesNumeros'       => $mesesNumeros,
            'pagosTodos' => $pagosTodos,
            'usuarioTienePagos'  => $usuarioTienePagos,
            'misPagos'           => $misPagos,
            'misPagosTodos'      => $misPagosTodos ?? collect([]),
            'montoTotalMisPagos' => $montoTotalMisPagos,
            'cantidadMisPagos' => $misPagosTodos->count(),
            'pagosPorUsuario'    => $pagosPorUsuario ?? collect([]),
            'filters'            => [
                'gestion'     => $year,
                'mes'         => $mes,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
            ],
        ]);
    }

    public function bandejaReporteLog(Request $request)
    {
        $tipo       = $request->query('tipo');
        $gestion    = $request->query('gestion');
        $mes        = $request->query('mes');
        $fechaDesde = $request->query('fecha_desde');
        $fechaHasta = $request->query('fecha_hasta');

        match ($tipo) {
            'resumen' => $this->logService->logReportPrint(
                'BandejaPago',
                'Reporte Resumen General de Pagos',
                array_filter([
                    'gestión'             => $gestion     ?: null,
                    'mes'                 => $mes          ?: null,
                    'fecha desde'         => $fechaDesde   ?: null,
                    'fecha hasta'         => $fechaHasta   ?: null,
                    'total beneficiarios' => $request->query('total_beneficiarios') ?? '—',
                    'monto total'         => 'Bs. ' . ($request->query('total_monto') ?? '—'),
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
                    'gestión'     => $gestion    ?: null,
                    'mes'         => $mes         ?: null,
                    'fecha desde' => $fechaDesde  ?: null,
                    'fecha hasta' => $fechaHasta  ?: null,
                    'usuario'     => $request->query('usuario')     ?? '—',
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
