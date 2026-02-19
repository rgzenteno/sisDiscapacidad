<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LogService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    protected $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'roles' => Role::with('permissions')->get(),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'rol' => 'required|exists:roles,id',  // ✅ Valida que sea un ID válido
            'cargo' => 'required|string|max:255',
            'email' => 'required|string|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            $user = User::create([
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'cargo' => $request->cargo,
                'estado' => 1,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Asignar roles - CAMBIAR: de $request->roles a $request->rol
            $user->syncRoles($request->rol);

            // Registra la creación
            $nombre = ucwords(strtolower("{$user->nombre} {$user->apellido}"));
            $this->logService->logCreation('Usuario', $user, "Usuario creado: {$nombre}");

            event(new Registered($user));

            // Redirigir a la lista de usuarios con mensaje de éxito
            return redirect()->route('usuario.index')
                ->with('success', 'Usuario registrado exitosamente');

        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error al crear el usuario: ' . $e->getMessage()
            ])->withInput();
        }
    }
}
