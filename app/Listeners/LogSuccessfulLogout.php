<?php

namespace App\Listeners;

use App\Services\LogService;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Cache;

class LogSuccessfulLogout
{
    public function __construct(protected LogService $logService) {}

    // app/Listeners/LogSuccessfulLogout.php
    public function handle(Logout $event): void
    {
        if (!$event->user) return;

        $cacheKey = "logout_logged_{$event->user->id}";

        if (Cache::has($cacheKey)) return;

        Cache::put($cacheKey, true, now()->addSeconds(3));

        \App\Models\User::where('id', $event->user->id)->update(['estado' => 0]);
        $this->logService->logAuth('logout', $event->user);
    }
}