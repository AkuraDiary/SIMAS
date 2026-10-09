<div class="flex flex-col gap-2">
    @php
    $statusColors = [
    'BARU' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:border-blue-800',
    'DIPROSES' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-800',
    'SELESAI' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-800',
    'TERKIRIM' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-800',
    'DITOLAK' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/40 dark:text-rose-300 dark:border-rose-800',
    'REVISI' => 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-900/40 dark:text-orange-300 dark:border-orange-800',
    ];

    $colorClass = $statusColors[$surat->status_surat] ?? 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700';

    $statusIcons = [
    'BARU' => 'heroicon-s-sparkles',
    'DIPROSES' => 'heroicon-s-arrow-path',
    'SELESAI' => 'heroicon-s-check-circle',
    'TERKIRIM' => 'heroicon-s-paper-airplane',
    'DITOLAK' => 'heroicon-s-x-circle',
    'REVISI' => 'heroicon-s-pencil-square',
    ];

    $icon = $statusIcons[$surat->status_surat] ?? 'heroicon-s-information-circle';

    $tipeColors = [
    'INTERNAL' => 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
    'PENGAJUAN' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800',
    'TERBITAN' => 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-950/40 dark:text-teal-300 dark:border-teal-800',
    'EKSTERNAL' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800',
    ];
    $tipeColorClass = $tipeColors[$surat->tipe_surat] ?? 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700';

    $tipeIcons = [
    'INTERNAL' => 'heroicon-m-building-office-2',
    'PENGAJUAN' => 'heroicon-m-document-text',
    'TERBITAN' => 'heroicon-m-document-check',
    'EKSTERNAL' => 'heroicon-m-globe-alt',
    ];
    $tipeIcon = $tipeIcons[$surat->tipe_surat] ?? 'heroicon-m-envelope';
    @endphp

    {{-- Row 1: Judul Surat + Inline Tipe Badge + Inline Status Badge --}}
    <div class="flex flex-wrap items-center gap-2">
        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-gray-950 dark:text-white leading-tight">
            {{ $surat->perihal }}
        </h1>

        {{-- Badge Tipe Surat --}}
        <span class="inline-flex items-center gap-1.5 {{ $tipeColorClass }} text-xs font-semibold px-2.5 py-1 rounded-full border shadow-xs shrink-0">
            <x-filament::icon :icon="$tipeIcon" class="h-3.5 w-3.5" />
            {{ ucfirst(strtolower($surat->tipe_surat)) }}
        </span>

        {{-- Badge Status Surat --}}
        <span class="inline-flex items-center gap-1.5 {{ $colorClass }} text-xs font-semibold px-2.5 py-1 rounded-full border shadow-xs shrink-0">
            <x-filament::icon :icon="$icon" class="h-3.5 w-3.5" />
            {{ ucfirst(strtolower($surat->status_surat)) }}
        </span>
    </div>

    {{-- Row 2: Metadata Bar (Nomor, Pemohon, Tanggal) --}}
    <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
        @if($surat->nomor_surat)
        <span class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-mono font-medium px-2 py-0.5 rounded-md border border-gray-200 dark:border-gray-700">
            <x-filament::icon icon="heroicon-m-hashtag" class="w-3 h-3 text-gray-400" />
            {{ $surat->nomor_surat }}
        </span>
        @endif

        @if($surat->isBackdate())
        @php
        $lastBackdate = $surat->nomorSuratLogs()->where('is_backdate', true)->latest('id')->first();
        @endphp
        <span class="inline-flex items-center gap-1 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 px-2 py-0.5 rounded-md border border-amber-200 dark:border-amber-800" title="{{ $lastBackdate?->alasan_backdate ?? 'Surat Ditetapkan Mundur' }}">
            <x-filament::icon icon="heroicon-m-clock" class="w-3 h-3 text-amber-500" />
            Backdate: {{ $lastBackdate?->tanggal_ditetapkan?->format('d/m/Y') }}
        </span>
        @endif

        @if($surat->nomor_surat_eksternal)
        <span class="inline-flex items-center gap-1 bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 px-2 py-0.5 rounded-md border border-purple-200 dark:border-purple-800">
            No. Asal: {{ $surat->nomor_surat_eksternal }}
        </span>
        @endif

        @if($surat->nomor_surat || $surat->nomor_surat_eksternal)
        <span class="text-gray-300 dark:text-gray-600 select-none">•</span>
        @endif

        {{-- Pemohon / Pengirim --}}
        <span class="inline-flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
            <x-filament::icon icon="heroicon-m-user" class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500" />
            @if ($surat->userPegawaiJabatan)
            {{-- Prioritas 1: Jika pengirim adalah Pegawai / Staf Unit --}}
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $surat->userPegawaiJabatan->pegawai->nama_lengkap ?? 'Pegawai' }}</span>
            <span class="text-gray-500 dark:text-gray-400">
                ({{ $surat->userPegawaiJabatan->jabatan->nama_jabatan ?? '' }} - {{ $surat->userPegawaiJabatan->unitKerja->nama_unit ?? $surat->unitPengirim?->nama_unit ?? '' }})
            </span>
            @elseif ($surat->tipe_surat === 'EKSTERNAL')
            {{-- Prioritas 2: Surat Eksternal --}}
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $surat->pengirim_nama ?? 'Eksternal' }}</span>
            <span class="text-gray-500 dark:text-gray-400">via {{ $surat->unitPengirim?->nama_unit ?? 'Sistem' }}</span>
            @elseif (filled($surat->pengirim_nama))
            {{-- Prioritas 3: Mahasiswa atau Pemohon Luar / Guest --}}
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $surat->pengirim_nama }}</span>
            @if($surat->pengirim_nim)
            <span class="text-gray-500 dark:text-gray-400">(NIM: {{ $surat->pengirim_nim }})</span>
            @elseif(!empty($surat->pengirim_metadata['instansi']))
            <span class="text-gray-500 dark:text-gray-400">({{ $surat->pengirim_metadata['instansi'] }})</span>
            @else
            <span class="text-gray-500 dark:text-gray-400">(Pemohon Luar / Guest)</span>
            @endif
            @else
            {{-- Fallback: Nama Unit Pengirim --}}
            <span>{{ $surat->unitPengirim?->nama_unit ?? 'Sistem' }}</span>
            @endif
        </span>

        {{-- Tanggal Dibuat --}}
        @if($surat->created_at)
        <span class="text-gray-300 dark:text-gray-600 select-none">•</span>
        <span class="inline-flex items-center gap-1 text-gray-500 dark:text-gray-400">
            <x-filament::icon icon="heroicon-m-calendar" class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500" />
            {{ $surat->created_at->translatedFormat('d M Y, H:i') }}
        </span>
        @endif
    </div>

    {{-- Banner Link to Terbitan (jika ini adalah PENGAJUAN dan sudah ada balasan) --}}
     @php
        $isPemohon = \Illuminate\Support\Facades\Auth::check() && (
            (\Illuminate\Support\Facades\Auth::user()->unit_kerja_id == $surat->unit_pengirim_id) ||
            (\Illuminate\Support\Facades\Auth::id() == $surat->user_pembuat_id)
        );
    @endphp
    @if($surat && $surat->tipe_surat === 'PENGAJUAN' && $isPemohon && $surat->terbitans->count() > 0)
    <div class="mt-2 flex">
        @foreach($surat->terbitans as $terbitan)
        @if(in_array($terbitan->status_surat, ['SELESAI', 'TERBIT']))
        <div class="px-6 py-2 w-2xl bg-teal-50 dark:bg-teal-900/30 border border-teal-200 dark:border-teal-800 rounded-lg flex justify-between shadow-sm">
            <div class="flex items-center gap-2 text-teal-800 dark:text-teal-300">
                <x-filament::icon icon="heroicon-o-document-check" class="w-5 h-5 text-teal-600 dark:text-teal-400" />
                <span class="text-sm font-medium">Surat balasan untuk pengajuan ini telah selesai diproses.</span>
            </div>
            <x-filament::button
                tag="a"
                href="{{ \App\Filament\Pages\StafUnit\SuratMasuk\DetailSurat::getUrl(['surat' => Vinkla\Hashids\Facades\Hashids::encode($terbitan->id)]) }}"
                color="teal"
                class="text-teal-800 font-bold"
                >
                Buka Surat Terbitan
            </x-filament::button>
        </div>
        @endif
        @endforeach
    </div>
    @endif
</div>
