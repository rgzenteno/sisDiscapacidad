<?php

namespace App\Http\Controllers;

use App\Models\Gestion;
use App\Models\Habilitado;
use App\Models\HistorialEstados;
use App\Models\HistorialHabilitado;
use App\Models\Mes;
use App\Models\Persona;
use App\Models\User;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class GestionController extends Controller
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
        $año = $request->input('año')
            ?? session('selected_year')
            ?? Gestion::max('gestion')
            ?? Carbon::now()->year;

        session(['selected_year' => $año]); // Guardar en sesión
        $selectedYear = session('selected_year'); // Obtener de sesión
        //$añoActualSistema = Carbon::now()->year;
        $añoActualSistema = 2025;

        $gestionActual = Gestion::where('gestion', $añoActualSistema)->first();

        if ($gestionActual) {
            $existeAñoActual = true;
            $idGestionActual = $gestionActual->id_gestion;
            $PresuGestionActual = $gestionActual->presupuesto_anual;
        } else {
            $existeAñoActual = false;
            $idGestionActual = null;
        }

        //dd(session('selected_year'));
        // ================== VERIFICACIÓN INICIAL ==================
        // Verificar si existe la gestión para el año solicitado
        $gestionActual = Gestion::where('gestion', $selectedYear)->first();

        $existeGestion = Gestion::exists();

        // Si no existe la gestión, inicializar colección vacía para la consulta principal
        $gestion = collect();

        // Si existe la gestión, verificar si tiene meses asociados
        $tieneMeses = false;
        if ($gestionActual) {
            $tieneMeses = Mes::where('id_gestion', $gestionActual->id_gestion)->exists();
        }

        // ================== CONSULTA PRINCIPAL (solo si hay gestión y meses) ==================
        if ($gestionActual && $tieneMeses) {
            $query = Gestion::query()
                ->select([
                    'gestion.id_gestion as id',
                    'gestion.gestion as gestion',
                    'gestion.presupuesto_anual as presupuesto',
                    'mes.id_mes as id_mes',
                    'mes.mes as mes',
                    'mes.monto as monto',
                    'mes.presupuesto as presupuesto',

                    // Conteos - Optimizados con subconsultas más eficientes
                    DB::raw('(SELECT COUNT(DISTINCT h.id_persona)
                    FROM habilitado h
                    WHERE h.id_mes = mes.id_mes) as cantidad_habilitadas'),

                    DB::raw('(SELECT COUNT(DISTINCT p.id_persona) FROM persona p) as total_personas'),

                    DB::raw('((SELECT COUNT(DISTINCT p.id_persona) FROM persona p) -
                    (SELECT COUNT(DISTINCT h.id_persona) FROM habilitado h WHERE h.id_gestion = gestion.id_gestion)) as cantidad_no_habilitadas'),

                    // Carnets
                    DB::raw('(SELECT COUNT(DISTINCT p.id_persona)
                    FROM persona p
                    LEFT JOIN carnet c ON p.id_persona = c.id_persona
                    WHERE c.id_persona IS NULL) as sin_carnet_discapacidad'),

                    DB::raw('(SELECT COUNT(DISTINCT p.id_persona)
                    FROM persona p
                    INNER JOIN carnet c ON p.id_persona = c.id_persona
                    WHERE c.fecha_vencimiento < CURDATE()) as carnet_vencidos'),

                    // Estados - CORREGIDOS para usar el último registro de historial_estados
                    DB::raw("(SELECT COUNT(DISTINCT p.id_persona)
                    FROM persona p
                    INNER JOIN historial_estados he ON p.id_persona = he.id_persona
                    WHERE he.estado = 'activo'
                    AND he.id = (
                        SELECT MAX(he2.id)
                        FROM historial_estados he2
                        WHERE he2.id_persona = he.id_persona
                    )) as personas_activos"),

                    DB::raw("(SELECT COUNT(DISTINCT p.id_persona)
                    FROM persona p
                    INNER JOIN historial_estados he ON p.id_persona = he.id_persona
                    WHERE he.estado = 'baja_temporal'
                    AND he.id = (
                        SELECT MAX(he2.id)
                        FROM historial_estados he2
                        WHERE he2.id_persona = he.id_persona
                    )) as personas_baja_temporal"),

                    DB::raw("(SELECT COUNT(DISTINCT p.id_persona)
                    FROM persona p
                    INNER JOIN historial_estados he ON p.id_persona = he.id_persona
                    WHERE he.estado = 'baja_definitiva'
                    AND he.id = (
                        SELECT MAX(he2.id)
                        FROM historial_estados he2
                        WHERE he2.id_persona = he.id_persona
                    )) as personas_baja_definitiva"),

                    DB::raw("(SELECT COUNT(DISTINCT p.id_persona) FROM persona p WHERE p.id_tutor IS NULL) AS personas_sin_tutor"),

                    // PAGOS CORREGIDOS - Filtrados por mes específico
                    DB::raw('(SELECT COUNT(pag.id_pago)
                    FROM pago pag
                    INNER JOIN habilitado hab ON pag.id_habilitado = hab.id_habilitado
                    WHERE hab.id_gestion = gestion.id_gestion
                    AND hab.id_mes = mes.id_mes) as cantidad_total_pagos'),

                    DB::raw('((SELECT COUNT(DISTINCT h.id_persona) FROM habilitado h WHERE h.id_mes = mes.id_mes) -
                    (SELECT COUNT(pag.id_pago) FROM pago pag
                    INNER JOIN habilitado hab ON pag.id_habilitado = hab.id_habilitado
                    WHERE hab.id_gestion = gestion.id_gestion
                    AND hab.id_mes = mes.id_mes)) as cantidad_no_pagados'),

                    DB::raw('(SELECT COALESCE(SUM(pag.monto), 0)
                    FROM pago pag
                    INNER JOIN habilitado hab ON pag.id_habilitado = hab.id_habilitado
                    WHERE hab.id_gestion = gestion.id_gestion
                    AND hab.id_mes = mes.id_mes) as total_pagado'),

                    // Total pagado contexto (filtrado por mes)
                    DB::raw('(SELECT COALESCE(SUM(pag.monto), 0)
                    FROM pago pag
                    INNER JOIN habilitado hab ON pag.id_habilitado = hab.id_habilitado
                    WHERE hab.id_gestion = gestion.id_gestion
                    AND hab.id_mes = mes.id_mes) as total_pagado_contexto'),

                    // Presupuesto restante - Usando subconsulta (total de la gestión)
                    DB::raw('(gestion.presupuesto_anual -
                    (SELECT COALESCE(SUM(pag.monto), 0)
                    FROM pago pag
                    INNER JOIN habilitado hab ON pag.id_habilitado = hab.id_habilitado
                    WHERE hab.id_gestion = gestion.id_gestion)) as presupuesto_restante'),

                    // Presupuesto - Simplificado
                    DB::raw('gestion.presupuesto_anual as presupuesto_anual'),

                    DB::raw('(SELECT COALESCE(SUM(p2.monto), 0)
                    FROM pago p2
                    INNER JOIN habilitado h2 ON p2.id_habilitado = h2.id_habilitado
                    WHERE h2.id_gestion = gestion.id_gestion
                    ) as presupuesto_anual_utilizado'),

                    DB::raw('(gestion.presupuesto_anual -
                    (SELECT COALESCE(SUM(p2.monto), 0)
                    FROM pago p2
                    INNER JOIN habilitado h2 ON p2.id_habilitado = h2.id_habilitado
                    WHERE h2.id_gestion = gestion.id_gestion)
                    ) as presupuesto_anual_restante'),

                    // Porcentajes - Corregidos para mostrar por mes
                    DB::raw('ROUND(((SELECT COUNT(DISTINCT h.id_persona) FROM habilitado h WHERE h.id_gestion = gestion.id_gestion) * 100.0) /
                    NULLIF((SELECT COUNT(DISTINCT p.id_persona) FROM persona p), 0), 2) as porcentaje_habilitado'),

                    DB::raw('ROUND(((SELECT COUNT(pag.id_pago)
                    FROM pago pag
                    INNER JOIN habilitado hab ON pag.id_habilitado = hab.id_habilitado
                    WHERE hab.id_gestion = gestion.id_gestion
                    AND hab.id_mes = mes.id_mes) * 100.0) /
                    NULLIF((SELECT COUNT(DISTINCT h.id_persona) FROM habilitado h WHERE h.id_mes = mes.id_mes), 0), 2) as porcentaje_pagado'),
                ])
                ->from('gestion')
                ->leftJoin('mes', 'mes.id_gestion', '=', 'gestion.id_gestion')

                ->where('gestion.gestion', '=', $selectedYear)
                ->groupBy([
                    'gestion.id_gestion',
                    'gestion.gestion',
                    'gestion.presupuesto_anual',
                    'mes.id_mes',
                    'mes.mes',
                    'mes.monto',
                    'mes.presupuesto',
                ])
                ->orderBy('mes.mes', 'asc');

            $gestion = $query->get();
        }

        // ================== MES DISPONIBLE ==================
        //$mesActual = Carbon::now()->month;
        $mesActual = 12;
        $mesDisponible = 0;

        // Buscar la gestión del año actual del sistema (no la seleccionada)
        $gestionAñoActualSistema = Gestion::where('gestion', $añoActualSistema)->first();

        if ($gestionAñoActualSistema) {
            $mesYaExiste = Mes::where('id_gestion', $gestionAñoActualSistema->id_gestion)
                ->where('mes', $mesActual)
                ->exists();
            if (!$mesYaExiste) {
                $mesDisponible = $mesActual;
            }
        }

        // ================== CONSULTAS AUXILIARES ==================
        $presupuestosAnuales = Gestion::select('gestion as año', 'presupuesto_anual')
            ->distinct()
            ->orderBy('año', 'desc')
            ->get()
            ->keyBy('año')
            ->map(fn($item) => $item->presupuesto_anual);

        $gestiones = Gestion::select('gestion')
            ->distinct()
            ->orderBy('gestion', 'desc')
            ->get();

        // Segunda consulta: solo la suma que necesitas
        $sumaPresupuestoMensual = DB::table('mes as m')
            ->join('gestion as g', 'm.id_gestion', '=', 'g.id_gestion')
            ->where('g.gestion', $selectedYear)
            ->sum('m.presupuesto') ?? 0;

        $total_personas_validas = Persona::query()
            ->join('carnet', 'persona.id_persona', '=', 'carnet.id_persona')
            ->join('historial_estados', function ($join) {
                $join->on('historial_estados.id_persona', '=', 'persona.id_persona')
                    ->where('historial_estados.id', '=', function ($query) {
                        $query->select(DB::raw('MAX(he2.id)'))
                            ->from('historial_estados as he2')
                            ->whereColumn('he2.id_persona', 'historial_estados.id_persona');
                    });
            })
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('historial_estados')
                    ->whereColumn('historial_estados.id_persona', 'persona.id_persona')
                    ->where('historial_estados.estado', 'activo')
                    ->where('historial_estados.id', '=', function ($subQuery) {
                        $subQuery->select(DB::raw('MAX(he2.id)'))
                            ->from('historial_estados as he2')
                            ->whereColumn('he2.id_persona', 'historial_estados.id_persona');
                    });
            })
            ->whereNotNull('persona.id_tutor')
            ->where('carnet.fecha_vencimiento', '>', now())
            ->count();

        // ================== RETORNO ==================
        return Inertia::render('Gestion/index', [
            'totalGestion' => Gestion::count(),
            'meses_registrados_año_actual' => $gestionActual && $tieneMeses ?
                Mes::where('id_gestion', $gestionActual->id_gestion)->pluck('mes')->toArray() : [],
            'gestiones' => $gestiones,
            'gestion' => $gestion, // Será colección vacía si no hay gestión o no tiene meses
            'filters' => [
                'año' => $selectedYear,
                'buscador' => $request->input('buscador', '')
            ],
            'años_registrados' => $gestiones->pluck('gestion'),
            'presupuestosAnuales' => $presupuestosAnuales,
            'sumaPresupuestoMensual' => $sumaPresupuestoMensual,
            'total_personas_validas' => $total_personas_validas,
            'añoSeleccionado' => $gestionActual ? $gestionActual : (object) ['gestion' => $selectedYear],
            'año_actual' => [
                'añoActualSistema' => $añoActualSistema,
                'existeAñoActual' => $existeAñoActual,
                'id' => $idGestionActual,
                'presupuesto_anual' => $PresuGestionActual ?? 0
            ],
            'mes_actual_disponible' => $mesDisponible,
            'gestionTotal' => Gestion::count(),
            'existe_gestion' => $gestionActual ? true : false, // Flag adicional para la vista
            'gestionData' => $existeGestion,
            'tiene_meses' => $tieneMeses, // Flag adicional para la vista
            'btnAgregar' => $existeAñoActual  // 👈 SOLO AGREGAR ESTA LÍNEA
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
        $data = $request->all();
        $gestion = Gestion::create($data);
        $mensaje = "Nueva gestion creada: {$gestion->gestion} - Presupuesto Anual: " . number_format($gestion->presupuesto_anual);

        $this->logService->logCreation('Gestion:', $gestion, $mensaje);

        // 👇 AÑADIR ESTO
        session(['selected_year' => $gestion->gestion]);
        return redirect()->route('gestion.index', ['año' => $gestion->gestion]);
    }

    public function addMes(Request $request)
    {
        $userId = Auth::id();
        $data = $request->all();
        $fechaHoy = Carbon::now('America/La_Paz');
        $fechaHoyString = $fechaHoy->format('Y-m-d');
        $usuario = Auth::user();
        $nombreCompletoUsuario = $this->obtenerNombreCompletoUsuario($usuario);

        Log::info('Iniciando store de gestión', ['user_id' => $userId, 'datos' => $data]);

        try {
            DB::beginTransaction();

            // Crear mes
            $mes = Mes::create($data);
            $gestion = Gestion::where('id_gestion', $mes->id_gestion)->value('gestion');

            // Registrar creación del mes
            $this->registrarCreacionMes($mes);

            // Procesar archivo Excel si existe
            $resultado = $this->procesarArchivoExcel(
                $request,
                $mes,
                $userId,
                $fechaHoy,
                $fechaHoyString,
                $nombreCompletoUsuario
            );

            DB::commit();
            Log::info('Gestión creada exitosamente', [
                'id_gestion' => $mes->id_gestion,
                'id_mes' => $mes->id_mes
            ]);

            if ($resultado) {
                // Antes del redirect, guarda en log
                Log::info('Resultado a enviar:', $resultado);

                return redirect()->back()
                    ->with('resultadosMes', $resultado);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear gestión', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    /**
     * Obtener nombre completo del usuario
     */
    private function obtenerNombreCompletoUsuario($usuario)
    {
        return trim($usuario->name . ' ' . ($usuario->apellido_paterno ?? '') . ' ' . ($usuario->apellido_materno ?? ''));
    }

    /**
     * Registrar la creación del mes en el log
     */
    private function registrarCreacionMes($mes)
    {
        $meses = [
            '',
            'Enero',
            'Febrero',
            'Marzo',
            'Abril',
            'Mayo',
            'Junio',
            'Julio',
            'Agosto',
            'Septiembre',
            'Octubre',
            'Noviembre',
            'Diciembre'
        ];

        $nombreMes = $meses[$mes->mes];
        $montoFormateado = number_format($mes->monto, 2);
        $presupuestoFormateado = number_format($mes->presupuesto_mesual, 2);
        $mensaje = "Nuevo mes creado: {$nombreMes} - Monto: {$montoFormateado} Presupuesto mensual: {$presupuestoFormateado}";

        $this->logService->logCreation('Mes', $mes, $mensaje);
    }

    /**
     * Procesar archivo Excel
     */
    private function procesarArchivoExcel($request, $mes, $userId, $fechaHoy, $fechaHoyString, $nombreCompletoUsuario)
    {
        if (!$request->hasFile('archivo_excel')) {
            Log::warning('No se recibió archivo Excel');
            return null;
        }

        $file = $request->file('archivo_excel');

        // LOGS DE DEBUG - Para diagnosticar el problema
        Log::info('Información del archivo recibido', [
            'nombre' => $file->getClientOriginalName(),
            'tamaño' => $file->getSize(),
            'mime' => $file->getMimeType(),
            'path' => $file->getPathname(),
            'existe' => file_exists($file->getPathname())
        ]);

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // LOG CRÍTICO - Ver cuántas filas se leyeron
            Log::info('Filas leídas del Excel', [
                'total_filas' => count($rows),
                'primeras_3_filas' => array_slice($rows, 0, 3)
            ]);

            // Buscar encabezados
            $headerInfo = $this->buscarEncabezados($rows);

            if ($headerInfo['index'] === -1) {
                throw new \Exception('No se encontraron las columnas requeridas (C.I., Nombres, Observaciones) en el archivo Excel');
            }

            Log::info('Encabezados encontrados en fila', [
                'fila' => $headerInfo['index'] + 1,
                'columnas' => $headerInfo['columns']
            ]);

            // Procesar filas
            return $this->procesarFilasExcel(
                $rows,
                $headerInfo,
                $mes,
                $userId,
                $fechaHoy,
                $fechaHoyString,
                $nombreCompletoUsuario
            );
        } catch (\Exception $e) {
            Log::error('Error al procesar archivo Excel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Buscar encabezados en el archivo Excel
     */
    private function buscarEncabezados($rows)
    {
        $headerRowIndex = -1;
        $columnIndexes = [];

        for ($i = 0; $i < min(5, count($rows)); $i++) {
            $row = array_map(function ($cell) {
                return trim(strtoupper($cell ?? ''));
            }, $rows[$i]);

            $tempIndexes = [
                'ci' => false,
                'nombre' => false,
                'observaciones' => false
            ];

            foreach ($row as $index => $cellValue) {
                if (strpos($cellValue, 'C.I.') !== false || $cellValue === 'CI') {
                    $tempIndexes['ci'] = $index;
                }
                if (strpos($cellValue, 'APELLIDOS Y NOMBRES') !== false || strpos($cellValue, 'NOMBRE') !== false) {
                    $tempIndexes['nombre'] = $index;
                }
                if (strpos($cellValue, 'OBSERVACIONES') !== false) {
                    $tempIndexes['observaciones'] = $index;
                }
            }

            if ($tempIndexes['ci'] !== false && $tempIndexes['nombre'] !== false && $tempIndexes['observaciones'] !== false) {
                $headerRowIndex = $i;
                $columnIndexes = $tempIndexes;
                break;
            }
        }

        return [
            'index' => $headerRowIndex,
            'columns' => $columnIndexes
        ];
    }

    /**
     * Procesar todas las filas del Excel
     */
    private function procesarFilasExcel($rows, $headerInfo, $mes, $userId, $fechaHoy, $fechaHoyString, $nombreCompletoUsuario)
    {
        $insertados = [];
        $habilitados = [];
        $bajasTemporales = [];
        $bajasDefinitivas = [];
        $errores = [];
        $columnIndexes = $headerInfo['columns'];
        $headerRowIndex = $headerInfo['index'];

        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            $ciCompleto = trim($row[$columnIndexes['ci']] ?? '');
            $nombreCompleto = trim($row[$columnIndexes['nombre']] ?? '');
            $observaciones = trim($row[$columnIndexes['observaciones']] ?? '');

            if (!$this->esFilaValida($ciCompleto, $nombreCompleto)) {
                continue;
            }

            try {
                $ciData = $this->separarCiYComplemento($ciCompleto);

                $resultado = $this->procesarPersona(
                    $ciData,
                    $nombreCompleto,
                    $observaciones,
                    $mes,
                    $userId,
                    $fechaHoy,
                    $fechaHoyString,
                    $nombreCompletoUsuario,
                    $i // Pasamos el índice de fila
                );

                $ciCompleto = $ciData['ci'] . ($ciData['complemento'] ? '-' . $ciData['complemento'] : '');

                if ($resultado['tipo'] === 'creada') {
                    $insertados[] = [
                        'fila' => $i + 1,
                        'ci' => $ciCompleto,
                        'nombre' => $nombreCompleto
                    ];
                }

                if ($resultado['habilitado']) {
                    $habilitados[] = [
                        'fila' => $i + 1,
                        'ci' => $ciCompleto,
                        'nombre' => $nombreCompleto
                    ];
                }

                if ($resultado['estado'] === 'baja_temporal') {
                    $bajasTemporales[] = [
                        'fila' => $i + 1,
                        'ci' => $ciCompleto,
                        'nombre' => $nombreCompleto,
                        'motivo' => $observaciones
                    ];
                }

                if ($resultado['estado'] === 'baja_definitiva') {
                    $bajasDefinitivas[] = [
                        'fila' => $i + 1,
                        'ci' => $ciCompleto,
                        'nombre' => $nombreCompleto,
                        'motivo' => $observaciones
                    ];
                }
            } catch (\Exception $e) {
                $errores[] = [
                    'fila' => $i + 1,
                    'ci' => $ciCompleto,
                    'nombre' => $nombreCompleto,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'insertados' => $insertados,
            'habilitados' => $habilitados,
            'bajas_temporales' => $bajasTemporales,
            'bajas_definitivas' => $bajasDefinitivas,
            'errores' => $errores,
            'total_procesado' => count($insertados) + count($habilitados) + count($bajasTemporales) + count($bajasDefinitivas)
        ];
    }

    /**
     * Separar CI y complemento
     * Ejemplos: "8505504 - 1P" -> ['ci' => '8505504', 'complemento' => '1P']
     *           "8505504" -> ['ci' => '8505504', 'complemento' => null]
     */
    private function separarCiYComplemento($ciCompleto)
    {
        $ciCompleto = trim($ciCompleto);

        // Buscar el guion para separar CI de complemento
        if (strpos($ciCompleto, '-') !== false) {
            $partes = explode('-', $ciCompleto, 2);
            return [
                'ci' => trim($partes[0]),
                'complemento' => trim($partes[1])
            ];
        }

        return [
            'ci' => $ciCompleto,
            'complemento' => null
        ];
    }

    /**
     * Validar si la fila es válida para procesar
     */
    private function esFilaValida($ci, $nombreCompleto)
    {
        // Extraer solo el CI numérico (antes del guion si existe)
        $ciNumerico = $ci;
        if (strpos($ci, '-') !== false) {
            $ciNumerico = trim(explode('-', $ci, 2)[0]);
        }

        // Saltar filas vacías y filas de totales/resúmenes
        if (
            empty($ciNumerico) ||
            empty($nombreCompleto) ||
            !is_numeric($ciNumerico) ||
            stripos($nombreCompleto, 'TOTAL') !== false ||
            stripos($nombreCompleto, 'PERSONAS CON DISCAPACIDAD') !== false ||
            stripos($nombreCompleto, 'FUNCIONARIOS') !== false
        ) {
            return false;
        }

        return true;
    }

    /**
     * Procesar una persona (crear o actualizar)
     */
    private function procesarPersona($ciData, $nombreCompleto, $observaciones, $mes, $userId, $fechaHoy, $fechaHoyString, $nombreCompletoUsuario, $fila)
    {
        $ci = $ciData['ci'];
        $complementoExcel = $ciData['complemento'];
        $personaExistente = Persona::where('ci_persona', $ci)->first();

        if (!$personaExistente) {
            return $this->crearNuevaPersona(
                $ci,
                $complementoExcel,
                $nombreCompleto,
                $observaciones,
                $mes,
                $userId,
                $fechaHoy,
                $fechaHoyString,
                $nombreCompletoUsuario
            );
        } else {
            return $this->actualizarPersonaExistente(
                $personaExistente,
                $complementoExcel,
                $observaciones,
                $mes,
                $userId,
                $fechaHoy,
                $fechaHoyString,
                $nombreCompletoUsuario,
                $ci
            );
        }
    }

    /**
     * Crear nueva persona
     */
    private function crearNuevaPersona($ci, $complemento, $nombreCompleto, $observaciones, $mes, $userId, $fechaHoy, $fechaHoyString, $nombreCompletoUsuario)
    {
        $nuevaPersona = Persona::create([
            'nombre_completo' => $nombreCompleto,
            'ci_persona' => $ci,
            'complemento' => $complemento,
            'tipo_registro' => 'pendiente',
            'fecha_registro' => $fechaHoyString,
        ]);

        $estadoInfo = $this->determinarEstadoSegunObservaciones($observaciones);

        HistorialEstados::create([
            'id_persona' => $nuevaPersona->id_persona,
            'estado' => $estadoInfo['estado'],
            'fecha_inicio' => $fechaHoyString,
            'fecha_fin' => null,
            'fecha_registro' => $fechaHoy,
            'usuario_modificacion' => $nombreCompletoUsuario,
            'observaciones' => $estadoInfo['observacion']
        ]);

        $habilitado = false;
        if ($this->debeCrearHabilitado($observaciones)) {
            $this->crearHabilitado($nuevaPersona->id_persona, $mes, $userId, $ci);
            $habilitado = true;
        }

        Log::info('Nueva persona creada desde Excel', [
            'ci' => $ci,
            'complemento' => $complemento,
            'nombre' => $nombreCompleto,
            'estado' => $estadoInfo['estado']
        ]);

        return [
            'tipo' => 'creada',
            'estado' => $estadoInfo['estado'],
            'habilitado' => $habilitado
        ];
    }

    /**
     * Actualizar persona existente
     */
    private function actualizarPersonaExistente($personaExistente, $complementoExcel, $observaciones, $mes, $userId, $fechaHoy, $fechaHoyString, $nombreCompletoUsuario, $ci)
    {
        if ($complementoExcel && !$personaExistente->complemento) {
            $personaExistente->update(['complemento' => $complementoExcel]);
        }

        if ($personaExistente->tipo_registro === 'registrado') {
            return $this->procesarPersonaRegistrada(
                $personaExistente,
                $observaciones,
                $mes,
                $userId,
                $fechaHoy,
                $fechaHoyString,
                $nombreCompletoUsuario,
                $ci
            );
        } elseif (in_array($personaExistente->tipo_registro, ['beneficiario', 'pendiente'])) {
            return $this->procesarPersonaBeneficiaria(
                $personaExistente,
                $observaciones,
                $mes,
                $userId,
                $fechaHoy,
                $fechaHoyString,
                $nombreCompletoUsuario,
                $ci
            );
        }

        return ['tipo' => null, 'estado' => null, 'habilitado' => false];
    }

    /**
     * Procesar persona con tipo_registro = 'registrado'
     */
    private function procesarPersonaRegistrada($personaExistente, $observaciones, $mes, $userId, $fechaHoy, $fechaHoyString, $nombreCompletoUsuario, $ci)
    {
        $personaExistente->update(['tipo_registro' => 'pendiente']);
        $estadoInfo = $this->determinarEstadoSegunObservaciones($observaciones);

        HistorialEstados::create([
            'id_persona' => $personaExistente->id_persona,
            'estado' => $estadoInfo['estado'],
            'fecha_inicio' => $fechaHoyString,
            'fecha_fin' => null,
            'fecha_registro' => $fechaHoy,
            'usuario_modificacion' => $nombreCompletoUsuario,
            'observaciones' => $estadoInfo['observacion']
        ]);

        $habilitado = false;
        if (!in_array($estadoInfo['estado'], ['baja_temporal', 'baja_definitiva'])) {
            $this->crearHabilitado($personaExistente->id_persona, $mes, $userId, $ci);
            $habilitado = true;
        }

        return [
            'tipo' => 'actualizada',
            'estado' => $estadoInfo['estado'],
            'habilitado' => $habilitado
        ];
    }

    /**
     * Procesar persona con tipo_registro = 'beneficiario' o 'pendiente'
     */
    private function procesarPersonaBeneficiaria($personaExistente, $observaciones, $mes, $userId, $fechaHoy, $fechaHoyString, $nombreCompletoUsuario, $ci)
    {
        $ultimoHistorial = HistorialEstados::where('id_persona', $personaExistente->id_persona)
            ->orderBy('id', 'desc')
            ->first();

        if (!$ultimoHistorial) {
            return ['tipo' => null, 'estado' => null, 'habilitado' => false];
        }

        if (in_array($ultimoHistorial->estado, ['baja_temporal', 'baja_definitiva'])) {
            return ['tipo' => null, 'estado' => $ultimoHistorial->estado, 'habilitado' => false];
        }

        if ($ultimoHistorial->estado === 'activo') {
            return $this->procesarBeneficiarioActivo(
                $personaExistente,
                $observaciones,
                $mes,
                $userId,
                $fechaHoy,
                $fechaHoyString,
                $nombreCompletoUsuario,
                $ci
            );
        }

        return ['tipo' => 'actualizada', 'estado' => 'activo', 'habilitado' => false];
    }

    /**
     * Procesar beneficiario con estado activo
     */
    private function procesarBeneficiarioActivo($personaExistente, $observaciones, $mes, $userId, $fechaHoy, $fechaHoyString, $nombreCompletoUsuario, $ci)
    {
        $habilitado = false;
        $estado = 'activo';

        if (!empty($observaciones)) {
            $estadoInfo = $this->determinarEstadoSegunObservaciones($observaciones);

            if (in_array($estadoInfo['estado'], ['baja_temporal', 'baja_definitiva'])) {
                HistorialEstados::create([
                    'id_persona' => $personaExistente->id_persona,
                    'estado' => $estadoInfo['estado'],
                    'fecha_inicio' => $fechaHoyString,
                    'fecha_fin' => null,
                    'fecha_registro' => $fechaHoy,
                    'usuario_modificacion' => $nombreCompletoUsuario,
                    'observaciones' => $observaciones
                ]);
                $estado = $estadoInfo['estado'];
            }
        } else {
            $this->crearHabilitado($personaExistente->id_persona, $mes, $userId, $ci);
            $habilitado = true;
        }

        return [
            'tipo' => 'actualizada',
            'estado' => $estado,
            'habilitado' => $habilitado
        ];
    }

    /**
     * Determinar estado según observaciones
     */
    private function determinarEstadoSegunObservaciones($observaciones)
    {
        $estado = 'activo';
        $observacionHistorial = $observaciones;

        if (!empty($observaciones)) {
            $observacionesUpper = strtoupper($observaciones);

            if (
                strpos($observacionesUpper, 'FALLECIO') !== false ||
                strpos($observacionesUpper, 'FALLECI') !== false ||
                strpos($observacionesUpper, 'MUERTO') !== false
            ) {
                $estado = 'baja_definitiva';
            } elseif (
                strpos($observacionesUpper, 'PADRE FUNCIONARIO TRABAJANDO CON ITEM') !== false ||
                strpos($observacionesUpper, 'FUNCIONARIO') !== false ||
                strpos($observacionesUpper, 'TRABAJANDO') !== false
            ) {
                $estado = 'baja_temporal';
            }
        }

        return [
            'estado' => $estado,
            'observacion' => $observacionHistorial
        ];
    }

    /**
     * Determinar si debe crear habilitado
     */
    private function debeCrearHabilitado($observaciones)
    {
        if (empty($observaciones)) {
            return true;
        }

        $observacionesUpper = strtoupper($observaciones);

        return stripos($observacionesUpper, 'FALLECIO') === false &&
            stripos($observacionesUpper, 'PADRE FUNCIONARIO TRABAJANDO CON ITEM') === false;
    }

    /**
     * Crear habilitado e historial habilitado
     */
    private function crearHabilitado($idPersona, $mes, $userId, $ci)
    {
        $habilitado = Habilitado::create([
            'habilitado' => 1,
            'id' => $userId,
            'id_persona' => $idPersona,
            'id_gestion' => $mes->id_gestion,
            'id_mes' => $mes->id_mes,
            'fecha_habilitado' => now()
        ]);

        HistorialHabilitado::create([
            'habilitado_historial' => 1,
            'id' => $userId,
            'id_persona' => $idPersona,
            'id_gestion' => $mes->id_gestion,
            'id_mes' => $mes->id_mes,
            'id_habilitado' => $habilitado->id_habilitado,
        ]);

        Log::info('Habilitado creado', [
            'ci' => $ci,
            'id_habilitado' => $habilitado->id_habilitado
        ]);

        return $habilitado;
    }

    public function reporte(Request $request)
    {
        $gestiones = Gestion::selectRaw('YEAR(gestion) as año')
            ->distinct()
            ->orderBy('año', 'desc')
            ->pluck('año');

        $year = $request->input('gestion');

        $request->session()->put([
            'gestion' => $request->input('gestion')
        ]);

        // Inicializar resultados como una colección vacía
        $resultados = collect([]);
        // Inicializar resultadoDatos como una colección vacía
        $resultadoDatos = collect([]);

        // Solo ejecutar la consulta si se proporciona al menos un filtro
        if (!empty($year)) {
            $query = Gestion::query()
                ->select([
                    'gestion.id_gestion as id',
                    'gestion.gestion as gestion',
                    'gestion.presupuesto_anual as presupuesto',
                    'mes.monto as monto',

                    // Conteos de personas
                    DB::raw('COUNT(DISTINCT habilitado.id_persona) as cantidad_habilitadas'),
                    DB::raw('COUNT(DISTINCT persona.id_persona) as total_personas'),
                    DB::raw('COUNT(DISTINCT persona.id_persona) - COUNT(DISTINCT habilitado.id_persona) as cantidad_no_habilitadas'),

                    // ✅ CORREGIDO: Usar historial_estados en lugar de persona.estado
                    DB::raw('COUNT(DISTINCT CASE
                                    WHEN carnet.id_persona IS NULL
                                        OR historial_estados.estado IN ("baja_temporal", "baja_definitiva")
                                        OR carnet.fecha_vencimiento < CURDATE()
                                    THEN persona.id_persona
                                    END) as inhabilitado'),

                    // ✅ CORREGIDO: Usar historial_estados para estado activo
                    DB::raw("COUNT(DISTINCT CASE WHEN historial_estados.estado = 'activo' THEN persona.id_persona END) as personas_activos"),

                    // Pagos
                    DB::raw('COUNT(pago.id_pago) as cantidad_total_pagos'),
                    DB::raw('COALESCE(SUM(pago.monto), 0) as total_pagado_contexto'),
                    DB::raw('COUNT(DISTINCT habilitado.id_persona) - COUNT(pago.id_pago) as cantidad_no_pagados'),
                    DB::raw('gestion.presupuesto_anual - COALESCE(SUM(pago.monto), 0) as presupuesto_restante'),

                    // ✅ SIMPLIFICADO: Una sola subconsulta para todos los cálculos anuales
                    DB::raw('(SELECT COALESCE(SUM(p2.monto), 0)
                                    FROM pago p2
                                    INNER JOIN habilitado h2 ON p2.id_habilitado = h2.id_habilitado
                                    INNER JOIN gestion g2 ON h2.id_gestion = g2.id_gestion
                                    WHERE YEAR(g2.gestion) = YEAR(gestion.gestion)
                                    ) as total_pagado_anual'),

                    // ✅ REUTILIZAR: Usar el cálculo anterior
                    DB::raw('(SELECT presupuesto_anual
              FROM gestion g2
              WHERE YEAR(g2.gestion) = YEAR(gestion.gestion)
                    AND g2.presupuesto_anual IS NOT NULL
              LIMIT 1) as presupuesto_anual'),

                    // ✅ CALCULADO: Restar en la aplicación o usar expresión simple
                    DB::raw('(SELECT COALESCE(g2.presupuesto_anual, 0) - COALESCE(pagos.total, 0)
              FROM gestion g2
              LEFT JOIN (
                  SELECT YEAR(g3.gestion) as year, SUM(p2.monto) as total
                  FROM pago p2
                  INNER JOIN habilitado h2 ON p2.id_habilitado = h2.id_habilitado
                  INNER JOIN gestion g3 ON h2.id_gestion = g3.id_gestion
                  GROUP BY YEAR(g3.gestion)
              ) pagos ON YEAR(g2.gestion) = pagos.year
              WHERE YEAR(g2.gestion) = YEAR(gestion.gestion)
                    AND g2.presupuesto_anual IS NOT NULL
              LIMIT 1) as presupuesto_anual_restante'),
                ])

                // ✅ MEJORADO: Usar JOIN más eficiente que CROSS JOIN
                ->from('gestion')
                ->join('persona', function ($join) {
                    // Solo personas relacionadas con gestiones
                    $join->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('habilitado')
                            ->whereColumn('id_persona', 'persona.id_persona');
                    });
                })

                // ✅ NUEVO: JOIN con historial_estados para obtener el último registro por persona
                /* ->join('historial_estados', function ($join) {
                    $join->on('historial_estados.id_persona', '=', 'persona.id_persona')
                        ->where('historial_estados.id', '=', function ($query) {
                            $query->select(DB::raw('MAX(he2.id)'))
                                ->from('historial_estados as he2')
                                ->whereColumn('he2.id_persona', 'historial_estados.id_persona');
                        });
                }) */

                ->leftJoin('habilitado', function ($join) {
                    $join->on('gestion.id_gestion', '=', 'habilitado.id_gestion')
                        ->on('habilitado.id_persona', '=', 'persona.id_persona');
                })
                ->leftJoin('mes', 'mes.id_gestion', '=', 'gestion.id_gestion')
                ->leftJoin('carnet', 'carnet.id_persona', '=', 'persona.id_persona')
                ->leftJoin('pago', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')

                ->groupBy('gestion.id_gestion', 'gestion.gestion', 'gestion.presupuesto_anual', 'mes.monto')
                ->orderBy('gestion.gestion', 'asc');

            // Aplicar filtro
            if (!empty($year)) {
                $query->whereYear('gestion.gestion', $year);
            }

            $resultados = $query->orderBy('gestion.id_gestion', 'asc')
                ->paginate(15)
                ->appends($request->query());

            // Agregar esto después de la consulta para ver el SQL generado
            /* dd($query->toSql(), $query->getBindings()); */
        }

        return Inertia::render('Gestion/reporteGestion', [
            'resultados' => $resultados,
            'gestiones' => $gestiones
        ]);
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
        // Buscar la gestión por ID
        $gestion = Gestion::findOrFail($id);

        // Guardar datos antiguos antes de la actualización
        $oldData = $gestion->getOriginal();

        // Guardamos los datos que vienen en el request
        $fieldsToUpdate = $request->all();

        // Preparar los cambios para el log
        $changes = [
            'campos_modificados' => [],
            'valores_anteriores' => [],
            'valores_nuevos' => []
        ];

        // Identificar qué campos han cambiado
        foreach ($fieldsToUpdate as $field => $newValue) {
            if (isset($oldData[$field]) && $oldData[$field] !== $newValue) {
                $changes['campos_modificados'][$field] = $field;
                $changes['valores_anteriores'][$field] = $oldData[$field];
                $changes['valores_nuevos'][$field] = $newValue;
            }
        }

        // Actualizamos los datos de la gestión
        $gestion->update($fieldsToUpdate);

        // Registro log
        $mes = Gestion::where('id_gestion', $gestion->id_gestion)->first();

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

        $mesNumero = Carbon::parse($mes->gestion)->month;
        $nombreMes = $meses[$mesNumero];
        $anio = date('Y', strtotime($gestion->gestion));
        $this->logService->logUpdate(
            'gestion',
            $gestion,
            $changes,
            'Registro de Mes Actualizado: ' . $nombreMes . ' - ' . $anio
        );
    }

    public function updateMes(Request $request, string $id)
    {
        // Buscar el mes por ID
        $mes = Mes::findOrFail($id);

        // Guardar datos antiguos antes de la actualización
        $oldData = $mes->getOriginal();

        // Guardamos los datos que vienen en el request (solo monto y presupuesto)
        $fieldsToUpdate = $request->only(['monto', 'presupuesto']);

        // Preparar los cambios para el log
        $changes = [
            'campos_modificados' => [],
            'valores_anteriores' => [],
            'valores_nuevos' => []
        ];

        // Identificar qué campos han cambiado
        foreach ($fieldsToUpdate as $field => $newValue) {
            if (isset($oldData[$field]) && $oldData[$field] !== $newValue) {
                $changes['campos_modificados'][$field] = $field;
                $changes['valores_anteriores'][$field] = $oldData[$field];
                $changes['valores_nuevos'][$field] = $newValue;
            }
        }

        // Actualizamos los datos del mes
        $mes->update($fieldsToUpdate);

        // Registro log
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

        $nombreMes = $meses[$mes->mes] ?? 'Desconocido';

        $this->logService->logUpdate(
            'mes',
            $mes,
            $changes,
            'Registro de Mes Actualizado: ' . $nombreMes . ' - Monto: ' . $mes->monto . ', Presupuesto: ' . $mes->presupuesto
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
