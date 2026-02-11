<?php

namespace App\Http\Controllers;

use App\Models\Carnet;
use App\Models\Gestion;
use App\Models\Habilitado;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\Tutor;
use Exception;
use App\Models\User;
use App\Services\LogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
        // Obtener el id_habilitado del request
        $id_habilitado = $request->id_habilitado;
        $tipo = $request->tipo;

        // Buscar el monto asociado al habilitado
        $habilitado = Habilitado::query()
            ->join('gestion', 'habilitado.id_gestion', 'gestion.id_gestion')
            ->join('mes', 'gestion.id_gestion', 'mes.id_gestion')
            ->join('persona', 'habilitado.id_persona', 'persona.id_persona')
            ->join('tutor', 'persona.id_tutor', 'tutor.id_tutor')
            ->where('habilitado.id_habilitado', '=', $id_habilitado)
            ->select(
                'persona.nombre_persona',
                'persona.apellido_persona',
                'tutor.id_tutor',
                'mes.monto',
                'mes.mes',
                'gestion.gestion',
                'habilitado.id_habilitado'
            )
            ->first();

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
        $mesNumero = Carbon::parse($habilitado->getAttributes()['gestion'])->month;

        // Obtener el nombre del mes
        $nombreMes = $meses[$mesNumero];

        if (!$habilitado) {
            return response()->json(['error' => 'Habilitado no encontrado'], 404);
        }

        $fechaHoy = Carbon::now('America/La_Paz')->format('Y-m-d');

        // ✅ GENERAR EL NÚMERO DE BOLETA CORRELATIVO
        $numeroBoleta = $this->generarNumeroBoleta($id_habilitado);

        // Preparar los datos para crear el pago
        $data = $request->all();
        $data['fecha_pago'] = now();
        $data['pago'] = '1';
        $data['tipo_pago'] = $tipo;
        $data['monto'] = $habilitado->monto;
        $data['numero_boleta'] = $numeroBoleta; // ✅ AGREGAR EL NÚMERO DE BOLETA

        // Capitalizar nombre y apellido de la persona
        $nombrePersona = ucwords(strtolower("{$habilitado->nombre_persona} {$habilitado->apellido_persona}"));
        // Capitalizar el nombre del mes
        $nombreMes = ucwords(strtolower($nombreMes));

        Pago::create($data);

        $descripcion = sprintf(
            'Se realizó el pago %s para el beneficiario %s. ',
            'en efectivo',
            $nombrePersona
        );

        // IMPORTANTE: Pasamos los datos extras en el formato correcto para la vista
        $extraData = [
            'detalles' => [
                'mes' => $nombreMes,
                'beneficiario' => $nombrePersona,
                'tipo_pago' => ucfirst('En efectivo'),
                'monto' => $habilitado->monto,
                'numero_boleta' => $numeroBoleta // ✅ AGREGAR AL LOG
            ]
        ];

        // Modificación - atributos necesarios al objeto antes de pasarlo
        $habilitado->nombre = $nombrePersona;

        // Registrar en el log con datos adicionales
        $this->logService->log(
            'pago realizado',
            'Pago',
            $descripcion,
            $habilitado,
            $extraData
        );
    }

    // Agregar este método en tu controlador (antes o después del método store)
    private function generarNumeroBoleta($idHabilitado)
{
    // Obtener la gestión del habilitado
    $habilitado = Habilitado::query()
        ->join('gestion', 'habilitado.id_gestion', '=', 'gestion.id_gestion')
        ->where('habilitado.id_habilitado', $idHabilitado)
        ->select('gestion.id_gestion')
        ->first();

    if (!$habilitado) {
        return null;
    }

    $idGestion = $habilitado->id_gestion;

    // Obtener el último número de boleta de esa gestión
    $ultimoNumero = Pago::query()
        ->join('habilitado', 'pago.id_habilitado', '=', 'habilitado.id_habilitado')
        ->where('habilitado.id_gestion', $idGestion)
        ->whereNotNull('pago.numero_boleta')
        ->selectRaw('MAX(CAST(pago.numero_boleta AS UNSIGNED)) as ultimo')
        ->value('ultimo') ?? 0;

    // Generar el nuevo número con 6 dígitos
    $nuevoNumero = $ultimoNumero + 1;

    return str_pad($nuevoNumero, 6, '0', STR_PAD_LEFT);
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
            ->join('tutor', 'persona.id_tutor', '=', 'tutor.id_tutor')
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

        $gestion = session('gestion');

        $distrito = session('distrito');

        $empleado = null;

        $this->logService->logReportPrint(
            'Reporte',
            'Reporte de Pago Mensual a Beneficiarios',
            [
                'Gestión' => $gestion,
                'Distrito' => $distrito ?? 'sin distrito',
            ],
            $empleado,
            $request->descripcion,
            'PDF'
        );
        $request->session()->forget(['gestion', 'distrito']);
    }

    public function reportePago(Request $request)
    {

        $gestiones = Gestion::selectRaw('YEAR(gestion) as año')
            ->distinct()
            ->orderBy('año', 'desc')
            ->pluck('año');

        $gestion = $request->input('gestion');
        $mes = $request->input('mes');
        $distrito = $request->input('distrito');

        // Corregido: sintaxis incorrecta en session()->put()
        $request->session()->put([
            'gestion' => $gestion,
            'mes' => $mes, // Error corregido: era $mes = en lugar de 'mes' =>
            'distrito' => $distrito
        ]);

        // Inicializar resultados como una colección vacía
        $resultados = collect([]);
        // Inicializar resultadoDatos como una colección vacía
        $resultadoDatos = collect([]);

        // Solo ejecutar la consulta si se proporciona al menos un filtro
        if (!empty($gestion) || !empty($distrito)) {
            $query = Habilitado::query()
                ->join('pago', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
                ->join('persona', 'habilitado.id_persona', '=', 'persona.id_persona')
                ->join('tutor', 'persona.id_tutor', '=', 'tutor.id_tutor')
                ->join('gestion', 'habilitado.id_gestion', '=', 'gestion.id_gestion')
                ->select([
                    'persona.id_persona',
                    'persona.nombre_persona',
                    'persona.apellido_persona',
                    'persona.ci_persona',
                    'persona.distrito',
                    'tutor.nombre_tutor',
                    'tutor.apellido_tutor',
                    'tutor.telefono',
                    'tutor.direccion',
                    'gestion.gestion',
                    'gestion.monto',
                    'habilitado.id_habilitado',
                    'habilitado.fecha_habilitado',
                    'habilitado.observaciones_habilitado',
                    'pago.fecha_pago'
                ]);

            // Aplicar filtros - Corregido: usar 'gestion' en lugar de 'anio'
            if ($request->filled('gestion')) {
                $query->whereYear('gestion.gestion', $gestion);
            }

            // Corregido: convertir nombre del mes a número
            if ($request->filled('mes')) {
                $mesNumero = $this->convertirMesANumero($mes);
                if ($mesNumero) {
                    $query->whereMonth('gestion.gestion', $mesNumero);
                }
            }

            if (!empty($distrito)) {
                $query->where('persona.distrito', $distrito);
            }

            // Obtener datos paginados con los filtros aplicados
            $queryClone = clone $query;

            $resultados = $query->orderBy('persona.id_persona', 'desc')
                ->paginate(15)
                ->appends($request->query());

            $resultadoDatos = $queryClone->orderBy('persona.id_persona', 'desc')->get();
        }

        return Inertia::render('Pago/reportePago', [
            'resultados' => $resultados,
            'resultadoDatos' => $resultadoDatos,
            'gestiones' => $gestiones
        ]);
    }

    // Método auxiliar para convertir nombres de meses a números
    private function convertirMesANumero($nombreMes)
    {
        $meses = [
            'Enero' => 1,
            'Febrero' => 2,
            'Marzo' => 3,
            'Abril' => 4,
            'Mayo' => 5,
            'Junio' => 6,
            'Julio' => 7,
            'Agosto' => 8,
            'Septiembre' => 9,
            'Octubre' => 10,
            'Noviembre' => 11,
            'Diciembre' => 12
        ];

        return $meses[$nombreMes] ?? null;
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
