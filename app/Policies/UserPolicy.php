<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * ダッシュボード等で落ちないように、まずは一覧可にしておく
     * （必要に応じて後で締める）
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * 自分自身のユーザー情報のみ閲覧可（必要に応じて拡張）
     */
    public function view(User $user, User $model): bool
    {
        return (int) $user->id === (int) $model->id;
    }

    /**
     * 一般ユーザーが他ユーザーを作成するケースは通常ないので false。
     * 必要なら権限フラグ等で条件を追加。
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, User $model): bool
    {
        return (int) $user->id === (int) $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return false;
    }

    public function restore(User $user, User $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}

