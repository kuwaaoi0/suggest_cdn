<?php

namespace App\Services;

use App\Models\Site;

class SuggestToken
{
    // base64url helpers
    protected static function b64e(string $d): string { return rtrim(strtr(base64_encode($d), '+/', '-_'), '='); }
    protected static function b64d(string $d): string { return base64_decode(strtr($d, '-_', '+/')); }

    public static function encode(array $claims, string $secret, string $issuer = 'suggest-sa', int $ttl = 900): string
    {
        $header  = ['alg'=>'HS256','typ'=>'JWT'];
        $now     = time();
        $payload = array_merge($claims, ['iss'=>$issuer,'iat'=>$now,'exp'=>$now + $ttl]);

        $h = self::b64e(json_encode($header));
        $p = self::b64e(json_encode($payload));
        $sig = hash_hmac('sha256', "$h.$p", $secret, true);
        return "$h.$p.".self::b64e($sig);
    }

    public static function decode(?string $jwt, ?string $secret = null, ?string $issuer = null): ?array
    {
        if (!$jwt) return null;
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return null;

        [$h,$p,$s] = $parts;
        $payload = json_decode(self::b64d($p), true);
        if (!is_array($payload)) return null;

        // 署名検証（secret が与えられた場合）
        if ($secret) {
            $expected = self::b64e(hash_hmac('sha256', "$h.$p", $secret, true));
            if (!hash_equals($expected, $s)) return null;
        }

        // iss チェック
        if ($issuer && ($payload['iss'] ?? null) !== $issuer) return null;

        // exp チェック
        if (isset($payload['exp']) && time() >= (int)$payload['exp']) return null;

        return $payload;
    }

    public static function decodeForSite(?string $jwt, Site $site): ?array
    {
        $secret = (string)($site->jwt_secret ?? '');
        $issuer = (string)($site->jwt_issuer ?? 'suggest-sa');
        if ($secret === '') return null;
        return self::decode($jwt, $secret, $issuer);
    }
}
