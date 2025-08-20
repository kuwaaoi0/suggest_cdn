<?php

namespace App\Filament\Resources;

use App\Models\UserKeyword;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\UserKeywordResource\Pages;

class UserKeywordResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = UserKeyword::class;
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationGroup = 'Dictionary';

    // 明示スラッグ（念のため）
    protected static ?string $slug = 'user-keywords';

    public static function getNavigationLabel(): string
    {
        return __('nav.user_keywords');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.user');
    }

    public static function getModelLabel(): string
    {
        return __('nav.user_keywords');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.user_keywords');
    }

    // ★ 万一ルート未定義でも落とさない保険（ナビ生成時の例外を吸収）
    public static function getNavigationUrl(): string
    {
        try {
            return parent::getNavigationUrl();
        } catch (\Throwable $e) {
            return url('/admin'); // フォールバック
        }
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('label')->required(),
            Forms\Components\TextInput::make('reading'),
            Forms\Components\TextInput::make('weight')->numeric()->default(0),
            Forms\Components\Select::make('visibility')
                ->options(['default' => 'default', 'force_show'=>'force_show', 'force_hide'=>'force_hide'])
                ->default('default'),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')->searchable(),
                Tables\Columns\TextColumn::make('weight')->sortable(),
                Tables\Columns\TextColumn::make('visibility')->badge(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->actions([ Tables\Actions\EditAction::make() ])
            ->bulkActions([ Tables\Actions\DeleteBulkAction::make() ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUserKeywords::route('/'),
            'create' => Pages\CreateUserKeyword::route('/create'),
            'edit'   => Pages\EditUserKeyword::route('/{record}/edit'),
        ];
    }
}
