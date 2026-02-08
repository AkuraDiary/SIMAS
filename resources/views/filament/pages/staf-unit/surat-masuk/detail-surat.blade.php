<x-filament-panels::page>

    @php
    $userUnitId = auth()->user()->unit_kerja_id;
    @endphp

    <x-filament::section collapsible>
        <x-slot name="heading">Lembar Disposisi</x-slot>

        @forelse ($disposisiUntukSaya as $disposisi)
        <div class="border rounded p-4 space-y-2">
            <hr>
            <br>
            <p><strong>Tanggal Disposisi:</strong> {{ $disposisi->tanggal_disposisi }}</p>
            <p><strong>Asal:</strong> {{ $disposisi->pembuat->unitKerja->nama_unit }}</p>
            <p><strong>Instruksi:</strong> {{ $disposisi->jenis_instruksi }}</p>
            <p><strong>Sifat:</strong> {{ $disposisi->sifat }}</p>
            <p><strong>Catatan:</strong><br>{{ $disposisi->catatan }}</p>
            <p class="text-sm text-gray-500">
                <strong>Status:</strong> {{ $disposisi->status_disposisi }}
            </p>
        </div>
        @empty
        <p class="text-gray-500 italic">
            Tidak ada disposisi untuk unit ini.
        </p>
        @endforelse

        <br>
        <hr>
        <br>
        @if ($disposisiLainnya->isEmpty())
        <p class="text-gray-500 italic">Tidak ada disposisi untuk unit lain.</p>
        @else
        <ul class="list-disc pl-5">
            @foreach ($disposisiLainnya as $d)
            <li>
                {{ $d->unitTujuan->nama_unit }}
                ({{ $d->status_disposisi }})
            </li>
            @endforeach
        </ul>
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
        <hr>
        <br>

        {!! nl2br(e($surat->isi_surat)) !!}

        <br><br>
        <hr>
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