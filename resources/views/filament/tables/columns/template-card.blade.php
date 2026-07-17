<div class="flex flex-col h-full bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 transition-all hover:shadow-md">
    <div class="flex justify-between items-start mb-4">
        @if ($getRecord()->is_active)
            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-md bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                ACTIVE
            </span>
        @else
            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-md bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                DRAFT
            </span>
        @endif
        
        <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 dark:bg-gray-700">
            @if ($getRecord()->render_engine === 'DOCX')
                <x-heroicon-o-document class="w-6 h-6 text-gray-400" />
            @else
                <x-heroicon-o-document-text class="w-6 h-6 text-gray-400" />
            @endif
        </div>
    </div>
    
    <div class="flex-1">
        <h3 class="text-base font-bold text-gray-900 dark:text-white leading-tight mb-2">
            {{ $getRecord()->nama_template }}
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-3">
            {{ $getRecord()->deskripsi ?: 'Tidak ada deskripsi.' }}
        </p>
    </div>
    
    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
        <span class="text-[10px] text-gray-400 font-medium tracking-wide">
            Last edit: {{ $getRecord()->updated_at->format('M d, Y') }}
        </span>
    </div>
</div>
