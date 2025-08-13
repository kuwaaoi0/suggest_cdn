<?php

namespace App\Filament\Resources\UserKeywordAliasResource\Pages;

use App\Filament\Resources\UserKeywordAliasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserKeywordAlias extends EditRecord
{
    protected static string $resource = UserKeywordAliasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
