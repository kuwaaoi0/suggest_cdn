<?php

namespace App\Filament\Resources\KeywordAliasResource\Pages;

use App\Filament\Resources\KeywordAliasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKeywordAliases extends ListRecords
{
    protected static string $resource = KeywordAliasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
