<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Events\Registered;
use App\Listeners\CreateDefaultSiteForUser;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */

     protected $listen = [
        Registered::class => [
            CreateDefaultSiteForUser::class,
        ],
    ];

    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
	FilamentView::registerRenderHook(
		PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
		fn (): string => view('filament/custom/login-register-link')->render()
	);
    }
}
