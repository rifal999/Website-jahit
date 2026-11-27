<?php

use App\Models\Booking;
use App\Models\SlotBooking;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Booking Masuk')] class extends Component {
    use WithPagination;

    public string $cari = '';
    public string $filterStatus = '';

    // idEdit / idTolak kosong = modal tertutup
    public ?int $idEdit = null;
    public string $statusBaru = '';
    public string $estimasi = '';

    public ?int $idTolak = null;
    public string $alasan = '';

    public ?string $pesan = null;
    public ?string $galat = null;

    // Status yang boleh dipilih lewat form ubah status (Ditolak lewat tombol Tolak)
    public function daftarStatus(): array
    {
        return ['Baru Diterima', 'Sedang Dikerjakan', 'Selesai'];
    }

    public function updatedCari(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function bookings()
    {
        return Booking::with('slot')
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->cari !== '', function ($q) {
                $q->where(function ($q) {
                    $q->where('kode_booking', 'like', '%'.$this->cari.'%')
                      ->orWhere('nama_pelanggan', 'like', '%'.$this->cari.'%');
                });
            })
            ->orderByDesc('id_booking')
            ->paginate(10);
    }

    #[Computed]
    public function bookingEdit()
    {
        return $this->idEdit ? Booking::find($this->idEdit) : null;
    }

    #[Computed]
    public function bookingTolak()
    {
        return $this->idTolak ? Booking::find($this->idTolak) : null;
    }

    public function pilih(int $id): void
    {
        $booking = Booking::findOrFail($id);

        $this->idEdit = $id;
        $this->statusBaru = $booking->status;
        $this->estimasi = $booking->estimasi_selesai?->toDateString() ?? '';
        $this->pesan = $this->galat = null;
        $this->resetErrorBag();
    }

    public function batal(): void
    {
        $this->idEdit = null;
        $this->resetErrorBag();
    }

    public function simpan(): void
    {
        $booking = Booking::findOrFail($this->idEdit);

        $this->validate([
            'statusBaru' => ['required', Rule::in($this->daftarStatus())],
            'estimasi' => ['nullable', 'date', 'after_or_equal:'.$booking->tanggal_booking->toDateString()],
        ], [
            'statusBaru.required' => 'Status booking harus dipilih.',
            'statusBaru.in' => 'Status booking tidak valid.',
            'estimasi.date' => 'Format tanggal estimasi tidak valid.',
            'estimasi.after_or_equal' => 'Estimasi selesai tidak boleh sebelum tanggal booking.',
        ]);

        $booking->update([
            'status' => $this->statusBaru,
            'estimasi_selesai' => $this->estimasi !== '' ? $this->estimasi : null,
            'id_user' => auth()->id(),
        ]);

        $this->idEdit = null;
        $this->pesan = 'Status booking berhasil diperbarui.';
    }

    public function pilihTolak(int $id): void
    {
        Booking::findOrFail($id);

        $this->idTolak = $id;
        $this->alasan = '';
        $this->pesan = $this->galat = null;
        $this->resetErrorBag();
    }

    public function batalTolak(): void
    {
        $this->idTolak = null;
        $this->resetErrorBag();
    }

    public function simpanTolak(): void
    {
        $this->validate([
            'alasan' => 'required|string|min:5|max:255',
        ], [
            'alasan.required' => 'Alasan penolakan harus diisi.',
            'alasan.min' => 'Alasan penolakan minimal 5 karakter.',
            'alasan.max' => 'Alasan penolakan maksimal 255 karakter.',
        ]);

        $berhasil = DB::transaction(function () {
            $booking = Booking::lockForUpdate()->findOrFail($this->idTolak);

            // Hanya booking yang masih berjalan yang boleh ditolak
            if (! in_array($booking->status, ['Baru Diterima', 'Sedang Dikerjakan'])) {
                return false;
            }

            $booking->update([
                'status' => 'Ditolak',
                'alasan_penolakan' => $this->alasan,
                'estimasi_selesai' => null,
                'id_user' => auth()->id(),
            ]);

            // Slot dikembalikan agar bisa dipakai pelanggan lain
            SlotBooking::where('id_slot', $booking->id_slot)
                ->where('terpakai', '>', 0)
                ->decrement('terpakai');

            return true;
        });

        $this->idTolak = null;

        if ($berhasil) {
            $this->pesan = 'Booking berhasil ditolak dan slotnya dikembalikan.';
        } else {
            $this->galat = 'Booking ini tidak bisa ditolak karena sudah selesai atau sudah ditolak.';
        }
    }

    public function hapus(int $id): void
    {
        $this->pesan = $this->galat = null;

        $booking = Booking::findOrFail($id);

        // Hanya data yang tanggalnya sudah lewat yang boleh dihapus; yang belum lewat ditolak saja
        if ($booking->tanggal_booking->gte(today())) {
            $this->galat = 'Hanya booking yang tanggalnya sudah lewat yang bisa dihapus. Gunakan Tolak untuk membatalkan booking yang belum lewat.';
            return;
        }

        DB::transaction(function () use ($booking) {
            // Booking yang sudah ditolak slotnya sudah dikembalikan sebelumnya
            if ($booking->status !== 'Ditolak') {
                SlotBooking::where('id_slot', $booking->id_slot)
                    ->where('terpakai', '>', 0)
                    ->decrement('terpakai');
            }
            $booking->delete();
        });

        $this->pesan = 'Data booking berhasil dihapus.';
    }
};
?>

@php
    $warnaStatus = [
        'Baru Diterima' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
        'Sedang Dikerjakan' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        'Selesai' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
        'Ditolak' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    ];
    $kotak = 'rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800';
@endphp

<div class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6">
    <div>
        <h1 class="text-2xl font-bold">Booking Masuk</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Kelola booking pelanggan: ubah status, tentukan estimasi, tolak, atau hapus data yang sudah lewat.</p>
    </div>

    @if ($pesan)
        <div x-data="{ tampil: true }" x-init="setTimeout(() => tampil = false, 4000)" x-show="tampil" x-transition
             class="flex items-center gap-2 rounded-lg border border-green-300 bg-green-50 p-3 text-sm font-medium text-green-800">
            <flux:icon name="check-circle" class="size-5" /> {{ $pesan }}
        </div>
    @endif

    @if ($galat)
        <div class="rounded-lg border border-red-300 bg-red-50 p-3 text-sm font-medium text-red-700">{{ $galat }}</div>
    @endif

    {{-- Pencarian dan filter --}}
    <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 md:flex-row md:items-center">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="cari" icon="magnifying-glass" placeholder="Cari kode atau nama pelanggan" />
        </div>
        <select wire:model.live="filterStatus" class="{{ $kotak }}">
            <option value="">Semua status</option>
            @foreach ([...$this->daftarStatus(), 'Ditolak'] as $s)
                <option value="{{ $s }}">{{ $s }}</option>
            @endforeach
        </select>
    </div>

    {{-- Tabel booking --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Pelanggan</th>
                        <th class="px-4 py-3">Jenis jahitan</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Estimasi</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->bookings as $b)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50" wire:key="booking-{{ $b->id_booking }}">
                            <td class="px-4 py-3 font-mono font-medium">{{ $b->kode_booking }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ $b->nama_pelanggan }}</p>
                                <p class="text-xs text-zinc-500">{{ $b->no_hp }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $b->jenis_jahitan }}</td>
                            <td class="px-4 py-3">{{ $b->tanggal_booking->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $warnaStatus[$b->status] ?? '' }}">{{ $b->status }}</span>
                                @if ($b->status === 'Ditolak' && $b->alasan_penolakan)
                                    <p class="mt-1 max-w-48 text-xs text-zinc-500">{{ $b->alasan_penolakan }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($b->status === 'Ditolak')
                                    -
                                @else
                                    {{ $b->estimasi_selesai ? $b->estimasi_selesai->format('d/m/Y') : 'Belum ditentukan' }}
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @if ($b->status !== 'Ditolak')
                                        <flux:button size="sm" icon="pencil-square" wire:click="pilih({{ $b->id_booking }})">Ubah</flux:button>
                                    @endif
                                    @if (in_array($b->status, ['Baru Diterima', 'Sedang Dikerjakan']))
                                        <flux:button size="sm" variant="danger" icon="x-circle" wire:click="pilihTolak({{ $b->id_booking }})">Tolak</flux:button>
                                    @endif
                                    @if ($b->tanggal_booking->lt(today()))
                                        <flux:button size="sm" variant="ghost" icon="trash"
                                                     wire:click="hapus({{ $b->id_booking }})"
                                                     wire:confirm="Hapus data booking ini? Data yang dihapus tidak bisa dikembalikan.">Hapus</flux:button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-zinc-500">Tidak ada booking yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $this->bookings->links() }}

    {{-- Modal ubah status --}}
    @if ($idEdit && $this->bookingEdit)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:key="modal-ubah">
            <form wire:submit="simpan" class="w-full max-w-md space-y-4 rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-900">
                <div>
                    <h2 class="text-lg font-bold">Ubah status booking</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $this->bookingEdit->kode_booking }} &middot; {{ $this->bookingEdit->nama_pelanggan }}
                        &middot; booking {{ $this->bookingEdit->tanggal_booking->format('d/m/Y') }}
                    </p>
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-medium">Status</label>
                    <select wire:model="statusBaru" class="{{ $kotak }} w-full">
                        <option value="">-- Pilih status --</option>
                        @foreach ($this->daftarStatus() as $s)
                            <option value="{{ $s }}">{{ $s }}</option>
                        @endforeach
                    </select>
                    @error('statusBaru') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-medium">Estimasi selesai <span class="font-normal text-zinc-500">(boleh dikosongkan)</span></label>
                    <input type="date" wire:model="estimasi" min="{{ $this->bookingEdit->tanggal_booking->toDateString() }}" class="{{ $kotak }} w-full">
                    @error('estimasi') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:button type="button" wire:click="batal">Batal</flux:button>
                    <flux:button type="submit" variant="primary">Simpan perubahan</flux:button>
                </div>
            </form>
        </div>
    @endif

    {{-- Modal tolak booking --}}
    @if ($idTolak && $this->bookingTolak)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:key="modal-tolak">
            <form wire:submit="simpanTolak" class="w-full max-w-md space-y-4 rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-900">
                <div>
                    <h2 class="text-lg font-bold">Tolak booking</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $this->bookingTolak->kode_booking }} &middot; {{ $this->bookingTolak->nama_pelanggan }}
                        &middot; {{ $this->bookingTolak->jenis_jahitan }}
                    </p>
                </div>

                <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                    Slot pada tanggal {{ $this->bookingTolak->tanggal_booking->format('d/m/Y') }} akan dikembalikan, dan alasan ini akan terlihat oleh pelanggan.
                </p>

                <div class="space-y-1">
                    <label class="text-sm font-medium">Alasan penolakan</label>
                    <textarea wire:model="alasan" rows="3" placeholder="Contoh: bahan tidak tersedia atau jadwal sudah penuh"
                              class="{{ $kotak }} w-full"></textarea>
                    @error('alasan') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:button type="button" wire:click="batalTolak">Batal</flux:button>
                    <flux:button type="submit" variant="danger">Tolak booking</flux:button>
                </div>
            </form>
        </div>
    @endif
</div>