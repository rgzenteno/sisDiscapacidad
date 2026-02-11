<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\Response;

class CheckSuperUserPermissions
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo validar si se están enviando permisos
        if ($request->has('permissions')) {
            $permissionIds = $request->input('permissions', []);

            // Verificar si algún permiso es super_only
            $hasSuperOnlyPermission = Permission::whereIn('id', $permissionIds)
                ->where('super_only', true)
                ->exists();

            // Si intenta asignar permisos super_only y NO es superUsuario
            if ($hasSuperOnlyPermission && !Auth::user()->hasRole('superUsuario')) {
                return redirect()->back()->withErrors([
                    'permissions' => 'No tienes permiso para asignar permisos exclusivos de superUsuario.'
                ]);
            }
        }

        return $next($request);
    }
}
