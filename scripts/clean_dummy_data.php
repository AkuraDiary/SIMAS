<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;

echo "=== SIMAS Safe Database Cleanup ===\n";

// 1. Pre-cleanup verification
$admin = DB::table('users')->where('id', 1)->first();
if (!$admin || $admin->username !== 'admin') {
    fwrite(STDERR, "[FATAL] Admin account id=1 not found or invalid username!\n");
    exit(1);
}

if (!Hash::check('password', $admin->password)) {
    fwrite(STDERR, "[WARNING] Admin password does not match 'password'!\n");
} else {
    echo "[OK] Admin user verified (id: 1, username: 'admin', password matches 'password').\n";
}

// 2. Target tables to clear completely
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

echo "Disabling foreign key checks...\n";
DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

try {
    foreach ($tablesToClear as $table) {
        $count = DB::table($table)->count();
        DB::table($table)->delete();
        DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1;");
        echo " - Cleared `{$table}` (deleted {$count} rows, auto_increment reset)\n";
    }

    // Media Spatie
    $mediaCount = DB::table('media')->where('model_type', 'App\\Models\\Surat')->count();
    DB::table('media')->where('model_type', 'App\\Models\\Surat')->delete();
    echo " - Cleared `media` for Surat ({$mediaCount} rows deleted)\n";

    // Sessions non-admin
    $sessionsCount = DB::table('sessions')->where('user_id', '!=', 1)->orWhereNull('user_id')->delete();
    echo " - Cleared non-admin sessions ({$sessionsCount} rows deleted)\n";

    // Users non-admin
    $deletedUsers = DB::table('users')->where('id', '!=', 1)->delete();
    DB::statement("ALTER TABLE `users` AUTO_INCREMENT = 2;");
    echo " - Cleared non-admin users ({$deletedUsers} users deleted, auto_increment set to 2)\n";
    echo "[OK] Database tables cleared successfully.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "[ERROR] " . $e->getMessage() . "\n");
    exit(1);
} finally {
    DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    echo "Re-enabled foreign key checks.\n";
}

// 3. Remove physical storage dummy media
$mediaDirs = ['68', '69', '70', '71'];
$basePrivate = storage_path('app/private');
foreach ($mediaDirs as $dir) {
    $path = $basePrivate . DIRECTORY_SEPARATOR . $dir;
    if (File::exists($path)) {
        File::deleteDirectory($path);
        echo " - Removed physical media directory: {$path}\n";
    }
}

// 4. Verify post-cleanup state
$totalUsers = DB::table('users')->count();
$adminCheck = DB::table('users')->where('id', 1)->where('username', 'admin')->first();
$suratsCount = DB::table('surats')->count();
$disposisisCount = DB::table('disposisis')->count();
$unitKerjasCount = DB::table('unit_kerjas')->count();
$templatesCount = DB::table('templates')->count();
$notificationsCount = DB::table('notifications')->count();
$mediaCountRemaining = DB::table('media')->where('model_type', 'App\\Models\\Surat')->count();

echo "\n=== Post-Cleanup Verification ===\n";
echo "Total users: {$totalUsers} (Admin preserved: " . ($adminCheck ? 'YES' : 'NO') . ")\n";
echo "Surats count: {$suratsCount}\n";
echo "Disposisis count: {$disposisisCount}\n";
echo "Unit kerjas count: {$unitKerjasCount}\n";
echo "Templates count: {$templatesCount}\n";
echo "Notifications count: {$notificationsCount}\n";
echo "Media Surat count: {$mediaCountRemaining}\n";

if ($totalUsers !== 1 || !$adminCheck || $suratsCount !== 0 || $disposisisCount !== 0 || $unitKerjasCount !== 0 || $templatesCount !== 0) {
    fwrite(STDERR, "[FAIL] Post-cleanup verification failed!\n");
    exit(1);
}

echo "[SUCCESS] Database cleanup completed and verified!\n";
