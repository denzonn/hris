<?php

use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\Jobdesk;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->otoritas(1)->create();
    $divisi = Divisi::create(['nama' => 'Produksi']);
    $this->jabatan = Jabatan::create(['nama' => 'Operator', 'divisi_id' => $divisi->id]);
});

test('admin dan hr bisa membuka halaman jobdesk', function (int $otoritas) {
    $user = User::factory()->otoritas($otoritas)->create();

    $this->actingAs($user)->get('/jobdesk')->assertOk()->assertSee('Tambah Jobdesk')->assertSee('Operator');
})->with([1, 2]);

test('admin bisa menambah, mengubah, dan menghapus jobdesk', function () {
    $this->actingAs($this->admin)->postJson('/jobdesk', [
        'jabatan_id' => $this->jabatan->id,
        'deskripsi' => 'Menjalankan mesin',
    ])->assertOk()->assertJson(['message' => 'Jobdesk berhasil ditambahkan.']);

    $jobdesk = Jobdesk::firstOrFail();

    $this->actingAs($this->admin)->putJson("/jobdesk/{$jobdesk->id}", [
        'jabatan_id' => $this->jabatan->id,
        'deskripsi' => 'Merawat mesin',
    ])->assertOk()->assertJson(['message' => 'Jobdesk berhasil diperbarui.']);
    expect($jobdesk->fresh()->deskripsi)->toBe('Merawat mesin');

    $this->actingAs($this->admin)->deleteJson("/jobdesk/{$jobdesk->id}")
        ->assertOk()->assertJson(['message' => 'Jobdesk berhasil dihapus.']);
    $this->assertDatabaseMissing('jobdesk', ['id' => $jobdesk->id]);
});

test('validasi jobdesk', function () {
    $this->actingAs($this->admin)->postJson('/jobdesk', ['jabatan_id' => 999])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['jabatan_id', 'deskripsi']);
});

test('data jobdesk dikelompokkan per jabatan dan bisa difilter', function () {
    $lain = Jabatan::create(['nama' => 'Kasir', 'divisi_id' => $this->jabatan->divisi_id]);
    Jobdesk::create(['jabatan_id' => $this->jabatan->id, 'deskripsi' => 'A']);
    Jobdesk::create(['jabatan_id' => $lain->id, 'deskripsi' => 'B1']);
    Jobdesk::create(['jabatan_id' => $lain->id, 'deskripsi' => 'Hitung kas']);
    User::factory()->create(['jabatan_id' => $lain->id]);
    User::factory()->nonaktif()->create(['jabatan_id' => $lain->id]); // nonaktif tidak dihitung

    $this->actingAs($this->admin)->getJson("/jobdesk/data?jabatan_id={$lain->id}")
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.nama', 'Kasir')
        ->assertJsonPath('data.0.jobdesk.0.deskripsi', 'B1')
        ->assertJsonPath('data.0.jobdesk.1.deskripsi', 'Hitung kas')
        ->assertJsonPath('data.0.pegawai_count', 1);

    // cari berdasarkan isi tugas → jabatan pemiliknya
    $this->actingAs($this->admin)->getJson('/jobdesk/data?search=kas')
        ->assertJsonPath('total', 1)->assertJsonPath('data.0.nama', 'Kasir');
    // jabatan tanpa jobdesk tetap tampil (supaya bisa ditambah dari kartunya)
    $this->actingAs($this->admin)->getJson('/jobdesk/data')->assertJsonPath('total', 2);
});

test('jobdesk ikut terhapus saat jabatan dihapus', function () {
    Jobdesk::create(['jabatan_id' => $this->jabatan->id, 'deskripsi' => 'A']);

    $this->actingAs($this->admin)->deleteJson("/jabatan/{$this->jabatan->id}")->assertOk();

    expect(Jobdesk::count())->toBe(0);
});

test('karyawan tidak bisa akses jobdesk', function () {
    $karyawan = User::factory()->otoritas(3)->create();
    $jobdesk = Jobdesk::create(['jabatan_id' => $this->jabatan->id, 'deskripsi' => 'A']);
    $payload = ['jabatan_id' => $this->jabatan->id, 'deskripsi' => 'B'];

    $this->actingAs($karyawan)->get('/jobdesk')->assertForbidden();
    $this->actingAs($karyawan)->getJson('/jobdesk/data')->assertForbidden();
    $this->actingAs($karyawan)->postJson('/jobdesk', $payload)->assertForbidden();
    $this->actingAs($karyawan)->putJson("/jobdesk/{$jobdesk->id}", $payload)->assertForbidden();
    $this->actingAs($karyawan)->deleteJson("/jobdesk/{$jobdesk->id}")->assertForbidden();
});
