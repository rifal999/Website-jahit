<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Profile settings')]
class Profile extends Component
{
    public string $name = '';

    public string $username = '';

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->username = Auth::user()->username;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        // Fortify mengubah username saat login menjadi huruf kecil,
        // jadi username juga disimpan dalam huruf kecil agar tetap cocok
        $this->username = Str::lower($this->username);

        // Username harus unik, kecuali milik admin yang sedang login
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique(User::class, 'username')->ignore($user->id),
            ],
        ]);

        $user->fill($validated)->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }
}