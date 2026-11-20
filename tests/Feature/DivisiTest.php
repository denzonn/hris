<?php

use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->otoritas(1)->create();
});

test('admin dan hr bisa membuka halaman divisi', function (int $otoritas) {
    $user = User::factory()->otoritas($otoritas)->create();

    $this->actingAs($user)->get('/divisi')->assertOk()->assertSee('Tambah Divisi');
})->with([1, 2]);

test('admin bisa menambah divisi', function () {
    $this->actingAs($this->admin)->postJson('/divisi', ['nama' => 'Produksi'])
        ->assertOk()->assertJson(['message' => 'Divisi berhasil ditambahkan.']);

    $this->assertDatabaseHas('divisi', ['nama' => 'Produksi']);
});

test('validasi divisi wajib dan unik', function () {
    Divisi::create(['nama' => 'Produksi']);

    $this->actingAs($this->admin)->postJson('/divisi', [])
        ->assertUnprocessable()->assertJsonValidationErrors(['nama']);
    $this->actingAs($this->admin)->postJson('/divisi', ['nama' => 'Produksi'])
        ->assertUnprocessable()->assertJsonValidationErrors(['nama']);
});

test('admin bisa mengambil data divisi dengan search', function () {
    Divisi::create(['nama' => 'Produksi']);
    Divisi::create(['nama' => 'Keuangan']);

    $this->actingAs($this->admin)->getJson('/divisi/data?search=prod')
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.nama', 'Produksi');
});

test('data divisi menyertakan jumlah jabatan dan pegawai aktif', function () {
    $divisi = Divisi::create(['nama' => 'Produksi']);
    Jabatan::create(['nama' => 'Operator', 'divisi_id' => $divisi->id]);
    User::factory()->count(2)->create(['divisi_id' => $divisi->id]);
    User::factory()->nonaktif()->create(['divisi_id' => $divisi->id]);

    $this->actingAs($this->admin)->getJson('/divisi/data?search=prod')
        ->assertJsonPath('data.0.jabatan_count', 1)
        ->assertJsonPath('data.0.pegawai_count', 2);
});

test('admin bisa mengubah divisi tanpa bentrok unik dengan dirinya sendiri', function () {
    $divisi = Divisi::create(['nama' => 'Lama']);

    $this->actingAs($this->admin)->putJson("/divisi/{$divisi->id}", ['nama' => 'Lama'])->assertOk();
    $this->actingAs($this->admin)->putJson("/divisi/{$divisi->id}", ['nama' => 'Baru'])
        ->assertOk()->assertJson(['message' => 'Divisi berhasil diperbarui.']);

    expect($divisi->fresh()->nama)->toBe('Baru');
});

test('admin bisa menghapus divisi', function () {
    $divisi = Divisi::create(['nama' => 'Hapus']);

    $this->actingAs($this->admin)->deleteJson("/divisi/{$divisi->id}")
        ->assertOk()->assertJson(['message' => 'Divisi berhasil dihapus.']);

    $this->assertDatabaseMissing('divisi', ['id' => $divisi->id]);
});

test('karyawan tidak bisa akses divisi', function () {
    $karyawan = User::factory()->otoritas(3)->create();
    $divisi = Divisi::create(['nama' => 'X']);

    $this->actingAs($karyawan)->get('/divisi')->assertForbidden();
    $this->actingAs($karyawan)->getJson('/divisi/data')->assertForbidden();
    $this->actingAs($karyawan)->postJson('/divisi', ['nama' => 'Y'])->assertForbidden();
    $this->actingAs($karyawan)->putJson("/divisi/{$divisi->id}", ['nama' => 'Y'])->assertForbidden();
    $this->actingAs($karyawan)->deleteJson("/divisi/{$divisi->id}")->assertForbidden();
});
