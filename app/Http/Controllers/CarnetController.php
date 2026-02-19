<?php

namespace App\Http\Controllers;

use App\Models\Carnet;
use App\Models\NotificacionTutor;
use App\Models\Persona;
use App\Models\User;
use App\Services\LogService;
use App\Traits\PagosDisponiblesTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
                session(['carnet_origen' => 'Habilitaciones']); // 👈 CAMBIADO
            } elseif (str_contains($referrer, '/persona')) {
                $origen = 'Beneficiarios';
                session(['carnet_origen' => 'Beneficiarios']); // 👈 CAMBIADO
            } else {
                $origen = session('carnet_origen', 'Beneficiarios'); // 👈 CAMBIADO default
            }
        } else {
            $origen = session('carnet_origen', 'Beneficiarios'); // 👈 CAMBIADO default
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
        $data = $request->all();

        //dd($data);
        $id_persona = $data['id_persona'];

        // Buscar la persona
        $persona = Persona::find($id_persona);
        $nombre = ucwords(strtolower("{$persona->nombre_persona} {$persona->apellido_persona}"));

        // Calcular fecha de vencimiento (4 años después de fecha_emision)
        if (isset($data['fecha_emision'])) {
            $fechaEmision = \Carbon\Carbon::parse($data['fecha_emision']);
            $data['fecha_vencimiento'] = $fechaEmision->addYears(4)->format('Y-m-d');
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

        $this->logService->logCreation('Carnet', $carnet, "Carnet agregado, beneficiario: {$nombre}");
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

        // Buscar la persona usando el id_persona del carnet
        $persona = Persona::find($carnet->id_persona);

        // Guardar datos antiguos antes de la actualización
        $oldData = $carnet->getOriginal();

        // Guardamos los datos que vienen en el request
        $fieldsToUpdate = $request->all();

        // Calcular fecha de vencimiento (4 años después de fecha_emision)
        if (isset($fieldsToUpdate['fecha_emision'])) {
            $fechaEmision = \Carbon\Carbon::parse($fieldsToUpdate['fecha_emision']);
            $fieldsToUpdate['fecha_vencimiento'] = $fechaEmision->addYears(4)->format('Y-m-d');
        }

        // Preparar los cambios para el log
        $changes = [
            'campos_modificados' => [],
            'valores_anteriores' => [],
            'valores_nuevos' => []
        ];

        // Registrar los cambios
        foreach ($fieldsToUpdate as $field => $newValue) {
            if (isset($oldData[$field]) && $oldData[$field] !== $newValue) {
                $changes['campos_modificados'][$field] = $field;
                $changes['valores_anteriores'][$field] = $oldData[$field];
                $changes['valores_nuevos'][$field] = $newValue;
            }
        }

        // Actualizamos los datos del carnet
        $carnet->update($fieldsToUpdate);

        $nombre = ucwords(strtolower("{$persona->nombre_persona} {$persona->apellido_persona}"));
        $this->logService->logUpdate('Carnet', $carnet, $changes, "Carnet Actualizado: {$nombre}");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
