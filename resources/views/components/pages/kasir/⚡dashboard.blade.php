<?php

use App\Models\Billing;
use App\Models\Booking;
use App\Models\Table;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Carbon\Carbon;

new #[Layout('layouts.app', ['title' => 'Dashboard Kasir', 'breadcrumbs' => [
    ['title' => 'Home'],
    ['title' => 'Dashboard Kasir', 'url' => '#'],
]])] class extends Component {

    public int    $billingAktif       = 0;
    public int    $billingHariIni     = 0;
    public int    $billingSelesaiHari = 0;
    public float  $pendapatanHariIni  = 0;
    public int    $bookingPending     = 0;
    public int    $mejaAvailable      = 0;
    public int    $mejaOccupied       = 0;
    public int    $mejaMaint          = 0;

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $today = today();

        $this->billingAktif       = Billing::where('status', 'active')->count();
        $this->billingHariIni     = Billing::whereDate('started_at', $today)->count();
        $this->billingSelesaiHari = Billing::whereDate('ended_at', $today)->where('status', 'completed')->count();
        $this->pendapatanHariIni  = (float) Billing::whereDate('ended_at', $today)->where('status', 'completed')->sum('grand_total');
        $this->bookingPending     = Booking::where('status', 'pending')->count();

        $mejaAll = Table::where('is_active', true)->get();
        $this->mejaAvailable = $mejaAll->where('status', 'available')->count();
        $this->mejaOccupied  = $mejaAll->where('status', 'occupied')->count();
        $this->mejaMaint     = $mejaAll->where('status', 'maintenance')->count();
    }

    // Computed: meja aktif
    public function getMejaAllProperty()
    {
        return Table::where('is_active', true)->orderBy('table_number')->get();
    }

    // Computed: billing aktif
    public function getActiveBillingsProperty()
    {
        return Billing::where('status', 'active')
            ->with(['table', 'customer', 'package', 'pricing'])
            ->orderBy('started_at')
            ->get();
    }

    // Computed: booking pending
    public function getPendingBookingsProperty()
    {
        return Booking::where('status', 'pending')
            ->with(['customer', 'table', 'package'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    // Computed: booking mendatang
    public function getBookingMendatangProperty()
    {
        return Booking::where('status', 'confirmed')
            ->whereDate('scheduled_date', '>=', today())
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_start')
            ->with(['customer', 'table', 'package'])
            ->limit(5)
            ->get();
    }
};
?>

<div wire:poll.30s>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- BARIS 1 — STATISTIK HARI INI                         --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">

        {{-- Billing Aktif --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #198754 !important;">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:48px;height:48px;background:#d1fae5;">
                        <i class="fa-solid fa-circle-play text-success fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Billing Aktif</div>
                        <div class="fw-bold fs-3 lh-1 text-success">{{ $billingAktif }}</div>
                        <div class="text-muted" style="font-size:11px;">Sedang berjalan</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Billing Hari Ini --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #0d6efd !important;">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:48px;height:48px;background:#dbeafe;">
                        <i class="fa-solid fa-receipt text-primary fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Billing Hari Ini</div>
                        <div class="fw-bold fs-3 lh-1 text-primary">{{ $billingHariIni }}</div>
                        <div class="text-muted" style="font-size:11px;">{{ $billingSelesaiHari }} selesai</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pendapatan Hari Ini --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #f59e0b !important;">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:48px;height:48px;background:#fef3c7;">
                        <i class="fa-solid fa-money-bill-wave text-warning fa-lg"></i>
                    </div>
                    <div class="overflow-hidden">
                        <div class="text-muted small fw-medium">Pendapatan Hari Ini</div>
                        <div class="fw-bold fs-5 lh-1 text-warning text-truncate">
                            Rp {{ number_format($pendapatanHariIni, 0, ',', '.') }}
                        </div>
                        <div class="text-muted" style="font-size:11px;">Billing completed</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Booking Pending --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #ef4444 !important;">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:48px;height:48px;background:#fee2e2;">
                        <i class="fa-solid fa-clock-rotate-left text-danger fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Booking Pending</div>
                        <div class="fw-bold fs-3 lh-1 text-danger">{{ $bookingPending }}</div>
                        <div class="text-muted" style="font-size:11px;">Butuh konfirmasi</div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- BARIS 2 — STATUS MEJA (VISUAL GRID)                  --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold">
                <i class="fa-solid fa-table me-2 text-primary"></i>
                Status Meja Real-Time
            </h5>
            <div class="d-flex gap-3 align-items-center">
                <span class="d-flex align-items-center gap-1 small">
                    <span class="rounded-circle d-inline-block" style="width:10px;height:10px;background:#198754;"></span>
                    Tersedia ({{ $this->mejaAvailable }})
                </span>
                <span class="d-flex align-items-center gap-1 small">
                    <span class="rounded-circle d-inline-block" style="width:10px;height:10px;background:#ef4444;"></span>
                    Terpakai ({{ $this->mejaOccupied }})
                </span>
                <span class="d-flex align-items-center gap-1 small">
                    <span class="rounded-circle d-inline-block" style="width:10px;height:10px;background:#f59e0b;"></span>
                    Perawatan ({{ $this->mejaMaint }})
                </span>
            </div>
        </div>
        <div class="card-body">
            @if($this->mejaAll->isEmpty())
                <div class="text-center text-muted py-4">
                    <i class="fa-solid fa-table fa-2x mb-2 d-block opacity-30"></i>
                    <p class="small mb-0">Belum ada meja terdaftar.</p>
                </div>
            @else
                <div class="row g-3">
                    @foreach($this->mejaAll as $meja)
                        @php
                            $config = match($meja->status) {
                                'available'   => ['bg' => '#d1fae5', 'border' => '#6ee7b7', 'text' => '#065f46', 'badge' => 'bg-success',   'icon' => 'fa-check-circle',        'label' => 'Tersedia'],
                                'occupied'    => ['bg' => '#fee2e2', 'border' => '#fca5a5', 'text' => '#7f1d1d', 'badge' => 'bg-danger',    'icon' => 'fa-circle-xmark',        'label' => 'Terpakai'],
                                'maintenance' => ['bg' => '#fef9c3', 'border' => '#fde68a', 'text' => '#713f12', 'badge' => 'bg-warning text-dark', 'icon' => 'fa-screwdriver-wrench', 'label' => 'Perawatan'],
                                default       => ['bg' => '#f3f4f6', 'border' => '#d1d5db', 'text' => '#374151', 'badge' => 'bg-secondary', 'icon' => 'fa-circle-question',     'label' => $meja->status],
                            };
                            $activeBilling = $this->activeBillings->firstWhere('table_id', $meja->id);
                        @endphp
                        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                            <div class="rounded-3 text-center p-3 h-100 d-flex flex-column align-items-center justify-content-center"
                                style="background:{{ $config['bg'] }};border:2px solid {{ $config['border'] }};">
                                <i class="fa-solid {{ $config['icon'] }} fa-lg mb-2" style="color:{{ $config['text'] }};"></i>
                                <div class="fw-bold small" style="color:{{ $config['text'] }};">{{ $meja->name }}</div>
                                <div class="text-muted" style="font-size:10px;">No. {{ $meja->table_number }}</div>
                                <span class="badge {{ $config['badge'] }} mt-2 px-2" style="font-size:9px;">
                                    {{ $config['label'] }}
                                </span>
                                @if($activeBilling)
                                    <div class="mt-1 font-monospace" style="font-size:9px;color:{{ $config['text'] }};">
                                        {{ $activeBilling->elapsed_formatted }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="row g-4">

        {{-- ══════════════════════════════════════════════════════ --}}
        {{-- KOLOM KIRI — BILLING AKTIF                           --}}
        {{-- ══════════════════════════════════════════════════════ --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fa-solid fa-circle-play me-2 text-success"></i>
                        Billing Sedang Berjalan
                    </h5>
                    <a href="{{ route('kasir.billing.index') }}" class="btn btn-sm btn-outline-success" wire:navigate>
                        <i class="fa-solid fa-arrow-right me-1"></i> Semua Billing
                    </a>
                </div>
                @if($this->activeBillings->isEmpty())
                    <div class="card-body text-center py-5 text-muted">
                        <i class="fa-solid fa-circle-check fa-3x mb-3 d-block text-success opacity-50"></i>
                        <p class="fw-semibold mb-0">Tidak ada billing aktif saat ini</p>
                        <p class="small mt-1 mb-0">Semua meja sedang kosong.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Meja</th>
                                    <th>Pelanggan</th>
                                    <th>Mulai</th>
                                    <th class="text-center">Durasi</th>
                                    <th class="text-end">Total Sementara</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->activeBillings as $ab)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $ab->table?->name ?? '-' }}</div>
                                            <div class="text-muted small">{{ $ab->table?->table_number ?? '' }}</div>
                                        </td>
                                        <td>
                                            @if($ab->customer)
                                                <div class="fw-medium">{{ $ab->customer->name }}</div>
                                                <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:10px;">Member</span>
                                            @elseif($ab->guest_name)
                                                <div class="fw-medium">{{ $ab->guest_name }}</div>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:10px;">Walk-In</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="small">{{ $ab->started_at?->format('H:i') }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-success fw-bold font-monospace px-2">
                                                {{ $ab->elapsed_formatted }}
                                            </span>
                                            @if($ab->scheduled_end_at)
                                                <div class="small text-muted mt-1">
                                                    Selesai: {{ $ab->scheduled_end_at->format('H:i') }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold text-success">
                                            {{ $ab->formatted_current_total }}
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('kasir.billing.show', $ab->id) }}"
                                               class="btn btn-sm btn-outline-primary px-2 py-1" wire:navigate>
                                                <i class="fa-solid fa-receipt me-1"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════ --}}
        {{-- KOLOM KANAN (2 CARD VERTIKAL)                        --}}
        {{-- ══════════════════════════════════════════════════════ --}}
        <div class="col-lg-4 d-flex flex-column gap-4">

            {{-- Booking Pending --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fa-solid fa-hourglass-half me-2 text-danger"></i>
                        Perlu Dikonfirmasi
                        @if($bookingPending > 0)
                            <span class="badge bg-danger ms-1">{{ $bookingPending }}</span>
                        @endif
                    </h6>
                    <a href="{{ route('kasir.booking.index') }}" class="btn btn-sm btn-outline-danger" wire:navigate>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
                @if($this->pendingBookings->isEmpty())
                    <div class="card-body text-center py-4 text-muted">
                        <i class="fa-solid fa-circle-check fa-2x mb-2 d-block text-success opacity-50"></i>
                        <p class="small mb-0">Tidak ada booking pending.</p>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($this->pendingBookings as $pb)
                            <a href="{{ route('kasir.booking.show', $pb->id) }}"
                               class="list-group-item list-group-item-action py-2 px-3" wire:navigate>
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-medium small">{{ $pb->customer?->name ?? '-' }}</div>
                                        <div class="text-muted" style="font-size:11px;">
                                            Meja {{ $pb->table?->table_number ?? '?' }}
                                            · {{ $pb->scheduled_date?->format('d M') }}
                                            · {{ \Carbon\Carbon::parse($pb->scheduled_start)->format('H:i') }}
                                        </div>
                                    </div>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Booking Mendatang --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fa-solid fa-calendar-day me-2 text-primary"></i>
                        Booking Mendatang
                    </h6>
                    <a href="{{ route('kasir.booking.index') }}" class="btn btn-sm btn-outline-primary" wire:navigate>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
                @if($this->bookingMendatang->isEmpty())
                    <div class="card-body text-center py-4 text-muted">
                        <i class="fa-solid fa-calendar-xmark fa-2x mb-2 d-block opacity-30"></i>
                        <p class="small mb-0">Tidak ada booking terkonfirmasi.</p>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($this->bookingMendatang as $bm)
                            <a href="{{ route('kasir.booking.show', $bm->id) }}"
                               class="list-group-item list-group-item-action py-2 px-3" wire:navigate>
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-medium small">{{ $bm->customer?->name ?? '-' }}</div>
                                        <div class="text-muted" style="font-size:11px;">
                                            Meja {{ $bm->table?->table_number ?? '?' }}
                                            · {{ $bm->scheduled_date?->format('d M') }}
                                            · {{ \Carbon\Carbon::parse($bm->scheduled_start)->format('H:i') }}
                                            @if($bm->scheduled_end)
                                                – {{ \Carbon\Carbon::parse($bm->scheduled_end)->format('H:i') }}
                                            @endif
                                        </div>
                                        @if($bm->package)
                                            <div style="font-size:10px;" class="text-primary">
                                                {{ $bm->package->name }}
                                            </div>
                                        @endif
                                    </div>
                                    <span class="badge bg-success">Terkonfirmasi</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Jam terakhir refresh --}}
    <div class="text-muted text-end mt-3" style="font-size:11px;">
        <i class="fa-solid fa-arrows-rotate me-1"></i>
        Auto-refresh setiap 30 detik · Terakhir: {{ now()->format('H:i:s') }}
    </div>

</div>
