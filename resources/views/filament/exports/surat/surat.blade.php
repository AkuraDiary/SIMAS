<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Surat</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .meta {
            margin-bottom: 16px;
        }

        .meta td {
            padding: 4px 8px;
            vertical-align: top;
        }

        .isi {
            margin-top: 20px;
            white-space: pre-line;
        }

        .footer {
            margin-top: 40px;
        }

        .arsip {
            border: 1px solid #000;
            padding: 6px;
            display: inline-block;
            font-size: 10px;
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

    @if($isArsip)
    <div class="arsip">
        SURAT ARSIP
    </div>
    @endif

    <div class="surat-content">
        {!! $renderedHtml !!}
    </div>


</body>

</html>
