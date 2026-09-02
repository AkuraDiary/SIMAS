<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'Portal Layanan Surat - SIMAS' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Important for Filament / Alpine.js -->
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <!-- Styles/Scripts -->
    @vite(['resources/css/app.css', 'resources/css/filament/simas/theme.css', 'resources/js/app.js'])

    <!-- Livewire & Filament Styles -->
    @livewireStyles
    @filamentStyles
</head>

<body class="font-sans antialiased text-gray-900 bg-gray-50 min-h-screen flex flex-col">

    <!-- Simple Header -->
    <x-portal-navbar />

    <!-- Main Content -->
    <main class="w-full flex-grow">
        {{ $slot }}
    </main>

    <!-- Livewire & Filament Scripts -->
    @livewireScripts
    @filamentScripts
    @stack('scripts')
</body>

</html>
