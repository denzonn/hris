<?php

use App\Models\Absensi;
use App\Models\Cabang;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-19 10:00'));
    $this->hr = User::factory()->otoritas(2)->create();
    $cabang = Cabang::create(['nama' => 'Pusat', 'radius_meter' => 100]);
    $cabang->pengaturanAbsensi()->create([
        'jam_masuk' => '08:00', 'jam_pulang' => '17:00', 'toleransi_telat_menit' => 10, 'hari_kerja' => [1, 2, 3, 4, 5, 6],
    ]);
    $this->karyawan = User::factory()->create(['cabang_id' => $cabang->id]);
});

test('admin dan hr bisa membuka halaman input absensi', function (int $otoritas) {
    $this->actingAs(User::factory()->otoritas($otoritas)->create())->get('/absensi_karyawan')->assertOk()->assertSee('Tambah Absensi');
})->with([1, 2]);

test('hr input hadir: telat dihitung otomatis dari pengaturan cabang', function () {
    // jadwal 08:00, toleransi 10 → masuk 08:25 = telat 25 menit
    $this->actingAs($this->hr)->postJson('/absensi_karyawan', [
        'user_id' => $this->karyawan->id, 'tanggal' => '2026-11-18', 'status' => 'hadir',
        'jam_masuk' => '08:25', 'jam_pulang' => '17:00', 'keterangan' => 'Lupa absen',
    ])->assertOk()->assertJson(['message' => 'Absensi berhasil ditambahkan.']);

    expect(Absensi::first())
        ->status->toBe('telat')->menit_telat->toBe(25)->diubah_oleh->toBe($this->hr->id);
});

test('hr input hadir dalam toleransi tercatat hadir', function () {
    $this->actingAs($this->hr)->postJson('/absensi_karyawan', [
        'user_id' => $this->karyawan->id, 'tanggal' => '2026-11-18', 'status' => 'hadir', 'jam_masuk' => '08:10',
    ])->assertOk();

    expect(Absensi::first())->status->toBe('hadir')->menit_telat->toBe(0);
});

test('status selain hadir mengosongkan jam dan menit telat', function () {
    $this->actingAs($this->hr)->postJson('/absensi_karyawan', [
        'user_id' => $this->karyawan->id, 'tanggal' => '2026-11-18', 'status' => 'sakit', 'jam_masuk' => '09:00',
    ])->assertOk();

    expect(Absensi::first())->status->toBe('sakit')->jam_masuk->toBeNull()->menit_telat->toBe(0);
});

test('validasi input absensi: unik per pegawai per tanggal, jam wajib untuk hadir, tidak boleh tanggal depan', function () {
    Absensi::create(['user_id' => $this->karyawan->id, 'tanggal' => '2026-11-18', 'status' => 'hadir']);

    $this->actingAs($this->hr)->postJson('/absensi_karyawan', [
        'user_id' => $this->karyawan->id, 'tanggal' => '2026-11-18', 'status' => 'hadir',
    ])->assertUnprocessable()->assertJsonValidationErrors(['tanggal', 'jam_masuk']);

    $this->actingAs($this->hr)->postJson('/absensi_karyawan', [
        'user_id' => $this->karyawan->id, 'tanggal' => '2026-11-20', 'status' => 'izin',
    ])->assertUnprocessable()->assertJsonValidationErrors(['tanggal']);

    // pegawai lain di tanggal sama boleh
    $this->actingAs($this->hr)->postJson('/absensi_karyawan', [
        'user_id' => User::factory()->create()->id, 'tanggal' => '2026-11-18', 'status' => 'izin',
    ])->assertOk();
});

test('hr bisa koreksi lalu hapus absensi', function () {
    $absensi = Absensi::create(['user_id' => $this->karyawan->id, 'tanggal' => '2026-11-18', 'status' => 'alpha']);

    $this->actingAs($this->hr)->putJson("/absensi_karyawan/{$absensi->id}", [
        'user_id' => $this->karyawan->id, 'tanggal' => '2026-11-18', 'status' => 'hadir', 'jam_masuk' => '07:55',
    ])->assertOk()->assertJson(['message' => 'Absensi berhasil diperbarui.']);
    expect($absensi->fresh())->status->toBe('hadir')->diubah_oleh->toBe($this->hr->id);

    $this->actingAs($this->hr)->deleteJson("/absensi_karyawan/{$absensi->id}")->assertOk();
    $this->assertDatabaseCount('absensi', 0);
});

test('data absensi bisa difilter tanggal dan status', function () {
    Absensi::create(['user_id' => $this->karyawan->id, 'tanggal' => '2026-11-17', 'status' => 'hadir']);
    Absensi::create(['user_id' => $this->karyawan->id, 'tanggal' => '2026-11-18', 'status' => 'alpha']);

    $this->actingAs($this->hr)->getJson('/absensi_karyawan/data?tanggal_mulai=2026-11-18&tanggal_selesai=2026-11-18')
        ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.status', 'alpha');
    $this->actingAs($this->hr)->getJson('/absensi_karyawan/data?status=hadir')
        ->assertJsonPath('total', 1)->assertJsonPath('data.0.tanggal', '2026-11-17');
});

test('karyawan tidak bisa akses input absensi', function () {
    $absensi = Absensi::create(['user_id' => $this->karyawan->id, 'tanggal' => '2026-11-18', 'status' => 'alpha']);

    $this->actingAs($this->karyawan)->get('/absensi_karyawan')->assertForbidden();
    $this->actingAs($this->karyawan)->getJson('/absensi_karyawan/data')->assertForbidden();
    $this->actingAs($this->karyawan)->postJson('/absensi_karyawan', [])->assertForbidden();
    $this->actingAs($this->karyawan)->putJson("/absensi_karyawan/{$absensi->id}", [])->assertForbidden();
    $this->actingAs($this->karyawan)->deleteJson("/absensi_karyawan/{$absensi->id}")->assertForbidden();
});
