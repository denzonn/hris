<?php

use App\Models\KomponenGaji;
use App\Models\PengaturanPayroll;
use App\Models\User;

beforeEach(function () {
    $this->hr = User::factory()->otoritas(2)->create();
    $this->pegawai = User::factory()->create();
});

test('admin dan hr bisa membuka halaman komponen, gaji pegawai, pengaturan payroll', function (string $url) {
    $this->actingAs($this->hr)->get($url)->assertOk();
})->with(['/komponen_gaji', '/gaji_pegawai', '/pengaturan_payroll']);

test('crud komponen gaji dengan validasi', function () {
    $this->actingAs($this->hr)->postJson('/komponen_gaji', [])
        ->assertUnprocessable()->assertJsonValidationErrors(['nama', 'tipe', 'sifat']);
    $this->actingAs($this->hr)->postJson('/komponen_gaji', ['nama' => 'Uang Makan', 'tipe' => 'bonus', 'sifat' => 'tetap'])
        ->assertUnprocessable()->assertJsonValidationErrors(['tipe']);

    $this->actingAs($this->hr)->postJson('/komponen_gaji', ['nama' => 'Uang Makan', 'tipe' => 'tunjangan', 'sifat' => 'per_hari_hadir'])
        ->assertOk()->assertJson(['message' => 'Komponen gaji berhasil ditambahkan.']);
    $k = KomponenGaji::first();

    $this->actingAs($this->hr)->putJson("/komponen_gaji/{$k->id}", ['nama' => 'Uang Makan', 'tipe' => 'tunjangan', 'sifat' => 'tetap'])->assertOk();
    expect($k->fresh()->sifat)->toBe('tetap');

    $this->actingAs($this->hr)->getJson('/komponen_gaji/data?tipe=tunjangan')->assertJsonPath('total', 1)->assertJsonPath('data.0.pegawai_count', 0);
    $this->actingAs($this->hr)->deleteJson("/komponen_gaji/{$k->id}")->assertOk();
});

test('komponen yang dipakai pegawai tidak bisa dihapus', function () {
    $k = KomponenGaji::create(['nama' => 'Transport', 'tipe' => 'tunjangan', 'sifat' => 'tetap']);
    $this->pegawai->komponenGaji()->attach($k, ['nominal' => 100000]);

    $this->actingAs($this->hr)->deleteJson("/komponen_gaji/{$k->id}")
        ->assertUnprocessable()->assertJson(['message' => 'Komponen tidak bisa dihapus karena masih dipakai pegawai.']);
});

test('simpan nominal komponen pegawai: format rupiah dibaca, nol berarti dilepas', function () {
    $makan = KomponenGaji::create(['nama' => 'Uang Makan', 'tipe' => 'tunjangan', 'sifat' => 'per_hari_hadir']);
    $kasbon = KomponenGaji::create(['nama' => 'Kasbon', 'tipe' => 'potongan', 'sifat' => 'tetap']);
    $this->pegawai->komponenGaji()->attach($kasbon, ['nominal' => 50000]);

    $this->actingAs($this->hr)->putJson("/gaji_pegawai/{$this->pegawai->id}", [
        'komponen' => [$makan->id => '25.000', $kasbon->id => '0', 9999 => '1.000'],
    ])->assertOk();

    $komponen = $this->pegawai->komponenGaji()->get();
    expect($komponen->pluck('nama')->all())->toBe(['Uang Makan'])
        ->and((int) $komponen->first()->pivot->nominal)->toBe(25000);

    $this->actingAs($this->hr)->getJson('/gaji_pegawai/data?search='.urlencode($this->pegawai->name))
        ->assertJsonPath('data.0.komponen_gaji.0.nama', 'Uang Makan');
});

test('komponen gaji super admin tidak bisa diatur', function () {
    $admin = User::factory()->otoritas(1)->create();

    $this->actingAs($this->hr)->putJson("/gaji_pegawai/{$admin->id}", ['komponen' => []])->assertForbidden();
});

test('pengaturan payroll tersimpan dari input berformat rupiah', function () {
    $this->actingAs($this->hr)->putJson('/pengaturan_payroll', [
        'tanggal_cutoff' => 25, 'potongan_per_menit_telat' => '1.000', 'potongan_per_alpha' => '100.000',
    ])->assertOk()->assertJson(['message' => 'Pengaturan payroll berhasil disimpan.']);

    expect(PengaturanPayroll::aktif())
        ->tanggal_cutoff->toBe(25)->potongan_per_menit_telat->toBe('1000.00')->potongan_per_alpha->toBe('100000.00');
    $this->assertDatabaseCount('pengaturan_payroll', 1);

    $this->actingAs($this->hr)->putJson('/pengaturan_payroll', ['tanggal_cutoff' => 31])
        ->assertUnprocessable()->assertJsonValidationErrors(['tanggal_cutoff']);
});

test('karyawan tidak bisa akses master gaji', function () {
    foreach (['/komponen_gaji', '/gaji_pegawai', '/pengaturan_payroll'] as $url) {
        $this->actingAs($this->pegawai)->get($url)->assertForbidden();
    }
    $this->actingAs($this->pegawai)->putJson("/gaji_pegawai/{$this->pegawai->id}", ['komponen' => []])->assertForbidden();
    $this->actingAs($this->pegawai)->putJson('/pengaturan_payroll', [])->assertForbidden();
});
