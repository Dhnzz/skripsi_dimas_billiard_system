<?php

use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

new #[Layout('layouts.app', ['title' => 'Tambah Member', 'breadcrumbs' => [['title' => 'Manajemen Member', 'url' => '/owner/member'], ['title' => 'Tambah Member', 'url' => '#']]])] class extends Component {
    use WithFileUploads;

    public $name = '';
    public $email = '';
    public $phone = '';
    public $password = '';
    public $avatar;

    public $is_active = false;

    public function checkEmail()
    {
        $this->resetValidation('email');
        $this->validateOnly(
            'email',
            [
                'email' => 'required|email|unique:users,email',
            ],
            [
                'email.required' => 'Email harus diisi',
                'email.email' => 'Email harus berisi format email',
                'email.unique' => 'Email sudah terdaftar',
            ],
        );
    }

    public function updatedAvatar()
    {
        $this->resetValidation('avatar');
        $this->validateOnly(
            'avatar',
            [
                'avatar' => 'nullable|image|max:1024',
            ],
            [
                'avatar.image' => 'Avatar harus berisi format gambar',
                'avatar.max' => 'Avatar harus berisi maksimal 1MB',
            ],
        );
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama lengkap harus diisi',
            'name.min' => 'Nama lengkap harus berisi minimal 3 karakter',
            'name.max' => 'Nama lengkap harus berisi maksimal 255 karakter',
            'email.required' => 'Email harus diisi',
            'email.email' => 'Email harus berisi format email',
            'email.unique' => 'Email sudah terdaftar',
            'phone.required' => 'Nomor telepon harus diisi',
            'phone.regex' => 'Nomor telepon harus berisi angka',
            'phone.max' => 'Nomor telepon harus berisi maksimal 15 karakter',
            'password.required' => 'Password harus diisi',
            'password.min' => 'Password harus berisi minimal 6 karakter',
            'avatar.max' => 'Avatar harus berisi maksimal 1MB',
            'avatar.image' => 'Avatar harus berisi format gambar',
        ];
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|regex:/^[0-9]+$/|max:15',
            'password' => 'required|string|min:6',
            'avatar' => 'nullable|image|max:1024',
        ]);

        $avatarFileName = null;
        if ($this->avatar) {
            $avatarFileName = $this->avatar->store('avatars', 'public');
        }

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => Hash::make($this->password),
            'is_active' => $this->is_active,
            'avatar' => $avatarFileName,
        ]);

        $user->assignRole('member');

        session()->flash('success', 'Data member berhasil ditambahkan!');

        return $this->redirectRoute('owner.member.index', navigate: true);
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
                            <h4 class="card-title">Foto Profil</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <div class="profile-img-edit position-relative d-flex justify-content-center mb-3">
                                @if ($avatar)
                                    <img src="{{ $avatar->temporaryUrl() }}" alt="profile-pic"
                                        class="theme-color-default-img profile-pic rounded"
                                        style="object-fit: cover; width: 150px; height: 150px;">
                                @else
                                    <img src="{{ asset('dashboard_asset/images/avatars/01.png') }}" alt="profile-pic"
                                        class="theme-color-default-img profile-pic rounded"
                                        style="object-fit: cover; width: 150px; height: 150px;">
                                @endif
                            </div>
                            <div class="img-extension mt-3">
                                <div class="d-inline-block align-items-center w-100">
                                    <input type="file" wire:model="avatar" class="form-control form-control-sm"
                                        accept="image/*">
                                </div>
                                <div wire:loading wire:target="avatar" class="text-info small mt-2">Mengunggah...</div>
                                @error('avatar')
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
                            <h4 class="card-title">Informasi Member Baru</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="new-user-info">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="name">Nama Lengkap:</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" wire:model="name" placeholder="Nama lengkap member">
                                    @error('name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="phone">Nomor Telepon:</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                        id="phone" wire:model="phone" placeholder="Contoh: 08123456789">
                                    @error('phone')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="email">Alamat Email:</label>
                                    <div class="position-relative">
                                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                                            id="email" wire:model="email" wire:change="checkEmail"
                                            placeholder="Email aktif">
                                        <div wire:loading wire:target="checkEmail"
                                            class="position-absolute top-50 end-0 translate-middle-y me-3"
                                            style="z-index: 5;">
                                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                <span class="visually-hidden">Memeriksa...</span>
                                            </div>
                                        </div>
                                    </div>
                                    @error('email')
                                        <span class="text-danger small mt-1 d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="password">Password Mula:</label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                        id="password" wire:model="password"
                                        placeholder="Password akun (min. 6 karakter)">
                                    @error('password')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-end">
                                <a href="{{ route('owner.member.index') }}" wire:navigate
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
