<!DOCTYPE html>
<html lang="id" data-theme="light">

<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name') }}</title>

    {{-- Tailwind CSS via CDN (development) --}}
    {{-- Untuk production, gunakan npm run build --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- DaisyUI component library --}}
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @livewireStyles
    @stack('styles')
</head>

<body class="bg-gray-50 font-[Inter]">

    {{-- Navbar --}}
    @include('layouts.partials.member-navbar')

    {{-- Flash message --}}
    @if (session('success'))
        <div class="alert alert-success fixed top-4 right-4 w-80 z-50 shadow-lg">
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Main Content --}}
    <main class="min-h-screen">
        {{ $slot }}
    </main>

    @livewireScripts
    @stack('scripts')
</body>

</html>
