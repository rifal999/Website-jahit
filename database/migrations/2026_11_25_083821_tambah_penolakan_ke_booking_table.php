<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah nilai Ditolak pada kolom status
        DB::statement("ALTER TABLE booking MODIFY status ENUM('Baru Diterima','Sedang Dikerjakan','Selesai','Ditolak') NOT NULL DEFAULT 'Baru Diterima'");

        Schema::table('booking', function (Blueprint $table) {
            $table->text('alasan_penolakan')->nullable()->after('estimasi_selesai');
        });
    }

    public function down(): void
    {
        Schema::table('booking', function (Blueprint $table) {
            $table->dropColumn('alasan_penolakan');
        });

        DB::statement("UPDATE booking SET status = 'Baru Diterima' WHERE status = 'Ditolak'");
        DB::statement("ALTER TABLE booking MODIFY status ENUM('Baru Diterima','Sedang Dikerjakan','Selesai') NOT NULL DEFAULT 'Baru Diterima'");
    }
};