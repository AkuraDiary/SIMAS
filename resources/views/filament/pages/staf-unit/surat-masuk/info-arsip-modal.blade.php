@if ($arsip)
    <div class="space-y-4 text-sm">
        <div class="flex items-center gap-3 rounded-lg border border-primary-200 bg-primary-50/50 p-3.5 dark:border-primary-900/40 dark:bg-primary-950/30">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-primary-600 dark:bg-primary-900/50 dark:text-primary-400">
                <x-heroicon-o-archive-box class="h-6 w-6" />
            </div>
            <div>
                <div class="text-xs font-medium text-primary-700 dark:text-primary-300">Status Arsip</div>
                <div class="font-semibold text-primary-900 dark:text-primary-100">Surat Tersimpan di Arsip Unit</div>
            </div>
        </div>

        <dl class="divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white p-4 dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex justify-between py-2.5">
                <dt class="font-medium text-gray-500 dark:text-gray-400">Kategori Arsip</dt>
                <dd class="font-semibold text-gray-900 dark:text-white">
                    <span class="inline-flex items-center rounded-md bg-info-50 px-2.5 py-1 text-xs font-medium text-info-700 ring-1 ring-inset ring-info-700/10 dark:bg-info-400/10 dark:text-info-400 dark:ring-info-400/30">
                        {{ $arsip->kategoriArsip?->nama ?? '-' }}
                    </span>
                </dd>
            </div>

            <div class="flex justify-between py-2.5">
                <dt class="font-medium text-gray-500 dark:text-gray-400">Tanggal Diarsipkan</dt>
                <dd class="font-medium text-gray-900 dark:text-white">
                    {{ $arsip->tanggal_arsip ? \Carbon\Carbon::parse($arsip->tanggal_arsip)->format('d F Y, H:i') : '-' }} WIB
                </dd>
            </div>

            <div class="py-2.5">
                <dt class="font-medium text-gray-500 dark:text-gray-400">Catatan Arsip</dt>
                <dd class="mt-1 text-gray-900 dark:text-gray-200">
                    {{ $arsip->catatan ?: '-' }}
                </dd>
            </div>
        </dl>
    </div>
@else
    <p class="text-sm text-gray-500">Data arsip tidak ditemukan.</p>
@endif
