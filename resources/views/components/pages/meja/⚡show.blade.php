<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\Table;

new #[Layout('layouts.app', ['title' => 'Detail Meja', 'breadcrumbs' => [['title' => 'Manajemen Meja', 'url' => '/owner/meja'], ['title' => 'Detail Meja', 'url' => '#']]])] class extends Component {
    public Table $table;

    public function mount($id)
    {
        $this->table = Table::with(['activeBilling'])->findOrFail($id);
    }
};
?>

<div>
    <div class="row">
        {{-- Info Utama + Lampu --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <div class="header-title">
                        <h4 class="card-title">Identitas Meja</h4>
                    </div>
                </div>
                <div class="card-body text-center">

                    {{-- Nomor Meja --}}
                    <div class="mb-3">
                        <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center"
                            style="width: 90px; height: 90px;">
                            <span class="text-white fw-bold fs-1">{{ $table->table_number }}</span>
                        </div>
                    </div>
                    <h5 class="mb-1">{{ $table->name }}</h5>
                    <p class="text-muted small mb-3">Nomor: {{ $table->table_number }}</p>

                    {{-- ── Indikator Lampu Meja ─────────────────────── --}}
                    @php $lightOn = $table->status === 'occupied'; @endphp
                    <div class="lamp-card mb-4 {{ $lightOn ? 'lamp-card-on' : 'lamp-card-off' }}">
                        <div class="lamp-icon-wrap {{ $lightOn ? 'lamp-icon-on' : 'lamp-icon-off' }}">
                            <i class="{{ $lightOn ? 'fa-solid' : 'fa-regular' }} fa-lightbulb"></i>
                        </div>
                        <div class="lamp-status-text {{ $lightOn ? 'text-warning' : 'text-muted' }}">
                            @if($lightOn)
                                <span class="fw-bold">LAMPU MENYALA</span>
                                <div class="small mt-1" style="font-size:11px;">Sesi bermain berlangsung</div>
                            @else
                                <span class="fw-semibold">LAMPU MATI</span>
                                <div class="small mt-1" style="font-size:11px;">Tidak ada sesi aktif</div>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex justify-content-center gap-2 mb-3">
                        {{-- Badge Kondisi --}}
                        @if ($table->status == 'available')
                            <span class="badge bg-success px-3 py-2">
                                <i class="fa-solid fa-circle-check me-1"></i> Tersedia
                            </span>
                        @elseif($table->status == 'occupied')
                            <span class="badge bg-danger px-3 py-2">
                                <i class="fa-solid fa-circle-xmark me-1"></i> Tidak Tersedia
                            </span>
                        @else
                            <span class="badge bg-warning px-3 py-2">
                                <i class="fa-solid fa-wrench me-1"></i> Maintenance
                            </span>
                        @endif

                        {{-- Badge Status --}}
                        @if ($table->is_active)
                            <span class="badge bg-success px-3 py-2">
                                <i class="fa-solid fa-toggle-on me-1"></i> Aktif
                            </span>
                        @else
                            <span class="badge bg-danger px-3 py-2">
                                <i class="fa-solid fa-toggle-off me-1"></i> Nonaktif
                            </span>
                        @endif
                    </div>

                    <hr>
                    <div class="d-flex justify-content-center gap-2">
                        @role('owner')
                        <a href="{{ route('owner.meja.edit', $table->id) }}" wire:navigate
                            class="btn btn-sm btn-warning">
                            <i class="fa-solid fa-pen me-1"></i> Edit
                        </a>
                        @endrole
                        <a href="{{ auth()->user()->hasRole('owner') ? route('owner.meja.index') : route('kasir.meja.index') }}" wire:navigate class="btn btn-sm btn-secondary">
                            <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>

            {{-- ── API Endpoint Microcontroller ────────────── --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fa-solid fa-microchip me-2 text-info"></i>
                        Endpoint Microcontroller
                    </h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">
                        Microcontroller dapat polling URL berikut untuk membaca status lampu meja ini:
                    </p>
                    <div class="d-flex align-items-center gap-2 p-2 rounded bg-dark mb-2">
                        <span class="badge bg-success flex-shrink-0">GET</span>
                        <code class="text-warning small text-break" style="font-size:11px;">
                            {{ url('/api/microcontroller/table/' . $table->id . '/light') }}
                        </code>
                    </div>
                    <div class="small text-muted">
                        <strong>Response saat ini:</strong>
                        <code class="{{ $lightOn ? 'text-warning' : 'text-muted' }}">
                            light_on: {{ $lightOn ? 'true' : 'false' }}
                        </code>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detail Info --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div class="header-title">
                        <h4 class="card-title">Informasi Detail</h4>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <td class="fw-medium text-muted" style="width: 200px;">Nomor Meja</td>
                                    <td>{{ $table->table_number }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium text-muted">Nama Meja</td>
                                    <td>{{ $table->name }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium text-muted">Kondisi</td>
                                    <td>
                                        @if ($table->status == 'available')
                                            Tersedia
                                        @elseif($table->status == 'occupied')
                                            Tidak Tersedia
                                        @else
                                            Maintenance
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-medium text-muted">Status Lampu</td>
                                    <td>
                                        @if($lightOn)
                                            <span class="text-warning fw-semibold">
                                                <i class="fa-solid fa-lightbulb me-1"></i>Menyala
                                            </span>
                                        @else
                                            <span class="text-muted">
                                                <i class="fa-regular fa-lightbulb me-1"></i>Mati
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-medium text-muted">Status</td>
                                    <td>{{ $table->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium text-muted">Deskripsi</td>
                                    <td>{{ $table->description ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium text-muted">Dibuat Pada</td>
                                    <td>{{ $table->created_at->format('d M Y, H:i') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium text-muted">Terakhir Diperbarui</td>
                                    <td>{{ $table->updated_at->format('d M Y, H:i') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Riwayat Booking --}}
            <div class="card">
                <div class="card-header">
                    <div class="header-title">
                        <h4 class="card-title">Booking Mendatang</h4>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($table->upcomingBookings()->orderBy('scheduled_date')->orderBy('scheduled_start')->limit(5)->get() as $booking)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($booking->scheduled_date)->format('d M Y') }}</td>
                                        <td>{{ $booking->scheduled_start }}</td>
                                        <td>
                                            @if ($booking->status == 'confirmed')
                                                <span class="badge bg-success">Dikonfirmasi</span>
                                            @elseif($booking->status == 'pending')
                                                <span class="badge bg-warning">Menunggu</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">
                                            Tidak ada booking mendatang.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ── Lampu Card (halaman detail) ─────────────────────────── */
.lamp-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px 12px;
    border-radius: 16px;
    margin: 0 auto;
    max-width: 200px;
    transition: all .3s ease;
}
.lamp-card-on {
    background: linear-gradient(135deg, #fef9ec, #fef3c7);
    border: 2px solid #fde68a;
    box-shadow: 0 0 24px 6px #fbbf2444;
}
.lamp-card-off {
    background: #f9fafb;
    border: 2px solid #e5e7eb;
}

/* Ikon lampu besar */
.lamp-icon-wrap {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    transition: all .3s ease;
}
.lamp-icon-on {
    background: #fef3c7;
    color: #d97706;
    box-shadow: 0 0 0 4px #fde68a, 0 0 20px 8px #fbbf24aa;
    animation: lamp-pulse-big 1.8s ease-in-out infinite;
}
.lamp-icon-off {
    background: #f3f4f6;
    color: #9ca3af;
}

.lamp-status-text { line-height: 1.4; }

@keyframes lamp-pulse-big {
    0%, 100% { box-shadow: 0 0 0 4px #fde68a, 0 0 20px 8px #fbbf2488; }
    50%       { box-shadow: 0 0 0 7px #fde68acc, 0 0 34px 14px #fbbf24cc; }
}
</style>
