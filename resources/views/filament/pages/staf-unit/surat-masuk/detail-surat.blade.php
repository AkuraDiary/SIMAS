<x-filament-panels::page>
    @if ($isViewKirim)
    <x-filament::section collapsible>
        <x-slot name="heading">Penerima</x-slot>
        @foreach ($surat->suratUnits as $su)
        <p>
            <x-filament::badge :color="$su->jenis_tujuan === 'utama' ? 'primary' : 'secondary'">
                {{ $su->jenis_tujuan === 'utama' ? 'Tujuan Utama' : 'Tembusan' }}
            </x-filament::badge>
            <strong>{{ $su->unitKerja->nama_unit }}</strong>
        </p>
        <br>
        @endforeach
    </x-filament::section>
    @endif
    <x-filament::section collapsible collapsed>
        <x-slot name="heading">Alur Disposisi</x-slot>
        <x-slot name="description">
            Riwayat disposisi surat
        </x-slot>

        @if ($surat->disposisis->isEmpty())
        <p class="text-sm text-gray-500 italic">
            Surat ini belum memiliki disposisi.
        </p>
        @else
        @foreach ($surat->disposisis->sortBy('tanggal_disposisi') as $d)

        @php
        $isForMe = $d->unit_tujuan_id === $userUnitId;
        $isFromMe = $d->unit_pembuat_id=== $userUnitId;
        $prefix = $d->parent_disposisi_id ? '↳ ' : '';
        @endphp

        @if ($isForMe || $isFromMe)
        <p class="{{ $isForMe ? 'font-semibold fi-text-primary-600' : '' }}">
            @if ($isFromMe)
            [Dari Saya]
            @endif
            {{ $prefix }}
            <strong>
                {{ $d->unitPembuat->nama_unit }}
                →
                {{ $d->unitTujuan->nama_unit }}
            </strong>
            <span class="text-sm fi-text-gray-500">
                ({{ $d->tanggal_disposisi }})
            </span>
        </p>

        <p class="ml-4">
            <strong>Instruksi:</strong> {{ $d->jenis_instruksi }}
        </p>

        <p class="ml-4">
            <strong>Sifat:</strong>
            <x-filament::badge color="gray">
                {{ ucfirst($d->sifat) }}
            </x-filament::badge>

            |
            <strong>Status:</strong>
            <x-filament::badge :color="$d->status_disposisi === 'selesai' ? 'success' : 'warning'">
                {{ ucfirst($d->status_disposisi) }}
            </x-filament::badge>
        </p>

        @if ($d->catatan)
        <br>
        <p class="ml-4 text-gray-600 italic">
            {!! nl2br($d->catatan) !!}
        </p>
        @endif

        <br>
        @endif

        @endforeach
        @endif
    </x-filament::section>

    @if ($surat->status_surat != 'TERKIRIM' )
    <x-filament::badge :color="$surat->status_surat === 'SELESAI' ? 'success' : 'warning'">
        SURAT {{ ucfirst($surat->status_surat) }}
    </x-filament::badge>
    @endif
    <x-filament::section>
        <x-slot name="heading">
            Surat
        </x-slot>
        <x-slot name="description">
            Nomor: {{ $surat->nomor_surat }} | Agenda: {{ $surat->nomor_agenda }}
        </x-slot>

        <p><strong>Perihal:</strong> {{ $surat->perihal }}</p>
        <p><strong>Pengirim Awal:</strong> {{ $surat->unitPengirim->nama_unit }}</p>
        @if ($suratUnit)
        <p><strong>Diterima:</strong> {{ $suratUnit?->tanggal_terima ?? '-' }}</p>
        @endif

        <p><strong>Jenis Surat:</strong>
            {{ $jenisTujuanLabel }}
        </p>

        <br>

        <div class="prose max-w-none">
            {!! nl2br(e($surat->isi_surat)) !!}
        </div>

        <br>

        @php
        $lampirans = $surat->getMedia('lampiran-surat');
        @endphp

        <p><strong>Lampiran</strong></p>
        @if ($lampirans->isEmpty())
        <p class="text-gray-500 italic">Tidak ada lampiran.</p>
        @else
        {{-- Horizontal scroll --}}
        <p>
            @foreach ($lampirans as $lampiran)
            <a href="{{ route('media.download', $lampiran->id) }}" target="_blank">

                {{-- Thumbnail / placeholder --}}
                @if ($lampiran->hasGeneratedConversion('thumb'))
                <img src="{{ route('media.thumb', $lampiran->id) }}"
                    alt="{{ $lampiran->file_name }}" />
                @else
                <p>
                    {{ strtoupper($lampiran->extension) }}
                </p>
                @endif

                {{-- Nama file --}}
                <x-filament::badge class="mt-1 truncate w-full" color="gray">
                    {{ $lampiran->file_name }}
                </x-filament::badge>

            </a>
            @endforeach
    


        @endif
        </p>

    </x-filament::section>

</x-filament-panels::page>