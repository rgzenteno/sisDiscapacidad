<?php

namespace App\Listeners;

use App\Services\LogService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Cache;

class LogSuccessfulLogin
{
    public function __construct(protected LogService $logService) {}

    public function handle(Login $event): void
    {
        if ($event->remember) return;

        $cacheKey = "login_logged_{$event->user->id}";

        // Si ya se registró en los últimos 3 segundos, ignorar
        if (Cache::has($cacheKey)) return;

        Cache::put($cacheKey, true, now()->addSeconds(3));

        \App\Models\User::where('id', $event->user->id)->update(['estado' => 1]);
        $this->logService->logAuth('login', $event->user);
    }
}
