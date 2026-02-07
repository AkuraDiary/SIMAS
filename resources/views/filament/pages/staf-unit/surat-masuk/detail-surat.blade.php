<x-filament-panels::page>

    <x-filament::section>
        <x-slot name="heading">
            {{ $suratUnit->surat->perihal }}
        </x-slot>

        <p><strong>Nomor Surat:</strong> {{ $suratUnit->surat->nomor_surat }}</p>
        <p><strong>Pengirim:</strong> {{ $suratUnit->surat->unitPengirim->nama_unit }}</p>
        <p><strong>Diterima:</strong> {{ $suratUnit->tanggal_terima }}</p>
        <p><strong>Jenis Tujuan:</strong> {{ $suratUnit->jenis_tujuan }}</p>
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


</x-filament-panels::page>