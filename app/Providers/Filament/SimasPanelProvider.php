<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\SimasDashboard;
use App\Filament\Pages\StafUnit\SuratMasuk\DetailSurat;
use App\Filament\Pages\StafUnit\SuratMasuk\SuratMasuk;
use App\Filament\Pages\Admin\ManageOrganisasi;
use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
use Filament\Actions\Action;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SimasPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('simas')
            ->path('internal')
            ->viteTheme('resources/css/filament/simas/theme.css')
            ->favicon(asset('favicon.png'))
            ->authGuard('web')
            ->profile(EditProfile::class)
            ->brandName('SIMAS')
            ->login(Login::class)
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => [
                    50 => '255, 242, 235',
                    100 => '255, 222, 209',
                    200 => '255, 186, 158',
                    300 => '255, 145, 102',
                    400 => '230, 101, 46',
                    500 => '255, 125, 69',
                    600 => '255, 125, 69',
                    700 => '230, 101, 46',
                    800 => '204, 76, 24',
                    900 => '153, 52, 10',
                    950 => '102, 31, 0',
                ],
                'secondary' => [
                    50 => '255, 239, 230',
                    100 => '255, 215, 194',
                    200 => '255, 173, 133',
                    300 => '255, 126, 61',
                    400 => '255, 87, 10',
                    500 => '255, 91, 0',
                    600 => '255, 91, 0',
                    700 => '255, 91, 0',
                    800 => '204, 61, 0',
                    900 => '153, 41, 0',
                    950 => '102, 20, 0',
                ],
            ])
            ->userMenuItems(
                [
                    Action::make('Switch')
                        ->label('Ganti Peran (Unit)')
                        ->url(fn(): string => \App\Filament\Pages\SwitchRole::getUrl())
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->visible(fn(): bool => auth()->check() && auth()->user()->tipe_entitas !== 'ADMIN'),


                ]
            )

            ->databaseNotifications()
            ->databaseNotificationsPolling('7s')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                SimasDashboard::class,
                ManageOrganisasi::class,
                SuratMasuk::class,
                DetailSurat::class
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
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
            ->plugin(
                AuthUIEnhancerPlugin::make()
                    ->showEmptyPanelOnMobile(false)
                    ->formPanelPosition('right')
                    ->formPanelWidth('40%')
                    ->emptyPanelBackgroundImageUrl('https://images.unsplash.com/photo-1603796846097-bee99e4a601f?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'),
            )
            ->authMiddleware([
                Authenticate::class,
            ])
        ;
    }
}
