<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KeywordAliasResource\Pages;
use App\Models\KeywordAlias;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
// ↓ プロジェクトにあるなら（各Resourceで使っている想定のトレイト）
//use App\Traits\ScopesToSite;

class KeywordAliasResource extends Resource
{
    // サイトスコープのトレイトを使っている場合は有効化
    //use ScopesToSite;

    protected static ?string $model = KeywordAlias::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    // （任意）サイドバー表示名の日本語化
    public static function getNavigationLabel(): string
    {
        return __('nav.keyword_aliases'); // 「キーワード別名」
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.master'); // 例：「マスター」
    }

    public static function getModelLabel(): string
    {
        return __('nav.keyword_aliases');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.keyword_aliases');
    }

    /**
     * フォーム定義：ここが“プルダウンでキーワード選択”の肝
     */
    public static function form(Form $form): Form
    {
        return $form->schema([
            // current_site を自動セット（共有として登録したい運用があればここを調整）
            Hidden::make('site_id')
                ->default(fn () => session('current_site_id')),

            // ← これまでは TextInput('keyword_id') 等だった想定。Select に置き換え。
            Select::make('keyword_id')
                ->label('対象キーワード')
                ->relationship(
                    name: 'keyword',           // KeywordAlias::keyword() リレーションが必要
                    titleAttribute: 'name',
                    modifyQueryUsing: function (Builder $query) {
                        $siteId = session('current_site_id');
                        $query->where(function ($q) use ($siteId) {
                            $q->where('site_id', $siteId)
                              ->orWhereNull('site_id'); // 共有(NULL)も候補に含める
                        })->orderBy('name');
                    }
                )
                ->searchable()    // 入力で絞り込み
                ->preload()       // 候補を先読み（数が多ければ外す）
                ->required()
                // 共有レコードを見分けやすくする表示
                ->getOptionLabelFromRecordUsing(
                    fn ($record) => $record->name . ($record->site_id ? '' : '（共有）')
                ),

            TextInput::make('alias')
                ->label('別名')
                ->maxLength(255)
                ->required(),

            // 必要に応じてメモ等の追加フィールド
            // TextInput::make('note')->label('備考')->maxLength(255),
        ]);
    }

    /**
     * 一覧テーブル：IDではなく「キーワード名」を見せる
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('col.id'))
                    ->sortable()
                    ->toggleable(),

                // リレーション経由で表示（keyword.name）
                TextColumn::make('keyword.name')
                    ->label('対象キーワード'),

                TextColumn::make('alias')
                    ->label('別名')
                    ->searchable(),

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
                // 必要に応じてフィルタを追加
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
            'index'  => Pages\ListKeywordAliases::route('/'),
            'create' => Pages\CreateKeywordAlias::route('/create'),
            'edit'   => Pages\EditKeywordAlias::route('/{record}/edit'),
        ];
    }
}
