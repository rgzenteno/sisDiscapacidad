<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogSuccessfulLogout;
use Illuminate\Support\Facades\Event;
use App\Services\LogService;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LogService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        // ─── Listeners de autenticación ───────────────────────────────
        Event::listen(Login::class,  LogSuccessfulLogin::class);
        Event::listen(Logout::class, LogSuccessfulLogout::class);
    }
}
