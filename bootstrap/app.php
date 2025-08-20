<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

// プロジェクトのミドルウェア
use App\Http\Middleware\BypassFilamentAuth;
use App\Http\Middleware\CheckApiKey;
use App\Http\Middleware\CheckSiteOrigin;
use App\Http\Middleware\ThrottlePerSite;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 逆プロキシ
        $middleware->trustProxies(at: '*');

        // ルートで使うエイリアス
        $middleware->alias([
            'bypass.filament.auth' => BypassFilamentAuth::class,
            'check.api.key'        => CheckApiKey::class,
            'check.site.origin'    => CheckSiteOrigin::class,
            'throttle.site'        => ThrottlePerSite::class,
        ]);

        // Web グループ
        $middleware->group('web', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // API グループ
        // ※ Framework の ThrottleRequests:api は使わず、サイト別レート制限のみ適用
        $middleware->group('api', [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            CheckSiteOrigin::class,
            ThrottlePerSite::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 403 の最小ログ
        $exceptions->report(function (\Throwable $e) {
            if ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 403) {
                $r = request();
                Log::warning('403 thrown', [
                    'path'   => $r->path(),
                    'route'  => optional($r->route())->getActionName(),
                    'userId' => optional($r->user())->id,
                ]);
            }
        });
    })
    ->create();

