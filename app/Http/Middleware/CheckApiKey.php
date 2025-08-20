<?php

namespace App\Http\Middleware;

use App\Models\Site;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $siteKey = (string) (
            $request->query('site_key')
            ?? $request->input('site_key')
            ?? $request->header('X-Site-Key')
            ?? ''
        );

        if ($siteKey === '') {
            $raw = $request->getContent();
            if (is_string($raw) && $raw !== '') {
                try {
                    $json = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($json) && isset($json['site_key'])) {
                        $siteKey = (string) $json['site_key'];
                    }
                } catch (\Throwable $e) {
                    // JSON でなければ無視
                }
            }
        }

        abort_if($siteKey === '', 400, 'site_key required');

        $site = Site::where('site_key', $siteKey)->where('is_active', true)->first();
        if (!$site) abort(403, 'invalid site');

        // ヘッダ優先・なければクエリに fallback
        $supplied = (string) $request->headers->get('X-Suggest-Key', $request->query('api_key', ''));

        // 開発環境で未設定を許す場合はここを緩めてもOK
        if (app()->environment('local') && $supplied === '') {
            return $next($request);
        }

        if (empty($site->api_key) || !hash_equals($site->api_key, $supplied)) {
            abort(401, 'invalid api key');
        }

        // 後段で使えるように共有
        $request->attributes->set('site', $site);

        return $next($request);
    }
}
