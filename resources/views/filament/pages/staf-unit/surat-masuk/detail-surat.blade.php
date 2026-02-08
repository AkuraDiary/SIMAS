<x-filament-panels::page>

<x-filament::section>
        <x-slot name="heading">Lembar Disposisi</x-slot>

        @forelse ($disposisiUntukSaya as $disposisi)
        <div class="border rounded p-4 space-y-2">
            <p><strong>Dari:</strong> {{ $disposisi->pembuat->unitKerja->nama_unit }}</p>
            <p><strong>Instruksi:</strong> {{ $disposisi->jenis_instruksi }}</p>
            <p><strong>Sifat:</strong> {{ $disposisi->sifat }}</p>
            <p><strong>Catatan:</strong><br>{{ $disposisi->catatan }}</p>
            <p class="text-sm text-gray-500">
                Status: {{ $disposisi->status_disposisi }}
            </p>
        </div>
        @empty
        <p class="text-gray-500 italic">
            Tidak ada disposisi untuk unit ini.
        </p>
        @endforelse

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
            Perihal : {{ $surat->perihal }}
        </x-slot>

        <p><strong>Nomor Surat:</strong> {{ $surat->nomor_surat }}</p>
        <p><strong>Pengirim:</strong> {{ $surat->unitPengirim->nama_unit }}</p>
        <p><strong>Diterima:</strong> {{ $suratUnit?->tanggal_terima ?? '-' }}</p>
        <p>
            <strong>Jenis Surat:</strong>
            {{ $jenisTujuanLabel }}
        </p>

    </x-filament::section>

    <x-filament::section>
        {!! nl2br(e($suratUnit->surat->isi_surat)) !!}
    </x-filament::section>



    <x-filament::section>
        <x-slot name="heading">Lampiran</x-slot>

        @if ($suratUnit->surat->lampirans->isEmpty())
        <p class="text-gray-500 italic">
            Tidak ada lampiran.
        </p>
        @else
        <ul class="list-disc pl-5 space-y-2">
            @foreach ($suratUnit->surat->lampirans as $lampiran)
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