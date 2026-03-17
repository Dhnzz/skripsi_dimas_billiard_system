<?php

use App\Models\Package;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app', ['title' => 'Manajemen Paket', 'breadcrumbs' => [['title' => 'Manajemen Paket', 'url' => '#']]])] class extends Component {
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDir = 'desc';

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
    public function packages()
    {
        $query = Package::with('pricing');

        if (!empty($this->search)) {
            $searchLower = strtolower($this->search);
            $search = $this->search;

            // Mapping status
            $activeMap = ['aktif' => 1, 'nonaktif' => 0];
            $matchedActive = null;
            foreach ($activeMap as $label => $value) {
                if (str_contains($label, $searchLower)) {
                    $matchedActive = $value;
                    break;
                }
            }

            // Mapping tipe paket
            $typeMap = [
                'normal' => 'normal',
                'loss'   => 'loss',
            ];
            $matchedType = null;
            foreach ($typeMap as $label => $value) {
                if (str_contains($label, $searchLower)) {
                    $matchedType = $value;
                    break;
                }
            }

            $query->where(function ($q) use ($search, $matchedActive, $matchedType) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhereHas('pricing', fn($pq) => $pq->where('name', 'like', '%' . $search . '%'));

                if ($matchedActive !== null) {
                    $q->orWhere('is_active', $matchedActive);
                }
                if ($matchedType !== null) {
                    $q->orWhere('type', $matchedType);
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

    public function deletePackage($id)
    {
        Package::findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Data paket berhasil dihapus!', type: 'success');
    }
};
?>

<div>
    <div class="row">
        <div class="col-md-12">
            <div class="overflow-hidden card">
                <div class="flex-wrap card-header d-flex justify-content-between">
                    <div class="header-title">
                        <h4 class="mb-2 card-title">Data Paket</h4>
                    </div>
                    <a href="{{ route('owner.package.create') }}" wire:navigate
                        class="btn btn-sm btn-success fs-6 display-6">
                        <small><i class="fa-solid fa-plus"></i> Tambah Paket</small>
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
                                class="form-control form-control-sm" placeholder="Cari paket...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0 table-striped" role="grid">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th wire:click="setSortBy('name')" style="cursor:pointer;">
                                        Nama Paket
                                        @if ($sortBy !== 'name') <i class="fa-solid fa-sort ms-1 text-muted"></i>
                                        @elseif($sortDir === 'asc') <i class="fa-solid fa-sort-up ms-1"></i>
                                        @else <i class="fa-solid fa-sort-down ms-1"></i>
                                        @endif
                                    </th>
                                    <th wire:click="setSortBy('type')" style="cursor:pointer;">
                                        Tipe
                                        @if ($sortBy !== 'type') <i class="fa-solid fa-sort ms-1 text-muted"></i>
                                        @elseif($sortDir === 'asc') <i class="fa-solid fa-sort-up ms-1"></i>
                                        @else <i class="fa-solid fa-sort-down ms-1"></i>
                                        @endif
                                    </th>
                                    <th>Tarif Dasar</th>
                                    <th wire:click="setSortBy('duration_hours')" style="cursor:pointer;">
                                        Durasi (Jam)
                                        @if ($sortBy !== 'duration_hours') <i class="fa-solid fa-sort ms-1 text-muted"></i>
                                        @elseif($sortDir === 'asc') <i class="fa-solid fa-sort-up ms-1"></i>
                                        @else <i class="fa-solid fa-sort-down ms-1"></i>
                                        @endif
                                    </th>
                                    <th>Harga</th>
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
                                @forelse ($this->packages as $pkg)
                                    <tr>
                                        <td>{{ $this->packages->firstItem() + $loop->index }}</td>
                                        <td class="align-middle fw-medium">{{ $pkg->name }}</td>
                                        <td class="align-middle">
                                            @if ($pkg->type === 'normal')
                                                <span class="badge bg-primary">Normal</span>
                                            @else
                                                <span class="badge bg-warning text-light">Loss</span>
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            {{ $pkg->pricing?->name ?? '-' }}
                                        </td>
                                        <td class="align-middle">
                                            {{ $pkg->duration_hours ? number_format((int)$pkg->duration_hours, 0, ',', '.') . ' jam' : '-' }}
                                        </td>
                                        <td class="align-middle fw-medium">
                                            {{ $pkg->formatted_price }}
                                        </td>
                                        <td class="align-middle">
                                            @if ($pkg->is_active)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-danger">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            <a class="btn btn-sm btn-icon btn-warning rounded-circle"
                                                data-bs-toggle="tooltip" title="Edit"
                                                href="{{ route('owner.package.edit', $pkg->id) }}" wire:navigate>
                                                <span class="btn-inner"><i class="fa-solid fa-pen"></i></span>
                                            </a>
                                            <button class="btn btn-sm btn-icon btn-danger rounded-circle"
                                                data-bs-toggle="tooltip" title="Hapus"
                                                @click="
                                                    Swal.fire({
                                                        title: 'Hapus Paket?',
                                                        text: 'Paket {{ $pkg->name }} akan dihapus secara permanen!',
                                                        icon: 'warning',
                                                        showCancelButton: true,
                                                        confirmButtonColor: '#d33',
                                                        cancelButtonColor: '#6c757d',
                                                        confirmButtonText: 'Ya, Hapus!',
                                                        cancelButtonText: 'Batal'
                                                    }).then((result) => {
                                                        if (result.isConfirmed) {
                                                            $wire.deletePackage({{ $pkg->id }})
                                                        }
                                                    })
                                                ">
                                                <span class="btn-inner"><i class="fa-solid fa-trash"></i></span>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            Tidak ada data paket yang ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="p-3 pb-1 d-flex justify-content-end">
                            {{ $this->packages->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>