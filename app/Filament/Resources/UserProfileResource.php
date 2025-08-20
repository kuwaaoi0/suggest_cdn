<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserProfileResource\Pages;
use App\Filament\Resources\UserProfileResource\RelationManagers;
use App\Models\UserProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserProfileResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = UserProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return __('nav.user_profiles');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.user'); // 例：「ユーザー管理」
    }

    public static function getModelLabel(): string
    {
        return __('nav.user_profiles');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.user_profiles');
    }

    public static function form(Forms\Form $form): Forms\Form {
        return $form->schema([
            Forms\Components\Select::make('site_id')->relationship('site','name')->required()->preload()->searchable(),
            Forms\Components\TextInput::make('external_user_id')->required(),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table {
        return $table->columns([
          Tables\Columns\TextColumn::make('site.name')->label('Site'),
          Tables\Columns\TextColumn::make('external_user_id')->searchable(),
          Tables\Columns\TextColumn::make('updated_at')->since(),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserProfiles::route('/'),
            'create' => Pages\CreateUserProfile::route('/create'),
            'edit' => Pages\EditUserProfile::route('/{record}/edit'),
        ];
    }
}
