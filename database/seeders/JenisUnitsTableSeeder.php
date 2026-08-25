<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class JenisUnitsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('jenis_units')->delete();
        
        \DB::table('jenis_units')->insert(array (
            0 => 
            array (
                'id' => 1,
                'nama_jenis' => 'Rektorat',
                'deskripsi' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'nama_jenis' => 'Logistik',
                'deskripsi' => NULL,
            ),
            2 => 
            array (
                'id' => 3,
                'nama_jenis' => 'Akademis',
                'deskripsi' => NULL,
            ),
            3 => 
            array (
                'id' => 4,
                'nama_jenis' => 'Prodi',
                'deskripsi' => NULL,
            ),
        ));
        
        
    }
}