<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="{{ route("admin.dashboard") }}" class="brand-link">
        <span class="brand-text font-weight-bold">Billiard App</span>
    </a>
    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column">

                <li class="nav-item">
                    <a href="{{ route("admin.dashboard") }}"
                        class="nav-link {{ request()->routeIs("admin.dashboard") ? "active" : "" }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route("admin.meja") }}"
                        class="nav-link {{ request()->routeIs("admin.meja") ? "active" : "" }}">
                        <i class="nav-icon fas fa-table"></i>
                        <p>Meja Billiard</p>
                    </a>
                </li>
                {{-- Tambah menu lain di sini --}}

            </ul>
        </nav>
    </div>
</aside>