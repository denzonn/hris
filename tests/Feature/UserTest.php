<?php

use App\Models\Cabang;
use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = User::factory()->otoritas(1)->create();
    $this->hr = User::factory()->otoritas(2)->create();
    $this->cabang = Cabang::create(['nama' => 'Makassar', 'radius_meter' => 100]);
    $this->divisi = Divisi::create(['nama' => 'Produksi']);
    $this->jabatan = Jabatan::create(['nama' => 'Operator', 'divisi_id' => $this->divisi->id]);
});

function pegawaiPayload(array $override = []): array
{
    return array_merge([
        'name' => 'Budi',
        'email' => 'budi@hris.test',
        'password' => 'rahasia123',
        'otoritas_id' => 3,
        'nik' => 'P001',
        'status_kerja' => 'kontrak',
        'tanggal_masuk' => '2026-01-05',
        'tanggal_akhir_kontrak' => '2026-12-31',
        'gaji_pokok' => 4500000,
        'status_ptkp' => 'K/1',
    ], $override);
}

test('admin dan hr bisa membuka halaman pegawai', function (string $role) {
    $this->actingAs($this->{$role})->get('/user')->assertOk()->assertSee('Tambah Pegawai');
})->with(['admin', 'hr']);

test('admin bisa menambah pegawai lengkap dengan foto', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)->post('/user', pegawaiPayload([
        'cabang_id' => $this->cabang->id,
        'divisi_id' => $this->divisi->id,
        'jabatan_id' => $this->jabatan->id,
        'foto' => UploadedFile::fake()->image('budi.jpg'),
    ]), ['Accept' => 'application/json'])->assertOk()->assertJson(['message' => 'Pegawai berhasil ditambahkan.']);

    $user = User::where('email', 'budi@hris.test')->firstOrFail();
    expect($user)
        ->jabatan_id->toBe($this->jabatan->id)
        ->gaji_pokok->toBe('4500000.00')
        ->jatah_cuti->toBe(12)
        ->aktif->toBeTrue();
    expect(Hash::check('rahasia123', $user->password))->toBeTrue();
    Storage::disk('public')->assertExists($user->foto);
});

test('validasi pegawai: password wajib saat tambah, nik & email unik, jabatan harus sesuai divisi', function () {
    User::factory()->create(['email' => 'budi@hris.test', 'nik' => 'P001']);
    $lain = Divisi::create(['nama' => 'Keuangan']);

    $this->actingAs($this->admin)->postJson('/user', pegawaiPayload([
        'password' => '',
        'divisi_id' => $lain->id,
        'jabatan_id' => $this->jabatan->id,
        'status_kerja' => 'harian',
    ]))->assertUnprocessable()
        ->assertJsonValidationErrors(['password', 'email', 'nik', 'jabatan_id', 'status_kerja']);
});

test('password opsional saat update dan foto lama dihapus saat diganti', function () {
    Storage::fake('public');
    $lama = UploadedFile::fake()->image('lama.jpg')->store('pegawai', 'public');
    $user = User::factory()->create(['foto' => $lama]);
    $hashLama = $user->password;

    $this->actingAs($this->admin)->post("/user/{$user->id}", pegawaiPayload([
        '_method' => 'PUT',
        'email' => $user->email,
        'password' => '',
        'name' => 'Nama Baru',
        'foto' => UploadedFile::fake()->image('baru.jpg'),
    ]), ['Accept' => 'application/json'])->assertOk()->assertJson(['message' => 'Pegawai berhasil diperbarui.']);

    $user->refresh();
    expect($user->name)->toBe('Nama Baru')->and($user->password)->toBe($hashLama);
    Storage::disk('public')->assertMissing($lama);
    Storage::disk('public')->assertExists($user->foto);
});

test('data pegawai bisa difilter search, cabang, divisi, dan status aktif', function () {
    User::factory()->create(['name' => 'Andi', 'nik' => 'X-77', 'cabang_id' => $this->cabang->id, 'divisi_id' => $this->divisi->id]);
    User::factory()->nonaktif()->create(['name' => 'Cici', 'cabang_id' => $this->cabang->id]);

    $this->actingAs($this->admin)->getJson('/user/data?search=x-77')
        ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.name', 'Andi');
    $this->actingAs($this->admin)->getJson("/user/data?cabang_id={$this->cabang->id}")
        ->assertJsonPath('total', 2);
    $this->actingAs($this->admin)->getJson("/user/data?divisi_id={$this->divisi->id}")
        ->assertJsonPath('total', 1);
    $this->actingAs($this->admin)->getJson('/user/data?aktif=0')
        ->assertJsonPath('total', 1)->assertJsonPath('data.0.name', 'Cici');
});

test('admin bisa menonaktifkan dan mengaktifkan pegawai', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)->patchJson("/user/{$user->id}/aktif")
        ->assertOk()->assertJson(['message' => 'Pegawai berhasil dinonaktifkan.']);
    expect($user->fresh()->aktif)->toBeFalse();

    $this->actingAs($this->admin)->patchJson("/user/{$user->id}/aktif")
        ->assertOk()->assertJson(['message' => 'Pegawai berhasil diaktifkan.']);
    expect($user->fresh()->aktif)->toBeTrue();
});

test('admin tidak bisa menonaktifkan dirinya sendiri', function () {
    $this->actingAs($this->admin)->patchJson("/user/{$this->admin->id}/aktif")
        ->assertUnprocessable()->assertJson(['message' => 'Anda tidak bisa menonaktifkan akun sendiri.']);

    expect($this->admin->fresh()->aktif)->toBeTrue();
});

test('pegawai tidak bisa di-hard delete', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)->deleteJson("/user/{$user->id}")->assertMethodNotAllowed();
    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

test('hr tidak bisa membuat atau mengubah user menjadi super admin', function () {
    $user = User::factory()->create();

    $this->actingAs($this->hr)->postJson('/user', pegawaiPayload(['otoritas_id' => 1]))
        ->assertUnprocessable()->assertJsonValidationErrors(['otoritas_id']);
    $this->actingAs($this->hr)->putJson("/user/{$user->id}", pegawaiPayload(['email' => $user->email, 'otoritas_id' => 1]))
        ->assertUnprocessable()->assertJsonValidationErrors(['otoritas_id']);

    expect($user->fresh()->otoritas_id)->toBe(3);

    // HR tetap bisa membuat HR / karyawan
    $this->actingAs($this->hr)->postJson('/user', pegawaiPayload(['otoritas_id' => 2]))->assertOk();
});

test('hr tidak bisa mengubah atau menonaktifkan akun super admin', function () {
    $this->actingAs($this->hr)->putJson("/user/{$this->admin->id}", pegawaiPayload(['email' => $this->admin->email, 'otoritas_id' => 3]))
        ->assertForbidden();
    $this->actingAs($this->hr)->patchJson("/user/{$this->admin->id}/aktif")->assertForbidden();

    expect($this->admin->fresh())->otoritas_id->toBe(1)->aktif->toBeTrue();
});

test('master yang masih dipakai pegawai tidak bisa dihapus', function () {
    User::factory()->create([
        'cabang_id' => $this->cabang->id,
        'divisi_id' => $this->divisi->id,
        'jabatan_id' => $this->jabatan->id,
    ]);
    $divisiTanpaJabatan = Divisi::create(['nama' => 'Gudang']);
    User::factory()->create(['divisi_id' => $divisiTanpaJabatan->id]);

    $this->actingAs($this->admin)->deleteJson("/cabang/{$this->cabang->id}")
        ->assertUnprocessable()->assertJson(['message' => 'Cabang tidak bisa dihapus karena masih dipakai pegawai.']);
    $this->actingAs($this->admin)->deleteJson("/jabatan/{$this->jabatan->id}")
        ->assertUnprocessable()->assertJson(['message' => 'Jabatan tidak bisa dihapus karena masih dipakai pegawai.']);
    $this->actingAs($this->admin)->deleteJson("/divisi/{$divisiTanpaJabatan->id}")
        ->assertUnprocessable()->assertJson(['message' => 'Divisi tidak bisa dihapus karena masih dipakai pegawai.']);
});

test('karyawan tidak bisa akses pegawai', function () {
    $karyawan = User::factory()->otoritas(3)->create();

    $this->actingAs($karyawan)->get('/user')->assertForbidden();
    $this->actingAs($karyawan)->getJson('/user/data')->assertForbidden();
    $this->actingAs($karyawan)->postJson('/user', pegawaiPayload())->assertForbidden();
    $this->actingAs($karyawan)->putJson("/user/{$karyawan->id}", pegawaiPayload())->assertForbidden();
    $this->actingAs($karyawan)->patchJson("/user/{$this->hr->id}/aktif")->assertForbidden();
});
