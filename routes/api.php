<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuggestController;
use App\Http\Controllers\ClickController;
use App\Http\Controllers\PingController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/suggest', function (Request $req) {
    $q = trim((string) $req->query('query', ''));
    $siteKey = (string) $req->query('site_key', '');
    abort_if($siteKey === '', 403, 'site_key required');

    $all = ['iPhone 15 Pro','iPad Air','iMac','AirPods Pro','Apple Watch'];

    // 前方一致（大文字小文字を無視）
    $items = array_values(array_filter($all, function ($w) use ($q) {
        if ($q === '') return false;
        return stripos($w, $q) === 0;
    }));

    return response()->json([
        'q'     => $q,
        'items' => array_map(function ($w) {
            return ['id' => crc32($w), 'label' => $w, 'genre' => null];
        }, $items),
        'meta'  => ['latency_ms' => 1],
    ]);
});

Route::middleware(['check.api.key','check.site.origin','throttle.site'])
->get('/suggest', SuggestController::class);

Route::middleware(['check.api.key','check.site.origin','throttle.site'])
    ->post('/click', ClickController::class);

Route::middleware(['check.api.key','check.site.origin','throttle.site'])
->get('/ping', PingController::class);
