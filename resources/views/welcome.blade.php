@php
    $slotHariIni = \App\Models\SlotBooking::whereDate('tanggal', today())->first();
    $slotTerdekat = \App\Models\SlotBooking::whereDate('tanggal', '>=', today())
        ->whereColumn('terpakai', '<', 'kuota')
        ->orderBy('tanggal')
        ->first();

    $sisaHariIni = $slotHariIni ? max(0, $slotHariIni->kuota - $slotHariIni->terpakai) : null;

    $langkah = [
        ['judul' => '1. Pilih tanggal', 'ikon' => 'calendar-days', 'isi' => 'Lihat tanggal yang masih tersedia, lalu pilih hari yang paling cocok untuk Anda.'],
        ['judul' => '2. Isi data dan kirim', 'ikon' => 'pencil-square', 'isi' => 'Lengkapi nama, nomor HP, dan jenis jahitan, lalu kirim. Anda akan mendapat kode booking.'],
        ['judul' => '3. Pantau status', 'ikon' => 'magnifying-glass', 'isi' => 'Masukkan kode booking di halaman Cek Status untuk melihat perkembangan jahitan Anda.'],
    ];
@endphp

<x-layouts::publik title="SiJahit - Toko Jahit Cikopak">

    <section class="relative isolate overflow-hidden bg-gradient-to-br from-rose-100 via-orange-50 to-amber-100">
        <div aria-hidden class="pub-dots pointer-events-none absolute inset-0 -z-10 opacity-60"></div>
        <div aria-hidden class="pointer-events-none absolute -top-24 -left-24 -z-10 size-96 rounded-full bg-rose-300/40 blur-3xl"></div>
        <div aria-hidden class="pointer-events-none absolute top-40 -right-24 -z-10 size-96 rounded-full bg-amber-300/50 blur-3xl"></div>

        <div class="mx-auto grid max-w-5xl items-center gap-10 px-4 py-14 sm:py-20 lg:grid-cols-5 lg:gap-14">

            <div class="lg:col-span-3">
                <span class="inline-flex items-center gap-2 rounded-full border border-amber-300 bg-amber-50 px-3 py-1 text-sm font-medium text-rose-700">
                    <span class="size-2 rounded-full bg-amber-500"></span>
                    Toko Jahit Cikopak
                </span>

                <h1 class="mt-5 text-4xl font-bold tracking-tight sm:text-5xl">
                    Booking jahitan jadi
                    <span class="bg-gradient-to-r from-rose-600 to-amber-500 bg-clip-text text-transparent">lebih mudah</span>
                </h1>

                <p class="mt-4 max-w-xl text-base leading-relaxed text-zinc-600 sm:text-lg">
                    Pilih tanggal yang tersedia, isi data jahitan Anda, lalu pantau status pengerjaannya
                    dari ponsel tanpa perlu datang berulang kali ke toko.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('booking') }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-rose-600 to-rose-500 px-6 py-3 font-semibold text-white shadow-lg shadow-rose-300 transition hover:-translate-y-0.5 hover:shadow-xl">
                        Booking Sekarang
                        <flux:icon name="arrow-right" class="size-4" />
                    </a>
                    <a href="{{ route('booking.status') }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-amber-300 bg-white px-6 py-3 font-semibold text-rose-700 transition hover:bg-amber-50">
                        Cek Status Jahitan
                    </a>
                </div>

                <ul class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-zinc-500">
                    <li class="flex items-center gap-1.5"><flux:icon name="check-circle" class="size-4 text-amber-500" /> Tanpa antre</li>
                    <li class="flex items-center gap-1.5"><flux:icon name="check-circle" class="size-4 text-amber-500" /> Kode booking pribadi</li>
                    <li class="flex items-center gap-1.5"><flux:icon name="check-circle" class="size-4 text-amber-500" /> Status bisa dipantau kapan saja</li>
                </ul>
            </div>

            <div class="relative lg:col-span-2">
                <div aria-hidden class="absolute -inset-3 -z-10 rounded-[2rem] border-2 border-dashed border-amber-300/70"></div>

                <div class="overflow-hidden rounded-2xl border border-rose-100 bg-white shadow-xl shadow-rose-200/60">
                    <div class="flex items-center gap-3 bg-gradient-to-r from-rose-600 to-rose-700 px-6 py-5 text-white">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-white/20">
                            <flux:icon name="calendar-days" class="size-5" />
                        </span>
                        <div>
                            <h2 class="font-bold">Info Slot &amp; Pengerjaan</h2>
                            <p class="text-xs text-rose-100">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</p>
                        </div>
                    </div>

                    <div class="p-6 pt-2">
                        <dl class="divide-y divide-rose-100/70 text-sm">
                            <div class="flex items-center justify-between gap-3 py-3">
                                <dt class="flex items-center gap-2 text-zinc-500">
                                    <flux:icon name="ticket" class="size-4 text-amber-500" /> Slot hari ini
                                </dt>
                                <dd>
                                    @if ($sisaHariIni === null)
                                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-600">Belum dibuka</span>
                                    @elseif ($sisaHariIni === 0)
                                        <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">Penuh</span>
                                    @else
                                        <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">Tersedia &middot; {{ $sisaHariIni }} sisa</span>
                                    @endif
                                </dd>
                            </div>

                            <div class="flex items-center justify-between gap-3 py-3">
                                <dt class="flex items-center gap-2 text-zinc-500">
                                    <flux:icon name="calendar" class="size-4 text-amber-500" /> Slot terdekat
                                </dt>
                                <dd class="text-right font-medium">
                                    @if ($slotTerdekat)
                                        {{ $slotTerdekat->tanggal->locale('id')->translatedFormat('D, d M Y') }}
                                        <span class="block text-xs font-normal text-zinc-500">{{ $slotTerdekat->kuota - $slotTerdekat->terpakai }} slot tersisa</span>
                                    @else
                                        <span class="text-zinc-500">Belum slot dibuka</span>
                                    @endif
                                </dd>
                            </div>

                            <div class="flex items-center justify-between gap-3 py-3">
                                <dt class="flex items-center gap-2 text-zinc-500">
                                    <flux:icon name="building-storefront" class="size-4 text-amber-500" /> Jam layanan
                                </dt>
                                <dd class="text-right font-medium">Senin - Sabtu<span class="block text-xs font-normal text-zinc-500">09.00 - 17.30 WIB</span></dd>
                            </div>
                        </dl>

                        <p class="mt-2 rounded-xl bg-amber-50 px-4 py-3 text-xs leading-relaxed text-rose-800">
                            Slot per tanggal terbatas. Booking lebih awal agar tanggal pilihan Anda tidak penuh.
                        </p>

                        <a href="{{ route('booking') }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-rose-700 hover:underline">
                            Lihat tanggal &amp; booking
                            <flux:icon name="arrow-right" class="size-4" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-gradient-to-b from-orange-50 to-rose-100/70">
        <div class="mx-auto max-w-5xl px-4 py-14">
            <div class="mx-auto max-w-xl text-center">
                <p class="text-sm font-semibold uppercase tracking-wider text-amber-600">Cara kerja</p>
                <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Tiga langkah, selesai</h2>
            </div>

            <div class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach ($langkah as $l)
                    <div class="relative rounded-2xl border border-rose-100 border-t-4 border-t-amber-400 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-rose-200/60">
                        <span class="flex size-12 items-center justify-center rounded-xl bg-gradient-to-br from-rose-600 to-amber-500 text-white shadow-md shadow-rose-200">
                            <flux:icon :name="$l['ikon']" class="size-6" />
                        </span>
                        <h3 class="mt-4 font-bold">{{ $l['judul'] }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-zinc-600">{{ $l['isi'] }}</p>

                        @unless ($loop->last)
                            <span aria-hidden class="absolute -right-6 top-12 hidden w-6 border-t-2 border-dashed border-amber-400 md:block"></span>
                        @endunless
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    <section class="mx-auto max-w-5xl px-4 py-16">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-rose-700 via-rose-800 to-rose-950 px-6 py-12 text-center text-white shadow-xl shadow-rose-300/50">
            <div aria-hidden class="pointer-events-none absolute -top-16 -right-16 size-56 rounded-full bg-amber-400/30 blur-3xl"></div>
            <h2 class="relative text-xl font-bold tracking-tight sm:text-2xl">Siap menjahit?</h2>
            <p class="relative mx-auto mt-2 max-w-md text-sm text-rose-100">Pilih tanggal yang tersedia dan kirim data jahitan Anda sekarang.</p>
            <a href="{{ route('booking') }}"
               class="relative mt-6 inline-flex items-center gap-2 rounded-xl bg-amber-400 px-6 py-3 font-semibold text-rose-950 shadow-lg transition hover:-translate-y-0.5 hover:bg-amber-300">
                Booking Sekarang
                <flux:icon name="arrow-right" class="size-4" />
            </a>
        </div>
    </section>

</x-layouts::publik>