<?php

use App\Http\Controllers\CabangController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DivisiController;
use App\Http\Controllers\JabatanController;
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
    });
});

require __DIR__.'/auth.php';
