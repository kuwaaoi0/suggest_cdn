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

        // リクエストのオリジンを取得（Origin優先、なければRefererから復元）
        $origin = $request->headers->get('Origin') ?: $this->originFromReferer($request->headers->get('Referer'));

        // サイト設定の allowed_origins を配列化
        $allowed = [];
        if ($siteKey !== '') {
            $site = Site::where('site_key', $siteKey)->first();
            if ($site) {
                $allowed = $this->parseAllowed($site->allowed_origins);
            }
        }

        /** @var Response $response */
        $response = $next($request);

        // 許可した Origin をレスポンスに反映（CORS: 実リクエスト用。プリフライトは HandleCors が対応）
        if ($origin) {
            if (empty($allowed) || in_array('*', $allowed, true) || in_array($this->normalizeOrigin($origin), $allowed, true)) {
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Vary', 'Origin');
            }
        }

        return $response;
    }

    private function originFromReferer(?string $referer): ?string
    {
        if (!$referer) {
            return null;
        }
        $parts = parse_url($referer);
        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        return $this->normalizeOrigin($parts['scheme'] . '://' . $parts['host'] . $port);
    }

    private function normalizeOrigin(string $origin): string
    {
        return rtrim(mb_strtolower(trim($origin)), '/');
    }

    /**
     * DBの allowed_origins を配列に正規化
     * - 空/NULL → []
     * - 文字列（カンマ or 空白/改行区切り）→ 分割して正規化
     * - 配列 → 正規化のみ
     * - '*' を含む場合はワイルドカード許可
     *
     * @return array<string>
     */
    private function parseAllowed(mixed $raw): array
    {
        if ($raw === null) {
            return [];
        }
        if (is_array($raw)) {
            $items = $raw;
        } else {
            $raw = trim((string) $raw);
            if ($raw === '') {
                return [];
            }
            // カンマ or 連続空白/改行で分割
            $items = preg_split('/\s*,\s*|\s+/', $raw) ?: [];
        }

        $items = array_map(fn ($v) => $this->normalizeOrigin((string) $v), $items);
        $items = array_values(array_filter(array_unique($items), fn ($v) => $v !== ''));
        return $items;
    }
}

