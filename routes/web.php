<?php

use App\Http\Controllers\AbsenController;
use App\Http\Controllers\AbsensiKaryawanController;
use App\Http\Controllers\CabangController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DivisiController;
use App\Http\Controllers\GajiPegawaiController;
use App\Http\Controllers\HariLiburController;
use App\Http\Controllers\IzinKaryawanController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\JobdeskController;
use App\Http\Controllers\KomponenGajiController;
use App\Http\Controllers\LemburController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PengaturanAbsensiController;
use App\Http\Controllers\PengaturanPayrollController;
use App\Http\Controllers\RekapAbsensiController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('otoritas:1')->group(function () {
        Route::get('cabang', [CabangController::class, 'index'])->name('cabang');
        Route::get('cabang/data', [CabangController::class, 'data'])->name('cabang.data');
        Route::post('cabang', [CabangController::class, 'store'])->name('cabang.store');
        Route::put('cabang/{cabang}', [CabangController::class, 'update'])->name('cabang.update');
        Route::delete('cabang/{cabang}', [CabangController::class, 'destroy'])->name('cabang.destroy');
    });

    Route::middleware('otoritas:1,2')->group(function () {
        Route::get('divisi', [DivisiController::class, 'index'])->name('divisi');
        Route::get('divisi/data', [DivisiController::class, 'data'])->name('divisi.data');
        Route::post('divisi', [DivisiController::class, 'store'])->name('divisi.store');
        Route::put('divisi/{divisi}', [DivisiController::class, 'update'])->name('divisi.update');
        Route::delete('divisi/{divisi}', [DivisiController::class, 'destroy'])->name('divisi.destroy');

        Route::get('jabatan', [JabatanController::class, 'index'])->name('jabatan');
        Route::get('jabatan/data', [JabatanController::class, 'data'])->name('jabatan.data');
        Route::post('jabatan', [JabatanController::class, 'store'])->name('jabatan.store');
        Route::put('jabatan/{jabatan}', [JabatanController::class, 'update'])->name('jabatan.update');
        Route::delete('jabatan/{jabatan}', [JabatanController::class, 'destroy'])->name('jabatan.destroy');

        Route::get('jobdesk', [JobdeskController::class, 'index'])->name('jobdesk');
        Route::get('jobdesk/data', [JobdeskController::class, 'data'])->name('jobdesk.data');
        Route::post('jobdesk', [JobdeskController::class, 'store'])->name('jobdesk.store');
        Route::put('jobdesk/{jobdesk}', [JobdeskController::class, 'update'])->name('jobdesk.update');
        Route::delete('jobdesk/{jobdesk}', [JobdeskController::class, 'destroy'])->name('jobdesk.destroy');

        // Pegawai: tidak ada hard delete, hanya toggle aktif
        Route::get('user', [UserController::class, 'index'])->name('user');
        Route::get('user/data', [UserController::class, 'data'])->name('user.data');
        Route::post('user', [UserController::class, 'store'])->name('user.store');
        Route::put('user/{user}', [UserController::class, 'update'])->name('user.update');
        Route::patch('user/{user}/aktif', [UserController::class, 'toggleAktif'])->name('user.aktif');

        // 1 pengaturan per cabang: hanya simpan (upsert), tanpa tambah/hapus
        Route::get('pengaturan_absensi', [PengaturanAbsensiController::class, 'index'])->name('pengaturan_absensi');
        Route::get('pengaturan_absensi/data', [PengaturanAbsensiController::class, 'data'])->name('pengaturan_absensi.data');
        Route::put('pengaturan_absensi/{cabang}', [PengaturanAbsensiController::class, 'update'])->name('pengaturan_absensi.update');

        Route::get('hari_libur', [HariLiburController::class, 'index'])->name('hari_libur');
        Route::get('hari_libur/data', [HariLiburController::class, 'data'])->name('hari_libur.data');
        Route::post('hari_libur', [HariLiburController::class, 'store'])->name('hari_libur.store');
        Route::put('hari_libur/{hari_libur}', [HariLiburController::class, 'update'])->name('hari_libur.update');
        Route::delete('hari_libur/{hari_libur}', [HariLiburController::class, 'destroy'])->name('hari_libur.destroy');

        Route::patch('lembur/{lembur}/status', [LemburController::class, 'status'])->name('lembur.status');
        Route::patch('izin_karyawan/{izin}/status', [IzinKaryawanController::class, 'status'])->name('izin_karyawan.status');

        Route::get('absensi_karyawan', [AbsensiKaryawanController::class, 'index'])->name('absensi_karyawan');
        Route::get('absensi_karyawan/data', [AbsensiKaryawanController::class, 'data'])->name('absensi_karyawan.data');
        Route::post('absensi_karyawan', [AbsensiKaryawanController::class, 'store'])->name('absensi_karyawan.store');
        Route::put('absensi_karyawan/{absensi}', [AbsensiKaryawanController::class, 'update'])->name('absensi_karyawan.update');
        Route::delete('absensi_karyawan/{absensi}', [AbsensiKaryawanController::class, 'destroy'])->name('absensi_karyawan.destroy');

        Route::get('rekap_absensi', [RekapAbsensiController::class, 'index'])->name('rekap_absensi');
        Route::get('rekap_absensi/data', [RekapAbsensiController::class, 'data'])->name('rekap_absensi.data');
        Route::get('rekap_absensi/export', [RekapAbsensiController::class, 'export'])->name('rekap_absensi.export');

        Route::get('komponen_gaji', [KomponenGajiController::class, 'index'])->name('komponen_gaji');
        Route::get('komponen_gaji/data', [KomponenGajiController::class, 'data'])->name('komponen_gaji.data');
        Route::post('komponen_gaji', [KomponenGajiController::class, 'store'])->name('komponen_gaji.store');
        Route::put('komponen_gaji/{komponen_gaji}', [KomponenGajiController::class, 'update'])->name('komponen_gaji.update');
        Route::delete('komponen_gaji/{komponen_gaji}', [KomponenGajiController::class, 'destroy'])->name('komponen_gaji.destroy');

        // Nominal komponen per pegawai: hanya simpan (sync), tanpa tambah/hapus
        Route::get('gaji_pegawai', [GajiPegawaiController::class, 'index'])->name('gaji_pegawai');
        Route::get('gaji_pegawai/data', [GajiPegawaiController::class, 'data'])->name('gaji_pegawai.data');
        Route::put('gaji_pegawai/{user}', [GajiPegawaiController::class, 'update'])->name('gaji_pegawai.update');

        Route::get('pengaturan_payroll', [PengaturanPayrollController::class, 'index'])->name('pengaturan_payroll');
        Route::put('pengaturan_payroll', [PengaturanPayrollController::class, 'update'])->name('pengaturan_payroll.update');

        Route::get('payroll', [PayrollController::class, 'index'])->name('payroll');
        Route::get('payroll/data', [PayrollController::class, 'data'])->name('payroll.data');
        Route::post('payroll', [PayrollController::class, 'store'])->name('payroll.store');
        Route::get('payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
        Route::get('payroll/{payroll}/data', [PayrollController::class, 'detail'])->name('payroll.detail');
        Route::post('payroll/{payroll}/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
        Route::patch('payroll/{payroll}/final', [PayrollController::class, 'final'])->name('payroll.final');
        Route::delete('payroll/{payroll}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
        Route::get('payroll/{payroll}/slip/{detail}', [PayrollController::class, 'slip'])->name('payroll.slip');
    });

    // Absen mandiri: semua otoritas, hanya untuk diri sendiri
    Route::get('absen', [AbsenController::class, 'index'])->name('absen');
    Route::get('absen/data', [AbsenController::class, 'data'])->name('absen.data');
    Route::post('absen/masuk', [AbsenController::class, 'masuk'])->name('absen.masuk');
    Route::post('absen/pulang', [AbsenController::class, 'pulang'])->name('absen.pulang');

    // Lembur: semua otoritas; karyawan dibatasi ke datanya sendiri di controller
    Route::get('lembur', [LemburController::class, 'index'])->name('lembur');
    Route::get('lembur/data', [LemburController::class, 'data'])->name('lembur.data');
    Route::post('lembur', [LemburController::class, 'store'])->name('lembur.store');
    Route::put('lembur/{lembur}', [LemburController::class, 'update'])->name('lembur.update');
    Route::delete('lembur/{lembur}', [LemburController::class, 'destroy'])->name('lembur.destroy');

    // Izin/sakit/cuti: semua otoritas; karyawan dibatasi ke datanya sendiri di controller
    Route::get('izin_karyawan', [IzinKaryawanController::class, 'index'])->name('izin_karyawan');
    Route::get('izin_karyawan/data', [IzinKaryawanController::class, 'data'])->name('izin_karyawan.data');
    Route::post('izin_karyawan', [IzinKaryawanController::class, 'store'])->name('izin_karyawan.store');
    Route::put('izin_karyawan/{izin}', [IzinKaryawanController::class, 'update'])->name('izin_karyawan.update');
    Route::delete('izin_karyawan/{izin}', [IzinKaryawanController::class, 'destroy'])->name('izin_karyawan.destroy');
    Route::get('izin_karyawan/{izin}/lampiran', [IzinKaryawanController::class, 'lampiran'])->name('izin_karyawan.lampiran');
});

require __DIR__.'/auth.php';
