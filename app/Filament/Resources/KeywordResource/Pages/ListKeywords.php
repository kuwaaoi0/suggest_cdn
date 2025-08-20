<?php

namespace App\Filament\Resources\KeywordResource\Pages;

use App\Filament\Resources\KeywordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Imports\KeywordImporter;
use App\Filament\Exports\KeywordExporter;

class ListKeywords extends ListRecords
{
    protected static string $resource = KeywordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\ImportAction::make()
                ->label('CSVインポート')
                ->importer(KeywordImporter::class),
            Actions\ExportAction::make()
                ->label('CSVエクスポート')
                ->exporter(KeywordExporter::class),
        ];
    }
}
