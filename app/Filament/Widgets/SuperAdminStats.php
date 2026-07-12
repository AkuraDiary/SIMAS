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
            Stat::make('Unit Aktif', UnitKerja::where('is_active', true)->count()),
            Stat::make('Akun User Aktif', User::where('is_active', true)->where('tipe_entitas', 'STAF')->count()),
        ];
    }
}
