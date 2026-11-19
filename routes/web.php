<?php

use App\Http\Controllers\CabangController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DivisiController;
use App\Http\Controllers\HariLiburController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\JobdeskController;
use App\Http\Controllers\LemburController;
use App\Http\Controllers\PengaturanAbsensiController;
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
    });

    // Lembur: semua otoritas; karyawan dibatasi ke datanya sendiri di controller
    Route::get('lembur', [LemburController::class, 'index'])->name('lembur');
    Route::get('lembur/data', [LemburController::class, 'data'])->name('lembur.data');
    Route::post('lembur', [LemburController::class, 'store'])->name('lembur.store');
    Route::put('lembur/{lembur}', [LemburController::class, 'update'])->name('lembur.update');
    Route::delete('lembur/{lembur}', [LemburController::class, 'destroy'])->name('lembur.destroy');
});

require __DIR__.'/auth.php';
