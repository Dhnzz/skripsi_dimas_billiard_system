<?php

use App\Models\Billing;
use App\Models\Booking;
use App\Models\Table;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Carbon\Carbon;

new #[Layout('layouts.app', ['title' => 'Dashboard Owner', 'breadcrumbs' => [
    ['title' => 'Home'],
    ['title' => 'Dashboard', 'url' => '#'],
]])] class extends Component {

    // ── Statistik Publik ─────────────────────────────────────
    public int   $kasirCount       = 0;
    public int   $memberCount      = 0;
    public int   $tableCount       = 0;
    public int   $billingAktif     = 0;
    public int   $billingHariIni   = 0;
    public int   $bookingPending   = 0;
    public float $pendapatanHariIni  = 0;
    public float $pendapatanMingguIni = 0;
    public float $pendapatanBulanIni  = 0;
    public int   $mejaAvailable    = 0;
    public int   $mejaOccupied     = 0;
    public int   $mejaMaint        = 0;

    public function mount(): void
    {
        $today = today();
        $this->kasirCount     = User::role('kasir')->count();
        $this->memberCount    = User::role('member')->count();
        $this->tableCount     = Table::where('is_active', true)->count();
        $this->billingAktif   = Billing::where('status', 'active')->count();
        $this->billingHariIni = Billing::whereDate('started_at', $today)->count();
        $this->bookingPending = Booking::where('status', 'pending')->count();

        $this->pendapatanHariIni   = (float) Billing::whereDate('ended_at', $today)->where('status', 'completed')->sum('grand_total');
        $this->pendapatanMingguIni = (float) Billing::whereBetween('ended_at', [now()->startOfWeek(), now()->endOfWeek()])->where('status', 'completed')->sum('grand_total');
        $this->pendapatanBulanIni  = (float) Billing::whereMonth('ended_at', now()->month)->whereYear('ended_at', now()->year)->where('status', 'completed')->sum('grand_total');

        $mejaAll = Table::where('is_active', true)->get();
        $this->mejaAvailable = $mejaAll->where('status', 'available')->count();
        $this->mejaOccupied  = $mejaAll->where('status', 'occupied')->count();
        $this->mejaMaint     = $mejaAll->where('status', 'maintenance')->count();
    }

    // ── Computed Properties ──────────────────────────────────
    public function getMejaAllProperty()
    {
        return Table::where('is_active', true)->orderBy('table_number')->get();
    }

    public function getActiveBillingsProperty()
    {
        return Billing::where('status', 'active')
            ->with(['table', 'customer'])
            ->orderBy('started_at')
            ->get();
    }

    public function getPendingBookingsProperty()
    {
        return Booking::where('status', 'pending')
            ->with(['customer', 'table'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    public function getRecentCompletedProperty()
    {
        return Billing::where('status', 'completed')
            ->with(['table', 'customer'])
            ->orderBy('ended_at', 'desc')
            ->limit(5)
            ->get();
    }

    /** Pendapatan per hari untuk 7 hari terakhir */
    public function getPendapatanChartProperty(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = today()->subDays($i);
            $total = Billing::whereDate('ended_at', $d)->where('status', 'completed')->sum('grand_total');
            $data[] = [
                'label' => $d->format('d/m'),
                'value' => (float) $total,
            ];
        }
        return $data;
    }
};
?>

<div wire:poll.60s>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- BARIS 1 — 4 KPI UTAMA                                --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">

        {{-- Pendapatan Hari Ini --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#198754;"></div>
                <div class="card-body ps-4 py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width:46px;height:46px;background:#d1fae5;">
                            <i class="fa-solid fa-money-bill-wave text-success"></i>
                        </div>
                        <div class="overflow-hidden">
                            <div class="text-muted small fw-medium">Pendapatan Hari Ini</div>
                            <div class="fw-bold fs-5 lh-1 text-success text-truncate">
                                Rp {{ number_format($pendapatanHariIni, 0, ',', '.') }}
                            </div>
                            <div class="text-muted" style="font-size:10px;">
                                Bulan ini: Rp {{ number_format($pendapatanBulanIni, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Billing Aktif --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#0d6efd;"></div>
                <div class="card-body ps-4 py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width:46px;height:46px;background:#dbeafe;">
                            <i class="fa-solid fa-circle-play text-primary"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-medium">Billing Aktif</div>
                            <div class="fw-bold fs-3 lh-1 text-primary">{{ $billingAktif }}</div>
                            <div class="text-muted" style="font-size:10px;">{{ $billingHariIni }} billing hari ini</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status Meja --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#f59e0b;"></div>
                <div class="card-body ps-4 py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width:46px;height:46px;background:#fef3c7;">
                            <i class="fa-solid fa-table-tennis-paddle-ball text-warning"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-medium">Status Meja</div>
                            <div class="fw-bold fs-3 lh-1 text-warning">{{ $mejaOccupied }}/{{ $tableCount }}</div>
                            <div class="text-muted" style="font-size:10px;">
                                {{ $mejaAvailable }} tersedia · {{ $mejaMaint }} perawatan
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pengguna --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden">
                <div class="position-absolute top-0 start-0 h-100" style="width:4px;background:#8b5cf6;"></div>
                <div class="card-body ps-4 py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width:46px;height:46px;background:#ede9fe;">
                            <i class="fa-solid fa-users" style="color:#8b5cf6;"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-medium">Pengguna Terdaftar</div>
                            <div class="fw-bold fs-3 lh-1" style="color:#8b5cf6;">{{ $memberCount }}</div>
                            <div class="text-muted" style="font-size:10px;">{{ $kasirCount }} kasir aktif</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- BARIS 2 — GRAFIK 7 HARI + STATUS MEJA GRID           --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="row g-4 mb-4">

        {{-- Grafik Pendapatan 7 Hari --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fa-solid fa-chart-bar me-2 text-success"></i>
                        Pendapatan 7 Hari Terakhir
                    </h5>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        Minggu ini: Rp {{ number_format($pendapatanMingguIni, 0, ',', '.') }}
                    </span>
                </div>
                <div class="card-body" wire:ignore>
                    @php $chartData = $this->pendapatanChart; @endphp
                    <div style="position:relative;height:220px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                    <script>
                        document.addEventListener('livewire:navigated', () => { initRevenueChart(); });
                        document.addEventListener('DOMContentLoaded', () => { initRevenueChart(); });
                        function initRevenueChart() {
                            const canvas = document.getElementById('revenueChart');
                            if (!canvas || !window.Chart) return;
                            // destroy existing
                            if (canvas._chartInstance) { canvas._chartInstance.destroy(); }
                            const labels = @json(array_column($chartData, 'label'));
                            const values = @json(array_column($chartData, 'value'));
                            canvas._chartInstance = new Chart(canvas.getContext('2d'), {
                                type: 'bar',
                                data: {
                                    labels: labels,
                                    datasets: [{
                                        label: 'Pendapatan (Rp)',
                                        data: values,
                                        backgroundColor: 'rgba(25, 135, 84, 0.75)',
                                        borderRadius: 6,
                                        borderSkipped: false,
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: { legend: { display: false } },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                callback: v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v),
                                                font: { size: 10 }
                                            },
                                            grid: { color: 'rgba(0,0,0,.05)' }
                                        },
                                        x: { grid: { display: false } }
                                    }
                                }
                            });
                        }
                        initRevenueChart();
                    </script>
                </div>
            </div>
        </div>

        {{-- Status Meja Grid --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fa-solid fa-table me-2 text-primary"></i>
                        Status Meja
                    </h5>
                    <a href="{{ route('owner.meja.index') }}" class="btn btn-sm btn-outline-primary" wire:navigate>
                        <i class="fa-solid fa-cog me-1"></i> Kelola
                    </a>
                </div>
                <div class="card-body">
                    @if($this->mejaAll->isEmpty())
                        <div class="text-center text-muted py-4">
                            <i class="fa-solid fa-table fa-2x mb-2 d-block opacity-30"></i>
                            <p class="small mb-0">Belum ada meja.</p>
                        </div>
                    @else
                        <div class="row g-2">
                            @foreach($this->mejaAll as $meja)
                                @php
                                    $cfg = match($meja->status) {
                                        'available'   => ['bg'=>'#d1fae5','brd'=>'#6ee7b7','txt'=>'#065f46','lbl'=>'Tersedia'],
                                        'occupied'    => ['bg'=>'#fee2e2','brd'=>'#fca5a5','txt'=>'#7f1d1d','lbl'=>'Terpakai'],
                                        'maintenance' => ['bg'=>'#fef9c3','brd'=>'#fde68a','txt'=>'#713f12','lbl'=>'Perawatan'],
                                        default       => ['bg'=>'#f3f4f6','brd'=>'#d1d5db','txt'=>'#374151','lbl'=>$meja->status],
                                    };
                                    $ab = $this->activeBillings->firstWhere('table_id', $meja->id);
                                @endphp
                                <div class="col-4">
                                    <div class="rounded-3 text-center py-2 px-1"
                                        style="background:{{ $cfg['bg'] }};border:1.5px solid {{ $cfg['brd'] }};">
                                        <div class="fw-bold small lh-1" style="color:{{ $cfg['txt'] }};">
                                            {{ $meja->name }}
                                        </div>
                                        <div style="font-size:9px;color:{{ $cfg['txt'] }};" class="mb-1">
                                            No.{{ $meja->table_number }}
                                        </div>
                                        <span style="font-size:9px;" class="badge
                                            {{ $meja->status === 'available' ? 'bg-success' : ($meja->status === 'occupied' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                            {{ $cfg['lbl'] }}
                                        </span>
                                        @if($ab)
                                            <div style="font-size:9px;color:{{ $cfg['txt'] }};" class="mt-1 font-monospace">
                                                {{ $ab->elapsed_formatted }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- BARIS 3 — BILLING AKTIF + RINGKASAN KANAN            --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="row g-4">

        {{-- Billing Aktif --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fa-solid fa-circle-play me-2 text-success"></i>
                        Billing Sedang Berjalan
                    </h5>
                    <a href="{{ route('owner.billing.index') }}" class="btn btn-sm btn-outline-success" wire:navigate>
                        <i class="fa-solid fa-arrow-right me-1"></i> Semua Billing
                    </a>
                </div>
                @if($this->activeBillings->isEmpty())
                    <div class="card-body text-center py-5 text-muted">
                        <i class="fa-solid fa-circle-check fa-3x mb-3 d-block text-success opacity-50"></i>
                        <p class="fw-semibold mb-0">Tidak ada billing aktif saat ini</p>
                        <p class="small mb-0">Semua meja sedang kosong.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Meja</th>
                                    <th>Pelanggan</th>
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
                                            <div class="text-muted small">No. {{ $ab->table?->table_number }}</div>
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
                                        <td class="text-center">
                                            <span class="badge bg-success fw-bold font-monospace">
                                                {{ $ab->elapsed_formatted }}
                                            </span>
                                            @if($ab->scheduled_end_at)
                                                <div class="text-muted small mt-1" style="font-size:10px;">
                                                    s/d {{ $ab->scheduled_end_at->format('H:i') }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold text-success">
                                            {{ $ab->formatted_current_total }}
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('owner.billing.show', $ab->id) }}"
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

        {{-- Kolom Kanan --}}
        <div class="col-lg-4 d-flex flex-column gap-4">

            {{-- Booking Pending --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fa-solid fa-hourglass-half me-2 text-warning"></i>
                        Booking Perlu Dikonfirmasi
                        @if($bookingPending > 0)
                            <span class="badge bg-danger ms-1">{{ $bookingPending }}</span>
                        @endif
                    </h6>
                    <a href="{{ route('owner.booking.index') }}" class="btn btn-sm btn-outline-warning" wire:navigate>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
                @if($this->pendingBookings->isEmpty())
                    <div class="card-body text-center py-3 text-muted">
                        <i class="fa-solid fa-circle-check fa-2x mb-2 d-block text-success opacity-50"></i>
                        <p class="small mb-0">Tidak ada booking pending.</p>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($this->pendingBookings as $pb)
                            <a href="{{ route('owner.booking.show', $pb->id) }}"
                               class="list-group-item list-group-item-action py-2 px-3" wire:navigate>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-medium small">{{ $pb->customer?->name ?? 'Walk-In' }}</div>
                                        <div class="text-muted" style="font-size:10px;">
                                            Meja {{ $pb->table?->table_number ?? '?' }}
                                            · {{ $pb->scheduled_date?->format('d M') }}
                                        </div>
                                    </div>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Billing Terakhir Selesai --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fa-solid fa-check-circle me-2 text-primary"></i>
                        Baru Saja Selesai
                    </h6>
                    <a href="{{ route('owner.billing.index') }}" class="btn btn-sm btn-outline-primary" wire:navigate>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
                @if($this->recentCompleted->isEmpty())
                    <div class="card-body text-center py-3 text-muted">
                        <i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-30"></i>
                        <p class="small mb-0">Belum ada billing selesai.</p>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($this->recentCompleted as $rc)
                            <a href="{{ route('owner.billing.show', $rc->id) }}"
                               class="list-group-item list-group-item-action py-2 px-3" wire:navigate>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-medium small">
                                            {{ $rc->customer?->name ?? $rc->guest_name ?? 'Walk-In' }}
                                        </div>
                                        <div class="text-muted" style="font-size:10px;">
                                            {{ $rc->table?->name }} · {{ $rc->ended_at?->diffForHumans() }}
                                        </div>
                                    </div>
                                    <span class="fw-bold text-success small">
                                        {{ $rc->formatted_current_total }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Refresh indicator --}}
    <div class="text-muted text-end mt-3" style="font-size:11px;">
        <i class="fa-solid fa-arrows-rotate me-1"></i>
        Auto-refresh setiap 60 detik · {{ now()->format('H:i:s') }} WIT
    </div>

</div>
