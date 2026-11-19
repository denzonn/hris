<?php

use App\Models\Cabang;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->otoritas(1)->create();
    $this->cabang = Cabang::create(['nama' => 'Makassar', 'radius_meter' => 100]);
});

function pengaturanPayload(array $override = []): array
{
    return array_merge([
        'jam_masuk' => '08:00',
        'jam_pulang' => '17:00',
        'toleransi_telat_menit' => 15,
        'hari_kerja' => ['6', '1', '2', '3', '4', '5'],
        'wajib_foto' => '1',
    ], $override);
}

test('admin dan hr bisa membuka halaman pengaturan absensi', function (int $otoritas) {
    $user = User::factory()->otoritas($otoritas)->create();

    $this->actingAs($user)->get('/pengaturan_absensi')->assertOk()->assertSee('Pengaturan Absensi');
})->with([1, 2]);

test('data menampilkan semua cabang, yang belum diatur bernilai null', function () {
    $this->actingAs($this->admin)->getJson('/pengaturan_absensi/data')
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.nama', 'Makassar')
        ->assertJsonPath('data.0.pengaturan_absensi', null);
});

test('simpan pengaturan membuat lalu memperbarui satu baris per cabang', function () {
    $this->actingAs($this->admin)->putJson("/pengaturan_absensi/{$this->cabang->id}", pengaturanPayload())
        ->assertOk()->assertJson(['message' => 'Pengaturan absensi Makassar berhasil disimpan.']);

    $p = $this->cabang->pengaturanAbsensi()->first();
    expect($p->hari_kerja)->toBe([1, 2, 3, 4, 5, 6])
        ->and($p->toleransi_telat_menit)->toBe(15)
        ->and($p->wajib_foto)->toBeTrue()
        ->and($p->wajib_lokasi)->toBeFalse(); // checkbox tidak dikirim = false

    $this->actingAs($this->admin)->putJson("/pengaturan_absensi/{$this->cabang->id}", pengaturanPayload([
        'jam_masuk' => '09:00', 'hari_kerja' => [1, 2, 3, 4, 5], 'wajib_lokasi' => '1',
    ]))->assertOk();

    $this->assertDatabaseCount('pengaturan_absensi', 1);
    $p = $p->fresh();
    expect($p->jam_masuk)->toStartWith('09:00')
        ->and($p->hari_kerja)->toBe([1, 2, 3, 4, 5])
        ->and($p->wajib_lokasi)->toBeTrue();
});

test('validasi pengaturan absensi', function () {
    $this->actingAs($this->admin)->putJson("/pengaturan_absensi/{$this->cabang->id}", [])
        ->assertUnprocessable()->assertJsonValidationErrors(['jam_masuk', 'jam_pulang', 'hari_kerja']);

    $this->actingAs($this->admin)->putJson("/pengaturan_absensi/{$this->cabang->id}", pengaturanPayload([
        'jam_pulang' => '07:00', 'hari_kerja' => [1, 8],
    ]))->assertUnprocessable()->assertJsonValidationErrors(['jam_pulang', 'hari_kerja.1']);
});

test('karyawan tidak bisa akses pengaturan absensi', function () {
    $karyawan = User::factory()->otoritas(3)->create();

    $this->actingAs($karyawan)->get('/pengaturan_absensi')->assertForbidden();
    $this->actingAs($karyawan)->getJson('/pengaturan_absensi/data')->assertForbidden();
    $this->actingAs($karyawan)->putJson("/pengaturan_absensi/{$this->cabang->id}", pengaturanPayload())->assertForbidden();
});
