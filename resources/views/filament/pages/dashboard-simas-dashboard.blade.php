<x-filament-panels::page>
    @if(auth()->user()->tipe_entitas === 'ADMIN')
    <!-- Overview Stats Component -->
    @include('filament.pages.dashboard.overview-stats', [
    'totalPengguna' => $totalPengguna,
    'templateAktif' => $templateAktif,
    'unitOrganisasi' => $unitOrganisasi
    ])
    <!--  Actions Component -->
    @include('filament.pages.dashboard.quick-actions')

    @elseif(auth()->user()->tipe_entitas === 'STAF')
    <!-- Staff Overview Component  Referensi -->
    @include('filament.pages.dashboard.staff-overview', [
    'hasJabatanAktif' => $hasJabatanAktif ?? false,
    'namaUnit' => $namaUnit,
    'namaJabatan' => $namaJabatan,
    'perluTindakan' => $perluTindakan,
    'belumDibaca' => $belumDibaca,
    'dalamProses' => $dalamProses,
    'selesaiBulanIni' => $selesaiBulanIni,
    ])

    @elseif(auth()->user()->tipe_entitas === 'MAHASISWA')
    <!-- Mahasiswa Overview Component -->
    @include('filament.pages.dashboard.mahasiswa-overview', [
    'totalPengajuan' => $totalPengajuan,
    'menungguVerifikasi' => $menungguVerifikasi,
    'selesai' => $selesai,
    'pengajuans' => $pengajuans,
    ])
    @else
    <!-- Fallback widgets for Staff / other users -->
    <x-filament-widgets::widgets
        :widgets="$this->getWidgets()"
        :columns="$this->getColumns()" />
    @endif
</x-filament-panels::page>
