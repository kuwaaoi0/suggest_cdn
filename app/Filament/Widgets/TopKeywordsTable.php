<?php

namespace App\Filament\Widgets;

use App\Models\KeywordClick;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class TopKeywordsTable extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static ?string $heading = '過去7日の人気キーワード';

    /** 集計クエリを返す（Eloquent Builder 必須） */
    protected function getTableQuery(): Builder
    {
        $from = now()->subDays(6)->toDateString(); // 今日含め7日

        // keyword_clicks を基点に、共有/ユーザー両方のラベルを COALESCE で集約
        return KeywordClick::query()
            ->selectRaw('COALESCE(k.label, uk.label) AS label, SUM(keyword_clicks.count) AS clicks')
            ->leftJoin('keywords AS k', 'keyword_clicks.keyword_id', '=', 'k.id')
            ->leftJoin('user_keywords AS uk', 'keyword_clicks.user_keyword_id', '=', 'uk.id')
            ->where('keyword_clicks.day', '>=', $from)
            ->groupByRaw('COALESCE(k.label, uk.label)')
            ->orderByDesc('clicks');
    }

    /** 表示カラム */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('label')
                ->label('キーワード')
                ->searchable()
                ->limit(40),

            Tables\Columns\TextColumn::make('clicks')
                ->label('クリック')
                ->sortable()
                ->alignRight(),
        ];
    }

    /** 件数を20件程度に（お好みで） */
    protected function getDefaultTableRecordsPerPage(): int
    {
        return 20;
    }

    /** ページネーションを消したい場合は true を返す */
    // protected function isTablePaginationEnabled(): bool
    // {
    //     return false;
    // }
}
