<?php

use App\Models\Booking;
use App\Models\SlotBooking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.publik')] class extends Component {
    public string $tanggal = '';
    public string $nama_pelanggan = '';
    public string $no_hp = '';
    public string $jenis_jahitan = '';
    public ?int $sisa = null;
    public ?string $kode = null;

    protected function rules(): array
    {
        return [
            'tanggal' => 'required|date|after_or_equal:today',
            'nama_pelanggan' => 'required|string|max:100',
            'no_hp' => 'required|regex:/^[0-9]{9,15}$/',
            'jenis_jahitan' => 'required|string|max:100',
        ];
    }

    public function updatedTanggal(): void
    {
        $this->kode = null;
        $this->sisa = null;
        $this->validateOnly('tanggal');

        // Slot hanya ada kalau admin sudah membuka tanggal ini
        $slot = SlotBooking::where('tanggal', $this->tanggal)->first();

        if (! $slot) {
            $this->addError('tanggal', 'Tanggal ini belum dibuka untuk booking. Silahkan kembali lagi nanti jika sudah dibuka.');
            return;
        }

        $this->sisa = max(0, $slot->kuota - $slot->terpakai);
    }

    public function simpan(): void
    {
        $this->validate();

        // Transaksi + lockForUpdate: dua pelanggan tidak bisa mengambil slot terakhir bersamaan
        $kode = DB::transaction(function () {
            $slot = SlotBooking::where('tanggal', $this->tanggal)
                ->lockForUpdate()
                ->first();

            // Tanggal belum dibuka atau slot sudah penuh
            if (! $slot || $slot->terpakai >= $slot->kuota) {
                return null;
            }

            $booking = new Booking();
            $booking->kode_booking = $this->buatKode();
            $booking->nama_pelanggan = $this->nama_pelanggan;
            $booking->no_hp = $this->no_hp;
            $booking->jenis_jahitan = $this->jenis_jahitan;
            $booking->tanggal_booking = $this->tanggal;
            $booking->id_slot = $slot->getKey();
            $booking->save();

            $slot->increment('terpakai');

            return $booking->kode_booking;
        });

        if ($kode === null) {
            $this->sisa = 0;
            $this->addError('tanggal', 'Slot pada tanggal ini sudah penuh atau belum dibuka.');
            return;
        }

        $this->kode = $kode;
        $this->reset(['nama_pelanggan', 'no_hp', 'jenis_jahitan']);
    }

    public function ulang(): void
    {
        $this->reset(['tanggal', 'sisa', 'kode']);
    }

    protected function buatKode(): string
    {
        do {
            $kode = 'SJ-' . strtoupper(Str::random(6));
        } while (Booking::where('kode_booking', $kode)->exists());

        return $kode;
    }
};
?>

@php
    $input = 'w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-200';
@endphp

<div class="mx-auto w-full max-w-xl px-4 py-10">
    <div class="mb-6 text-center">
        <h1 class="text-3xl font-bold tracking-tight">Booking Jahitan</h1>
        <p class="mt-2 text-zinc-600">Pilih tanggal, isi data, lalu simpan kode booking untuk memantau jahitanmu.</p>
    </div>

    @if ($kode)
        {{-- Booking berhasil --}}
        <div class="rounded-2xl border border-green-200 bg-white p-8 text-center shadow-sm"
             x-data="{ copied: false }">
            <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-green-100 text-green-600">
                <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>
            <h2 class="mt-4 text-xl font-bold">Booking berhasil!</h2>
            <p class="mt-1 text-sm text-zinc-600">Simpan kode di bawah ini untuk mengecek status jahitanmu.</p>

            <p class="my-5 rounded-xl bg-rose-50 py-4 font-mono text-3xl font-bold tracking-widest text-rose-700">{{ $kode }}</p>

            <div class="flex flex-wrap justify-center gap-2">
                <button type="button"
                        @click="navigator.clipboard.writeText('{{ $kode }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium hover:bg-zinc-50">
                    <span x-show="!copied">Salin kode</span>
                    <span x-show="copied" x-cloak>Tersalin!</span>
                </button>
                <a href="{{ route('booking.status') }}" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                    Cek status
                </a>
                <button type="button" wire:click="ulang" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium hover:bg-zinc-50">
                    Buat booking lagi
                </button>
            </div>
        </div>
    @else
        <div class="space-y-6 rounded-2xl border border-rose-100 bg-white p-6 shadow-sm sm:p-8">
            {{-- Langkah 1: tanggal dan slot --}}
            <div>
                <div class="mb-3 flex items-center gap-2">
                    <span class="flex size-6 items-center justify-center rounded-full bg-rose-600 text-xs font-bold text-white">1</span>
                    <h2 class="font-semibold">Pilih tanggal</h2>
                </div>

                <input type="date" wire:model.live="tanggal" min="{{ now()->toDateString() }}" class="{{ $input }}">
                @error('tanggal') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                <div wire:loading wire:target="tanggal" class="mt-2 text-sm text-zinc-500">Memeriksa slot...</div>

                @if ($sisa !== null)
                    <div wire:loading.remove wire:target="tanggal" class="mt-3">
                        @if ($sisa > 0)
                            <span class="inline-flex items-center gap-2 rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-700">
                                <span class="size-2 rounded-full bg-green-500"></span> Slot tersedia: {{ $sisa }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-2 rounded-full bg-red-100 px-3 py-1 text-sm font-semibold text-red-700">
                                <span class="size-2 rounded-full bg-red-500"></span> Slot penuh, pilih tanggal lain
                            </span>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Langkah 2: data pelanggan, tampil kalau slot masih ada --}}
            @if ($sisa !== null && $sisa > 0)
                <form wire:submit="simpan" class="space-y-4 border-t border-zinc-100 pt-6">
                    <div class="flex items-center gap-2">
                        <span class="flex size-6 items-center justify-center rounded-full bg-rose-600 text-xs font-bold text-white">2</span>
                        <h2 class="font-semibold">Isi data</h2>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Nama</label>
                        <input type="text" wire:model="nama_pelanggan" placeholder="Nama lengkap" class="{{ $input }}">
                        @error('nama_pelanggan') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">No. HP</label>
                        <input type="text" wire:model="no_hp" inputmode="numeric" placeholder="08xxxxxxxxxx" class="{{ $input }}">
                        @error('no_hp') <p class="mt-1 text-sm text-red-600">Nomor HP harus berupa angka (9-15 digit).</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Jenis jahitan</label>
                        <input type="text" wire:model="jenis_jahitan" placeholder="Contoh: Kebaya, kemeja, seragam" class="{{ $input }}">
                        @error('jenis_jahitan') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="simpan"
                            class="w-full rounded-lg bg-rose-600 px-4 py-3 font-semibold text-white shadow-sm hover:bg-rose-700 disabled:opacity-60">
                        <span wire:loading.remove wire:target="simpan">Kirim booking</span>
                        <span wire:loading wire:target="simpan">Memproses...</span>
                    </button>
                </form>
            @endif
        </div>
    @endif
</div>