<x-filament-panels::page>
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 20px;
        }

        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #475569;
        }
    </style>
    <x-filament::modal
        id="preview-modal"
        width="7xl">
        <x-slot name="heading">
            Preview File
        </x-slot>

        @if ($previewUrl)
        <iframe src="{{ $previewUrl }}" style="height: 75vh;  object-fit: cover;" class="border-0 w-full"></iframe>
        @endif

        <x-slot name="footer">
            <div class="flex justify-end">
                <x-filament::button
                    tag="a"
                    href="{{ $downloadUrl }}"
                    target="_blank"
                    color="primary">
                    Download
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>


    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- LEFT COLUMN: Main Content & Attachments --}}
        <div class="col-span-1 md:col-span-2 space-y-6">
            {{-- Paper Card for HTML Content --}}

            <div class="mt-4 rounded-xl border border-gray-200 bg-gray-100 p-6 flex justify-center overflow-x-auto dark:border-gray-800 dark:bg-gray-900/50">
                <div class="relative w-full max-w-3xl min-h-[800px] bg-white text-black p-10 shadow-lg dark:shadow-none ring-1 ring-gray-950/5">
                    <div class="prose max-w-none prose-sm sm:prose-base dark:prose-invert">
                        {!! $this->renderedHtml !!}
                    </div>
                </div>
            </div>


            {{-- Lampiran Section --}}
            @php
            $lampirans = $surat->getMedia('lampiran-surat');
            @endphp
            @if ($lampirans->isNotEmpty())
            <x-filament::section>
                <x-slot name="heading">
                    Lampiran Berkas ({{ $lampirans->count() }})
                </x-slot>

                <div class="flex flex-wrap gap-4 mt-2">
                    @foreach ($lampirans as $lampiran)
                    <x-filament::card class="w-full sm:w-auto">
                        <button wire:click="openPreview({{ $lampiran->id }})" class="flex items-center gap-4 text-left w-full hover:bg-gray-50 dark:hover:bg-white/5 transition-colors p-2 rounded-lg -m-2">
                            <div class="w-12 h-12 flex-shrink-0 flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                                @if ($lampiran->hasGeneratedConversion('thumb'))
                                <img src="{{ route('media.thumb', $lampiran->id) }}" alt="{{ $lampiran->file_name }}" class="object-cover w-full h-full" />
                                @else
                                <span class="text-xs font-bold text-gray-500">{{ strtoupper($lampiran->extension) }}</span>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-xs">{{ $lampiran->file_name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($lampiran->size / 1024, 2) }} KB</p>
                            </div>
                        </button>
                    </x-filament::card>
                    @endforeach
                </div>
            </x-filament::section>
            @endif
        </div>


        {{-- RIGHT COLUMN: Metadata & Disposition Timeline --}}
        <div class="col-span-1 space-y-6">

            {{-- Informasi Pengirim --}}
            <x-filament::section>
                <x-slot name="heading">
                    <span class="text-xs font-bold tracking-widest text-gray-500 uppercase">Informasi Pengirim</span>
                </x-slot>

                <div class="grid grid-cols-2 gap-y-6 gap-x-4 mt-2">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Informasi Pengirim</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            @if ($surat->tipe_surat === 'EKSTERNAL')
                            Eksternal melalui {{ $surat->unitPengirim?->nama_unit ?? 'Sistem' }}
                            @else
                            @if ($surat->userPegawaiJabatan)
                            {{ $surat->userPegawaiJabatan->pegawai->nama_lengkap ?? 'Pegawai' }}<br>
                            <span class="text-xs text-gray-500 font-normal">
                                {{ $surat->userPegawaiJabatan->jabatan->nama_jabatan ?? '' }} - {{ $surat->userPegawaiJabatan->unitKerja->nama_unit ?? '' }}
                            </span>
                            @else
                            {{ $surat->unitPengirim?->nama_unit ?? 'Sistem' }}
                            @endif
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Tanggal Surat</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $surat->created_at->format('d M Y') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Diterima Via</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $surat->tipe_surat === 'EKSTERNAL' ? 'Manual/Upload' : 'Sistem SIMAS' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Tanggal Terima</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $suratUnit?->tanggal_terima ? \Carbon\Carbon::parse($suratUnit->tanggal_terima)->format('d M Y') : '-' }}
                        </p>
                    </div>

                </div>
            </x-filament::section>



            {{-- Riwayat & Perjalanan Surat --}}
            <x-filament::section>
                <x-slot name="heading">
                    <span class="text-xs font-bold tracking-widest text-gray-500 uppercase">Perjalanan Surat</span>
                </x-slot>

                <div class="mt-4 max-h-[400px] overflow-y-auto pr-4 custom-scrollbar">
                    @include('filament.pages.components.surat-timeline')
                </div>
            </x-filament::section>
            {{-- Lembar Disposisi --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex justify-between items-center w-full">
                        <span class="text-xs font-bold tracking-widest text-gray-500 uppercase">Lembar Disposisi</span>
                    </div>
                </x-slot>

                <div class="mt-4 max-h-[400px] overflow-y-auto pr-4 custom-scrollbar">
                    @if ($surat->disposisis->isEmpty())
                    <div class="flex flex-col items-center justify-center py-6 text-center">
                        <x-heroicon-o-document-text class="w-8 h-8 text-gray-400 mb-2" />
                        <p class="text-sm text-gray-500 italic">Tidak/Belum ada disposisi.</p>
                    </div>
                    @else
                    <div class="relative border-l-2 border-gray-200 dark:border-gray-700 ml-3 space-y-8 mt-2">
                        @foreach ($surat->disposisis->sortBy('tanggal_disposisi') as $d)
                        <div class="relative pl-6">
                            {{-- Timeline Dot --}}
                            <span class="absolute flex items-center justify-center w-4 h-4 rounded-full -left-[9px] top-1 {{ $d->status_disposisi === 'selesai' ? 'bg-green-500 ring-4 ring-green-100 dark:ring-green-900' : 'bg-blue-500 ring-4 ring-blue-100 dark:ring-blue-900' }}">
                            </span>

                            {{-- Target Unit & Status Badge --}}
                            <div class="flex flex-wrap gap-2 items-center mb-1">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                    Ke {{ $d->unitTujuan->nama_unit }}
                                </h3>
                                @if($d->status_disposisi === 'SELESAI')
                                <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded dark:bg-emerald-900/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">Selesai</span>
                                @else
                                <span class="bg-indigo-100 text-indigo-800 text-[10px] font-bold px-2 py-0.5 rounded dark:bg-indigo-900/50 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">Sedang Proses</span>
                                @endif
                            </div>

                            {{-- Date & Sender --}}
                            <time class="block mb-3 text-xs font-normal text-gray-500 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($d->tanggal_disposisi)->format('d M Y, H:i') }} • Dari: {{ $d?->pembuat?->name ?? ''}}
                                @if ($d?->userPegawaiJabatan)
                                ({{ $d->userPegawaiJabatan->jabatan->nama_jabatan ?? '' }} - {{ $d->userPegawaiJabatan->unitKerja->nama_unit ?? '' }})
                                @endif
                            </time>

                            {{-- Content Card --}}
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3 border border-gray-100 dark:border-gray-700">
                                <p class="text-xs text-gray-600 dark:text-gray-300 mb-2">
                                    {!! nl2br($d->catatan ?? 'Tidak ada catatan.') !!}
                                </p>
                                <div class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                    <span class="text-gray-400">Instruksi:</span> {{ $d->jenis_instruksi }}
                                </div>
                            </div>

                            {{-- Bukti Media --}}
                            @if ($d->getMedia('bukti-disposisi')->isNotEmpty())
                            <div class="mt-3 flex gap-2">
                                @foreach ($d->getMedia('bukti-disposisi') as $media)
                                <button
                                    type="button"
                                    @click="$dispatch('open-image-modal', { url: '{{ route('media.preview', $media->id) }}' })"
                                    class="block w-12 h-12 rounded border border-gray-200 overflow-hidden hover:opacity-75 transition-opacity cursor-pointer focus:outline-none">
                                    <img src="{{ route('media.preview', $media->id) }}" class="object-cover w-full h-full" alt="Bukti" />
                                </button>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </x-filament::section>

        </div>

    </div>

    @include('filament.pages.components.image-modal')

</x-filament-panels::page>