<?php

namespace App\Filament\Resources\UserKeywordResource\Pages;

use App\Filament\Resources\UserKeywordResource;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Imports\UserKeywordImporter;
use App\Filament\Exports\UserKeywordExporter;
use Filament\Actions;

class ListUserKeywords extends ListRecords
{
    protected static string $resource = UserKeywordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ImportAction::make()
                ->label('CSVインポート')
                ->importer(UserKeywordImporter::class),
            Actions\ExportAction::make()
                ->label('CSVエクスポート')
                ->exporter(UserKeywordExporter::class),
        ];
    }
}
