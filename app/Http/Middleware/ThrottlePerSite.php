<?php

namespace App\Http\Middleware;

use App\Models\Site;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottlePerSite
{
    public function __construct(private RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next): Response
    {
        $site = $request->attributes->get('site'); // CheckApiKeyでset済
        if (!$site instanceof Site) {
            // fallback（CheckApiKey未通過時）
            $siteKey = (string)$request->query('site_key','');
            $site = Site::where('site_key',$siteKey)->first();
        }

        $max = (int)($site?->rate_limit_per_min ?? 120);
        $key = 'rl:'.$site?->id.':'.($request->ip());

        if ($this->limiter->tooManyAttempts($key, $max)) {
            $retry = $this->limiter->availableIn($key);
            return response('Too Many Requests', 429)->header('Retry-After', $retry);
        }

        $this->limiter->hit($key, 60); // 60秒で回復
        return $next($request);
    }
}
