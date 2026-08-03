<?php

namespace App\Http\Middleware;

use App\Models\Parametro;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $shared = parent::share($request);

        return array_merge($shared, [
            'auth' => [
                'user' => $request->user(),
                'permissions' => $request->user()
                    ? $request->user()->getAllPermissions()->pluck('name')
                    : [],
                // Aparte de los permisos: algunas validaciones (ej. tocar
                // estados anteriores al mes vigente) tienen que basarse en el
                // rol/jerarquía real, no en un permiso que podría estar mal
                // asignado a otro rol.
                'roles' => $request->user()
                    ? $request->user()->getRoleNames()
                    : [],
            ],
            // Compartido globalmente (incluye páginas de invitado como
            // Login/Register) para que el frontend derive de acá todos los
            // tonos de la interfaz — ver resources/js/composables/useColorSistema.js.
            // Fallback al azul actual mientras el superusuario no lo configure
            // en Configuración (Parametro "Color - Sistema").
            'sistema' => [
                'color' => Parametro::valorDe('Color - Sistema') ?? '#285FC6',
            ],
            'flash' => [
                'message' => $request->session()->get('message'),
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'temporary_password' => $request->session()->get('temporary_password'),
                'username' => $request->session()->get('username'),
                'resultadosMes' => $request->session()->get('resultadosMes'),
                'importResults' => $request->session()->get('importResults'),
                'errorFormato' => $request->session()->get('errorFormato'),
                'persona_existente' => $request->session()->get('persona_existente'),
            ],
        ]);
    }
}
