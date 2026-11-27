<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('booking', function (Blueprint $table) {
            $table->id('id_booking');
            $table->string('kode_booking')->unique();
            $table->string('nama_pelanggan');
            $table->string('no_hp');
            $table->string('jenis_jahitan');
            $table->date('tanggal_booking');
            $table->enum('status', ['Baru Diterima', 'Sedang Dikerjakan', 'Selesai'])
                  ->default('Baru Diterima');
            $table->foreignId('id_slot')->constrained('slot_booking', 'id_slot');
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking');
    }
};
