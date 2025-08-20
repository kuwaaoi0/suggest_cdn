<?php

namespace App\Filament\Resources\KeywordAliasResource\Pages;

use App\Filament\Resources\KeywordAliasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKeywordAlias extends EditRecord
{
    protected static string $resource = KeywordAliasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
