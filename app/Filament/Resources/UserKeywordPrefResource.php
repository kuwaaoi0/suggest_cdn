<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserKeywordPrefResource\Pages;
use App\Filament\Resources\UserKeywordPrefResource\RelationManagers;
use App\Models\UserKeywordPref;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserKeywordPrefResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = UserKeywordPref::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Forms\Form $form): Forms\Form {
        return $form->schema([
          Forms\Components\Select::make('user_profile_id')->relationship('user','external_user_id')->required()->searchable()->preload(),
          Forms\Components\Select::make('keyword_id')->relationship('keyword','label')->required()->searchable()->preload(),
          Forms\Components\Select::make('visibility')->options([
            'default'=>'default','force_show'=>'force_show','force_hide'=>'force_hide'
          ])->default('default'),
          Forms\Components\TextInput::make('boost')->numeric()->default(0)->helperText('-100〜+100 推奨'),
        ]);
    }

public static function table(Tables\Table $table): Tables\Table {
  return $table->columns([
    Tables\Columns\TextColumn::make('user.external_user_id')->label('User'),
    Tables\Columns\TextColumn::make('keyword.label')->label('Keyword')->searchable(),
    Tables\Columns\TextColumn::make('visibility'),
    Tables\Columns\TextColumn::make('boost'),
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
            'index' => Pages\ListUserKeywordPrefs::route('/'),
            'create' => Pages\CreateUserKeywordPref::route('/create'),
            'edit' => Pages\EditUserKeywordPref::route('/{record}/edit'),
        ];
    }
}
