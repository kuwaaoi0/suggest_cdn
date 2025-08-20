<?php

namespace App\Filament\Exports;

use App\Models\UserKeyword;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Exports\ExportColumn;
use Illuminate\Database\Eloquent\Builder;

class UserKeywordExporter extends Exporter
{
    protected static ?string $model = UserKeyword::class;

    protected static ?string $disk = 'public';
    protected static ?string $fileName = 'user_keywords-{date:Ymd-His}.csv';

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.external_user_id')->label('external_user_id'),
            ExportColumn::make('site.site_key')->label('site_key'),
            //ExportColumn::make('label'),
            ExportColumn::make('label')->label('label')->formatStateUsing(fn ($state) => $state),
            ExportColumn::make('reading'),
            ExportColumn::make('genre.name')->label('genre'),
            //ExportColumn::make('weight'),
            ExportColumn::make('weight')->label('weight')->formatStateUsing(fn ($state) => $state),
            ExportColumn::make('visibility'),
            ExportColumn::make('boost'),
            //ExportColumn::make('is_active'),
            ExportColumn::make('is_active')->label('is_active')->formatStateUsing(fn ($state) => $state ? 1 : 0),
            //ExportColumn::make('aliases.alias')
            //    ->label('aliases')
            //    ->formatStateUsing(fn ($value, $record) =>
            //        $record->aliases?->pluck('alias')->implode(';') ?? ''
            //    ),
            ExportColumn::make('aliases')
                ->label('aliases')
                ->state(fn ($record) => $record->aliases?->pluck('alias')->implode(';') ?? ''),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return "ユーザー辞書のエクスポートが完了しました。レコード: {$export->successful_rows}";
    }

    public static function modifyQueryUsing(Builder $query): Builder
    {
        return $query->with(['aliases:id,user_keyword_id,alias']);
    }
}
