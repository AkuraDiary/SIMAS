    <div style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px;">
        <strong style="font-size: 16px;">DETAIL & METADATA PENGAJUAN SURAT</strong><br>
    </div>

    <table style="margin-bottom: 20px; width: 100%;" cellspacing="0">
        <tr>
            <td style="width: 150px; font-weight: bold; padding: 8px 8px; vertical-align: top; border-bottom: 1px solid #ddd;">Nama Lengkap</td>
            <td style="padding: 8px 8px; vertical-align: top; border-bottom: 1px solid #ddd;">: {{ $state['pengirim_nama'] ?? '-' }}</td>
        </tr>
        @if(($state['tipe_pengirim'] ?? '') === 'mahasiswa')
        <tr>
            <td style="width: 150px; font-weight: bold; padding: 8px 8px; vertical-align: top; border-bottom: 1px solid #ddd;">NIM / NIDN</td>
            <td style="padding: 8px 8px; vertical-align: top; border-bottom: 1px solid #ddd;">: {{ $state['pengirim_nim'] ?? '-' }}</td>
        </tr>
        <tr>
            <td style="width: 150px; font-weight: bold; padding: 8px 8px; vertical-align: top; border-bottom: 1px solid #ddd;">Fakultas / Prodi</td>
            <td style="padding: 8px 8px; vertical-align: top; border-bottom: 1px solid #ddd;">: 
                @php
                    $fakultas = \App\Models\UnitKerja::find($state['pengirim_fakultas'] ?? null)?->nama_unit ?? '-';
                    $prodi = \App\Models\UnitKerja::find($state['pengirim_prodi'] ?? null)?->nama_unit ?? '-';
                    echo $fakultas . ' / ' . $prodi;
                @endphp
            </td>
        </tr>
        @else
        <tr>
            <td style="width: 150px; font-weight: bold; padding: 8px 8px; vertical-align: top; border-bottom: 1px solid #ddd;">Instansi / Asal</td>
            <td style="padding: 8px 8px; vertical-align: top; border-bottom: 1px solid #ddd;">: {{ $state['pengirim_instansi'] ?? '-' }}</td>
        </tr>
        @endif
        <tr>
            <td style="width: 150px; font-weight: bold; padding: 8px 8px; vertical-align: top; border-bottom: 1px solid #ddd;">Email</td>
            <td style="padding: 8px 8px; vertical-align: top; border-bottom: 1px solid #ddd;">: {{ $state['pengirim_email'] ?? '-' }}</td>
        </tr>
        <tr>
            <td style="width: 150px; font-weight: bold; padding: 8px 8px; vertical-align: top; border-bottom: 1px solid #ddd;">No. Telepon / WA</td>
            <td style="padding: 8px 8px; vertical-align: top; border-bottom: 1px solid #ddd;">: {{ $state['pengirim_telp'] ?? '-' }}</td>
        </tr>
        <tr>
            <td style="width: 150px; font-weight: bold; padding: 8px 8px; vertical-align: top; border-bottom: 1px solid #ddd;">Tujuan Surat</td>
            <td style="padding: 8px 8px; vertical-align: top; border-bottom: 1px solid #ddd;">: {{ $tujuan }}</td>
        </tr>
        <tr>
            <td style="width: 150px; font-weight: bold; padding: 8px 8px; vertical-align: top; border-bottom: 1px solid #ddd;">Waktu Pengajuan</td>
            <td style="padding: 8px 8px; vertical-align: top; border-bottom: 1px solid #ddd;">: {{ \Carbon\Carbon::now()->format('d M Y H:i:s') }}</td>
        </tr>
        
        @if(!empty($state['lampiran_names']))
        <tr>
            <td style="width: 150px; font-weight: bold; padding: 8px 8px; vertical-align: top; border-bottom: 1px solid #ddd;">Dokumen Lampiran</td>
            <td style="padding: 8px 8px; vertical-align: top; border-bottom: 1px solid #ddd;">: 
                <ul style="margin: 0; padding-left: 16px;">
                @foreach($state['lampiran_names'] as $name)
                    <li>{{ $name }}</li>
                @endforeach
                </ul>
            </td>
        </tr>
        @endif
    </table>
