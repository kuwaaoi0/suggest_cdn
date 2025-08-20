<?php

namespace App\Filament\Widgets;

use App\Models\KeywordClick;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TopKeywordsTable extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static ?string $heading = '過去7日の人気キーワード';

    /**
     * 過去7日のクリック集計を、キーワードごとにランキング表示します。
     * 集計で主キーが消えるため、_row_key を作成し、record key に用います。
     */
    protected function getTableQuery(): Builder
    {
        $since = now()->subDays(7)->startOfDay();

        // KeywordClick = keyword_clicks テーブル想定
        // 集計のため MIN(id) と CONCAT(...) で _row_key（行キー）を作る
        $q = KeywordClick::query()
            ->join('keywords', 'keywords.id', '=', 'keyword_clicks.keyword_id')
            ->where('keyword_clicks.created_at', '>=', $since)
            // ログインユーザーに紐づくデータだけを表示（必要に応じて調整）
            ->when(Auth::check(), function (Builder $b) {
                $b->where('keywords.user_id', Auth::id());
            })
            ->selectRaw('
                MIN(keyword_clicks.id) AS any_id,
                keyword_clicks.keyword_id AS keyword_id,
                keywords.label AS keyword_name,
                COUNT(*) AS total,
                CONCAT(keyword_clicks.keyword_id, "-", MAX(keyword_clicks.id)) AS _row_key
            ')
            ->groupBy('keyword_clicks.keyword_id', 'keywords.label')
            ->orderByDesc('total');

        return $q;
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('keyword_name')
                ->label('キーワード')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('total')
                ->label('クリック数')
                ->numeric()
                ->sortable(),
        ];
    }

    /**
     * Filament が行キーに使う値。必ず string を返す。
     * 主キーが無い（集計結果）ため、自前で作った _row_key を優先して返す。
     */
    public function getTableRecordKey(Model $record): string
    {
        // 通常の主キーが取れるならそれを返す
        if ($record->getKey() !== null) {
            return (string) $record->getKey();
        }

        // 集計で作成した仮キー
        if ($record->getAttribute('_row_key')) {
            return (string) $record->getAttribute('_row_key');
        }

        // フォールバック（まず any_id、その後 keyword_id など）
        return (string) (
            $record->getAttribute('any_id')
            ?? $record->getAttribute('keyword_id')
            ?? spl_object_id($record) // 最終手段：オブジェクトID
        );
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

