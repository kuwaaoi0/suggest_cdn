<?php

// app/Models/Concerns/OwnedByUser.php
namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait OwnedByUser
{
    public static function bootOwnedByUser(): void
    {
        static::creating(function (Model $model) {
            if (auth()->check() && empty($model->user_id)) {
                $model->user_id = auth()->id();
            }
        });

        // 一覧や検索など常に自分の分だけに絞る
        static::addGlobalScope('owned', function (Builder $q) {
            if (auth()->check()) {
                $q->where($q->getModel()->getTable().'.user_id', auth()->id());
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}

