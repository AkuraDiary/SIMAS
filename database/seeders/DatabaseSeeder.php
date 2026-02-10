<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\UnitKerja;
use App\Models\Surat;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
       // Unit Kerja
    //    $units = UnitKerja::factory()->count(5)->create();

       // SuperAdmin
       User::factory()->superAdmin()->create();

    //    // Staf Unit
    //    User::factory()
    //        ->count(10)
    //        ->create();

    //    // Surat
    //    Surat::factory()
    //        ->count(10)
    //        ->create();

       // Pivot SuratUnit
    //    $this->call(SuratUnitSeeder::class);

    }
}
