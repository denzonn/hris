<?php

use App\Models\HariLibur;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->otoritas(1)->create();
});

test('admin dan hr bisa membuka halaman hari libur', function (int $otoritas) {
    $user = User::factory()->otoritas($otoritas)->create();

    $this->actingAs($user)->get('/hari_libur')->assertOk()->assertSee('Tambah Hari Libur');
})->with([1, 2]);

test('admin bisa menambah hari libur', function () {
    $this->actingAs($this->admin)->postJson('/hari_libur', ['tanggal' => '2026-08-17', 'keterangan' => 'HUT RI'])
        ->assertOk()->assertJson(['message' => 'Hari libur berhasil ditambahkan.']);

    $this->assertDatabaseHas('hari_libur', ['keterangan' => 'HUT RI']);
});

test('tanggal hari libur wajib dan unik', function () {
    HariLibur::create(['tanggal' => '2026-08-17', 'keterangan' => 'HUT RI']);

    $this->actingAs($this->admin)->postJson('/hari_libur', [])
        ->assertUnprocessable()->assertJsonValidationErrors(['tanggal', 'keterangan']);
    $this->actingAs($this->admin)->postJson('/hari_libur', ['tanggal' => '2026-08-17', 'keterangan' => 'Dobel'])
        ->assertUnprocessable()->assertJsonValidationErrors(['tanggal']);
});

test('data hari libur bisa difilter tahun dan search', function () {
    HariLibur::create(['tanggal' => '2026-08-17', 'keterangan' => 'HUT RI']);
    HariLibur::create(['tanggal' => '2026-12-25', 'keterangan' => 'Natal']);
    HariLibur::create(['tanggal' => '2027-01-01', 'keterangan' => 'Tahun Baru']);

    $this->actingAs($this->admin)->getJson('/hari_libur/data?tahun=2026')
        ->assertOk()->assertJsonPath('total', 2)->assertJsonPath('data.0.tanggal', '2026-08-17');
    $this->actingAs($this->admin)->getJson('/hari_libur/data?search=natal')
        ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.keterangan', 'Natal');
});

test('admin bisa mengubah dan menghapus hari libur', function () {
    $libur = HariLibur::create(['tanggal' => '2026-08-17', 'keterangan' => 'HUT RI']);

    $this->actingAs($this->admin)->putJson("/hari_libur/{$libur->id}", ['tanggal' => '2026-08-17', 'keterangan' => 'HUT RI ke-81'])
        ->assertOk()->assertJson(['message' => 'Hari libur berhasil diperbarui.']);
    expect($libur->fresh()->keterangan)->toBe('HUT RI ke-81');

    $this->actingAs($this->admin)->deleteJson("/hari_libur/{$libur->id}")
        ->assertOk()->assertJson(['message' => 'Hari libur berhasil dihapus.']);
    $this->assertDatabaseMissing('hari_libur', ['id' => $libur->id]);
});

test('karyawan tidak bisa akses hari libur', function () {
    $karyawan = User::factory()->otoritas(3)->create();
    $libur = HariLibur::create(['tanggal' => '2026-08-17', 'keterangan' => 'HUT RI']);

    $this->actingAs($karyawan)->get('/hari_libur')->assertForbidden();
    $this->actingAs($karyawan)->getJson('/hari_libur/data')->assertForbidden();
    $this->actingAs($karyawan)->postJson('/hari_libur', ['tanggal' => '2026-09-01', 'keterangan' => 'X'])->assertForbidden();
    $this->actingAs($karyawan)->putJson("/hari_libur/{$libur->id}", ['tanggal' => '2026-09-01', 'keterangan' => 'X'])->assertForbidden();
    $this->actingAs($karyawan)->deleteJson("/hari_libur/{$libur->id}")->assertForbidden();
});
