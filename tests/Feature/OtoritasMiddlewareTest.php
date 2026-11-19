<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth', 'otoritas:1,2'])->get('/_test-otoritas', fn () => 'ok');
});

test('otoritas yang diizinkan bisa akses', function (int $otoritas) {
    $this->actingAs(User::factory()->otoritas($otoritas)->create())
        ->get('/_test-otoritas')
        ->assertOk();
})->with([1, 2]);

test('otoritas yang tidak diizinkan dapat 403', function () {
    $this->actingAs(User::factory()->otoritas(3)->create())
        ->get('/_test-otoritas')
        ->assertForbidden();
});
