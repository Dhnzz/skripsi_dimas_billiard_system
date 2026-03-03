@props([
    'type' => 'common',
    'title',
    'icon',
    'value',
    'color' => 'primary',
    'filter' => false,
    'filterOptions' => [],
    'valueBind' => null,
])
@if ($type == 'slider')
    <li class="swiper-slide card card-slide position-relative" data-aos="fade-up" data-aos-delay="700">
        <div class="card-body">
            @if ($filter)
                <div class="dropdown position-absolute top-0 end-0 mt-3 me-3">
                    <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false" title="Filter Data">
                        <i class="fa-solid fa-filter"></i>
                        <small>Filter</small>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><button class="dropdown-item"
                                wire:click="filter{{ \Illuminate\Support\Str::studly($title) }}('all')">Semua</button>
                        </li>
                        @foreach ($filterOptions as $optionKey => $optionValue)
                            <li><button class="dropdown-item"
                                    wire:click="filter{{ \Illuminate\Support\Str::studly($title) }}('{{ $optionKey }}')">{{ $optionValue }}</button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="d-flex justify-content-between align-items-center mt-2">
                <div class="bg-{{ $color }} text-white rounded p-3">
                    {!! $icon !!}
                </div>
                <div class="text-end mt-2">
                    <span class="text-muted">{{ $title }}</span>
                    <h2 class="counter mb-0" style="visibility: visible;"
                        @if ($valueBind) x-data x-text="{{ $valueBind }}" @endif>{{ $value }}
                    </h2>
                </div>
            </div>
        </div>
    </li>
@else
    <div class="card position-relative">
        <div class="card-body">
            @if ($filter)
                <div class="dropdown position-absolute top-0 end-0 mt-3 me-3">
                    <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false"
                        title="Filter Data">
                        <i class="fa-solid fa-filter"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><button class="dropdown-item"
                                wire:click="filter{{ \Illuminate\Support\Str::studly($title) }}('all')">Semua</button>
                        </li>
                        <li><button class="dropdown-item"
                                wire:click="filter{{ \Illuminate\Support\Str::studly($title) }}('day')">Hari
                                ini</button></li>
                        <li><button class="dropdown-item"
                                wire:click="filter{{ \Illuminate\Support\Str::studly($title) }}('week')">Minggu
                                ini</button></li>
                        <li><button class="dropdown-item"
                                wire:click="filter{{ \Illuminate\Support\Str::studly($title) }}('month')">Bulan
                                ini</button></li>
                    </ul>
                </div>
            @endif
            <div class="d-flex justify-content-between align-items-center mt-2">
                <div class="bg-{{ $color }} text-white rounded p-3">
                    {!! $icon !!}
                </div>
                <div class="text-end mt-2">
                    <span class="text-muted">{{ $title }}</span>
                    <h2 class="counter mb-0" style="visibility: visible;"
                        @if ($valueBind) x-data x-text="{{ $valueBind }}" @endif>
                        {{ $value }}</h2>
                </div>
            </div>
        </div>
    </div>
@endif
