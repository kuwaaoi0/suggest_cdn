<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KeywordResource\Pages;
use App\Models\Keyword;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
// サイトスコープ用のトレイトを使っているなら（あなたの実装に合わせて）
//use App\Traits\ScopesToSite;

class KeywordResource extends Resource
{
    //use ScopesToSite;

    protected static ?string $model = Keyword::class;

    // サイドバーのアイコン（点になってしまう対策）
    protected static ?string $navigationIcon = 'heroicon-o-tag';

    // サイドバーとタイトルの日本語化
    public static function getNavigationLabel(): string
    {
        return __('nav.keywords'); // 「キーワード」
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.master'); // 例：「マスター」
    }

    public static function getModelLabel(): string
    {
        return __('nav.keywords');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.keywords');
    }

    /**
     * 作成/編集フォーム
     */
    public static function form(Form $form): Form
    {
        return $form->schema([
            // current_site を自動セット（共有で作りたい運用があればここを調整）
            Hidden::make('site_id')
                ->default(fn () => session('current_site_id')),

            // キーワード名
            TextInput::make('name')
                ->label('キーワード')
                ->maxLength(255)
                ->required()
                ->autofocus(),

            // ジャンル選択：genre_id を Select + relationship で名前選択に
            Select::make('genre_id')
                ->label('ジャンル')
                ->relationship(
                    name: 'genre',           // Keyword::genre() が必要
                    titleAttribute: 'name',
                    modifyQueryUsing: function (Builder $query) {
                        $siteId = session('current_site_id');
                        $query->where(function ($q) use ($siteId) {
                            $q->where('site_id', $siteId)
                              ->orWhereNull('site_id'); // 共有(NULL)も候補に含める。不要なら削除
                        })->orderBy('name');
                    }
                )
                ->searchable()
                ->preload()
                ->required()
                ->getOptionLabelFromRecordUsing(
                    fn ($record) => $record->name . ($record->site_id ? '' : '（共有）')
                ),
        ]);
    }

    /**
     * 一覧テーブル
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('col.id'))
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('キーワード')
                    ->searchable()
                    ->sortable(),

                // ジャンル名で表示（IDではなく名前）
                TextColumn::make('genre.name')
                    ->label('ジャンル')
                    ->searchable(),

                // サイト名（共有は「共有」と表示）。不要なら削除OK
                TextColumn::make('site.name')
                    ->label(__('col.site'))
                    ->formatStateUsing(fn ($state) => $state ?? '共有')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('col.created_at'))
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label(__('col.updated_at'))
                    ->dateTime('Y/m/d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // ジャンルで絞り込み（サイト範囲での候補）
                SelectFilter::make('genre_id')
                    ->label('ジャンル')
                    ->relationship(
                        name: 'genre',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (Builder $query) {
                            $siteId = session('current_site_id');
                            $query->where(function ($q) use ($siteId) {
                                $q->where('site_id', $siteId)
                                  ->orWhereNull('site_id');
                            })->orderBy('name');
                        }
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            // 関連マネージャがあればここに
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListKeywords::route('/'),
            'create' => Pages\CreateKeyword::route('/create'),
            'edit'   => Pages\EditKeyword::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $siteId = session('current_site_id');

        return parent::getEloquentQuery()
            // ※ ここで「where だけ」足し、select は * のままにする
            ->where(function ($q) use ($siteId) {
                $q->where('site_id', $siteId)
                  ->orWhereNull('site_id'); // 共有も一覧に出すなら残す。不要なら削除
            })
            ->select('*'); // 念のため明示
    }
}
