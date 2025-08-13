<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);
        $middleware->append(\App\Http\Middleware\EnsureCurrentSite::class);
        $middleware->append(\App\Http\Middleware\SetAppLocale::class);
        $middleware->redirectUsersTo('/admin');
        $middleware->alias([
            'check.site.origin' => \App\Http\Middleware\CheckSiteOrigin::class,
            'check.api.key'     => \App\Http\Middleware\CheckApiKey::class,
            'throttle.site' => \App\Http\Middleware\ThrottlePerSite::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // ここは空でOK（独自のハンドラ登録は不要）
    })
    ->create();
