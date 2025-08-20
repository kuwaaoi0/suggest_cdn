<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DocsController;
use Illuminate\Support\Facades\Route;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

Route::get('/', function () {
    return redirect('/admin/login');
});

Route::get('/search', function () {
    return view('search');
});

Route::get('/dashboard', function () {
    return redirect('/admin');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/_who', function () {
    return [
        'auth_check' => auth()->check(),
        'web_check'  => auth('web')->check(),
        'id'         => auth()->id(),
        'guard'      => config('auth.defaults.guard'),
        'session_id' => session()->getId(),
    ];
});

Route::get('/_diag', function (Request $r) {
    // --------- 保護（?key=... でのみ表示）---------
    $key = config('app.diag_key'); // ← 後述 ② 参照
    if (!$key || $r->query('key') !== $key) {
        abort(404);
    }

    // --------- 基本情報 ---------
    $user   = Auth::user();
    $guards = collect(config('auth.guards') ?? [])->keys()->mapWithKeys(fn($g) => [$g => Auth::guard($g)->check()]);
    $sess   = [
        'id'        => session()->getId(),
        'driver'    => config('session.driver'),
        'cookie'    => config('session.cookie'),
        'domain'    => config('session.domain'),
        'secure'    => (bool) config('session.secure'),
        'same_site' => config('session.same_site'),
    ];
    // 書き込みテスト（このリクエストでセット）
    session(['__diag_ping' => now()->toDateTimeString()]);
    $sess['write_test'] = session('__diag_ping');

    // --------- Cookie / CSRF ---------
    $cookies = $r->cookies->all();
    $csrf    = [
        'csrf_token()'     => csrf_token(),
        'session_token()'  => session()->token(),
        'match'            => hash_equals((string) csrf_token(), (string) session()->token()),
    ];

    // --------- Livewire アセット疎通（HTTPで自己診断）---------
    $livewireUrl = url('/livewire/livewire.js');
    try {
        $head = Http::withHeaders(['User-Agent' => 'diag'])->timeout(5)->head($livewireUrl);
        $livewire = ['url' => $livewireUrl, 'status' => $head->status()];
    } catch (\Throwable $e) {
        $livewire = ['url' => $livewireUrl, 'status' => null, 'error' => $e->getMessage()];
    }

    // --------- Filament パネル推測（ダッシュボードURL/ルート名）---------
    $filament = [];
    try {
        // 代表的なパネル名 'admin' を試す（別名なら調整）
        $dashUrl = route('filament.admin.pages.dashboard', [], false); // 例: /admin
        $filament['dashboard_url']  = $dashUrl;
        $filament['dashboard_name'] = 'filament.admin.pages.dashboard';
    } catch (\Throwable $e) {
        $filament['dashboard_url']  = null;
        $filament['dashboard_name'] = null;
        $filament['hint']           = 'パネル名が admin 以外なら、適宜読み替えてください（console等）。';
    }

    // --------- /admin（または推測URL）をHTTPで頭だけ叩く ---------
    $adminPath = $filament['dashboard_url'] ?: '/admin';
    try {
        $probe = Http::withHeaders(['User-Agent' => 'diag'])
            ->timeout(5)->head(url($adminPath));
        $adminProbe = ['path' => $adminPath, 'status' => $probe->status()];
    } catch (\Throwable $e) {
        $adminProbe = ['path' => $adminPath, 'status' => null, 'error' => $e->getMessage()];
    }

    // --------- 代表モデルのポリシーを軽くチェック（存在すれば）---------
    $policy = [];
    foreach ([
        \App\Models\Site::class     => 'Site',
        \App\Models\Keyword::class  => 'Keyword',
        \App\Models\User::class     => 'User',
    ] as $class => $label) {
        if (class_exists($class) && $user) {
            try {
                $policy[$label] = [
                    'viewAny' => Gate::forUser($user)->check('viewAny', $class),
                ];
            } catch (\Throwable $e) {
                $policy[$label] = ['error' => $e->getMessage()];
            }
        }
    }

    // --------- ユーザー⇄サイトの関連状況（存在すれば）---------
    $sites = null;
    if ($user && method_exists($user, 'sites')) {
        try {
            // belongsToMany でも hasMany でもOKな pluck('id')
            $siteIds = method_exists($user, 'siteIds') ? $user->siteIds() : $user->sites()->pluck('sites.id')->all();

            $sites = [
                'count'   => count($siteIds),
                'ids'     => $siteIds,
                'exists'  => !empty($siteIds),
            ];
        } catch (\Throwable $e) {
            $sites = ['error' => $e->getMessage()];
        }
    }

    // --------- ルート情報（/admin系のミドルウェア）---------
    $routeInfo = [];
    try {
        $router = app('router');
        foreach ($router->getRoutes() as $route) {
            $uri = $route->uri();
            if (Str::startsWith($uri, ['admin', 'console'])) {
                $routeInfo[] = [
                    'uri'        => '/'.$uri,
                    'name'       => $route->getName(),
                    'methods'    => $route->methods(),
                    'middleware' => $route->gatherMiddleware(),
                ];
            }
        }
    } catch (\Throwable $e) {
        $routeInfo = ['error' => $e->getMessage()];
    }

    return response()->json([
        'app'      => [
            'env'  => app()->environment(),
            'url'  => config('app.url'),
            'time' => now()->toDateTimeString(),
        ],
        'auth'     => [
            'default_guard' => config('auth.defaults.guard'),
            'guards'        => $guards,
            'user'          => $user ? [
                'id'    => $user->id,
                'email'=> $user->email ?? null,
            ] : null,
        ],
        'session'  => $sess,
        'cookies'  => $cookies,
        'csrf'     => $csrf,
        'livewire' => $livewire,
        'filament' => $filament,
        'admin_probe' => $adminProbe,
        'policy'   => $policy,
        'user_sites' => $sites,
        'routes_admin_like' => $routeInfo,
    ], 200, ['Cache-Control' => 'no-store']);
});

Route::get('/docs/how-to', [DocsController::class, 'howto'])->name('docs.howto.index'); // スラッグ無し → 先頭を表示
Route::get('/docs/how-to/{slug}', [DocsController::class, 'howto'])->name('docs.howto');

Route::get('/login', fn () => redirect('/admin/login'));

require __DIR__.'/auth.php';
