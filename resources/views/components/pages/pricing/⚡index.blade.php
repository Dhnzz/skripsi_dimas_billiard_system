<?php

use App\Models\Pricing;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app', ['title' => 'Manajemen Tarif', 'breadcrumbs' => [['title' => 'Manajemen Tarif', 'url' => '#']]])] class extends Component {
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDir = 'desc';

    // Nama hari dalam Bahasa Indonesia → internal value
    protected array $dayLabels = [
        'senin'  => 'senin',
        'selasa' => 'selasa',
        'rabu'   => 'rabu',
        'kamis'  => 'kamis',
        'jumat'  => 'jumat',
        'sabtu'  => 'sabtu',
        'minggu' => 'minggu',
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingPerPage() { $this->resetPage(); }

    public function setSortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
            return;
        }
        $this->sortBy = $field;
        $this->sortDir = 'asc';
    }

    #[Computed]
    public function pricings()
    {
        $query = Pricing::query();

        if (!empty($this->search)) {
            $search = $this->search;
            $searchLower = strtolower($search);

            // Mapping label aktif/nonaktif
            $activeMap = ['aktif' => 1, 'nonaktif' => 0];
            $matchedActive = null;
            foreach ($activeMap as $label => $value) {
                if (str_contains($label, $searchLower)) {
                    $matchedActive = $value;
                    break;
                }
            }

            // Mapping pencarian hari — daftar semua hari yang namanya mengandung kata pencarian
            $allDays = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
            $matchedDays = array_filter($allDays, fn($day) => str_contains($day, $searchLower));

            // Cek apakah user mencari "semua hari" (tarif tanpa apply_days)
            $searchAllDays = str_contains('semua hari', $searchLower) || str_contains('semua', $searchLower);

            $query->where(function ($q) use ($search, $matchedActive, $matchedDays, $searchAllDays) {
                // Cari berdasarkan nama tarif
                $q->where('name', 'like', '%' . $search . '%');

                // Cari berdasarkan status aktif/nonaktif
                if ($matchedActive !== null) {
                    $q->orWhere('is_active', $matchedActive);
                }

                // Cari tarif yang berlaku di hari yang cocok (LIKE pada kolom JSON)
                foreach ($matchedDays as $day) {
                    $q->orWhere('apply_days', 'like', '%"' . $day . '"%');
                }

                // Cari tarif "semua hari" (apply_days null atau kosong)
                if ($searchAllDays) {
                    $q->orWhereNull('apply_days')
                      ->orWhere('apply_days', '[]')
                      ->orWhere('apply_days', 'null');
                }
            });
        }

        return $query->orderBy($this->sortBy, $this->sortDir)->paginate($this->perPage);
    }

    public function mount()
    {
        if (session()->has('success')) {
            $this->dispatch('notify', message: session('success'), type: 'success');
        }
        if (session()->has('error')) {
            $this->dispatch('notify', message: session('error'), type: 'error');
        }
    }

    public function deletePricing($id)
    {
        $pricing = Pricing::findOrFail($id);
        $pricing->delete();
        $this->dispatch('notify', message: 'Data tarif berhasil dihapus!', type: 'success');
    }
};
?>

<div>
    <div class="row">
        <div class="col-md-12">
            <div class="overflow-hidden card">
                <div class="flex-wrap card-header d-flex justify-content-between">
                    <div class="header-title">
                        <h4 class="mb-2 card-title">Data Tarif</h4>
                    </div>
                    <a href="{{ route('owner.pricing.create') }}" wire:navigate
                        class="btn btn-sm btn-success fs-6 display-6">
                        <small><i class="fa-solid fa-plus"></i> Tambah Tarif</small>
                    </a>
                </div>
                <div class="p-0 card-body">
                    <div class="d-flex justify-content-between align-items-center p-3">
                        <div class="d-flex align-items-center">
                            <span class="me-2">Tampilkan</span>
                            <select wire:model.live="perPage" class="form-select form-select-sm w-auto">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                            <span class="ms-2">Entri</span>
                        </div>
                        <div>
                            <input type="text" wire:model.live.debounce.300ms="search"
                                class="form-control form-control-sm" placeholder="Cari tarif...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0 table-striped" role="grid">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th wire:click="setSortBy('name')" style="cursor:pointer;">
                                        Nama Tarif
                                        @if ($sortBy !== 'name') <i class="fa-solid fa-sort ms-1 text-muted"></i>
                                        @elseif($sortDir === 'asc') <i class="fa-solid fa-sort-up ms-1"></i>
                                        @else <i class="fa-solid fa-sort-down ms-1"></i>
                                        @endif
                                    </th>
                                    <th wire:click="setSortBy('price_per_hour')" style="cursor:pointer;">
                                        Harga/Jam
                                        @if ($sortBy !== 'price_per_hour') <i class="fa-solid fa-sort ms-1 text-muted"></i>
                                        @elseif($sortDir === 'asc') <i class="fa-solid fa-sort-up ms-1"></i>
                                        @else <i class="fa-solid fa-sort-down ms-1"></i>
                                        @endif
                                    </th>
                                    <th>Berlaku Hari</th>
                                    <th>Jam Berlaku</th>
                                    <th wire:click="setSortBy('is_active')" style="cursor:pointer;">
                                        Status
                                        @if ($sortBy !== 'is_active') <i class="fa-solid fa-sort ms-1 text-muted"></i>
                                        @elseif($sortDir === 'asc') <i class="fa-solid fa-sort-up ms-1"></i>
                                        @else <i class="fa-solid fa-sort-down ms-1"></i>
                                        @endif
                                    </th>
                                    <th class="text-center">Opsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $dayLabelsDisplay = [
                                        'senin'  => 'Sen',
                                        'selasa' => 'Sel',
                                        'rabu'   => 'Rab',
                                        'kamis'  => 'Kam',
                                        'jumat'  => 'Jum',
                                        'sabtu'  => 'Sab',
                                        'minggu' => 'Min',
                                    ];
                                @endphp
                                @forelse ($this->pricings as $pricing)
                                    <tr>
                                        <td>{{ $this->pricings->firstItem() + $loop->index }}</td>
                                        <td class="align-middle fw-medium">{{ $pricing->name }}</td>
                                        <td class="align-middle fw-medium">{{ $pricing->formatted_price }}/jam</td>
                                        <td class="align-middle">
                                            @if (empty($pricing->apply_days))
                                                <span class="badge bg-secondary">Semua Hari</span>
                                            @else
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach ($pricing->apply_days as $day)
                                                        <span class="badge bg-primary">
                                                            {{ $dayLabelsDisplay[$day] ?? ucfirst($day) }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            @if ($pricing->start_time && $pricing->end_time)
                                                <span class="text-nowrap">
                                                    {{ \Carbon\Carbon::parse($pricing->start_time)->format('H:i') }}
                                                    –
                                                    {{ \Carbon\Carbon::parse($pricing->end_time)->format('H:i') }}
                                                </span>
                                            @else
                                                <span class="text-muted">Sepanjang Hari</span>
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            @if ($pricing->is_active)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-danger">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            <a class="btn btn-sm btn-icon btn-warning rounded-circle"
                                                data-bs-toggle="tooltip" title="Edit"
                                                href="{{ route('owner.pricing.edit', $pricing->id) }}" wire:navigate>
                                                <span class="btn-inner"><i class="fa-solid fa-pen"></i></span>
                                            </a>
                                            <button class="btn btn-sm btn-icon btn-danger rounded-circle"
                                                data-bs-toggle="tooltip" title="Hapus"
                                                @click="
                                                    Swal.fire({
                                                        title: 'Hapus Tarif?',
                                                        text: 'Tarif {{ $pricing->name }} akan dihapus secara permanen!',
                                                        icon: 'warning',
                                                        showCancelButton: true,
                                                        confirmButtonColor: '#d33',
                                                        cancelButtonColor: '#6c757d',
                                                        confirmButtonText: 'Ya, Hapus!',
                                                        cancelButtonText: 'Batal'
                                                    }).then((result) => {
                                                        if (result.isConfirmed) {
                                                            $wire.deletePricing({{ $pricing->id }})
                                                        }
                                                    })
                                                ">
                                                <span class="btn-inner"><i class="fa-solid fa-trash"></i></span>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            Tidak ada data tarif yang ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="p-3 pb-1 d-flex justify-content-end">
                            {{ $this->pricings->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>