<?php

namespace App\Filament\Widgets;

use App\Models\UnitKerja;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SuperAdminStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Selamat Datang', Auth::user()->nama_lengkap,),
            Stat::make('Unit Aktif', UnitKerja::where('status_unit', 'aktif')->count()),
            Stat::make('Akun User Aktif', User::where('status_user', 'aktif')->count()),
        ];
    }
}
