<?php

namespace App\Filament\Resources\UserKeywordPrefResource\Pages;

use App\Filament\Resources\UserKeywordPrefResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserKeywordPrefs extends ListRecords
{
    protected static string $resource = UserKeywordPrefResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
