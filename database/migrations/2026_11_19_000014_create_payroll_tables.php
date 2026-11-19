<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll', function (Blueprint $table) {
            $table->id();
            $table->char('periode', 7)->unique(); // Y-m = bulan gajian
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('status', 10)->default('draft'); // draft / final
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('difinalkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('difinalkan_pada')->nullable();
            $table->timestamps();

            $table->index(['status', 'tanggal_mulai', 'tanggal_selesai']);
        });

        // Snapshot: angka disimpan apa adanya saat generate, tidak dihitung ulang dari master.
        Schema::create('payroll_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payroll')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('nama');
            $table->string('nik')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('cabang')->nullable();
            $table->string('bank')->nullable();
            $table->string('no_rekening')->nullable();
            $table->unsignedSmallInteger('hari_kerja')->default(0);
            $table->unsignedSmallInteger('hari_hadir')->default(0);
            $table->unsignedInteger('menit_telat')->default(0);
            $table->unsignedSmallInteger('jumlah_alpha')->default(0);
            $table->unsignedInteger('menit_lembur')->default(0);
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->decimal('tunjangan_jabatan', 15, 2)->default(0);
            $table->decimal('total_tunjangan', 15, 2)->default(0); // Σ komponen tunjangan
            $table->decimal('upah_lembur', 15, 2)->default(0);
            $table->decimal('potongan_telat', 15, 2)->default(0);
            $table->decimal('potongan_alpha', 15, 2)->default(0);
            $table->decimal('total_potongan', 15, 2)->default(0); // Σ komponen potongan
            $table->decimal('gaji_bersih', 15, 2)->default(0);
            $table->json('rincian'); // komponen: nama, tipe, sifat, nominal, jumlah
            $table->timestamps();

            $table->unique(['payroll_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_detail');
        Schema::dropIfExists('payroll');
    }
};
