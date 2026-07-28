<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <!-- Total Pengguna Card -->
    <div class="flex items-center justify-between rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div>
            <span class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Total Pengguna
            </span>
            <span class="text-4xl font-extrabold leading-none text-gray-900 dark:text-white">
                {{ $totalPengguna }}
            </span>
        </div>
        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-500/10">
            <x-heroicon-o-users class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
        </div>
    </div>

    <!-- Template Aktif Card -->
    <div class="flex items-center justify-between rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex h-full flex-col justify-between">
            <div>
                <span class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Template Aktif
                </span>
                <span class="text-4xl font-extrabold leading-none text-gray-900 dark:text-white">
                    {{ $templateAktif }}
                </span>
            </div>
        </div>
        <div class="flex h-12 w-12 items-center justify-center self-start rounded-lg bg-purple-50 dark:bg-purple-500/10">
            <x-heroicon-o-document-text class="h-6 w-6 text-purple-600 dark:text-purple-400" />
        </div>
    </div>

    <!-- Unit Organisasi Card -->
    <div class="flex items-center justify-between rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex h-full flex-col justify-between">
            <div>
                <span class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Unit Organisasi
                </span>
                <span class="text-4xl font-extrabold leading-none text-gray-900 dark:text-white">
                    {{ $unitOrganisasi }}
                </span>
            </div>
        </div>
        <div class="flex h-12 w-12 items-center justify-center self-start rounded-lg bg-green-50 dark:bg-green-500/10">
            <x-heroicon-o-squares-2x2 class="h-6 w-6 text-green-600 dark:text-green-400" />
        </div>
    </div>
</div>
