<?php

namespace App\Policies;

use App\Models\Keyword;
use App\Models\User;

class KeywordPolicy
{
    /**
     * ダッシュボード等で落ちないように、まずは一覧可にしておく
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Keyword $keyword): bool
    {
        return $this->ownsKeyword($user, $keyword);
    }

    public function create(User $user): bool
    {
        // 必要に応じて条件を追加。まずは許可。
        return true;
    }

    public function update(User $user, Keyword $keyword): bool
    {
        return $this->ownsKeyword($user, $keyword);
    }

    public function delete(User $user, Keyword $keyword): bool
    {
        return $this->ownsKeyword($user, $keyword);
    }

    public function restore(User $user, Keyword $keyword): bool
    {
        return $this->ownsKeyword($user, $keyword);
    }

    public function forceDelete(User $user, Keyword $keyword): bool
    {
        return false;
    }

    /**
     * ユーザー所有の Keyword か判定
     * - 完全ユーザー単位: keywords.user_id を優先
     * - 旧: site 経由の管理なら site_id ∈ ユーザーサイト
     */
    protected function ownsKeyword(User $user, Keyword $keyword): bool
    {
        // 直接 user_id を持っている場合
        if (isset($keyword->user_id)) {
            return (int) $keyword->user_id === (int) $user->id;
        }

        // Keyword::user() がある場合
        if (method_exists($keyword, 'user') && $keyword->user) {
            return (int) $keyword->user->getKey() === (int) $user->getKey();
        }

        // 旧設計: site_id で紐づく場合（ユーザーのサイト集合に含まれるか）
        if (isset($keyword->site_id) && method_exists($user, 'sites')) {
            try {
                $siteIds = method_exists($user, 'siteIds') ? $user->siteIds() : $user->sites()->pluck('sites.id')->all();
		return in_array((int) $keyword->site_id, array_map('intval', $siteIds), true);
            } catch (\Throwable $e) {
                return false;
            }
        }

        return false;
    }
}

