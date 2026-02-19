<?php

namespace App\Http\Controllers;

use App\Models\Carnet;
use App\Models\Discapacidad;
use App\Models\Distrito;
use App\Models\Gestion;
use App\Models\HistorialEstados;
use App\Models\Mes;
use App\Models\Persona;
use App\Models\Tutor;
use App\Services\LogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Inertia\Inertia;

class PersonaController extends Controller
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
        $selectedTutorId = session('selected_tutor_id');
        $selectedTutorName = '';

        if ($selectedTutorId) {
            $selectedTutor = Tutor::findOrFail($selectedTutorId);
            $selectedTutorName = "{$selectedTutor->nombre_tutor} {$selectedTutor->apellido_tutor}";
        }

        $search = $request->input('buscador');

        $personas = Persona::conRelacionesCompletas()
            ->beneficiarios()
            ->busquedaGlobal($search)
            // 👇 SOLUCIÓN: Usar UPPER o LOWER para ignorar mayúsculas/minúsculas
            ->selectRaw('persona.*,
            UPPER(COALESCE(
                CONCAT(TRIM(apellido_persona), " ", TRIM(nombre_persona)),
                nombre_completo
            )) as nombre_orden')
            ->orderByRaw('nombre_orden ASC')
            ->paginate(30)
            ->withQueryString();

        $personas->getCollection()->transform(function ($persona) {
            $persona->historial_completo = $persona->historialEstados()
                ->orderBy('fecha_registro', 'desc')
                ->get();

            $estadoActual = $persona->ultimoEstado;

            if ($estadoActual) {
                $persona->id_estado = $estadoActual->id;
                $persona->estado_actual = $estadoActual->estado;
                $persona->fecha_inicio = $estadoActual->fecha_inicio;
                $persona->fecha_fin = $estadoActual->fecha_fin;
                $persona->fecha_registro_estado = $estadoActual->fecha_registro;
                $persona->usuario_modificacion = $estadoActual->usuario_modificacion;
                $persona->observaciones = $estadoActual->observaciones;
            }

            $persona->makeHidden(['historialEstados', 'ultimoEstado']);

            $persona->carnet_vigente = $persona->carnet &&
                $persona->carnet->fecha_vencimiento >= now() ? 1 : 0;

            return $persona;
        });

        return Inertia::render('Persona/index', [
            'persona' => $personas,
            'personas' => $personas,
            'distrito' => Distrito::all(),
            'discapacidad' => Discapacidad::all(),
            'carnet' => Carnet::all(),
            'tutor' => Tutor::all(),
            'selectedTutorName' => $selectedTutorName,
            'filters' => [
                'buscador' => $search
            ]
        ]);
    }

    public function estado(Request $request)
    {
        DB::beginTransaction();

        $user = Auth::user();
        $nombreCompleto = "{$user->nombre} {$user->apellido}";

        try {
            // Obtener el estado actual
            $estadoActual = HistorialEstados::where('id_persona', $request->id_persona)
                ->whereNull('fecha_fin')
                ->first();

            $fechaInicio = Carbon::parse($request->fecha_inicio);

            if ($estadoActual) {
                $fechaRegistro = Carbon::parse($estadoActual->fecha_registro);
                $mismodia = $fechaInicio->isSameDay($fechaRegistro);

                // Determinar fechas basado en la misma lógica
                $fechaFinPropuesta = $mismodia ? $fechaInicio : $fechaInicio->copy()->subDay();
                $fechaInicioNuevo = $mismodia ? $fechaInicio->copy()->addDay() : $fechaInicio;

                // Cerrar estado actual
                HistorialEstados::where('id_persona', $request->id_persona)
                    ->whereNull('fecha_fin')
                    ->update(['fecha_fin' => $fechaFinPropuesta]);
            } else {
                $fechaInicioNuevo = $fechaInicio;
            }

            // Determinar observaciones según el estado
            $observaciones = $request->observacion;

            if ($request->estado === 'baja_temporal') {
                $observaciones = 'PADRE FUNCIONARIO TRABAJANDO CON ITEM';
            } elseif ($request->estado === 'baja_definitiva') {
                $observaciones = 'FALLECIO';
            }

            // Crear nuevo registro
            HistorialEstados::create([
                'id_persona' => $request->id_persona,
                'estado' => $request->estado,
                'fecha_inicio' => $fechaInicioNuevo,
                'fecha_fin' => null,
                'usuario_modificacion' => $nombreCompleto,
                'observaciones' => $observaciones
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    public function reporte(Request $request)
    {
        $mesesNumeros = collect([]);
        $resultados = collect([]);
        $resultadosReporte = collect([]);

        $year = $request->input('gestion_gestion');
        $mes = $request->input('mes');

        // Si se seleccionó una gestión, obtener los meses disponibles
        if ($year) {
            $gestion = Gestion::where('gestion', $year)->first();

            if ($gestion) {
                $mesesNumeros = Mes::where('id_gestion', $gestion->id_gestion)
                    ->pluck('mes')
                    ->sort()
                    ->values();
            }
        }

        // Verificar que tengamos gestión y mes para ejecutar la consulta principal
        if ($year && $mes) {
            $gestion = Gestion::where('gestion', $year)->first();

            if ($gestion) {
                $query = Persona::query()
                    ->select([
                        'persona.id_persona',
                        'persona.ci_persona as ci',
                        'persona.complemento as complemento',
                        'persona.apellido_persona as apellido',
                        'persona.nombre_persona as nombre',
                        'mes.mes as mes',
                        'mes.monto as monto',
                        'gestion.gestion as gestion',
                        'historial_estados.observaciones as observaciones',
                        'historial_estados.estado as estado_actual',
                        DB::raw('COUNT(*) OVER() as total_personas'),
                        DB::raw('COUNT(*) OVER() * mes.monto as monto_total'),
                        DB::raw("COUNT(CASE WHEN historial_estados.estado IN ('baja_temporal', 'baja_definitiva') THEN 1 END) OVER() as cantidad_bajas"),
                        DB::raw("COUNT(CASE WHEN historial_estados.estado IN ('baja_temporal', 'baja_definitiva') THEN 1 END) OVER() * mes.monto as monto_bajas"),
                        DB::raw("COUNT(CASE WHEN historial_estados.estado = 'activo' THEN 1 END) OVER() as cantidad_activos"),
                        DB::raw("COUNT(CASE WHEN historial_estados.estado = 'activo' THEN 1 END) OVER() * mes.monto as monto_activos")
                    ])
                    ->join('carnet', 'carnet.id_persona', '=', 'persona.id_persona')
                    ->join('gestion', 'gestion.id_gestion', '=', DB::raw($gestion->id_gestion))
                    ->join('mes', function ($join) use ($gestion, $mes) {
                        $join->on('mes.id_gestion', '=', 'gestion.id_gestion')
                            ->where('mes.mes', '=', $mes);
                    })
                    ->leftJoin(
                        DB::raw('(SELECT id_persona, estado, observaciones FROM historial_estados WHERE id IN (SELECT MAX(id) FROM historial_estados GROUP BY id_persona)) as historial_estados'),
                        'historial_estados.id_persona',
                        '=',
                        'persona.id_persona'
                    )
                    ->whereNotNull('persona.id_tutor')
                    ->where('carnet.fecha_vencimiento', '>=', now())
                    ->whereRaw("DATE_FORMAT(persona.fecha_registro, '%Y%m') <= ?", [
                        sprintf('%04d%02d', $year, $mes)
                    ])
                    ->groupBy([
                        'persona.id_persona',
                        'persona.ci_persona',
                        'persona.complemento',
                        'persona.apellido_persona',
                        'persona.nombre_persona',
                        'mes.mes',
                        'mes.monto',
                        'gestion.gestion',
                        'historial_estados.observaciones',
                        'historial_estados.estado'
                    ])
                    ->orderBy('persona.apellido_persona', 'asc')
                    ->orderBy('persona.nombre_persona', 'asc');

                // Clonar la consulta ANTES de paginar
                $resultadosReporte = clone $query;

                // Paginación para la vista
                $resultados = $query->paginate(100)->appends($request->query());

                // Todos los resultados para imprimir/exportar
                $resultadosReporte = $resultadosReporte->get();
            }
        }

        // Obtener todas las gestiones disponibles
        $gestiones = Gestion::select('gestion as anio')
            ->distinct()
            ->orderBy('gestion', 'desc')
            ->get();

        return Inertia::render('Persona/reporteBeneficiario', [
            'resultados' => $resultados,
            'resultadosReporte' => $resultadosReporte,
            'gestiones' => $gestiones,
            'mesesNumeros' => $mesesNumeros,
        ]);
    }



    public function reporteGeneral(Request $request)
    {
        $resultados = collect([]);
        $year = $request->input('gestion_gestion');

        // Si se seleccionó una gestión
        if ($year) {
            $gestion = Gestion::where('gestion', $year)->first();

            if ($gestion) {
                // Aumentar el límite de GROUP_CONCAT
                DB::statement('SET SESSION group_concat_max_len = 10000');

                // Consulta optimizada con GROUP_CONCAT
                $resultados = DB::table('persona as p')
                    ->select([
                        'p.ci_persona',
                        'p.nombre_persona',
                        'p.apellido_persona',
                        DB::raw("GROUP_CONCAT(
                        CONCAT(m.mes, ':',
                            CASE
                                WHEN h.id_mes IS NOT NULL THEN 'H'
                                ELSE 'NH'
                            END
                        )
                        ORDER BY m.mes ASC
                        SEPARATOR ','
                    ) as meses_estados")
                    ])
                    ->crossJoin('mes as m')
                    ->leftJoin('habilitado as h', function ($join) {
                        $join->on('h.id_persona', '=', 'p.id_persona')
                            ->on('h.id_mes', '=', 'm.id_mes');
                    })
                    ->whereYear('p.fecha_registro', '<=', $year)
                    ->whereNotNull('p.id_tutor')
                    ->where('m.id_gestion', $gestion->id_gestion)
                    // NUEVO: Solo mostrar personas que tienen al menos un registro en habilitado para esta gestión
                    ->whereExists(function ($query) use ($gestion) {
                        $query->select(DB::raw(1))
                            ->from('habilitado as hab')
                            ->join('mes as mes_hab', 'mes_hab.id_mes', '=', 'hab.id_mes')
                            ->whereColumn('hab.id_persona', 'p.id_persona')
                            ->where('mes_hab.id_gestion', $gestion->id_gestion);
                    })
                    ->groupBy('p.id_persona', 'p.ci_persona', 'p.nombre_persona', 'p.apellido_persona')
                    ->orderBy('p.apellido_persona', 'asc')
                    ->orderBy('p.nombre_persona', 'asc')
                    ->get()
                    ->map(function ($persona) {
                        // Convertir el string de meses_estados a array
                        $mesesArray = collect(explode(',', $persona->meses_estados))
                            ->map(function ($mesEstado) {
                                list($mes, $estado) = explode(':', $mesEstado);
                                return [
                                    'mes' => (int) $mes,
                                    'estado' => $estado
                                ];
                            });

                        return [
                            'ci_persona' => $persona->ci_persona,
                            'nombre_persona' => $persona->nombre_persona,
                            'apellido_persona' => $persona->apellido_persona,
                            'meses' => $mesesArray
                        ];
                    });
            }
        }

        // Obtener todas las gestiones disponibles
        $gestiones = Gestion::select('gestion as anio')
            ->distinct()
            ->orderBy('gestion', 'desc')
            ->get();

        return Inertia::render('Persona/reporteGeneral', [
            'resultados' => $resultados,
            'gestiones' => $gestiones,
        ]);
    }

    public function mostrarFormulario()
    {
        // Usamos Inertia para devolver la vista con el formulario de importación
        return Inertia::render('Persona/importarExcel', []);
    }

    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:10240'
        ]);

        DB::beginTransaction();

        try {
            $archivo = $request->file('archivo');
            $spreadsheet = IOFactory::load($archivo->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            Log::info('Total de filas:', ['total' => count($rows)]);

            $insertados = 0;
            $actualizados = 0;
            $duplicados = 0;
            $errores = 0;

            // ✅ Obtener usuario autenticado
            $user = Auth::user();
            $nombreCompleto = "{$user->nombre} {$user->apellido}";

            foreach ($rows as $index => $row) {
                // ✅ SALTAR ENCABEZADO
                if ($index === 0 || empty($row[0])) {
                    continue;
                }

                try {
                    // ✅ Extraer datos básicos (5 columnas)
                    $nombre = $this->limpiarTexto($row[0] ?? null);
                    $apellido = $this->limpiarTexto($row[1] ?? null);
                    $distrito = $this->limpiarTexto($row[2] ?? null);
                    $ciCompleto = trim($row[3] ?? '');
                    $observacionRaw = $row[4] ?? null;

                    // ✅ Procesar observación: si es "NINGUNO" o vacío -> NULL
                    $observacion = $this->procesarObservacion($observacionRaw);

                    // ✅ Log de datos extraídos
                    Log::info("Fila $index: Datos extraídos", [
                        'nombre' => $nombre,
                        'apellido' => $apellido,
                        'distrito' => $distrito,
                        'ci' => $ciCompleto,
                        'observacion_raw' => $observacionRaw,
                        'observacion_procesada' => $observacion,
                    ]);

                    // ✅ Validar CI obligatorio
                    if (empty($ciCompleto)) {
                        Log::warning("Fila $index: CI vacío, saltando");
                        $errores++;
                        continue;
                    }

                    // ✅ Separar CI y complemento
                    $datosCi = $this->separarCIyComplemento($ciCompleto);
                    $ci = $datosCi['ci'];
                    $complemento = $datosCi['complemento'];

                    // ✅ Validar que CI sea numérico y tenga formato válido
                    if (!is_numeric($ci) || strlen($ci) < 5 || strlen($ci) > 10) {
                        Log::warning("Fila $index: CI inválido '$ciCompleto' (extraído: '$ci'), saltando");
                        $errores++;
                        continue;
                    }

                    // ✅ Validar que no sea una fecha mal interpretada
                    if (strlen($ci) > 8 && substr($ci, 0, 2) === '19') {
                        Log::warning("Fila $index: CI parece ser una fecha '$ci', saltando");
                        $errores++;
                        continue;
                    }

                    // ✅ VERIFICAR SI YA EXISTE EL CI (SOLO POR CI) usando Eloquent
                    $personaExistente = Persona::where('ci_persona', $ci)->first();

                    if ($personaExistente) {
                        Log::info("Fila $index: CI $ci encontrado en BD", [
                            'id_persona' => $personaExistente->id_persona,
                            'complemento_bd' => $personaExistente->complemento,
                            'complemento_excel' => $complemento,
                            'observacion_bd' => $personaExistente->observacion_persona,
                            'observacion_excel' => $observacion,
                        ]);

                        // ✅ VERIFICAR QUÉ CAMPOS NECESITAN ACTUALIZARSE
                        $datosActualizar = [];

                        if (empty($personaExistente->nombre_persona) && !empty($nombre)) {
                            $datosActualizar['nombre_persona'] = $nombre;
                        }

                        if (empty($personaExistente->apellido_persona) && !empty($apellido)) {
                            $datosActualizar['apellido_persona'] = $apellido;
                        }

                        if (empty($personaExistente->distrito) && !empty($distrito)) {
                            $datosActualizar['distrito'] = $distrito;
                        }

                        // ✅ Solo actualizar observación si está vacía en BD Y hay dato válido en Excel
                        if (empty($personaExistente->observacion_persona) && !empty($observacion)) {
                            $datosActualizar['observacion_persona'] = $observacion;
                        }

                        if (empty($personaExistente->complemento) && !empty($complemento)) {
                            $datosActualizar['complemento'] = $complemento;
                        }

                        // ✅ Si hay campos para actualizar
                        if (count($datosActualizar) > 0) {
                            $datosActualizar['tipo_registro'] = 'beneficiario';

                            Log::info("Fila $index: Actualizando campos vacíos", [
                                'ci' => $ci,
                                'campos_actualizar' => $datosActualizar
                            ]);

                            $personaExistente->update($datosActualizar);

                            // ✅ Crear registro en HistorialEstados solo si no existe uno activo usando Eloquent
                            $tieneEstadoActivo = HistorialEstados::where('id_persona', $personaExistente->id_persona)
                                ->where('estado', 'activo')
                                ->whereNull('fecha_fin')
                                ->exists();

                            if (!$tieneEstadoActivo) {
                                HistorialEstados::create([
                                    'id_persona' => $personaExistente->id_persona,
                                    'estado' => 'activo',
                                    'fecha_inicio' => $personaExistente->fecha_registro ?? Carbon::now()->format('Y-m-d'),
                                    'fecha_fin' => null,
                                    'usuario_modificacion' => $nombreCompleto,
                                    'observaciones' => ''
                                ]);
                            }

                            $actualizados++;
                        } else {
                            Log::info("Fila $index: CI $ci ya tiene todos los datos completos, marcando como duplicado");
                            $duplicados++;
                        }

                        continue;
                    }

                    // ✅ NO EXISTE: INSERTAR NUEVO REGISTRO
                    Log::info("Fila $index: CI $ci no existe, insertando nuevo registro");

                    $fechaRegistro = Carbon::now()->format('Y-m-d');

                    // ✅ Agregar mensaje de fecha de nacimiento a la observación
                    if (!empty($observacion)) {
                        $observacion .= ', Fecha de nacimiento no proporcionada';
                    } else {
                        $observacion = 'Fecha de nacimiento no proporcionada';
                    }

                    // ✅ Crear nueva persona usando Eloquent
                    $nuevaPersona = Persona::create([
                        'ci_persona' => $ci,
                        'complemento' => $complemento,
                        'nombre_persona' => $nombre,
                        'apellido_persona' => $apellido,
                        'distrito' => $distrito,
                        'fecha_nacimiento' => null,
                        'observacion_persona' => $observacion,
                        'tipo_registro' => 'beneficiario',
                        'fecha_registro' => $fechaRegistro,
                    ]);

                    Log::info("Fila $index: Registro insertado correctamente", [
                        'id_persona' => $nuevaPersona->id_persona,
                        'observacion' => $observacion
                    ]);

                    // ✅ Crear registro en HistorialEstados para nueva persona usando Eloquent
                    HistorialEstados::create([
                        'id_persona' => $nuevaPersona->id_persona,
                        'estado' => 'activo',
                        'fecha_inicio' => $fechaRegistro,
                        'fecha_fin' => null,
                        'usuario_modificacion' => $nombreCompleto,
                        'observaciones' => ''
                    ]);

                    $insertados++;
                } catch (\Exception $e) {
                    Log::error("Error procesando fila $index:", [
                        'error' => $e->getMessage(),
                        'ci' => $ci ?? 'desconocido',
                        'trace' => $e->getTraceAsString()
                    ]);
                    $errores++;
                }
            }

            DB::commit();

            Log::info("Importación finalizada", [
                'insertados' => $insertados,
                'actualizados' => $actualizados,
                'duplicados' => $duplicados,
                'errores' => $errores
            ]);

            return back()->with('importResults', [
                'type' => ($insertados > 0 || $actualizados > 0) ? 'success' : 'warning',
                'insertados' => $insertados,
                'actualizados' => $actualizados,
                'duplicados' => $duplicados,
                'errores' => $errores,
                'total_procesado' => $insertados + $actualizados + $duplicados + $errores,
                'message' => $this->generarMensajeResumen($insertados, $actualizados, $duplicados, $errores)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en importación:', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
            return back()->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }

    /**
     * Procesa observación: convierte "NINGUNO" o vacío a NULL
     */
    private function procesarObservacion($valor): ?string
    {
        if (empty($valor)) {
            return null;
        }

        $texto = trim($valor);

        // Si es vacío o "NINGUNO" (case insensitive) o "activo" -> retornar NULL
        if (
            $texto === '' ||
            strtoupper($texto) === 'NINGUNO' ||
            strtoupper($texto) === 'NINGUNA' ||
            strtolower($texto) === 'activo'
        ) {
            return null;
        }

        // Limpiar y capitalizar
        return ucfirst(strtolower($texto));
    }

    /**
     * Separa CI y complemento si tiene guion
     */
    private function separarCIyComplemento(string $ciCompleto): array
    {
        $ci = $ciCompleto;
        $complemento = null;

        if (strpos($ciCompleto, '-') !== false) {
            $partes = explode('-', $ciCompleto, 2);
            $ci = trim($partes[0]);
            $complemento = strtoupper(trim($partes[1]));
        }

        // Eliminar espacios
        $ci = str_replace(' ', '', $ci);

        if ($complemento) {
            $complemento = str_replace(' ', '', $complemento);
            if (empty($complemento)) {
                $complemento = null;
            }
        }

        return ['ci' => $ci, 'complemento' => $complemento];
    }

    /**
     * Limpia texto: trim, capitaliza primera letra de cada palabra
     */
    private function limpiarTexto($valor): ?string
    {
        if (empty($valor)) {
            return null;
        }

        $texto = trim($valor);
        if ($texto === '') {
            return null;
        }

        return ucwords(strtolower($texto));
    }

    /**
     * Genera mensaje de resumen
     */
    private function generarMensajeResumen(int $insertados, int $actualizados, int $duplicados, int $errores): string
    {
        $mensaje = "Importación completada: {$insertados} nuevos registros.";

        if ($actualizados > 0) {
            $mensaje .= " {$actualizados} registros actualizados.";
        }

        if ($duplicados > 0) {
            $mensaje .= " {$duplicados} registros ya completos (ignorados).";
        }

        if ($errores > 0) {
            $mensaje .= " {$errores} errores.";
        }

        return $mensaje;
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
        // Verificar si existe la sesión y obtener el ID del tutor
        if (session('selected_tutor_id')) {
            $id_tutor = session('selected_tutor_id');
        }

        $fecha_actual = Carbon::now('America/La_Paz')->format('Y-m-d');

        // Preparar los datos para la creación
        $data = $request->all();

        // Verificar si ya existe una persona con ese CI
        $existePersona = Persona::where('ci_persona', $data['ci_persona'])->exists();

        if ($existePersona) {
            $persona = Persona::where('ci_persona', $data['ci_persona'])->first();
            return back()
                ->withErrors(['ci_persona' => 'Ya existe una persona con esta cédula'])
                ->with('persona_existente', [
                    'tipo_registro' => $persona->tipo_registro
                ]);
        }

        // Agregar el ID del tutor si existe
        if (isset($id_tutor)) {
            $data['id_tutor'] = $id_tutor;
        }

        $data['fecha_registro'] = $fecha_actual;
        $data['tipo_registro'] = 'beneficiario';

        // ✅ Construir observación de forma dinámica
        $observaciones = [];

        if (!isset($id_tutor)) {
            $observaciones[] = 'Tutor no proporcionado';
        }

        $observaciones[] = 'Carnet de discapacidad no proporcionado';

        // Unir todas las observaciones con coma
        $data['observacion_persona'] = implode(', ', $observaciones);

        // Crear el registro en la base de datos
        $persona = Persona::create($data);

        $user = Auth::user();

        // Para mostrarlo junto
        $nombreCompleto = "{$user->nombre} {$user->apellido}";

        $dataEstado = [
            'id_persona' => $persona->id_persona,
            'estado' => 'activo', // Estado inicial
            'fecha_inicio' => $fecha_actual,
            'fecha_fin' => null, // Estado actual
            'usuario_modificacion' => $nombreCompleto,
            'observaciones' => ''
        ];

        $estado = HistorialEstados::create($dataEstado);

        // Registra la creación
        $nombre = ucwords(strtolower("{$persona->nombre_persona} {$persona->apellido_persona}"));
        $this->logService->logCreation('Beneficiario', $persona, "Beneficiario creado: {$nombre}");

        // Eliminar la sesión del tutor seleccionado
        session()->forget('selected_tutor_id');

        return redirect()->back();
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $persona = Persona::findOrFail($id);

        // Guardamos los datos que vienen en el request
        $fieldsToUpdate = $request->all();

        unset($fieldsToUpdate['id_persona']);

        // ✅ Verificar y actualizar tipo_registro si es necesario
        if ($persona->tipo_registro === 'pendiente') {
            $fieldsToUpdate['tipo_registro'] = 'beneficiario';
        }

        // ✅ Limpiar observación si se está enviando el campo (incluso si es null o vacío)
        if (array_key_exists('observacion_persona', $fieldsToUpdate)) {
            $observacion = $fieldsToUpdate['observacion_persona'];

            // Si el usuario envió una observación vacía o null intencionalmente, respetarla
            if (empty($observacion)) {
                $fieldsToUpdate['observacion_persona'] = null;
            } else {
                // Eliminar "Fecha de nacimiento no proporcionada"
                $observacion = str_ireplace('Fecha de nacimiento no proporcionada', '', $observacion);

                // Limpiar comas y espacios sobrantes
                $observacion = preg_replace('/\s*,\s*,\s*/', ', ', $observacion); // ,, -> ,
                $observacion = preg_replace('/^\s*,\s*/', '', $observacion); // coma al inicio
                $observacion = preg_replace('/\s*,\s*$/', '', $observacion); // coma al final
                $observacion = trim($observacion);

                // Si quedó vacío después de limpiar, establecer como null
                $fieldsToUpdate['observacion_persona'] = empty($observacion) ? null : $observacion;
            }
        } else {
            // ✅ PLAN B: Si la observación no viene en el request pero la persona ya tiene una, limpiarla también
            if (!empty($persona->observacion_persona)) {
                $observacion = $persona->observacion_persona;

                $observacion = str_ireplace('Fecha de nacimiento no proporcionada', '', $observacion);
                $observacion = preg_replace('/\s*,\s*,\s*/', ', ', $observacion);
                $observacion = preg_replace('/^\s*,\s*/', '', $observacion);
                $observacion = preg_replace('/\s*,\s*$/', '', $observacion);
                $observacion = trim($observacion);

                $fieldsToUpdate['observacion_persona'] = empty($observacion) ? null : $observacion;
            }
        }

        // Preparar los cambios para el log
        $changes = [
            'campos_modificados' => [],
            'valores_anteriores' => [],
            'valores_nuevos' => []
        ];

        // Actualizamos los datos de la persona
        $persona->update($fieldsToUpdate);

        // Registrar el log
        $nombre = ucwords(strtolower("{$persona->nombre_persona} {$persona->apellido_persona}"));
        $this->logService->logUpdate(
            'personas',
            $persona,
            $changes,
            'Registro Actualizado: ' . $nombre
        );
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroyEstado(string $id)
    {
        $historialEstado = HistorialEstados::findOrFail($id);

        $primerRegistro = HistorialEstados::where('id_persona', $historialEstado->id_persona)
            ->orderBy('fecha_registro', 'asc')
            ->first();

        if ($historialEstado->id === $primerRegistro->id) {
            abort(403, 'No se puede eliminar el estado inicial.');
        }

        $estadoAnterior = HistorialEstados::where('id_persona', $historialEstado->id_persona)
            ->where('fecha_inicio', '<', $historialEstado->fecha_inicio)
            ->orderBy('fecha_inicio', 'desc')
            ->first();

        if ($estadoAnterior) {
            $estadoAnterior->update(['fecha_fin' => null]);
        }

        $historialEstado->delete();
    }
}
