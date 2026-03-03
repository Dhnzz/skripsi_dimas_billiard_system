<div class="position-relative iq-banner">
    <!--Nav Start-->
    <nav class="nav navbar navbar-expand-lg navbar-light iq-navbar">
        <div class="container-fluid navbar-inner">
            <a href="../dashboard/index.html" class="navbar-brand">
                <!--Logo start-->
                <!--logo End-->

                <!--Logo start-->
                <div class="logo-main">
                    <div class="logo-normal w-fit">
                        <img src="{{ asset('dashboard_asset/images/brands/icon.png') }}" class="icon-30" alt="">
                    </div>
                    <div class="logo-mini">
                        <img src="{{ asset('dashboard_asset/images/brands/icon.png') }}" class="icon-30" alt="">
                    </div>
                </div>
                <!--logo End-->
                <h4 class="logo-title">Swiss 18 Pool</h4>
            </a>
            <div class="sidebar-toggle" data-toggle="sidebar" data-active="true">
                <i class="icon">
                    <svg width="20px" class="icon-20" viewBox="0 0 24 24">
                        <path fill="currentColor"
                            d="M4,11V13H16L10.5,18.5L11.92,19.92L19.84,12L11.92,4.08L10.5,5.5L16,11H4Z" />
                    </svg>
                </i>
            </div>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="mb-2 navbar-nav ms-auto align-items-center navbar-list mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="py-0 nav-link d-flex align-items-center" href="#" id="navbarDropdown"
                            role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            @php
                                $avatar = auth()->user()->avatar;

                                if ($avatar == null) {
                                    $path = asset('dashboard_asset/images/avatars/01.png');
                                } else {
                                    $path = asset('dashboard_asset/images/' . $avatar);
                                }

                            @endphp
                            <img src="{{ $path }}" alt="User-Profile"
                                class="theme-color-default-img img-fluid avatar avatar-50 avatar-rounded">
                            <div class="caption ms-3 d-none d-md-block ">
                                <h6 class="mb-0 caption-title">
                                    {{ \Illuminate\Support\Str::title(auth()->user()->name ?? 'Pengguna') }}</h6>
                                <p class="mb-0 caption-sub-title">
                                    {{ \Illuminate\Support\Str::title(auth()->user()->getRoleNames()->first()) }}</p>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="../dashboard/app/user-profile.html">Profile</a>
                            </li>
                            <li><a class="dropdown-item" href="../dashboard/app/user-privacy-setting.html">Privacy
                                    Setting</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                {{-- <a class="dropdown-item" href="{{ route('logout') }}">Logout</a> --}}
                                <form action="{{ route('logout') }}" method="post">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav> <!-- Nav Header Component Start -->
    <div class="iq-navbar-header" style="height: 215px;">
        <div class="container-fluid iq-container">
            <div class="row">
                <div class="col-md-12">
                    <div class="flex-wrap d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="mb-2">
                                {{ $title ?? 'Page Title' }}
                            </h1>
                            @isset($breadcrumbs)
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb mb-0">
                                        @foreach ($breadcrumbs as $item)
                                            @if (isset($item['url']) && !$loop->last)
                                                <li class="breadcrumb-item">
                                                    <a href="{{ $item['url'] }}"
                                                        class="text-white-50" wire:navigate>{{ $item['title'] }}</a>
                                                </li>
                                            @else
                                                <li class="breadcrumb-item active text-white" aria-current="page">
                                                    {{ $item['title'] }}
                                                </li>
                                            @endif
                                        @endforeach
                                    </ol>
                                </nav>
                            @endisset
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="iq-header-img">
            <img src="{{ asset('dashboard_asset/images/dashboard/top-header.png') }}" alt="header"
                class="theme-color-default-img img-fluid w-100 h-100 animated-scaleX">
        </div>
    </div> <!-- Nav Header Component End -->
    <!--Nav End-->
</div>
