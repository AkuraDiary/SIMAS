<div x-data="{
        selectedTemplate: $wire.$entangle('{{ $getStatePath() }}'),
        search: '',
        activeFilter: 'Semua'
    }"
    class="w-full">

    <!-- Top Header & Search -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h2 class="text-3xl font-extrabold text-gray-900 mb-1">Pilih Template Surat</h2>
            <p class="text-gray-500">Gunakan template yang tersedia untuk mempercepat proses pengajuan surat Anda.</p>
        </div>
        <div class="relative w-full md:w-72">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input x-model="search" type="text" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition" placeholder="Cari template...">
        </div>
    </div>

    @php
    $allowedAkses = ['PUBLIK'];
    if (auth()->check() && auth()->user()->tipe_entitas === 'MAHASISWA') {
    $allowedAkses[] = 'MAHASISWA';
    }

    $templates = \App\Models\Template::with('kategori')
    ->where('is_active', true)
    ->whereIn('aksesibilitas', $allowedAkses)
    ->get();

    $categories = $templates->pluck('kategori.nama_kategori')->filter()->unique()->values()->toArray();
    array_unshift($categories, 'Semua');
    @endphp





    <!-- Filter Pills -->
    <div class="flex flex-wrap gap-2 mb-6">
        @foreach($categories as $filter)
        <button @click="activeFilter = '{{ $filter }}'"
            type="button"
            :class="activeFilter === '{{ $filter }}' ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-600 border-gray-200 hover:border-primary-300'"
            class="px-5 py-2 rounded-full border text-sm font-semibold transition shadow-sm">
            {{ $filter }}
        </button>
        @endforeach
    </div>

    <!-- Template Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($templates as $template)
        <div @click="selectedTemplate = '{{ $template->id }}'"
            x-show="(activeFilter === 'Semua' || '{{ $template->kategori?->nama_kategori ?? '' }}' === activeFilter) && ('{{ strtolower(addslashes($template->nama_template)) }}'.includes(search.toLowerCase()) || '{{ strtolower(addslashes($template->deskripsi ?? '')) }}'.includes(search.toLowerCase()))"
            :class="selectedTemplate === '{{ $template->id }}' ? 'border-primary-600 ring-1 ring-primary-600 shadow-md bg-primary-50/30' : 'border-gray-200 hover:border-primary-300'"
            class="bg-white border rounded-xl p-6 flex flex-col cursor-pointer transition">
            <div class="w-12 h-12 bg-primary-100 text-primary-700 rounded-lg flex items-center justify-center mb-5">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $template->nama_template }}</h3>
            <p class="text-sm text-gray-500 mb-6 flex-grow">{{ $template->deskripsi ?? 'Tidak ada deskripsi.' }}</p>
            <button type="button"
                :class="selectedTemplate === '{{ $template->id }}' ? 'bg-primary-600 text-white border-primary-600' : 'bg-transparent text-primary-600 border-primary-600 hover:bg-primary-50'"
                class="w-full border-2 font-bold py-2 rounded-lg transition text-sm">
                <span x-text="selectedTemplate === '{{ $template->id }}' ? 'Terpilih' : 'Pilih'"></span>
            </button>
        </div>
        @empty
        <div class="col-span-full text-center py-10 text-gray-500">
            Belum ada template surat yang tersedia.
        </div>
        @endforelse
    </div>

    <div class="flex items-center gap-4 mb-6">
        <hr class="flex-grow border-gray-200">
        <span class="text-sm font-semibold text-gray-400 uppercase tracking-wider">ATAU BUAT SURAT DARI AWAL</span>
        <hr class="flex-grow border-gray-200">
    </div>


    <!-- Opsi Pembuatan Mandiri -->
    <div class="mb-10">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Opsi Pembuatan Bebas</h3>
        <div @click="selectedTemplate = 'scratch'"
            :class="selectedTemplate === 'scratch' ? 'border-primary-600 ring-1 ring-primary-600 shadow-md bg-primary-50/30' : 'border-gray-200 hover:border-primary-300'"
            class="bg-white border rounded-xl p-5 flex flex-col md:flex-row items-center gap-5 cursor-pointer transition">
            <div class="w-14 h-14 bg-gray-100 text-gray-700 rounded-lg flex-shrink-0 flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
            </div>
            <div class="flex-grow text-center md:text-left">
                <h3 class="text-lg font-bold text-gray-900 mb-1">Buat Surat Tanpa Template</h3>
                <p class="text-sm text-gray-500">Tulis surat dari awal secara mandiri menggunakan editor teks. Cocok untuk kebutuhan surat dengan format khusus.</p>
            </div>
            <div class="w-full md:w-48 mt-2 md:mt-0 flex-shrink-0">
                <button type="button"
                    :class="selectedTemplate === 'scratch' ? 'bg-primary-600 text-white border-primary-600' : 'bg-transparent text-primary-600 border-primary-600 hover:bg-primary-50'"
                    class="w-full border-2 font-bold py-2 rounded-lg transition text-sm">
                    <span x-text="selectedTemplate === 'scratch' ? 'Terpilih' : 'Gunakan Opsi Ini'"></span>
                </button>
            </div>
        </div>
    </div>


    <!-- Hidden validation error message if they try to click Next without selecting -->
    @error($getStatePath())
    <p class="mt-4 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
