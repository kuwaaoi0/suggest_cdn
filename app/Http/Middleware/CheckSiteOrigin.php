<?php

namespace App\Http\Middleware;

use App\Models\Site;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSiteOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        $siteKey = (string) $request->query('site_key', '');
        if ($siteKey === '') {
            // site_keyが無いAPIにも使えるよう、何もせず通す（必要なら 403 にしてもOK）
            return $next($request);
        }

        $site = Site::where('site_key', $siteKey)->where('is_active', true)->first();
        if (!$site) {
            abort(403, 'invalid site');
        }

        $origin = $request->headers->get('Origin');
        $allowed = $site->allowed_origins ?? [];

        if (!empty($allowed) && $origin) {
            if (!in_array($origin, $allowed, true)) {
                abort(403, 'origin not allowed');
            }
        }

        /** @var Response $response */
        $response = $next($request);

        // 許可したOriginをレスポンスに反映（CORS: GET/POST想定）
        if ($origin && (empty($allowed) || in_array($origin, $allowed, true))) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Vary', 'Origin');
        }

        return $response;
    }
}
