<x-filament-panels::page>

    <x-filament::section>
        <x-slot name="heading">
            {{ $suratUnit->surat->perihal }}
        </x-slot>

        <p><strong>Nomor Surat:</strong> {{ $suratUnit->surat->nomor_surat }}</p>
        <p><strong>Pengirim:</strong> {{ $suratUnit->surat->unitPengirim->nama_unit }}</p>
        <p><strong>Diterima:</strong> {{ $suratUnit->tanggal_terima }}</p>
        <p>
            <strong>Jenis Surat:</strong>
            {{ $jenisTujuanLabel }}
        </p>

    </x-filament::section>

    <x-filament::section>
        {!! nl2br(e($suratUnit->surat->isi_surat)) !!}
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Disposisi</x-slot>
        <p class="text-gray-500 italic">
            Belum ada disposisi.
        </p>
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