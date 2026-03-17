<?php

use App\Models\Billing;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search      = '';
    public $filterStatus = '';
    public $perPage     = 10;
    public $sortBy      = 'created_at';
    public $sortDir     = 'desc';

    public function updatingSearch()   { $this->resetPage(); }
    public function updatingPerPage()  { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }

    public function setSortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
            return;
        }
        $this->sortBy  = $field;
        $this->sortDir = 'asc';
    }

    #[Computed]
    public function billings()
    {
        $query = Billing::with(['customer', 'table', 'package', 'pricing', 'booking']);

        // Filter dropdown status
        if (!empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        // Search
        if (!empty($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('billing_code', 'like', '%' . $search . '%')
                  ->orWhere('guest_name', 'like', '%' . $search . '%')
                  ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', '%' . $search . '%'))
                  ->orWhereHas('table', fn($tq) => $tq->where('table_number', 'like', '%' . $search . '%'));
            });
        }

        return $query->orderBy($this->sortBy, $this->sortDir)->paginate($this->perPage);
    }

    #[Computed]
    public function statusCounts()
    {
        return [
            'all'       => Billing::count(),
            'active'    => Billing::where('status', 'active')->count(),
            'completed' => Billing::where('status', 'completed')->count(),
        ];
    }
};
?>

<div>
    {{-- Summary Cards --}}
    <div class="row mb-3 g-3">
        @php
            $cardItems = [
                ['label' => 'Semua',        'key' => '',          'color' => 'secondary', 'icon' => 'fa-list'],
                ['label' => 'Aktif',        'key' => 'active',    'color' => 'success',   'icon' => 'fa-play'],
                ['label' => 'Selesai',      'key' => 'completed', 'color' => 'primary',   'icon' => 'fa-flag-checkered'],
            ];
        @endphp
        @foreach ($cardItems as $item)
            <div class="col-6 col-md-4">
                <div class="card mb-0 h-100 border-0 shadow-sm {{ $filterStatus === $item['key'] ? 'bg-' . $item['color'] . ' text-white' : '' }}"
                    style="cursor:pointer;"
                    wire:click="$set('filterStatus', '{{ $item['key'] }}')">
                    <div class="card-body p-3 text-center">
                        <i class="fa-solid {{ $item['icon'] }} fa-lg mb-1 {{ $filterStatus === $item['key'] ? 'text-white' : 'text-' . $item['color'] }}"></i>
                        <div class="fw-bold fs-5">
                            {{ $item['key'] === '' ? $this->statusCounts['all'] : ($this->statusCounts[$item['key']] ?? 0) }}
                        </div>
                        <div class="small {{ $filterStatus === $item['key'] ? 'text-white' : 'text-muted' }}">{{ $item['label'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="overflow-hidden card">
                <div class="flex-wrap card-header d-flex justify-content-between align-items-center">
                    <div class="header-title">
                        <h4 class="mb-0 card-title">Daftar Billing</h4>
                    </div>
                    <div>
                        <a href="{{ request()->routeIs('owner.*') ? route('owner.billing.create') : route('kasir.billing.create') }}"
                           class="btn btn-primary btn-sm" wire:navigate>
                            <i class="fa-solid fa-plus me-1"></i> Tambah Billing Manual (Walk-In)
                        </a>
                    </div>
                </div>
                <div class="p-0 card-body">
                    <div class="d-flex justify-content-between align-items-center p-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="me-1">Tampilkan</span>
                            <select wire:model.live="perPage" class="form-select form-select-sm w-auto">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                            <span>Entri</span>
                        </div>
                        <div>
                            <input type="text" wire:model.live.debounce.300ms="search"
                                class="form-control form-control-sm" placeholder="Cari kode billing, member, meja...">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table mb-0 table-striped text-center align-middle" role="grid">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th wire:click="setSortBy('billing_code')" style="cursor:pointer;">
                                        Kode
                                        @if ($sortBy !== 'billing_code') <i class="fa-solid fa-sort ms-1 text-muted"></i>
                                        @elseif($sortDir === 'asc') <i class="fa-solid fa-sort-up ms-1"></i>
                                        @else <i class="fa-solid fa-sort-down ms-1"></i>
                                        @endif
                                    </th>
                                    <th>Meja</th>
                                    <th>Member</th>
                                    <th>Mulai</th>
                                    <th>Selesai</th>
                                    <th>Total & Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->billings as $billing)
                                    <tr>
                                        <td>{{ $this->billings->firstItem() + $loop->index }}</td>
                                        <td>
                                            <span class="fw-medium font-monospace">{{ $billing->billing_code }}</span>
                                        </td>
                                        <td>Meja {{ $billing->table?->table_number ?? '-' }}</td>
                                        <td>
                                            <div class="fw-medium text-start">
                                                @if($billing->customer)
                                                    <i class="fa-solid fa-user me-1 text-muted" style="font-size:11px;"></i>
                                                    {{ $billing->customer->name }}
                                                @elseif($billing->guest_name)
                                                    <i class="fa-solid fa-person-walking me-1 text-muted" style="font-size:11px;"></i>
                                                    {{ $billing->guest_name }}
                                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-1" style="font-size:10px;">Walk-In</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>{{ $billing->started_at?->format('d M y H:i') }}</td>
                                        <td>
                                            {{ $billing->isActive() ? 'Sedang Berjalan' : $billing->ended_at?->format('d M y H:i') }}
                                        </td>
                                        <td>
                                            <div class="fw-bold text-success">{{ $billing->formatted_current_total }}</div>
                                            <div class="small text-muted">{{ $billing->isActive() ? 'Berjalan...' : ($billing->ended_at?->diffForHumans() ?? '-') }}</div>
                                            <span class="badge {{ $billing->isActive() ? 'bg-success' : 'bg-primary' }}">
                                                {{ $billing->isActive() ? 'Aktif' : 'Selesai' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 justify-content-center flex-wrap">
                                                {{-- Tombol Detail Billing (selalu tampil) --}}
                                                <a class="btn btn-sm btn-icon btn-primary rounded-circle"
                                                    title="Detail Billing"
                                                    href="{{ request()->routeIs('owner.*') ? route('owner.billing.show', $billing->id) : route('kasir.billing.show', $billing->id) }}" wire:navigate>
                                                    <span class="btn-inner"><i class="fa-solid fa-receipt"></i></span>
                                                </a>
                                                {{-- Tombol ke Halaman Booking (hanya jika punya booking) --}}
                                                @if($billing->booking_id)
                                                    <a class="btn btn-sm btn-icon btn-outline-info rounded-circle"
                                                        title="Lihat Detail Booking"
                                                        href="{{ request()->routeIs('owner.*') ? route('owner.booking.show', $billing->booking_id) : route('kasir.booking.show', $billing->booking_id) }}" wire:navigate>
                                                        <span class="btn-inner"><i class="fa-solid fa-calendar-check"></i></span>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            Tidak ada data billing.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="p-3 pb-1 d-flex justify-content-end">
                            {{ $this->billings->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
