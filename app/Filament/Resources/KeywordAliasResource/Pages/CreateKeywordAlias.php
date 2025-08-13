<?php

namespace App\Filament\Resources\KeywordAliasResource\Pages;

use App\Filament\Resources\KeywordAliasResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateKeywordAlias extends CreateRecord
{
    protected static string $resource = KeywordAliasResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
