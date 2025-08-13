<?php

namespace App\Support;

use Firebase\JWT\JWT;

class SuggestJwt
{
    public static function issueFor(string $externalUserId, int $ttlSeconds = 600): string
    {
        $payload = [
            'iss' => env('SUGGEST_JWT_ISSUER', 'suggest-cdn'),
            'sub' => $externalUserId,      // ← suggest-backend 側で external_user_id として受ける
            'exp' => time() + $ttlSeconds,
        ];
        return JWT::encode($payload, env('SUGGEST_JWT_SECRET'), 'HS256');
    }
}
