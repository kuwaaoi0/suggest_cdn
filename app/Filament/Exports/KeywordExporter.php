<?php

namespace App\Filament\Exporters;

use App\Models\Keyword;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Exports\ExportColumn;

class KeywordExporter extends Exporter
{
    protected static ?string $model = Keyword::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('site.site_key')->label('site_key'),
            ExportColumn::make('label'),
            ExportColumn::make('reading'),
            ExportColumn::make('genre.name')->label('genre'),
            ExportColumn::make('weight'),
            ExportColumn::make('is_active'),
            // aliases は集計して1列に
            ExportColumn::make('aliases.alias')
                ->label('aliases') // "別名1;別名2"
                ->formatStateUsing(fn ($value, $record) =>
                    $record->aliases?->pluck('alias')->implode(';') ?? ''
                ),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return "キーワードのエクスポートが完了しました。レコード: {$export->successful_rows}";
    }
}
