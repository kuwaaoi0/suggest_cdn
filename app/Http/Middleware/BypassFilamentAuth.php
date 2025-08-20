<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Filament のパネル認可を一時的にバイパスするためのデバッグ用ミドルウェア。
 * 認証済み/未認証を問わず next() へ通す。絶対に abort しない。
 */
class BypassFilamentAuth
{
    public function handle(Request $request, Closure $next)
    {
        // 最低限のログだけ（storage/logs/laravel.log）
        logger()->info('[BypassFilamentAuth] pass', [
            'path' => $request->path(),
            'user_id' => optional(Auth::user())->id,
            'guard' => Auth::getDefaultDriver(),
        ]);

        return $next($request);
    }
}

