<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\Table;

new #[Layout('layouts.app', ['title' => 'Detail Meja', 'breadcrumbs' => [['title' => 'Manajemen Meja', 'url' => '/owner/meja'], ['title' => 'Detail Meja', 'url' => '#']]])] class extends Component {
    public Table $table;

    public function mount($id)
    {
        $this->table = Table::findOrFail($id);
    }
};
?>

<div>
    <div class="row">
        {{-- Info Utama --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <div class="header-title">
                        <h4 class="card-title">Identitas Meja</h4>
                    </div>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center"
                            style="width: 90px; height: 90px;">
                            <span class="text-white fw-bold fs-1">{{ $table->table_number }}</span>
                        </div>
                    </div>
                    <h5 class="mb-1">{{ $table->name }}</h5>
                    <p class="text-muted small mb-3">Nomor: {{ $table->table_number }}</p>

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
                        <a href="{{ route('owner.meja.edit', $table->id) }}" wire:navigate
                            class="btn btn-sm btn-warning">
                            <i class="fa-solid fa-pen me-1"></i> Edit
                        </a>
                        <a href="{{ route('owner.meja.index') }}" wire:navigate class="btn btn-sm btn-secondary">
                            <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                        </a>
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
