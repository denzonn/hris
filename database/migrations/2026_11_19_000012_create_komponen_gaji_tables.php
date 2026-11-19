<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('komponen_gaji', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->string('tipe', 10); // tunjangan / potongan
            $table->string('sifat', 15); // tetap / per_hari_hadir
            $table->timestamps();
        });

        Schema::create('komponen_gaji_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('komponen_gaji_id')->constrained('komponen_gaji'); // restrict: dicek di controller
            $table->decimal('nominal', 15, 2);
            $table->timestamps();

            $table->unique(['user_id', 'komponen_gaji_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('komponen_gaji_user');
        Schema::dropIfExists('komponen_gaji');
    }
};
