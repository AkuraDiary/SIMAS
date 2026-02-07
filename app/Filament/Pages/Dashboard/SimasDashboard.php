<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;
use App\Filament\Widgets\SuperAdminStats;
use App\Filament\Widgets\StafUnitStats;


class SimasDashboard extends BaseDashboard
{
    protected static ?string $title = 'Beranda SIMAS';

    public function getWidgets(): array
    {
        $user = Auth::user();

        // Return widgets based on the 'peran' column
        if ($user->peran === 'superadmin') {
            return [
                SuperAdminStats::class,
            ];
        }

        if ($user->peran === 'stafunit') {
            return [
                StafUnitStats::class,
            ];
        }

        return [];
    }
    // protected string $view = 'filament.pages.dashboard-simas-dashboard';
}
