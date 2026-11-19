<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nik', 50)->nullable()->unique()->after('email');
            $table->foreignId('cabang_id')->nullable()->after('nik')->constrained('cabang')->nullOnDelete();
            $table->foreignId('divisi_id')->nullable()->after('cabang_id')->constrained('divisi')->nullOnDelete();
            $table->foreignId('jabatan_id')->nullable()->after('divisi_id')->constrained('jabatan')->nullOnDelete();
            $table->string('no_hp', 30)->nullable();
            $table->string('alamat', 500)->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->enum('status_kerja', ['tetap', 'kontrak', 'magang'])->default('tetap');
            $table->date('tanggal_akhir_kontrak')->nullable();
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->string('bank', 100)->nullable();
            $table->string('no_rekening', 50)->nullable();
            $table->string('npwp', 30)->nullable();
            $table->string('status_ptkp', 10)->nullable();
            $table->unsignedTinyInteger('jatah_cuti')->default(12);
            $table->string('foto')->nullable();
            $table->boolean('aktif')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cabang_id');
            $table->dropConstrainedForeignId('divisi_id');
            $table->dropConstrainedForeignId('jabatan_id');
            $table->dropUnique(['nik']);
            $table->dropColumn([
                'nik', 'no_hp', 'alamat', 'tanggal_masuk', 'status_kerja', 'tanggal_akhir_kontrak', 'gaji_pokok',
                'bank', 'no_rekening', 'npwp', 'status_ptkp', 'jatah_cuti', 'foto', 'aktif',
            ]);
        });
    }
};
