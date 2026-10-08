<x-filament-panels::page>

    {{-- MODAL PREVIEW BERKAS (Identik dengan Halaman Pegawai) --}}
    <x-filament::modal id="preview-modal" width="7xl">
        <x-slot name="heading">
            Pratinjau Berkas Lampiran
        </x-slot>

        @if ($previewUrl)
        @if($previewIsImage)
        <div class="flex justify-center items-center h-[75vh] w-full bg-gray-100 dark:bg-gray-900 rounded-xl overflow-hidden p-2">
            <img src="{{ $previewUrl }}" class="max-h-full max-w-full object-contain rounded-lg" />
        </div>
        @else
        <iframe src="{{ $previewUrl }}" style="height: 75vh;" class="border-0 w-full rounded-xl shadow-inner"></iframe>
        @endif
        @else
        <div class="p-8 text-center text-gray-500">
            Format berkas ini tidak mendukung pratinjau langsung di peramban. Silakan klik tombol di bawah untuk mengunduh.
        </div>
        @endif

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                @if($downloadUrl)
                <x-filament::button
                    tag="a"
                    href="{{ $downloadUrl }}"
                    target="_blank"
                    color="primary"
                    icon="heroicon-o-arrow-down-tray">
                    Unduh Berkas
                </x-filament::button>
                @endif
            </div>
        </x-slot>
    </x-filament::modal>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- KOLOM KIRI: Draf Dokumen Surat & Berkas Lampiran --}}
        <div class="col-span-1 md:col-span-2 space-y-6">


            {{-- HASIL AKHIR: SURAT BALASAN RESMI ATAU DOKUMEN PENGESAHAN --}}
            @php
            $terbitan = $this->record->terbitans->sortByDesc('created_at')->first();
            $terbitanMedia = $terbitan?->getFirstMedia('dokumen-final') ?? $terbitan?->getFirstMedia('lampiran-surat');
            $terbitanNomor = $terbitan?->nomorSuratLogs->last()?->nomor_lengkap ?? $terbitan?->nomor_surat;
            $terbitanUnit = $terbitan?->unitPengirim?->nama_unit ?? 'Unit Terkait';
            $isTerbitanSelesai = $terbitan && (in_array(strtoupper($terbitan->status_surat), ['SELESAI', 'TERBIT']) || $terbitanMedia !== null);

            $pengajuanMedia = $this->record->getFirstMedia('dokumen-final');
            $pengajuanNomor = $this->record->nomorSuratLogs->last()?->nomor_lengkap ?? $this->record->nomor_surat;
            $isPengajuanSelesai = in_array(strtoupper($this->record->status_surat), ['SELESAI', 'TERBIT']) || $pengajuanMedia !== null;
            @endphp

            {{-- KASUS 1: SURAT BALASAN RESMI TELAH TERBIT --}}
            @if($terbitan && $isTerbitanSelesai && $terbitanMedia)
            <div class="p-6 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xs">
                <div class="flex items-start sm:items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-md">
                        <x-filament::icon icon="heroicon-o-check-badge" class="w-7 h-7" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h4 class="text-sm font-bold text-emerald-900 dark:text-emerald-200">Surat Balasan Resmi Telah Diterbitkan</h4>
                            <span class="inline-flex items-center text-[10px] font-semibold bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300 px-2 py-0.5 rounded-full border border-emerald-300 dark:border-emerald-700">
                                Output Resmi
                            </span>
                        </div>
                        <p class="text-xs text-emerald-800 dark:text-emerald-300 mt-1">
                            No. Surat: <span class="font-mono font-semibold">{{ $terbitanNomor ?: '-' }}</span>
                            <span class="text-emerald-400 dark:text-emerald-600 mx-1">•</span>
                            Perihal: <span class="font-medium">{{ $terbitan->perihal }}</span>
                            @if($terbitanUnit)
                            <span class="text-emerald-400 dark:text-emerald-600 mx-1">•</span>
                            Oleh: <span class="font-medium">{{ $terbitanUnit }}</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    {{-- Tombol Lihat Surat Balasan Resmi --}}
                    <button type="button"
                        wire:click="openPreview({{ $terbitanMedia->id }})"
                        class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm transition inline-flex items-center gap-2 cursor-pointer">
                        <x-filament::icon icon="heroicon-o-eye" class="w-4 h-4" />
                        <span>Lihat Surat Balasan</span>
                    </button>

                    {{-- Jika naskah pengajuan awal juga sempat memiliki dokumen-final --}}
                    @if($pengajuanMedia)
                    <button type="button"
                        wire:click="openPreview({{ $pengajuanMedia->id }})"
                        class="px-3.5 py-2.5 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 text-xs font-semibold rounded-xl shadow-sm transition cursor-pointer"
                        title="Lihat Naskah Pengajuan Awal">
                        Naskah Awal
                    </button>
                    @endif
                </div>
            </div>

            {{-- KASUS 2: SURAT BALASAN SEDANG DALAM PROSES PENYIAPAN --}}
            @elseif($terbitan && !$isTerbitanSelesai)
            <div class="p-5 bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xs">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-md">
                        <x-filament::icon icon="heroicon-o-arrow-path" class="w-6 h-6 animate-spin" />
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-blue-900 dark:text-blue-200">Surat Balasan Resmi Sedang Disiapkan</h4>
                        <p class="text-xs text-blue-700 dark:text-blue-300 mt-0.5">
                            Permohonan Anda telah disetujui. {{ $terbitanUnit }} sedang menyiapkan dokumen balasan/keputusan resmi.
                        </p>
                    </div>
                </div>
                @if($pengajuanMedia)
                <button type="button"
                    wire:click="openPreview({{ $pengajuanMedia->id }})"
                    class="px-3.5 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-blue-700 dark:text-blue-300 border border-blue-300 dark:border-blue-700 text-xs font-semibold rounded-xl shadow-sm transition cursor-pointer shrink-0">
                    Lihat Dokumen Pengajuan
                </button>
                @endif
            </div>

            {{-- KASUS 3: DOKUMEN PENGAJUAN DISAHKAN LANGSUNG (SELF-SERVICE / SURAT KETERANGAN RESMI) --}}
            @elseif($isPengajuanSelesai && $pengajuanMedia)
            <div class="p-6 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xs">
                <div class="flex items-start sm:items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-md">
                        <x-filament::icon icon="heroicon-o-check-badge" class="w-7 h-7" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h4 class="text-sm font-bold text-emerald-900 dark:text-emerald-200">Dokumen Resmi Telah Disahkan</h4>
                            <span class="inline-flex items-center text-[10px] font-semibold bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300 px-2 py-0.5 rounded-full border border-emerald-300 dark:border-emerald-700">
                                Selesai
                            </span>
                        </div>
                        <p class="text-xs text-emerald-800 dark:text-emerald-300 mt-1">
                            No. Dokumen: <span class="font-mono font-semibold">{{ $pengajuanNomor ?: '-' }}</span>
                            <span class="text-emerald-400 dark:text-emerald-600 mx-1">•</span>
                            Permohonan Anda telah disetujui dan dokumen resmi telah diterbitkan.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button"
                        wire:click="openPreview({{ $pengajuanMedia->id }})"
                        class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm transition inline-flex items-center gap-2 cursor-pointer">
                        <x-filament::icon icon="heroicon-o-arrow-down-tray" class="w-4 h-4" />
                        <span>Unduh / Lihat Dokumen</span>
                    </button>
                </div>
            </div>
            @endif

            {{-- 1. KERTAS DOKUMEN DIGITAL --}}
            <div class="space-y-2">
                <div class="flex items-center justify-between px-1">
                    <span class="text-xs font-semibold tracking-wider text-gray-500 uppercase flex items-center gap-1.5">
                        <x-filament::icon icon="heroicon-o-document-text" class="w-4 h-4 text-gray-400" />
                        Naskah Pengajuan Mahasiswa
                    </span>
                    @if($this->record->nomor_surat)
                    <span class="text-xs font-mono text-gray-500 dark:text-gray-400">
                        No. {{ $this->record->nomor_surat }}
                    </span>
                    @endif
                </div>
                <div class="rounded-2xl border border-gray-200 bg-gray-100 p-6 flex justify-center overflow-x-auto dark:border-gray-800 dark:bg-gray-900/50">
                    <div class="relative w-full max-w-3xl min-h-[800px] bg-white text-black p-10 shadow-lg dark:shadow-none ring-1 ring-gray-950/5 rounded-xl">
                        <div class="prose max-w-none prose-sm sm:prose-base dark:prose-invert">
                            {!! $this->renderedHtml !!}
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. BERKAS LAMPIRAN --}}
            @php
            $lampirans = $this->record->getMedia('lampiran-surat');
            @endphp
            @if($lampirans->isNotEmpty())
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-paper-clip" class="w-5 h-5 text-primary-600" />
                        <span>Lampiran Berkas Pendukung ({{ $lampirans->count() }})</span>
                    </div>
                </x-slot>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                    @foreach($lampirans as $media)
                    <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:border-primary-300 dark:hover:border-primary-700 transition">
                        {{-- Tombol Buka Preview --}}
                        <button type="button" wire:click="openPreview({{ $media->id }})" class="flex items-center gap-3 overflow-hidden text-left flex-1 cursor-pointer">
                            <div class="w-10 h-10 rounded-lg bg-primary-50 dark:bg-primary-950/50 text-primary-600 flex items-center justify-center shrink-0 font-bold text-xs uppercase">
                                {{ strtoupper($media->extension) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate hover:text-primary-600" title="{{ $media->file_name }}">
                                    {{ $media->file_name }}
                                </p>
                                <p class="text-[10px] text-gray-400">{{ number_format($media->size / 1024, 1) }} KB &bull; Klik untuk preview</p>
                            </div>
                        </button>

                        {{-- Tombol Langsung Unduh --}}
                        {{--
                        <a href="{{ route('media.download', $media->id) }}" target="_blank" class="p-2 text-gray-400 hover:text-primary-600 rounded-lg transition" title="Unduh Langsung">
                        <x-filament::icon icon="heroicon-o-arrow-down-tray" class="w-4 h-4" />
                        </a>
                        --}}
                    </div>
                    @endforeach
                </div>
            </x-filament::section>
            @endif


        </div>

        {{-- KOLOM KANAN: Informasi Status, Panel Revisi, & Linimasa Perjalanan Surat --}}
        <div class="col-span-1 space-y-6">

            {{-- 1. INFORMASI STATUS & PENGAJUAN --}}
            <x-filament::section>
                <x-slot name="heading">
                    <span class="text-xs font-bold tracking-widest text-gray-500 uppercase">Informasi Permohonan</span>
                </x-slot>

                @php
                $status = strtoupper($this->record->status_surat ?? 'DIPROSES');
                $isApproved = in_array($status, ['SELESAI', 'TERBIT']);
                $isPending = in_array($status, ['DIPROSES', 'TERKIRIM', 'MENUNGGU']);
                $badgeColor = $isApproved ? 'success' : ($isPending ? 'warning' : 'danger');
                @endphp

                <div class="space-y-4 text-xs">
                    <div>
                        <span class="text-gray-400 block mb-1">Status Terkini</span>
                        <x-filament::badge :color="$badgeColor" size="lg">
                            {{ $status }}
                        </x-filament::badge>
                    </div>

                    <div>
                        <span class="text-gray-400 block mb-0.5">Kode Pelacakan</span>
                        <p class="font-mono font-bold text-primary-600 dark:text-primary-400 text-sm">
                            {{ $this->record->tracking_code ?: 'REQ-' . $this->record->id }}
                        </p>
                    </div>

                    <div>
                        <span class="text-gray-400 block mb-0.5">Waktu Pengajuan</span>
                        <p class="font-medium text-gray-800 dark:text-gray-200">
                            {{ $this->record->created_at ? $this->record->created_at->translatedFormat('d F Y, H:i') : '-' }} WIB
                        </p>
                    </div>

                    <div>
                        <span class="text-gray-400 block mb-0.5">Unit Tujuan</span>
                        <p class="font-medium text-gray-800 dark:text-gray-200">
                            {{ $this->record->unitTujuan->pluck('nama_unit')->join(', ') ?: ($this->record->template?->entryPointUnit?->nama_unit ?? 'Unit Terkait') }}
                        </p>
                    </div>
                </div>
            </x-filament::section>

            {{-- 2. PANEL PERINGATAN REVISI DENGAN TOMBOL PERBAIKI LANGSUNG --}}
            @if($this->record->status_surat === 'REVISI')
            @php
            $catatanRevisi = $this->record->riwayats->where('status', 'REVISI')->last()?->catatan ?? 'Pemeriksa meminta Anda untuk melengkapi atau memperbaiki dokumen ini.';
            @endphp
            <div class="rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 p-5 space-y-4">
                <div class="flex items-start gap-3">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-6 h-6 text-amber-600 shrink-0 mt-0.5" />
                    <div>
                        <h4 class="text-sm font-bold text-amber-900 dark:text-amber-200">Perlu Perbaikan Berkas</h4>
                        <p class="text-xs text-amber-800 dark:text-amber-300 mt-1 leading-relaxed">
                            <strong>Catatan Petugas:</strong> {{ $catatanRevisi }}
                        </p>
                    </div>
                </div>

                <div class="pt-2">
                    <a href="{{ \App\Filament\Resources\MahasiswaSurats\MahasiswaSuratResource::getUrl('edit', ['record' => $this->record]) }}"
                        class="inline-flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl shadow-sm transition">
                        <x-filament::icon icon="heroicon-m-pencil-square" class="w-4 h-4" />
                        <span>Perbaiki Surat Sekarang &rarr;</span>
                    </a>
                </div>
            </div>
            @endif

            {{-- 3. LINIMASA PERJALANAN SURAT (TIMELINE) --}}
            <x-filament::section>
                <x-slot name="heading">
                    <span class="text-xs font-bold tracking-widest text-gray-500 uppercase">Linimasa Surat</span>
                </x-slot>

                <div class="mt-2 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                    @include('filament.pages.components.surat-timeline')
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
