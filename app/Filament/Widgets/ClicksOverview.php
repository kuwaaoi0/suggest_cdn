<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ClicksOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        // 表示しないだけ。ダッシュボードを 403 で落とさない。
        return Auth::check();
    }

protected function getCards(): array
{
    $user = \Illuminate\Support\Facades\Auth::user();
    if (!$user) {
        // 未ログイン時は 403 にせず空カードで返す
        return [
            Card::make('今日のクリック', '0'),
            Card::make('直近7日', '0'),
            Card::make('累計', '0'),
        ];
    }

    // keyword_clicks が user_id を持つなら最優先でユーザー絞り込み
    $useUserId = \Illuminate\Support\Facades\Schema::hasColumn('keyword_clicks', 'user_id');

    // 旧設計の site_id フォールバック
    $siteIds = method_exists($user, 'siteIds') ? $user->siteIds() : $user->sites()->pluck('sites.id')->map(fn($v)=>(int)$v)->all();

    $today     = now()->toDateString();
    $yesterday = now()->subDay()->toDateString();
    $last7     = now()->subDays(6)->toDateString(); // 今日含め7日

    $base = \Illuminate\Support\Facades\DB::table('keyword_clicks');

    $filter = function ($q) use ($useUserId, $user, $siteIds) {
        if ($useUserId) {
            $q->where('user_id', $user->id);
        } else {
            // フォールバック
            $q->whereIn('site_id', $siteIds ?: [0]);
        }
    };

    $todayClicks = (int) (clone $base)->when(true, $filter)->where('day', $today)->sum('count');
    $yesterdayClicks = (int) (clone $base)->when(true, $filter)->where('day', $yesterday)->sum('count');
    $delta = $yesterdayClicks > 0 ? (($todayClicks - $yesterdayClicks) / $yesterdayClicks * 100) : 0;

    $weekClicks = (int) (clone $base)->when(true, $filter)->where('day', '>=', $last7)->sum('count');
    $allClicks  = (int) (clone $base)->when(true, $filter)->sum('count');

    return [
        Card::make('今日のクリック', number_format($todayClicks))
            ->description(($delta >= 0 ? '+' : '') . number_format($delta, 1) . '%')
            ->descriptionIcon($delta >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down'),
        Card::make('直近7日', number_format($weekClicks)),
        Card::make('累計', number_format($allClicks)),
    ];
}
}
