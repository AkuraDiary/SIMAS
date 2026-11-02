<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;

class DatabaseCleanupSeeder extends Seeder
{
    /**
     * Run the safe database cleanup.
     * Preserves strictly users.id = 1 (admin).
     */
    public function run(): void
    {
        $this->command->info('=== SIMAS Safe Database Cleanup ===');

        // 1. Verify admin user exists
        $admin = DB::table('users')->where('id', 1)->first();
        if (!$admin || $admin->username !== 'admin') {
            $this->command->error("[FATAL] Admin account (id: 1) not found or invalid!");
            throw new \RuntimeException("Admin account (id: 1) missing or invalid!");
        }

        if (!Hash::check('password', $admin->password)) {
            $this->command->warn("[WARNING] Admin password does not match 'password'!");
        } else {
            $this->command->info("[OK] Admin account verified (id: 1, username: 'admin', password matches 'password').");
        }

        // 2. 25-step topological cleanup
        $tablesToClear = [
            'notifications',
            'surat_media',
            'surat_ttds',
            'surat_unit',
            'surat_komentars',
            'nomor_surat_logs',
            'arsip_surats',
            'kategori_arsips',
            'disposisis',
            'surat_riwayats',
            'surats',
            'template_unit_akses',
            'templates',
            'template_kategoris',
            'format_nomor_surats',
            'user_pegawai_jabatans',
            'user_pegawai',
            'user_mahasiswa',
            'jabatans',
            'unit_kerjas',
            'jenis_units',
        ];

        $isMysql = DB::getDriverName() === 'mysql';

        if ($isMysql) {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        try {
            foreach ($tablesToClear as $table) {
                $count = DB::table($table)->count();
                DB::table($table)->delete();
                if ($isMysql) {
                    DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1;");
                }
                $this->command->line(" - Cleared `{$table}` ({$count} rows deleted)");
            }

            // Media Spatie for Surat
            $mediaCount = DB::table('media')->where('model_type', 'App\\Models\\Surat')->count();
            DB::table('media')->where('model_type', 'App\\Models\\Surat')->delete();
            $this->command->line(" - Cleared `media` for Surat ({$mediaCount} rows deleted)");

            // Sessions non-admin
            $sessionsCount = DB::table('sessions')->where('user_id', '!=', 1)->orWhereNull('user_id')->delete();
            $this->command->line(" - Cleared sessions ({$sessionsCount} rows deleted)");

            // Non-admin users
            $deletedUsers = DB::table('users')->where('id', '!=', 1)->delete();
            if ($isMysql) {
                DB::statement("ALTER TABLE `users` AUTO_INCREMENT = 2;");
            }
            $this->command->line(" - Cleared non-admin users ({$deletedUsers} deleted)");
        } finally {
            if ($isMysql) {
                DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
            } elseif (DB::getDriverName() === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            }
            $this->command->line(" - Re-enabled foreign key checks");
        }

        // 3. Purge physical storage dummy media
        $mediaDirs = ['68', '69', '70', '71'];
        $basePrivate = storage_path('app/private');
        foreach ($mediaDirs as $dir) {
            $path = $basePrivate . DIRECTORY_SEPARATOR . $dir;
            if (File::exists($path)) {
                File::deleteDirectory($path);
                $this->command->line(" - Removed physical media directory: {$path}");
            }
        }

        // 4. Verify post-cleanup state
        $totalUsers = DB::table('users')->count();
        $adminCheck = DB::table('users')->where('id', 1)->where('username', 'admin')->first();
        $suratsCount = DB::table('surats')->count();
        $disposisisCount = DB::table('disposisis')->count();
        $unitKerjasCount = DB::table('unit_kerjas')->count();
        $templatesCount = DB::table('templates')->count();

        $this->command->info("\n=== Post-Cleanup Status ===");
        $this->command->info("Users: {$totalUsers} (Admin preserved: " . ($adminCheck ? 'YES' : 'NO') . ")");
        $this->command->info("Surats: {$suratsCount}");
        $this->command->info("Disposisis: {$disposisisCount}");
        $this->command->info("Unit Kerjas: {$unitKerjasCount}");
        $this->command->info("Templates: {$templatesCount}");

        if ($totalUsers !== 1 || !$adminCheck || $suratsCount !== 0 || $disposisisCount !== 0 || $unitKerjasCount !== 0 || $templatesCount !== 0) {
            throw new \RuntimeException("Post-cleanup verification assertion failed!");
        }

        $this->command->info("[SUCCESS] Database cleanup finished with 100% integrity!");
    }
}
