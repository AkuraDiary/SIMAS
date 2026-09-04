<x-filament-panels::page>
    <div class="space-y-8">
        {{-- Unit Information Banner --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-md bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700 ring-1 ring-inset ring-primary-700/10 dark:bg-primary-950/50 dark:text-primary-400">
                            Unit Kerja
                        </span>
                        <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                            {{ $unitNama ?? 'Unit Kerja' }}
                        </h2>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Atur visibilitas surat masuk dan delegasi wewenang disposisi untuk seluruh pegawai di bawah naungan unit Anda.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-700/10 dark:bg-emerald-950/50 dark:text-emerald-400">
                        <x-heroicon-s-shield-check class="h-4 w-4" />
                        Otoritas Kepala Unit
                    </span>
                </div>
            </div>
        </div>

        {{-- Section 1: Kebijakan Surat Masuk Unit --}}
        <div class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 pb-4 dark:border-gray-800">
                <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Kebijakan Visibilitas Surat Masuk Unit
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Pilih bagaimana surat masuk yang ditujukan ke unit ini dapat diakses secara umum oleh pegawai di unit Anda.
                </p>
            </div>

            <div class="mt-6 space-y-6">
                {{-- Opsi 1: Terbuka --}}
                <label class="my-4 relative flex cursor-pointer items-start rounded-xl border p-4 transition hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ $kebijakanSuratMasuk === 'TERBUKA' ? 'border-primary-600 ring-1 ring-primary-600 bg-primary-50/20 dark:bg-primary-950/10' : 'border-gray-200 dark:border-gray-800' }}">
                    <div class="flex h-5 items-center">
                        <input style="visibility:hidden;  height: 0; width: 0;" type="radio" wire:model.live="kebijakanSuratMasuk" value="TERBUKA" class="h-4 w-4 border-gray-300 text-primary-600 focus:ring-primary-600">
                    </div>
                    <div class="ml-3 text-sm">
                        <span class="font-semibold text-gray-900 dark:text-white">Terbuka untuk Semua Pegawai (Bawaan)</span>
                        <p class="mt-1 text-gray-500 dark:text-gray-400">
                            Seluruh staf di unit ini dapat melihat semua surat masuk unit secara bebas di daftar Surat Masuk mereka.
                        </p>
                    </div>
                </label>

                {{-- Opsi 2: Terbatas Disposisi --}}
                <label class="relative flex cursor-pointer items-start rounded-xl border p-4 transition hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ $kebijakanSuratMasuk === 'TERBATAS_DISPOSISI' ? 'border-primary-600 ring-1 ring-primary-600 bg-primary-50/20 dark:bg-primary-950/10' : 'border-gray-200 dark:border-gray-800' }}">
                    <div class="flex h-5 items-center">
                        <input style="visibility:hidden;  height: 0; width: 0;" type="radio" wire:model.live="kebijakanSuratMasuk" value="TERBATAS_DISPOSISI" class="none h-4 w-4 border-gray-300 text-primary-600 focus:ring-primary-600">
                    </div>
                    <div class="ml-3 text-sm">
                        <span class="font-semibold text-gray-900 dark:text-white">Terbatas (Disposisi Saja)</span>
                        <p class="mt-1 text-gray-500 dark:text-gray-400">
                            Surat masuk unit hanya dapat dilihat oleh <strong>Kepala Unit</strong> dan staf yang diberi akses khusus (misal TU). Pegawai biasa hanya dapat melihat surat yang telah <strong>didisposisikan</strong> kepada unit/dirinya atau surat yang mereka buat.
                        </p>
                    </div>
                </label>

                {{-- Opsi 3: Berdasarkan Level Jabatan --}}
                <label class="relative flex cursor-pointer items-start rounded-xl border p-4 transition hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ $kebijakanSuratMasuk === 'LEVEL_JABATAN' ? 'border-primary-600 ring-1 ring-primary-600 bg-primary-50/20 dark:bg-primary-950/10' : 'border-gray-200 dark:border-gray-800' }}">
                    <div class="flex h-5 items-center">
                        <input style="visibility:hidden;  height: 0; width: 0;" type="radio" wire:model.live="kebijakanSuratMasuk" value="LEVEL_JABATAN" class="h-4 w-4 border-gray-300 text-primary-600 focus:ring-primary-600">
                    </div>
                    <div class="ml-3 text-sm w-full">
                        <span class="font-semibold text-gray-900 dark:text-white">Berdasarkan Tingkat Jabatan</span>
                        <p class="mt-1 text-gray-500 dark:text-gray-400">
                            Hanya pejabat dengan level jabatan tertentu ke atas yang dapat melihat seluruh surat masuk unit. Staf di bawah level tersebut hanya melihat surat yang didisposisikan.
                        </p>

                        @if ($kebijakanSuratMasuk === 'LEVEL_JABATAN')
                        <div class="mt-4 max-w-xs rounded-lg bg-gray-50 p-3 dark:bg-gray-800/80">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                                Batas Maksimal Level Jabatan
                            </label>
                            <div class="mt-1 flex items-center gap-2">
                                <x-filament::input.wrapper class="w-24">
                                    <x-filament::input
                                        type="number"
                                        min="1"
                                        max="10"
                                        wire:model="minLevelJabatan" />
                                </x-filament::input.wrapper>

                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    (Level 1 = Paling Tinggi, 2 = Wakil/Sekretaris, dst.)
                                </span>
                            </div>
                        </div>
                        @endif
                    </div>
                </label>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="button" wire:click="saveUnitPolicy" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">
                    <x-heroicon-m-check class="h-4 w-4" />
                    Simpan Kebijakan Unit
                </button>
            </div>
        </div>

        {{-- Section 2: Matriks Hak Akses Pegawai di Bawahnya --}}
        <div class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 pb-4 dark:border-gray-800">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                            Hak Akses & Delegasi Pegawai di Bawahnya
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Atur izin khusus per-staf (misal: memberikan akses penuh kepada <strong>Sekretaris / Staf TU</strong> untuk memilah surat masuk atau membuat disposisi).
                        </p>
                    </div>
                    <!-- <button type="button" wire:click="saveStaffPermissions" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">
                        <x-heroicon-m-check class="h-4 w-4" />
                        Simpan Akses Pegawai
                    </button> -->
                </div>
            </div>

            <div class="mt-6 overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-4 py-3">Pegawai & NIP</th>
                            <th scope="col" class="px-4 py-3">Jabatan & Level</th>
                            <th scope="col" class="px-4 py-3">Akses Surat Masuk</th>
                            <th scope="col" class="px-4 py-3 text-center">Delegasi Disposisi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse ($staffList as $staff)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                            {{-- Nama & NIP --}}
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                <div class="flex items-center gap-2">
                                    <div>
                                        <span>{{ $staff['nama'] }}</span>
                                        <div class="text-xs font-normal text-gray-500 dark:text-gray-400">
                                            NIP: {{ $staff['nip'] }}
                                        </div>
                                    </div>
                                    @if ($staff['is_kepala'])
                                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-950/50 dark:text-amber-400">
                                        Kepala Unit
                                    </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Jabatan & Level --}}
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ $staff['nama_jabatan'] }}</span>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Level Jabatan: <span class="font-semibold text-primary-600 dark:text-primary-400">{{ $staff['level_jabatan'] }}</span>
                                </div>
                            </td>

                            {{-- Akses Surat Masuk --}}
                            <td class="px-4 py-3">
                                @if ($staff['is_kepala'])
                                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                    ✓ Akses Penuh (Pimpinan)
                                </span>
                                @else
                                <x-filament::input.wrapper class="max-w-xs">
                                    <x-filament::input.select wire:model="staffPermissions.{{ $staff['id'] }}.akses_surat_masuk">
                                        <option value="DEFAULT">Ikuti Kebijakan Unit</option>
                                        <option value="SEMUA">Akses Penuh (Semua Surat Masuk)</option>
                                        <option value="HANYA_DISPOSISI">Hanya Surat Terdisposisi</option>
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                                @endif
                            </td>

                            {{-- Delegasi Disposisi --}}
                            <td class="px-4 py-3 text-center">
                                @if ($staff['is_kepala'])
                                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                    Otomatis Berwenang
                                </span>
                                @else
                                <label class="inline-flex cursor-pointer items-center gap-2">
                                    <x-filament::input.checkbox wire:model="staffPermissions.{{ $staff['id'] }}.can_disposisi" />
                                    <span class="text-xs text-gray-600 dark:text-gray-300">Izinkan</span>
                                </label>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                Belum ada pegawai yang terdaftar pada unit ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="button" wire:click="saveStaffPermissions" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">
                    <x-heroicon-m-check class="h-4 w-4" />
                    Simpan Hak Akses Pegawai
                </button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
