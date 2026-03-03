<!doctype html>
<html lang="en" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Swiss 18 Pool | Owner - Dashboard</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('dashboard_asset/images/brands/icon.png') }}" />

    <!-- Library / Plugin Css Build -->
    <link rel="stylesheet" href="{{ asset('dashboard_asset/css/core/libs.min.css') }}" />

    <!-- Aos Animation Css -->
    <link rel="stylesheet" href="{{ asset('dashboard_asset/vendor/aos/dist/aos.css') }}" />

    <!-- Hope Ui Design System Css -->
    <link rel="stylesheet" href="{{ asset('dashboard_asset/css/hope-ui.min.css?v=2.0.0') }}" />

    <!-- Custom Css -->
    <link rel="stylesheet" href="{{ asset('dashboard_asset/css/custom.min.css?v=2.0.0') }}" />

    <!-- Dark Css -->
    <link rel="stylesheet" href="{{ asset('dashboard_asset/css/dark.min.css') }}" />

    <!-- Customizer Css -->
    <link rel="stylesheet" href="{{ asset('dashboard_asset/css/customizer.min.css') }}" />

    <!-- RTL Css -->
    <link rel="stylesheet" href="{{ asset('dashboard_asset/css/rtl.min.css') }}" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.2.0/css/all.min.css" />


    {{-- Livewire Styles (WAJIB di <head>) --}}
    @livewireStyles

    <!-- SweetAlert2 -->
    <script data-navigate-once src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Custom CSS tambahan --}}
    @stack('styles')
</head>

<body>

    @include('layouts.sidebar')
    <main class="main-content">
        @include('layouts.navbar')
        <div class="conatiner-fluid content-inner mt-n5 py-0 position-relative">
            <!-- Content Loader -->
            <div id="content-loading"
                style="display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.7); z-index: 99; min-height: 300px;">
                <div class="d-flex justify-content-center align-items-center" style="height: 300px;">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Memuat...</span>
                    </div>
                </div>
            </div>
            {{ $slot }}
        </div>
        <!-- Footer Section Start -->
        <footer class="footer">
            <div class="footer-body">
                <ul class="left-panel list-inline mb-0 p-0">
                    <li class="list-inline-item"><a href="../dashboard/extra/privacy-policy.html">Privacy Policy</a>
                    </li>
                    <li class="list-inline-item"><a href="../dashboard/extra/terms-of-service.html">Terms of Use</a>
                    </li>
                </ul>
                <div class="right-panel">
                    © {{ date('Y') }} Hope UI, Made with
                    <span class="">
                        <svg class="icon-15" width="15" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M15.85 2.50065C16.481 2.50065 17.111 2.58965 17.71 2.79065C21.401 3.99065 22.731 8.04065 21.62 11.5806C20.99 13.3896 19.96 15.0406 18.611 16.3896C16.68 18.2596 14.561 19.9196 12.28 21.3496L12.03 21.5006L11.77 21.3396C9.48102 19.9196 7.35002 18.2596 5.40102 16.3796C4.06102 15.0306 3.03002 13.3896 2.39002 11.5806C1.26002 8.04065 2.59002 3.99065 6.32102 2.76965C6.61102 2.66965 6.91002 2.59965 7.21002 2.56065H7.33002C7.61102 2.51965 7.89002 2.50065 8.17002 2.50065H8.28002C8.91002 2.51965 9.52002 2.62965 10.111 2.83065H10.17C10.21 2.84965 10.24 2.87065 10.26 2.88965C10.481 2.96065 10.69 3.04065 10.89 3.15065L11.27 3.32065C11.3618 3.36962 11.4649 3.44445 11.554 3.50912C11.6104 3.55009 11.6612 3.58699 11.7 3.61065C11.7163 3.62028 11.7329 3.62996 11.7496 3.63972C11.8354 3.68977 11.9247 3.74191 12 3.79965C13.111 2.95065 14.46 2.49065 15.85 2.50065ZM18.51 9.70065C18.92 9.68965 19.27 9.36065 19.3 8.93965V8.82065C19.33 7.41965 18.481 6.15065 17.19 5.66065C16.78 5.51965 16.33 5.74065 16.18 6.16065C16.04 6.58065 16.26 7.04065 16.68 7.18965C17.321 7.42965 17.75 8.06065 17.75 8.75965V8.79065C17.731 9.01965 17.8 9.24065 17.94 9.41065C18.08 9.58065 18.29 9.67965 18.51 9.70065Z"
                                fill="currentColor"></path>
                        </svg>
                    </span> by <a href="https://iqonic.design/">IQONIC Design</a>.
                </div>
            </div>
        </footer>
        <!-- Footer Section End -->
    </main>
    <!-- Wrapper End-->

    <!-- Toast Notification -->
    <div id="toast-container" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
        <div x-data="toastNotification()" x-on:notify.window="showToast($event.detail)">
            <template x-if="visible">
                <div class="toast show align-items-center border-0 shadow-lg" role="alert" aria-live="assertive"
                    aria-atomic="true"
                    :class="{
                        'text-bg-success': type === 'success',
                        'text-bg-danger': type === 'error',
                        'text-bg-warning': type === 'warning',
                        'text-bg-info': type === 'info'
                    }"
                    style="min-width: 300px;" x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-x-full"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-x-0"
                    x-transition:leave-end="opacity-0 translate-x-full">
                    <div class="d-flex">
                        <div class="toast-body d-flex align-items-center">
                            <i class="me-2"
                                :class="{
                                    'fa-solid fa-circle-check': type === 'success',
                                    'fa-solid fa-circle-xmark': type === 'error',
                                    'fa-solid fa-triangle-exclamation': type === 'warning',
                                    'fa-solid fa-circle-info': type === 'info'
                                }"></i>
                            <span x-text="message"></span>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto"
                            @click="visible = false"></button>
                    </div>
                </div>
            </template>
        </div>
    </div>


    <!-- Library Bundle Script -->
    <script data-navigate-once src="{{ asset('dashboard_asset/js/core/libs.min.js') }}"></script>

    <!-- External Library Bundle Script -->
    <script data-navigate-once src="{{ asset('dashboard_asset/js/core/external.min.js') }}"></script>

    <!-- Widgetchart Script -->
    <script data-navigate-once src="{{ asset('dashboard_asset/js/charts/widgetcharts.js') }}"></script>

    <!-- mapchart Script -->
    <script data-navigate-once src="{{ asset('dashboard_asset/js/charts/vectore-chart.js') }}"></script>
    <script data-navigate-once src="{{ asset('dashboard_asset/js/charts/dashboard.js') }}"></script>

    <!-- fslightbox Script -->
    <script data-navigate-once src="{{ asset('dashboard_asset/js/plugins/fslightbox.js') }}"></script>

    <!-- Settings Script -->
    <script data-navigate-once src="{{ asset('dashboard_asset/js/plugins/setting.js') }}"></script>

    <!-- Form Wizard Script -->
    <script data-navigate-once src="{{ asset('dashboard_asset/js/plugins/form-wizard.js') }}"></script>

    <!-- AOS Animation Plugin-->
    <script data-navigate-once src="{{ asset('dashboard_asset/vendor/aos/dist/aos.js') }}"></script>

    <!-- App Script -->
    <script data-navigate-once src="{{ asset('dashboard_asset/js/hope-ui.js') }}" defer></script>

    {{-- Custom scripts --}}
    @stack('scripts')

    <!-- Font Awesome Kit -->
    <script data-navigate-once src="https://kit.fontawesome.com/d989f340c1.js" crossorigin="anonymous"></script>


    {{-- Livewire Scripts (WAJIB sebelum </body>) --}}
    @livewireScripts

    <script data-navigate-once>
        function toastNotification() {
            return {
                visible: false,
                message: '',
                type: 'success',
                timeout: null,
                showToast(detail) {
                    clearTimeout(this.timeout);
                    this.message = detail.message || detail[0]?.message || 'Berhasil!';
                    this.type = detail.type || detail[0]?.type || 'success';
                    this.visible = true;
                    this.timeout = setTimeout(() => {
                        this.visible = false;
                    }, 3000);
                }
            }
        }

        // Mendengar Livewire dispatch event
        document.addEventListener('livewire:init', () => {
            Livewire.on('notify', (detail) => {
                window.dispatchEvent(new CustomEvent('notify', {
                    detail
                }));
            });
        });

        // Mendengar session flash setelah navigasi
        document.addEventListener('livewire:navigated', () => {
            const flashSuccess = document.querySelector('meta[name="flash-success"]');
            const flashError = document.querySelector('meta[name="flash-error"]');
            if (flashSuccess) {
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message: flashSuccess.content,
                        type: 'success'
                    }
                }));
                flashSuccess.remove();
            }
            if (flashError) {
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message: flashError.content,
                        type: 'error'
                    }
                }));
                flashError.remove();
            }
        });

        // Content Loader: hanya di area konten
        function hideContentLoader() {
            const loader = document.getElementById('content-loading');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => {
                    loader.style.display = 'none';
                    loader.style.opacity = '1';
                }, 300);
            }
        }

        function showContentLoader() {
            const loader = document.getElementById('content-loading');
            if (loader) {
                loader.style.display = '';
            }
        }

        // Tampilkan loader saat navigasi SPA dimulai, sembunyikan saat selesai
        document.addEventListener('livewire:navigate', showContentLoader);
        document.addEventListener('livewire:navigated', hideContentLoader);
    </script>
</body>

</html>
