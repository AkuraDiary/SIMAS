<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StafUnitStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Role', 'Staf Unit')
            //
        ];
    }
}
