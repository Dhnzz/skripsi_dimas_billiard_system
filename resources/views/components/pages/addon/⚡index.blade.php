<?php

use App\Models\Addon;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app', ['title' => 'Manajemen Addon', 'breadcrumbs' => [['title' => 'Manajemen Addon', 'url' => '#']]])] class extends Component {
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
    public function addons()
    {
        $query = Addon::query();

        if (!empty($this->search)) {
            $search = $this->search;

            // Mapping label badge ke nilai boolean is_active
            $activeMap = [
                'aktif' => 1,
                'nonaktif' => 0,
            ];

            $matchedActive = null;
            foreach ($activeMap as $label => $value) {
                if (str_contains($label, strtolower($search))) {
                    $matchedActive = $value;
                    break;
                }
            }

            $query->where(function ($q) use ($search, $matchedActive) {
                $q->where('name', 'like', '%' . $search . '%')->orWhere('category', 'like', '%' . $search . '%');

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

    public function deleteAddon($id)
    {
        $addon = Addon::findOrFail($id);

        // Hapus gambar dari storage jika ada
        if ($addon->image && Storage::disk('public')->exists($addon->image)) {
            Storage::disk('public')->delete($addon->image);
        }

        $addon->delete();

        $this->dispatch('notify', message: 'Data addon berhasil dihapus!', type: 'success');
    }
};
?>

<div>
    <div class="row">
        <div class="col-md-12 col-lg-12">
            <div class="overflow-hidden card">
                <div class="flex-wrap card-header d-flex justify-content-between">
                    <div class="header-title">
                        <h4 class="mb-2 card-title">Data Addon</h4>
                    </div>
                    <a href="{{ route('owner.addon.create') }}" wire:navigate
                        class="btn btn-sm btn-success fs-6 display-6">
                        <small>
                            <i class="fa-solid fa-plus"></i> Tambah Addon
                        </small>
                    </a>
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
                                class="form-control form-control-sm" placeholder="Cari addon...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="addon-table" class="table mb-0 table-striped" role="grid">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Gambar</th>
                                    <th wire:click="setSortBy('name')" style="cursor: pointer;">
                                        Nama
                                        @if ($sortBy !== 'name')
                                            <i class="fa-solid fa-sort ms-1 text-muted"></i>
                                        @elseif($sortDir === 'asc')
                                            <i class="fa-solid fa-sort-up ms-1"></i>
                                        @else
                                            <i class="fa-solid fa-sort-down ms-1"></i>
                                        @endif
                                    </th>
                                    <th wire:click="setSortBy('category')" style="cursor: pointer;">
                                        Kategori
                                        @if ($sortBy !== 'category')
                                            <i class="fa-solid fa-sort ms-1 text-muted"></i>
                                        @elseif($sortDir === 'asc')
                                            <i class="fa-solid fa-sort-up ms-1"></i>
                                        @else
                                            <i class="fa-solid fa-sort-down ms-1"></i>
                                        @endif
                                    </th>
                                    <th wire:click="setSortBy('price')" style="cursor: pointer;">
                                        Harga
                                        @if ($sortBy !== 'price')
                                            <i class="fa-solid fa-sort ms-1 text-muted"></i>
                                        @elseif($sortDir === 'asc')
                                            <i class="fa-solid fa-sort-up ms-1"></i>
                                        @else
                                            <i class="fa-solid fa-sort-down ms-1"></i>
                                        @endif
                                    </th>
                                    <th wire:click="setSortBy('stock')" style="cursor: pointer;">
                                        Stok
                                        @if ($sortBy !== 'stock')
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
                                @forelse ($this->addons as $addon)
                                    <tr>
                                        <td>
                                            {{ $this->addons->firstItem() + $loop->index }}
                                        </td>
                                        <td>
                                            <img src="{{ $addon->image_url }}" class="rounded avatar-50"
                                                style="object-fit: cover;" alt="{{ $addon->name }}">
                                        </td>
                                        <td class="align-middle fw-medium">{{ $addon->name }}</td>
                                        <td class="align-middle">
                                            <span class="badge bg-info">{{ $addon->category }}</span>
                                        </td>
                                        <td class="align-middle fw-medium">{{ $addon->formatted_price }}</td>
                                        <td class="align-middle fw-medium">
                                            {{ $addon->stock !== null ? $addon->stock : '∞' }}
                                        </td>
                                        <td class="align-middle">
                                            @if ($addon->is_active)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-danger">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            <a class="btn btn-sm btn-icon btn-warning rounded-circle"
                                                data-bs-toggle="tooltip" data-bs-placement="top" title="Edit"
                                                href="{{ route('owner.addon.edit', $addon->id) }}" wire:navigate>
                                                <span class="btn-inner">
                                                    <i class="fa-solid fa-pen"></i>
                                                </span>
                                            </a>
                                            <button class="btn btn-sm btn-icon btn-danger rounded-circle"
                                                data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus"
                                                @click="
                                                    Swal.fire({
                                                        title: 'Hapus Addon?',
                                                        text: 'Data addon {{ $addon->name }} akan dihapus secara permanen!',
                                                        icon: 'warning',
                                                        showCancelButton: true,
                                                        confirmButtonColor: '#d33',
                                                        cancelButtonColor: '#6c757d',
                                                        confirmButtonText: 'Ya, Hapus!',
                                                        cancelButtonText: 'Batal'
                                                    }).then((result) => {
                                                        if (result.isConfirmed) {
                                                            $wire.deleteAddon({{ $addon->id }})
                                                        }
                                                    })
                                                ">
                                                <span class="btn-inner">
                                                    <i class="fa-solid fa-trash"></i>
                                                </span>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            Tidak ada data addon yang ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="p-3 pb-1 d-flex justify-content-end">
                            {{ $this->addons->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
