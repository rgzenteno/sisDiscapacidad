<?php

namespace App\Http\Controllers;

use App\Models\Habilitado;
use App\Models\Persona;
use App\Models\Tutor;
use App\Models\User;
use Exception;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TutorController extends Controller
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

        // Construir la consulta base para la tabla 'tutors'
        $query = Tutor::query();

        // Aplicar filtros de búsqueda si hay un término proporcionado
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre_tutor', 'like', "%{$search}%")
                    ->orWhere('apellido_tutor', 'like', "%{$search}%")
                    ->orWhere('ci_tutor', 'like', "%{$search}%")
                    ->orWhere('telefono', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('direccion', 'like', "%{$search}%");
            });
        }

        // Obtener datos paginados con los filtros aplicados
        $tutor = $query->orderBy('id_tutor', 'desc')
            ->paginate(50)
            ->withQueryString();

        // Retornar la vista con los datos paginados
        return Inertia::render('Tutor/index', [
            'totalTutores' => Tutor::count(),
            'tutor' => $tutor,
            'tutores' => $tutor,
            'filters' => [
                'buscador' => $search, // Mantiene el término de búsqueda
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $existingTutor = Tutor::where('ci_tutor', $data['ci_tutor'])->first();

        if ($existingTutor) {
            session(['selected_tutor_id' => $existingTutor->id_tutor]);

            $campos = ['nombre_tutor', 'apellido_tutor', 'ci_tutor', 'complemento_tutor', 'telefono', 'direccion', 'email'];
            $cambios = [];

            foreach ($campos as $campo) {
                $valorNuevo    = $data[$campo] ?? null;
                $valorAnterior = $existingTutor->$campo;

                if ($valorNuevo !== $valorAnterior) {
                    $cambios[$campo] = ['anterior' => $valorAnterior, 'nuevo' => $valorNuevo];
                }
            }

            if (!empty($cambios)) {
                $existingTutor->update(array_combine(
                    array_keys($cambios),
                    array_map(fn($c) => $c['nuevo'], $cambios)
                ));

                $nombre = ucwords(strtolower("{$existingTutor->nombre_tutor} {$existingTutor->apellido_tutor}"));

                $mapaLabels = [
                    'nombre_tutor'      => 'nombre completo',
                    'apellido_tutor'    => 'apellido',
                    'ci_tutor'          => 'c.i.',
                    'complemento_tutor' => 'complemento',
                    'telefono'          => 'teléfono',
                    'direccion'         => 'dirección',
                    'email'             => 'email',
                ];

                $camposModificados = [];
                $valoresAnteriores = [];
                $valoresNuevos     = [];

                foreach ($cambios as $campo => $vals) {
                    $label = $mapaLabels[$campo] ?? $campo;
                    $camposModificados[$label] = $vals['nuevo'];
                    $valoresAnteriores[$label] = $vals['anterior'];
                    $valoresNuevos[$label]     = $vals['nuevo'];
                }

                $this->logService->logUpdate(
                    'Tutor',
                    $existingTutor,
                    [
                        'campos_modificados' => $camposModificados,
                        'valores_anteriores' => $valoresAnteriores,
                        'valores_nuevos'     => $valoresNuevos,
                    ],
                    "Se actualizaron los datos del tutor {$nombre}."
                );
            }

            $tutor = $existingTutor->fresh();
        } else {
            $tutor = Tutor::create($data);

            $nombre = ucwords(strtolower("{$tutor->nombre_tutor} {$tutor->apellido_tutor}"));

            $this->logService->logCreation(
                'Tutor',
                $tutor,
                "Se registró al tutor {$nombre} en el sistema.",
                null,
                [
                    'nombre completo' => $nombre,
                    'c.i.'            => $tutor->ci_tutor,
                    'complemento'            => $tutor->complemento_tutor,
                    'teléfono'        => $tutor->telefono ?? null,
                    'dirección'       => $tutor->direccion ?? null,
                    'email'           => $tutor->email ?? null,
                ]
            );

            session(['selected_tutor_id' => $tutor->id_tutor]);
        }

        if ($request->has('id_persona') && $request->id_persona !== null) {
            $persona = Persona::findOrFail($request->id_persona);

            // Guardar tutor anterior antes de modificar
            $tutorAnterior = $persona->tutor; // relación belongsTo

            $persona->id_tutor = $tutor->id_tutor;

            if (!empty($persona->observacion_persona)) {
                $observacion = $persona->observacion_persona;
                $observacion = str_ireplace('Tutor no proporcionado', '', $observacion);
                $observacion = preg_replace('/\s*,\s*,\s*/', ', ', $observacion);
                $observacion = preg_replace('/^\s*,\s*/', '', $observacion);
                $observacion = preg_replace('/\s*,\s*$/', '', $observacion);
                $observacion = trim($observacion);
                $persona->observacion_persona = empty($observacion) ? null : $observacion;
            }

            $persona->save();
            session()->forget('selected_tutor_id');

            $nombreBeneficiario = ucwords(strtolower("{$persona->nombre_persona} {$persona->apellido_persona}"));
            $nombreTutorNuevo   = ucwords(strtolower("{$tutor->nombre_tutor} {$tutor->apellido_tutor}"));

            // Si había un tutor antes, loguear el cambio; si no, loguear la asignación inicial
            if ($tutorAnterior) {
                $nombreTutorAnterior = ucwords(strtolower("{$tutorAnterior->nombre_tutor} {$tutorAnterior->apellido_tutor}"));

                $this->logService->logUpdate(
                    'Beneficiario',
                    $persona,
                    [
                        'campos_modificados' => ['tutor' => $nombreTutorNuevo],
                        'valores_anteriores' => ['tutor' => "{$nombreTutorAnterior} (C.I. {$tutorAnterior->ci_tutor})"],
                        'valores_nuevos'     => ['tutor' => "{$nombreTutorNuevo} (C.I. {$tutor->ci_tutor})"],
                    ],
                    "Se cambió el tutor de {$nombreBeneficiario} en el sistema."
                );
            } else {
                $this->logService->logUpdate(
                    'Beneficiario',
                    $persona,
                    [
                        'campos_modificados' => ['tutor' => $nombreTutorNuevo],
                        'valores_anteriores' => ['tutor' => null],
                        'valores_nuevos'     => ['tutor' => "{$nombreTutorNuevo} (C.I. {$tutor->ci_tutor})"],
                    ],
                    "Se asignó el tutor al beneficiario {$nombreBeneficiario}."
                );
            }
        }

        return back();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    // Controlador
    public function tutorados(string $encrypted_id)
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

        // Usar el ID desencriptado en lugar de $request->query('code')
        $id_tutor = $id;

        $tutorData = Tutor::query()
            ->where('id_tutor', '=', $id_tutor)
            ->select(
                'nombre_tutor',
                'apellido_tutor',
                'telefono',
                'email',
                'direccion',
            )
            ->first();

        /*  $user = Auth::user();

        $this->logService->logView('usuarios', $user, 'El Usuario ' . $user . ' vizualizó Descripción'); */

        $personas = Persona::query()
            ->leftJoin('carnet', 'persona.id_persona', '=', 'carnet.id_persona')
            ->leftJoin('habilitado', 'persona.id_persona', '=', 'habilitado.id_persona')
            ->leftJoin('pago', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
            ->leftJoin(DB::raw('(
                        SELECT id_persona, estado, id
                        FROM historial_estados h1
                        WHERE h1.id = (
                            SELECT MAX(h2.id)
                            FROM historial_estados h2
                            WHERE h2.id_persona = h1.id_persona
                        )
                    ) as ultimo_historial'), 'persona.id_persona', '=', 'ultimo_historial.id_persona')
            ->where('persona.id_tutor', '=', $id_tutor)
            ->select(
                'persona.id_persona',
                'persona.nombre_persona as nombre',
                'persona.apellido_persona as apellido',
                'persona.nombre_completo',
                'persona.distrito',
                'persona.ci_persona as ci',
                'persona.complemento',
                'persona.observacion_persona as observacion',
                'carnet.id_carnet',
                DB::raw('MAX(habilitado.id_habilitado) as id_habilitado'),
                DB::raw('MAX(habilitado.habilitado) as habilitado'),
                DB::raw('MAX(pago.id_pago) as id_pago'),
                DB::raw('MAX(pago.fecha_pago) as fecha_pago'),
                'ultimo_historial.estado as estado',
                DB::raw('(CASE WHEN MAX(habilitado.id_habilitado) IS NOT NULL THEN 1 ELSE 0 END) as tiene_habilitado'),
                DB::raw('(CASE WHEN MAX(pago.id_pago) IS NOT NULL THEN 1 ELSE 0 END) as tiene_pago')
            )
            ->groupBy(
                'persona.id_persona',
                'persona.nombre_persona',
                'persona.apellido_persona',
                'persona.distrito',
                'persona.ci_persona',
                'persona.complemento',
                'persona.observacion_persona',
                'carnet.id_carnet',
                'ultimo_historial.estado'
            )
            ->paginate(12);

        return Inertia::render('Tutor/tutorados', [
            'tutorData' => $tutorData,
            'personas' => $personas,
            'totalPersonas' => $personas->total(),
        ]);
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
        $tutor = Tutor::findOrFail($id);

        $fieldsToUpdate = $request->all();

        $mapaLabels = [
            'nombre_tutor'   => 'nombre',
            'apellido_tutor' => 'apellido',
            'ci_tutor'       => 'c.i.',
            'telefono'       => 'teléfono',
            'direccion'      => 'dirección',
            'email'          => 'email',
        ];

        $camposModificados = [];
        $valoresAnteriores = [];
        $valoresNuevos     = [];

        foreach ($fieldsToUpdate as $campo => $nuevoValor) {
            if (!array_key_exists($campo, $mapaLabels)) continue;

            $valorAnterior = $tutor->$campo;
            $label         = $mapaLabels[$campo];

            if ($valorAnterior != $nuevoValor) {
                $camposModificados[$label] = $nuevoValor;
                $valoresAnteriores[$label] = $valorAnterior;
                $valoresNuevos[$label]     = $nuevoValor;
            }
        }

        if (empty($camposModificados)) {
            return;
        }

        $tutor->update($fieldsToUpdate);

        $nombre = ucwords(strtolower("{$tutor->nombre_tutor} {$tutor->apellido_tutor}"));

        $this->logService->logUpdate(
            'Tutor',
            $tutor,
            [
                'campos_modificados' => $camposModificados,
                'valores_anteriores' => $valoresAnteriores,
                'valores_nuevos'     => $valoresNuevos,
            ],
            "Se actualizó el registro del tutor {$nombre} en el sistema."
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