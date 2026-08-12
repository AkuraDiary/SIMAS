<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? 'Portal Layanan Surat - SIMAS' }}</title>
        
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Styles/Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Livewire Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased text-gray-900 bg-gray-50 dark:bg-gray-900 dark:text-gray-100 min-h-screen flex flex-col selection:bg-indigo-500 selection:text-white">
        
        <!-- Simple Header -->
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold">
                        S
                    </div>
                    <span class="font-bold text-lg tracking-tight text-gray-900 dark:text-white">SIMAS</span>
                </div>
                
                <nav class="flex gap-4">
                    <a href="{{ route('pengajuan') }}" class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition">Ajukan Surat</a>
                    <a href="{{ route('lacak') }}" class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition">Lacak Status</a>
                    <a href="/" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 transition">Login Pegawai &rarr;</a>
                </nav>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            {{ $slot }}
        </main>
        
        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-6 mt-auto">
            <div class="max-w-7xl mx-auto px-4 text-center text-sm text-gray-500 dark:text-gray-400">
                &copy; {{ date('Y') }} SIMAS - Sistem Informasi Manajemen Surat.
            </div>
        </footer>

        <!-- Livewire Scripts -->
        @livewireScripts
    </body>
</html>
