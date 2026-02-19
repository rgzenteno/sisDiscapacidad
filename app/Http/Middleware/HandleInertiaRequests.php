<?php

namespace App\Http\Middleware;

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
