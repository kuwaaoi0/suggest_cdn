<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Site;
use App\Models\User as EloquentUser;

class CreateDefaultSiteForUser
{
    public function handle(Registered $event): void
    {
        // 登録されたユーザー（Eloquentで無い可能性も考慮）
        $u = $event->user;
        $userId = $u->id ?? (method_exists($u, 'getAuthIdentifier') ? $u->getAuthIdentifier() : null);
        if (!$userId) return;

        // 可能なら Eloquent の User に引き直す
        $user = $u instanceof EloquentUser ? $u : EloquentUser::find($userId);

        // すでに所属があれば何もしない（Eloquentがなければピボット直参照）
        if ($user && method_exists($user, 'sites')) {
            if ($user->sites()->exists()) return;
        } else {
            if (DB::table('site_user')->where('user_id', $userId)->exists()) return;
        }

        DB::transaction(function () use ($user, $userId, $u) {
            $makeKey = function () {
                return 'site_' . Str::lower(Str::random(10));
            };
            $siteKey = $makeKey();
            while (Site::where('site_key', $siteKey)->exists()) $siteKey = $makeKey();

            $gen = fn($len)=> rtrim(strtr(base64_encode(random_bytes($len)), '+/', '-_'), '=');

            $name = ($user?->name ?? $u->name ?? 'My') . ' Site';

            $site = Site::create([
                'name'               => $name,
                'site_key'           => $siteKey,
                'api_key'            => $gen(28),
                'jwt_secret'         => $gen(48),
                'jwt_issuer'         => 'suggest-sa',
                'rate_limit_per_min' => 120,
                'allowed_origins'    => [],
                'is_active'          => true,
            ]);

            // 紐付け（Eloquentがあればattach、なければピボット直書き）
            if ($user && method_exists($user, 'sites')) {
                $site->users()->attach($userId, ['role' => 'owner']);
            } else {
                DB::table('site_user')->insert([
                    'site_id' => $site->id,
                    'user_id' => $userId,
                    'role'    => 'owner',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
