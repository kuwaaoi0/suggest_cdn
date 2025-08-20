<?php

namespace App\Filament\Exports;

//use App\Models\Keyword;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Exports\ExportColumn;
use Illuminate\Database\Eloquent\Builder;

class KeywordExporter extends Exporter
{
    protected static ?string $model = Keyword::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('site.site_key')->label('site_key'),
            ExportColumn::make('label'),
            ExportColumn::make('reading'),
            //ExportColumn::make('genre.name')->label('genre'),
	    ExportColumn::make('genre.name')->label('genre')->formatStateUsing(fn ($state) => $state),
            //ExportColumn::make('weight'),
	    ExportColumn::make('weight')->label('weight')->formatStateUsing(fn ($state) => $state),
            //ExportColumn::make('is_active'),
	    ExportColumn::make('is_active')->label('is_active')->formatStateUsing(fn ($state) => $state ? 1 : 0),
            // aliases は集計して1列に
            //ExportColumn::make('aliases.alias')
            //    ->label('aliases') // "別名1;別名2"
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
        return "キーワードのエクスポートが完了しました。レコード: {$export->successful_rows}";
    }

    public static function modifyQueryUsing(Builder $query): Builder
    {
        return $query->with(['aliases:id,keyword_id,alias', 'genre:id,name']);
    }
}
