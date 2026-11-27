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

// Halaman admin: daftar booking masuk + ubah status, estimasi selesai, dan penolakan
new #[Layout('layouts.app')] #[Title('Booking Masuk')] class extends Component {
    use WithPagination;

    // Pencarian (kode atau nama) dan filter status
    public string $cari = '';
    public string $filterStatus = '';

    // Data form ubah status. idEdit kosong = form tidak tampil
    public ?int $idEdit = null;
    public string $statusBaru = '';
    public string $estimasi = '';
    public string $alasanPenolakan = '';

    // Pesan berhasil setelah menyimpan
    public ?string $pesan = null;

    // Status yang tersedia
    public function daftarStatus(): array
    {
        return ['Baru Diterima', 'Sedang Dikerjakan', 'Selesai', 'Ditolak'];
    }

    // Kembali ke halaman 1 kalau pencarian atau filter berubah
    public function updatedCari(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedStatusBaru(): void
    {
        if ($this->statusBaru !== 'Ditolak') {
            $this->alasanPenolakan = '';
            $this->resetErrorBag('alasanPenolakan');
        }
    }

    // Daftar booking sesuai filter, terbaru di atas, 10 per halaman
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

    // Admin menekan "Ubah": isi form dengan data booking yang dipilih
    public function pilih(int $id): void
    {
        $booking = Booking::findOrFail($id);

        $this->idEdit = $id;
        $this->statusBaru = $booking->status;
        $this->estimasi = $booking->estimasi_selesai?->toDateString() ?? '';
        $this->alasanPenolakan = $booking->alasan_penolakan ?? '';
        $this->pesan = null;
        $this->resetErrorBag();
    }

    // Admin menekan "Batal": tutup form
    public function batal(): void
    {
        $this->idEdit = null;
        $this->statusBaru = '';
        $this->estimasi = '';
        $this->alasanPenolakan = '';
        $this->resetErrorBag();
    }

    // Admin menekan "Simpan perubahan"
    public function simpan(): void
    {
        $bookingAwal = Booking::findOrFail($this->idEdit);

        // Status wajib dipilih, estimasi tidak boleh sebelum tanggal booking, alasan wajib jika Ditolak
        $this->validate([
            'statusBaru' => ['required', Rule::in($this->daftarStatus())],
            'estimasi' => ['nullable', 'date', 'after_or_equal:'.$bookingAwal->tanggal_booking->toDateString()],
            'alasanPenolakan' => ['required_if:statusBaru,Ditolak', 'nullable', 'string', 'max:500'],
        ], [
            'statusBaru.required' => 'Status booking harus dipilih.',
            'statusBaru.in' => 'Status booking tidak valid.',
            'estimasi.date' => 'Format tanggal estimasi tidak valid.',
            'estimasi.after_or_equal' => 'Estimasi selesai tidak boleh sebelum tanggal booking.',
            'alasanPenolakan.required_if' => 'Alasan penolakan wajib diisi jika status Ditolak.',
            'alasanPenolakan.max' => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        DB::transaction(function () {
            $booking = Booking::lockForUpdate()->findOrFail($this->idEdit);
            $statusLama = (string) $booking->status;
            $statusBaru = $this->statusBaru;

            $alasan = $statusBaru === 'Ditolak'
                ? trim($this->alasanPenolakan)
                : null;

            // Simpan status, estimasi (kosong = belum ditentukan), alasan penolakan, dan admin yang menangani
            $booking->update([
                'status' => $statusBaru,
                'estimasi_selesai' => $this->estimasi !== '' ? $this->estimasi : null,
                'alasan_penolakan' => $alasan,
                'id_user' => auth()->id(),
            ]);

            // Kembalikan 1 kuota slot hanya saat transisi menjadi Ditolak
            if ($statusLama !== 'Ditolak' && $statusBaru === 'Ditolak' && $booking->id_slot) {
                $slot = SlotBooking::lockForUpdate()->find($booking->id_slot);

                if ($slot) {
                    $slot->update([
                        'terpakai' => max(0, (int) $slot->terpakai - 1),
                    ]);
                }
            }
        });

        $this->idEdit = null;
        $this->statusBaru = '';
        $this->estimasi = '';
        $this->alasanPenolakan = '';
        $this->pesan = 'Status booking berhasil diperbarui.';
    }
};
?>

{{-- Tampilan halaman --}}
<div class="sj-page">
    <x-admin.topbar
        judul="Booking Masuk"
        deskripsi="Pantau pesanan jahit pelanggan, ubah status, dan tentukan estimasi selesai."
    />

    {{-- Pesan berhasil --}}
    @if ($pesan)
        <div
            x-data="{ tampil: true }"
            x-init="setTimeout(() => tampil = false, 5000)"
            x-show="tampil"
            x-transition.opacity
            class="sj-alert sj-alert-ok"
        >
            <flux:icon name="check-circle" class="size-5 shrink-0" />
            {{ $pesan }}
        </div>
    @endif

    {{-- Form ubah status: tampil hanya setelah admin menekan "Ubah" --}}
    @if ($idEdit)
        @php $sedangDiubah = \App\Models\Booking::find($idEdit); @endphp

        <form wire:submit="simpan" wire:key="form-ubah-{{ $idEdit }}" class="sj-card sj-card-editing sj-form sj-rise">
            <div class="sj-form-head">
                <span class="sj-bubble"><flux:icon name="pencil-square" class="size-5" /></span>
                <div>
                    <h2>Ubah status booking</h2>
                    @if ($sedangDiubah)
                        <p>{{ $sedangDiubah->kode_booking }} &middot; {{ $sedangDiubah->nama_pelanggan }}</p>
                    @endif
                </div>
                <span class="sj-pill">Mode ubah</span>
            </div>

            <div class="sj-field">
                <label class="sj-label">Status</label>
                <select wire:model.live="statusBaru" class="sj-input">
                    <option value="">-- Pilih status --</option>
                    @foreach ($this->daftarStatus() as $s)
                        <option value="{{ $s }}">{{ $s }}</option>
                    @endforeach
                </select>
                @error('statusBaru') <p class="sj-error">{{ $message }}</p> @enderror
            </div>

            @if ($statusBaru === 'Ditolak')
                <div class="sj-field sj-rise">
                    <label class="sj-label">Alasan penolakan</label>
                    <textarea
                        rows="3"
                        wire:model="alasanPenolakan"
                        placeholder="Tuliskan alasan pesanan ditolak..."
                        class="sj-input"
                    ></textarea>
                    <p class="sj-hint">
                        Menolak pesanan akan otomatis mengembalikan 1 kuota pada slot booking terkait.
                    </p>
                    @error('alasanPenolakan') <p class="sj-error">{{ $message }}</p> @enderror
                </div>
            @endif

            <div class="sj-field">
                <label class="sj-label">Estimasi selesai <small>(boleh dikosongkan)</small></label>
                <input type="date" wire:model="estimasi" class="sj-input">
                @error('estimasi') <p class="sj-error">{{ $message }}</p> @enderror
            </div>

            <div class="sj-actions">
                <button type="submit" wire:loading.attr="disabled" wire:target="simpan" class="sj-btn sj-btn-primary">
                    <flux:icon name="check" class="size-4" />
                    Simpan perubahan
                </button>
                <button type="button" wire:click="batal" class="sj-btn sj-btn-ghost">
                    Batal
                </button>
            </div>
        </form>
    @endif

    {{-- Pencarian dan filter status --}}
    <div class="sj-toolbar">
        <label class="sj-search">
            <flux:icon name="magnifying-glass" />
            <input type="text" wire:model.live.debounce.300ms="cari" placeholder="Cari kode atau nama pelanggan" class="sj-input">
        </label>
        <select wire:model.live="filterStatus" class="sj-input sj-select">
            <option value="">Semua status</option>
            @foreach ($this->daftarStatus() as $s)
                <option value="{{ $s }}">{{ $s }}</option>
            @endforeach
        </select>
    </div>

    {{-- Tabel booking --}}
    <div class="sj-card sj-table-wrap">
        <table class="sj-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>No. HP</th>
                    <th>Jenis jahitan</th>
                    <th>Tanggal booking</th>
                    <th>Status</th>
                    <th>Estimasi selesai</th>
                    <th class="sj-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->bookings as $b)
                    @php
                        // Warna badge mengikuti status booking
                        $tone = match ($b->status) {
                            'Baru Diterima' => 'warn',
                            'Sedang Dikerjakan' => 'info',
                            'Selesai' => 'ok',
                            'Ditolak' => 'bad',
                            default => 'mute',
                        };
                    @endphp

                    <tr wire:key="booking-{{ $b->id_booking }}" @class(['is-editing' => $idEdit == $b->id_booking])>
                        <td class="sj-nowrap"><span class="sj-code">{{ $b->kode_booking }}</span></td>
                        <td class="font-medium">{{ $b->nama_pelanggan }}</td>
                        <td class="sj-nowrap">{{ $b->no_hp }}</td>
                        <td>{{ $b->jenis_jahitan }}</td>
                        <td class="sj-nowrap">{{ $b->tanggal_booking->format('d/m/Y') }}</td>
                        <td>
                            <span class="sj-badge sj-badge-{{ $tone }}">{{ $b->status }}</span>
                            @if ($b->status === 'Ditolak' && $b->alasan_penolakan)
                                <div class="sj-reason">Alasan: {{ $b->alasan_penolakan }}</div>
                            @endif
                        </td>
                        <td class="sj-nowrap {{ $b->estimasi_selesai ? '' : 'sj-muted' }}">
                            {{ $b->estimasi_selesai ? $b->estimasi_selesai->format('d/m/Y') : 'Belum ditentukan' }}
                        </td>
                        <td class="sj-right">
                            <button
                                type="button"
                                wire:click="pilih({{ $b->id_booking }})"
                                x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                                class="sj-btn sj-btn-soft sj-btn-sm"
                            >
                                <flux:icon name="pencil-square" class="size-4" />
                                Ubah
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="sj-empty">
                            <flux:icon name="inbox" />s
                            Belum ada booking.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->bookings->links() }}
</div>