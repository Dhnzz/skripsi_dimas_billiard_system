<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Admin</title>

    {{-- AdminLTE CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    {{-- Google Font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Livewire Styles (WAJIB di <head>) --}}
    @livewireStyles

    {{-- Custom CSS tambahan --}}
    @stack('styles')
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        {{-- Navbar --}}
        @include('layouts.partials.admin-navbar')

        {{-- Sidebar --}}
        @include('layouts.partials.admin-sidebar')

        {{-- Content Wrapper --}}
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <h1 class="m-0">@yield('page-title', 'Dashboard')</h1>
                </div>
            </div>
            <section class="content">
                <div class="container-fluid">
                    {{ $slot }} {{-- Livewire component masuk sini --}}
                </div>
            </section>
        </div>

        <footer class="main-footer">
            <strong>Billiard App</strong> &copy; 2026
        </footer>
    </div>

    {{-- AdminLTE JS (jQuery harus sebelum AdminLTE) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

    {{-- Livewire Scripts (WAJIB sebelum </body>) --}}
    @livewireScripts

    {{-- Custom scripts --}}
    @stack('scripts')
</body>

</html>
