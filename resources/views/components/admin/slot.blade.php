<?php

use App\Models\SlotBooking;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Kelola Slot')] class extends Component {
    use WithPagination;

    public string $tanggal = '';
    public string $tanggalAkhir = '';
    public string $kuota = '5';

    public ?int $idEdit = null;

    public ?string $pesan = null;
    public ?string $galat = null;

    public function pilih(int $id): void
    {
        $slot = SlotBooking::findOrFail($id);

        $this->idEdit = $id;
        $this->tanggal = $slot->tanggal->toDateString();
        $this->tanggalAkhir = '';
        $this->kuota = (string) $slot->kuota;
        $this->pesan = null;
        $this->galat = null;

        $this->resetErrorBag();
    }

    public function batal(): void
    {
        $this->reset(['tanggal', 'tanggalAkhir', 'idEdit']);

        $this->kuota = '5';

        $this->resetErrorBag();
    }

    public function simpan(): void
    {
        $this->pesan = null;
        $this->galat = null;

        $this->validate([
            'tanggal' => $this->idEdit
                ? 'required|date'
                : 'required|date|after_or_equal:today',

            'tanggalAkhir' => 'nullable|date|after_or_equal:tanggal',

            'kuota' => 'required|integer|min:1|max:50',
        ], [
            'tanggal.required' => 'Tanggal harus diisi.',
            'tanggal.after_or_equal' => 'Tanggal tidak boleh sebelum hari ini.',
            'tanggalAkhir.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
            'kuota.required' => 'Kuota harus diisi.',
            'kuota.integer' => 'Kuota harus berupa angka.',
            'kuota.min' => 'Kuota minimal 1.',
            'kuota.max' => 'Kuota maksimal 50.',
        ]);

        $kuota = (int) $this->kuota;

        $mulai = Carbon::parse($this->tanggal);

        $akhir = ($this->idEdit || $this->tanggalAkhir === '')
            ? $mulai
            : Carbon::parse($this->tanggalAkhir);

        if (abs($mulai->diffInDays($akhir)) > 30) {
            $this->addError(
                'tanggalAkhir',
                'Rentang tanggal maksimal 31 hari.'
            );

            return;
        }

        // Memeriksa kuota untuk satu tanggal
        if ($mulai->equalTo($akhir)) {
            $ada = SlotBooking::where(
                'tanggal',
                $mulai->toDateString()
            )->first();

            if ($ada && $kuota < $ada->terpakai) {
                $this->addError(
                    'kuota',
                    "Kuota tidak boleh kurang dari booking yang sudah masuk ({$ada->terpakai})."
                );

                return;
            }
        }

        $baru = 0;
        $diubah = 0;
        $dilewati = 0;

        // Membuka atau memperbarui slot berdasarkan tanggal
        foreach (CarbonPeriod::create($mulai, $akhir) as $hari) {

            $slot = SlotBooking::where(
                'tanggal',
                $hari->toDateString()
            )->first();

            if ($slot) {

                if ($kuota < $slot->terpakai) {
                    $dilewati++;
                    continue;
                }

                $slot->update([
                    'kuota' => $kuota,
                ]);

                $diubah++;

            } else {

                SlotBooking::create([
                    'tanggal' => $hari->toDateString(),
                    'kuota' => $kuota,
                    'terpakai' => 0,
                ]);

                $baru++;
            }
        }

        $this->pesan = "Slot disimpan: {$baru} dibuka, {$diubah} diperbarui"
            . ($dilewati
                ? ", {$dilewati} dilewati (kuota lebih kecil dari booking masuk)"
                : '')
            . '.';

        $this->batal();

        // Kembali ke halaman pertama setelah penyimpanan
        $this->resetPage();
    }

    public function hapus(int $id): void
    {
        $this->pesan = null;
        $this->galat = null;

        $slot = SlotBooking::findOrFail($id);

        // Slot yang belum lewat tidak boleh dihapus selama masih punya booking aktif (belum ditolak)
        $masihAktif = $slot->bookings()->where('status', '!=', 'Ditolak')->exists();

        if ($slot->tanggal->gte(today()) && $masihAktif) {
            $this->galat = 'Slot yang belum lewat dan masih memiliki booking aktif tidak bisa dihapus. Tolak booking tersebut terlebih dahulu.';
            return;
        }

        // Booking pada slot ikut dihapus: data tanggal lampau atau booking yang sudah ditolak
        DB::transaction(function () use ($slot) {
            $slot->bookings()->delete();
            $slot->delete();
        });

        $this->pesan = 'Slot berhasil dihapus.';
    }
};
?>

@php
    // Mengambil data slot menggunakan pagination Laravel
    $slots = \App\Models\SlotBooking::orderByDesc('tanggal')->paginate(10);
@endphp

<div class="sj-page">
    <x-admin.topbar
        judul="Kelola Slot Booking"
        deskripsi="Buka tanggal dan tentukan jumlah slot yang tersedia. Tanggal yang belum dibuka tidak bisa dibooking pelanggan."
    />

    {{-- Pesan berhasil --}}
    @if ($pesan)
        <div
            x-data="{ tampil: true }"
            x-init="setTimeout(() => tampil = false, 5000)"
            x-show="tampil"
            x-transition
            class="sj-alert sj-alert-ok"
        >
            <flux:icon name="check-circle" class="size-5 shrink-0" />
            {{ $pesan }}
        </div>
    @endif

    {{-- Pesan kesalahan --}}
    @if ($galat)
        <div class="sj-alert sj-alert-bad">
            <flux:icon name="exclamation-triangle" class="size-5 shrink-0" />
            {{ $galat }}
        </div>
    @endif

    {{-- Form buka slot / ubah kuota. wire:key berbeda per mode supaya transisi antar-state terlihat --}}
    <form
        wire:submit="simpan"
        wire:key="form-slot-{{ $idEdit ?? 'baru' }}"
        class="sj-card sj-form sj-rise {{ $idEdit ? 'sj-card-editing' : '' }}"
    >
        <div class="sj-form-head">
            <span class="sj-bubble">
                <flux:icon :name="$idEdit ? 'pencil-square' : 'plus'" class="size-5" />
            </span>
            <div>
                <h2>{{ $idEdit ? 'Ubah kuota slot' : 'Buka slot baru' }}</h2>
                <p>
                    {{ $idEdit
                        ? 'Tanggal tidak bisa diganti, hanya kuotanya.'
                        : 'Satu tanggal atau rentang tanggal sekaligus.' }}
                </p>
            </div>
            @if ($idEdit)
                <span class="sj-pill">Mode ubah</span>
            @endif
        </div>

        <div class="grid gap-4 {{ $idEdit ? 'sm:grid-cols-2' : 'sm:grid-cols-3' }}">

            {{-- Tanggal mulai --}}
            <div class="sj-field">
                <label class="sj-label">
                    {{ $idEdit ? 'Tanggal' : 'Tanggal mulai' }}
                </label>

                <input
                    type="date"
                    wire:model="tanggal"
                    @disabled($idEdit)
                    min="{{ $idEdit ? '' : now()->toDateString() }}"
                    class="sj-input"
                >

                @error('tanggal')
                    <p class="sj-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tanggal akhir --}}
            @unless ($idEdit)
                <div class="sj-field">
                    <label class="sj-label">
                        Tanggal akhir
                        <small>(opsional)</small>
                    </label>

                    <input
                        type="date"
                        wire:model="tanggalAkhir"
                        min="{{ now()->toDateString() }}"
                        class="sj-input"
                    >

                    @error('tanggalAkhir')
                        <p class="sj-error">{{ $message }}</p>
                    @enderror
                </div>
            @endunless

            {{-- Kuota --}}
            <div class="sj-field">
                <label class="sj-label">
                    Kuota per tanggal
                </label>

                <input
                    type="number"
                    wire:model="kuota"
                    min="1"
                    max="50"
                    class="sj-input"
                >

                @error('kuota')
                    <p class="sj-error">{{ $message }}</p>
                @enderror
            </div>

        </div>

        <div class="sj-actions">
            <button type="submit" wire:loading.attr="disabled" wire:target="simpan" class="sj-btn sj-btn-primary">
                <flux:icon name="check" class="size-4" />
                {{ $idEdit ? 'Simpan perubahan' : 'Simpan slot' }}
            </button>

            @if ($idEdit)
                <button type="button" wire:click="batal" class="sj-btn sj-btn-ghost">
                    Batal
                </button>
            @endif
        </div>

        @unless ($idEdit)
            <p class="sj-hint">
                Isi tanggal akhir untuk membuka beberapa tanggal sekaligus
                (maksimal 31 hari).
            </p>
        @endunless
    </form>

    {{-- Daftar slot --}}
    <div class="sj-card sj-table-wrap">
        <table class="sj-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kuota</th>
                    <th>Terpakai</th>
                    <th>Sisa</th>
                    <th>Status</th>
                    <th class="sj-right">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($slots as $s)
                    @php
                        $sisa = max(0, $s->kuota - $s->terpakai);
                        $persen = $s->kuota > 0 ? min(100, round($s->terpakai / $s->kuota * 100)) : 0;

                        if ($s->tanggal->lt(today())) {
                            [$label, $tone] = ['Lewat', 'mute'];
                        } elseif ($sisa === 0) {
                            [$label, $tone] = ['Penuh', 'bad'];
                        } else {
                            [$label, $tone] = ['Tersedia', 'ok'];
                        }
                    @endphp

                    <tr wire:key="slot-{{ $s->id_slot }}" @class(['is-editing' => $idEdit == $s->id_slot])>
                        <td class="font-medium sj-nowrap">
                            {{ $s->tanggal->locale('id')->translatedFormat('l, d F Y') }}
                        </td>

                        <td>{{ $s->kuota }}</td>

                        <td>
                            {{ $s->terpakai }}
                            <div class="sj-meter"><span style="width: {{ $persen }}%"></span></div>
                        </td>

                        <td class="font-semibold">{{ $sisa }}</td>

                        <td>
                            <span class="sj-badge sj-badge-{{ $tone }}">{{ $label }}</span>
                        </td>

                        <td class="sj-right">
                            <div class="flex justify-end gap-2">
                                <button
                                    type="button"
                                    wire:click="pilih({{ $s->id_slot }})"
                                    x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                                    class="sj-btn sj-btn-soft sj-btn-sm"
                                >
                                    <flux:icon name="pencil-square" class="size-4" />
                                    Ubah
                                </button>

                                <button
                                    type="button"
                                    wire:click="hapus({{ $s->id_slot }})"
                                    wire:confirm="Hapus slot ini? Data booking pada tanggal ini (jika ada) ikut terhapus dan tidak bisa dikembalikan."
                                    class="sj-btn sj-btn-danger sj-btn-sm"
                                >
                                    <flux:icon name="trash" class="size-4" />
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="sj-empty">
                            <flux:icon name="calendar-days" />
                            Belum ada slot yang dibuka.
                            Buka slot lewat form di atas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div>
        {{ $slots->links() }}
    </div>
</div>