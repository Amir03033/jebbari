<?php

namespace App\Providers\Filament;

use App\Filament\InitialsAvatarProvider;
use App\Filament\Pages\Dashboard;
use App\Http\Middleware\SecurityHeaders;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            // Authentication: login only. No registration route exists.
            ->login()
            ->passwordReset()
            // An authenticator app is required for every account before the panel can be used.
            ->multiFactorAuthentication([
                AppAuthentication::make()->brandName('Jebbari')->recoverable(),
            ], isRequired: true)
            ->brandName('Jebbari')
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            ->favicon(asset('favicon.svg'))
            ->font('system-ui', provider: LocalFontProvider::class)
            ->colors(['primary' => Color::Zinc])
            ->unsavedChangesAlerts()
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make('Inhoud'),
                NavigationGroup::make('Inbox'),
                NavigationGroup::make('Site'),
                NavigationGroup::make('Systeem'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([Dashboard::class])
            // The dashboard lists its own widgets (App\Filament\Pages\Dashboard); nothing is auto-added.
            ->middleware([
                SecurityHeaders::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class])
            // A clear reminder on every admin page while the public site is offline.
            ->renderHook(PanelsRenderHook::TOPBAR_END, fn () => view('filament.offline-badge'))
            // Dashboard styling: a plain stylesheet on Filament's own colour variables, so light and dark both follow.
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn () => new HtmlString('<link rel="stylesheet" href="'.e(asset('css/admin.css')).'?v=1">'));
    }
}
