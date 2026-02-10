<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .meta { margin-bottom: 16px; }
        .meta td { padding: 4px 8px; vertical-align: top; }
        .isi { margin-top: 20px; white-space: pre-line; }
        .footer { margin-top: 40px; }
        .arsip {
            border: 1px solid #000;
            padding: 6px;
            display: inline-block;
            font-size: 10px;
        }
    </style>
</head>
<body>

<div class="header">
    <strong>{{ $surat->unitPengirim->nama_unit }}</strong><br>
    <span>SURAT</span>
</div>

@if($isArsip)
    <div class="arsip">
        SURAT ARSIP
    </div>
@endif

<table class="meta">
    <tr>
        <td>Nomor Surat</td>
        <td>: {{ $surat->nomor_surat }}</td>
    </tr>
    <tr>
        <td>Nomor Agenda</td>
        <td>: {{ $surat->nomor_agenda }}</td>
    </tr>
    <tr>
        <td>Perihal</td>
        <td>: {{ $surat->perihal }}</td>
    </tr>
    <tr>
        <td>Tanggal</td>
        <td>: {{  \Carbon\Carbon::parse($surat->tanggal_kirim)->format('d M Y') }}</td>
    </tr>
</table>

<div class="isi">
    {!! nl2br(e($surat->isi_surat)) !!}
</div>

<div class="footer">
    <div>
        Dibuat oleh:<br>
        {{ $surat->pembuat->nama_lengkap ?? '-' }}<br>
        {{ $surat->unitPengirim->nama_unit }}
    </div>
</div>

</body>
</html>
