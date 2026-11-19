<?php

use App\Http\Controllers\CabangController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DivisiController;
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
    });
});

require __DIR__.'/auth.php';
