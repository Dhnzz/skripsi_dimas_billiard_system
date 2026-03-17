<?php

use App\Models\Package;
use App\Models\Pricing;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app', ['title' => 'Edit Paket', 'breadcrumbs' => [['title' => 'Manajemen Paket', 'url' => '/owner/package'], ['title' => 'Edit Paket', 'url' => '#']]])] class extends Component {

    public $packageId;
    public $name = '';
    public $type = 'normal';
    public $pricing_id = '';
    public $duration_hours = '';
    public $price = '';
    public $description = '';
    public $is_active = true;

    public function getPricingsProperty()
    {
        return Pricing::where('is_active', true)->orderBy('name')->get();
    }

    public function mount($id)
    {
        $pkg = Package::findOrFail($id);

        $this->packageId = $pkg->id;
        $this->name = $pkg->name;
        $this->type = $pkg->type;
        $this->pricing_id = $pkg->pricing_id;
        $this->duration_hours = $pkg->duration_hours;
        $this->price = $pkg->price ? (int) $pkg->price : '';
        $this->description = $pkg->description ?? '';
        $this->is_active = $pkg->is_active;
    }

    public function updatedType()
    {
        if ($this->type === 'loss') {
            $this->price = '';
            $this->duration_hours = '';
        }
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama paket harus diisi',
            'name.max' => 'Nama paket maksimal 255 karakter',
            'type.required' => 'Tipe paket harus dipilih',
            'type.in' => 'Tipe paket tidak valid',
            'pricing_id.required' => 'Tarif dasar harus dipilih',
            'pricing_id.exists' => 'Tarif dasar tidak ditemukan',
            'duration_hours.required_if' => 'Durasi harus diisi untuk paket Normal',
            'duration_hours.numeric' => 'Durasi harus berupa angka',
            'duration_hours.min' => 'Durasi minimal 0.5 jam',
            'price.required_if' => 'Harga paket harus diisi untuk tipe Normal',
            'price.integer' => 'Harga harus berupa angka bulat',
            'price.min' => 'Harga minimal 0',
            'description.max' => 'Deskripsi maksimal 500 karakter',
        ];
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:normal,loss',
            'pricing_id' => 'required|exists:pricings,id',
            'duration_hours' => 'required_if:type,normal|nullable|numeric|min:0.5',
            'price' => 'required_if:type,normal|nullable|integer|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        $pkg = Package::findOrFail($this->packageId);
        $pkg->update([
            'name' => $this->name,
            'type' => $this->type,
            'pricing_id' => $this->pricing_id,
            'price' => $this->type === 'normal' ? $this->price : null,
            'duration_hours' => $this->type === 'normal' ? $this->duration_hours : null,
            'description' => $this->description ?: null,
            'is_active' => $this->is_active,
        ]);

        session()->flash('success', 'Data paket berhasil diperbarui!');
        return $this->redirectRoute('owner.package.index', navigate: true);
    }
};
?>

<div>
    <form wire:submit="save">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="header-title">
                            <h4 class="card-title">Edit Informasi Paket</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">

                            {{-- Nama Paket --}}
                            <div class="form-group col-md-6">
                                <label class="form-label" for="name">Nama Paket:</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    id="name" wire:model="name" placeholder="Contoh: Paket Hemat 2 Jam">
                                @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            {{-- Tipe Paket --}}
                            <div class="form-group col-md-6">
                                <label class="form-label" for="type">Tipe Paket:</label>
                                <select class="form-select @error('type') is-invalid @enderror"
                                    id="type" wire:model.live="type">
                                    <option value="normal">Normal (Harga Tetap)</option>
                                    <option value="loss">Loss (Bayar per Jam)</option>
                                </select>
                                @error('type') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                <div class="form-text text-muted mt-1">
                                    @if ($type === 'normal')
                                        <i class="fa-solid fa-circle-info me-1 text-primary"></i>
                                        <strong>Normal:</strong> Pelanggan membayar harga tetap sesuai durasi paket.
                                    @else
                                        <i class="fa-solid fa-circle-info me-1 text-warning"></i>
                                        <strong>Loss:</strong> Pelanggan membayar sesuai jam yang digunakan (tarif per jam dari tarif dasar).
                                    @endif
                                </div>
                            </div>

                            {{-- Tarif Dasar --}}
                            <div class="form-group col-md-6">
                                <label class="form-label" for="pricing_id">Tarif Dasar:</label>
                                <select class="form-select @error('pricing_id') is-invalid @enderror"
                                    id="pricing_id" wire:model="pricing_id">
                                    <option value="">-- Pilih Tarif --</option>
                                    @foreach ($this->pricings as $pricing)
                                        <option value="{{ $pricing->id }}">
                                            {{ $pricing->name }} ({{ $pricing->formatted_price }}/jam)
                                        </option>
                                    @endforeach
                                </select>
                                @error('pricing_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            {{-- Durasi (Hanya untuk Normal) --}}
                            @if ($type === 'normal')
                            <div class="form-group col-md-6">
                                <label class="form-label" for="duration_hours">
                                    Durasi Bermain:
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control @error('duration_hours') is-invalid @enderror"
                                        id="duration_hours" wire:model="duration_hours"
                                        placeholder="Contoh: 2" min="0.5" step="0.5">
                                    <span class="input-group-text">Jam</span>
                                    @error('duration_hours') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            @endif

                            {{-- Harga (hanya untuk tipe Normal) --}}
                            @if ($type === 'normal')
                                <div class="form-group col-md-6"
                                    x-data="{
                                        display: '',
                                        formatRupiah(val) {
                                            if (!val && val !== 0) return '';
                                            return parseInt(val).toLocaleString('id-ID');
                                        },
                                        init() {
                                            const raw = $wire.get('price');
                                            this.display = raw ? this.formatRupiah(raw) : '';
                                        },
                                        onInput(e) {
                                            const digits = e.target.value.replace(/\D/g, '');
                                            this.display = digits ? this.formatRupiah(parseInt(digits)) : '';
                                            $wire.set('price', digits ? parseInt(digits) : '');
                                        }
                                    }">
                                    <label class="form-label" for="price">Harga Paket:</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text"
                                            class="form-control @error('price') is-invalid @enderror"
                                            id="price"
                                            x-model="display"
                                            @input="onInput($event)"
                                            placeholder="Contoh: 50.000">
                                        @error('price') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            @endif

                            {{-- Deskripsi --}}
                            <div class="form-group col-md-12">
                                <label class="form-label" for="description">Deskripsi:</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                    id="description" wire:model="description" rows="3"
                                    placeholder="Deskripsi singkat tentang paket ini (opsional)"></textarea>
                                @error('description') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            {{-- Status --}}
                            <div class="form-group col-md-6">
                                <label class="form-label d-block">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="is_active"
                                        wire:model="is_active" @if($is_active) checked @endif>
                                    <label class="form-check-label" for="is_active">Aktifkan paket ini</label>
                                </div>
                            </div>

                        </div>
                        <hr>
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('owner.package.index') }}" wire:navigate class="btn btn-danger me-2">Batal</a>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save">Simpan Perubahan</span>
                                <span wire:loading wire:target="save">Menyimpan...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>