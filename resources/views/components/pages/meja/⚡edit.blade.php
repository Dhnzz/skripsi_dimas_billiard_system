<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\Table;

new #[Layout('layouts.app', ['title' => 'Edit Meja', 'breadcrumbs' => [['title' => 'Manajemen Meja', 'url' => '/owner/meja'], ['title' => 'Edit Meja', 'url' => '#']]])] class extends Component {
    public $tableId;
    public $table_number = '';
    public $name = '';
    public $description = '';
    public $status = 'available';
    public $is_active = true;

    public function mount($id)
    {
        $table = Table::findOrFail($id);

        $this->tableId = $table->id;
        $this->table_number = $table->table_number;
        $this->name = $table->name;
        $this->description = $table->description;
        $this->status = $table->status;
        $this->is_active = $table->is_active;
    }

    public function messages()
    {
        return [
            'table_number.required' => 'Nomor meja harus diisi',
            'table_number.max' => 'Nomor meja harus berisi maksimal 10 karakter',
            'table_number.unique' => 'Nomor meja sudah terdaftar',
            'name.required' => 'Nama meja harus diisi',
            'name.min' => 'Nama meja harus berisi minimal 3 karakter',
            'name.max' => 'Nama meja harus berisi maksimal 255 karakter',
            'description.max' => 'Deskripsi harus berisi maksimal 500 karakter',
            'status.required' => 'Kondisi meja harus dipilih',
            'status.in' => 'Kondisi meja tidak valid',
        ];
    }

    public function save()
    {
        $this->validate([
            'table_number' => 'required|string|max:10|unique:tables,table_number,' . $this->tableId,
            'name' => 'required|string|min:3|max:255',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:available,occupied,maintenance',
        ]);

        $table = Table::findOrFail($this->tableId);

        $table->update([
            'table_number' => $this->table_number,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'is_active' => $this->is_active,
        ]);

        session()->flash('success', 'Data meja berhasil diperbarui!');

        return $this->redirectRoute('owner.meja.index', navigate: true);
    }
};
?>

<div>
    <form wire:submit="save">
        <div class="row">
            <div class="col-xl-12 col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">Edit Informasi Meja</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="new-user-info">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="table_number">Nomor Meja:</label>
                                    <input type="text"
                                        class="form-control @error('table_number') is-invalid @enderror"
                                        id="table_number" wire:model="table_number"
                                        placeholder="Contoh: M1, M2, VIP1...">
                                    @error('table_number')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="name">Nama Meja:</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" wire:model="name" placeholder="Contoh: Meja VIP 1">
                                    @error('name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="status">Kondisi Meja:</label>
                                    <select class="form-select @error('status') is-invalid @enderror" id="status"
                                        wire:model="status">
                                        <option value="available">Tersedia</option>
                                        <option value="occupied">Tidak Tersedia</option>
                                        <option value="maintenance">Maintenance</option>
                                    </select>
                                    @error('status')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label d-block">Status Meja</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="is_active"
                                            wire:model="is_active" @if($is_active) checked @endif>
                                        <label class="form-check-label" for="is_active">
                                            Aktifkan meja ini
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group col-md-12">
                                    <label class="form-label" for="description">Deskripsi:</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" wire:model="description"
                                        rows="3" placeholder="Deskripsi singkat tentang meja (opsional)"></textarea>
                                    @error('description')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-end">
                                <a href="{{ route('owner.meja.index') }}" wire:navigate
                                    class="btn btn-danger me-2">Batal</a>
                                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"
                                    wire:target="save">
                                    <span wire:loading.remove wire:target="save">Simpan Perubahan</span>
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
