<?php

use App\Models\Booking;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app', ['title' => 'Manajemen Booking', 'breadcrumbs' => [['title' => 'Monitoring'], ['title' => 'Booking', 'url' => '#']]])] class extends Component {
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search      = '';
    public $filterStatus = '';
    public $perPage     = 10;
    public $sortBy      = 'created_at';
    public $sortDir     = 'desc';

    // Mapping label status → enum value
    protected array $statusMap = [
        'pending'   => 'pending',
        'dikonfirmasi' => 'confirmed',
        'confirmed' => 'confirmed',
        'ditolak'   => 'rejected',
        'rejected'  => 'rejected',
        'dibatalkan'=> 'cancelled',
        'cancelled' => 'cancelled',
        'selesai'   => 'completed',
        'completed' => 'completed',
    ];

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
    public function bookings()
    {
        $query = Booking::with(['customer', 'table', 'package', 'pricing']);

        // Filter dropdown status
        if (!empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        // Search
        if (!empty($this->search)) {
            $search      = $this->search;
            $searchLower = strtolower($search);

            // Cari status yang cocok dari kata kunci
            $matchedStatus = null;
            foreach ($this->statusMap as $label => $value) {
                if (str_contains($label, $searchLower)) {
                    $matchedStatus = $value;
                    break;
                }
            }

            $query->where(function ($q) use ($search, $matchedStatus) {
                $q->where('booking_code', 'like', '%' . $search . '%')
                  ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', '%' . $search . '%'))
                  ->orWhereHas('table', fn($tq) => $tq->where('table_number', 'like', '%' . $search . '%'));

                if ($matchedStatus) {
                    $q->orWhere('status', $matchedStatus);
                }
            });
        }

        return $query->orderBy($this->sortBy, $this->sortDir)->paginate($this->perPage);
    }

    #[Computed]
    public function statusCounts()
    {
        return [
            'all'       => Booking::count(),
            'pending'   => Booking::where('status', 'pending')->count(),
            'confirmed' => Booking::where('status', 'confirmed')->count(),
            'rejected'  => Booking::where('status', 'rejected')->count(),
            'cancelled' => Booking::where('status', 'cancelled')->count(),
            'completed' => Booking::where('status', 'completed')->count(),
        ];
    }

    public function mount()
    {
        if (session()->has('success')) {
            $this->dispatch('notify', message: session('success'), type: 'success');
        }
    }

    public function confirmBooking($id)
    {
        $booking = Booking::findOrFail($id);
        if (!$booking->isPending()) {
            $this->dispatch('notify', message: 'Booking sudah tidak berstatus pending.', type: 'error');
            return;
        }
        $booking->update([
            'status'       => 'confirmed',
            'confirmed_by' => auth()->id(),
            'confirmed_at' => now(),
        ]);

        // Jika booking untuk hari ini → langsung tandai meja occupied
        if ($booking->scheduled_date?->isToday()) {
            $booking->table?->update(['status' => 'occupied']);
        }

        $this->dispatch('notify', message: "Booking {$booking->booking_code} berhasil dikonfirmasi!", type: 'success');
    }

    public function rejectBooking($id, $reason = null)
    {
        $booking = Booking::findOrFail($id);
        if (!$booking->isPending()) {
            $this->dispatch('notify', message: 'Booking sudah tidak berstatus pending.', type: 'error');
            return;
        }
        $booking->update([
            'status'          => 'rejected',
            'rejected_reason' => $reason,
        ]);
        $this->dispatch('notify', message: "Booking {$booking->booking_code} berhasil ditolak.", type: 'success');
    }
};
?>

<div>
    {{-- Summary Cards --}}
    <div class="row mb-3 g-3">
        @php
            $cardItems = [
                ['label' => 'Semua',        'key' => '',          'color' => 'secondary', 'icon' => 'fa-list'],
                ['label' => 'Menunggu',     'key' => 'pending',   'color' => 'warning',   'icon' => 'fa-clock'],
                ['label' => 'Dikonfirmasi', 'key' => 'confirmed', 'color' => 'success',   'icon' => 'fa-circle-check'],
                ['label' => 'Ditolak',      'key' => 'rejected',  'color' => 'danger',    'icon' => 'fa-ban'],
                ['label' => 'Dibatalkan',   'key' => 'cancelled', 'color' => 'dark',      'icon' => 'fa-xmark'],
                ['label' => 'Selesai',      'key' => 'completed', 'color' => 'primary',   'icon' => 'fa-flag-checkered'],
            ];
        @endphp
        @foreach ($cardItems as $item)
            <div class="col-6 col-md-4 col-lg-2">
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
                        <h4 class="mb-0 card-title">Daftar Booking</h4>
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
                                class="form-control form-control-sm" placeholder="Cari kode, nama member, meja...">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table mb-0 table-striped" role="grid">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th wire:click="setSortBy('booking_code')" style="cursor:pointer;">
                                        Kode Booking
                                        @if ($sortBy !== 'booking_code') <i class="fa-solid fa-sort ms-1 text-muted"></i>
                                        @elseif($sortDir === 'asc') <i class="fa-solid fa-sort-up ms-1"></i>
                                        @else <i class="fa-solid fa-sort-down ms-1"></i>
                                        @endif
                                    </th>
                                    <th>Member</th>
                                    <th>Meja</th>
                                    <th>Paket</th>
                                    <th wire:click="setSortBy('scheduled_date')" style="cursor:pointer;">
                                        Jadwal
                                        @if ($sortBy !== 'scheduled_date') <i class="fa-solid fa-sort ms-1 text-muted"></i>
                                        @elseif($sortDir === 'asc') <i class="fa-solid fa-sort-up ms-1"></i>
                                        @else <i class="fa-solid fa-sort-down ms-1"></i>
                                        @endif
                                    </th>
                                    <th wire:click="setSortBy('status')" style="cursor:pointer;">
                                        Status
                                        @if ($sortBy !== 'status') <i class="fa-solid fa-sort ms-1 text-muted"></i>
                                        @elseif($sortDir === 'asc') <i class="fa-solid fa-sort-up ms-1"></i>
                                        @else <i class="fa-solid fa-sort-down ms-1"></i>
                                        @endif
                                    </th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $statusBadge = [
                                        'pending'   => ['class' => 'bg-warning text-dark', 'label' => 'Menunggu'],
                                        'confirmed' => ['class' => 'bg-success',            'label' => 'Dikonfirmasi'],
                                        'rejected'  => ['class' => 'bg-danger',             'label' => 'Ditolak'],
                                        'cancelled' => ['class' => 'bg-secondary',          'label' => 'Dibatalkan'],
                                        'completed' => ['class' => 'bg-primary',            'label' => 'Selesai'],
                                    ];
                                @endphp
                                @forelse ($this->bookings as $booking)
                                    <tr>
                                        <td class="align-middle">{{ $this->bookings->firstItem() + $loop->index }}</td>
                                        <td class="align-middle">
                                            <span class="fw-medium font-monospace">{{ $booking->booking_code }}</span>
                                        </td>
                                        <td class="align-middle">
                                            <div class="fw-medium">{{ $booking->customer?->name ?? '-' }}</div>
                                            <small class="text-muted">{{ $booking->customer?->email }}</small>
                                        </td>
                                        <td class="align-middle">
                                            Meja {{ $booking->table?->table_number ?? '-' }}
                                        </td>
                                        <td class="align-middle">
                                            {{ $booking->package?->name ?? '-' }}
                                        </td>
                                        <td class="align-middle">
                                            <div class="fw-medium">
                                                {{ $booking->scheduled_date?->format('d M Y') ?? '-' }}
                                            </div>
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($booking->scheduled_start)->format('H:i') }}
                                                –
                                                {{ \Carbon\Carbon::parse($booking->scheduled_end)->format('H:i') }}
                                            </small>
                                        </td>
                                        <td class="align-middle">
                                            @php $badge = $statusBadge[$booking->status] ?? ['class' => 'bg-secondary', 'label' => $booking->status]; @endphp
                                            <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                        </td>
                                        <td class="text-center align-middle text-nowrap">
                                            {{-- Detail --}}
                                            <a class="btn btn-sm btn-icon btn-info rounded-circle"
                                                title="Lihat Detail"
                                                href="{{ auth()->user()->hasRole('owner') ? route('owner.booking.show', $booking->id) : route('kasir.booking.show', $booking->id) }}" wire:navigate>
                                                <span class="btn-inner"><i class="fa-solid fa-eye"></i></span>
                                            </a>

                                            {{-- Konfirmasi (hanya pending) --}}
                                            @if ($booking->isPending())
                                                <button class="btn btn-sm btn-icon btn-success rounded-circle"
                                                    title="Konfirmasi"
                                                    @click="
                                                        Swal.fire({
                                                            title: 'Konfirmasi Booking?',
                                                            text: 'Booking {{ $booking->booking_code }} akan dikonfirmasi.',
                                                            icon: 'question',
                                                            showCancelButton: true,
                                                            confirmButtonColor: '#198754',
                                                            cancelButtonColor: '#6c757d',
                                                            confirmButtonText: 'Ya, Konfirmasi!',
                                                            cancelButtonText: 'Batal'
                                                        }).then((result) => {
                                                            if (result.isConfirmed) {
                                                                $wire.confirmBooking({{ $booking->id }})
                                                            }
                                                        })
                                                    ">
                                                    <span class="btn-inner"><i class="fa-solid fa-check"></i></span>
                                                </button>
                                                <button class="btn btn-sm btn-icon btn-danger rounded-circle"
                                                    title="Tolak"
                                                    @click="
                                                        Swal.fire({
                                                            title: 'Tolak Booking?',
                                                            input: 'textarea',
                                                            inputLabel: 'Alasan Penolakan',
                                                            inputPlaceholder: 'Isi alasan penolakan (opsional)...',
                                                            icon: 'warning',
                                                            showCancelButton: true,
                                                            confirmButtonColor: '#dc3545',
                                                            cancelButtonColor: '#6c757d',
                                                            confirmButtonText: 'Ya, Tolak!',
                                                            cancelButtonText: 'Batal'
                                                        }).then((result) => {
                                                            if (result.isConfirmed) {
                                                                $wire.rejectBooking({{ $booking->id }}, result.value || null)
                                                            }
                                                        })
                                                    ">
                                                    <span class="btn-inner"><i class="fa-solid fa-xmark"></i></span>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            Tidak ada data booking yang ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="p-3 pb-1 d-flex justify-content-end">
                            {{ $this->bookings->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>