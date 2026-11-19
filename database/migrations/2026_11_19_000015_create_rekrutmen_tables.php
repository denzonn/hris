<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lowongan', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->foreignId('divisi_id')->nullable()->constrained('divisi')->nullOnDelete();
            $table->foreignId('jabatan_id')->nullable()->constrained('jabatan')->nullOnDelete();
            $table->text('deskripsi');
            $table->text('kualifikasi');
            $table->date('tanggal_tutup')->nullable(); // null = sampai dinonaktifkan
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('pelamar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lowongan_id')->constrained('lowongan')->cascadeOnDelete();
            $table->string('nama');
            $table->string('email');
            $table->string('no_hp', 30);
            $table->string('pendidikan_terakhir', 50);
            $table->text('pesan')->nullable();
            $table->string('cv'); // disk local (private)
            $table->string('status', 10)->default('baru'); // baru / interview / diterima / ditolak
            $table->string('catatan')->nullable(); // catatan internal HR
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // terisi setelah jadi pegawai
            $table->timestamps();

            $table->unique(['lowongan_id', 'email']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelamar');
        Schema::dropIfExists('lowongan');
    }
};
