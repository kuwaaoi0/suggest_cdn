<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteResource\Pages;
use App\Models\Site;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use App\Filament\Resources\Traits\ScopesToSite;
//use App\Traits\ScopesToSite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class SiteResource extends Resource
{
    use ScopesToSite;

    protected static ?string $model = Site::class;

    protected static ?string $navigationIcon  = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'Suggest';
    protected static ?string $navigationLabel = 'Sites';
    protected static ?int    $navigationSort  = 10;

    public static function getNavigationLabel(): string
    {
        return __('nav.sites'); // サイドバー表示名
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.master'); // グループ見出し（任意）
    }

    public static function getModelLabel(): string
    {
        return __('nav.sites');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basic')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('site_key')
                        ->label('Site Key')
                        ->helperText('埋め込みスクリプトの data-site-key で使用')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),

                    Forms\Components\TextInput::make('jwt_secret')
                        ->label('JWT Secret')
                        ->password()
                        ->revealable()
                        ->helperText('Embed で user token を使う場合の署名鍵'),

                    Forms\Components\TextInput::make('jwt_issuer')
                        ->label('JWT Issuer')
                        ->helperText('例: suggest-sa'),

                    Forms\Components\TextInput::make('allowed_origins')
                        ->label('Allowed Origins')
                        ->placeholder('https://example.com')
                        ->helperText('CORS許可するオリジンを列挙（空=制限なし）'),
                ]),

            Forms\Components\Section::make('Embed (参照用)')
                ->schema([
                    Forms\Components\Textarea::make('embed_snippet')
                        ->label('HTML Snippet（参考）')
                        ->rows(6)
                        ->readOnly()
                        ->dehydrated(false)
                        ->helperText('公開時は CDN / API のドメインに置き換えてご利用ください')
                        ->formatStateUsing(function (?string $state, $record) {
                            if (!$record) return '';
                            $siteKey = $record->site_key ?? '';
                            $apiKey  = $record->api_key ?? '';

                            // 実環境のドメインを APP_URL から展開
                            $base    = rtrim(config('app.url'), '/');
                            $cdn     = "{$base}/cdn";
                            $apiBase = $base; // API は同ドメイン配下 /api を想定

                            return <<<HTML
<link rel="stylesheet" href="{$cdn}/suggest.css">
<script defer src="{$cdn}/suggest.js"
  data-site-key="{$siteKey}"
  data-api="{$apiBase}/api/suggest"
  data-click="{$apiBase}/api/click"
  data-api-key="{$apiKey}"
  data-input-class="my-suggest"
  data-open-on-focus="true"></script>
HTML;
                        }),
                ])->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('site_key')
                    ->label('Site Key')
                    ->badge()
                    ->copyable()
                    ->copyMessage('Copied!')
                    ->searchable(),

                Tables\Columns\TextColumn::make('api_key')
                    ->label('API Key')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable()
                    ->copyMessage('Copied!')
                    ->limit(24),

                Tables\Columns\TextColumn::make('allowed_origins')
                    ->label('Allowed Origins')
                    ->limit(30),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('Y-m-d H:i')
                    ->since(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('regenerateApiKey')
                    ->label('Regenerate API Key')
                    ->requiresConfirmation()
                    ->action(function (Site $record) {
                        $record->regenerateApiKey();
                        Notification::make()
                            ->title('API Key regenerated')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index'  => Pages\ListSites::route('/'),
            'create' => Pages\CreateSite::route('/create'),
            'edit'   => Pages\EditSite::route('/{record}/edit'),
        ];
    }

    /**
     * ナビゲーションや一覧クエリのテナント/ユーザー境界
     */
    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();

        // 所有モデルなら user_id で
        if (Schema::hasColumn('sites', 'user_id')) {
            return $q->where('user_id', Auth::id());
        }

        // 多対多の場合は join でユーザーに紐づくものだけ
        return $q->whereHas('users', fn(Builder $b) => $b->whereKey(Auth::id()));
    }
}

