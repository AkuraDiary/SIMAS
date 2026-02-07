<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UnitKerja;
use App\Models\Surat;
use Illuminate\Support\Facades\DB;

class SuratUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = UnitKerja::all();

        Surat::all()->each(function ($surat) use ($units) {
            $tujuan = $units->random(rand(1, 3));

            foreach ($tujuan as $index => $unit) {
                DB::table('surat_unit')->insert([
                    'surat_id' => $surat->id,
                    'unit_kerja_id' => $unit->id,
                    'jenis_tujuan' => $index === 0 ? 'utama' : 'tembusan',
                    'tanggal_terima' => now()->subDays(rand(0, 3)),
                    
                ]);
            }
        });
    }
}
