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
    Schema::table('booking', function (Blueprint $table) {
        // Tanggal estimasi selesai, diisi admin. Kosong = "belum ditentukan"
        $table->date('estimasi_selesai')->nullable()->after('status');
    });
}

public function down(): void
{
    Schema::table('booking', function (Blueprint $table) {
        $table->dropColumn('estimasi_selesai');
    });
}
};
