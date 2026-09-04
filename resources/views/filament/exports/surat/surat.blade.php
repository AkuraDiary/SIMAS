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
    </style>
</head>

<body>



    @if($isArsip)
    <div class="arsip">
        SURAT ARSIP
    </div>
    @endif


    <!-- <div class="mt-4 rounded-xl border border-gray-200 bg-gray-100 p-6 flex justify-center overflow-x-auto dark:border-gray-800 dark:bg-gray-900/50">
        <div class="relative w-full max-w-3xl min-h-[800px] bg-white text-black p-10 shadow-lg dark:shadow-none ring-1 ring-gray-950/5"> -->
    <div class="prose max-w-none prose-sm sm:prose-base dark:prose-invert">
        {!! $renderedHtml !!}
    </div>
    <!-- </div>
    </div> -->


</body>

</html>
