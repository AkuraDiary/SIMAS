<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Surat;
use App\Models\UnitKerja;
use App\Services\UnitAksesService;
use App\Filament\Livewire\DatabaseNotifications;
use Filament\Notifications\Notification;

echo "=== TEST 1: MULTI-JABATAN ACCESS RESTRICTION ISOLATION ===\n";

$yanto = User::where('username', 'yanto')->first();
if (!$yanto) {
    die("User yanto not found\n");
}

$activeJabatans = $yanto->pegawai->jabatans->where('status_jabatan', 'AKTIF')->values();
if ($activeJabatans->count() < 2) {
    die("Yanto needs at least 2 active jabatans for testing\n");
}

// In user's real scenario:
// Jabatan A = Kepala Logistik (Sender)
// Jabatan B = Sekretaris FT (Receiver, where FT Chief restricts access for staff/secretary)
$jabatanA = $activeJabatans->first(fn($j) => str_contains(strtolower($j->jabatan->nama_jabatan), 'logistik') || $j->jabatan->level_jabatan === 1);
$jabatanB = $activeJabatans->first(fn($j) => str_contains(strtolower($j->jabatan->nama_jabatan), 'sekretaris') || $j->jabatan->level_jabatan > 1);

if (!$jabatanA || !$jabatanB) {
    die("Could not find both a Kepala jabatan and a non-Kepala jabatan for Yanto\n");
}

$unitSenderId = $jabatanA->unit_kerja_id;
$unitReceiverId = $jabatanB->unit_kerja_id;

echo "Jabatan A (Sender / Kepala): {$jabatanA->jabatan->nama_jabatan} | Unit: {$jabatanA->unitKerja->nama_unit} ({$unitSenderId})\n";
echo "Jabatan B (Receiver / Staf): {$jabatanB->jabatan->nama_jabatan} | Unit: {$jabatanB->unitKerja->nama_unit} ({$unitReceiverId})\n";

// Ensure Jabatan B is restricted (HANYA_DISPOSISI) and its unit is TERBATAS_DISPOSISI
$jabatanB->update(['akses_surat_masuk' => 'HANYA_DISPOSISI']);
$receiverUnit = UnitKerja::find($unitReceiverId);
if ($receiverUnit) {
    $receiverUnit->update(['pengaturan_akses' => ['kebijakan_surat_masuk' => 'TERBATAS_DISPOSISI']]);
}

// Find or create a letter from Unit Sender to Unit Receiver created by Yanto under Jabatan A
$surat = Surat::where('unit_pengirim_id', $unitSenderId)
    ->where('user_pembuat_id', $yanto->id)
    ->whereHas('unitTujuan', fn($q) => $q->where('unit_kerja_id', $unitReceiverId))
    ->first();

if (!$surat) {
    $surat = Surat::create([
        'user_pembuat_id' => $yanto->id,
        'user_pegawai_jabatan_id' => $jabatanA->id,
        'unit_pengirim_id' => $unitSenderId,
        'tipe_surat' => 'INTERNAL',
        'status_surat' => 'TERKIRIM',
        'perihal' => 'Surat Pengadaan Antar Unit',
        'tanggal_kirim' => now(),
    ]);
    $surat->unitTujuan()->attach($unitReceiverId, ['jenis_tujuan' => 'UTAMA', 'status_baca' => 'BELUM']);
}

echo "Testing letter ID: {$surat->id} | Pengirim: {$surat->unit_pengirim_id} | Pembuat: {$surat->user_pembuat_id}\n";

$service = app(UnitAksesService::class);

// Scenario A: Yanto active as Receiver (Jabatan B)
session(['active_jabatan_id' => $jabatanB->id]);
auth()->login($yanto);
echo "Active unit: " . $yanto->getActiveUnitId() . " (" . $yanto->getActiveJabatan()->unitKerja->nama_unit . ")\n";

$canAccessInReceiver = $service->canUserAccessSurat($yanto, $surat, $unitReceiverId);
$inSuratMasukReceiver = $service->applySuratMasukFilter(Surat::where('id', $surat->id), $yanto, $unitReceiverId)->exists();

echo "-> Can Yanto access the letter under Receiver unit? " . ($canAccessInReceiver ? "YES (LEAKED!)" : "NO (CORRECT)") . "\n";
echo "-> Does letter appear in Receiver Surat Masuk? " . ($inSuratMasukReceiver ? "YES (LEAKED!)" : "NO (CORRECT)") . "\n";

assert(!$canAccessInReceiver, "Yanto should NOT have access to the letter under Receiver unit when restricted!");
assert(!$inSuratMasukReceiver, "Letter should NOT be in Receiver Surat Masuk when restricted!");

// Scenario B: Yanto active as Sender (Jabatan A)
session(['active_jabatan_id' => $jabatanA->id]);
echo "Active unit: " . $yanto->getActiveUnitId() . " (" . $yanto->getActiveJabatan()->unitKerja->nama_unit . ")\n";

$canAccessInSender = $service->canUserAccessSurat($yanto, $surat, $unitSenderId);
echo "-> Can Yanto access the letter under Sender unit? " . ($canAccessInSender ? "YES (CORRECT)" : "NO (ERROR)") . "\n";

assert($canAccessInSender, "Yanto should have access under sender unit!");

echo "\n=== TEST 2: GLOBAL DATABASE NOTIFICATIONS POP-UP LOGIC ===\n";

// Create instance of our custom component
$notifComponent = new DatabaseNotifications();

// Simulate mount
$notifComponent->mount();
$initialKnownCount = count($notifComponent->knownNotificationIds);
echo "Initial known unread notifications: {$initialKnownCount}\n";

// Clear session notifications before test
session()->forget('filament.notifications');

// Run rendering() immediately after mount: should NOT emit any toast
$notifComponent->rendering();
$afterMountSessionCount = count(session()->get('filament.notifications') ?? []);
echo "Toasts dispatched on initial mount: {$afterMountSessionCount}\n";
assert($afterMountSessionCount === 0, "Initial mount must NOT dispatch any toast pop-up!");

// Simulate a new notification arriving in DB for Yanto under active unit (Sender unit)
Notification::make()
    ->title('Surat Masuk Test Pop-up')
    ->body('Perihal: Uji coba pop-up notifikasi global')
    ->info()
    ->viewData([
        'unit_kerja_id' => $unitSenderId,
        'surat_id' => $surat->id,
    ])
    ->sendToDatabase($yanto);

// Ensure user has notifikasi_popup = true
$yanto->settings = array_merge($yanto->settings ?? [], ['notifikasi_popup' => true]);
$yanto->save();

// Now simulate poll via rendering()
$notifComponent->rendering();
$sessionToasts = session()->get('filament.notifications') ?? [];
echo "Toasts in session after poll: " . count($sessionToasts) . "\n";
if (!empty($sessionToasts)) {
    $lastToast = end($sessionToasts);
    echo "Toast Title: " . ($lastToast['title'] ?? '') . "\n";
    echo "Toast Body: " . ($lastToast['body'] ?? '') . "\n";
}
assert(count($sessionToasts) >= 1, "A new toast should have been dispatched to session!");

// Test disabling pop-up preference
session()->forget('filament.notifications');
$yanto->settings = array_merge($yanto->settings ?? [], ['notifikasi_popup' => false]);
$yanto->save();

// Send another notification
Notification::make()
    ->title('Surat Masuk Test Suppressed')
    ->body('Should not pop up')
    ->info()
    ->viewData([
        'unit_kerja_id' => 6,
        'surat_id' => $surat->id,
    ])
    ->sendToDatabase($yanto);

$notifComponent->rendering();
$suppressedToasts = session()->get('filament.notifications') ?? [];
echo "Toasts in session when notifikasi_popup is false: " . count($suppressedToasts) . "\n";
assert(count($suppressedToasts) === 0, "Toast must be suppressed when notifikasi_popup is false!");

// Restore settings
$yanto->settings = array_merge($yanto->settings ?? [], ['notifikasi_popup' => true]);
$yanto->save();

echo "\nALL TESTS PASSED SUCCESSFULLY!\n";
