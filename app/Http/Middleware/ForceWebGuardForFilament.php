<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceWebGuardForFilament
{
    public function handle(Request $request, Closure $next)
    {
        $routeName = $request->route()?->getName() ?? '';

        // Filament 配下だけ Laravel のデフォルトガードを web に固定
        if (str_starts_with($routeName, 'filament.')) {
            auth()->shouldUse('web');
        }

        return $next($request);
    }
}

