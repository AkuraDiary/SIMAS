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

        {{-- KARTU PERINGATAN REVISI & TOMBOL PERBAIKI --}}
        @if($surat->status_surat === 'REVISI')
        @php
        $catatanRevisi = $surat->riwayats->where('status', 'REVISI')->last()?->catatan ?? 'Pemeriksa meminta Anda untuk melengkapi atau memperbaiki dokumen permohonan ini.';
        @endphp
        <div class="rounded-2xl bg-white shadow-sm border border-gray-200 p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-amber-500/20">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-7 h-7" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Pengajuan Memerlukan Perbaikan
                        </h3>
                        <p class="text-xs text-amber-800 dark:text-amber-300 font-semibold uppercase tracking-wider my-2">
                            Catatan dari Petugas Pemeriksa:
                        </p>
                        <div class="p-3 bg-white/80 dark:bg-gray-200/60 rounded-xl text-sm text-gray-800 dark:text-gray-200">
                            {{ $catatanRevisi }}
                        </div>
                    </div>
                </div>

                <div class="shrink-0 w-full sm:w-auto">
                    <a
                        href="{{ route('pengajuan', ['revisi' => $surat->tracking_code, 'step' => 2]) }}"
                        class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-6 py-3 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-sm shadow-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <x-filament::icon icon="heroicon-m-pencil-square" class="w-4 h-4" />
                        <span>Perbaiki Pengajuan Sekarang</span>
                    </a>
                </div>
            </div>
        </div>
        @endif

        {{-- MODAL FORM PERBAIKAN --}}
        @if($showRevisiModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-xl w-full p-6 sm:p-8 space-y-6 border border-gray-100 dark:border-gray-800">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-arrow-up-tray" class="w-5 h-5 text-amber-600" />
                        Kirim Berkas Perbaikan
                    </h3>
                    <button type="button" wire:click="closeRevisiModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="submitRevisi" class="space-y-4">
                    {{-- Input Catatan Perbaikan --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                            Penjelasan Perbaikan <span class="text-red-500">*</span>
                        </label>
                        <x-filament::input.wrapper class="grow">
                            <textarea
                                type="text"
                                name="catatanPerbaikan"
                                wire:model="catatanPerbaikan"
                                class="w-full block border-0 p-2 outline-0"
                                placeholder="Jelaskan perubahan yang telah Anda lakukan atau tanggapan untuk petugas..."
                                required></textarea>
                        </x-filament::input.wrapper>
                        <!-- <textarea

                            \
                            required></textarea> -->
                        @error('catatanPerbaikan')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Upload Lampiran Baru / Tambahan --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                            Unggah Berkas Baru / Pengganti (Opsional)
                        </label>
                        <input
                            type="file"
                            wire:model="lampiranBaru"
                            multiple
                            class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 order border-gray-200 rounded-xl p-2 cursor-pointer" />
                        <p class="text-[11px] text-gray-400 mt-1">Format: PDF, JPG, PNG (Maks. 5MB per berkas). Berkas baru akan ditambahkan ke daftar lampiran surat.</p>
                        @error('lampiranBaru.*')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror

                        <div wire:loading wire:target="lampiranBaru" class="text-xs text-primary-600 mt-1">
                            Mengunggah berkas sementara...
                        </div>
                    </div>

                    {{-- Tombol Aksi Modal --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                        <button
                            type="button"
                            wire:click="closeRevisiModal"
                            class="px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-50">
                            Batal
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-sm font-bold shadow-sm transition">
                            <span wire:loading.remove wire:target="submitRevisi">Kirim Ulang Sekarang</span>
                            <span wire:loading wire:target="submitRevisi">Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- 2. KARTU HASIL PENGUNDUHAN DOKUMEN (HANYA JIKA TERBITAN SUDAH SELESAI) -->
        @php
        $terbitan = $surat->terbitans()->whereIn('status_surat', ['SELESAI', 'TERBIT'])->latest()->first();
        $attachmentCount = $terbitan ? $terbitan->getMedia('lampiran-surat')->count() : 0;
        @endphp

        @if($terbitan)
        <div class="rounded-2xl border-2 hadow-sm border-gray-200  bg-white p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl  flex items-center justify-center shrink-0">
                        <x-filament::icon icon="heroicon-o-document-check" class="w-7 h-7" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Balasan Surat Sudah Terbit!
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                            Pengajuan anda telah selesai diproses dan surat balasan telah diterbitkan oleh pihak berwenang.
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
                        class="w-full sm:w-auto justify-center text-white bg-green-600">
                        Unduh Dokumen
                    </x-filament::button>
                    @else
                    <x-filament::button
                        wire:click="downloadTerbitan({{ $surat->id }})"
                        size="lg"
                        class="w-full sm:w-auto justify-center text-white bg-green-600">
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
