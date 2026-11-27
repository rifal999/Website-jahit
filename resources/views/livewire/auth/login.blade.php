{{-- Halaman login admin: memakai username dan password --}}
<x-layouts::admin-login :title="__('Login Admin')">
    <div class="sj-card sj-rise flex flex-col gap-6 p-7 sm:p-8">
        <div class="space-y-3">
        <div class="flex items-center gap-2">
                <span class="flex size-10 items-center justify-center rounded-xl bg-accent text-lg font-bold text-accent-foreground shadow-sm">S</span>
                <span class="text-lg font-bold tracking-tight">SiJahit</span>
            </div>
            <div>
                <h1 class="sj-title" style="font-size: 1.6rem; margin-top: 0;">{{ __('Login Admin') }}</h1>
                <p class="sj-subtitle">{{ __('Masukkan username dan password untuk masuk') }}</p>
            </div>
        </div>

        {{-- Pesan status (misalnya setelah logout) --}}
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            {{-- Kolom username (sebelumnya email) --}}
            <flux:input
                name="username"
                :label="__('Username')"
                :value="old('username')"
                type="text"
                icon="user"
                required
                autofocus
                autocomplete="username"
                placeholder="admin"
            />

            {{-- Kolom password --}}
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                icon="lock-closed"
                required
                autocomplete="current-password"
                :placeholder="__('Password')"
                viewable
            />

            {{-- Centang agar tetap login di perangkat ini --}}
            <flux:checkbox name="remember" :label="__('Ingat saya')" :checked="old('remember')" />

            <flux:button variant="primary" type="submit" icon-trailing="arrow-right" class="w-full" data-test="login-button">
                {{ __('Masuk') }}
            </flux:button>
        </form>
    </div>
</x-layouts::admin-login>