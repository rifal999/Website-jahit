<?php

use Illuminate\Support\Facades\Route;

// Halaman depan pelanggan (tanpa login)
Route::view('/', 'welcome')->name('home');

// Halaman pelanggan (tanpa login)
Route::livewire('/booking', 'booking.form')->name('booking');
Route::livewire('/cek-status', 'booking.status')->name('booking.status');

// Halaman admin: hanya bisa dibuka setelah login
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Booking masuk dan update status
    Route::livewire('booking', 'admin.booking')->name('admin.booking');

    // Atur tanggal dan kuota slot booking
    Route::livewire('slot', 'admin.slot')->name('admin.slot');
});

require __DIR__.'/settings.php';