<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lembar Kendali & Persetujuan Naskah</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #1a1a1a;
            line-height: 1.4;
            margin: 0;
            padding: 10px;
        }
        .header-title {
            text-align: center;
            border-bottom: 2px solid #111827;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .header-title h2 {
            margin: 0 0 4px 0;
            font-size: 13pt;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .header-title p {
            margin: 0;
            font-size: 9pt;
            color: #4b5563;
        }
        .section-heading {
            font-size: 9.5pt;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #f3f4f6;
            border-left: 3px solid #4f46e5;
            padding: 4px 8px;
            margin-top: 14px;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }
        table.meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.meta-table td {
            padding: 4px 6px;
            vertical-align: top;
            font-size: 9pt;
            border-bottom: 1px solid #e5e7eb;
        }
        table.meta-table td.label {
            width: 140px;
            font-weight: bold;
            color: #374151;
        }
        table.grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 12px;
        }
        table.grid-table th {
            background-color: #f9fafb;
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            font-size: 8.5pt;
            font-weight: bold;
            text-align: left;
            text-transform: uppercase;
            color: #374151;
        }
        table.grid-table td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
            font-size: 8.5pt;
            vertical-align: top;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 7.5pt;
            font-weight: bold;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-info { background: #e0e7ff; color: #3730a3; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-secondary { background: #f3f4f6; color: #4b5563; }

        .signature-grid {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
        }
        .signature-box {
            border: 1px solid #d1d5db;
            background: #fafafa;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
            vertical-align: top;
            width: 33.33%;
        }
        .signature-box img {
            max-width: 85px;
            max-height: 85px;
            margin: 4px auto;
            display: block;
        }
        .sig-name {
            font-weight: bold;
            font-size: 9pt;
            color: #111827;
            margin-top: 4px;
        }
        .sig-title {
            font-size: 8pt;
            color: #4b5563;
        }
        .sig-date {
            font-size: 7.5pt;
            color: #6b7280;
            margin-top: 3px;
        }
        .footer-note {
            margin-top: 18px;
            padding: 8px 10px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 4px;
            font-size: 7.5pt;
            color: #64748b;
            line-height: 1.3;
        }
    </style>
</head>
<body>

    <div class="header-title">
        <h2>Lembar Kendali Persuratan & Pengesahan Digital</h2>
        <p>Sistem Informasi Manajemen Arsip dan Surat (SIMAS) Universitas</p>
    </div>

    {{-- BAGIAN 1: METADATA & DATA POKOK NASKAH --}}
    <div class="section-heading">I. Data Pokok Naskah Dinas</div>
    <table class="meta-table">
        <tr>
            <td class="label">Nomor Surat</td>
            <td>: <strong>{{ $surat->nomor_surat ?? '-' }}</strong></td>
            <td class="label">Status Naskah</td>
            <td>: <span class="badge badge-success">{{ $surat->status_surat }}</span></td>
        </tr>
        <tr>
            <td class="label">Perihal</td>
            <td colspan="3">: {{ $surat->perihal }}</td>
        </tr>
        <tr>
            <td class="label">Tipe / Jenis Surat</td>
            <td>: {{ $surat->tipe_surat }}</td>
            <td class="label">Tanggal Registrasi</td>
            <td>: {{ $surat->created_at ? $surat->created_at->format('d/m/Y H:i') : '-' }} WIB</td>
        </tr>
        <tr>
            <td class="label">Unit Pembuat Asal</td>
            <td>: {{ $surat->unitPengirim?->nama_unit ?? ($surat->userPegawaiJabatan?->unitKerja?->nama_unit ?? 'Unit Internal') }}</td>
            <td class="label"> Pembuat</td>
            <td>: {{ $surat->userPegawaiJabatan->pegawai?->nama_lengkap ?? ($surat->pengirim_nama ?? '-') }}</td>
        </tr>
        <tr>
            <td class="label">Unit Tujuan / Sasaran</td>
            <td colspan="3">:
                @if($surat->unitTujuan->isNotEmpty())
                    {{ $surat->unitTujuan->pluck('nama_unit')->join(', ') }}
                @else
                    {{ $surat->pengirim_nama ?? 'Pihak Terkait' }}
                @endif
            </td>
        </tr>
        @if($surat->terbitan_for_surat_id && $surat->terbitanForSurat)
        <tr>
            <td class="label">Merujuk ke Pengajuan</td>
            <td colspan="3">: {{ $surat->terbitanForSurat->nomor_surat ?? $surat->terbitanForSurat->perihal }} (ID: #{{ $surat->terbitanForSurat->id }})</td>
        </tr>
        @endif
        @php
            $lampiranList = $lampirans->pluck('file_name')->toArray();
        @endphp
        <tr>
            <td class="label">Berkas Lampiran Resmi</td>
            <td colspan="3">:
                @if(!empty($lampiranList))
                    {{ implode(', ', $lampiranList) }} ({{ count($lampiranList) }} berkas)
                @else
                    <em>Tidak ada lampiran berkas</em>
                @endif
            </td>
        </tr>
    </table>

    {{-- BAGIAN 2: RIWAYAT ALUR PERSETUJUAN & DISPOSISI --}}
    <div class="section-heading">II. Linimasa Riwayat Verifikasi & Persetujuan</div>
    <table class="grid-table">
        <thead>
            <tr>
                <th style="width: 25px; text-align: center;">No</th>
                <th style="width: 140px;">Unit & Aktor</th>
                <th style="width: 90px; text-align: center;">Aksi / Status</th>
                <th style="width: 100px; text-align: center;">Waktu Eksekusi</th>
                <th>Catatan / Disposisi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($riwayats as $idx => $rw)
                @php
                    $statusClass = match($rw->status) {
                        'DISETUJUI', 'SELESAI' => 'badge-success',
                        'MENUNGGU'             => 'badge-warning',
                        'REVISI', 'DITOLAK'    => 'badge-danger',
                        'DITERUSKAN'           => 'badge-info',
                        default                => 'badge-secondary',
                    };
                @endphp
                <tr>
                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                    <td>
                        <strong>{{ $rw->unitTujuan?->nama_unit ?? ($rw->unitAsal?->nama_unit ?? 'Unit Sistem') }}</strong>
                        @if($rw->aktor)
                            <br><span style="color: #6b7280; font-size: 8pt;">Oleh: {{ $rw->aktor->nama_lengkap ?? $rw->aktor->name }}</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        <span class="badge {{ $statusClass }}">{{ $rw->status }}</span>
                    </td>
                    <td style="text-align: center; font-size: 8pt;">
                        {{ $rw->actioned_at ? \Carbon\Carbon::parse($rw->actioned_at)->format('d/m/Y H:i') : '-' }}
                    </td>
                    <td style="color: #374151;">
                        {{ $rw->catatan ?: '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #9ca3af; font-style: italic;">
                        Belum ada riwayat persetujuan tercatat.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- BAGIAN 3: PENGESAHAN DIGITAL & TANDA TANGAN / QR CODE --}}
    <div class="section-heading">III. Lembar Pengesahan & Tanda Tangan Elektronik</div>
    @if(!empty($ttdsWithImages) && count($ttdsWithImages) > 0)
        <table class="signature-grid">
            <tr>
                @foreach($ttdsWithImages as $item)
                    @php
                        $ttdModel = $item['model'];
                        $imgSrc = $item['image_data'];
                    @endphp
                    <td class="signature-box">
                        <div class="sig-title">{{ $ttdModel->jabatan_saat_ttd ?? 'Pejabat Penandatangan' }}</div>
                        <div style="font-size: 7.5pt; color: #4f46e5; font-weight: bold; margin-bottom: 4px;">{{ $ttdModel->unit_saat_ttd ?? '' }}</div>

                        @if($imgSrc)
                            <img src="{{ $imgSrc }}" alt="Tanda Tangan / QR" />
                        @else
                            <div style="height: 60px; border: 1px dashed #d1d5db; margin: 4px auto; line-height: 60px; color: #9ca3af; font-size: 7.5pt;">
                                [Disahkan Elektronik]
                            </div>
                        @endif

                        <div class="sig-name">{{ $ttdModel->user?->nama_lengkap ?? ($ttdModel->user?->name ?? 'Pejabat Berwenang') }}</div>
                        @if($ttdModel->user?->pegawai?->nip)
                            <div style="font-size: 7.5pt; color: #6b7280;">NIP. {{ $ttdModel->user->pegawai->nip }}</div>
                        @endif
                        <div class="sig-date">
                            Disahkan: {{ $ttdModel->signed_at ? \Carbon\Carbon::parse($ttdModel->signed_at)->format('d/m/Y H:i') : '-' }} WIB
                        </div>
                    </td>
                @endforeach
            </tr>
        </table>
    @else
        <div style="padding: 12px; background: #f9fafb; border: 1px dashed #d1d5db; text-align: center; color: #6b7280; font-size: 8.5pt; border-radius: 4px;">
            Naskah ini disahkan secara langsung oleh sistem tanpa penanda tangan elektronik individual.
        </div>
    @endif

    <div class="footer-note">
        Dokumen ini dihasilkan secara otomatis oleh Sistem Informasi Manajemen Arsip dan Surat (SIMAS).
    </div>

</body>
</html>
