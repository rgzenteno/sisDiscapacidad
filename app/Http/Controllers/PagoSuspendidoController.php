<?php

namespace App\Http\Controllers;

use App\Exceptions\PagoSuspendidoException;
use App\Models\PagoSuspendido;
use App\Services\PagoSuspendidoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PagoSuspendidoController extends Controller
{
    public function __construct(private PagoSuspendidoService $service)
    {
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_persona' => ['required', 'uuid', 'exists:persona,id_persona'],
            'fecha_inicio' => ['required', 'date_format:Y-m'],
            'observaciones' => ['required', 'string', 'max:1000'],
        ]);

        $user = Auth::user();
        $nombreCompleto = "{$user->nombre} {$user->apellido}";

        try {
            $this->service->suspender(
                $data['id_persona'],
                Carbon::createFromFormat('Y-m', $data['fecha_inicio'])->startOfMonth(),
                $data['observaciones'],
                $nombreCompleto
            );
        } catch (PagoSuspendidoException $e) {
            return back()->withErrors([$e->campo => $e->getMessage()]);
        }
    }

    public function destroy(PagoSuspendido $pagoSuspendido)
    {
        $user = Auth::user();
        $nombreCompleto = "{$user->nombre} {$user->apellido}";

        $this->service->reactivar($pagoSuspendido, $nombreCompleto);
    }
}
