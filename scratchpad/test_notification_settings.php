<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Filament\Pages\Auth\EditProfile;
use Illuminate\Support\Facades\DB;

echo "=== MEMULAI TEST PENGATURAN PREFERENSI NOTIFIKASI PENGGUNA ===\n\n";

DB::beginTransaction();

try {
    // ========================================================
    // 1. TEST FORMAT NOMOR TELEPON WHATSAPP
    // ========================================================
    echo "TEST 1: Standardisasi Format Nomor WhatsApp (getFormattedPhoneForWhatsApp)\n";
    
    $userStaf = User::create([
        'username' => 'test_notif_staf',
        'email' => 'staf_notif@univ.ac.id',
        'password' => bcrypt('password'),
        'tipe_entitas' => 'STAF',
        'is_active' => true,
        'phone' => '0812-3456-7890',
    ]);

    $formatted1 = $userStaf->getFormattedPhoneForWhatsApp();
    echo "  [1a] Input '0812-3456-7890' -> Result: {$formatted1}\n";
    assert($formatted1 === '6281234567890', "Test 1a Gagal: Format 08 harus jadi 628");

    $userStaf->phone = '+62 812 3456 7890';
    $formatted2 = $userStaf->getFormattedPhoneForWhatsApp();
    echo "  [1b] Input '+62 812 3456 7890' -> Result: {$formatted2}\n";
    assert($formatted2 === '6281234567890', "Test 1b Gagal: Format +62 spasi harus jadi 628");

    $userStaf->phone = '81234567890';
    $formatted3 = $userStaf->getFormattedPhoneForWhatsApp();
    echo "  [1c] Input '81234567890' -> Result: {$formatted3}\n";
    assert($formatted3 === '6281234567890', "Test 1c Gagal: Format awalan 8 harus jadi 628");

    $userStaf->phone = null;
    $formatted4 = $userStaf->getFormattedPhoneForWhatsApp();
    echo "  [1d] Input null -> Result: " . var_export($formatted4, true) . "\n";
    assert($formatted4 === null, "Test 1d Gagal: Phone null harus menghasilkan null");
    echo "  -> OK! [TEST 1 LULUS]\n\n";

    // ========================================================
    // 2. TEST HELPER wantsNotification()
    // ========================================================
    echo "TEST 2: Evaluasi Preferensi Notifikasi (wantsNotification)\n";

    // 2a. Staf tanpa nomor HP
    $userStaf->phone = null;
    $userStaf->settings = ['notifikasi_whatsapp' => true];
    $userStaf->save();
    assert($userStaf->wantsNotification('surat_masuk', 'whatsapp') === false, "Test 2a Gagal: Tanpa nomor HP harus false");
    echo "  [2a] Staf tanpa nomor HP -> WA notification: FALSE (OK)\n";

    // 2b. Staf dengan nomor HP tapi Master WhatsApp OFF
    $userStaf->phone = '081234567890';
    $userStaf->settings = ['notifikasi_whatsapp' => false];
    $userStaf->save();
    assert($userStaf->wantsNotification('surat_masuk', 'whatsapp') === false, "Test 2b Gagal: Master WA OFF harus false");
    echo "  [2b] Staf master WA OFF -> WA notification: FALSE (OK)\n";

    // 2c. Staf Master WhatsApp ON & Default Event
    $userStaf->settings = [
        'notifikasi_whatsapp' => true,
        'wa_notif_surat_masuk' => true,
        'wa_notif_surat_revisi' => true,
        'wa_notif_surat_selesai' => true,
        'wa_notif_surat_ditolak' => false, // Sengaja dimatikan user
    ];
    $userStaf->save();

    assert($userStaf->wantsNotification('surat_masuk', 'whatsapp') === true, "Test 2c-1 Gagal");
    assert($userStaf->wantsNotification('surat_revisi', 'whatsapp') === true, "Test 2c-2 Gagal");
    assert($userStaf->wantsNotification('surat_selesai', 'whatsapp') === true, "Test 2c-3 Gagal");
    assert($userStaf->wantsNotification('surat_ditolak', 'whatsapp') === false, "Test 2c-4 Gagal: Event ditolak harus false");
    echo "  [2c] Staf master WA ON dengan granular filter -> surat_masuk: TRUE, surat_ditolak: FALSE (OK)\n";

    // 2d. ADMIN Tidak Menerima Notifikasi Persuratan Personal
    $userAdmin = User::create([
        'username' => 'test_notif_admin',
        'email' => 'admin_notif@univ.ac.id',
        'password' => bcrypt('password'),
        'tipe_entitas' => 'ADMIN',
        'is_active' => true,
        'phone' => '081299999999',
        'settings' => [
            'notifikasi_whatsapp' => true,
            'wa_notif_surat_masuk' => true,
        ],
    ]);
    assert($userAdmin->wantsNotification('surat_masuk', 'whatsapp') === false, "Test 2d Gagal: Admin WA harus selalu false");
    assert($userAdmin->wantsNotification('surat_masuk', 'email') === false, "Test 2d Gagal: Admin Email harus selalu false");
    echo "  [2d] Admin user -> wantsNotification WA & Email: FALSE (OK)\n";
    echo "  -> OK! [TEST 2 LULUS]\n\n";

    // ========================================================
    // 3. TEST UPDATE PROFIL & PRESERVASI SETTING LAIN (LAST ROLE)
    // ========================================================
    echo "TEST 3: Simulasi EditProfile handleRecordUpdate & Settings Merge\n";

    // Inisialisasi user dengan last_active_jabatan_id
    $userStaf->settings = [
        'last_active_jabatan_id' => 99,
        'notifikasi_whatsapp' => false,
    ];
    $userStaf->save();

    // Buat dummy page EditProfile dan panggil handleRecordUpdate via Reflection
    $editProfile = new EditProfile();
    $reflection = new ReflectionClass(EditProfile::class);
    $method = $reflection->getMethod('handleRecordUpdate');
    $method->setAccessible(true);

    $formData = [
        'nama_lengkap' => 'Nama Baru Staf Notif',
        'phone' => '081298765432',
        'settings' => [
            'notifikasi_whatsapp' => true,
            'wa_notif_surat_masuk' => true,
            'wa_notif_surat_revisi' => true,
            'wa_notif_surat_selesai' => true,
            'wa_notif_surat_ditolak' => true,
            'notifikasi_email' => true,
            'notifikasi_popup' => true,
        ],
    ];

    $updatedRecord = $method->invoke($editProfile, $userStaf, $formData);
    $userStaf->refresh();

    echo "  [3a] Hasil Phone di DB: {$userStaf->phone}\n";
    assert($userStaf->phone === '081298765432', "Test 3a Gagal: Phone tidak terupdate");

    echo "  [3b] Hasil Settings di DB: " . json_encode($userStaf->settings) . "\n";
    assert(($userStaf->settings['last_active_jabatan_id'] ?? null) === 99, "Test 3b Gagal: last_active_jabatan_id hilang saat update settings!");
    assert(($userStaf->settings['notifikasi_whatsapp'] ?? null) === true, "Test 3b Gagal: notifikasi_whatsapp harus true");
    assert(($userStaf->settings['wa_notif_surat_masuk'] ?? null) === true, "Test 3b Gagal: wa_notif_surat_masuk harus true");
    assert(($userStaf->settings['notifikasi_email'] ?? null) === true, "Test 3b Gagal: notifikasi_email harus true");
    assert(($userStaf->settings['notifikasi_popup'] ?? null) === true, "Test 3b Gagal: notifikasi_popup harus true");
    assert($userStaf->wantsNotification('surat_masuk', 'popup') === true, "Test 3b Gagal: wantsNotification popup harus true");
    assert($userStaf->wantsNotification('surat_masuk', 'web') === true, "Test 3b Gagal: wantsNotification web harus selalu true (database notif)");
    echo "  -> OK! [TEST 3 LULUS]\n\n";

    echo "=== SEMUA TEST PENGATURAN PREFERENSI NOTIFIKASI LULUS 100%! ===\n";

} finally {
    DB::rollBack();
    echo "\n[Database transaction rolled back to keep environment clean.]\n";
}
