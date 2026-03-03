<?php

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app', ['title' => 'Manajemen Kasir', 'breadcrumbs' => [['title' => 'Manajemen Kasir', 'url' => '#']]])] class extends Component {
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
    public function kasirs()
    {
        return User::role('kasir')
            ->where('name', 'like', '%' . $this->search . '%')
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate($this->perPage);
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

    public function deleteKasir($id)
    {
        $kasir = User::findOrFail($id);

        // Hapus avatar dari storage jika ada
        if ($kasir->avatar && Storage::disk('public')->exists($kasir->avatar)) {
            Storage::disk('public')->delete($kasir->avatar);
        }

        $kasir->delete();

        $this->dispatch('notify', message: 'Data kasir berhasil dihapus!', type: 'success');
    }
};
?>

<div>
    <div class="row">
        <div class="col-md-12 col-lg-12">
            <div class="overflow-hidden card">
                <div class="flex-wrap card-header d-flex justify-content-between">
                    <div class="header-title">
                        <h4 class="mb-2 card-title">Data Kasir</h4>
                    </div>
                    <a href="/owner/kasir/create" wire:navigate class="btn btn-sm btn-success fs-6 display-6">
                        <small>
                            <i class="fa-solid fa-plus"></i> Tambah Kasir
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
                                class="form-control form-control-sm" placeholder="Cari nama kasir...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="basic-table" class="table mb-0 table-striped" role="grid">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Foto</th>
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
                                    <th wire:click="setSortBy('phone')" style="cursor: pointer;">
                                        Nomor Telepon
                                        @if ($sortBy !== 'phone')
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
                                @forelse ($this->kasirs as $kasir)
                                    <tr>
                                        <td>
                                            {{ $this->kasirs->firstItem() + $loop->index }}
                                        </td>
                                        <td>
                                            @php
                                                $path = $kasir->avatar
                                                    ? Storage::url($kasir->avatar)
                                                    : asset('dashboard_asset/images/avatars/01.png');
                                            @endphp
                                            <img src="{{ $path }}" class="rounded-circle avatar-50"
                                                alt="">
                                        </td>
                                        <td class="align-middle fw-medium">{{ $kasir->name }}</td>
                                        <td class="align-middle fw-medium">{{ $kasir->phone }}</td>
                                        <td class="align-middle">
                                            @if ($kasir->is_active)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-danger">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            <a class="btn btn-sm btn-icon btn-warning rounded-circle"
                                                data-bs-toggle="tooltip" data-bs-placement="top" title="Edit"
                                                href="{{ route('owner.kasir.edit', $kasir->id) }}" wire:navigate>
                                                <span class="btn-inner">
                                                    <i class="fa fa-pen"></i>
                                                </span>
                                            </a>
                                            <button class="btn btn-sm btn-icon btn-danger rounded-circle"
                                                data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus"
                                                @click="
                                                    Swal.fire({
                                                        title: 'Hapus Kasir?',
                                                        text: 'Data kasir {{ $kasir->name }} akan dihapus secara permanen!',
                                                        icon: 'warning',
                                                        showCancelButton: true,
                                                        confirmButtonColor: '#d33',
                                                        cancelButtonColor: '#6c757d',
                                                        confirmButtonText: 'Ya, Hapus!',
                                                        cancelButtonText: 'Batal'
                                                    }).then((result) => {
                                                        if (result.isConfirmed) {
                                                            $wire.deleteKasir({{ $kasir->id }})
                                                        }
                                                    })
                                                ">
                                                <span class="btn-inner">
                                                    <i class="fa fa-trash"></i>
                                                </span>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            Tidak ada data kasir yang ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="p-3 pb-1 d-flex justify-content-end">
                            {{ $this->kasirs->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
