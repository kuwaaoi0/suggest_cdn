<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    /**
     * ダッシュボード等で落ちないように、まずは一覧可にしておく
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Site $site): bool
    {
        return $this->belongsToUser($user, $site);
    }

    public function create(User $user): bool
    {
        // 必要に応じて条件を追加。まずは許可。
        return true;
    }

    public function update(User $user, Site $site): bool
    {
        return $this->belongsToUser($user, $site);
    }

    public function delete(User $user, Site $site): bool
    {
        return $this->belongsToUser($user, $site);
    }

    public function restore(User $user, Site $site): bool
    {
        return $this->belongsToUser($user, $site);
    }

    public function forceDelete(User $user, Site $site): bool
    {
        return false;
    }

    /**
     * 所有判定（1対多 or 多対多 どちらでも動くように）
     */
    protected function belongsToUser(User $user, Site $site): bool
    {
        // 1対多（sites.user_id）
        if (isset($site->user_id)) {
            return (int) $site->user_id === (int) $user->id;
        }

        // Site::user() リレーションがある場合
        if (method_exists($site, 'user') && $site->user) {
            return (int) $site->user->getKey() === (int) $user->getKey();
        }

        // 多対多（site_user ピボット）の場合
        if (method_exists($site, 'users')) {
            try {
                return $site->users()->whereKey($user->getKey())->exists();
            } catch (\Throwable $e) {
                return false;
            }
        }

        return false;
    }
}

