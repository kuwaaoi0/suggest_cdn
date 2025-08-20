<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SetAppLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // .env / config('app.locale') の値をアプリ全体に反映
        app()->setLocale(config('app.locale'));

        // Carbon のロケールも合わせる（相対時間などが日本語に）
        if (class_exists(Carbon::class)) {
            Carbon::setLocale(app()->getLocale());
        }

        return $next($request);
    }
}
