<x-filament-panels::page>

    @php
    $userUnitId = auth()->user()->unit_kerja_id;
    @endphp
    <x-filament::section collapsible>
        <x-slot name="heading">Alur Disposisi</x-slot>
        <x-slot name="description">
            Riwayat disposisi surat
        </x-slot>

        @php
        $userUnitId = auth()->user()->unit_kerja_id;
        @endphp

        @if ($surat->disposisis->isEmpty())
        <p class="text-sm text-gray-500 italic">
            Surat ini belum memiliki disposisi.
        </p>
        @else
        @foreach ($surat->disposisis->sortBy('tanggal_disposisi') as $d)
        @php
        $isForMe = $d->unit_tujuan_id === $userUnitId;
        $prefix = $d->parent_disposisi_id ? '↳ ' : '';
        @endphp

        <p class="{{ $isForMe ? 'font-semibold text-primary-600' : '' }}">
            {{ $prefix }}
            <strong>
                {{ $d->pembuat->unitKerja->nama_unit }}
                →
                {{ $d->unitTujuan->nama_unit }}
            </strong>
            <span class="text-sm text-gray-500">
                ({{ $d->tanggal_disposisi }})
            </span>
        </p>

        <p class="ml-4">
            <strong>Instruksi:</strong> {{ $d->jenis_instruksi }}
        </p>

        <p class="ml-4">
            <strong>Sifat:</strong> {{ ucfirst($d->sifat) }}
            |
            <strong>Status:</strong> {{ ucfirst($d->status_disposisi) }}
        </p>

        @if ($d->catatan)
        <br>
        <p class="ml-4 text-gray-600 italic">
            {{ $d->catatan }}
        </p>
        @endif

        <br>
        @endforeach
        @endif
    </x-filament::section>


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

        <p><strong>Lampiran</strong>

            @if ($surat->lampirans->isEmpty())
        <p class="text-gray-500 italic">
            Tidak ada lampiran.
        </p>
        @else
        <ul class="list-disc pl-5 space-y-2">
            @foreach ($surat->lampirans as $lampiran)
            <li>
                <a
                    href="{{ $lampiran->url }}"
                    target="_blank"
                    class="text-primary-600 hover:underline">
                    {{ $lampiran->filename }}
                </a>
            </li>
            @endforeach
        </ul>
        @endif
    </x-filament::section>

</x-filament-panels::page>