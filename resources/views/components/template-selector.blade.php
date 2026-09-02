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
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input x-model="search" type="text" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition" placeholder="Cari template...">
        </div>
    </div>

    <!-- Filter Pills -->
    <div class="flex flex-wrap gap-2 mb-8">
        <template x-for="filter in ['Semua', 'Akademik', 'Kepegawaian', 'Umum', 'Kemahasiswaan']">
            <button @click="activeFilter = filter"
                    type="button"
                    :class="activeFilter === filter ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-600 border-gray-200 hover:border-primary-300'"
                    class="px-5 py-2 rounded-full border text-sm font-semibold transition shadow-sm"
                    x-text="filter">
            </button>
        </template>
    </div>

    <!-- Template Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <!-- Card 1 -->
        <div @click="selectedTemplate = '1'"
             :class="selectedTemplate === '1' ? 'border-primary-600 ring-1 ring-primary-600 shadow-md bg-primary-50/30' : 'border-gray-200 hover:border-primary-300'"
             class="bg-white border rounded-xl p-6 flex flex-col cursor-pointer transition">
            <div class="w-12 h-12 bg-primary-100 text-primary-700 rounded-lg flex items-center justify-center mb-5">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Surat Keterangan Aktif Kuliah</h3>
            <p class="text-sm text-gray-500 mb-6 flex-grow">Digunakan untuk keperluan administrasi luar kampus yang membutuhkan bukti status kemahasiswaan aktif.</p>
            <button type="button"
                    :class="selectedTemplate === '1' ? 'bg-primary-600 text-white border-primary-600' : 'bg-transparent text-primary-600 border-primary-600 hover:bg-primary-50'"
                    class="w-full border-2 font-bold py-2 rounded-lg transition text-sm">
                <span x-text="selectedTemplate === '1' ? 'Terpilih' : 'Pilih'"></span>
            </button>
        </div>

        <!-- Card 2 -->
        <div @click="selectedTemplate = '2'"
             :class="selectedTemplate === '2' ? 'border-primary-600 ring-1 ring-primary-600 shadow-md bg-primary-50/30' : 'border-gray-200 hover:border-primary-300'"
             class="bg-white border rounded-xl p-6 flex flex-col cursor-pointer transition">
            <div class="w-12 h-12 bg-primary-100 text-primary-700 rounded-lg flex items-center justify-center mb-5">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Surat Pengantar Magang / PKL</h3>
            <p class="text-sm text-gray-500 mb-6 flex-grow">Surat resmi untuk izin melakukan penelitian di instansi atau lembaga mitra bagi mahasiswa.</p>
            <button type="button"
                    :class="selectedTemplate === '2' ? 'bg-primary-600 text-white border-primary-600' : 'bg-transparent text-primary-600 border-primary-600 hover:bg-primary-50'"
                    class="w-full border-2 font-bold py-2 rounded-lg transition text-sm">
                <span x-text="selectedTemplate === '2' ? 'Terpilih' : 'Pilih'"></span>
            </button>
        </div>

        <!-- Card 3 -->
        <div @click="selectedTemplate = '3'"
             :class="selectedTemplate === '3' ? 'border-primary-600 ring-1 ring-primary-600 shadow-md bg-primary-50/30' : 'border-gray-200 hover:border-primary-300'"
             class="bg-white border rounded-xl p-6 flex flex-col cursor-pointer transition">
            <div class="w-12 h-12 bg-primary-100 text-primary-700 rounded-lg flex items-center justify-center mb-5">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Surat Keterangan Lulus (SKL)</h3>
            <p class="text-sm text-gray-500 mb-6 flex-grow">Format universal untuk berbagai kebutuhan pernyataan tertulis resmi akademik.</p>
            <button type="button"
                    :class="selectedTemplate === '3' ? 'bg-primary-600 text-white border-primary-600' : 'bg-transparent text-primary-600 border-primary-600 hover:bg-primary-50'"
                    class="w-full border-2 font-bold py-2 rounded-lg transition text-sm">
                <span x-text="selectedTemplate === '3' ? 'Terpilih' : 'Pilih'"></span>
            </button>
        </div>

    </div>

    <!-- Hidden validation error message if they try to click Next without selecting -->
    @error($getStatePath())
        <p class="mt-4 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
