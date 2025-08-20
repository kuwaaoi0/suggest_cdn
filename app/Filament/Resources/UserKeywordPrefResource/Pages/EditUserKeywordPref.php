<?php

namespace App\Filament\Resources\UserKeywordPrefResource\Pages;

use App\Filament\Resources\UserKeywordPrefResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserKeywordPref extends EditRecord
{
    protected static string $resource = UserKeywordPrefResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
