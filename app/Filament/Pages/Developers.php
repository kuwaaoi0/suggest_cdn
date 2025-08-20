<?php

namespace App\Filament\Pages;

use App\Models\Site;
use App\Services\SuggestToken;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;

class Developers extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-code-bracket';
    protected static ?string $navigationGroup = 'Suggest';
    protected static ?string $navigationLabel = 'Developers';
    protected static ?int    $navigationSort  = 99;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.developers';

    public ?int $site_id = null;
    public string $external_user_id = '';
    public int $ttl = 900;

    public ?string $token = null;
    public ?string $embed = null;
    public ?string $sample_php = null;
    public ?string $sample_node = null;
    public ?string $sample_python = null;

    public function mount(): void
    {
        $this->form->fill([
            'site_id' => Site::query()->value('id'),
            'ttl' => 900,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('JWT Generator')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('site_id')
                        ->label('Site')
                        ->options(Site::query()->pluck('name','id'))
                        ->searchable()
                        ->required()
                        ->live(),
                    Forms\Components\TextInput::make('external_user_id')
                        ->label('external_user_id')
                        ->placeholder('user-123')
                        ->required(),
                    Forms\Components\TextInput::make('ttl')
                        ->numeric()
                        ->label('TTL (sec)')
                        ->default(900),
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('generate')
                            ->label('Generate JWT & Snippets')
                            ->action(function () {
                                $site = Site::find($this->site_id);
                                if (!$site || !$site->jwt_secret) {
                                    $this->token = null;
                                    $this->embed = null;
                                    return;
                                }
                                $claims = ['external_user_id' => $this->external_user_id];
                                $this->token = SuggestToken::encode(
                                    $claims,
                                    $site->jwt_secret,
                                    $site->jwt_issuer ?: 'suggest-sa',
                                    max(60, (int)$this->ttl)
                                );
                                $this->buildSnippets($site);
                            }),
                    ])->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Embed HTML')
                ->schema([
                    Forms\Components\Textarea::make('embed')
                        ->rows(7)
                        ->readOnly()
                        ->dehydrated(false),
                ]),

            Forms\Components\Section::make('JWT Samples')
                ->schema([
                    Forms\Components\Textarea::make('sample_php')->label('PHP')->rows(10)->readOnly()->dehydrated(false),
                    Forms\Components\Textarea::make('sample_node')->label('Node.js')->rows(10)->readOnly()->dehydrated(false),
                    Forms\Components\Textarea::make('sample_python')->label('Python')->rows(10)->readOnly()->dehydrated(false),
                ])->columns(3),
        ]);
    }

    protected function buildSnippets(Site $site): void
    {
	$base = rtrim(config('app.url'), '/');   // = https://サジェスト検索.com
        $api = $base;
        $cdn = $base;

        $this->embed = <<<HTML
<link rel="stylesheet" href="{$cdn}/assets/suggest.css?v=1">
<script defer src="{$cdn}/assets/suggest.js?v=1"
  data-site-key="{$site->site_key}"
  data-api="{$api}/api/suggest"
  data-click="{$api}/api/click"
  data-api-key="{$site->api_key}"
  data-user-token="{$this->token}"
  data-input-class="my-suggest"
  data-open-on-focus="true"></script>
HTML;

        $issuer = $site->jwt_issuer ?: 'suggest-sa';
        $secret = $site->jwt_secret;

        $this->sample_php = <<<PHP
<?php
// composer require firebase/php-jwt:^6
use Firebase\\JWT\\JWT;

\$payload = [
  'external_user_id' => '{$this->external_user_id}',
  'iss' => '{$issuer}',
  'iat' => time(),
  'exp' => time() + {$this->ttl},
];
\$jwt = JWT::encode(\$payload, '{$secret}', 'HS256');
echo \$jwt;
PHP;

        $this->sample_node = <<<JS
// npm i jsonwebtoken
const jwt = require('jsonwebtoken');
const token = jwt.sign(
  { external_user_id: '{$this->external_user_id}' },
  '{$secret}',
  { algorithm: 'HS256', issuer: '{$issuer}', expiresIn: {$this->ttl} }
);
console.log(token);
JS;

        $this->sample_python = <<<PY
# pip install pyjwt
import time, jwt
payload = {{
  "external_user_id": "{\$this->external_user_id}",
  "iss": "{\$issuer}",
  "iat": int(time.time()),
  "exp": int(time.time()) + { \$this->ttl }
}}
token = jwt.encode(payload, "{\$secret}", algorithm="HS256")
print(token)
PY;
    }


    public static function canAccess(): bool
    {
        return false;
    }

}
