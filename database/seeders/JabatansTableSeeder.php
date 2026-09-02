<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class JabatansTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('jabatans')->delete();
        
        \DB::table('jabatans')->insert(array (
            0 => 
            array (
                'id' => 1,
                'unit_kerja_id' => 1,
                'nama_jabatan' => 'Direktur',
                'level_jabatan' => 1,
                'deleted_at' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'unit_kerja_id' => 2,
                'nama_jabatan' => 'Kepala Logistik',
                'level_jabatan' => 1,
                'deleted_at' => NULL,
            ),
            2 => 
            array (
                'id' => 3,
                'unit_kerja_id' => 3,
                'nama_jabatan' => 'Kepala Kema',
                'level_jabatan' => 1,
                'deleted_at' => NULL,
            ),
            3 => 
            array (
                'id' => 4,
                'unit_kerja_id' => 4,
                'nama_jabatan' => 'Dekan FT',
                'level_jabatan' => 1,
                'deleted_at' => NULL,
            ),
            4 => 
            array (
                'id' => 5,
                'unit_kerja_id' => 5,
                'nama_jabatan' => 'Kaprodi',
                'level_jabatan' => 1,
                'deleted_at' => NULL,
            ),
            5 => 
            array (
                'id' => 6,
                'unit_kerja_id' => 6,
                'nama_jabatan' => 'Kaprodi Elektro',
                'level_jabatan' => 1,
                'deleted_at' => NULL,
            ),
        ));
        
        
    }
}