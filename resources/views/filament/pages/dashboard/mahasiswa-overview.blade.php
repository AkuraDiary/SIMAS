<div class="space-y-8">
    {{-- Top Header: Judul & Tombol Buat Pengajuan Baru --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">History Pengajuan</h2>
        </div>
        <div>
            <a href="{{ \App\Filament\Resources\MahasiswaSurats\Pages\CreateMahasiswaSurat::getUrl() }}"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                <span>Buat Pengajuan Baru</span>
            </a>
        </div>
    </div>

    {{-- 3 Kartu Statistik --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Card 1: Total Pengajuan --}}
        <div class="flex items-center gap-4 p-6 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-900 dark:border-gray-800">
            <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400 shrink-0">
                <x-heroicon-o-document-text class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Pengajuan</p>
                <h3 class="text-3xl font-extrabold text-gray-900 dark:text-white mt-0.5">{{ $totalPengajuan }}</h3>
            </div>
        </div>

        {{-- Card 2: Menunggu Verifikasi --}}
        <div class="flex items-center gap-4 p-6 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-900 dark:border-gray-800">
            <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400 shrink-0">
                <x-heroicon-o-clock class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Menunggu Verifikasi</p>
                <h3 class="text-3xl font-extrabold text-gray-900 dark:text-white mt-0.5">{{ $menungguVerifikasi }}</h3>
            </div>
        </div>

        {{-- Card 3: Selesai --}}
        <div class="flex items-center gap-4 p-6 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-900 dark:border-gray-800">
            <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400 shrink-0">
                <x-heroicon-o-check-badge class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Selesai</p>
                <h3 class="text-3xl font-extrabold text-gray-900 dark:text-white mt-0.5">{{ $selesai }}</h3>
            </div>
        </div>
    </div>

    {{-- Main Container: Daftar Pengajuan Dokumen --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden dark:bg-gray-900 dark:border-gray-800 p-6">
        {{-- Card Header & Live Search --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-base font-bold text-gray-900 dark:text-white">Daftar Pengajuan Dokumen</h3>
            <div class="relative w-full sm:w-72">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                </div>
                <input wire:model.live.debounce.300ms="searchMahasiswa"
                    type="text"
                    placeholder="Cari registrasi..."
                    class="block w-full pl-9 pr-4 py-2 text-xs border border-gray-200 rounded-xl bg-gray-50/50 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 transition">
            </div>
        </div>

        {{-- Tabel Riwayat --}}
        <div class="overflow-x-auto mt-4">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800 text-[11px] font-bold uppercase tracking-wider text-gray-400">
                        <th class="py-3 px-4">KODE PELACAKAN</th>
                        <th class="py-3 px-4">PERIHAL</th>
                        <th class="py-3 px-4">TANGGAL</th>
                        <th class="py-3 px-4">STATUS</th>
                        <th class="py-3 px-4 text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800/60 text-xs">
                    @forelse($pengajuans as $item)
                    @php
                    $status = strtoupper($item->status_surat ?? 'DIPROSES');
                    $isApproved = in_array($status, ['SELESAI', 'TERBIT']);
                    $isPending = in_array($status, ['DIPROSES', 'TERKIRIM', 'MENUNGGU']);
                    $isRejected = in_array($status, ['REVISI', 'DITOLAK']);

                    $labelStatus = $isApproved ? 'Approved' : ($isRejected ? 'Rejected' : 'Pending');
                    $badgeClass = $isApproved
                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800'
                    : ($isRejected
                    ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800'
                    : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800');

                    $namaDokumen =  $item->perihal;
                    $trackingCode = $item->tracking_code ?: ('REQ-' . $item->id);
                    @endphp
                    <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition">
                        <td class="py-3.5 px-4 font-semibold text-primary-600 dark:text-primary-400">
                            <a href="{{ \App\Filament\Resources\MahasiswaSurats\MahasiswaSuratResource::getUrl('view', ['record' => $item]) }}" class="hover:underline">
                                {{ $trackingCode }}
                            </a>
                        </td>
                        <td class="py-3.5 px-4 font-medium text-gray-700 dark:text-gray-200">
                            {{ $namaDokumen }}
                        </td>
                        <td class="py-3.5 px-4 text-gray-500 dark:text-gray-400">
                            {{ $item->created_at ? $item->created_at->translatedFormat('d M Y') : '-' }}
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $badgeClass }}">
                                {{ $labelStatus }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <a href="{{ \App\Filament\Resources\MahasiswaSurats\MahasiswaSuratResource::getUrl('view', ['record' => $item]) }}"
                                title="Lihat Detail & Status"
                                class="inline-flex items-center justify-center p-1.5 rounded-lg text-gray-400 hover:text-primary-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                                <x-heroicon-o-eye class="w-4 h-4" />
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-10 text-center text-gray-400 dark:text-gray-500">
                            Belum ada riwayat permohonan pengajuan surat.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer Paginasi Sesuai Mockup --}}
        @if($pengajuans->hasPages() || $pengajuans->total() > 0)
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-6 mt-4 border-t border-gray-100 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400">
            <div>
                Showing <strong>{{ $pengajuans->firstItem() ?? 0 }}</strong> of <strong>{{ $pengajuans->total() }}</strong> entries
            </div>
            <div class="flex items-center gap-1">
                {{-- Previous Page Link --}}
                @if ($pengajuans->onFirstPage())
                <span class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 text-gray-300 dark:text-gray-600 cursor-not-allowed">&lt;</span>
                @else
                <button wire:click="previousPage('mahasiswaPage')" type="button" class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">&lt;</button>
                @endif

                {{-- Pagination Elements --}}
                @for ($page = 1; $page <= $pengajuans->lastPage(); $page++)
                    @if ($page == $pengajuans->currentPage())
                    <span class="w-7 h-7 flex items-center justify-center rounded-lg bg-primary-600 text-white font-bold">{{ $page }}</span>
                    @elseif ($page <= 3 || $page>= $pengajuans->lastPage() - 2 || abs($page - $pengajuans->currentPage()) <= 1)
                            <button wire:click="gotoPage({{ $page }}, 'mahasiswaPage')" type="button" class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">{{ $page }}</button>
                            @endif
                            @endfor

                            {{-- Next Page Link --}}
                            @if ($pengajuans->hasMorePages())
                            <button wire:click="nextPage('mahasiswaPage')" type="button" class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">&gt;</button>
                            @else
                            <span class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 text-gray-300 dark:text-gray-600 cursor-not-allowed">&gt;</span>
                            @endif
            </div>
        </div>
        @endif
    </div>

    {{-- Banner Informasi Verifikasi Sesuai Mockup
    <div class="flex items-start gap-4 p-5 bg-indigo-50/70 border border-indigo-100 rounded-2xl dark:bg-indigo-950/20 dark:border-indigo-900/40">
        <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0 dark:bg-indigo-900/50 dark:text-indigo-300">
            <x-heroicon-o-information-circle class="w-5 h-5" />
        </div>
        <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-950 dark:text-indigo-200">INFORMASI VERIFIKASI</h4>
            <p class="text-xs text-indigo-900/80 dark:text-indigo-300/80 mt-1">Pastikan dokumen yang anda unggah sudah benar.</p>
        </div>
    </div>

    --}}
</div>
