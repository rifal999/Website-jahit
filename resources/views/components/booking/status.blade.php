<?php

use App\Models\Booking;
use App\Models\SlotBooking;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.publik')] class extends Component {
    public string $kode = '';
    public ?Booking $booking = null;
    public bool $sudahCari = false;

    public bool $formTolak = false;
    public string $alasanPenolakan = '';
    public ?string $pesanSukses = null;

    public function getIsAdminProperty(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        // Sesuaikan pengecekan admin dengan struktur model User di proyekmu
        return (bool) ($user->is_admin ?? ($user->role === 'admin'));
    }

    public function cari(): void
    {
        $this->validate(['kode' => 'required|string|max:20']);

        $this->pesanSukses = null;
        $this->formTolak = false;
        $this->alasanPenolakan = '';

        $this->booking = Booking::with('slot')
            ->where('kode_booking', strtoupper(trim($this->kode)))
            ->first();

        $this->sudahCari = true;
    }

    public function bukaFormTolak(): void
    {
        if (! $this->isAdmin || ! $this->booking || $this->booking->status === 'Ditolak') {
            return;
        }

        $this->alasanPenolakan = (string) ($this->booking->alasan_penolakan ?? '');
        $this->formTolak = true;
        $this->resetErrorBag('alasanPenolakan');
    }

    public function batalTolak(): void
    {
        $this->formTolak = false;
        $this->alasanPenolakan = '';
        $this->resetErrorBag('alasanPenolakan');
    }

    public function tolak(): void
    {
        if (! $this->isAdmin || ! $this->booking) {
            abort(403);
        }

        $this->validate([
            'alasanPenolakan' => 'required|string|max:500',
        ], [
            'alasanPenolakan.required' => 'Alasan penolakan wajib diisi.',
            'alasanPenolakan.max' => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        DB::transaction(function () {
            $booking = Booking::lockForUpdate()->findOrFail($this->booking->getKey());

            if ($booking->status === 'Ditolak') {
                return;
            }

            $booking->update([
                'status' => 'Ditolak',
                'alasan_penolakan' => trim($this->alasanPenolakan),
            ]);

            if ($booking->id_slot) {
                $slot = SlotBooking::lockForUpdate()->find($booking->id_slot);

                if ($slot) {
                    $slot->update([
                        'terpakai' => max(0, (int) $slot->terpakai - 1),
                    ]);
                }
            }
        });

        $this->booking = Booking::with('slot')->find($this->booking->getKey());
        $this->formTolak = false;
        $this->alasanPenolakan = '';
        $this->pesanSukses = 'Pesanan berhasil ditolak dan kuota slot telah dikembalikan.';
    }
};
?>

@php
    $tahap = ['Baru Diterima', 'Sedang Dikerjakan', 'Selesai'];
    $ditolak = $booking && $booking->status === 'Ditolak';
    $posisi = ($booking && ! $ditolak) ? (int) array_search($booking->status, $tahap) : 0;
    $input = 'w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm uppercase shadow-sm focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-200';
@endphp

<div class="mx-auto w-full max-w-xl px-4 py-10">
    <div class="mb-6 text-center">
        <h1 class="text-3xl font-bold tracking-tight">Cek Status Jahitan</h1>
        <p class="mt-2 text-zinc-600">Masukkan kode booking untuk melihat perkembangan jahitanmu.</p>
    </div>

    <form wire:submit="cari" class="space-y-3 rounded-2xl border border-rose-100 bg-white p-6 shadow-sm">
        <label class="block text-sm font-medium">Kode booking</label>
        <input type="text" wire:model="kode" placeholder="Contoh: SJ-A1B2C3" class="{{ $input }}">
        @error('kode') <p class="text-sm text-red-600">Kode booking wajib diisi.</p> @enderror

        <button type="submit" wire:loading.attr="disabled" wire:target="cari"
                class="w-full rounded-lg bg-rose-600 px-4 py-3 font-semibold text-white shadow-sm hover:bg-rose-700 disabled:opacity-60">
            <span wire:loading.remove wire:target="cari">Cek status</span>
            <span wire:loading wire:target="cari">Mencari...</span>
        </button>
    </form>

    @if ($sudahCari)
        @if ($booking)
            <div class="mt-6 space-y-6 rounded-2xl border border-rose-100 bg-white p-6 shadow-sm">
                @if ($pesanSukses)
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-sm font-medium text-emerald-800">
                        {{ $pesanSukses }}
                    </div>
                @endif

                @if ($ditolak)
                    {{-- Booking ditolak: tampilkan alasan, tanpa indikator tahapan --}}
                    <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                        <p class="font-semibold text-red-700">Booking ditolak</p>
                        <p class="mt-1 text-sm text-red-700">
                            {{ $booking->alasan_penolakan ?: 'Mohon maaf, booking kamu tidak dapat kami proses.' }}
                        </p>
                        <p class="mt-2 text-xs text-red-600">Kamu bisa membuat booking baru pada tanggal lain.</p>
                    </div>
                @else
                    <ol class="flex items-start">
                        @foreach ($tahap as $i => $nama)
                            <li class="flex-1 text-center">
                                <div class="flex items-center">
                                    <div class="h-0.5 flex-1 {{ $i === 0 ? 'bg-transparent' : ($i <= $posisi ? 'bg-rose-500' : 'bg-zinc-200') }}"></div>
                                    <div class="flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $i <= $posisi ? 'bg-rose-600 text-white' : 'bg-zinc-200 text-zinc-500' }}">
                                        {{ $i + 1 }}
                                    </div>
                                    <div class="h-0.5 flex-1 {{ $i === count($tahap) - 1 ? 'bg-transparent' : ($i < $posisi ? 'bg-rose-500' : 'bg-zinc-200') }}"></div>
                                </div>
                                <p class="mt-2 text-xs font-medium {{ $i <= $posisi ? 'text-rose-700' : 'text-zinc-400' }}">{{ $nama }}</p>
                            </li>
                        @endforeach
                    </ol>
                @endif

                <dl class="divide-y divide-zinc-100 text-sm">
                    <div class="flex justify-between py-2.5"><dt class="text-zinc-500">Kode</dt><dd class="font-mono font-semibold">{{ $booking->kode_booking }}</dd></div>
                    <div class="flex justify-between py-2.5"><dt class="text-zinc-500">Nama</dt><dd class="font-medium">{{ $booking->nama_pelanggan }}</dd></div>
                    <div class="flex justify-between py-2.5"><dt class="text-zinc-500">Jenis jahitan</dt><dd class="font-medium">{{ $booking->jenis_jahitan }}</dd></div>
                    <div class="flex justify-between py-2.5"><dt class="text-zinc-500">Tanggal booking</dt><dd class="font-medium">{{ $booking->slot?->tanggal?->format('d/m/Y') ?? ($booking->tanggal_booking?->format('d/m/Y') ?? '-') }}</dd></div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-zinc-500">Status</dt>
                        <dd>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $ditolak ? 'bg-red-100 text-red-700' : 'bg-rose-100 text-rose-700' }}">{{ $booking->status }}</span>
                        </dd>
                    </div>
                    @unless ($ditolak)
                        <div class="flex justify-between py-2.5">
                            <dt class="text-zinc-500">Estimasi selesai</dt>
                            <dd class="font-medium">{{ $booking->estimasi_selesai ? $booking->estimasi_selesai->format('d/m/Y') : 'Belum ditentukan' }}</dd>
                        </div>
                    @endunless
                </dl>

                @if ($this->isAdmin && ! $ditolak)
                    <div class="border-t border-zinc-100 pt-4">
                        @if (! $formTolak)
                            <button
                                type="button"
                                wire:click="bukaFormTolak"
                                class="w-full rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-100"
                            >
                                Tolak pesanan
                            </button>
                        @else
                            <div class="space-y-3 rounded-xl border border-red-200 bg-red-50/60 p-4">
                                <label class="block text-sm font-medium text-red-900">
                                    Alasan penolakan <span class="text-red-600">*</span>
                                </label>
                                <textarea
                                    rows="3"
                                    wire:model="alasanPenolakan"
                                    placeholder="Contoh: Bahan kain tidak mencukupi atau antrean penuh..."
                                    class="w-full rounded-lg border border-red-200 bg-white px-3 py-2 text-sm text-zinc-800 shadow-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-200"
                                ></textarea>
                                @error('alasanPenolakan')
                                    <p class="text-xs text-red-600">{{ $message }}</p>
                                @enderror

                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        wire:click="batalTolak"
                                        class="rounded-lg border border-zinc-300 bg-white px-3.5 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="tolak"
                                        wire:confirm="Yakin ingin menolak pesanan ini?"
                                        wire:loading.attr="disabled"
                                        wire:target="tolak"
                                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 disabled:opacity-60"
                                    >
                                        <span wire:loading.remove wire:target="tolak">Konfirmasi tolak</span>
                                        <span wire:loading wire:target="tolak">Memproses...</span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @else
            <p class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-center text-red-700">
                Kode booking tidak ditemukan. Periksa kembali kodenya.
            </p>
        @endif
    @endif
</div>