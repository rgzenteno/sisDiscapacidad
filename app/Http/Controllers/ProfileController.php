<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\LogService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    protected $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }
    /**
     * Display the user's profile form.
     */
    // app/Http/Controllers/ProfileController.php

    public function edit(Request $request)
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'user' => $request->user(), // <-- Añade esta línea
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        // Capturar cambios antes de guardar
        $dirty  = $request->user()->getDirty();
        $original = $request->user()->getOriginal();

        $mapaLabels = [
            'nombre'   => 'nombre',
            'apellido' => 'apellido',
            'usuario'    => 'email',
            'cargo'    => 'cargo',
        ];

        $camposModificados = [];
        $valoresAnteriores = [];
        $valoresNuevos     = [];

        foreach ($dirty as $campo => $nuevoValor) {
            if (!array_key_exists($campo, $mapaLabels)) continue;
            $label                     = $mapaLabels[$campo];
            $camposModificados[$label] = $nuevoValor;
            $valoresAnteriores[$label] = $original[$campo] ?? null;
            $valoresNuevos[$label]     = $nuevoValor;
        }

        $request->user()->save();

        if (!empty($camposModificados)) {
            $nombre = ucwords(strtolower("{$request->user()->nombre} {$request->user()->apellido}"));
            $this->logService->logUpdate(
                'Usuario',
                $request->user(),
                [
                    'campos_modificados' => $camposModificados,
                    'valores_anteriores' => $valoresAnteriores,
                    'valores_nuevos'     => $valoresNuevos,
                ],
                "Se actualizó el perfil del usuario {$nombre} en el sistema."
            );
        }

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}