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
        return __('nav.sites'); // 画面タイトル等で使う単数名
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.sites'); // 複数名
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

                    Forms\Components\TextInput::make('rate_limit_per_min')
                        ->label('Rate Limit / min')
                        ->numeric()
                        ->default(120)
                        ->minValue(10),
                ]),

            Forms\Components\Section::make('Security / API')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('api_key')
                        ->label('API Key (公開可)')
                        ->readOnly()
                        ->dehydrated(false)
                        ->helperText('Rotate API Key アクションで再発行できます'),

                    Forms\Components\TextInput::make('jwt_secret')
                        ->label('JWT Secret (非公開)')
                        ->password()
                        ->revealable()
                        ->readOnly()
                        ->dehydrated(false)
                        ->helperText('Rotate JWT Secret アクションで再発行'),

                    Forms\Components\TextInput::make('jwt_issuer')
                        ->label('JWT Issuer')
                        ->default('suggest-sa')
                        ->maxLength(255),

                    Forms\Components\TagsInput::make('allowed_origins')
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
                            return <<<HTML
<link rel="stylesheet" href="https://your-cdn.example/suggest.css">
<input type="search" class="my-suggest" placeholder="検索">
<script defer src="https://your-cdn.example/suggest.js"
  data-site-key="{$siteKey}"
  data-api="https://api.your-domain.example/api/suggest"
  data-click="https://api.your-domain.example/api/click"
  data-api-key="{$apiKey}"
  data-input-class="my-suggest"
  data-open-on-focus="true"></script>
HTML;
                        }),
                ]),
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

                Tables\Columns\TextColumn::make('jwt_issuer')
                    ->label('JWT Issuer')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('rate_limit_per_min')
                    ->label('Rate / min')
                    ->alignRight(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->since()
                    ->label('Updated'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\Action::make('rotateApiKey')
                    ->label('Rotate API Key')
                    ->requiresConfirmation()
                    ->color('warning')
                    ->action(function (Site $record) {
                        $record->api_key = rtrim(strtr(base64_encode(random_bytes(28)), '+/', '-_'), '=');
                        $record->save();
                        Notification::make()->title('API Key rotated')->success()->send();
                    }),

                Tables\Actions\Action::make('rotateJwtSecret')
                    ->label('Rotate JWT Secret')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(function (Site $record) {
                        $record->jwt_secret = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
                        $record->save();
                        Notification::make()->title('JWT Secret rotated')->success()->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSites::route('/'),
            'create' => Pages\CreateSite::route('/create'),
            'edit'   => Pages\EditSite::route('/{record}/edit'),
        ];
    }
}
