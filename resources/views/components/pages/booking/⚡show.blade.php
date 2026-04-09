<?php

use App\Models\Booking;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app', ['title' => 'Detail Booking', 'breadcrumbs' => [['title' => 'Monitoring'], ['title' => 'Booking', 'url' => '/owner/booking'], ['title' => 'Detail', 'url' => '#']]])] class extends Component {

    public Booking $booking;

    public $showRejectModal   = false;
    public $rejectReason      = '';
    public $showAddonModal    = false;
    
    public $isTimeUp          = false;
    public $showExtendModal   = false;
    public $extendHours       = 1;

    public function mount($id)
    {
        $this->booking = Booking::with([
            'customer', 'table', 'package', 'pricing', 'confirmedBy', 'billing'
        ])->findOrFail($id);

        $this->checkBillingTime();
    }

    public function checkBillingTime()
    {
        $billing = $this->booking->billing;
        if ($billing && $billing->isActive() && $billing->scheduled_end_at) {
            if (now()->greaterThanOrEqualTo($billing->scheduled_end_at)) {
                $this->isTimeUp = true;
                // Auto finish jika melewati grace period (misal 5 menit dari jadwal habis)
                if (now()->greaterThanOrEqualTo($billing->scheduled_end_at->copy()->addMinutes(5))) {
                    $this->finishBilling();
                    $this->dispatch('notify', message: 'Waktu lewat 5 menit. Billing otomatis diselesaikan!', type: 'info');
                }
            } else {
                $this->isTimeUp = false;
            }
        }
    }

    public function finishBilling()
    {
        $billing = $this->booking->billing;
        if (!$billing || !$billing->isActive()) return;

        $package = $this->booking->package;
        $pricing = $this->booking->pricing;
        
        $end = now();
        
        // Hentikan meteran di jadwal akhir batas waktu. Toleransi waktu (5 menit) 
        // hanya untuk delay auto-finish, bukan untuk menambah beban biaya billing.
        if ($billing->scheduled_end_at && $end->greaterThan($billing->scheduled_end_at)) {
            $end = $billing->scheduled_end_at;
        }

        $elapsedSeconds = $billing->started_at->diffInSeconds($end);
        
        // Pembulatan ke bawah (floor) untuk mengabaikan kelebihan menit, minimum 1 jam.
        $elapsedHours = max(1, floor($elapsedSeconds / 3600));

        if (!$package) {
            $basePrice = $elapsedHours * ($pricing?->price_per_hour ?? 0);
            $extraPrice = 0;
        } elseif ($package->type === 'normal') {
            $basePrice = (float) $package->price;
            $extraHrs = max(0, $elapsedHours - $package->duration_hours);
            $extraPrice = $extraHrs * ($pricing?->price_per_hour ?? 0);
        } else {
            $basePrice = $elapsedHours * ($pricing?->price_per_hour ?? 0);
            $extraPrice = 0;
        }

        $addonTotal = $billing->confirmedAddons()->sum('subtotal');
        $grandTotal = $basePrice + $extraPrice + $addonTotal;

        $billing->update([
            'status' => 'completed',
            'ended_at' => $end,
            'actual_duration_hours' => $elapsedHours,
            'base_price' => $basePrice,
            'extra_price' => $extraPrice,
            'addon_total' => $addonTotal,
            'grand_total' => $grandTotal,
            'ended_by' => auth()->id()
        ]);

        $this->booking->update(['status' => 'completed']);
        if ($this->booking->table) {
            // Billing selesai: lampu mati
            $this->booking->table->update(['status' => 'available', 'device_status' => false]);
        }

        $this->isTimeUp = false;
        $this->showExtendModal = false;
        $this->booking->refresh();
        $this->dispatch('notify', message: 'Billing permainan berhasil diselesaikan!', type: 'success');
    }

    public function extendBilling()
    {
        $billing = $this->booking->billing;
        if (!$billing || !$billing->isActive() || !$billing->scheduled_end_at) return;

        $this->validate([
            'extendHours' => 'required|numeric|min:0.5'
        ]);

        $newEnd = $billing->scheduled_end_at->copy()->addMinutes($this->extendHours * 60);

        // Check Conflict
        $conflict = false;
        $upcomingBookings = Booking::where('table_id', $this->booking->table_id)
            ->where('status', 'confirmed')
            ->where('id', '!=', $this->booking->id)
            ->whereDate('scheduled_date', '>=', today())
            ->get();

        foreach($upcomingBookings as $ub) {
            if (!$ub->scheduled_start) continue;
            
            $ubStart = \Carbon\Carbon::parse($ub->scheduled_date->format('Y-m-d') . ' ' . $ub->scheduled_start);
            if ($newEnd->greaterThan($ubStart)) {
                $conflict = true;
                break;
            }
        }

        if ($conflict) {
            $this->dispatch('notify', message: 'Gagal! Ada booking lain yang menempati meja ini di jam tersebut.', type: 'error');
            return;
        }

        $billing->update([
            'scheduled_end_at' => $newEnd
        ]);

        $this->booking->update([
            'scheduled_end' => $newEnd->format('H:i:s')
        ]);

        $this->showExtendModal = false;
        $this->isTimeUp = false;
        $this->booking->refresh();
        $this->dispatch('notify', message: 'Waktu berhasil diperpanjang ' . $this->extendHours . ' jam!', type: 'success');
    }

    public function confirmBooking()
    {
        if (!$this->booking->isPending()) return;

        $this->booking->update([
            'status'       => 'confirmed',
            'confirmed_by' => auth()->id(),
            'confirmed_at' => now(),
        ]);

        // Jika booking untuk hari ini → langsung tandai meja 'occupied'
        // agar meja tidak bisa dipakai walk-in lain sebelum billing dimulai
        if ($this->booking->scheduled_date?->isToday()) {
            $this->booking->table?->update(['status' => 'occupied']);
        }

        $this->booking->refresh();
        $this->dispatch('notify', message: 'Booking berhasil dikonfirmasi!', type: 'success');
    }

    public function rejectBooking()
    {
        if (!$this->booking->isPending()) return;

        $this->booking->update([
            'status'          => 'rejected',
            'rejected_reason' => $this->rejectReason ?: null,
        ]);

        // Kembalikan meja ke 'available' jika tidak ada booking confirmed lain
        // atau billing aktif yang masih berjalan di meja ini
        $table = $this->booking->table;
        if ($table) {
            $hasActiveBilling = \App\Models\Billing::where('table_id', $table->id)
                ->where('status', 'active')
                ->exists();

            $hasOtherConfirmedToday = \App\Models\Booking::where('table_id', $table->id)
                ->where('id', '!=', $this->booking->id)
                ->where('status', 'confirmed')
                ->whereDate('scheduled_date', today())
                ->exists();

            if (!$hasActiveBilling && !$hasOtherConfirmedToday) {
                $table->update(['status' => 'available']);
            }
        }

        $this->booking->refresh();
        $this->showRejectModal = false;
        $this->rejectReason = '';
        $this->dispatch('notify', message: 'Booking berhasil ditolak.', type: 'success');
    }

    public function createBilling()
    {
        if (!$this->booking->isConfirmed() || $this->booking->billing) return;

        $now = now();
        $scheduledEndAt = null;

        if ($this->booking->package && $this->booking->package->type === 'normal') {
            $scheduledEndAt = $now->copy()->addHours((float) $this->booking->package->duration_hours);
        } else {
            if ($this->booking->scheduled_end) {
                // Konversi string "H:i:s" ke waktu dengan aman hari ini
                $start = \Carbon\Carbon::parse($this->booking->scheduled_start);
                $end = \Carbon\Carbon::parse($this->booking->scheduled_end);
                $diffInMinutes = $start->diffInMinutes($end);
                if ($diffInMinutes < 0) $diffInMinutes += 1440; // Lewati tengah malam
                $scheduledEndAt = $now->copy()->addMinutes($diffInMinutes);
            }
        }

        \App\Models\Billing::create([
            'billing_code'     => '', // Akan digenerate otomatis di observer
            'booking_id'       => $this->booking->id,
            'customer_id'      => $this->booking->customer_id,
            'table_id'         => $this->booking->table_id,
            'package_id'       => $this->booking->package_id,
            'pricing_id'       => $this->booking->pricing_id,
            'started_at'       => $now,
            'ended_at'         => $now, // Placeholder, karena ended_at aslinya not nullable di DB
            'scheduled_end_at' => $scheduledEndAt,
            'status'           => 'active',
            'started_by'       => auth()->id(),
        ]);

        $this->booking->table->update(['status' => 'occupied', 'device_status' => true]);
        $this->booking->refresh();
        $this->dispatch('notify', message: 'Billing berhasil dibuat & permainan dimulai!', type: 'success');
    }

    // --- Addon logic ---
    public function getAvailableAddonsProperty()
    {
        return \App\Models\Addon::where('is_active', true)->get();
    }

    public function addAddonToBilling($addonId)
    {
        $billing = $this->booking->billing;
        if (!$billing || !$billing->isActive()) return;

        $addon = \App\Models\Addon::find($addonId);
        if (!$addon) return;

        // Cek apakah addon ini sudah ada di daftar pesanan
        $existing = \App\Models\BillingAddon::where('billing_id', $billing->id)
            ->where('addon_id', $addon->id)
            ->where('status', 'confirmed')
            ->first();

        if ($existing) {
            // Tambah kuantitas jika sudah ada
            $existing->update([
                'quantity' => $existing->quantity + 1,
                'subtotal' => $addon->price * ($existing->quantity + 1),
            ]);
        } else {
            // Buat barisan baru
            \App\Models\BillingAddon::create([
                'billing_id'        => $billing->id,
                'addon_id'          => $addon->id,
                'quantity'          => 1,
                'unit_price'        => $addon->price,
                'subtotal'          => $addon->price * 1,
                'status'            => 'confirmed', // Karena owner/kasir yang add
                'requested_by'      => auth()->id(),
                'requested_by_role' => 'kasir',
                'confirmed_by'      => auth()->id(),
                'confirmed_at'      => now(),
            ]);
        }

        // Update total addon di billing utama
        $billing->update([
            'addon_total' => $billing->confirmedAddons()->sum('subtotal')
        ]);

        $this->booking->refresh();
        $this->showAddonModal = false;
        $this->dispatch('notify', message: 'Ditambahkan: 1x ' . $addon->name, type: 'success');
    }
};
?>

<div wire:poll.10s="checkBillingTime">
    {{-- Notifikasi Waktu Habis --}}
    @if($isTimeUp && $booking->billing && $booking->billing->isActive())
        <div class="alert alert-danger shadow-sm d-flex flex-column flex-md-row align-items-center justify-content-between mb-4 border-danger">
            <div class="d-flex align-items-center mb-3 mb-md-0">
                <i class="fa-solid fa-clock fa-2x text-danger me-3 animate__animated animate__pulse animate__infinite"></i>
                <div>
                    <strong class="d-block fs-5 text-danger">Waktu Permainan Telah Habis!</strong>
                    <span>Billing ini telah melewati batas waktu yang dijadwalkan. Jika tidak ada konfirmasi dalam beberapa saat, sistem akan menyelesaikan billing secara otomatis.</span>
                </div>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
                <button class="btn btn-outline-danger" wire:click="finishBilling">
                    <i class="fa-solid fa-stop me-1"></i> Selesaikan Sekarang
                </button>
                <button class="btn btn-success fw-bold px-4" @click="$wire.set('showExtendModal', true)">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i> Perpanjang Waktu
                </button>
            </div>
        </div>
    @endif

    <div class="row">
        {{-- LEFT: Info Booking --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="header-title">
                        <h4 class="card-title mb-0">Detail Booking</h4>
                    </div>
                    @php
                        $badgeMap = [
                            'pending'   => ['class' => 'bg-warning text-dark', 'label' => 'Menunggu Konfirmasi'],
                            'confirmed' => ['class' => 'bg-success',            'label' => 'Dikonfirmasi'],
                            'rejected'  => ['class' => 'bg-danger',             'label' => 'Ditolak'],
                            'cancelled' => ['class' => 'bg-secondary',          'label' => 'Dibatalkan'],
                            'completed' => ['class' => 'bg-primary',            'label' => 'Selesai'],
                        ];
                        $badge = $badgeMap[$booking->status] ?? ['class' => 'bg-secondary', 'label' => $booking->status];
                    @endphp
                    <span class="badge {{ $badge['class'] }} fs-6 px-3 py-2">{{ $badge['label'] }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 bg-light">
                                <div class="text-muted small mb-1"><i class="fa-solid fa-hashtag me-1"></i>Kode Booking</div>
                                <div class="fw-bold font-monospace fs-5">{{ $booking->booking_code }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 bg-light">
                                <div class="text-muted small mb-1"><i class="fa-solid fa-calendar me-1"></i>Dibuat Pada</div>
                                <div class="fw-medium">{{ $booking->created_at->format('d M Y, H:i') }}</div>
                            </div>
                        </div>

                        {{-- Jadwal --}}
                        <div class="col-12">
                            <h6 class="text-muted fw-semibold border-bottom pb-2 mt-2">
                                <i class="fa-solid fa-calendar-days me-1"></i> Jadwal Bermain
                            </h6>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Tanggal</div>
                            <div class="fw-medium">{{ $booking->scheduled_date?->format('d M Y') ?? '-' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Jam Mulai</div>
                            <div class="fw-medium">{{ \Carbon\Carbon::parse($booking->scheduled_start)->format('H:i') }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Jam Selesai</div>
                            <div class="fw-medium">{{ \Carbon\Carbon::parse($booking->scheduled_end)->format('H:i') }}</div>
                        </div>

                        {{-- Fasilitas --}}
                        <div class="col-12">
                            <h6 class="text-muted fw-semibold border-bottom pb-2 mt-2">
                                <i class="fa-solid fa-circle-info me-1"></i> Detail Fasilitas
                            </h6>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Meja</div>
                            <div class="fw-medium">Meja {{ $booking->table?->table_number ?? '-' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Paket</div>
                            <div class="fw-medium">{{ $booking->package?->name ?? 'Tanpa Paket' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Tarif Dasar</div>
                            <div class="fw-medium">{{ $booking->pricing?->name ?? '-' }}</div>
                        </div>

                        {{-- Catatan --}}
                        @if ($booking->notes)
                            <div class="col-12">
                                <div class="text-muted small">Catatan</div>
                                <div class="p-2 rounded bg-light mt-1">{{ $booking->notes }}</div>
                            </div>
                        @endif

                        {{-- Info Konfirmasi / Penolakan --}}
                        @if ($booking->isConfirmed())
                            <div class="col-12">
                                <div class="alert alert-success d-flex align-items-start gap-2 mb-0">
                                    <i class="fa-solid fa-circle-check mt-1"></i>
                                    <div>
                                        <strong>Dikonfirmasi oleh:</strong> {{ $booking->confirmedBy?->name ?? '-' }}<br>
                                        <small class="text-muted">{{ $booking->confirmed_at?->format('d M Y, H:i') }}</small>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($booking->isRejected() && $booking->rejected_reason)
                            <div class="col-12">
                                <div class="alert alert-danger d-flex align-items-start gap-2 mb-0">
                                    <i class="fa-solid fa-ban mt-1"></i>
                                    <div>
                                        <strong>Alasan Penolakan:</strong><br>
                                        {{ $booking->rejected_reason }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Footer Aksi --}}
                @if ($booking->isPending())
                    <div class="card-footer d-flex justify-content-end gap-2">
                        <button class="btn btn-danger"
                            @click="$wire.set('showRejectModal', true)">
                            <i class="fa-solid fa-ban me-1"></i> Tolak Booking
                        </button>
                        <button class="btn btn-success"
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
                                    if (result.isConfirmed) $wire.confirmBooking()
                                })
                            ">
                            <i class="fa-solid fa-check me-1"></i> Konfirmasi Booking
                        </button>
                    </div>
                @elseif($booking->isConfirmed() && !$booking->billing)
                    <div class="card-footer d-flex justify-content-end gap-2">
                         <button class="btn btn-primary"
                            @click="
                                Swal.fire({
                                    title: 'Mulai Permainan?',
                                    text: 'Billing akan dibuat dan meja akan ditandai Sedang Digunakan.',
                                    icon: 'info',
                                    showCancelButton: true,
                                    confirmButtonColor: '#0d6efd',
                                    cancelButtonColor: '#6c757d',
                                    confirmButtonText: 'Ya, Mulai!',
                                    cancelButtonText: 'Batal'
                                }).then((result) => {
                                    if (result.isConfirmed) $wire.createBilling()
                                })
                            ">
                            <i class="fa-solid fa-play me-1"></i> Mulai Permainan (Buat Billing)
                        </button>
                    </div>
                @elseif($booking->billing && $booking->billing->isActive())
                    <div class="card-footer d-flex justify-content-end gap-2 bg-light">
                        @if($booking->billing->scheduled_end_at)
                            <button class="btn btn-outline-primary" @click="$wire.set('showExtendModal', true)">
                                <i class="fa-solid fa-clock-rotate-left me-1"></i> Perpanjang Waktu
                            </button>
                        @endif
                        <button class="btn btn-danger"
                            @click="
                                Swal.fire({
                                    title: 'Selesaikan Permainan?',
                                    text: 'Meja akan dikosongkan dan total tagihan akan dihitung final.',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#dc3545',
                                    cancelButtonColor: '#6c757d',
                                    confirmButtonText: 'Ya, Selesaikan!',
                                    cancelButtonText: 'Batal'
                                }).then((result) => {
                                    if (result.isConfirmed) $wire.finishBilling()
                                })
                            ">
                            <i class="fa-solid fa-stop me-1"></i> Selesaikan Permainan
                        </button>
                    </div>
                @endif
            </div>

            {{-- TABEL DAFTAR ADDON (Di bawah Card Booking) --}}
            @if ($booking->billing && $booking->billing->addons->isNotEmpty())
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fa-solid fa-burger me-2"></i>Daftar Pesanan F&B</h5>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover table-striped mb-0 text-center align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th class="text-start">Item</th>
                                <th>Qty</th>
                                <th class="text-end">Harga Satuan</th>
                                <th class="text-end pe-3">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($booking->billing->addons as $index => $ba)
                            <tr>
                                <td class="text-muted">{{ $index + 1 }}</td>
                                <td class="text-start fw-medium">{{ $ba->addon->name ?? 'Terhapus' }}</td>
                                <td>{{ $ba->quantity }}</td>
                                <td class="text-end text-muted">{{ 'Rp ' . number_format($ba->unit_price, 0, ',', '.') }}</td>
                                <td class="text-end pe-3 text-success fw-semibold">{{ $ba->formatted_subtotal }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Total F&B:</td>
                                <td class="text-end pe-3 fw-bold text-primary">{{ 'Rp ' . number_format($booking->billing->addon_total, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif
        </div>

        {{-- RIGHT: Info Pelanggan + Billing --}}
        <div class="col-lg-4">
            {{-- Pelanggan --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fa-solid fa-user me-2"></i>Informasi Member</h5>
                </div>
                <div class="card-body">
                    @if ($booking->customer)
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="flex-shrink-0">
                                @if ($booking->customer->avatar)
                                    <img src="{{ Storage::url($booking->customer->avatar) }}"
                                        class="rounded-circle" width="52" height="52" style="object-fit:cover;" alt="">
                                @else
                                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center"
                                        style="width:52px;height:52px;">
                                        <span class="text-white fw-bold fs-5">
                                            {{ strtoupper(substr($booking->customer->name, 0, 1)) }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <div>
                                <div class="fw-semibold">{{ $booking->customer->name }}</div>
                                <small class="text-muted">{{ $booking->customer->email }}</small>
                            </div>
                        </div>
                        <div class="small text-muted mb-1">No. HP</div>
                        <div class="mb-2">{{ $booking->customer->phone ?? '-' }}</div>
                    @else
                        <p class="text-muted mb-0">Data member tidak ditemukan.</p>
                    @endif
                </div>
            </div>

            {{-- Billing --}}
            <div class="card mt-0">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fa-solid fa-receipt me-2"></i>Informasi Billing</h5>
                </div>
                <div class="card-body">
                    @if ($booking->billing)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Kode Billing</span>
                            <span class="fw-medium font-monospace">{{ $booking->billing->billing_code }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Status</span>
                            <span class="badge {{ $booking->billing->isActive() ? 'bg-success' : 'bg-primary' }}">
                                {{ $booking->billing->isActive() ? 'Aktif' : 'Selesai' }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Total</span>
                            <span class="fw-bold text-success">{{ $booking->billing->formatted_current_total }}</span>
                        </div>

                        @if ($booking->billing->isActive())
                            <div class="mt-3">
                                <button class="btn btn-outline-primary w-100" @click="$wire.set('showAddonModal', true)">
                                    <i class="fa-solid fa-plus me-1"></i> Tambah Addon F&B
                                </button>
                            </div>
                        @endif

                    @else
                        <p class="text-muted small mb-0">Billing belum dibuat untuk booking ini.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Back Button --}}
    <div class="mt-2">
        <a href="{{ auth()->user()->hasRole('owner') ? route('owner.booking.index') : route('kasir.booking.index') }}" wire:navigate class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Daftar Booking
        </a>
    </div>

    {{-- Modal Tolak --}}
    @if ($showRejectModal)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger"><i class="fa-solid fa-ban me-2"></i>Tolak Booking</h5>
                        <button type="button" class="btn-close" @click="$wire.set('showRejectModal', false)"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">Kode: <strong>{{ $booking->booking_code }}</strong></p>
                        <div class="mb-3">
                            <label class="form-label">Alasan Penolakan <span class="text-muted">(opsional)</span></label>
                            <textarea class="form-control" wire:model="rejectReason" rows="3"
                                placeholder="Masukkan alasan penolakan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" @click="$wire.set('showRejectModal', false)">Batal</button>
                        <button class="btn btn-danger" wire:click="rejectBooking" wire:loading.attr="disabled">
                            <span wire:loading.remove>Tolak Booking</span>
                            <span wire:loading>Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Tambah Addon --}}
    @if ($showAddonModal)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.6);">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title text-primary"><i class="fa-solid fa-boxes-stacked me-2"></i>Katalog F&B (Addon)</h5>
                        <button type="button" class="btn-close" @click="$wire.set('showAddonModal', false)"></button>
                    </div>
                    <div class="modal-body p-4 bg-light bg-opacity-50">
                        <p class="text-muted mb-4 text-center">Silakan klik ikon produk di bawah ini untuk menambahkannya langsung ke tagihan pelanggan.</p>
                        <div style="max-height: 400px; overflow-y: auto; overflow-x: hidden;" class="pe-2 pb-2">
                            <div class="row g-3">
                                @forelse($this->availableAddons as $ad)
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="card h-100 border-0 shadow-sm user-select-none text-center"
                                        style="cursor: pointer; transition: all 0.2s;"
                                        onmouseover="this.style.transform='scale(1.05)'; this.classList.add('shadow');"
                                        onmouseout="this.style.transform='scale(1)'; this.classList.remove('shadow');"
                                        wire:click="addAddonToBilling({{ $ad->id }})">
                                        
                                        <div class="card-img-top bg-light ratio ratio-1x1 border-bottom">
                                            <div style="background-image: url('{{ $ad->image_url }}'); background-size: cover; background-position: center; border-radius: inherit;"></div>
                                        </div>
                                        <div class="card-body p-2 d-flex flex-column justify-content-between">
                                            <div class="fw-semibold text-truncate mb-1" style="font-size: 0.85rem;" title="{{ $ad->name }}">{{ $ad->name }}</div>
                                            <div class="text-primary fw-bold" style="font-size: 0.9rem;">{{ $ad->formatted_price }}</div>
                                        </div>
                                        
                                        <div class="position-absolute align-items-center justify-content-center bg-white shadow-sm border border-primary rounded-circle" 
                                             style="top: 5px; right: 5px; width: 26px; height: 26px; display: flex;">
                                            <i class="fa-solid fa-plus text-primary small"></i>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <div class="col-12 text-center text-muted col-py-5">
                                    <p>Tidaka ada produk Addon F&B yang aktif.</p>
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Perpanjang Waktu --}}
    @if ($showExtendModal)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Perpanjang Waktu</h5>
                        <button type="button" class="btn-close" @click="$wire.set('showExtendModal', false)"></button>
                    </div>
                    <div class="modal-body py-4">
                        <label class="form-label text-muted small fw-semibold">Tambahan Durasi (Jam)</label>
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" type="button" @click="$wire.set('extendHours', Math.max(0.5, {{ $extendHours }} - 0.5))">
                                <i class="fa-solid fa-minus"></i>
                            </button>
                            <input type="number" class="form-control text-center fw-bold fs-5" wire:model="extendHours" step="0.5" min="0.5" readonly>
                            <button class="btn btn-outline-secondary" type="button" @click="$wire.set('extendHours', {{ $extendHours }} + 0.5)">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                        <div class="text-center mt-2 small text-muted">
                            Jam Berakhir Baru: <br>
                            <strong class="text-dark">{{ $booking->billing->scheduled_end_at ? $booking->billing->scheduled_end_at->copy()->addMinutes($extendHours * 60)->format('H:i') : '-' }}</strong>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-center border-0 pt-0">
                        <button class="btn btn-primary w-100 fw-bold" wire:click="extendBilling" wire:loading.attr="disabled">
                            <i class="fa-solid fa-check me-1"></i> Terapkan Perpanjangan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>