<?php

namespace App\Http\Controllers;

use App\Models\Carnet;
use App\Models\Persona;
use App\Services\LogService;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CarnetController extends Controller
{

    protected $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(string $encrypted_id)
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

        $nombre = Persona::query()->where('persona.id_persona', '=', $id)->first();

        if (!$nombre) {
            abort(404, 'Persona no encontrada');
        }

        // DEBUG: Agregar logs temporales
        $referrer = request()->headers->get('referer');
        $sessionOrigen = session('carnet_origen');

        $origen = 'Beneficiario'; // Default

        if ($referrer) {
            if (str_contains($referrer, '/habilitado')) {
                $origen = 'Habilitaciones';
                session(['carnet_origen' => 'Habilitaciones']);
            } elseif (str_contains($referrer, '/persona')) {
                $origen = 'Beneficiarios';
                session(['carnet_origen' => 'Beneficiarios']);
            } else {
                $origen = session('carnet_origen', 'Beneficiarios');
            }
        } else {
            $origen = session('carnet_origen', 'Beneficiarios');
        }

        $nombreCompleto = $nombre->nombre_persona . ' ' . $nombre->apellido_persona;

        return Inertia::render('Carnet/index', [
            'persona' => Persona::all(),
            'carnet' => Carnet::all(),
            'id_persona' => $id,
            'origen' => $origen,
            'nombreCompleto' => $nombreCompleto
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
        //dd($request->all());
        $data = $request->all();

        $id_persona = $data['id_persona'];

        // Buscar la persona
        $persona = Persona::find($id_persona);


        if (!empty($data['indefinido'])) {
            $data['fecha_emision'] = null;
            $data['fecha_vencimiento'] = now();
        }

        // Crear el carnet con la fecha_vencimiento calculada
        $carnet = Carnet::create($data);

        // ✅ Limpiar observación de la persona eliminando "Carnet de discapacidad no proporcionado"
        if (!empty($persona->observacion_persona)) {
            $observacion = $persona->observacion_persona;
            // Eliminar "Carnet de discapacidad no proporcionado"
            $observacion = str_ireplace('Carnet de discapacidad no proporcionado', '', $observacion);
            // Limpiar comas y espacios sobrantes
            $observacion = preg_replace('/\s*,\s*,\s*/', ', ', $observacion); // ,, -> ,
            $observacion = preg_replace('/^\s*,\s*/', '', $observacion); // coma al inicio
            $observacion = preg_replace('/\s*,\s*$/', '', $observacion); // coma al final
            $observacion = trim($observacion);
            // Actualizar la observación de la persona
            $persona->observacion_persona = empty($observacion) ? null : $observacion;
            $persona->save();
        }

        $nombre = ucwords(strtolower("{$persona->nombre_persona} {$persona->apellido_persona}"));

        $this->logService->logCreation(
            'Carnet',
            $carnet,
            "Se registró el carnet de discapacidad del beneficiario {$nombre}.",
            null,
            [
                'beneficiario'      => $nombre,
                'c.i.'              => $persona->ci_persona,
                'c.i. disc.'        => $carnet->doc,
                'discapacidad'      => $carnet->discapacidad,
                'fecha emision'     => $carnet->fecha_emision     ?? 'Indefinido',
                'fecha vencimiento' => $carnet->fecha_vencimiento  ?? 'Indefinido',
            ]
        );
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
        $carnet = Carnet::findOrFail($id);
        $persona = Persona::find($carnet->id_persona);

        $fieldsToUpdate = $request->all();

        if (!empty($fieldsToUpdate['indefinido'])) {
            $fieldsToUpdate['fecha_emision'] = null;
            $fieldsToUpdate['fecha_vencimiento'] = now()->format('Y-m-d');
        }
        unset($fieldsToUpdate['indefinido']);

        $mapaLabels = [
            'doc'              => 'Nº Doc.',
            'discapacidad'     => 'Discapacidad',
            'fecha_emision'    => 'Fecha Emisión',
            'fecha_vencimiento' => 'Fecha Vencimiento',
        ];

        $camposModificados = [];
        $valoresAnteriores = [];
        $valoresNuevos     = [];

        $formatearFecha = fn($campo, $valor) => in_array($campo, ['fecha_emision', 'fecha_vencimiento']) && is_null($valor)
            ? 'Indefinido'
            : $valor;

        foreach ($fieldsToUpdate as $campo => $nuevoValor) {
            if (!array_key_exists($campo, $mapaLabels)) continue;

            $valorAnterior = $carnet->$campo;
            $label         = $mapaLabels[$campo];

            if ($valorAnterior != $nuevoValor) {
                $camposModificados[$label] = $formatearFecha($campo, $nuevoValor);
                $valoresAnteriores[$label] = $formatearFecha($campo, $valorAnterior);
                $valoresNuevos[$label]     = $formatearFecha($campo, $nuevoValor);
            }
        }

        if (empty($camposModificados)) {
            return;
        }

        $carnet->update($fieldsToUpdate);

        $nombre = ucwords(strtolower("{$persona->nombre_persona} {$persona->apellido_persona}"));

        $this->logService->logUpdate(
            'Carnet',
            $carnet,
            [
                'campos_modificados' => $camposModificados,
                'valores_anteriores' => $valoresAnteriores,
                'valores_nuevos'     => $valoresNuevos,
            ],
            "Se actualizó el carnet de disc. de {$nombre} en el sistema."
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
