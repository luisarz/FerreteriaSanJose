<?php

namespace App\Providers\Filament;

use App\Filament\Auth\CustomLogin;
use App\Filament\Resources\LogResource;
use App\Filament\Resources\SaleResource;
use App\Models\DteTransmisionWherehouse;
use CharrafiMed\GlobalSearchModal\GlobalSearchModalPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Pages\Auth\EditProfile;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Rmsramos\Activitylog\ActivitylogPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {

        return $panel
            ->brandLogo(fn() => view('logo'))
            ->brandLogoHeight('6rem')
            ->default()
            ->font('Poppins')
            ->sidebarWidth('17rem')
            ->id('admin')
            ->path('admin')
            ->profile(isSimple: false)
            ->authGuard('web')
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->login(CustomLogin::class)
            ->maxContentWidth('full')
            ->spa()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\pages\Dashboard::class,
            ])

            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([

                \App\Filament\Resources\SaleResource\Widgets\SalesStat::class,
                SaleResource\Widgets\ChartWidgetSales::class,

            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \Hasnayeen\Themes\Http\Middleware\SetTheme::class
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                \Hasnayeen\Themes\ThemesPlugin::make(),
                GlobalSearchModalPlugin::make(),
                ActivitylogPlugin::make()->label('Bitacora')
                    ->pluralLabel('Bitacora')->navigationSort(3),

            ])
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE, function () {
                $whereHouse = auth()->user()->employee->branch_id ?? null;
                $DTETransmisionType = DteTransmisionWherehouse::where('wherehouse', $whereHouse)->first();
                $labelTransmisionType = "Previo Normal";
                $labelTransmisionTypeBorderColor = " #52b01e ";
                if ($DTETransmisionType->billing_model != 1) {//Previo Normal)
                    $labelTransmisionType = " Deferido Contingencia ";
                    $labelTransmisionTypeBorderColor = " red ";
                }

                return Blade::render(
                    '<div style="border: solid {{ $borderColor }} 1px; border-radius: 10px; padding: 1px; display: flex; align-items: center; gap: 10px;">
                            <div>Transmisión</div>
                            <div style="border: solid {{ $borderColor }} 1px; background-color: {{$borderColor}}; border-radius: 10px; padding: 5px;" >{{ $text }}</div>
                    </div>',
                    [
                        'text' => $labelTransmisionType,
                        'borderColor' => $labelTransmisionTypeBorderColor, // Asegúrate de que esta variable esté definida.
                    ]
                );


            })
        ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, function () {
        return Blade::render('@env(\'local\')<x-login-link />@endenv');
    })
            ->collapsibleNavigationGroups()
        ->navigationGroups([
            NavigationGroup::make()
                ->label('Almacén')
                ->icon('heroicon-o-building-office')
                ->collapsed(),
            NavigationGroup::make()
                ->label('Inventario')
                ->icon('heroicon-o-circle-stack')
                ->collapsed(),
            NavigationGroup::make()
                ->label('Facturación')
                ->icon('heroicon-o-shopping-cart')
                ->collapsed(),
            NavigationGroup::make()
                ->label('Caja Chica')
                ->icon('heroicon-o-currency-dollar')
                ->collapsed(),
            NavigationGroup::make()
                ->label('Contabilidad')
                ->icon('heroicon-o-building-office')
                ->collapsed(),
            NavigationGroup::make()
                ->label('Recursos Humanos')
                ->icon('heroicon-o-academic-cap')
                ->collapsed(),
            NavigationGroup::make()
                ->label('Configuración')
                ->icon('heroicon-o-cog-6-tooth')
                ->collapsed(),
            NavigationGroup::make()
                ->label('Catálogos Hacienda')
                ->icon('heroicon-o-magnifying-glass-circle')
                ->collapsed(),
            NavigationGroup::make()
                ->label('Seguridad')
                ->icon('heroicon-o-shield-check')
                ->collapsed(),

        ])
        ->navigationItems([
            NavigationItem::make('Manual de usuario')
                ->url(asset('storage/manual.pdf'), shouldOpenInNewTab: true)
                ->icon('heroicon-o-book-open')
        ]);

    }
}