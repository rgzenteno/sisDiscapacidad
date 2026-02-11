<?php

namespace App\Http\Controllers;

use App\Services\NombreIAService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Imports\PostulantesImport;
use App\Models\Distrito;
use App\Models\HistorialEstados;
use App\Models\Persona;
use App\Models\Tutor;
use App\Services\LogService;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Services\SeparadorSimple;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RegistroGeneralController extends Controller
{

    protected $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //Campos para la busqueda
        $camposBusqueda = [
            'nombre_completo',
            'nombre_persona',
            'apellido_persona',
            'tipo_registro',
            'ci_persona',
            'complemento'
        ];

        $general = Persona::with([
            'tutor' => function ($query) {
                $query->select('id_tutor', 'nombre_tutor', 'apellido_tutor');
            },
            'ultimoEstado',  // 👈 Agregado para cargar el último estado
            'historialEstados' // 👈 Si necesitas el historial completo
        ])
            ->search(request('buscador'), $camposBusqueda)
            ->selectRaw('persona.*, UPPER(nombre_completo) as nombre_orden')
            ->orderByRaw('nombre_orden ASC')
            ->paginate(100)
            ->appends(request()->query());

        // 👇 Transform para agregar el estado actual a cada postulante
        $general->getCollection()->transform(function ($persona) {
            // Historial completo (si lo necesitas)
            $persona->historial_completo = $persona->historialEstados()
                ->orderBy('fecha_registro', 'desc')
                ->get();

            // Estado actual desde ultimoEstado
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

            // Ocultar relaciones innecesarias
            $persona->makeHidden(['historialEstados', 'ultimoEstado']);

            return $persona;
        });

        return Inertia::render('General/index', [
            'general' => $general,
            'distrito' => Distrito::all(),
            'tutor' => Tutor::all(),
            'filters' => [
                'buscador' => request('buscador')
            ]
        ]);
    }

    public function editRegistro(Request $request, string $id)
    {
        //dd($request->all());
        // 1. VALIDACIÓN
        $validated = $request->validate([
            'nombre_persona' => 'required|string|max:255',
            'apellido_persona' => 'required|string|max:255',
            'ci_persona' => 'required|string|max:20',
            'distrito' => 'nullable|string',
            'complemento' => 'nullable|string|max:5',
            'fecha_nacimiento' => 'nullable|date',
            'observacion_persona' => 'nullable|string',
            'documento_respaldo' => 'nullable|string',
        ]);

        // 2. BUSCAR LA PERSONA
        $persona = Persona::findOrFail($id);

        // 3. PREPARAR DATOS PARA ACTUALIZAR
        $dataToUpdate = $validated;
        $dataToUpdate['tipo_registro'] = 'beneficiario';

        // ✅ Construir observación de forma dinámica
        $observaciones = [];

        // Agregar tutor si existe en sesión
        if (session()->has('selected_tutor_id')) {
            $dataToUpdate['id_tutor'] = session('selected_tutor_id');
        } else {
            $observaciones[] = 'Tutor no proporcionado';
        }

        $observaciones[] = 'Carnet de discapacidad no proporcionado';

        // Unir todas las observaciones con coma
        $dataToUpdate['observacion_persona'] = implode(', ', $observaciones);

        // 4. ACTUALIZAR PERSONA
        $persona->update($dataToUpdate);

        // 5. CREAR HISTORIAL DE ESTADO
        $user = Auth::user();
        $nombreCompletoUsuario = "{$user->nombre} {$user->apellido}";

        // Usar relación en lugar de crear directamente
        $persona->historialEstados()->create([
            'estado' => 'activo',
            'fecha_inicio' => now('America/La_Paz')->format('Y-m-d'),
            'fecha_fin' => null,
            'fecha_registro' => $persona->fecha_registro,
            'usuario_modificacion' => $nombreCompletoUsuario,
            'observaciones' => 'Alta inicial en el sistema'
        ]);

        // 6. LOG DEL SISTEMA
        $nombreCompleto = $persona->nombre_completo;
        $this->logService->logCreation('Beneficiario', $persona, "Beneficiario creado: {$nombreCompleto}");

        // 7. LIMPIAR SESIÓN
        session()->forget('selected_tutor_id');
    }

    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:10240'
        ]);

        \DB::beginTransaction();

        try {
            $archivo = $request->file('archivo');
            $spreadsheet = IOFactory::load($archivo->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Ya no necesitas validar formato porque el frontend lo hizo
            // Solo busca los encabezados en las primeras 3 filas
            $inicioRegistros = null;

            for ($i = 0; $i < min(3, count($rows)); $i++) {
                // Buscar la fila que contiene "DOCUMENTO DE IDENTIDAD" o similar
                if (
                    isset($rows[$i][1]) &&
                    (stripos($rows[$i][1], 'DOCUMENTO') !== false ||
                        stripos($rows[$i][1], 'IDENTIDAD') !== false ||
                        $rows[$i][0] === 'Nº')
                ) {
                    $inicioRegistros = $i + 1;
                    break;
                }
            }

            // Si por alguna razón no se encontró, asumir fila 1 (row 0 = headers)
            if ($inicioRegistros === null) {
                $inicioRegistros = 1;
            }

            $insertados = [];
            $duplicados = [];
            $errores = [];
            $cisProcesados = [];

            // Procesar registros desde la fila después de los encabezados
            for ($index = $inicioRegistros; $index < count($rows); $index++) {
                $row = $rows[$index];

                // Saltar filas vacías
                if (empty($row[1]) || $row[0] === 'Nº') {
                    continue;
                }

                $ciCompleto = trim($row[1] ?? '');
                $nombreCompleto = mb_strtolower(trim($row[2] ?? ''), 'UTF-8');

                if (empty($nombreCompleto)) {
                    $errores[] = [
                        'fila' => $index + 1,
                        'ci' => $ciCompleto ?: 'Sin CI',
                        'nombre' => 'Sin nombre',
                        'motivo' => 'Nombre completo vacío'
                    ];
                    continue;
                }

                // Procesar CI y complemento
                $ci = $ciCompleto;
                $complemento = null;

                if (strpos($ciCompleto, '-') !== false) {
                    $partes = explode('-', $ciCompleto, 2);
                    $ci = trim($partes[0]);
                    $complemento = strtoupper(trim($partes[1]));
                }

                // Validar que CI sea numérico
                if (!is_numeric($ci)) {
                    $errores[] = [
                        'fila' => $index + 1,
                        'ci' => $ciCompleto,
                        'nombre' => $nombreCompleto,
                        'motivo' => "CI no numérico: '$ci'"
                    ];
                    continue;
                }

                $ciKey = $ci . ($complemento ? "-$complemento" : "");

                // Validar duplicado en el archivo actual
                if (isset($cisProcesados[$ciKey])) {
                    $duplicados[] = [
                        'fila' => $index + 1,
                        'ci' => $ciKey,
                        'nombre' => $nombreCompleto,
                        'motivo' => "Duplicado en el archivo (fila {$cisProcesados[$ciKey]})"
                    ];
                    continue;
                }

                // Verificar si ya existe en BD
                $existe = Persona::where('ci_persona', $ci)->exists();

                if ($existe) {
                    $registroExistente = Persona::where('ci_persona', $ci)->first();

                    $duplicados[] = [
                        'fila' => $index + 1,
                        'ci' => $ciKey,
                        'nombre' => $nombreCompleto,
                        'motivo' => 'Ya existe en la base de datos' .
                            ($registroExistente->complemento ? " (complemento: {$registroExistente->complemento})" : "")
                    ];
                    continue;
                }

                // Insertar nuevo registro
                try {
                    Persona::create([
                        'ci_persona' => $ci,
                        'complemento' => $complemento,
                        'nombre_completo' => $nombreCompleto,
                        'nombre_persona' => null,
                        'apellido_persona' => null,
                        'tutor_nombre' => !empty($row[3]) ? mb_strtolower(trim($row[3]), 'UTF-8') : null,
                        'documento_respaldo' => !empty($row[4]) ? trim($row[4]) : null,
                        'tipo_registro' => 'registrado',
                        'fecha_registro' => now(),
                        'distrito' => null,
                        'fecha_nacimiento' => null,
                        'observacion_persona' => null,
                        'id_tutor' => null,
                    ]);

                    $cisProcesados[$ciKey] = $index + 1;

                    $insertados[] = [
                        'fila' => $index + 1,
                        'ci' => $ciKey,
                        'nombre' => $nombreCompleto
                    ];
                } catch (\Exception $e) {
                    $errores[] = [
                        'fila' => $index + 1,
                        'ci' => $ciKey,
                        'nombre' => $nombreCompleto,
                        'motivo' => $e->getMessage()
                    ];
                }
            }

            \DB::commit();

            return back()->with('importResults', [
                'type' => count($insertados) > 0 ? 'success' : 'warning',
                'insertados' => $insertados,
                'duplicados' => $duplicados,
                'errores' => $errores,
                'total_procesado' => count($insertados) + count($duplicados) + count($errores),
                'message' => $this->generarMensajeResumen(count($insertados), count($duplicados), count($errores))
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();

            return back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    /**
     * Genera mensaje de resumen de la importación
     */
    private function generarMensajeResumen(int $insertados, int $duplicados, int $errores): string
    {
        $total = $insertados + $duplicados + $errores;
        $mensaje = "Importación completada: {$insertados} de {$total} registros insertados exitosamente.";

        if ($duplicados > 0) {
            $mensaje .= " {$duplicados} registros duplicados omitidos.";
        }

        if ($errores > 0) {
            $mensaje .= " {$errores} registros con errores.";
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
