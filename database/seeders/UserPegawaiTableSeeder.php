<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserPegawaiTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('user_pegawai')->delete();
        
        \DB::table('user_pegawai')->insert(array (
            0 => 
            array (
                'id' => 1,
                'user_id' => 3,
                'nip' => '123',
                'nama_lengkap' => 'Pak Rektor',
                'deleted_at' => NULL,
                'created_at' => '2026-08-12 08:18:55',
                'updated_at' => '2026-08-13 08:35:50',
            ),
            1 => 
            array (
                'id' => 2,
                'user_id' => 6,
                'nip' => '12345678',
                'nama_lengkap' => 'Ahmad Dahlan',
                'deleted_at' => NULL,
                'created_at' => '2026-08-13 08:35:28',
                'updated_at' => '2026-08-13 08:35:28',
            ),
            2 => 
            array (
                'id' => 3,
                'user_id' => 7,
                'nip' => '123123',
                'nama_lengkap' => 'Budi Santoso',
                'deleted_at' => NULL,
                'created_at' => '2026-08-13 08:35:28',
                'updated_at' => '2026-08-13 08:35:28',
            ),
            3 => 
            array (
                'id' => 4,
                'user_id' => 8,
                'nip' => '‘555',
                'nama_lengkap' => 'Yanto Logistik',
                'deleted_at' => NULL,
                'created_at' => '2026-08-13 08:35:29',
                'updated_at' => '2026-08-13 08:35:29',
            ),
            4 => 
            array (
                'id' => 5,
                'user_id' => 9,
                'nip' => '321321',
                'nama_lengkap' => 'Arasy',
                'deleted_at' => NULL,
                'created_at' => '2026-08-19 17:08:25',
                'updated_at' => '2026-08-19 17:08:25',
            ),
        ));
        
        
    }
}