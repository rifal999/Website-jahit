@php
    $total = \App\Models\Booking::count();
    $baru = \App\Models\Booking::where('status', 'Baru Diterima')->count();
    $dikerjakan = \App\Models\Booking::where('status', 'Sedang Dikerjakan')->count();
    $selesai = \App\Models\Booking::where('status', 'Selesai')->count();

    // Lima booking terbaru untuk tabel di bawah
    $terbaru = \App\Models\Booking::orderByDesc('id_booking')->take(5)->get();

    $warnaStatus = [
        'Baru Diterima' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
        'Sedang Dikerjakan' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        'Selesai' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
    ];

    $kartu = [
        ['label' => 'Total booking', 'nilai' => $total, 'ikon' => 'clipboard-document-list', 'kelas' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200'],
        ['label' => 'Baru Diterima', 'nilai' => $baru, 'ikon' => 'inbox', 'kelas' => $warnaStatus['Baru Diterima']],
        ['label' => 'Sedang Dikerjakan', 'nilai' => $dikerjakan, 'ikon' => 'clock', 'kelas' => $warnaStatus['Sedang Dikerjakan']],
        ['label' => 'Selesai', 'nilai' => $selesai, 'ikon' => 'check-circle', 'kelas' => $warnaStatus['Selesai']],
    ];
@endphp

<x-layouts::app :title="__('Dashboard')">
    <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6">
        {{-- Sapaan --}}
        <div>
            <h1 class="text-2xl font-bold">Halo, {{ auth()->user()->name }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Ringkasan booking jahitan hari ini.</p>
        </div>

        {{-- Kartu ringkasan --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($kartu as $k)
                <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-lg {{ $k['kelas'] }}">
                        <flux:icon :name="$k['ikon']" class="size-6" />
                    </div>
                    <div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $k['label'] }}</p>
                        <p class="text-3xl font-bold">{{ $k['nilai'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pintasan --}}
        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('admin.booking')" variant="primary" icon="clipboard-document-list" wire:navigate>
                Kelola booking masuk
            </flux:button>
        </div>

        {{-- Booking terbaru --}}
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-4 py-3 font-semibold dark:border-zinc-700">Booking terbaru</div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Jenis jahitan</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($terbaru as $b)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-3 font-mono font-medium">{{ $b->kode_booking }}</td>
                                <td class="px-4 py-3">{{ $b->nama_pelanggan }}</td>
                                <td class="px-4 py-3">{{ $b->jenis_jahitan }}</td>
                                <td class="px-4 py-3">{{ $b->tanggal_booking->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $warnaStatus[$b->status] ?? '' }}">
                                        {{ $b->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-zinc-500">Belum ada booking.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts::app>