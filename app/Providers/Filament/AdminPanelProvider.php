<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Sales\Widgets\ChartWidgetSales;
use Filafly\Themes\Brisk\BriskTheme;
use Filament\Pages\Enums\SubNavigationPosition;
use Hasnayeen\Themes\Http\Middleware\SetTheme;
use Hasnayeen\Themes\ThemesPlugin;
use App\Filament\Auth\CustomLogin;
use App\Filament\Resources\LogResource;
use App\Filament\Resources\Sales\SaleResource;
use App\Models\Contingency;
use App\Models\DteTransmisionWherehouse;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use CharrafiMed\GlobalSearchModal\GlobalSearchModalPlugin;
use Devonab\FilamentEasyFooter\EasyFooterPlugin;
use EightyNine\FilamentPageAlerts\FilamentPageAlertsPlugin;
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
use Hydrat\TableLayoutToggle\TableLayoutTogglePlugin;
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
use Resma\FilamentAwinTheme\FilamentAwinTheme;
use Rmsramos\Activitylog\ActivitylogPlugin;
use Tapp\FilamentMailLog\FilamentMailLogPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {

        return $panel
            ->brandLogo(fn() => view('logo'))
            ->brandLogoHeight('6rem')
            ->default()
            ->font('Popins')
            ->colors(Color::Red)
            ->sidebarWidth('18rem')
            ->id('admin')
            ->path('admin')
            ->profile(isSimple: false)
            ->authGuard('web')
            ->sidebarCollapsibleOnDesktop()
//            ->sidebarAccordion()
            ->databaseNotifications()
            ->login(CustomLogin::class)
            ->maxContentWidth('full')
            ->collapsibleNavigationGroups()
            ->spa(hasPrefetching: true)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([

//                \App\Filament\Resources\SaleResource\Widgets\SalesStat::class,
                ChartWidgetSales::class,

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
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->plugins([
//                FilamentAwinTheme::make()->primaryColor(Color::Orange),
                FilamentShieldPlugin::make()
                    ->gridColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 4,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ]),


            ])
            ->renderHook(PanelsRenderHook::TOPBAR_LOGO_AFTER, function () {
                $labelTransmisionType = Session::get('empleado');
                $sucursal = Session::get('sucursal_actual');
                $labelTransmisionTypeBorderColor = " #52b01e ";


                return Blade::render(
                    '<div style=" padding-left: 10px; border: solid {{ $borderColor }} 1px; border-radius: 10px;  display: flex; align-items: center; gap: 10px;">
                            <div>{{$sucursal}}</div>
                            <div style="border: solid {{ $borderColor }} 1px; background-color: {{$borderColor}}; border-radius: 10px; padding: 5px;" >{{ $empleado }}</div>
                    </div>',
                    [
                        'sucursal' => $labelTransmisionType,
                        'empleado' => $sucursal,
                        'borderColor' => $labelTransmisionTypeBorderColor, // Asegúrate de que esta variable esté definida.
                    ]
                );


            })
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE, function () {
                $whereHouse = auth()->user()->employee->branch_id ?? null;
                $DTETransmisionType = Contingency::where('warehouse_id', $whereHouse)->where('is_close', 0)->first();
                $labelTransmisionType = "Previo Normal";
                $labelTransmisionTypeBorderColor = " #52b01e ";
                if ($DTETransmisionType) {//Previo Normal)
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
            ->collapsibleNavigationGroups()
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Almacén')
                    ->icon('heroicon-o-building-storefront')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Inventario')
                    ->icon('heroicon-o-archive-box')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Facturación')
                    ->icon('heroicon-o-document-text')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Caja Chica')
                    ->icon('heroicon-o-banknotes')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Contabilidad')
                    ->icon('heroicon-o-calculator')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Recursos Humanos')
                    ->icon('heroicon-o-user-group')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Configuración')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Catálogos Hacienda')
                    ->icon('heroicon-o-building-library')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Seguridad')
                    ->icon('heroicon-o-shield-check')
                    ->collapsed(),

            ])
            ->navigationItems([
                NavigationItem::make('Manual de usuario')
                    ->visible(fn() => auth()->user()?->hasAnyRole(['admin', 'super_admin']))
                    ->url(asset('storage/manual.pdf'), shouldOpenInNewTab: true)
                    ->icon('heroicon-o-book-open')

            ])
            ->renderHook('topbar.start', function () {
                return 'asd';
//                return '<div class="text-lg font-bold text-gray-900">' . (Session::get('modulo_nombre') ?? 'Módulo Actual') . '</div>';
            });
    }
}