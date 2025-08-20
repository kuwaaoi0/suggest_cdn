<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserKeywordAliasResource\Pages;
use App\Filament\Resources\UserKeywordAliasResource\RelationManagers;
use App\Models\UserKeywordAlias;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserKeywordAliasResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = UserKeywordAlias::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return __('nav.user_keyword_aliases');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.user');
    }

    public static function getModelLabel(): string
    {
        return __('nav.user_keyword_aliases');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.user_keyword_aliases');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_keyword_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('alias')
                    ->required(),
                Forms\Components\TextInput::make('alias_norm')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_keyword_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('alias')
                    ->searchable(),
                Tables\Columns\TextColumn::make('alias_norm')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListUserKeywordAliases::route('/'),
            'create' => Pages\CreateUserKeywordAlias::route('/create'),
            'edit' => Pages\EditUserKeywordAlias::route('/{record}/edit'),
        ];
    }
}
