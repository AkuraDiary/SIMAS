<div class="space-y-6" x-data>
    {{-- Form Tambah Kategori --}}
    <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-800/40">
        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
            Tambah Kategori Baru
        </label>
        <div class="mt-2 flex items-center gap-2">
            <x-filament::input.wrapper class="w-full">
                <x-filament::input
                    type="text"
                    wire:model="newKategoriNama"
                    wire:keydown.enter="addKategori"
                    placeholder="Contoh: Surat Keputusan, Akademik, Keuangan..."
                />
            </x-filament::input.wrapper>
            <x-filament::button
                type="button"
                wire:click="addKategori"
                icon="heroicon-m-plus"
                class="shrink-0"
            >
                Tambah
            </x-filament::button>
        </div>
    </div>

    {{-- Daftar Kategori Arsip --}}
    @php
        $currentEditingId = $editingKategoriId ?? null;
        $list = $kategoriList ?? collect();
    @endphp
    <div>
        <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
            Daftar Kategori Arsip Unit Anda
        </h4>

        <div class="mt-3 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-gray-800">
                <thead class="bg-gray-50 text-xs font-medium uppercase text-gray-500 dark:bg-gray-800/50 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-2.5">Nama Kategori</th>
                        <th scope="col" class="px-4 py-2.5 text-center">Jumlah Surat</th>
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
                                                wire:keydown.enter="saveEditKategori"
                                            />
                                        </x-filament::input.wrapper>
                                        <x-filament::button
                                            type="button"
                                            wire:click="saveEditKategori"
                                            size="xs"
                                        >
                                            Simpan
                                        </x-filament::button>
                                        <x-filament::button
                                            type="button"
                                            wire:click="cancelEditKategori"
                                            color="gray"
                                            size="xs"
                                        >
                                            Batal
                                        </x-filament::button>
                                    </div>
                                @else
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $kategori->nama }}
                                    </span>
                                @endif
                            </td>

                        {{-- Jumlah Surat Terarsip --}}
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                {{ $kategori->arsip_surats_count }} surat
                            </span>
                        </td>

                        {{-- Aksi --}}
                        <td class="px-4 py-3 text-right">
                            @if ($currentEditingId !== $kategori->id)
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    wire:click="startEditKategori({{ $kategori->id }}, '{{ addslashes($kategori->nama) }}')"
                                    class="text-xs font-medium text-primary-600 transition hover:text-primary-500 dark:text-primary-400">
                                    Ubah
                                </button>
                                <span class="text-gray-300 dark:text-gray-700">|</span>
                                @if ($kategori->arsip_surats_count === 0)
                                <button
                                    type="button"
                                    wire:click="deleteKategori({{ $kategori->id }})"
                                    wire:confirm="Yakin ingin menghapus kategori '{{ $kategori->nama }}'?"
                                    class="text-xs font-medium text-red-600 transition hover:text-red-500 dark:text-red-400">
                                    Hapus
                                </button>
                                @else
                                <span class="text-xs text-gray-400 dark:text-gray-600 cursor-not-allowed" title="Kategori masih memiliki surat terarsip">
                                    Hapus
                                </span>
                                @endif
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            Belum ada kategori arsip untuk unit Anda. Silakan tambahkan kategori di atas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
