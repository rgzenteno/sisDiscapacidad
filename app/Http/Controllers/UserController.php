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
        $user = Auth::user();
        $isSuperUser = $user->hasRole('superUsuario');

        $query = User::with('roles');

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

        // Filtrar usuarios según jerarquía de roles
        if (!$isSuperUser) {
            $userMaxLevel = $user->getMaxHierarchyLevel();

            $query->whereHas('roles', function ($q) use ($userMaxLevel) {
                $q->where('hierarchy_level', '<=', $userMaxLevel);
            })->orWhereDoesntHave('roles');
        }

        $users = $query
            ->select('users.*')
            ->orderByRaw('
                        (SELECT MAX(r.hierarchy_level)
                        FROM roles r
                        INNER JOIN model_has_roles mhr ON r.id = mhr.role_id
                        WHERE mhr.model_id = users.id
                        AND mhr.model_type = ?)
                        DESC
                    ', ['App\\Models\\User'])
            ->orderBy('users.id', 'asc')
            ->get()
            ->map(function ($user) {
                return [
                    ...$user->toArray(),
                    'rol' => $user->roles->first()?->name ?? 'sin rol',
                ];
            });

        // Filtrar roles según jerarquía (igual que en el index de roles)
        $rolesQuery = Role::with('permissions');

        if (!$isSuperUser) {
            $userMaxLevel = $user->getMaxHierarchyLevel();
            $rolesQuery->where('hierarchy_level', '<', $userMaxLevel);
        }

        $roles = $rolesQuery->orderBy('hierarchy_level', 'desc')->get();

        return Inertia::render('User/index', [
            'usuarios' => $users,
            'roles' => $roles,
            'isSuperUser' => $isSuperUser,
            'userHierarchyLevel' => $user->getMaxHierarchyLevel(),
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
            'nombre'    => 'required|string|max:255',
            'apellido'  => 'required|string|max:255',
            'rol'       => 'required|string|max:255',
            'cargo'     => 'required|string|max:255',
            'email'     => 'required|string|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'password.required'  => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min'       => 'La contraseña debe tener al menos 6 caracteres.',
            'email.unique'       => 'Este correo ya está registrado.',
            'email.required'     => 'El correo es obligatorio.',
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
        $rol = ucwords(strtolower("{$request->rol}"));
        $cargo = strtoupper($user->cargo);

        $this->logService->logCreation(
            'Usuario',
            $user,
            "Se registró al usuario {$nombre} en el sistema.",
            null,
            [
                'nombre'  => $nombre,
                'rol'     => $rol,
                'cargo'   => $cargo,
                'usuario' => $user->email,
            ]
        );
    }

    public function resetPassword(User $user)
    {
        $tempPassword = Str::random(12);

        $user->update([
            'password' => Hash::make($tempPassword),
            'password_reset_required' => true,
        ]);

        $userName = ucwords(strtolower("{$user->nombre} {$user->apellido}"));

        $this->logService->logPasswordReset(
            $user,
            "Se restableció la contraseña del usuario {$userName}."
        );

        // ✅ AGREGAR ESTA LÍNEA - Retornar la contraseña temporal al frontend
        return redirect()->back()->with('temporary_password', $tempPassword);
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
        $usuario = User::findOrFail($id);

        $oldData        = $usuario->getOriginal();
        $fieldsToUpdate = $request->only(['nombre', 'apellido', 'cargo', 'habilitado']);

        $mapaLabels = [
            'nombre'     => 'nombre',
            'apellido'   => 'apellido',
            'cargo'      => 'cargo'  ,
            'habilitado' => 'habilitado',
        ];

        $camposModificados = [];
        $valoresAnteriores = [];
        $valoresNuevos     = [];

        foreach ($fieldsToUpdate as $campo => $nuevoValor) {
            if (!array_key_exists($campo, $mapaLabels)) continue;

            $valorAnterior = $oldData[$campo] ?? null;
            $label         = $mapaLabels[$campo];

            if ($valorAnterior != $nuevoValor) {
                $camposModificados[$label] = $nuevoValor;
                $valoresAnteriores[$label] = $valorAnterior;
                $valoresNuevos[$label]     = $nuevoValor;
            }
        }

        // Detectar cambio de rol
        if ($request->has('rol')) {
            $rolAnterior = $usuario->getRoleNames()->first() ?? null;
            $rolNuevo    = $request->rol;

            if ($rolAnterior !== $rolNuevo) {
                $camposModificados['rol'] = $rolNuevo;
                $valoresAnteriores['rol'] = $rolAnterior;
                $valoresNuevos['rol']     = $rolNuevo;
            }

            $usuario->syncRoles([$rolNuevo]);
        }

        if (empty($camposModificados)) {
            return;
        }

        $usuario->update($fieldsToUpdate);

        $nombre = ucwords(strtolower("{$usuario->nombre} {$usuario->apellido}"));

        $this->logService->logUpdate(
            'Usuario',
            $usuario,
            [
                'campos_modificados' => $camposModificados,
                'valores_anteriores' => $valoresAnteriores,
                'valores_nuevos'     => $valoresNuevos,
            ],
            "Se actualizó el registro del usuario {$nombre} en el sistema."
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
