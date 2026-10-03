<div class="space-y-8">
    {{-- Header Overview --}}
    <div>
        <!-- <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Overview</h2> -->
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Statistik persuratan dan aktivitas terkini untuk <strong>{{ $namaUnit }}</strong> ({{ $namaJabatan }}).
        </p>
    </div>

    {{-- 4 Stat Cards --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        {{-- 1. Perlu Tindakan --}}
        <a href="{{ \App\Filament\Pages\StafUnit\SuratMasuk\SuratMasuk::getUrl() }}" class="block p-6 bg-white rounded-2xl border border-gray-100 shadow-sm transition hover:shadow-md hover:border-rose-200 dark:bg-gray-900 dark:border-gray-800 dark:hover:border-rose-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    PERLU TINDAKAN
                </span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-500 font-extrabold text-base dark:bg-rose-950/40 dark:text-rose-400">
                    !
                </div>
            </div>
            <div class="mt-4">
                <span class="text-4xl font-black tracking-tight text-gray-900 dark:text-white">
                    {{ $perluTindakan }}
                </span>
            </div>
        </a>

        {{-- 2. Belum Dibaca --}}
        <a href="{{ \App\Filament\Pages\StafUnit\SuratMasuk\SuratMasuk::getUrl() }}" class="block p-6 bg-white rounded-2xl border border-gray-100 shadow-sm transition hover:shadow-md hover:border-indigo-200 dark:bg-gray-900 dark:border-gray-800 dark:hover:border-indigo-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    BELUM DIBACA
                </span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-500 dark:bg-indigo-950/40 dark:text-indigo-400">
                    <x-heroicon-o-envelope class="h-5 w-5" />
                </div>
            </div>
            <div class="mt-4">
                <span class="text-4xl font-black tracking-tight text-gray-900 dark:text-white">
                    {{ $belumDibaca }}
                </span>
            </div>
        </a>

        {{-- 3. Dalam Proses --}}
        <a href="{{ \App\Filament\Resources\Surats\SuratResource::getUrl('index', ['scope' => 'keluar']) }}" class="block p-6 bg-white rounded-2xl border border-gray-100 shadow-sm transition hover:shadow-md hover:border-amber-200 dark:bg-gray-900 dark:border-gray-800 dark:hover:border-amber-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    DALAM PROSES
                </span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-500 dark:bg-amber-950/40 dark:text-amber-400">
                    <x-heroicon-o-ellipsis-horizontal-circle class="h-6 w-6" />
                </div>
            </div>
            <div class="mt-4">
                <span class="text-4xl font-black tracking-tight text-gray-900 dark:text-white">
                    {{ $dalamProses }}
                </span>
            </div>
        </a>

        {{-- 4. Selesai (Bulan Ini) --}}
        <a href="#" class="block p-6 bg-white rounded-2xl border border-gray-100 shadow-sm transition hover:shadow-md hover:border-emerald-200 dark:bg-gray-900 dark:border-gray-800 dark:hover:border-emerald-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    SELESAI (BULAN INI)
                </span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-500 dark:bg-emerald-950/40 dark:text-emerald-400">
                    <x-heroicon-o-check-circle class="h-6 w-6" />
                </div>
            </div>
            <div class="mt-4">
                <span class="text-4xl font-black tracking-tight text-gray-900 dark:text-white">
                    {{ $selesaiBulanIni }}
                </span>
            </div>
        </a>
    </div>

    {{-- Aksi Cepat Card Container --}}
    <div class="p-6 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-900 dark:border-gray-800">
        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-5">
            AKSI CEPAT
        </h3>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            {{-- 1. Surat Masuk --}}
            <a href="{{ \App\Filament\Pages\StafUnit\SuratMasuk\SuratMasuk::getUrl() }}" class="flex flex-col items-center justify-center p-6 bg-white rounded-xl border border-gray-200 hover:border-indigo-400 hover:bg-gray-50/70 transition-all dark:bg-gray-800/60 dark:border-gray-700 dark:hover:border-indigo-500 dark:hover:bg-gray-800">
                <x-heroicon-o-inbox-arrow-down class="h-6 w-6 text-indigo-600 dark:text-indigo-400 mb-2.5" />
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Surat Masuk</span>
            </a>

            {{-- 2. Arsip Surat --}}
            <a href="{{ \App\Filament\Resources\Surats\SuratResource::getUrl('index', ['scope' => 'arsip']) }}" class="flex flex-col items-center justify-center p-6 bg-white rounded-xl border border-gray-200 hover:border-indigo-400 hover:bg-gray-50/70 transition-all dark:bg-gray-800/60 dark:border-gray-700 dark:hover:border-indigo-500 dark:hover:bg-gray-800">
                <x-heroicon-o-archive-box class="h-6 w-6 text-indigo-600 dark:text-indigo-400 mb-2.5" />
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Arsip Surat</span>
            </a>

            {{-- 3. Pengajuan / Terbitan Baru --}}
            <a href="{{ \App\Filament\Resources\Surats\Pages\CreateSurat::getUrl(['tipe_surat' => 'TERBITAN']) }}" class="flex flex-col items-center justify-center p-6 bg-white rounded-xl border border-gray-200 hover:border-indigo-400 hover:bg-gray-50/70 transition-all dark:bg-gray-800/60 dark:border-gray-700 dark:hover:border-indigo-500 dark:hover:bg-gray-800">
                <x-heroicon-o-arrow-up-tray class="h-6 w-6 text-indigo-600 dark:text-indigo-400 mb-2.5" />
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Terbitkan Surat Rujukan</span>
            </a>

            {{-- 4. Buat Draft Surat --}}
            <a href="{{ \App\Filament\Resources\Surats\Pages\CreateSurat::getUrl() }}" class="flex flex-col items-center justify-center p-6 bg-white rounded-xl border border-gray-200 hover:border-indigo-400 hover:bg-gray-50/70 transition-all dark:bg-gray-800/60 dark:border-gray-700 dark:hover:border-indigo-500 dark:hover:bg-gray-800">
                <x-heroicon-o-document-plus class="h-6 w-6 text-indigo-600 dark:text-indigo-400 mb-2.5" />
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Buat Draft Surat</span>
            </a>
        </div>
    </div>
</div>
