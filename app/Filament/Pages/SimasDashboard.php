<?php

namespace App\Filament\Pages;


use App\Models\Surat;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class SimasDashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';
    use WithPagination;

    public static string|BackedEnum|null $navigationIcon = 'dashboard-r';

    protected static ?string $slug = 'simas-dashboard';
    protected static string $routePath = '/';

    protected string $view = 'filament.pages.dashboard-simas-dashboard';

    public string $searchMahasiswa = '';
    public function updatedSearchMahasiswa(): void
    {
        $this->resetPage('mahasiswaPage');
    }

    public function getViewData(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }
        // 1. Data untuk ADMIN
        if ($user->tipe_entitas === 'ADMIN') {
            return [
                'totalPengguna'  => \App\Models\User::count(),
                'templateAktif'  => \App\Models\Template::where('is_active', true)->count(),
                'unitOrganisasi' => \App\Models\UnitKerja::count(),
            ];
        }
        // 2. Data untuk STAF / PEGAWAI UNIT
        if ($user->tipe_entitas === 'STAF') {
            $unitId = $user->getActiveUnitId() ?? $user->unit_kerja_id;
            $perluTindakan = 0;
            $belumDibaca = 0;
            $dalamProses = 0;
            $selesaiBulanIni = 0;
            if ($unitId) {
                // Perlu Tindakan: Surat masuk yang menunggu verifikasi/ttd dari unit ini
                $perluTindakan = Surat::where('status_surat', 'DIPROSES')
                    ->whereHas('riwayats', function ($q) use ($unitId, $user) {
                        $q->where('unit_tujuan_id', $unitId)
                            ->where('status', 'MENUNGGU')
                            ->where(function ($sub) use ($user) {
                                $sub->whereNull('user_aktor_id')
                                    ->orWhere('user_aktor_id', $user->id);
                            });
                    })->count();
                // Belum Dibaca: Surat yang masuk ke unit dan belum dibuka
                $belumDibaca = Surat::whereHas('unitTujuan', function ($q) use ($unitId) {
                    $q->where('unit_kerja_id', $unitId)
                        ->where('status_baca', 'BELUM');
                })->count();
                // Dalam Proses: Surat aktif unit yang masih berjalan
                $dalamProses = Surat::whereIn('status_surat', ['DIPROSES', 'TERKIRIM', 'REVISI'])
                    ->where(function ($q) use ($unitId) {
                        $q->where('unit_pengirim_id', $unitId)
                            ->orWhereHas('unitTujuan', fn($u) => $u->where('unit_kerja_id', $unitId));
                    })->count();
                // Selesai Bulan Ini: Surat yang telah tuntas di bulan berjalan
                $selesaiBulanIni = Surat::whereIn('status_surat', ['SELESAI', 'TERBIT', 'ARSIP'])
                    ->where(function ($q) use ($unitId) {
                        $q->where('unit_pengirim_id', $unitId)
                            ->orWhereHas('unitTujuan', fn($u) => $u->where('unit_kerja_id', $unitId));
                    })
                    ->whereMonth('updated_at', now()->month)
                    ->whereYear('updated_at', now()->year)
                    ->count();
            }
            return [
                'namaUnit'        => $user->getActiveJabatan()?->unitKerja?->nama_unit ?? 'Unit Kerja',
                'namaJabatan'     => $user->getActiveJabatan()?->jabatan?->nama_jabatan ?? 'Pegawai',
                'perluTindakan'   => $perluTindakan,
                'belumDibaca'     => $belumDibaca,
                'dalamProses'     => $dalamProses,
                'selesaiBulanIni' => $selesaiBulanIni,
            ];
        }

        // 3. Data untuk MAHASISWA (History Pengajuan)
        if ($user->tipe_entitas === 'MAHASISWA') {
            $nim = $user->mahasiswa?->nim;
            // Query dasar: semua surat pengajuan milik mahasiswa ini (berdasarkan user_id ataupun NIM)
            $baseQuery = Surat::query()
                ->where('tipe_surat', 'PENGAJUAN')
                ->where(function ($q) use ($user, $nim) {
                    $q->where('user_pembuat_id', $user->id);
                    if ($nim) {
                        $q->orWhere('pengirim_nim', $nim);
                    }
                });
            $totalPengajuan     = (clone $baseQuery)->count();
            $menungguVerifikasi = (clone $baseQuery)->whereIn('status_surat', ['DIPROSES', 'TERKIRIM', 'MENUNGGU'])->count();
            $selesai            = (clone $baseQuery)->whereIn('status_surat', ['SELESAI', 'TERBIT'])->count();
            // Query tabel pengajuan dengan pencarian instan dan paginasi
            $tableQuery = (clone $baseQuery)->with(['template']);
            if (filled($this->searchMahasiswa)) {
                $search = trim($this->searchMahasiswa);
                $tableQuery->where(function ($q) use ($search) {
                    $q->where('tracking_code', 'like', "%{$search}%")
                        ->orWhere('perihal', 'like', "%{$search}%")
                        ->orWhereHas('template', fn($t) => $t->where('nama_template', 'like', "%{$search}%"));
                });
            }
            $pengajuans = $tableQuery->latest('created_at')->paginate(5, ['*'], 'mahasiswaPage');
            return [
                'totalPengajuan'     => $totalPengajuan,
                'menungguVerifikasi' => $menungguVerifikasi,
                'selesai'            => $selesai,
                'pengajuans'         => $pengajuans,
            ];
        }
        return [];
    }
    // public function getViewData(): array
    // {
    //     $user = Auth::user();
    //     if ($user && $user->tipe_entitas === 'MAHASISWA') {
    //         return [];
    //     }

    //     return [
    //         'totalPengguna' => \App\Models\User::count(),
    //         'templateAktif' => \App\Models\Template::where('is_active', true)->count(),
    //         'unitOrganisasi' => \App\Models\UnitKerja::count(),
    //     ];
    // }
}
