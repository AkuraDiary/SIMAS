<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('users')->delete();
        
        \DB::table('users')->insert(array (
            0 => 
            array (
                'id' => 1,
                'username' => 'admin',
                'email' => 'admin@internal.test',
                'password' => '$2y$12$KUW8vyX0kZNuSuBpyN8v9OMgpwwYGbQ5dYky.4shHAsZmGTJeJKyC',
                'phone' => NULL,
                'tipe_entitas' => 'ADMIN',
                'is_active' => 1,
                'remember_token' => NULL,
                'deleted_at' => NULL,
                'created_at' => '2026-08-12 07:20:45',
                'updated_at' => '2026-08-12 07:20:45',
                'settings' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'username' => 'mahasiswa',
                'email' => 'mahasiswa@internal.test',
                'password' => '$2y$12$zWmT1Lvt9yLEFmriWbKl8eZ1ZTunDQlo8LYTajQVk0E0vBUUPbLue',
                'phone' => NULL,
                'tipe_entitas' => 'MAHASISWA',
                'is_active' => 1,
                'remember_token' => NULL,
                'deleted_at' => NULL,
                'created_at' => '2026-08-12 07:20:45',
                'updated_at' => '2026-08-12 16:52:21',
                'settings' => NULL,
            ),
            2 => 
            array (
                'id' => 3,
                'username' => 'pakrektor',
                'email' => 'pegawai@internal.test',
                'password' => '$2y$12$tAaXtqBf048HLkVtW1.Y6O5zKBnPTf0aevSOtEmhh6UPZ8FrWeOj6',
                'phone' => NULL,
                'tipe_entitas' => 'STAF',
                'is_active' => 1,
                'remember_token' => NULL,
                'deleted_at' => NULL,
                'created_at' => '2026-08-12 07:20:46',
                'updated_at' => '2026-08-24 09:55:53',
                'settings' => NULL,
            ),
            3 => 
            array (
                'id' => 6,
                'username' => 'ahmaddahlan',
                'email' => 'dahlan.a@simas.bebek.id',
                'password' => '$2y$12$pgfiHcMzYcq4Imf6juoGZO.bXMqyKFowPLIEhfzKgLN1w6DPR3YXW',
                'phone' => '081234567890',
                'tipe_entitas' => 'STAF',
                'is_active' => 1,
                'remember_token' => NULL,
                'deleted_at' => NULL,
                'created_at' => '2026-08-13 08:35:28',
                'updated_at' => '2026-08-24 09:56:15',
                'settings' => NULL,
            ),
            4 => 
            array (
                'id' => 7,
                'username' => 'budisantoso',
                'email' => 'budi@simas.id',
                'password' => '$2y$12$4f8S8rCJZrnx6x5eVydD6eJl6Ya14lhY0yZInDmusb.0W4gNWjrzO',
                'phone' => '09778723723',
                'tipe_entitas' => 'STAF',
                'is_active' => 1,
                'remember_token' => NULL,
                'deleted_at' => NULL,
                'created_at' => '2026-08-13 08:35:28',
                'updated_at' => '2026-08-24 09:56:34',
                'settings' => NULL,
            ),
            5 => 
            array (
                'id' => 8,
                'username' => 'yanto',
                'email' => 'yanto@simas.com',
                'password' => '$2y$12$ymkbhVhBzuF5HlJBqspAsOnm7AK.laBwNC8BMb715sl3w4aNcdPb6',
                'phone' => '091237123',
                'tipe_entitas' => 'STAF',
                'is_active' => 1,
                'remember_token' => NULL,
                'deleted_at' => NULL,
                'created_at' => '2026-08-13 08:35:29',
                'updated_at' => '2026-08-24 09:57:27',
                'settings' => NULL,
            ),
            6 => 
            array (
                'id' => 9,
                'username' => 'arasy',
                'email' => 'arasy@jmk.id',
                'password' => '$2y$12$ekL.KVbCYg1QReGpwkVOqu.WZybwz43sbLWaXUX88ifGbTcKP2XR.',
                'phone' => NULL,
                'tipe_entitas' => 'STAF',
                'is_active' => 1,
                'remember_token' => NULL,
                'deleted_at' => NULL,
                'created_at' => '2026-08-19 17:08:25',
                'updated_at' => '2026-08-24 09:56:56',
                'settings' => '{"notifikasi_whatsapp": false}',
            ),
        ));
        
        
    }
}