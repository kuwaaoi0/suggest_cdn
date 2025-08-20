<?php

namespace App\Filament\Resources\UserKeywordResource\Pages;

use App\Filament\Resources\UserKeywordResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserKeyword extends CreateRecord
{
    protected static string $resource = UserKeywordResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
