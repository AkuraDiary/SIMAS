<x-filament-panels::page>
    @if(auth()->user()->tipe_entitas === 'ADMIN')
    <!-- Clean, Separable Overview Stats Component -->
    @include('filament.pages.dashboard.overview-stats', [
    'totalPengguna' => $totalPengguna,
    'templateAktif' => $templateAktif,
    'unitOrganisasi' => $unitOrganisasi
    ])

    <!-- Clean, Separable Quick Actions Component -->
    @include('filament.pages.dashboard.quick-actions')
    @elseif(auth()->user()->tipe_entitas === 'STAF')
    <!-- Staff Overview Component Sesuai Mockup Referensi -->
    @include('filament.pages.dashboard.staff-overview', [
    'namaUnit' => $namaUnit,
    'namaJabatan' => $namaJabatan,
    'perluTindakan' => $perluTindakan,
    'belumDibaca' => $belumDibaca,
    'dalamProses' => $dalamProses,
    'selesaiBulanIni' => $selesaiBulanIni,
    ])
    @elseif(auth()->user()->tipe_entitas === 'MAHASISWA')
    <div class="p-6 bg-white rounded-2xl shadow-sm border border-gray-100 dark:bg-gray-900 dark:border-gray-800">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Selamat Datang, {{ auth()->user()->nama_lengkap ?? 'Mahasiswa' }}!</h2>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
            Anda dapat melihat riwayat dan mengajukan permohonan surat melalui menu <strong>Surat Saya</strong> di sidebar.
        </p>
    </div>
    @else
    <!-- Fallback widgets for Staff / other users -->
    <x-filament-widgets::widgets
        :widgets="$this->getWidgets()"
        :columns="$this->getColumns()" />
    @endif
</x-filament-panels::page>
