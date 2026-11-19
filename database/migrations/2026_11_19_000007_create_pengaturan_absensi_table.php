<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1 jadwal per cabang. Shift baru ditambah kalau memang ada karyawan shift.
        Schema::create('pengaturan_absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabang_id')->unique()->constrained('cabang')->cascadeOnDelete();
            $table->time('jam_masuk');
            $table->time('jam_pulang');
            $table->unsignedSmallInteger('toleransi_telat_menit')->default(0);
            $table->json('hari_kerja'); // ISO day: 1 = Senin ... 7 = Minggu
            $table->boolean('wajib_foto')->default(true);
            $table->boolean('wajib_lokasi')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_absensi');
    }
};
