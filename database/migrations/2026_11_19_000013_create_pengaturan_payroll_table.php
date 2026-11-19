<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hanya 1 baris (pengaturan global)
        Schema::create('pengaturan_payroll', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('tanggal_cutoff')->nullable(); // null = akhir bulan (periode 1 bulan kalender)
            $table->decimal('potongan_per_menit_telat', 15, 2)->default(0);
            $table->decimal('potongan_per_alpha', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_payroll');
    }
};
