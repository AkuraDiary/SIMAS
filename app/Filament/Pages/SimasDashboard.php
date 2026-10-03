<?php

namespace App\Filament\Pages;


use App\Models\Surat;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class SimasDashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    public static string|BackedEnum|null $navigationIcon = 'dashboard-r';

    protected static ?string $slug = 'simas-dashboard';
    protected static string $routePath = '/';

    protected string $view = 'filament.pages.dashboard-simas-dashboard';

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
