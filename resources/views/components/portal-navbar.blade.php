<nav class="flex justify-between items-center py-5 px-6 md:px-10 bg-white shadow-sm border-b border-gray-100">
    <div>
        <a href="{{ route('home') }}" class="text-2xl font-bold text-primary-700 tracking-tight">SIMAS</a>
    </div>
    <div class=" md:flex space-x-8 text-sm font-semibold text-gray-500">
        <a href="{{ route('home') }}" class="hover:text-primary-600 transition">Beranda</a>
        <a href="{{ route('lacak') }}" class="hover:text-primary-600 transition">Cek Status</a>
        <a href="#" class="hover:text-primary-600 transition">Pusat Bantuan</a>
    </div>
    <div>
        <a href="/internal/login" class="bg-primary-600 text-white px-6 py-2.5 rounded-lg font-bold hover:bg-primary-700 transition shadow-sm shadow-primary-200">Masuk</a>
    </div>
</nav>
