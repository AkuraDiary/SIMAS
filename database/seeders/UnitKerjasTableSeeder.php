<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UnitKerjasTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('unit_kerjas')->delete();
        
        \DB::table('unit_kerjas')->insert(array (
            0 => 
            array (
                'id' => 1,
                'parent_id' => NULL,
                'jenis_unit_id' => 1,
                'nama_unit' => 'Rektorat',
                'singkatan' => 'REK',
                'is_active' => 1,
                'deleted_at' => NULL,
                'created_at' => '2026-08-12 08:17:21',
                'updated_at' => '2026-08-12 08:17:21',
            ),
            1 => 
            array (
                'id' => 2,
                'parent_id' => 1,
                'jenis_unit_id' => 2,
                'nama_unit' => 'Logistik',
                'singkatan' => 'Log',
                'is_active' => 1,
                'deleted_at' => NULL,
                'created_at' => '2026-08-13 08:28:15',
                'updated_at' => '2026-08-13 08:28:23',
            ),
            2 => 
            array (
                'id' => 3,
                'parent_id' => 1,
                'jenis_unit_id' => 3,
                'nama_unit' => 'Kemahasiswaan',
                'singkatan' => 'KEMA',
                'is_active' => 1,
                'deleted_at' => NULL,
                'created_at' => '2026-08-13 08:28:54',
                'updated_at' => '2026-08-13 08:28:54',
            ),
            3 => 
            array (
                'id' => 4,
                'parent_id' => 3,
                'jenis_unit_id' => 3,
                'nama_unit' => 'Fakultas Teknik',
                'singkatan' => 'FT',
                'is_active' => 1,
                'deleted_at' => NULL,
                'created_at' => '2026-08-13 08:29:10',
                'updated_at' => '2026-08-13 08:29:10',
            ),
            4 => 
            array (
                'id' => 5,
                'parent_id' => 4,
                'jenis_unit_id' => 4,
                'nama_unit' => 'RPL',
                'singkatan' => 'RPL',
                'is_active' => 1,
                'deleted_at' => NULL,
                'created_at' => '2026-08-18 20:26:41',
                'updated_at' => '2026-08-19 17:05:02',
            ),
            5 => 
            array (
                'id' => 6,
                'parent_id' => 4,
                'jenis_unit_id' => 4,
                'nama_unit' => 'Teknik Elektro',
                'singkatan' => 'ELKTR',
                'is_active' => 1,
                'deleted_at' => NULL,
                'created_at' => '2026-08-24 21:15:58',
                'updated_at' => '2026-08-24 21:15:58',
            ),
        ));
        
        
    }
}