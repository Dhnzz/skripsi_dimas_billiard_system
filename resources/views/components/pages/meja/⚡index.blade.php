<?php

use App\Models\Table;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app', ['title' => 'Manajemen Meja', 'breadcrumbs' => [['title' => 'Manajemen Meja', 'url' => '#']]])] class extends Component {
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDir = 'desc';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function setSortBy($sortByField)
    {
        if ($this->sortBy === $sortByField) {
            if ($this->sortDir === 'asc') {
                $this->sortDir = 'desc';
            } else {
                $this->sortBy = 'created_at';
                $this->sortDir = 'desc';
            }
            return;
        }

        $this->sortBy = $sortByField;
        $this->sortDir = 'asc';
    }

    #[Computed]
    public function tables()
    {
        $query = Table::query();

        if (!empty($this->search)) {
            $search = $this->search;

            // Mapping label badge ke nilai enum status
            $statusMap = [
                'tersedia' => 'available',
                'tidak tersedia' => 'occupied',
                'maintenance' => 'maintenance',
            ];

            // Mapping label badge ke nilai boolean is_active
            $activeMap = [
                'aktif' => 1,
                'nonaktif' => 0,
            ];

            // Cari status enum yang cocok dengan kata pencarian
            $matchedStatuses = [];
            foreach ($statusMap as $label => $value) {
                if (str_contains($label, strtolower($search))) {
                    $matchedStatuses[] = $value;
                }
            }

            // Cari is_active boolean yang cocok dengan kata pencarian
            $matchedActive = null;
            foreach ($activeMap as $label => $value) {
                if (str_contains($label, strtolower($search))) {
                    $matchedActive = $value;
                    break;
                }
            }

            $query->where(function ($q) use ($search, $matchedStatuses, $matchedActive) {
                $q->where('name', 'like', '%' . $search . '%');

                if (!empty($matchedStatuses)) {
                    $q->orWhereIn('status', $matchedStatuses);
                }

                if ($matchedActive !== null) {
                    $q->orWhere('is_active', $matchedActive);
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

    public function deleteTable($id)
    {
        $table = Table::findOrFail($id);
        $table->delete();

        $this->dispatch('notify', message: 'Data meja berhasil dihapus!', type: 'success');
    }
};
?>

<div>
    <div class="row">
        <div class="col-md-12 col-lg-12">
            <div class="overflow-hidden card">
                <div class="flex-wrap card-header d-flex justify-content-between">
                    <div class="header-title">
                        <h4 class="mb-2 card-title">Data Meja</h4>
                    </div>
                    @role('owner')
                    <a href="{{ route('owner.meja.create') }}" wire:navigate class="btn btn-sm btn-success fs-6 display-6">
                        <small>
                            <i class="fa-solid fa-plus"></i> Tambah Meja
                        </small>
                    </a>
                    @endrole
                </div>
                <div class="p-0 card-body">
                    <div class="d-flex justify-content-between align-items-center p-3">

                        {{-- Per Page --}}
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

                        {{-- Search --}}
                        <div>
                            <input type="text" wire:model.live.debounce.300ms="search"
                                class="form-control form-control-sm" placeholder="Cari meja...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="basic-table" class="table mb-0 table-striped" role="grid">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th wire:click="setSortBy('name')" style="cursor: pointer;">
                                        Nama Meja
                                        @if ($sortBy !== 'name')
                                            <i class="fa-solid fa-sort ms-1 text-muted"></i>
                                        @elseif($sortDir === 'asc')
                                            <i class="fa-solid fa-sort-up ms-1"></i>
                                        @else
                                            <i class="fa-solid fa-sort-down ms-1"></i>
                                        @endif
                                    </th>
                                    <th wire:click="setSortBy('status')" style="cursor: pointer;">
                                        Kondisi Meja
                                        @if ($sortBy !== 'status')
                                            <i class="fa-solid fa-sort ms-1 text-muted"></i>
                                        @elseif($sortDir === 'asc')
                                            <i class="fa-solid fa-sort-up ms-1"></i>
                                        @else
                                            <i class="fa-solid fa-sort-down ms-1"></i>
                                        @endif
                                    </th>
                                    <th wire:click="setSortBy('is_active')" style="cursor: pointer;">
                                        Status
                                        @if ($sortBy !== 'is_active')
                                            <i class="fa-solid fa-sort ms-1 text-muted"></i>
                                        @elseif($sortDir === 'asc')
                                            <i class="fa-solid fa-sort-up ms-1"></i>
                                        @else
                                            <i class="fa-solid fa-sort-down ms-1"></i>
                                        @endif
                                    </th>
                                    <th class="text-center">Opsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->tables as $table)
                                    <tr>
                                        <td>
                                            {{ $table->table_number }}
                                        </td>
                                        <td>
                                            {{ $table->name }}
                                        </td>
                                        <td class="align-middle fw-medium">
                                            @if ($table->status == 'available')
                                                <span class="badge bg-success">Tersedia</span>
                                            @elseif($table->status == 'occupied')
                                                <span class="badge bg-danger">Tidak Tersedia</span>
                                            @else
                                                <span class="badge bg-warning">Maintenance</span>
                                            @endif
                                        </td>
                                        <td class="align-middle fw-medium">
                                            @if ($table->is_active)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-danger">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            <a class="btn btn-sm btn-icon btn-info rounded-circle"
                                                data-bs-toggle="tooltip" data-bs-placement="top" title="Detail"
                                                href="{{ auth()->user()->hasRole('owner') ? route('owner.meja.show', $table->id) : route('kasir.meja.show', $table->id) }}" wire:navigate>
                                                <span class="btn-inner">
                                                    <i class="fa-solid fa-eye"></i>
                                                </span>
                                            </a>
                                            @role('owner')
                                            <a class="btn btn-sm btn-icon btn-warning rounded-circle"
                                                data-bs-toggle="tooltip" data-bs-placement="top" title="Edit"
                                                href="{{ route('owner.meja.edit', $table->id) }}" wire:navigate>
                                                <span class="btn-inner">
                                                    <i class="fa fa-pen"></i>
                                                </span>
                                            </a>
                                            <button class="btn btn-sm btn-icon btn-danger rounded-circle"
                                                data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus"
                                                @click="
                                                    Swal.fire({
                                                        title: 'Hapus Meja?',
                                                        text: 'Data meja {{ $table->name }} akan dihapus secara permanen!',
                                                        icon: 'warning',
                                                        showCancelButton: true,
                                                        confirmButtonColor: '#d33',
                                                        cancelButtonColor: '#6c757d',
                                                        confirmButtonText: 'Ya, Hapus!',
                                                        cancelButtonText: 'Batal'
                                                    }).then((result) => {
                                                        if (result.isConfirmed) {
                                                            $wire.deleteTable({{ $table->id }})
                                                        }
                                                    })
                                                ">
                                                <span class="btn-inner">
                                                    <i class="fa fa-trash"></i>
                                                </span>
                                            </button>
                                            @endrole
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            Tidak ada data meja yang ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="p-3 pb-1 d-flex justify-content-end">
                            {{ $this->tables->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
