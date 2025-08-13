<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureCurrentSite
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && !session()->has('current_site_id')) {
            $first = Auth::user()->sites()->value('sites.id');
            if ($first) session(['current_site_id' => $first]);
        }
        return $next($request);
    }
}
