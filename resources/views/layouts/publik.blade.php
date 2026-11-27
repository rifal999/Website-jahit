<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'SiJahit - Toko Jahit Cikopak' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="pub-bg flex min-h-screen flex-col bg-orange-50 text-zinc-800 antialiased">
    {{-- Menu atas --}}
    <header class="sticky top-0 z-30 border-b border-rose-100 bg-orange-50/85 backdrop-blur">
        <div aria-hidden class="h-1 bg-gradient-to-r from-rose-600 via-amber-400 to-rose-600"></div>
        <nav class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <span class="flex size-9 items-center justify-center rounded-xl bg-rose-600 text-lg font-bold text-white shadow-sm shadow-rose-200">S</span>
                <span class="text-lg font-bold tracking-tight">SiJahit</span>
            </a>

            <div class="flex items-center gap-1 text-sm font-medium">
                <a href="{{ route('booking') }}"
                   class="flex items-center gap-1.5 rounded-lg px-3 py-2 transition hover:bg-rose-50 hover:text-rose-700 {{ request()->routeIs('booking') ? 'bg-rose-50 text-rose-700' : 'text-zinc-600' }}">
                    <flux:icon name="calendar-days" class="size-4" />
                    Booking
                </a>
                <a href="{{ route('booking.status') }}"
                   class="flex items-center gap-1.5 rounded-lg px-3 py-2 transition hover:bg-rose-50 hover:text-rose-700 {{ request()->routeIs('booking.status') ? 'bg-rose-50 text-rose-700' : 'text-zinc-600' }}">
                    <flux:icon name="magnifying-glass" class="size-4" />
                    Cek Status
                </a>
            </div>
        </nav>
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="bg-gradient-to-b from-rose-900 to-rose-950 text-rose-100">
        <div class="mx-auto grid max-w-5xl gap-8 px-4 py-10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="space-y-3 lg:col-span-2">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-rose-500 text-lg font-bold text-white">S</span>
                    <span class="text-lg font-bold tracking-tight text-white">SiJahit</span>
                </a>
                <p class="text-sm font-semibold text-amber-300">Toko Jahit Cikopak</p>
                <p class="max-w-sm text-sm text-rose-200">
                    Booking jahitan dan pantau status pengerjaan tanpa antre. Pilih tanggal, kirim data, lalu tunggu kabar dari toko.
                </p>
            </div>

            <div class="space-y-3 text-sm">
                <h3 class="font-semibold text-white">Jam &amp; lokasi</h3>
                <p class="flex items-start gap-2 text-rose-200">
                    <flux:icon name="clock" class="mt-0.5 size-4 shrink-0 text-amber-300" />
                    <span>Senin - Sabtu<br>09.00 - 17.30 WIB</span>
                </p>
                <p class="flex items-start gap-2 text-rose-200">
                    <flux:icon name="map-pin" class="mt-0.5 size-4 shrink-0 text-amber-300" />
                    <span>Cikopak, (Jl. Alternative Bukit indah - Purwakarta)</span>
                </p>
            </div>

            <div class="space-y-3 text-sm">
                <h3 class="font-semibold text-white">Tautan cepat</h3>
                <ul class="space-y-2 text-rose-200">
                    <li><a href="{{ route('home') }}" class="transition hover:text-amber-300">Beranda</a></li>
                    <li><a href="{{ route('booking') }}" class="transition hover:text-amber-300">Booking Sekarang</a></li>
                    <li><a href="{{ route('booking.status') }}" class="transition hover:text-amber-300">Cek Status Jahitan</a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-rose-800 py-4 text-center text-sm text-rose-300">
            &copy; {{ date('Y') }} Toko Jahit Cikopak
        </div>
    </footer>
</body>
</html>