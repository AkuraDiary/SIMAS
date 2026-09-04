@php
$data = $data ?? $livewire->data ?? [];
$isMahasiswa = ($data['tipe_pengirim'] ?? '') === 'mahasiswa';
$isScratch = ($data['template_id'] ?? '') === 'scratch';

$template = null;
$unitTujuanName = '-';
$perihal = '-';

if ($isScratch) {
$perihal = $data['perihal'] ?? '-';
$unitId = $data['unit_tujuan'] ?? null;
if ($unitId) {
$unitTujuanName = \App\Models\UnitKerja::find($unitId)?->nama_unit ?? '-';
}
} else {
$templateId = $data['template_id'] ?? null;
if ($templateId) {
$template = \App\Models\Template::with('entryPointUnit')->find($templateId);
$perihal = 'Pengajuan ' . ($template?->nama_template ?? '');
$unitTujuanName = $template?->entryPointUnit?->nama_unit ?? 'Sesuai Template';
}
}
@endphp

<div class="border border-gray-200 rounded-xl overflow-hidden bg-white mb-6 shadow-sm">

    <!-- DATA PENGIRIM -->
    <div class="border-b border-gray-200">
        <div class="flex items-center gap-2 px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            <h3 class="font-bold text-gray-900 text-lg">Data Pengirim</h3>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50/30">
            <div>
                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Nama Lengkap</p>
                <p class="font-medium text-gray-900">{{ $data['pengirim_nama'] ?? '-' }}</p>
            </div>

            @if($isMahasiswa)
            <div>
                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">NIM / NIDN</p>
                <p class="font-medium text-gray-900">{{ $data['pengirim_nim'] ?? '-' }}</p>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Fakultas / Unit</p>
                <p class="font-medium text-gray-900">
                    @php
                    $fakId = $data['pengirim_fakultas'] ?? null;
                    echo $fakId ? (\App\Models\UnitKerja::find($fakId)?->nama_unit ?? '-') : '-';
                    @endphp
                </p>
            </div>
            @else
            <div>
                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Instansi / Asal</p>
                <p class="font-medium text-gray-900">{{ $data['pengirim_instansi'] ?? '-' }}</p>
            </div>
            @endif

            <div>
                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Email / Kontak</p>
                <p class="font-medium text-gray-900">{{ $data['pengirim_email'] ?? '-' }} <br> <span class="text-sm text-gray-500">{{ $data['pengirim_telp'] ?? '' }}</span></p>
            </div>
        </div>
    </div>

    <!-- DETAIL SURAT -->
    <div class="border-b border-gray-200">
        <div class="flex items-center gap-2 px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="font-bold text-gray-900 text-lg">Detail Surat</h3>
        </div>
        <div class="p-6 bg-white">
            <div class="mb-5">
                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Perihal</p>
                <div class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3 text-gray-700 font-medium">
                    {{ $perihal }}
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Tujuan</p>
                    <p class="font-medium text-gray-900">{{ $unitTujuanName }}</p>
                </div>

                @if(!$isScratch && isset($data['content']) && is_array($data['content']))
                @foreach($data['content'] as $key => $val)
                @if(is_string($val))
                <div>
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">{{ str_replace('_', ' ', $key) }}</p>
                    <p class="font-medium text-gray-900">{{ $val }}</p>
                </div>
                @endif
                @endforeach
                @endif
            </div>
        </div>
    </div>

    <!-- LAMPIRAN -->
    <div class="border-b border-gray-200">
        <div class="flex items-center gap-2 px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
            </svg>
            <h3 class="font-bold text-gray-900 text-lg">Lampiran Pendukung</h3>
        </div>
        <div class="p-6 bg-white flex flex-wrap gap-4">
            @php 
                $lampiranFiles = $data['lampiran'] ?? []; 
                $lampiranNames = $data['lampiran_names'] ?? [];
                $lampiranNamesList = is_array($lampiranNames) ? array_values($lampiranNames) : [];
                $i = 0;
            @endphp
            @if(empty($lampiranFiles))
                <p class="text-sm text-gray-500 italic">Tidak ada lampiran.</p>
            @else
                @foreach($lampiranFiles as $key => $file)
                    @php
                        $originalName = 'Lampiran ' . ($i + 1);
                        if (is_object($file) && method_exists($file, 'getClientOriginalName')) {
                            $originalName = $file->getClientOriginalName();
                        } elseif (is_string($file)) {
                            $fallback = basename($file);
                            if (str_starts_with($fallback, 'livewire-file-') || str_starts_with($fallback, 'php')) {
                                $fallback = 'Lampiran ' . ($i + 1);
                            }
                            $originalName = $lampiranNames[$key] ?? $lampiranNames[$file] ?? $lampiranNamesList[$i] ?? $fallback;
                        }
                        $i++;
                    @endphp
                    <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg w-full md:w-auto min-w-[250px] bg-gray-50/50">
                        <div class="p-2 bg-red-100 text-red-600 rounded">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
                        </div>
                        <div class="flex-grow">
                            <p class="text-sm font-bold text-gray-800 line-clamp-1" title="{{ $originalName }}">
                                {{ $originalName }}
                            </p>
                            <p class="text-xs text-gray-500">Telah diunggah</p>
                        </div>
                        <div class="text-green-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <!-- DRAF DIGITAL -->

    <div class="p-6 bg-neutral-50 gap-6 items-center md:items-start flex flex-row">
        <div class="w-24 h-24 bg-white border border-gray-200 flex items-center justify-center rounded-lg shadow-sm shrink-0 relative overflow-hidden">
            <div class="absolute inset-0 bg-gray-100 opacity-50 flex">
                <!-- Decorative diagonal lines to mimic wireframe -->
                <div class="w-full h-full border-t border-l border-gray-300 transform rotate-45 scale-150"></div>
            </div>
            <svg class="w-10 h-10 text-primary-500 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
        </div>
        <div>
            <h4 class="text-lg font-bold text-gray-900 mb-1">Draf Surat Digital</h4>
            <p class="text-sm text-gray-500 mb-4">Ini adalah dokumen yang dihasilkan otomatis berdasarkan data yang Anda masukkan. Pastikan isi surat sudah sesuai sebelum dikirim.</p>
            <button type="button" wire:click="downloadDraft" class="text-primary-700 font-bold text-sm flex items-center gap-1 hover:underline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Download Draft (.pdf)
            </button>
        </div>
    </div>

</div>
