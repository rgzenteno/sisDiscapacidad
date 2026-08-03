<?php

namespace App\Http\Middleware;

use App\Services\LogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LogSessionExpiry
{
    public function __construct(protected LogService $logService) {}

    public function handle(Request $request, Closure $next)
    {
        // Si había un usuario en sesión pero Auth ya no lo reconoce → expiró
        $hadUser    = Session::has('auth_user_snapshot');
        $hasUser    = Auth::check();

        if ($hadUser && !$hasUser) {
            $snapshot = Session::get('auth_user_snapshot');

            // ✅ Cambiar estado a 0 (inactivo/offline)
            \App\Models\User::where('id', $snapshot['id'])->update(['estado' => 0]);

            $this->logService->logAuth(
                'session-expired',
                null,
                "La sesión del usuario {$snapshot['nombre']} expiró automáticamente.",
                ['snapshot' => $snapshot]
            );
            Session::forget('auth_user_snapshot');
        }

        // Mientras el usuario esté autenticado, guarda un snapshot ligero
        if ($hasUser) {
            $user = Auth::user();
            Session::put('auth_user_snapshot', [
                'id'     => $user->id,
                'nombre' => $user->nombre ?? $user->name,
                'email'  => $user->email,
            ]);
        }

        return $next($request);
    }
}