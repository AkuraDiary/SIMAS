<div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-5">
        Aksi Cepat
    </h3>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-2">
        <!-- New User Button -->
        <a href="{{ \App\Filament\Resources\UserPegawais\UserPegawaiResource::getUrl('create') }}" class="group flex flex-col items-center justify-center p-6 bg-white rounded-lg border border-gray-200 transition-colors hover:bg-gray-50 hover:border-primary-500 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700 dark:hover:border-primary-400">
            <x-heroicon-o-user-plus class="w-7 h-7 text-primary-500 mb-3 group-hover:text-primary-600 dark:text-primary-400 dark:group-hover:text-primary-300" />
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Buat Akun Pegawai</span>
        </a>

        <!-- Import Staff Button -->
        <a href="{{ \App\Filament\Resources\UserPegawais\UserPegawaiResource::getUrl() }}" class="group flex flex-col items-center justify-center p-6 bg-white rounded-lg border border-gray-200 transition-colors hover:bg-gray-50 hover:border-primary-500 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700 dark:hover:border-primary-400">
            <x-heroicon-o-arrow-up-tray class="w-7 h-7 text-primary-500 mb-3 group-hover:text-primary-600 dark:text-primary-400 dark:group-hover:text-primary-300" />
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Import Staff</span>
        </a>

        <!-- Kelola Organisasi Button -->
        <a href="{{ \App\Filament\Pages\Admin\ManageOrganisasi::getUrl() }}" class="group flex flex-col items-center justify-center p-6 bg-white rounded-lg border border-gray-200 transition-colors hover:bg-gray-50 hover:border-primary-500 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700 dark:hover:border-primary-400">
            <x-heroicon-o-building-office class="w-7 h-7 text-primary-500 mb-3 group-hover:text-primary-600 dark:text-primary-400 dark:group-hover:text-primary-300" />
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Kelola Organisasi</span>
        </a>

        <!-- Template Button -->
        <a href="{{ \App\Filament\Resources\TemplateResource\TemplateResource::getUrl('create') }}" class="group flex flex-col items-center justify-center p-6 bg-white rounded-lg border border-gray-200 transition-colors hover:bg-gray-50 hover:border-primary-500 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700 dark:hover:border-primary-400">
            <x-heroicon-o-document-plus class="w-7 h-7 text-primary-500 mb-3 group-hover:text-primary-600 dark:text-primary-400 dark:group-hover:text-primary-300" />
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Buat Template</span>
        </a>
    </div>
</div>
