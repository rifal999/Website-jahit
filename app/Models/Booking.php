<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Model untuk tabel booking: satu baris = satu pesanan jahitan pelanggan
class Booking extends Model
{
    protected $table = 'booking';
    protected $primaryKey = 'id_booking';

    // Kolom yang boleh diisi lewat form.
    // Kolom lain diabaikan supaya data tidak bisa diubah sembarangan.
    protected $fillable = [
        'kode_booking',     // kode untuk cek status oleh pelanggan
        'nama_pelanggan',
        'no_hp',
        'jenis_jahitan',
        'tanggal_booking',
        'status',           // Baru Diterima, Sedang Dikerjakan, atau Selesai
        'estimasi_selesai', // diisi admin saat update status (boleh kosong)
        'alasan_penolakan', // alasan admin saat menolak booking
        'id_slot',          // tanggal slot yang dipilih
        'id_user',          // admin yang menangani (boleh kosong)
    ];

    // Kolom tanggal dibaca sebagai objek tanggal, bukan teks biasa.
    protected $casts = [
        'tanggal_booking' => 'date',
        'estimasi_selesai' => 'date',
    ];

    public function slot(): BelongsTo
    {
        return $this->belongsTo(SlotBooking::class, 'id_slot', 'id_slot');
    }

    // Admin yang menangani booking ini.
    // Dipakai seperti: $booking->admin->name
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }
}