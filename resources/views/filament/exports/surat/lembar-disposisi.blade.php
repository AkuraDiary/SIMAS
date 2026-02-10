<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lembar Disposisi</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
        }
        th { background: #f0f0f0; }
        .header { margin-bottom: 16px; }
    </style>
</head>
<body>

<div class="header">
    <strong>LEMBAR DISPOSISI</strong><br>
    Nomor Surat: {{ $surat->nomor_surat }}<br>
    Perihal: {{ $surat->perihal }}
</div>

<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Dari</th>
            <th>Kepada</th>
            <th>Instruksi</th>
            <th>Sifat</th>
            <th>Catatan</th>
            <th>Tanggal</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($disposisis as $index => $disposisi)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $disposisi->unitPembuat->nama_unit }}</td>
                <td>{{ $disposisi->unitTujuan->nama_unit }}</td>
                <td>{{ $disposisi->jenis_instruksi }}</td>
                <td>{{ $disposisi->sifat }}</td>
                <td>{{ $disposisi->catatan }}</td>
                <td>{{ \Carbon\Carbon::parse($disposisi->tanggal_disposisi)->format('d M Y') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
