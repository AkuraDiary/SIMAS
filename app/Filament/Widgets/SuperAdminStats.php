<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SuperAdminStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Role', 'Super Admin')
            //
        ];
    }
}
