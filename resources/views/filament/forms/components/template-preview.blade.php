<div class="mt-4 rounded-xl border border-gray-200 bg-gray-100 p-6 flex justify-center overflow-x-auto dark:border-gray-800 dark:bg-gray-900/50">
    <div class="relative w-full max-w-3xl min-h-[800px] bg-white text-black p-10 shadow-lg dark:shadow-none ring-1 ring-gray-950/5">
        <div class="absolute top-0 right-0 rounded-bl-lg bg-primary-600 px-3 py-1 text-xs font-bold text-white shadow-sm">
            LIVE PREVIEW
        </div>
         
        <div class="prose max-w-none prose-sm sm:prose-base dark:prose-invert">
            <!-- Ensure text remains readable inside the white paper container -->
            <style>
                .preview-paper-content { color: #000000; }
                .preview-paper-content p, .preview-paper-content span, .preview-paper-content td, .preview-paper-content th { color: #000000 !important; }
            </style>
            <div class="preview-paper-content">
                {!! $html !!}
            </div>
        </div>
    </div>
</div>
