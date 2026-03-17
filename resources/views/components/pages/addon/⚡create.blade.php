<?php

use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Livewire\Component;
use App\Models\Addon;

new #[Layout('layouts.app', ['title' => 'Tambah Addon', 'breadcrumbs' => [['title' => 'Manajemen Addon', 'url' => '/owner/addon'], ['title' => 'Tambah Addon', 'url' => '#']]])] class extends Component {
    use WithFileUploads;

    public $name = '';
    public $category = '';
    public $price = '';
    public $stock = '';
    public $image;
    public $is_active = true;

    public function updatedImage()
    {
        $this->resetValidation('image');
        $this->validateOnly(
            'image',
            [
                'image' => 'nullable|image|max:1024',
            ],
            [
                'image.image' => 'File harus berisi format gambar',
                'image.max' => 'Gambar harus berisi maksimal 1MB',
            ],
        );
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama addon harus diisi',
            'name.min' => 'Nama addon harus berisi minimal 3 karakter',
            'name.max' => 'Nama addon harus berisi maksimal 255 karakter',
            'category.required' => 'Kategori harus diisi',
            'category.max' => 'Kategori harus berisi maksimal 100 karakter',
            'price.required' => 'Harga harus diisi',
            'price.numeric' => 'Harga harus berupa angka',
            'price.min' => 'Harga harus berisi minimal 0',
            'stock.integer' => 'Stok harus berupa angka bulat',
            'stock.min' => 'Stok harus berisi minimal 0',
            'image.image' => 'File harus berisi format gambar',
            'image.max' => 'Gambar harus berisi maksimal 1MB',
        ];
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|min:3|max:255',
            'category' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'image' => 'nullable|image|max:1024',
        ]);

        $imageFileName = null;
        if ($this->image) {
            $imageFileName = $this->image->store('addons', 'public');
        }

        Addon::create([
            'name' => $this->name,
            'category' => $this->category,
            'price' => $this->price,
            'stock' => $this->stock !== '' ? $this->stock : null,
            'image' => $imageFileName,
            'is_active' => $this->is_active,
            'created_by' => auth()->id(),
        ]);

        session()->flash('success', 'Data addon berhasil ditambahkan!');

        return $this->redirectRoute('owner.addon.index', navigate: true);
    }
};
?>

<div>
    <form wire:submit="save">
        <div class="row">
            <div class="col-xl-3 col-lg-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">Gambar Addon</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <div class="profile-img-edit position-relative d-flex justify-content-center mb-3">
                                @if ($image)
                                    <img src="{{ $image->temporaryUrl() }}" alt="addon-pic" class="rounded"
                                        style="object-fit: cover; width: 150px; height: 150px;">
                                @else
                                    <div class="rounded bg-light d-flex align-items-center justify-content-center"
                                        style="width: 150px; height: 150px;">
                                        <i class="fa-solid fa-image fa-3x text-muted"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="img-extension mt-3">
                                <div class="d-inline-block align-items-center w-100">
                                    <input type="file" wire:model="image" class="form-control form-control-sm"
                                        accept="image/*">
                                </div>
                                <div wire:loading wire:target="image" class="text-info small mt-2">Mengunggah...</div>
                                @error('image')
                                    <span class="text-danger small d-block mt-2">{{ $message }}</span>
                                @enderror
                                <p class="mb-0 mt-2 text-muted small">Pilih foto format .jpg/.png. Maks. 1MB.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-9 col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">Informasi Addon Baru</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="new-user-info">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="name">Nama Addon:</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" wire:model="name" placeholder="Contoh: Es Teh Manis">
                                    @error('name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="category">Kategori:</label>
                                    <input type="text" class="form-control @error('category') is-invalid @enderror"
                                        id="category" wire:model="category"
                                        placeholder="Contoh: Minuman, Makanan, Snack">
                                    @error('category')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="price">Harga (Rp):</label>
                                    <input type="number" class="form-control @error('price') is-invalid @enderror"
                                        id="price" wire:model="price" placeholder="Contoh: 15000" min="0">
                                    @error('price')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="stock">Stok:
                                        <small class="text-muted">(kosongkan jika tidak terbatas)</small>
                                    </label>
                                    <input type="number" class="form-control @error('stock') is-invalid @enderror"
                                        id="stock" wire:model="stock" placeholder="Jumlah stok" min="0">
                                    @error('stock')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label d-block">Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="is_active"
                                            wire:model="is_active" @if ($is_active) checked @endif>
                                        <label class="form-check-label" for="is_active">Aktifkan addon ini</label>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-end">
                                <a href="{{ route('owner.addon.index') }}" wire:navigate
                                    class="btn btn-danger me-2">Batal</a>
                                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"
                                    wire:target="save">
                                    <span wire:loading.remove wire:target="save">Tambah Data</span>
                                    <span wire:loading wire:target="save">Menyimpan...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
