<?php

use App\Models\Table;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app', ['title' => 'Owner Dashboard', 'breadcrumbs' => [['title' => 'Dashboard', 'url' => '#']]])] class extends Component {
    public $kasirCount = 0;
    public $memberCount = 0;
    public $tableCount = 0;

    public function mount()
    {
        $this->kasirCount = User::role('kasir')->count();
        $this->memberCount = User::role('member')->count();
        $this->tableCount = Table::count();
    }

    public function filterJumlahMeja($value)
    {
        if ($value === 'all') {
            $this->tableCount = Table::when($value === 'all', fn($query) => $query->count());
        } else {
            $this->tableCount = Table::when($value !== 'all', fn($query) => $query->where('status', $value))->count();
        }
    }
};
?>

<div>
    <div class="row">
        <div class="col-md-12 col-lg-12">
            <div class="row row-cols-1">
                <div class="overflow-hidden d-slider1" wire:ignore>
                    <ul class="p-0 m-0 mb-2 swiper-wrapper list-inline">
                        <x-partials.card type="slider" title="Jumlah Kasir" color="primary"
                            icon="<i class='fa-solid fa-user-tie'></i>" value="{{ $kasirCount }}" />
                        <x-partials.card type="slider" title="Jumlah Member" color="warning"
                            icon="<i class='fa-solid fa-users'></i>" value="{{ $memberCount }}" />
                        <x-partials.card type="slider" title="Jumlah Meja" color="success"
                            icon="<i class='fa-solid fa-table'></i>" value="{{ $tableCount }}" filter="true"
                            :filterOptions="[
                                'occupied' => 'Terpakai',
                                'maintenance' => 'Perawatan',
                                'available' => 'Tersedia',
                            ]" valueBind="$wire.tableCount" />
                    </ul>
                    <div class="swiper-button swiper-button-next"></div>
                    <div class="swiper-button swiper-button-prev"></div>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-lg-8">
            <div class="row">
                <div class="col-md-12 col-lg-12">
                    <div class="overflow-hidden card" data-aos="fade-up" data-aos-delay="600" wire:ignore>
                        <div class="flex-wrap card-header d-flex justify-content-between">
                            <div class="header-title">
                                <h4 class="mb-2 card-title">Enterprise Clients</h4>
                                <p class="mb-0">
                                    <svg class="me-2 text-primary icon-24" width="24" viewBox="0 0 24 24">
                                        <path fill="currentColor"
                                            d="M21,7L9,19L3.5,13.5L4.91,12.09L9,16.17L19.59,5.59L21,7Z" />
                                    </svg>
                                    15 new acquired this month
                                </p>
                            </div>
                        </div>
                        <div class="p-0 card-body">
                            <div class="mt-4 table-responsive">
                                <table id="basic-table" class="table mb-0 table-striped" role="grid">
                                    <thead>
                                        <tr>
                                            <th>COMPANIES</th>
                                            <th>CONTACTS</th>
                                            <th>ORDER</th>
                                            <th>COMPLETION</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img class="rounded bg-soft-primary img-fluid avatar-40 me-3"
                                                        src="{{ asset('dashboard_asset/images/shapes/01.png') }}"
                                                        alt="profile">
                                                    <h6>Addidis Sportwear</h6>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="iq-media-group iq-media-group-1">
                                                    <a href="#" class="iq-media-1">
                                                        <div class="icon iq-icon-box-3 rounded-pill">SP</div>
                                                    </a>
                                                    <a href="#" class="iq-media-1">
                                                        <div class="icon iq-icon-box-3 rounded-pill">PP</div>
                                                    </a>
                                                    <a href="#" class="iq-media-1">
                                                        <div class="icon iq-icon-box-3 rounded-pill">MM</div>
                                                    </a>
                                                </div>
                                            </td>
                                            <td>$14,000</td>
                                            <td>
                                                <div class="mb-2 d-flex align-items-center">
                                                    <h6>60%</h6>
                                                </div>
                                                <div class="shadow-none progress bg-soft-primary w-100"
                                                    style="height: 4px">
                                                    <div class="progress-bar bg-primary" data-toggle="progress-bar"
                                                        role="progressbar" aria-valuenow="60" aria-valuemin="0"
                                                        aria-valuemax="100"></div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-lg-4">
            <div class="row">
                <div class="col-md-12 col-lg-12">
                    <div class="card" data-aos="fade-up" data-aos-delay="600" wire:ignore>
                        <div class="flex-wrap card-header d-flex justify-content-between">
                            <div class="header-title">
                                <h4 class="mb-2 card-title">Activity overview</h4>
                                <p class="mb-0">
                                    <svg class="me-2 icon-24" width="24" height="24" viewBox="0 0 24 24">
                                        <path fill="#17904b"
                                            d="M13,20H11V8L5.5,13.5L4.08,12.08L12,4.16L19.92,12.08L18.5,13.5L13,8V20Z" />
                                    </svg>
                                    16% this month
                                </p>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-2  d-flex profile-media align-items-top">
                                <div class="mt-1 profile-dots-pills border-primary"></div>
                                <div class="ms-4">
                                    <h6 class="mb-1 ">$2400, Purchase</h6>
                                    <span class="mb-0">11 JUL 8:10 PM</span>
                                </div>
                            </div>
                            <div class="mb-2  d-flex profile-media align-items-top">
                                <div class="mt-1 profile-dots-pills border-primary"></div>
                                <div class="ms-4">
                                    <h6 class="mb-1 ">New order #8744152</h6>
                                    <span class="mb-0">11 JUL 11 PM</span>
                                </div>
                            </div>
                            <div class="mb-2  d-flex profile-media align-items-top">
                                <div class="mt-1 profile-dots-pills border-primary"></div>
                                <div class="ms-4">
                                    <h6 class="mb-1 ">Affiliate Payout</h6>
                                    <span class="mb-0">11 JUL 7:64 PM</span>
                                </div>
                            </div>
                            <div class="mb-2  d-flex profile-media align-items-top">
                                <div class="mt-1 profile-dots-pills border-primary"></div>
                                <div class="ms-4">
                                    <h6 class="mb-1 ">New user added</h6>
                                    <span class="mb-0">11 JUL 1:21 AM</span>
                                </div>
                            </div>
                            <div class="mb-1  d-flex profile-media align-items-top">
                                <div class="mt-1 profile-dots-pills border-primary"></div>
                                <div class="ms-4">
                                    <h6 class="mb-1 ">Product added</h6>
                                    <span class="mb-0">11 JUL 4:50 AM</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:navigated', () => {
            if (typeof jQuery !== 'undefined' && jQuery('.d-slider1').length > 0) {
                const options = {
                    centeredSlides: false,
                    loop: false,
                    slidesPerView: 4,
                    autoplay: false,
                    spaceBetween: 32,
                    breakpoints: {
                        320: {
                            slidesPerView: 1
                        },
                        550: {
                            slidesPerView: 2
                        },
                        991: {
                            slidesPerView: 3
                        },
                        1400: {
                            slidesPerView: 3
                        },
                        1500: {
                            slidesPerView: 4
                        },
                        1920: {
                            slidesPerView: 6
                        },
                        2040: {
                            slidesPerView: 7
                        },
                        2440: {
                            slidesPerView: 8
                        }
                    },
                    pagination: {
                        el: '.swiper-pagination'
                    },
                    navigation: {
                        nextEl: '.swiper-button-next',
                        prevEl: '.swiper-button-prev'
                    },
                    scrollbar: {
                        el: '.swiper-scrollbar'
                    }
                };
                new Swiper('.d-slider1', options);
            }
        });
    </script>
@endpush
