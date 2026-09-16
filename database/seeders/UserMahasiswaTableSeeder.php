<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserMahasiswaTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('user_mahasiswa')->delete();
        
        \DB::table('user_mahasiswa')->insert(array (
            0 => 
            array (
                'id' => 1,
                'user_id' => 2,
                'nim' => '124',
                'nama_lengkap' => 'mahasiswa',
                'tanggal_lahir' => NULL,
                'tahun_masuk' => NULL,
                'status' => 'AKTIF',
                'prodi_id' => NULL,
                'fakultas_id' => NULL,
                'deleted_at' => NULL,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
        ));
        
        
    }
}