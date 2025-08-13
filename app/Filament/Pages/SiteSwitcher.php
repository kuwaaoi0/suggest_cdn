<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class SiteSwitcher extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Account';
    protected static ?string $navigationLabel = 'Switch Site';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.site-switcher';

    public ?int $site_id = null;

    public static function getNavigationLabel(): string
    {
        return __('page.site_switcher'); // 「サイト切替」
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.tools'); // 例：「ツール」
    }

    public function mount(): void
    {
        $this->site_id = (int) session('current_site_id') ?: (Auth::user()?->sites()->value('sites.id') ?? 0);
        $this->form->fill(['site_id' => $this->site_id ?: null]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('site_id')
                ->label('Select a site')
                ->options(fn () => Auth::user()
                    ? Auth::user()->sites()->pluck('name', 'sites.id')->toArray()
                    : [])
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\Actions::make([
                Forms\Components\Actions\Action::make('use')
                    ->label('Use this site')
                    ->submit('save'),
            ])->columnSpanFull(),
        ]);
    }

    public function save(): void
    {
        $id = (int)($this->form->getState()['site_id'] ?? 0);
        if (!$id) return;

        // ログインユーザーが属しているサイトかチェック
        $ok = Auth::user()?->sites()->where('sites.id', $id)->exists();
        if (!$ok) {
            Notification::make()->title('You do not belong to this site.')->danger()->send();
            return;
        }

        session(['current_site_id' => $id]);
        Notification::make()->title('Current site updated.')->success()->send();
    }
}
