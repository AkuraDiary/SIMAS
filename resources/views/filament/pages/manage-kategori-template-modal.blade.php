<div class="space-y-6" x-data>
    {{-- Form Tambah Kategori Baru --}}
    <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-800/40">
        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
            Tambah Kategori Template Baru
        </label>
        <div class="mt-2 flex items-center gap-2">
            <x-filament::input.wrapper class="w-full">
                <x-filament::input
                    type="text"
                    wire:model="newKategoriNama"
                    wire:keydown.enter="addKategori"
                    placeholder="Contoh: Surat Keputusan, Surat Tugas, Surat Keterangan..." />
            </x-filament::input.wrapper>
            <x-filament::button
                type="button"
                wire:click="addKategori"
                icon="heroicon-m-plus"
                class="shrink-0">
                Tambah
            </x-filament::button>
        </div>
    </div>

    {{-- Daftar Kategori Template --}}
    @php
    $currentEditingId = $editingKategoriId ?? null;
    $list = $kategoriList ?? collect();
    @endphp
    <div>
        <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
            Daftar Kategori Template Surat
        </h4>

        <div class="mt-3 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-gray-800">
                <thead class="bg-gray-50 text-xs font-medium uppercase text-gray-500 dark:bg-gray-800/50 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-2.5">Nama Kategori</th>
                        <th scope="col" class="px-4 py-2.5 text-center">Jumlah Template</th>
                        <th scope="col" class="px-4 py-2.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-900">
                    @forelse ($list as $kategori)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                        {{-- Nama Kategori --}}
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                            @if ($currentEditingId === $kategori->id)
                            <div class="flex items-center gap-2">
                                <x-filament::input.wrapper class="w-full">
                                    <x-filament::input
                                        type="text"
                                        wire:model="editingKategoriNama"
                                        wire:keydown.enter="saveEditKategori" />
                                </x-filament::input.wrapper>
                                <x-filament::button
                                    type="button"
                                    wire:click="saveEditKategori"
                                    size="xs">
                                    Simpan
                                </x-filament::button>
                                <x-filament::button
                                    type="button"
                                    wire:click="cancelEditKategori"
                                    color="gray"
                                    size="xs">
                                    Batal
                                </x-filament::button>
                            </div>
                            @else
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $kategori->nama_kategori }}
                            </span>
                            @endif
                        </td>

                        {{-- Jumlah Template Terkait --}}
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                {{ $kategori->templates_count }} template
                            </span>
                        </td>

                        {{-- Aksi --}}
                        <td class="px-4 py-3 text-right">
                            @if ($currentEditingId !== $kategori->id)
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    wire:click="startEditKategori({{ $kategori->id }}, '{{ addslashes($kategori->nama_kategori) }}')"
                                    class="text-xs font-medium text-primary-600 transition hover:text-primary-500 dark:text-primary-400">
                                    Ubah
                                </button>
                                <span class="text-gray-300 dark:text-gray-700">|</span>
                                <button
                                    type="button"
                                    @disabled($kategori->templates_count > 0)
                                    wire:click="deleteKategori({{ $kategori->id }})"
                                    wire:confirm="Yakin ingin menghapus kategori '{{ $kategori->nama_kategori }}'?"
                                    class="text-xs font-medium text-danger-600 transition hover:text-danger-500 disabled:cursor-not-allowed disabled:text-gray-400 dark:text-danger-400 disabled:dark:text-gray-600">
                                    Hapus
                                </button>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-xs text-gray-500 italic">
                            Belum ada kategori template yang terdaftar.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
