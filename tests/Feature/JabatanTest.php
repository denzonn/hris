<?php

use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->otoritas(1)->create();
    $this->divisi = Divisi::create(['nama' => 'Produksi']);
});

test('admin dan hr bisa membuka halaman jabatan', function (int $otoritas) {
    $user = User::factory()->otoritas($otoritas)->create();

    $this->actingAs($user)->get('/jabatan')->assertOk()->assertSee('Tambah Jabatan')->assertSee('Produksi');
})->with([1, 2]);

test('admin bisa menambah jabatan', function () {
    $this->actingAs($this->admin)->postJson('/jabatan', [
        'nama' => 'Operator',
        'divisi_id' => $this->divisi->id,
        'tunjangan_jabatan' => 750000,
    ])->assertOk()->assertJson(['message' => 'Jabatan berhasil ditambahkan.']);

    $this->assertDatabaseHas('jabatan', ['nama' => 'Operator', 'tunjangan_jabatan' => 750000]);
});

test('tunjangan kosong disimpan sebagai 0', function () {
    $this->actingAs($this->admin)->postJson('/jabatan', [
        'nama' => 'Helper',
        'divisi_id' => $this->divisi->id,
        'tunjangan_jabatan' => '',
    ])->assertOk();

    expect(Jabatan::first()->tunjangan_jabatan)->toBe('0.00');
});

test('validasi jabatan', function () {
    $this->actingAs($this->admin)->postJson('/jabatan', ['divisi_id' => 999, 'tunjangan_jabatan' => -1])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nama', 'divisi_id', 'tunjangan_jabatan']);
});

test('data jabatan memuat nama divisi dan bisa difilter per divisi', function () {
    $lain = Divisi::create(['nama' => 'Keuangan']);
    Jabatan::create(['nama' => 'Operator', 'divisi_id' => $this->divisi->id]);
    Jabatan::create(['nama' => 'Kasir', 'divisi_id' => $lain->id]);

    $this->actingAs($this->admin)->getJson("/jabatan/data?divisi_id={$lain->id}")
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.nama', 'Kasir')
        ->assertJsonPath('data.0.divisi.nama', 'Keuangan');
});

test('admin bisa mengubah dan menghapus jabatan', function () {
    $jabatan = Jabatan::create(['nama' => 'Lama', 'divisi_id' => $this->divisi->id]);

    $this->actingAs($this->admin)->putJson("/jabatan/{$jabatan->id}", [
        'nama' => 'Baru',
        'divisi_id' => $this->divisi->id,
        'tunjangan_jabatan' => 1000000,
    ])->assertOk()->assertJson(['message' => 'Jabatan berhasil diperbarui.']);

    expect($jabatan->fresh())->nama->toBe('Baru')->tunjangan_jabatan->toBe('1000000.00');

    $this->actingAs($this->admin)->deleteJson("/jabatan/{$jabatan->id}")
        ->assertOk()->assertJson(['message' => 'Jabatan berhasil dihapus.']);
    $this->assertDatabaseMissing('jabatan', ['id' => $jabatan->id]);
});

test('divisi yang masih punya jabatan tidak bisa dihapus', function () {
    Jabatan::create(['nama' => 'Operator', 'divisi_id' => $this->divisi->id]);

    $this->actingAs($this->admin)->deleteJson("/divisi/{$this->divisi->id}")
        ->assertUnprocessable()
        ->assertJson(['message' => 'Divisi tidak bisa dihapus karena masih memiliki jabatan.']);

    $this->assertDatabaseHas('divisi', ['id' => $this->divisi->id]);
});

test('karyawan tidak bisa akses jabatan', function () {
    $karyawan = User::factory()->otoritas(3)->create();
    $jabatan = Jabatan::create(['nama' => 'X', 'divisi_id' => $this->divisi->id]);
    $payload = ['nama' => 'Y', 'divisi_id' => $this->divisi->id];

    $this->actingAs($karyawan)->get('/jabatan')->assertForbidden();
    $this->actingAs($karyawan)->getJson('/jabatan/data')->assertForbidden();
    $this->actingAs($karyawan)->postJson('/jabatan', $payload)->assertForbidden();
    $this->actingAs($karyawan)->putJson("/jabatan/{$jabatan->id}", $payload)->assertForbidden();
    $this->actingAs($karyawan)->deleteJson("/jabatan/{$jabatan->id}")->assertForbidden();
});
