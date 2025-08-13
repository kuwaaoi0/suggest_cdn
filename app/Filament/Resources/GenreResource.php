<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GenreResource\Pages;
use App\Filament\Resources\GenreResource\RelationManagers;
use App\Models\Genre;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\Traits\ScopesToSite;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;
//use App\Traits\ScopesToSite;


class GenreResource extends Resource
{
    use ScopesToSite;

    protected static ?string $model = Genre::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return __('nav.genres');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.master');
    }

    public static function getModelLabel(): string
    {
        return __('nav.genres');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.genres');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // A) ユーザーが所属する最初のサイトIDを自動セット
            Forms\Components\Hidden::make('site_id')
            ->default(fn () => (int) session('current_site_id') ?: (Auth::user()?->sites()->value('sites.id'))),

            // 既存のフォーム項目に続く…
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('slug')->helperText('空なら自動生成')->maxLength(255),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 既存のカラム…
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('slug')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                // B) 所属サイトで切替できるセレクトフィルタ
                Tables\Filters\SelectFilter::make('site_id')
                    ->label('Site')
                    ->options(fn () => Auth::user()
                        ? Auth::user()->sites()->pluck('name', 'sites.id')->toArray()
                        : [] )
                    ->preload(),
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
            'index' => Pages\ListGenres::route('/'),
            'create' => Pages\CreateGenre::route('/create'),
            'edit' => Pages\EditGenre::route('/{record}/edit'),
        ];
    }
}
