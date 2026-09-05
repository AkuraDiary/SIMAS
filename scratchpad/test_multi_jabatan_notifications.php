<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\UnitKerja;
use App\Models\Jabatan;
use App\Models\UserPegawai;
use App\Models\UserPegawaiJabatan;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

echo "=== MEMULAI TEST ISOLASI NOTIFIKASI MULTI JABATAN ===\n\n";

DB::beginTransaction();

try {
    // 1. Setup Units & User dengan 2 Jabatan Aktif
    $units = UnitKerja::take(2)->get();
    $unitA = $units[0];
    $unitB = $units[1];

    $jabatanA = Jabatan::firstOrCreate(['nama_jabatan' => 'Sekretaris Unit A'], ['level_jabatan' => 2]);
    $jabatanB = Jabatan::firstOrCreate(['nama_jabatan' => 'Kepala Unit B'], ['level_jabatan' => 1]);

    $user = User::create([
        'username' => 'test_multi_yanto',
        'email' => 'yanto_test@univ.ac.id',
        'password' => bcrypt('password'),
        'tipe_entitas' => 'STAF',
        'is_active' => true,
    ]);

    $pegawai = UserPegawai::create([
        'user_id' => $user->id,
        'nip' => '198888882020011001',
        'nama_lengkap' => 'Yanto Multi Role',
    ]);

    $upjA = UserPegawaiJabatan::create([
        'user_pegawai_id' => $pegawai->id,
        'jabatan_id' => $jabatanA->id,
        'unit_kerja_id' => $unitA->id,
        'status_jabatan' => 'AKTIF',
    ]);

    $upjB = UserPegawaiJabatan::create([
        'user_pegawai_id' => $pegawai->id,
        'jabatan_id' => $jabatanB->id,
        'unit_kerja_id' => $unitB->id,
        'status_jabatan' => 'AKTIF',
    ]);

    // 2. Kirim Notifikasi untuk Unit A
    Notification::make()
        ->title('Surat Masuk Unit A')
        ->body('Dokumen untuk Unit A')
        ->viewData([
            'unit_kerja_id' => (int) $unitA->id,
            'surat_id'      => 9991,
        ])
        ->sendToDatabase($user);

    // 3. Kirim Notifikasi untuk Unit B
    Notification::make()
        ->title('Surat Masuk Unit B')
        ->body('Dokumen untuk Unit B')
        ->viewData([
            'unit_kerja_id' => (int) $unitB->id,
            'surat_id'      => 9992,
        ])
        ->sendToDatabase($user);

    // 4. Kirim Notifikasi Umum Akun (Tanpa Unit)
    Notification::make()
        ->title('Pemberitahuan Sistem')
        ->body('Informasi umum akun Anda')
        ->sendToDatabase($user);

    // ==========================================================
    // SKENARIO 1: USER SEDANG AKTIF SEBAGAI KEPALA UNIT B
    // ==========================================================
    echo "SKENARIO 1: User Aktif di Unit B ({$unitB->nama_unit})\n";
    session(['active_jabatan_id' => $upjB->id]);

    $notifsWhenB = $user->notifications()->pluck('data')->toArray();
    $titlesWhenB = array_column($notifsWhenB, 'title');
    echo "  Notifikasi yang tampil: " . json_encode($titlesWhenB) . "\n";

    assert(in_array('Surat Masuk Unit B', $titlesWhenB), "Gagal: Notifikasi Unit B harus muncul saat aktif di Unit B");
    assert(in_array('Pemberitahuan Sistem', $titlesWhenB), "Gagal: Notifikasi umum harus muncul");
    assert(!in_array('Surat Masuk Unit A', $titlesWhenB), "Gagal: Notifikasi Unit A TIDAK BOLEH muncul saat aktif di Unit B!");
    echo "  -> OK! [SKENARIO 1 LULUS - Unit A terisolasi dengan sempurna]\n\n";

    // ==========================================================
    // SKENARIO 2: USER BERALIH PERAN KE SEKRETARIS UNIT A
    // ==========================================================
    echo "SKENARIO 2: User Switch Role ke Unit A ({$unitA->nama_unit})\n";
    session(['active_jabatan_id' => $upjA->id]);

    $notifsWhenA = $user->notifications()->pluck('data')->toArray();
    $titlesWhenA = array_column($notifsWhenA, 'title');
    echo "  Notifikasi yang tampil: " . json_encode($titlesWhenA) . "\n";

    assert(in_array('Surat Masuk Unit A', $titlesWhenA), "Gagal: Notifikasi Unit A harus muncul saat aktif di Unit A");
    assert(in_array('Pemberitahuan Sistem', $titlesWhenA), "Gagal: Notifikasi umum harus muncul");
    assert(!in_array('Surat Masuk Unit B', $titlesWhenA), "Gagal: Notifikasi Unit B TIDAK BOLEH muncul saat aktif di Unit A!");
    echo "  -> OK! [SKENARIO 2 LULUS - Unit B terisolasi dengan sempurna]\n\n";

    echo "=== SEMUA TEST ISOLASI NOTIFIKASI MULTI JABATAN LULUS 100%! ===\n";

} finally {
    DB::rollBack();
    echo "\n[Database transaction rolled back to keep environment clean.]\n";
}
