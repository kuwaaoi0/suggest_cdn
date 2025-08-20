<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\File;
use Spatie\YamlFrontMatter\YamlFrontMatter;

use App\Http\Middleware\BypassFilamentAuth;

use App\Http\Middleware\ForceWebGuardForFilament;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {

        $howtoItems = [];
        $dir = resource_path('docs/howto');
        if (is_dir($dir)) {
            $docs = collect(File::files($dir))
                ->filter(fn($f) => str_ends_with($f->getFilename(), '.md'))
                ->map(function ($f) {
                    $p = YamlFrontMatter::parse(File::get($f->getRealPath()));
                    return [
                        'title' => $p->matter('title') ?? pathinfo($f->getFilename(), PATHINFO_FILENAME),
                        'slug'  => $p->matter('slug'),
                        'order' => (int)($p->matter('order') ?? 999),
                    ];
                })
                ->sortBy('order')
                ->values();
            foreach ($docs as $d) {
                if (!empty($d['slug'])) {
                    $howtoItems[] = NavigationItem::make($d['title'])
                        ->url('/docs/how-to/' . $d['slug'], shouldOpenInNewTab: true)
                        ->group('How To')              // ← グループ名
                        ->sort($d['order'])            // ← 並び順
                        ->icon('heroicon-o-book-open');
                }
            }
        }

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
	    ->registration()
	    ->brandName('サジェスト検索.com')
	    ->authGuard('web')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->pages([ \Filament\Pages\Dashboard::class ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                //Widgets\FilamentInfoWidget::class,
            ])
	    //->widgets([])
            ->middleware([
		//ForceWebGuardForFilament::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
		//BypassFilamentAuth::class,
		Authenticate::class,
            ])
	    ->navigationItems($howtoItems);

    }
}
