<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Str;

class UserController extends Controller
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
        $query = User::with('roles'); // ✅ Cargar la relación de roles

        $search = $request->input('buscador');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('apellido', 'like', "%{$search}%")
                    ->orWhere('cargo', 'like', "%{$search}%")
                    ->orWhere('estado', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('roles', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $users = $query->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($user) {
                return [
                    // Todos los campos del usuario
                    ...$user->toArray(),
                    'rol' => $user->roles->first()?->name ?? 'sin rol',
                ];
            });

        return Inertia::render('User/index', [
            'usuarios' => $users,
            'roles' => Role::with('permissions')->get(),
            'filters' => [
                'buscador' => $search
            ]
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
        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'rol' => 'required|string|max:255', // Se recibe como string desde el frontend
            'cargo' => 'required|string|max:255',
            'email' => 'required|string|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'cargo' => $request->cargo,
            'estado' => 0,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // ✅ AGREGAR ESTA LÍNEA para asignar el rol
        $user->assignRole($request->rol);

        $nombre = ucwords(strtolower("{$user->nombre} {$user->apellido}"));
        $this->logService->logCreation('Usuario', $user, "Usuario creado: {$nombre}");
    }

    public function resetPassword(User $user)
    {
        $tempPassword = Str::random(12);

        $user->update([
            'password' => Hash::make($tempPassword),
            'password_reset_required' => true,
        ]);

        $admin = Auth::user();
        $adminName = ucwords(strtolower($admin->nombre . ' ' . $admin->apellido));
        $userName = ucwords(strtolower("{$user->nombre} {$user->apellido}"));

        $this->logService->logUpdate(
            'Usuario',
            $user,
            null,
            "Contraseña reseteada por admin: {$adminName} para el usuario: {$userName}"
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
        // Buscar la persona por ID
        $usuario = User::findOrFail($id);

        // Guardar datos antiguos antes de la actualización
        $oldData = $usuario->getOriginal();

        // Crear un array con los campos permitidos
        $camposPermitidos = [
            'nombre',
            'apellido',
            'cargo'
        ];

        // Filtrar solo los campos que están en la solicitud
        $datosActualizacion = $request->only($camposPermitidos);

        // Preparar los cambios para el log
        $changes = [
            'campos_modificados' => [],
            'valores_anteriores' => [],
            'valores_nuevos' => []
        ];

        // Verificar cambios solo en los campos permitidos y filtrados
        foreach ($datosActualizacion as $field => $newValue) {
            if (isset($oldData[$field]) && $oldData[$field] !== $newValue) {
                $changes['campos_modificados'][$field] = $field;
                $changes['valores_anteriores'][$field] = $oldData[$field];
                $changes['valores_nuevos'][$field] = $newValue;
            }
        }

        // Actualizar solo los campos filtrados
        $usuario->update($datosActualizacion);

        // Actualizar el rol
        if ($request->has('rol')) {
            $usuario->syncRoles([$request->rol]);
        }

        // Registrar el log
        $nombre = ucwords(strtolower("{$usuario->nombre} {$usuario->apellido}"));
        $this->logService->logUpdate(
            'usuarios',
            $usuario,
            $changes,
            'Registro Actualizado: ' . $nombre
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
