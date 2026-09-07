<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Surat;
use App\Models\UnitKerja;
use App\Models\User;
use App\Models\UserPegawai;
use App\Models\UserPegawaiJabatan;
use App\Models\Jabatan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

echo "=== MEMULAI TEST 3 PENYESUAIAN SISTEM PERSURATAN ===\n\n";

DB::beginTransaction();

try {
    // SETUP TEST DATA
    $units = UnitKerja::take(2)->get();
    $unit1 = $units[0] ?? UnitKerja::create(['nama_unit' => 'Fakultas Teknik', 'singkatan' => 'FT', 'jenis_unit_id' => 1]);
    $unit2 = $units[1] ?? UnitKerja::create(['nama_unit' => 'Prodi Informatika', 'singkatan' => 'IF', 'jenis_unit_id' => 1]);

    $jabatanDekan = Jabatan::firstOrCreate(['nama_jabatan' => 'Dekan Fakultas Teknik'], ['level_jabatan' => 1]);
    $jabatanKaprodi = Jabatan::firstOrCreate(['nama_jabatan' => 'Koordinator Prodi Informatika'], ['level_jabatan' => 2]);

    $user = User::where('username', 'test_multi_role_user')->first();
    if (!$user) {
        $user = User::create([
            'username' => 'test_multi_role_user',
            'email' => 'test_multi@univ.ac.id',
            'password' => bcrypt('password'),
            'tipe_entitas' => 'STAF',
            'is_active' => true,
        ]);
    }

    $pegawai = UserPegawai::firstOrCreate(['user_id' => $user->id], [
        'nip' => '198501012010011001',
        'nama_lengkap' => 'Prof. Dr. Ir. Budi Santoso, M.Kom.',
    ]);

    $upjDekan = UserPegawaiJabatan::firstOrCreate([
        'user_pegawai_id' => $pegawai->id,
        'jabatan_id' => $jabatanDekan->id,
        'unit_kerja_id' => $unit1->id,
    ], [
        'status_jabatan' => 'AKTIF',
    ]);

    $upjKaprodi = UserPegawaiJabatan::firstOrCreate([
        'user_pegawai_id' => $pegawai->id,
        'jabatan_id' => $jabatanKaprodi->id,
        'unit_kerja_id' => $unit2->id,
    ], [
        'status_jabatan' => 'AKTIF',
    ]);

    Auth::login($user);

    // ========================================================
    // TEST 1: Identitas Pengirim pada Surat Rujukan (terbitan_for_surat_id)
    // ========================================================
    echo "TEST 1: getIdentitasPengirim() pada Surat Rujukan / Pengajuan\n";

    // 1a. Pengajuan Mahasiswa / Guest
    $pengajuanMhs = Surat::create([
        'unit_pengirim_id' => $unit1->id,
        'tipe_surat' => 'PENGAJUAN',
        'perihal' => 'Permohonan Surat Keterangan Aktif Kuliah',
        'status_surat' => 'DIPROSES',
        'pengirim_nama' => 'Ahmad Fauzi',
        'pengirim_nim' => '220101001',
    ]);

    $identitasMhs = $pengajuanMhs->getIdentitasPengirim();
    echo "  [1a] Pengirim Mahasiswa: {$identitasMhs}\n";
    assert($identitasMhs === 'Ahmad Fauzi (220101001)', "Test 1a Gagal: Format pengirim mahasiswa salah");

    // 1b. Pengajuan Dosen / Pegawai
    $pengajuanPegawai = Surat::create([
        'unit_pengirim_id' => $unit1->id,
        'user_pegawai_jabatan_id' => $upjDekan->id,
        'tipe_surat' => 'PENGAJUAN',
        'perihal' => 'Pengajuan Anggaran Penelitian Fakultas',
        'status_surat' => 'DIPROSES',
    ]);

    $identitasPegawai = $pengajuanPegawai->getIdentitasPengirim();
    echo "  [1b] Pengirim Pegawai: {$identitasPegawai}\n";
    assert(str_contains($identitasPegawai, 'Prof. Dr. Ir. Budi Santoso') && str_contains($identitasPegawai, 'Dekan Fakultas Teknik'), "Test 1b Gagal: Format pengirim pegawai salah");

    // 1c. Pengajuan Eksternal
    $pengajuanEksternal = Surat::create([
        'unit_pengirim_id' => $unit1->id,
        'tipe_surat' => 'EKSTERNAL',
        'pengirim_eksternal' => 'Kementerian ESDM RI',
        'perihal' => 'Kerjasama Riset Transisi Energi',
        'status_surat' => 'DIPROSES',
    ]);

    $identitasEksternal = $pengajuanEksternal->getIdentitasPengirim();
    echo "  [1c] Pengirim Eksternal: {$identitasEksternal}\n";
    assert($identitasEksternal === 'Kementerian ESDM RI', "Test 1c Gagal: Format pengirim eksternal salah");

    echo "  -> OK! [TEST 1 LULUS]\n\n";

    // ========================================================
    // TEST 2 & 3: Multi Jabatan State & Switch Role Persistence
    // ========================================================
    echo "TEST 2 & 3: Save Last State Jabatan tanpa Skema Migrasi Baru & Default Kirim Sebagai\n";

    // Simulasikan SwitchRole ke Dekan
    $page = new \App\Filament\Pages\SwitchRole();
    $page->switchRole($upjDekan->id);

    $user->refresh();
    echo "  [2a] Session active_jabatan_id: " . session('active_jabatan_id') . "\n";
    echo "  [2a] User settings di DB: " . json_encode($user->settings) . "\n";
    assert(session('active_jabatan_id') == $upjDekan->id, "Test 2a Gagal: Session harus upjDekan");
    assert(($user->settings['last_active_jabatan_id'] ?? null) == $upjDekan->id, "Test 2a Gagal: Settings harus menyimpan last_active_jabatan_id");

    $currentActive = $user->getActiveJabatan();
    assert($currentActive->id == $upjDekan->id, "Test 2a Gagal: getActiveJabatan() harus Dekan");
    assert($user->unit_kerja_id == $unit1->id, "Test 2a Gagal: unit_kerja_id harus Unit 1");

    // Sekarang simulasikan SwitchRole ke Kaprodi
    $page->switchRole($upjKaprodi->id);
    $user->refresh();
    echo "  [2b] Beralih ke Kaprodi...\n";
    echo "  [2b] Session active_jabatan_id: " . session('active_jabatan_id') . "\n";
    echo "  [2b] User settings di DB: " . json_encode($user->settings) . "\n";
    assert(session('active_jabatan_id') == $upjKaprodi->id, "Test 2b Gagal: Session harus upjKaprodi");
    assert(($user->settings['last_active_jabatan_id'] ?? null) == $upjKaprodi->id, "Test 2b Gagal: Settings harus menyimpan Kaprodi");

    // SIMULASI LOGIN BARU (SESSION KOSONG):
    echo "  [3a] Mensimulasikan Login Baru (Session di-flush)...\n";
    session()->flush();
    assert(session('active_jabatan_id') === null, "Session harus kosong");

    // Saat login baru, getActiveJabatan() dipanggil
    $restoredActive = $user->getActiveJabatan();
    echo "  [3a] Hasil getActiveJabatan() setelah login fresh: {$restoredActive->jabatan->nama_jabatan} ({$restoredActive->unitKerja->nama_unit})\n";
    assert($restoredActive->id == $upjKaprodi->id, "Test 3a Gagal: Harus otomatis ter-restore sebagai Kaprodi!");
    assert(session('active_jabatan_id') == $upjKaprodi->id, "Test 3a Gagal: Session harus terisi Kaprodi kembali");
    assert($user->unit_kerja_id == $unit2->id, "Test 3a Gagal: unit_kerja_id harus Unit 2 (Prodi)");

    echo "  -> OK! [TEST 2 & 3 LULUS]\n\n";

    echo "=== SEMUA PENYESUAIAN BERHASIL DIVERIFIKASI 100%! ===\n";

} finally {
    DB::rollBack();
    echo "\n[Database transaction rolled back to keep environment clean.]\n";
}
