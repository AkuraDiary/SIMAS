<div class="flex flex-col gap-1">
    <div class="flex items-center gap-2">
        @if($surat->nomor_surat)
        <span class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-semibold px-2.5 py-1 rounded-full border border-gray-300 dark:border-gray-700">
            {{ $surat->nomor_surat }}
        </span>
        @endif
        
        @php
            $statusColors = [
                'BARU' => 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/50 dark:text-blue-300 dark:border-blue-800',
                'DIPROSES' => 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/50 dark:text-amber-300 dark:border-amber-800',
                'SELESAI' => 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/50 dark:text-emerald-300 dark:border-emerald-800',
                'TERKIRIM' => 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/50 dark:text-emerald-300 dark:border-emerald-800',
                'DITOLAK' => 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/50 dark:text-red-300 dark:border-red-800',
                'REVISI' => 'bg-orange-100 text-orange-700 border-orange-200 dark:bg-orange-900/50 dark:text-orange-300 dark:border-orange-800',
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
        @endphp
        
        <span class="flex items-center gap-1.5 {{ $colorClass }} text-xs font-semibold px-2.5 py-1 rounded-full border">
            <x-filament::icon
                :icon="$icon"
                class="h-4 w-4"
            />
            {{ ucfirst(strtolower($surat->status_surat)) }}
        </span>
    </div>
    
    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-950 dark:text-white">
        {{ $surat->perihal }}
    </h1>
    
    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        @if ($surat->tipe_surat === 'EKSTERNAL')
            Eksternal melalui {{ $surat->unitPengirim?->nama_unit ?? 'Sistem' }}
        @else
            @if ($surat->userPegawaiJabatan)
                {{ $surat->userPegawaiJabatan->pegawai->nama_lengkap ?? 'Pegawai' }} 
                <span class="text-xs text-gray-400">
                    ({{ $surat->userPegawaiJabatan->jabatan->nama_jabatan ?? '' }} - {{ $surat->userPegawaiJabatan->unitKerja->nama_unit ?? '' }})
                </span>
            @else
                {{ $surat->unitPengirim?->nama_unit ?? 'Sistem' }}
            @endif
        @endif
    </div>
</div>
