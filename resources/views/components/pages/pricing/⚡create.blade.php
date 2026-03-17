<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\Pricing;

new #[Layout('layouts.app', ['title' => 'Tambah Tarif', 'breadcrumbs' => [['title' => 'Manajemen Tarif', 'url' => '/owner/pricing'], ['title' => 'Tambah Tarif', 'url' => '#']]])] class extends Component {

    public $name = '';
    public $price_per_hour = '';
    public $start_time = '';
    public $end_time = '';
    public $is_active = true;

    // apply_days: array of selected days
    public $apply_days = [];

    public array $dayOptions = [
        'senin'  => 'Senin',
        'selasa' => 'Selasa',
        'rabu'   => 'Rabu',
        'kamis'  => 'Kamis',
        'jumat'  => 'Jumat',
        'sabtu'  => 'Sabtu',
        'minggu' => 'Minggu',
    ];

    public function messages()
    {
        return [
            'name.required' => 'Nama tarif harus diisi',
            'name.max' => 'Nama tarif harus berisi maksimal 255 karakter',
            'price_per_hour.required' => 'Harga per jam harus diisi',
            'price_per_hour.integer' => 'Harga per jam harus berupa angka bulat',
            'price_per_hour.min' => 'Harga per jam harus minimal 0',
            'end_time.after' => 'Jam selesai harus setelah jam mulai',
        ];
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'price_per_hour' => 'required|integer|min:0',
            'start_time' => 'nullable',
            'end_time' => 'nullable|after:start_time',
            'apply_days' => 'nullable|array',
            'apply_days.*' => 'in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
        ]);

        Pricing::create([
            'name' => $this->name,
            'price_per_hour' => $this->price_per_hour,
            'apply_days' => empty($this->apply_days) ? null : array_values($this->apply_days),
            'start_time' => $this->start_time ?: null,
            'end_time' => $this->end_time ?: null,
            'is_active' => $this->is_active,
            'created_by' => auth()->id(),
        ]);

        session()->flash('success', 'Data tarif berhasil ditambahkan!');
        return $this->redirectRoute('owner.pricing.index', navigate: true);
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
                            <h4 class="card-title">Informasi Tarif Baru</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">

                            {{-- Nama Tarif --}}
                            <div class="form-group col-md-6">
                                <label class="form-label" for="name">Nama Tarif:</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    id="name" wire:model="name" placeholder="Contoh: Tarif Reguler, Tarif Weekend">
                                @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            {{-- Harga per Jam --}}
                            <div class="form-group col-md-6"
                                x-data="{
                                    display: '',
                                    formatRupiah(val) {
                                        if (!val && val !== 0) return '';
                                        return parseInt(val).toLocaleString('id-ID');
                                    },
                                    init() {
                                        const raw = $wire.get('price_per_hour');
                                        this.display = raw ? this.formatRupiah(raw) : '';
                                    },
                                    onInput(e) {
                                        const digits = e.target.value.replace(/\D/g, '');
                                        this.display = digits ? this.formatRupiah(parseInt(digits)) : '';
                                        $wire.set('price_per_hour', digits ? parseInt(digits) : '');
                                    }
                                }">
                                <label class="form-label" for="price_per_hour">Harga per Jam:</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text"
                                        class="form-control @error('price_per_hour') is-invalid @enderror"
                                        id="price_per_hour"
                                        x-model="display"
                                        @input="onInput($event)"
                                        placeholder="Contoh: 25.000">
                                    @error('price_per_hour')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Jam Mulai --}}
                            <div class="form-group col-md-6">
                                <label class="form-label" for="start_time">
                                    Jam Mulai:
                                    <small class="text-muted">(kosongkan jika berlaku sepanjang hari)</small>
                                </label>
                                <input type="time" class="form-control @error('start_time') is-invalid @enderror"
                                    id="start_time" wire:model="start_time">
                                @error('start_time') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            {{-- Jam Selesai --}}
                            <div class="form-group col-md-6">
                                <label class="form-label" for="end_time">
                                    Jam Selesai:
                                    <small class="text-muted">(kosongkan jika berlaku sepanjang hari)</small>
                                </label>
                                <input type="time" class="form-control @error('end_time') is-invalid @enderror"
                                    id="end_time" wire:model="end_time">
                                @error('end_time') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            {{-- Berlaku Hari --}}
                            <div class="form-group col-md-12">
                                <label class="form-label d-block">
                                    Berlaku Hari:
                                    <small class="text-muted">(kosongkan jika berlaku semua hari)</small>
                                </label>
                                <div class="d-flex flex-wrap gap-3 mt-1">
                                    @foreach ($dayOptions as $value => $label)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                id="day_{{ $value }}"
                                                wire:model="apply_days"
                                                value="{{ $value }}">
                                            <label class="form-check-label" for="day_{{ $value }}">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('apply_days') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Status --}}
                            <div class="form-group col-md-6 mt-1">
                                <label class="form-label d-block">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="is_active"
                                        wire:model="is_active" @if($is_active) checked @endif>
                                    <label class="form-check-label" for="is_active">Aktifkan tarif ini</label>
                                </div>
                            </div>

                        </div>
                        <hr>
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('owner.pricing.index') }}" wire:navigate class="btn btn-danger me-2">Batal</a>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save">Tambah Data</span>
                                <span wire:loading wire:target="save">Menyimpan...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>