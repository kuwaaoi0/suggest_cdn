<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Facades\DB;

class ClicksOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getCards(): array
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $last7 = now()->subDays(6)->toDateString(); // 今日含め7日

        $todayClicks = (int) DB::table('keyword_clicks')->where('day', $today)->sum('count');
        $yesterdayClicks = (int) DB::table('keyword_clicks')->where('day', $yesterday)->sum('count');
        $delta = $yesterdayClicks > 0 ? (($todayClicks - $yesterdayClicks) / $yesterdayClicks * 100) : 0;

        $weekClicks = (int) DB::table('keyword_clicks')->where('day', '>=', $last7)->sum('count');
        $allClicks = (int) DB::table('keyword_clicks')->sum('count');

        return [
            Card::make('今日のクリック', number_format($todayClicks))
                ->description(($delta >= 0 ? '+' : '') . number_format($delta, 1) . '%')
                ->descriptionIcon($delta >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down'),
            Card::make('直近7日', number_format($weekClicks)),
            Card::make('累計', number_format($allClicks)),
        ];
    }
}
