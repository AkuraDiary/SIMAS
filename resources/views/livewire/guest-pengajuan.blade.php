<div class="max-w-8xl mx-auto py-10 px-2 sm:px-6 lg:px-4">
    @if($submitted)
        <!-- Success State -->
        <div class="bg-white shadow-sm sm:rounded-2xl p-10 text-center border border-gray-100">
            <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-6">
                <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h2 class="text-3xl font-extrabold text-gray-900 mb-2">Pengajuan Berhasil Dikirim!</h2>
            <p class="text-gray-500 text-lg mb-8 max-w-xl mx-auto">Harap simpan kode pelacakan unik di bawah ini dengan aman. Anda akan membutuhkannya untuk mengecek status atau mengunduh surat terbitan Anda nanti.</p>

            <div class="inline-block bg-gray-50 border-2 border-dashed border-primary-200 rounded-xl px-10 py-6 mb-8">
                <p class="text-sm text-gray-500 uppercase tracking-widest font-bold mb-2">KODE PELACAKAN</p>
                <div class="text-5xl font-mono font-black text-primary-700 tracking-wider">
                    {{ $trackingCode }}
                </div>
            </div>

            <div>
                <a href="{{ route('lacak') }}" class="inline-flex items-center px-8 py-3 border border-transparent text-lg font-bold rounded-xl shadow-sm shadow-primary-200 text-white bg-primary-600 hover:bg-primary-700 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    Lacak Surat Sekarang
                </a>
            </div>
        </div>
    @else
        <!-- NEW Form Header matching the UI Design -->
        <div class="text-center mb-10">
            <h1 class="text-4xl font-extrabold text-gray-900 mb-3">Ajukan Surat Baru</h1>
            <p class="text-gray-500 text-lg">Lengkapi langkah berikut untuk mengajukan surat baru</p>
        </div>

        <!-- Filament Form Render -->
        <!-- Notice we removed the extra white bg and borders here, letting Filament's Wizard card shine natively -->
        <div class="w-full">
            <form wire:submit="submit">
                {{ $this->form }}
            </form>
        </div>
    @endif
</div>

