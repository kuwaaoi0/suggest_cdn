<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PingController extends Controller
{
    public function __invoke(Request $req): JsonResponse
    {
        /** @var \App\Models\Site|null $site */
        $site = $req->attributes->get('site'); // CheckApiKeyでセット済み

        return response()->json([
            'ok' => true,
            'site' => $site ? [
                'id'   => $site->id,
                'name' => $site->name,
                'key'  => $site->site_key,
                'rate_limit_per_min' => $site->rate_limit_per_min,
                'allowed_origins'    => $site->allowed_origins,
            ] : null,
            'time' => now()->toIso8601String(),
            'ip'   => $req->ip(),
            'ua'   => (string) $req->userAgent(),
        ]);
    }
}
