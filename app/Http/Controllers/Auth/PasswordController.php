<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\LogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    protected $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', Rules\Password::defaults(), 'confirmed'],
        ], [
            'current_password.required'  => 'La contraseña actual es obligatoria.',
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.required'          => 'La nueva contraseña es obligatoria.',
            'password.confirmed'         => 'Las contraseñas no coinciden.',
            'password.min'               => 'La contraseña debe tener al menos 8 caracteres.',
            'password.mixed'             => 'La contraseña debe contener mayúsculas y minúsculas.',
            'password.numbers'           => 'La contraseña debe contener al menos un número.',
            'password.symbols'           => 'La contraseña debe contener al menos un símbolo.',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $nombre = ucwords(strtolower("{$request->user()->nombre} {$request->user()->apellido}"));

        $this->logService->logPasswordReset(
            $request->user(),
            "El usuario {$nombre} actualizó su contraseña en el sistema."
        );

        return back()->with('status', 'password-updated');
    }
}