<?php

namespace App\Filament\Exporters;

use App\Models\UserKeyword;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Exports\ExportColumn;

class UserKeywordExporter extends Exporter
{
    protected static ?string $model = UserKeyword::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.external_user_id')->label('external_user_id'),
            ExportColumn::make('site.site_key')->label('site_key'),
            ExportColumn::make('label'),
            ExportColumn::make('reading'),
            ExportColumn::make('genre.name')->label('genre'),
            ExportColumn::make('weight'),
            ExportColumn::make('visibility'),
            ExportColumn::make('boost'),
            ExportColumn::make('is_active'),
            ExportColumn::make('aliases.alias')
                ->label('aliases')
                ->formatStateUsing(fn ($value, $record) =>
                    $record->aliases?->pluck('alias')->implode(';') ?? ''
                ),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return "ユーザー辞書のエクスポートが完了しました。レコード: {$export->successful_rows}";
    }
}
