<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Surat</title>
    @php
        $template = $surat?->template;
        $kopUri = $template?->getMediaDataUri('kop_surat');
        $footerUri = $template?->getMediaDataUri('footer_surat');
        $bgUri = $template?->getMediaDataUri('background_surat');
        $kopPosisi = $template?->kop_posisi ?? 'AWAL_SAJA';
        $footerPosisi = $template?->footer_posisi ?? 'AKHIR_SAJA';

        $topMargin = ($kopUri && $kopPosisi === 'SEMUA_HALAMAN') ? '130px' : '40px';
        $bottomMargin = ($footerUri && $footerPosisi === 'SEMUA_HALAMAN') ? '90px' : '40px';
    @endphp
    <style>
        @page {
            margin: {{ $topMargin }} 45px {{ $bottomMargin }} 45px;
        }

        body {
            font-family: sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }

        .page-background {
            position: fixed;
            top: -{{ $topMargin }};
            left: -45px;
            right: -45px;
            bottom: -{{ $bottomMargin }};
            z-index: -10;
        }

        .page-background img {
            width: 100%;
            height: 100%;
        }

        .kop-fixed {
            position: fixed;
            top: -{{ $topMargin }};
            left: 0;
            right: 0;
            padding-top: 15px;
            z-index: 5;
        }

        .kop-inline {
            margin-bottom: 16px;
        }

        .footer-fixed {
            position: fixed;
            bottom: -{{ $bottomMargin }};
            left: 0;
            right: 0;
            padding-bottom: 15px;
            z-index: 5;
        }

        .footer-inline {
            margin-top: 28px;
        }

        .page-break {
            page-break-after: always;
        }

        .arsip {
            border: 1px solid #000;
            padding: 6px;
            display: inline-block;
            font-size: 10px;
            margin-bottom: 10px;
        }

        .signature-resize-handle {
            display: none !important;
        }

        .signature-ghost {
            display: none !important;
        }

        .draggable-signature {
            display: inline-block;
            border: none !important;
            outline: none !important;
        }

        .surat-content {
            position: relative;
        }

        .docx-preview-wrapper {
            position: relative;
        }
    </style>
</head>

<body>
    @if($bgUri)
    <div class="page-background">
        <img src="{{ $bgUri }}" alt="Background">
    </div>
    @endif

    @if($kopUri && $kopPosisi === 'SEMUA_HALAMAN')
    <div class="kop-fixed">
        <img src="{{ $kopUri }}" style="width: 100%; height: auto; display: block;" alt="Kop Surat">
    </div>
    @endif

    @if($footerUri && $footerPosisi === 'SEMUA_HALAMAN')
    <div class="footer-fixed">
        <img src="{{ $footerUri }}" style="width: 100%; height: auto; display: block;" alt="Footer Surat">
    </div>
    @endif

    @if($isArsip)
    <div class="arsip">
        SURAT ARSIP
    </div>
    @endif

    @if($kopUri && $kopPosisi === 'AWAL_SAJA')
    <div class="kop-inline">
        <img src="{{ $kopUri }}" style="width: 100%; height: auto; display: block;" alt="Kop Surat">
    </div>
    @endif

    <div class="surat-content">
        {!! $renderedHtml !!}
    </div>

    @if($footerUri && $footerPosisi === 'AKHIR_SAJA')
    <div class="footer-inline">
        <img src="{{ $footerUri }}" style="width: 100%; height: auto; display: block;" alt="Footer Surat">
    </div>
    @endif
</body>

</html>
