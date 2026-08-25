<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserPegawaiJabatansTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('user_pegawai_jabatans')->delete();
        
        \DB::table('user_pegawai_jabatans')->insert(array (
            0 => 
            array (
                'id' => 1,
                'user_pegawai_id' => 1,
                'unit_kerja_id' => 1,
                'jabatan_id' => 1,
                'status_jabatan' => 'AKTIF',
                'deleted_at' => '2026-08-13 08:35:50',
                'created_at' => '2026-08-12 08:18:55',
                'updated_at' => '2026-08-13 08:35:50',
            ),
            1 => 
            array (
                'id' => 2,
                'user_pegawai_id' => 2,
                'unit_kerja_id' => 4,
                'jabatan_id' => 4,
                'status_jabatan' => 'AKTIF',
                'deleted_at' => NULL,
                'created_at' => '2026-08-13 08:35:28',
                'updated_at' => '2026-08-13 08:35:28',
            ),
            2 => 
            array (
                'id' => 3,
                'user_pegawai_id' => 3,
                'unit_kerja_id' => 3,
                'jabatan_id' => 3,
                'status_jabatan' => 'AKTIF',
                'deleted_at' => NULL,
                'created_at' => '2026-08-13 08:35:28',
                'updated_at' => '2026-08-13 08:35:28',
            ),
            3 => 
            array (
                'id' => 4,
                'user_pegawai_id' => 4,
                'unit_kerja_id' => 2,
                'jabatan_id' => 2,
                'status_jabatan' => 'AKTIF',
                'deleted_at' => NULL,
                'created_at' => '2026-08-13 08:35:29',
                'updated_at' => '2026-08-13 08:35:29',
            ),
            4 => 
            array (
                'id' => 5,
                'user_pegawai_id' => 1,
                'unit_kerja_id' => 1,
                'jabatan_id' => 1,
                'status_jabatan' => 'AKTIF',
                'deleted_at' => NULL,
                'created_at' => '2026-08-13 08:35:50',
                'updated_at' => '2026-08-13 08:35:50',
            ),
            5 => 
            array (
                'id' => 6,
                'user_pegawai_id' => 5,
                'unit_kerja_id' => 5,
                'jabatan_id' => 5,
                'status_jabatan' => 'AKTIF',
                'deleted_at' => NULL,
                'created_at' => '2026-08-19 17:08:25',
                'updated_at' => '2026-08-19 17:08:25',
            ),
            6 => 
            array (
                'id' => 7,
                'user_pegawai_id' => 2,
                'unit_kerja_id' => 6,
                'jabatan_id' => 6,
                'status_jabatan' => 'AKTIF',
                'deleted_at' => NULL,
                'created_at' => '2026-08-24 21:15:58',
                'updated_at' => '2026-08-24 21:15:58',
            ),
        ));
        
        
    }
}