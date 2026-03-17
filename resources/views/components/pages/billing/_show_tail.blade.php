            {{-- ── CARD: DAFTAR ADDON ─────────────────────────── --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fa-solid fa-burger me-2 text-info"></i>
                        Pesanan Addon F&B
                    </h5>
                    @if($billing->isActive())
                        <button class="btn btn-sm btn-outline-info" @click="$wire.set('showAddonModal', true)">
                            <i class="fa-solid fa-plus me-1"></i> Tambah
                        </button>
                    @endif
                </div>
                @if($this->confirmedAddons->isEmpty())
                    <div class="card-body text-center py-4 text-muted">
                        <i class="fa-solid fa-basket-shopping fa-2x mb-2 d-block opacity-50"></i>
                        <p class="small mb-0">Belum ada pesanan addon.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Item</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Harga Satuan</th>
                                    <th class="text-end">Subtotal</th>
                                    @if($billing->isActive())
                                        <th class="text-center">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->confirmedAddons as $i => $ba)
                                    <tr>
                                        <td class="text-center text-muted">{{ $i + 1 }}</td>
                                        <td>
                                            <div class="fw-medium">{{ $ba->addon->name ?? 'Item Terhapus' }}</div>
                                            @if($ba->addon)
                                                <div class="small text-muted">{{ $ba->addon->category }}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($billing->isActive())
                                                <div class="d-flex align-items-center justify-content-center gap-1">
                                                    <button type="button" wire:click="decrementAddon({{ $ba->id }})"
                                                        class="btn btn-sm btn-outline-secondary p-0"
                                                        style="width:24px;height:24px;line-height:1;">
                                                        <i class="fa-solid fa-minus" style="font-size:9px;"></i>
                                                    </button>
                                                    <span class="fw-bold mx-1" style="min-width:20px;text-align:center;">{{ $ba->quantity }}</span>
                                                    <button type="button" wire:click="incrementAddon({{ $ba->id }})"
                                                        class="btn btn-sm btn-outline-primary p-0"
                                                        style="width:24px;height:24px;line-height:1;">
                                                        <i class="fa-solid fa-plus" style="font-size:9px;"></i>
                                                    </button>
                                                </div>
                                            @else
                                                <span class="fw-medium">{{ $ba->quantity }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end text-muted">Rp {{ number_format($ba->unit_price, 0, ',', '.') }}</td>
                                        <td class="text-end fw-semibold text-success">{{ $ba->formatted_subtotal }}</td>
                                        @if($billing->isActive())
                                            <td class="text-center">
                                                <button type="button"
                                                    wire:click="removeAddon({{ $ba->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="removeAddon({{ $ba->id }})"
                                                    class="btn btn-sm btn-icon btn-danger rounded-circle"
                                                    title="Hapus">
                                                    <span class="btn-inner">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </span>
                                                </button>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="{{ $billing->isActive() ? 4 : 3 }}" class="text-end fw-bold pe-3">
                                        Total Addon F&B:
                                    </td>
                                    <td class="text-end fw-bold text-primary">
                                        Rp {{ number_format($billing->addon_total, 0, ',', '.') }}
                                    </td>
                                    @if($billing->isActive())<td></td>@endif
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>

            {{-- ── CARD: RIWAYAT PERPANJANGAN WAKTU ──────────── --}}
            @if($billing->timeExtensions->isNotEmpty())
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fa-solid fa-clock-rotate-left me-2 text-warning"></i>
                            Riwayat Perpanjangan Waktu
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Tambah Durasi</th>
                                    <th>Batas Waktu Baru</th>
                                    <th>Oleh</th>
                                    <th>Waktu Proses</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($billing->timeExtensions as $idx => $ext)
                                    <tr>
                                        <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                        <td>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                                +{{ $ext->added_hours }} jam
                                            </span>
                                        </td>
                                        <td class="fw-medium">
                                            {{ $ext->new_scheduled_at
                                                ? \Carbon\Carbon::parse($ext->new_scheduled_at)->format('d M Y, H:i')
                                                : '-' }}
                                        </td>
                                        <td>{{ $ext->extendedByUser?->name ?? '-' }}</td>
                                        <td class="text-muted small">{{ $ext->created_at->format('d M Y, H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
        {{-- /col-lg-8 --}}


        {{-- ════════════════════════════════════════════════════ --}}
        {{-- KOLOM KANAN                                         --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="col-lg-4">

            {{-- ── CARD: INFORMASI PELANGGAN ─────────────────── --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fa-solid fa-user me-2 text-primary"></i>
                        Informasi Pelanggan
                    </h5>
                </div>
                <div class="card-body">
                    @if($this->isWalkIn)
                        {{-- Pelanggan Walk-In --}}
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                style="width:52px;height:52px;font-size:20px;">
                                {{ strtoupper(substr($billing->guest_name ?? 'T', 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-semibold fs-6">{{ $billing->guest_name ?? '-' }}</div>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle mt-1">
                                    <i class="fa-solid fa-person-walking me-1"></i>Walk-In
                                </span>
                            </div>
                        </div>
                        <div class="alert alert-warning py-2 border-0 bg-warning-subtle small mb-0">
                            <i class="fa-solid fa-circle-info me-1 text-warning"></i>
                            Pelanggan tanpa akun. Billing dibuat manual oleh kasir/owner.
                        </div>
                    @elseif($billing->customer)
                        {{-- Member terdaftar --}}
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="flex-shrink-0">
                                @if($billing->customer->avatar)
                                    <img src="{{ Storage::url($billing->customer->avatar) }}"
                                        class="rounded-circle" width="52" height="52"
                                        style="object-fit:cover;" alt="{{ $billing->customer->name }}">
                                @else
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                                        style="width:52px;height:52px;font-size:20px;">
                                        {{ strtoupper(substr($billing->customer->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div>
                                <div class="fw-semibold fs-6">{{ $billing->customer->name }}</div>
                                <div class="small text-muted">{{ $billing->customer->email }}</div>
                                <span class="badge bg-info-subtle text-info border border-info-subtle mt-1">
                                    <i class="fa-solid fa-id-card me-1"></i>Member
                                </span>
                            </div>
                        </div>
                        @if($billing->customer->phone)
                            <div class="text-muted small mb-1">No. HP</div>
                            <div class="fw-medium">{{ $billing->customer->phone }}</div>
                        @endif
                    @else
                        <p class="text-muted small mb-0">Data pelanggan tidak tersedia.</p>
                    @endif
                </div>
            </div>

            {{-- ── CARD: RINGKASAN BIAYA ─────────────────────── --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fa-solid fa-calculator me-2 text-success"></i>
                        Ringkasan Biaya
                    </h5>
                </div>
                <div class="card-body">
                    {{-- Harga Dasar --}}
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Harga Dasar</span>
                        <span class="fw-medium">
                            @if($billing->isCompleted())
                                Rp {{ number_format($billing->base_price, 0, ',', '.') }}
                            @else
                                <span class="text-muted fst-italic">Dihitung saat selesai</span>
                            @endif
                        </span>
                    </div>

                    {{-- Extra Waktu --}}
                    @if($billing->isCompleted() && $billing->extra_price > 0)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Biaya Extra Waktu</span>
                            <span class="fw-medium text-warning">
                                + Rp {{ number_format($billing->extra_price, 0, ',', '.') }}
                            </span>
                        </div>
                    @endif

                    {{-- Addon --}}
                    @if($billing->addon_total > 0)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Total Addon F&B</span>
                            <span class="fw-medium text-info">
                                + Rp {{ number_format($billing->addon_total, 0, ',', '.') }}
                            </span>
                        </div>
                    @endif

                    <hr class="my-2">

                    {{-- Grand Total --}}
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">
                            {{ $billing->isActive() ? 'Total Sementara' : 'Total Final' }}
                        </span>
                        <span class="fw-bold fs-5 text-success">
                            {{ $billing->formatted_current_total }}
                        </span>
                    </div>

                    {{-- Durasi aktual --}}
                    @if($billing->isCompleted() && $billing->actual_duration_hours)
                        <div class="mt-2 text-muted small text-end">
                            Durasi: {{ $billing->actual_duration_hours }} jam
                        </div>
                    @endif

                    {{-- Info Pembayaran --}}
                    @if($billing->payment)
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Status Pembayaran</span>
                            <span class="badge {{ $billing->payment->isPaid() ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ $billing->payment->isPaid() ? 'Lunas' : 'Belum Dibayar' }}
                            </span>
                        </div>
                        @if($billing->payment->isPaid())
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">Metode</span>
                                <span class="fw-medium text-uppercase">{{ $billing->payment->method }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Dibayar Pada</span>
                                <span class="fw-medium small">{{ $billing->payment->paid_at?->format('d M Y, H:i') ?? '-' }}</span>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- ── CARD: OPERATOR ────────────────────────────── --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fa-solid fa-user-shield me-2 text-secondary"></i>
                        Operator
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="text-muted small mb-1">
                            <i class="fa-solid fa-play me-1 text-success"></i>Dibuka oleh
                        </div>
                        <div class="fw-semibold">{{ $billing->startedBy?->name ?? '-' }}</div>
                        <div class="small text-muted">{{ $billing->started_at->format('d M Y, H:i') }}</div>
                    </div>
                    @if($billing->isCompleted() && $billing->endedBy)
                        <div>
                            <div class="text-muted small mb-1">
                                <i class="fa-solid fa-stop me-1 text-danger"></i>Diselesaikan oleh
                            </div>
                            <div class="fw-semibold">{{ $billing->endedBy->name }}</div>
                            <div class="small text-muted">
                                {{ $billing->ended_at?->format('d M Y, H:i') ?? '-' }}
                            </div>
                        </div>
                    @else
                        <div class="text-muted small fst-italic">
                            <i class="fa-solid fa-spinner fa-spin me-1"></i>Masih berlangsung...
                        </div>
                    @endif
                </div>
            </div>

        </div>
        {{-- /col-lg-4 --}}

    </div>
    {{-- /row --}}

    {{-- ── TOMBOL KEMBALI ───────────────────────────────────── --}}
    <div class="mt-3">
        <a href="{{ auth()->user()->hasRole('owner') ? route('owner.billing.index') : route('kasir.billing.index') }}"
            class="btn btn-outline-secondary btn-sm" wire:navigate>
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Daftar Billing
        </a>
    </div>


    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- MODAL: TAMBAH ADDON                                       --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    @if($showAddonModal)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.6);">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light border-bottom">
                        <h5 class="modal-title fw-semibold">
                            <i class="fa-solid fa-boxes-stacked me-2 text-info"></i>
                            Katalog Addon F&B
                        </h5>
                        <button type="button" class="btn-close" @click="$wire.set('showAddonModal', false)"></button>
                    </div>
                    <div class="modal-body p-4">

                        {{-- Banner Walk-In --}}
                        @if($this->isWalkIn)
                            <div class="alert border-0 bg-warning-subtle py-2 mb-3 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-person-walking text-warning flex-shrink-0"></i>
                                <div class="small text-warning-emphasis">
                                    Billing Walk-In — <strong>{{ $billing->guest_name }}</strong>.
                                    Addon akan langsung dikonfirmasi ke tagihan.
                                </div>
                            </div>
                        @else
                            <div class="alert border-0 bg-info-subtle py-2 mb-3 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-id-card text-info flex-shrink-0"></i>
                                <div class="small text-info-emphasis">
                                    Member — <strong>{{ $billing->customer?->name ?? '-' }}</strong>.
                                    Addon langsung dikonfirmasi oleh kasir/owner.
                                </div>
                            </div>
                        @endif

                        <p class="text-muted small text-center mb-3">
                            Klik produk untuk langsung menambahkan ke tagihan.
                        </p>

                        <div style="max-height:450px;overflow-y:auto;overflow-x:hidden;" class="pe-1">
                            @php $groupedAddons = $this->availableAddons->groupBy('category'); @endphp

                            @forelse($groupedAddons as $cat => $catItems)
                                <div class="mb-4">
                                    <div class="text-muted small fw-semibold text-uppercase mb-2 border-bottom pb-1">
                                        <i class="fa-solid fa-tag me-1"></i>{{ $cat }}
                                    </div>
                                    <div class="row g-2">
                                        @foreach($catItems as $ad)
                                            <div class="col-6 col-md-4 col-lg-3">
                                                <div class="card h-100 border-0 shadow-sm text-center user-select-none position-relative"
                                                    style="cursor:pointer;transition:transform .15s,box-shadow .15s;"
                                                    onmouseover="this.style.transform='scale(1.04)';this.style.boxShadow='0 4px 16px rgba(0,0,0,.12)';"
                                                    onmouseout="this.style.transform='scale(1)';this.style.boxShadow='';"
                                                    wire:click="addAddon({{ $ad->id }})"
                                                    wire:loading.class="opacity-50"
                                                    wire:target="addAddon({{ $ad->id }})">

                                                    {{-- Gambar --}}
                                                    <div class="ratio ratio-1x1 bg-light border-bottom" style="border-radius:inherit;">
                                                        <div style="background-image:url('{{ $ad->image_url }}');background-size:cover;background-position:center;border-radius:inherit;"></div>
                                                    </div>

                                                    {{-- Info --}}
                                                    <div class="card-body p-2">
                                                        <div class="fw-semibold text-truncate mb-1"
                                                            style="font-size:.82rem;"
                                                            title="{{ $ad->name }}">
                                                            {{ $ad->name }}
                                                        </div>
                                                        <div class="text-success fw-bold" style="font-size:.85rem;">
                                                            {{ $ad->formatted_price }}
                                                        </div>
                                                    </div>

                                                    {{-- Badge + ikon --}}
                                                    <div class="position-absolute d-flex align-items-center justify-content-center bg-white shadow-sm border border-primary rounded-circle"
                                                        style="top:5px;right:5px;width:24px;height:24px;">
                                                        <i class="fa-solid fa-plus text-primary" style="font-size:10px;"></i>
                                                    </div>

                                                    {{-- Loading overlay --}}
                                                    <div wire:loading wire:target="addAddon({{ $ad->id }})"
                                                        class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-white bg-opacity-75"
                                                        style="border-radius:inherit;">
                                                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-5">
                                    <i class="fa-solid fa-box-open fa-2x mb-2 d-block opacity-50"></i>
                                    <p class="small mb-0">Tidak ada addon aktif.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="modal-footer bg-light border-top">
                        <div class="me-auto small text-muted">
                            Total Addon saat ini:
                            <strong class="text-success">
                                Rp {{ number_format($billing->addon_total, 0, ',', '.') }}
                            </strong>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm"
                            @click="$wire.set('showAddonModal', false)">
                            <i class="fa-solid fa-xmark me-1"></i> Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif


    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- MODAL: PERPANJANG WAKTU                                   --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    @if($showExtendModal)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);">
            <div class="modal-dialog modal-dialog-centered" style="max-width:360px;">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-semibold">
                            <i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>
                            Perpanjang Waktu
                        </h5>
                        <button type="button" class="btn-close" @click="$wire.set('showExtendModal', false)"></button>
                    </div>

                    <div class="modal-body py-4 px-4">
                        <label class="form-label text-muted small fw-semibold mb-2">
                            Tambahan Durasi (Jam)
                        </label>
                        <div class="input-group mb-1">
                            <button class="btn btn-outline-secondary" type="button"
                                @click="$wire.set('extendHours', Math.max(0.5, {{ $extendHours }} - 0.5))">
                                <i class="fa-solid fa-minus"></i>
                            </button>
                            <input type="number" class="form-control text-center fw-bold fs-4"
                                wire:model.live="extendHours" step="0.5" min="0.5" readonly>
                            <button class="btn btn-outline-secondary" type="button"
                                @click="$wire.set('extendHours', {{ $extendHours }} + 0.5)">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                        @error('extendHours')
                            <div class="text-danger small mb-2">{{ $message }}</div>
                        @enderror

                        @if($billing->scheduled_end_at)
                            <div class="text-center bg-light rounded-3 p-3 mt-3 border">
                                <div class="text-muted small mb-1">Batas Waktu Baru</div>
                                <div class="fw-bold fs-2 text-primary lh-1">
                                    {{ $billing->scheduled_end_at->copy()->addMinutes((int)($extendHours * 60))->format('H:i') }}
                                </div>
                                <div class="text-muted small mt-1">
                                    {{ $billing->scheduled_end_at->copy()->addMinutes((int)($extendHours * 60))->format('d M Y') }}
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button class="btn btn-light flex-fill"
                            @click="$wire.set('showExtendModal', false)">
                            Batal
                        </button>
                        <button class="btn btn-primary flex-fill fw-semibold"
                            wire:click="extendBilling"
                            wire:loading.attr="disabled"
                            wire:target="extendBilling">
                            <span wire:loading.remove wire:target="extendBilling">
                                <i class="fa-solid fa-check me-1"></i> Terapkan
                            </span>
                            <span wire:loading wire:target="extendBilling">
                                <span class="spinner-border spinner-border-sm me-1"></span> Memproses...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif


    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- MODAL: KONFIRMASI SELESAIKAN PERMAINAN                    --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    @if($showFinishModal)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title text-danger fw-semibold">
                            <i class="fa-solid fa-stop-circle me-2"></i>
                            Selesaikan Permainan
                        </h5>
                        <button type="button" class="btn-close"
                            @click="$wire.set('showFinishModal', false)"></button>
                    </div>
                    <div class="modal-body pt-2">
                        <p class="text-muted mb-3">
                            Billing <strong class="font-monospace">{{ $billing->billing_code }}</strong>
                            akan diselesaikan. Meja akan dikosongkan dan total tagihan dihitung secara final.
                        </p>
                        <div class="p-3 rounded-3 bg-light border text-center">
                            <div class="text-muted small mb-1">Estimasi Total Tagihan</div>
                            <div class="fw-bold fs-2 text-success lh-1">
                                {{ $billing->formatted_current_total }}
                            </div>
                            @if($billing->elapsed_formatted)
                                <div class="text-muted small mt-2">
                                    Durasi berjalan: <strong>{{ $billing->elapsed_formatted }}</strong>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 gap-2">
                        <button class="btn btn-secondary flex-fill"
                            @click="$wire.set('showFinishModal', false)">
                            Batal
                        </button>
                        <button class="btn btn-danger flex-fill fw-semibold"
                            wire:click="finishBilling"
                            wire:loading.attr="disabled"
                            wire:target="finishBilling">
                            <span wire:loading.remove wire:target="finishBilling">
                                <i class="fa-solid fa-stop me-1"></i> Ya, Selesaikan
                            </span>
                            <span wire:loading wire:target="finishBilling">
                                <span class="spinner-border spinner-border-sm me-1"></span> Memproses...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
