<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
    public function __construct()
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

        $roles = $query->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        // Filtrar permisos según el rol del usuario
        $permissions = Permission::query()
            ->when(!$isSuperUser, function ($query) {
                $query->where('super_only', false);
            })
            ->orderBy('name')
            ->select('id', 'name', 'super_only')
            ->get();

        return Inertia::render("Roles/Index", [
            "roles" => $roles,
            "permissions" => $permissions,
            "isSuperUser" => $isSuperUser, // 👈 Agregar esto
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

        $role = Role::create(["name" => $request->name]);
        $role->syncPermissions($validPermissionNames);
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
            "permissions" => "required|array"
        ]);

        // ✅ Extraer solo los nombres de los permisos
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

        $role = Role::findOrFail($id);

        // 🔒 Proteger el rol superUsuario de ser modificado por no-superUsuarios
        if ($role->name === 'superUsuario' && !Auth::user()->hasRole('superUsuario')) {
            return back()->withErrors(['error' => 'No tienes permiso para modificar el rol de superUsuario.']);
        }

        $role->update(["name" => $request->name]);
        $role->syncPermissions($validPermissionNames);
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

        $role->delete();
    }
}
