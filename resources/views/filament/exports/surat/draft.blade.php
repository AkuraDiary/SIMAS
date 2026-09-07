<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Draf Pengajuan Surat</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .meta {
            margin-bottom: 20px;
            width: 100%;
        }

        .meta td {
            padding: 4px 8px;
            vertical-align: top;
        }

        .isi {
            margin-top: 20px;
        }

        .draft-watermark {
            position: absolute;
            top: 30%;
            left: 20%;
            font-size: 80px;
            color: rgba(200, 200, 200, 0.3);
            transform: rotate(-45deg);
            z-index: -1;
        }
    </style>
</head>
<body>


        {!! $renderedHtml !!}

</body>
</html>
