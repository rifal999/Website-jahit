<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SlotBooking extends Model
{
    protected $table = 'slot_booking';

    protected $primaryKey = 'id_slot';

    protected $fillable = [
        'tanggal',
        'kuota',
        'terpakai',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'id_slot', 'id_slot');
    }
}
