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

echo "=== MEMULAI TEST FILTER SURAT RUJUKAN (PENGAJUAN SELESAI & BELUM TERBIT) ===\n\n";

DB::beginTransaction();

try {
    $units = UnitKerja::take(2)->get();
    $unitA = $units[0];
    $unitB = $units[1];

    $jabatan = Jabatan::firstOrCreate(['nama_jabatan' => 'Staff Test Filter'], ['level_jabatan' => 3]);
    $user = User::firstOrCreate(['username' => 'test_filter_user'], [
        'email' => 'test_filter@univ.ac.id',
        'password' => bcrypt('password'),
        'tipe_entitas' => 'STAF',
        'is_active' => true,
    ]);

    $pegawai = UserPegawai::firstOrCreate(['user_id' => $user->id], [
        'nip' => '199001012020011001',
        'nama_lengkap' => 'Tester Filter Rujukan',
    ]);

    $upj = UserPegawaiJabatan::firstOrCreate([
        'user_pegawai_id' => $pegawai->id,
        'jabatan_id' => $jabatan->id,
        'unit_kerja_id' => $unitA->id,
    ], ['status_jabatan' => 'AKTIF']);

    Auth::login($user);
    session(['active_jabatan_id' => $upj->id]);

    // 1. Pengajuan Selesai untuk Unit A (HARUS MUNCUL)
    $suratSelesaiUnitA = Surat::create([
        'unit_pengirim_id' => $unitB->id,
        'tipe_surat' => 'PENGAJUAN',
        'perihal' => 'Pengajuan Selesai Unit A Siap Diterbitkan',
        'status_surat' => 'SELESAI',
        'pengirim_nama' => 'Mahasiswa 1',
        'pengirim_nim' => '220101',
    ]);
    $suratSelesaiUnitA->unitTujuan()->attach($unitA->id, ['jenis_tujuan' => 'utama', 'status_baca' => 'SUDAH']);

    // 2. Pengajuan DIPROSES untuk Unit A (TIDAK BOLEH MUNCUL KARENA FILTER = SELESAI)
    $suratDiprosesUnitA = Surat::create([
        'unit_pengirim_id' => $unitB->id,
        'tipe_surat' => 'PENGAJUAN',
        'perihal' => 'Pengajuan Masih Diproses Unit A',
        'status_surat' => 'DIPROSES',
        'pengirim_nama' => 'Mahasiswa 2',
        'pengirim_nim' => '220102',
    ]);
    $suratDiprosesUnitA->unitTujuan()->attach($unitA->id, ['jenis_tujuan' => 'utama', 'status_baca' => 'SUDAH']);

    // 3. Pengajuan Selesai TAPI SUDAH ADA TERBITAN RESMI (TIDAK BOLEH MUNCUL KARENA SUDAH TERBIT)
    $suratSudahTerbit = Surat::create([
        'unit_pengirim_id' => $unitB->id,
        'tipe_surat' => 'PENGAJUAN',
        'perihal' => 'Pengajuan Yang Sudah Diterbitkan Sebelumnya',
        'status_surat' => 'SELESAI',
        'pengirim_nama' => 'Mahasiswa 3',
        'pengirim_nim' => '220103',
    ]);
    $suratSudahTerbit->unitTujuan()->attach($unitA->id, ['jenis_tujuan' => 'utama', 'status_baca' => 'SUDAH']);

    $terbitanLama = Surat::create([
        'unit_pengirim_id' => $unitA->id,
        'tipe_surat' => 'TERBITAN',
        'terbitan_for_surat_id' => $suratSudahTerbit->id,
        'perihal' => 'Surat Terbitan Resmi untuk Mahasiswa 3',
        'status_surat' => 'TERKIRIM',
    ]);

    // 4. Pengajuan Selesai untuk Unit B (TIDAK BOLEH MUNCUL KARENA BUKAN UNIT A)
    $suratSelesaiUnitB = Surat::create([
        'unit_pengirim_id' => $unitB->id,
        'tipe_surat' => 'PENGAJUAN',
        'perihal' => 'Pengajuan Selesai untuk Unit B Saja',
        'status_surat' => 'SELESAI',
        'pengirim_nama' => 'Mahasiswa 4',
        'pengirim_nim' => '220104',
    ]);
    $suratSelesaiUnitB->unitTujuan()->attach($unitB->id, ['jenis_tujuan' => 'utama', 'status_baca' => 'SUDAH']);

    // TEST QUERY LOGIC DARI SuratForm.php
    $allowedStatuses = ['SELESAI'];
    $activeUnitId = Auth::user()?->getActiveJabatan()?->unit_kerja_id;

    $optionsQuery = Surat::query()
        ->where('tipe_surat', 'PENGAJUAN')
        ->whereIn('status_surat', $allowedStatuses)
        ->whereDoesntHave('terbitans', function ($tq) {
            $tq->whereNotIn('status_surat', ['DIBATALKAN', 'DITOLAK']);
        })
        ->when($activeUnitId, function ($q) use ($activeUnitId) {
            $q->where(function ($uq) use ($activeUnitId) {
                $uq->untukUnit($activeUnitId);
            });
        })
        ->pluck('id')
        ->toArray();

    echo "Hasil ID yang lolos filter: " . json_encode($optionsQuery) . "\n\n";

    echo "Assertion 1: Pengajuan Selesai Unit A (ID: {$suratSelesaiUnitA->id}) HARUS MUNCUL...\n";
    assert(in_array($suratSelesaiUnitA->id, $optionsQuery), "Test 1 Gagal: Surat Selesai Unit A harus muncul");
    echo "  -> OK!\n";

    echo "Assertion 2: Pengajuan DIPROSES (ID: {$suratDiprosesUnitA->id}) TIDAK BOLEH MUNCUL...\n";
    assert(!in_array($suratDiprosesUnitA->id, $optionsQuery), "Test 2 Gagal: Surat DIPROSES tidak boleh muncul");
    echo "  -> OK!\n";

    echo "Assertion 3: Pengajuan yang SUDAH ADA TERBITAN (ID: {$suratSudahTerbit->id}) TIDAK BOLEH MUNCUL...\n";
    assert(!in_array($suratSudahTerbit->id, $optionsQuery), "Test 3 Gagal: Surat sudah terbit tidak boleh muncul");
    echo "  -> OK!\n";

    echo "Assertion 4: Pengajuan Unit B (ID: {$suratSelesaiUnitB->id}) TIDAK BOLEH MUNCUL untuk Unit A...\n";
    assert(!in_array($suratSelesaiUnitB->id, $optionsQuery), "Test 4 Gagal: Surat unit lain tidak boleh muncul");
    echo "  -> OK!\n";

    echo "\n=== SEMUA ASSERTION FILTER RUJUKAN LULUS 100%! ===\n";

} finally {
    DB::rollBack();
    echo "\n[Database transaction rolled back to keep environment clean.]\n";
}
