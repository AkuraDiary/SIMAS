<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;
use App\Filament\Widgets\SuperAdminStats;
use App\Filament\Widgets\StafUnitStats;


class SimasDashboard extends BaseDashboard
{
    protected static ?string $title = 'Beranda';

    public function getWidgets(): array
    {
        $user = Auth::user();

        return match ($user->peran) {
            'superadmin' => [
                SuperAdminStats::class,
                // SuperAdminSuratChart::class,
            ],
            'stafunit' => [
                StafUnitStats::class,
                // StafUnitInboxStats::class,
            ],
            default => [],
        };
    }
    // protected string $view = 'filament.pages.dashboard-simas-dashboard';
}
