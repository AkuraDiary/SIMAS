<?php

namespace App\Filament\Pages;


use App\Filament\Widgets\StafUnitStats;
use App\Filament\Widgets\SuperAdminStats;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class SimasDashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    public static string|BackedEnum|null $navigationIcon = 'dashboard-r';

    protected static ?string $slug = 'simas-dashboard';
    protected static string $routePath = '/';

    protected string $view = 'filament.pages.dashboard-simas-dashboard';

    public function getViewData(): array
    {
        return [
            'totalPengguna' => \App\Models\User::count(),
            'templateAktif' => \App\Models\Template::where('is_active', true)->count(),
            'unitOrganisasi' => \App\Models\UnitKerja::count(),
        ];
    }
}
