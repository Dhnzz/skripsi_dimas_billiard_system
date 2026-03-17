<?php

use App\Models\Booking;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.member-booking', ['title' => 'Detail Booking & Billing'])] class extends Component {

    public Booking $booking;

    public function mount($id)
    {
        $this->booking = Booking::with([
            'table', 'package', 'pricing', 'billing.addons.addon'
        ])
        ->where('customer_id', auth()->id())
        ->findOrFail($id);
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom border-secondary">
        <h4 class="mb-0" style="color: var(--neon); font-family: 'Bebas Neue', sans-serif; font-size: 1.8rem; letter-spacing: 0.05em;">
            DETAIL BOOKING #{{ $booking->booking_code }}
        </h4>
        <a href="{{ route('member.booking.index') }}" wire:navigate class="btn-booking-back py-1 px-3">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="row g-4">
        {{-- Section Kiri: Info Booking --}}
        <div class="col-lg-6">
            <div class="booking-card h-100">
                <div class="booking-card-header">
                    <h5 class="mb-0"><i class="fa-solid fa-clipboard-list me-2 text-primary"></i>Informasi Booking</h5>
                    @php
                        $badgeMap = [
                            'pending'   => ['color' => '#ffc107', 'label' => 'Menunggu Konfirmasi'],
                            'confirmed' => ['color' => '#198754', 'label' => 'Dikonfirmasi'],
                            'rejected'  => ['color' => '#dc3545', 'label' => 'Ditolak'],
                            'cancelled' => ['color' => '#6c757d', 'label' => 'Dibatalkan'],
                            'completed' => ['color' => '#0d6efd', 'label' => 'Selesai'],
                        ];
                        $st = $badgeMap[$booking->status] ?? ['color' => '#6c757d', 'label' => $booking->status];
                    @endphp
                    <span class="bk-status-badge" style="color: {{ $st['color'] }}">{{ $st['label'] }}</span>
                </div>
                <div class="booking-card-body p-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="bk-meta-label">Meja</div>
                            <div class="bk-meta-value fw-bold text-white fs-5">Meja {{ $booking->table?->table_number ?? '-' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="bk-meta-label">Tanggal Main</div>
                            <div class="bk-meta-value text-white">{{ \Carbon\Carbon::parse($booking->scheduled_date)->format('d M Y') }}</div>
                        </div>
                        <div class="col-6">
                            <div class="bk-meta-label">Jam Mulai</div>
                            <div class="bk-meta-value text-white">{{ \Carbon\Carbon::parse($booking->scheduled_start)->format('H:i') }}</div>
                        </div>
                        <div class="col-6">
                            <div class="bk-meta-label">Jam Selesai</div>
                            <div class="bk-meta-value text-white">{{ $booking->scheduled_end ? \Carbon\Carbon::parse($booking->scheduled_end)->format('H:i') : 'Selesai (Loss)' }}</div>
                        </div>
                        <div class="col-12 mt-3 pt-3 border-top border-secondary">
                            <div class="bk-meta-label">Paket / Tarif</div>
                            <div class="bk-meta-value text-white">
                                {{ $booking->package ? $booking->package->name : 'Tanpa Paket (Tarif Dasar)' }}
                                <br><small class="text-muted">{{ $booking->pricing?->name }}</small>
                            </div>
                        </div>
                        @if($booking->notes)
                        <div class="col-12 mt-2">
                            <div class="bk-meta-label">Catatan</div>
                            <div class="bk-meta-value">{{ $booking->notes }}</div>
                        </div>
                        @endif

                        @if($booking->isRejected() && $booking->rejected_reason)
                            <div class="col-12 mt-3">
                                <div class="bk-reject-reason fs-6 mt-0">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> <strong>Alasan Penolakan:</strong><br>
                                    {{ $booking->rejected_reason }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Section Kanan: Billing & Sisa Waktu --}}
        <div class="col-lg-6">
            <div class="booking-card h-100 position-relative border-{{ $booking->billing && $booking->billing->isActive() ? 'success' : 'secondary' }}">
                <div class="booking-card-header">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-receipt me-2 {{ $booking->billing && $booking->billing->isActive() ? 'text-success' : 'text-secondary' }}"></i>
                        Informasi Billing
                    </h5>
                    @if($booking->billing)
                        <span class="badge {{ $booking->billing->isActive() ? 'bg-success text-white' : 'bg-primary' }}">
                            {{ $booking->billing->isActive() ? 'Sedang Berjalan' : 'Selesai' }}
                        </span>
                    @endif
                </div>

                <div class="booking-card-body p-4">
                    @if(!$booking->billing)
                        <div class="text-center py-5">
                            <i class="fa-solid fa-hourglass-start fa-3x mb-3 text-secondary" style="opacity: 0.5;"></i>
                            <p class="text-muted mb-0">Billing belum dimulai.</p>
                            @if($booking->isConfirmed())
                                <small class="text-warning">Silakan melapor ke kasir/admin untuk memulai permainan.</small>
                            @endif
                        </div>
                    @else
                        {{-- Timer Realtime jika aktif --}}
                        @if($booking->billing->isActive())
                            @if($booking->billing->scheduled_end_at)
                                @php
                                    $remainingSeconds = max(0, $booking->billing->scheduled_end_at->getTimestamp() - now()->getTimestamp());
                                @endphp
                                <div class="text-center mb-4 p-4 rounded-3" style="background: rgba(255,193,7,.05); border: 1px solid var(--border-mid);" 
                                    x-data="{
                                        remaining: {{ $remainingSeconds }},
                                        formatTime(sec) {
                                            if (sec <= 0) return '00:00:00 (Selesai)';
                                            let h = Math.floor(sec / 3600);
                                            let m = Math.floor((sec % 3600) / 60);
                                            let s = sec % 60;
                                            return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
                                        },
                                        init() {
                                            setInterval(() => { 
                                                if (this.remaining > 0) this.remaining--;
                                            }, 1000);
                                        }
                                    }">
                                    <div class="text-muted small text-uppercase letter-spacing-1 mb-2">Sisa Waktu Bermain</div>
                                    <div class="font-monospace fw-bold" style="font-size: 3rem; color: #ffc107; line-height:1;" x-text="formatTime(remaining)">
                                        --:--:--
                                    </div>
                                </div>
                            @else
                                <div class="text-center mb-4 p-4 rounded-3" style="background: rgba(57,255,143,.05); border: 1px solid var(--border-mid);" 
                                    x-data="{
                                        totalSeconds: {{ $booking->billing->elapsed_seconds }},
                                        formatTime(sec) {
                                            let h = Math.floor(sec / 3600);
                                            let m = Math.floor((sec % 3600) / 60);
                                            let s = sec % 60;
                                            return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
                                        },
                                        init() {
                                            setInterval(() => { this.totalSeconds++ }, 1000);
                                        }
                                    }">
                                    <div class="text-muted small text-uppercase letter-spacing-1 mb-2">Waktu Berjalan (Loss)</div>
                                    <div class="font-monospace fw-bold" style="font-size: 3rem; color: var(--neon); line-height:1;" x-text="formatTime(totalSeconds)">
                                        --:--:--
                                    </div>
                                </div>
                            @endif
                        @else
                            <div class="text-center mb-4 p-3 border-bottom border-secondary">
                                <div class="text-muted small text-uppercase mb-1">Total Waktu Bermain</div>
                                <div class="font-monospace fw-bold text-white fs-4">{{ $booking->billing->elapsed_formatted }}</div>
                            </div>
                        @endif

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="text-muted small">Mulai Bermain</div>
                                <div class="text-white">{{ $booking->billing->started_at->format('H:i') }}</div>
                            </div>
                            <div class="col-6 text-end">
                                <div class="text-muted small">Total Pembayaran</div>
                                <div class="text-success fw-bold fs-5">{{ $booking->billing->formatted_current_total }}</div>
                            </div>
                        </div>

                        {{-- Daftar Addon --}}
                        @if($booking->billing->addons->isNotEmpty())
                            <div class="mt-4">
                                <h6 class="text-muted small text-uppercase border-bottom border-secondary pb-2 mb-3">Pesanan F&B (Addon)</h6>
                                <ul class="list-unstyled mb-0">
                                    @foreach($booking->billing->addons as $ba)
                                        <li class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="text-white">
                                                <span class="text-muted me-2">{{ $ba->quantity }}x</span> {{ $ba->addon->name ?? 'Item Terhapus' }}
                                            </div>
                                            <div class="text-success">{{ $ba->formatted_subtotal }}</div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        @if($booking->billing->isActive())
                            <div class="mt-4 pt-3 border-top border-secondary text-center small text-muted">
                                *Untuk memesan tambahan makanan/minuman, silakan hubungi kasir atau pelayan.
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
