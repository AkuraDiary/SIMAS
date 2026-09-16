<div class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-8">

    <!-- FORM PENCARIAN -->
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-6 sm:p-8">
        <div class="max-w-2xl">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-magnifying-glass-circle" class="w-7 h-7 text-primary-600" />
                Lacak Pengajuan Surat
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Masukkan kode pelacakan yang Anda peroleh saat mengajukan surat untuk melihat status dan riwayat verifikasi secara langsung.
            </p>
        </div>

        <form wire:submit.prevent="search" class="mt-6 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="flex-1">
                <x-filament::input.wrapper
                    :valid="! $errors->has('trackingCode')"
                    prefix-icon="heroicon-m-qr-code">
                    <x-filament::input
                        type="text"
                        wire:model="trackingCode"
                        placeholder="Contoh: REQ-ABC12345"
                        class="uppercase"
                        autofocus />
                </x-filament::input.wrapper>
            </div>

            <x-filament::button
                type="submit"
                size="lg"
                class="bg-secondary-500 text-white px-6 py-2 rounded-lg font-medium hover:bg-secondary-600 transition shadow-sm shadow-secondary-200"
                wire:target="search"
                wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="search">Lacak Berkas</span>
                <span wire:loading wire:target="search">Mencari...</span>
            </x-filament::button>
        </form>

        @error('trackingCode')
        <p class="mt-2 text-xs text-danger-600">{{ $message }}</p>
        @enderror

        @if($errorMsg)
        <div class="mt-4 p-4 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 flex items-center gap-3 text-sm text-red-700 dark:text-red-300">
            <x-filament::icon icon="heroicon-m-exclamation-circle" class="w-5 h-5 shrink-0 text-red-500" />
            <span>{{ $errorMsg }}</span>
        </div>
        @endif
    </div>

    <!-- HASIL PELACAKAN -->
    @if($searched && $surat)
    <div class="space-y-6">

        <!-- 1. KARTU RINGKASAN SURAT -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Kode Pelacakan</span>
                    <p class="text-lg font-mono font-bold text-primary-600 dark:text-primary-400">
                        {{ $surat->tracking_code }}
                    </p>
                </div>

                <div>
                    @php
                    $status = $surat->status_surat;
                    $badgeColor = match($status) {
                    'SELESAI', 'TERBIT' => 'success',
                    'DIPROSES', 'DISETUJUI' => 'info',
                    'REVISI' => 'warning',
                    'DITOLAK' => 'danger',
                    default => 'gray',
                    };
                    @endphp
                    <x-filament::badge :color="$badgeColor" size="lg">
                        {{ ucfirst(strtolower($status)) }}
                    </x-filament::badge>
                </div>
            </div>

            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 bg-gray-50/50 dark:bg-gray-800/30">
                <div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-0.5">Perihal</span>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $surat->perihal }}</p>
                </div>

                <div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-0.5">Pemohon</span>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $surat->pengirim_nama ?? 'Guest' }}
                        @if($surat->pengirim_nim)
                        <span class="text-xs text-gray-500 font-normal">({{ $surat->pengirim_nim }})</span>
                        @elseif(!empty($surat->pengirim_metadata['instansi']))
                        <span class="text-xs text-gray-500 font-normal">({{ $surat->pengirim_metadata['instansi'] }})</span>
                        @endif
                    </p>
                </div>

                <div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-0.5">Waktu Pengajuan</span>
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                        {{ $surat->created_at->translatedFormat('d F Y, H:i') }} WIB
                    </p>
                </div>
            </div>
        </div>

        <!-- 2. KARTU HASIL PENGUNDUHAN DOKUMEN RESMI (FR-REQ-05) -->
        @php
        // Ambil data surat Terbitan jika ada
        $terbitan = $surat->terbitans()->latest()->first();
        $isFinished = in_array($surat->status_surat, ['SELESAI', 'TERBIT']) || $terbitan;
        @endphp

        @if($isFinished)
        <div class="rounded-2xl border-2 border-success-500/30 bg-success-50/60 dark:bg-success-950/30 p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-success-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-success-500/20">
                        <x-filament::icon icon="heroicon-o-document-check" class="w-7 h-7" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Dokumen Surat Resmi Telah Terbit!
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                            Pengajuan Anda telah selesai diproses dan surat balasan/rekomendasi resmi telah diterbitkan oleh pihak berwenang.
                        </p>
                        @if($terbitan?->nomor_surat)
                        <p class="text-xs font-mono font-semibold text-success-800 dark:text-success-400 mt-2 bg-success-100 dark:bg-success-900/50 inline-block px-2.5 py-1 rounded-md">
                            Nomor Surat: {{ $terbitan->nomor_surat }}
                        </p>
                        @endif
                    </div>
                </div>

                <div class="shrink-0 w-full sm:w-auto">
                    @if($terbitan)
                    <x-filament::button
                        wire:click="downloadTerbitan({{ $terbitan->id }})"
                        size="lg"
                        color="success"
                        icon="heroicon-m-arrow-down-tray"
                        class="w-full sm:w-auto justify-center">
                        Unduh Dokumen Resmi
                    </x-filament::button>
                    @else
                    <x-filament::button
                        wire:click="downloadTerbitan({{ $surat->id }})"
                        size="lg"
                        color="success"
                        icon="heroicon-m-arrow-down-tray"
                        class="w-full sm:w-auto justify-center">
                        Unduh Dokumen
                    </x-filament::button>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- 3. KARTU LINIMASA RIWAYAT PROSES (FR-REQ-04) -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-6 sm:p-8">
            <div class="border-b border-gray-100 dark:border-gray-800 pb-4 mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-clock" class="w-5 h-5 text-primary-600" />
                    Linimasa Alur Pengajuan
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Riwayat tahapan verifikasi, disposisi antar unit, dan catatan tindak lanjut dari petugas.
                </p>
            </div>

            <!-- Re-use Komponen Surat Timeline Bawaan SIMAS -->
            @if(!empty($this->timelineData))
            @include('filament.pages.components.surat-timeline')
            @else
            <p class="text-sm text-gray-500 italic py-4">Belum ada catatan riwayat untuk pengajuan ini.</p>
            @endif
        </div>

    </div>
    @endif

</div>
