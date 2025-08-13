<?php

namespace App\Filament\Resources\UserKeywordAliasResource\Pages;

use App\Filament\Resources\UserKeywordAliasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserKeywordAliases extends ListRecords
{
    protected static string $resource = UserKeywordAliasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
