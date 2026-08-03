<?php

namespace App\Http\Controllers;

use App\Services\LogService;
use Illuminate\Http\Request;

class DigitalSignatureController extends Controller
{
    protected LogService $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    public function update(Request $request)
    {
        $request->validate([
            'signature' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $user = $request->user();
        $tieniaFirmaAnterior = $user->digital_signature;

        if ($tieniaFirmaAnterior && file_exists(public_path($tieniaFirmaAnterior))) {
            unlink(public_path($tieniaFirmaAnterior));
        }

        $file = $request->file('signature');
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('firmas_digitales'), $filename);

        $path = 'firmas_digitales/' . $filename;

        $user->update([
            'digital_signature' => $path,
        ]);

        $nombre = ucwords(strtolower("{$user->nombre} {$user->apellido}"));

        $this->logService->logUpdate(
            'FirmaDigital',
            $user,
            null,
            $tieniaFirmaAnterior
                ? "El usuario {$nombre} actualizó su firma digital en el sistema."
                : "El usuario {$nombre} agregó su firma digital en el sistema."
        );

        return back();
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($user->digital_signature && file_exists(public_path($user->digital_signature))) {
            unlink(public_path($user->digital_signature));

            $user->update([
                'digital_signature' => null,
            ]);

            $nombre = ucwords(strtolower("{$user->nombre} {$user->apellido}"));

            $this->logService->logDeletion(
                'FirmaDigital',
                $user,
                "El usuario {$nombre} eliminó su firma digital del sistema.",
                []
            );
        }

        return back();
    }
}