<?php

namespace App\Filament\Resources\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait ScopesToSite
{
    /**
     * Filament Resource のクエリを「current_site + 共有(NULL)」に絞る。
     * ただし、site_id カラムが無いテーブルはスキップ。
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // モデルとテーブル名を取得
        $modelClass = static::$model ?? (method_exists(static::class, 'getModel') ? static::getModel() : null);
        if (!$modelClass) {
            return $query;
        }
        $table = app($modelClass)->getTable();

        // site_id が無ければ何もしない（sites 等）
        if (!Schema::hasColumn($table, 'site_id')) {
            return $query;
        }

        $siteId = session('current_site_id');

        // current_site + 共有(NULL)
        return $query->where(function ($q) use ($table, $siteId) {
            $q->where($table . '.site_id', $siteId)
              ->orWhereNull($table . '.site_id');
        });
    }
}
