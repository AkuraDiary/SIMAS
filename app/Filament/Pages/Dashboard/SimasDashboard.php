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

    public static string|BackedEnum|null $navigationIcon = 'gmdi-dashboard-r';

    public function getWidgets(): array
    {
        $user = Auth::user();

        return match ($user->tipe_entitas) {
            'ADMIN' => [
                SuperAdminStats::class,
                // SuperAdminSuratChart::class,
            ],
            'STAF' => [
                StafUnitStats::class,
                // StafUnitInboxStats::class,
            ],
            default => [],
        };
    }
    // protected string $view = 'filament.pages.dashboard-simas-dashboard';
}
