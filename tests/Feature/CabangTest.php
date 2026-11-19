<?php

use App\Models\Cabang;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->otoritas(1)->create();
});

test('admin bisa membuka halaman cabang', function () {
    $this->actingAs($this->admin)->get('/cabang')->assertOk()->assertSee('Tambah Cabang');
});

test('admin bisa menambah cabang', function () {
    $this->actingAs($this->admin)->postJson('/cabang', [
        'nama' => 'Cabang Makassar',
        'alamat' => 'Jl. Perintis',
        'latitude' => -5.1476651,
        'longitude' => 119.4327314,
        'radius_meter' => 150,
    ])->assertOk()->assertJson(['message' => 'Cabang berhasil ditambahkan.']);

    $this->assertDatabaseHas('cabang', ['nama' => 'Cabang Makassar', 'radius_meter' => 150]);
});

test('validasi cabang', function () {
    $this->actingAs($this->admin)->postJson('/cabang', ['latitude' => 100])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nama', 'latitude', 'radius_meter']);
});

test('admin bisa mengambil data cabang dengan search', function () {
    Cabang::create(['nama' => 'Cabang Makassar', 'radius_meter' => 100]);
    Cabang::create(['nama' => 'Cabang Jakarta', 'radius_meter' => 100]);

    $this->actingAs($this->admin)->getJson('/cabang/data?search=makas')
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.nama', 'Cabang Makassar');
});

test('admin bisa mengubah cabang', function () {
    $cabang = Cabang::create(['nama' => 'Lama', 'radius_meter' => 100]);

    $this->actingAs($this->admin)->putJson("/cabang/{$cabang->id}", [
        'nama' => 'Baru',
        'radius_meter' => 200,
    ])->assertOk()->assertJson(['message' => 'Cabang berhasil diperbarui.']);

    expect($cabang->fresh())->nama->toBe('Baru')->radius_meter->toBe(200);
});

test('admin bisa menghapus cabang', function () {
    $cabang = Cabang::create(['nama' => 'Hapus', 'radius_meter' => 100]);

    $this->actingAs($this->admin)->deleteJson("/cabang/{$cabang->id}")
        ->assertOk()->assertJson(['message' => 'Cabang berhasil dihapus.']);

    $this->assertDatabaseMissing('cabang', ['id' => $cabang->id]);
});

test('karyawan tidak bisa akses cabang', function () {
    $karyawan = User::factory()->otoritas(3)->create();
    $cabang = Cabang::create(['nama' => 'X', 'radius_meter' => 100]);

    $this->actingAs($karyawan)->get('/cabang')->assertForbidden();
    $this->actingAs($karyawan)->getJson('/cabang/data')->assertForbidden();
    $this->actingAs($karyawan)->postJson('/cabang', ['nama' => 'Y', 'radius_meter' => 100])->assertForbidden();
    $this->actingAs($karyawan)->putJson("/cabang/{$cabang->id}", ['nama' => 'Y', 'radius_meter' => 100])->assertForbidden();
    $this->actingAs($karyawan)->deleteJson("/cabang/{$cabang->id}")->assertForbidden();
});
