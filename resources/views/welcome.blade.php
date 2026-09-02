<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal SIMAS - Universitas Kadiri</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased">
    <!-- Navbar -->
    <x-portal-navbar />

    <!-- Hero Section -->
    <main class="max-w-5xl mx-auto mt-16 px-6">
        <h1 class="text-4xl md:text-5xl font-extrabold text-center text-primary-600 leading-tight mb-12">
            Portal surat satu pintu <br> Universitas Kadiri
        </h1>

        <!-- Cards -->
        <div class="grid md:grid-cols-2 gap-8">
            <!-- Ajukan Surat Card -->
            <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 flex flex-col items-start hover:border-primary-200 transition">
                <div class="bg-primary-600 text-white p-3 rounded-lg mb-6 shadow-sm shadow-primary-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                </div>
                <h2 class="text-2xl font-bold mb-2 text-gray-800">Ajukan Surat</h2>
                <p class="text-gray-500 mb-8 flex-grow">Buat dan kirim surat baru ke Universitas Kadiri</p>
                <a href="{{ route('pengajuan') }}" class="text-primary-600 font-semibold flex items-center hover:text-primary-800 transition">
                    Mulai Pengajuan <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
            </div>

            <!-- Lacak Pengajuan Card -->
            <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 flex flex-col items-start hover:border-secondary-200 transition">
                <div class="bg-gray-100 text-gray-600 p-3 rounded-lg mb-6">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <h2 class="text-2xl font-bold mb-2 text-gray-800">Lacak Pengajuan</h2>
                <p class="text-gray-500 mb-6">Periksa dan lacak status pengajuan surat melalui kode unik surat</p>
                <form action="{{ route('lacak') }}" method="GET" class="w-full flex space-x-2 mt-auto">
                    <input type="text" name="tracking_code" placeholder="Masukkan Kode Pelacakan" class="flex-grow border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500">
                    <button type="submit" class="bg-secondary-500 text-white px-6 py-2 rounded-lg font-medium hover:bg-secondary-600 transition shadow-sm shadow-secondary-200">Lacak</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
