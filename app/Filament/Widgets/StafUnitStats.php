<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use App\Models\Surat;

class StafUnitStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $unitId = Auth::user()->unit_kerja_id;

        return [
            Stat::make('Selamat Datang', Auth::user()->nama_lengkap,),
            Stat::make('Unit Kerja', Auth::user()->unitKerja->nama_unit,),
            Stat::make(
                'Total Surat',
                Surat::untukUnit($unitId)->count()
            )
                ->description('Seluruh surat yang ditujukan ke unit ini'),

            Stat::make(
                'Masuk Langsung',
                Surat::masukLangsung($unitId)->count()
            )
                ->description('Surat masuk tanpa disposisi'),

            Stat::make(
                'Disposisi',
                Surat::disposisi($unitId)->count()
            )
                ->description('Surat hasil disposisi'),
        
        ];
    }
}
