<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LogService;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Routing\Controller;

class RoleController extends Controller
{

    /**
     * Aplicar middleware de protección
     */
    public function __construct(protected LogService $logService)
    {
        $this->middleware('check.super.permissions')->only(['store', 'update']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('buscador');
        $user = Auth::user();
        $isSuperUser = $user->hasRole('superUsuario');

        // Construir la consulta base
        $query = Role::with('permissions');

        // Aplicar filtros de búsqueda si hay un término proporcionado
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Filtrar roles según jerarquía
        if (!$isSuperUser) {
            $userMaxLevel = $user->getMaxHierarchyLevel();
            $query->where('hierarchy_level', '<=', $userMaxLevel);
        }

        $roles = $query->orderBy('hierarchy_level', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Filtrar permisos según el rol del usuario
        if ($isSuperUser) {
            // SuperUsuario ve todos los permisos
            $permissions = Permission::orderBy('name')
                ->select('id', 'name', 'super_only')
                ->get();
        } else {
            // Usuario normal solo ve los permisos que tiene asignados
            $userPermissionIds = $user->getAllPermissions()->pluck('id')->toArray();

            $permissions = Permission::whereIn('id', $userPermissionIds)
                ->where('super_only', false) // Excluir permisos super_only
                ->orderBy('name')
                ->select('id', 'name', 'super_only')
                ->get();
        }

        return Inertia::render("Roles/Index", [
            "roles" => $roles,
            "permissions" => $permissions,
            "isSuperUser" => $isSuperUser,
            "userHierarchyLevel" => $user->getMaxHierarchyLevel(),
            'filters' => [
                'buscador' => $search,
            ],
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
            'name' => 'required|unique:roles,name',
            'hierarchy_level' => 'required|integer|min:1|max:9',
            "permissions" => "required|array"
        ]);

        // ✅ Extraer solo los nombres
        $permissionNames = collect($request->permissions)->map(function ($permission) {
            return is_array($permission) ? $permission['name'] : $permission;
        })->toArray();

        // 🔒 Validación adicional: verificar que los permisos existan y sean válidos
        $validPermissions = Permission::whereIn('name', $permissionNames);

        // Si NO es superUsuario, excluir permisos super_only
        if (!Auth::user()->hasRole('superUsuario')) {
            $validPermissions->where('super_only', false);
        }

        $validPermissionNames = $validPermissions->pluck('name')->toArray();

        $role = Role::create([
            "name" => $request->name,
            "hierarchy_level" => $request->hierarchy_level ?? 0
        ]);
        $role->syncPermissions($validPermissionNames);

        $nameRole = ucwords(strtolower("{$role->name}"));

        $this->logService->logCreation(
            'Rol',
            $role,
            "Se registró el rol {$nameRole} en el sistema.",
            null,
            [
                'rol'             => $nameRole,
                'nivel jerarquía' => $role->hierarchy_level,
                'permisos'        => implode(', ', $validPermissionNames),
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
        $request->validate([
            'name' => 'required|unique:roles,name,' . $id,
            'hierarchy_level' => 'required|integer|min:1|max:9',
            "permissions" => "required|array"
        ]);

        $role = Role::findOrFail($id);

        if ($role->name === 'superUsuario' && !Auth::user()->hasRole('superUsuario')) {
            return back()->withErrors(['error' => 'No tienes permiso para modificar el rol de superUsuario.']);
        }

        $permissionNames = collect($request->permissions)->map(function ($permission) {
            return is_array($permission) ? $permission['name'] : $permission;
        })->toArray();

        $validPermissions = Permission::whereIn('name', $permissionNames);

        if (!Auth::user()->hasRole('superUsuario')) {
            $validPermissions->where('super_only', false);
        }

        $validPermissionNames = $validPermissions->pluck('name')->toArray();

        // Detectar cambios
        $permisosAnteriores = $role->permissions->pluck('name')->sort()->values()->toArray();
        $permisosNuevos     = collect($validPermissionNames)->sort()->values()->toArray();

        $nombreCambio     = $role->name !== $request->name;
        $jerarquiaCambio  = (int) $role->hierarchy_level !== (int) $request->hierarchy_level;
        $permisosCambio   = $permisosAnteriores !== $permisosNuevos;

        if (!$nombreCambio && !$jerarquiaCambio && !$permisosCambio) {
            return;
        }

        $camposModificados = [];
        $valoresAnteriores = [];
        $valoresNuevos     = [];

        if ($nombreCambio) {
            $camposModificados['nombre']     = $request->name;
            $valoresAnteriores['nombre']     = $role->name;
            $valoresNuevos['nombre']         = $request->name;
        }

        if ($jerarquiaCambio) {
            $camposModificados['nivel jerarquía'] = $request->hierarchy_level;
            $valoresAnteriores['nivel jerarquía'] = $role->hierarchy_level;
            $valoresNuevos['nivel jerarquía']     = $request->hierarchy_level;
        }

        if ($permisosCambio) {
            $camposModificados['permisos']     = implode(', ', $permisosNuevos);
            $valoresAnteriores['permisos']     = implode(', ', $permisosAnteriores);
            $valoresNuevos['permisos']         = implode(', ', $permisosNuevos);
        }

        $role->update([
            "name"            => $request->name,
            "hierarchy_level" => $request->hierarchy_level ?? $role->hierarchy_level,
        ]);

        $role->syncPermissions($validPermissionNames);

        $this->logService->logUpdate(
            'Rol',
            $role,
            [
                'campos_modificados' => $camposModificados,
                'valores_anteriores' => $valoresAnteriores,
                'valores_nuevos'     => $valoresNuevos,
            ],
            "Se actualizó el rol {$role->name} en el sistema."
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);

        // 🔒 Proteger el rol superUsuario de ser eliminado
        if ($role->name === 'superUsuario') {
            return back()->withErrors(['error' => 'No se puede eliminar el rol de superUsuario.']);
        }

        // 🔒 Verificar si el rol está siendo usado por algún usuario
        if ($role->users()->count() > 0) {
            return back()->withErrors(['error' => 'No se puede eliminar un rol que está asignado a usuarios.']);
        }

        $this->logService->logDeletion(
            'Rol',
            $role,
            "Se eliminó el rol {$role->name} del sistema.",
            [
                'rol'             => $role->name,
                'nivel jerarquía' => $role->hierarchy_level,
            ]
        );

        $role->delete();
    }
}