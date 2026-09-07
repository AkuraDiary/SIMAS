<div x-data="{ tipe: $wire.$entangle('{{ $getStatePath() }}').live }" class="border-b border-gray-200 mb-2">
    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <!-- Guest Tab -->
        <button @click="tipe = 'guest'" type="button"
            :class="tipe === 'guest' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
            class="group inline-flex items-center py-4 px-1 border-b-2 font-bold text-sm transition gap-2 cursor-pointer">
            <svg class="w-5 h-5" :class="tipe === 'guest' ? 'text-primary-600' : 'text-gray-400 group-hover:text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
            Pihak Luar (Guest)
        </button>
        <!-- Mahasiswa Tab -->
        <button @click="tipe = 'mahasiswa'" type="button"
            :class="tipe === 'mahasiswa' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
            class="group inline-flex items-center py-4 px-1 border-b-2 font-bold text-sm transition gap-2 cursor-pointer">
            <svg class="w-5 h-5" :class="tipe === 'mahasiswa' ? 'text-primary-600' : 'text-gray-400 group-hover:text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path></svg>
            Mahasiswa
        </button>
    </nav>
</div>
