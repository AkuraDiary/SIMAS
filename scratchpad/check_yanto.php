<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where("username", "yanto")->first();
echo "User Yanto ID: " . ($user->id ?? "none") . "\n";
if ($user) {
    echo "Active Jabatan ID: " . ($user->getActiveJabatan()?->id ?? "none") . "\n";
    echo "Active Unit ID: " . ($user->getActiveUnitId() ?? "none") . "\n";
    echo "Active Jabatan Name: " . ($user->getActiveJabatan()?->jabatan?->nama_jabatan ?? "none") . "\n";
    echo "Active Unit Name: " . ($user->getActiveJabatan()?->unitKerja?->nama_unit ?? "none") . "\n";

    echo "\nAll Jabatans for Yanto:\n";
    foreach ($user->pegawai->jabatans ?? [] as $j) {
        echo " - Jabatan: {$j->jabatan->nama_jabatan} | Unit: {$j->unitKerja->nama_unit} ({$j->unit_kerja_id}) | Status: {$j->status_jabatan}\n";
    }

    echo "\nNotifications for Yanto via notifications() relation:\n";
    $notifs = $user->notifications()->take(5)->get();
    foreach ($notifs as $n) {
        $vd = isset($n->data['viewData']) ? json_encode($n->data['viewData']) : 'none';
        echo " - ID: {$n->id} | Title: {$n->data['title']} | viewData: {$vd}\n";
    }
}

echo "\n--- SIMULASI SWAP KE SEKRETARIS FT (UNIT 4) ---\n";
$sekretarisUpj = $user->pegawai->jabatans->where('unit_kerja_id', 4)->first();
session(['active_jabatan_id' => $sekretarisUpj->id]);
echo "Active Jabatan Sekarang: " . $user->getActiveJabatan()->jabatan->nama_jabatan . " | Unit: " . $user->getActiveJabatan()->unitKerja->nama_unit . " (" . $user->getActiveUnitId() . ")\n";

$notifsFT = $user->notifications()->take(5)->get();
foreach ($notifsFT as $n) {
    $vd = isset($n->data['viewData']) ? json_encode($n->data['viewData']) : 'none';
    echo " - ID: {$n->id} | Title: {$n->data['title']} | viewData: {$vd}\n";
}
