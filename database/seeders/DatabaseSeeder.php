<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
      

        User::create([
            'username' => 'ayam',
            'password' => Hash::make('admin'),
            'nama_lengkap' => 'Administrator Utama',
            'email' => 'admin@test.com',
            'peran' => 'SuperAdmin',
            'status_user' => 'aktif',
        ]);

        User::create([
            'username' => 'staf',
            'password' => Hash::make('ppm'),
            'nama_lengkap' => 'Staff Unit A',
            'email' => 'staf@test.com',
            'peran' => 'StafUnit',
            'status_user' => 'aktif',
            // 'idUnit' => 1, // Ensure this ID exists in your unit_kerja table!
        ]);

    }
}
