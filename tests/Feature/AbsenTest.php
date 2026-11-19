<?php

use App\Models\Absensi;
use App\Models\Cabang;
use App\Models\HariLibur;
use App\Models\PengaturanAbsensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

const LAT = -5.1476651;
const LNG = 119.4327314;

beforeEach(function () {
    $this->cabang = Cabang::create(['nama' => 'Pusat', 'latitude' => LAT, 'longitude' => LNG, 'radius_meter' => 100]);
    $this->pengaturan = $this->cabang->pengaturanAbsensi()->create([
        'jam_masuk' => '08:00', 'jam_pulang' => '17:00', 'toleransi_telat_menit' => 15,
        'hari_kerja' => [1, 2, 3, 4, 5, 6], 'wajib_foto' => false, 'wajib_lokasi' => true,
    ]);
    $this->karyawan = User::factory()->create(['cabang_id' => $this->cabang->id]);
    $this->travelTo(Carbon::parse('2026-11-16 07:58:00')); // Senin
});

$lokasi = ['latitude' => LAT, 'longitude' => LNG];

test('menit telat dihitung dari jam masuk jadwal, toleransi hanya batas', function () {
    $p = new PengaturanAbsensi(['jam_masuk' => '08:00:00', 'toleransi_telat_menit' => 15]);

    expect($p->menitTelat('07:55:00'))->toBe(0)
        ->and($p->menitTelat('08:15:59'))->toBe(0)   // 15 menit = masih toleransi
        ->and($p->menitTelat('08:16:00'))->toBe(16)  // lewat toleransi → dihitung dari 08:00
        ->and($p->menitTelat('09:30:00'))->toBe(90);
});

test('jarak haversine: geser 0,001 derajat lintang = ±111 meter', function () {
    expect($this->cabang->jarakKe(LAT, LNG))->toBe(0.0)
        ->and(round($this->cabang->jarakKe(LAT + 0.001, LNG)))->toBe(111.0)  // 6371000 × 0,001 × π/180 = 111,19
        ->and(round($this->cabang->jarakKe(LAT + 0.01, LNG)))->toBe(1112.0);
});

test('tanggal kerja melewati hari minggu dan hari libur', function () {
    HariLibur::create(['tanggal' => '2026-11-18', 'keterangan' => 'Libur']);

    expect($this->pengaturan->tanggalKerja(Carbon::parse('2026-11-16'), Carbon::parse('2026-11-22')))
        ->toBe(['2026-11-16', '2026-11-17', '2026-11-19', '2026-11-20', '2026-11-21']);
});

test('absen masuk tepat waktu tercatat hadir', function () use ($lokasi) {
    $this->actingAs($this->karyawan)->get('/absen')->assertOk()->assertSee('Absen Masuk');

    $this->actingAs($this->karyawan)->postJson('/absen/masuk', $lokasi)
        ->assertOk()->assertJson(['message' => 'Absen masuk 07:58 tercatat.']);

    $this->assertDatabaseHas('absensi', [
        'user_id' => $this->karyawan->id, 'tanggal' => '2026-11-16', 'jam_masuk' => '07:58:00', 'status' => 'hadir', 'menit_telat' => 0,
    ]);
});

test('absen masuk lewat toleransi tercatat telat dengan menit dari jam jadwal', function () use ($lokasi) {
    $this->travelTo(Carbon::parse('2026-11-16 08:20:30'));

    $this->actingAs($this->karyawan)->postJson('/absen/masuk', $lokasi)
        ->assertOk()->assertJson(['message' => 'Absen masuk 08:20 tercatat, telat 20 menit.']);

    expect(Absensi::first())->status->toBe('telat')->menit_telat->toBe(20);
});

test('absen ditolak di luar radius cabang', function () {
    // 0,001 derajat ≈ 111 m > radius 100 m
    $this->actingAs($this->karyawan)->postJson('/absen/masuk', ['latitude' => LAT + 0.001, 'longitude' => LNG])
        ->assertUnprocessable()->assertJson(['message' => 'Anda di luar area cabang (jarak 111 m, maksimal 100 m).']);

    $this->assertDatabaseCount('absensi', 0);
});

test('lokasi wajib dikirim bila wajib_lokasi aktif', function () {
    $this->actingAs($this->karyawan)->postJson('/absen/masuk', [])
        ->assertUnprocessable()->assertJsonValidationErrors(['latitude', 'longitude']);
});

test('foto wajib bila wajib_foto aktif dan disimpan', function () use ($lokasi) {
    Storage::fake('public');
    $this->pengaturan->update(['wajib_foto' => true]);

    $this->actingAs($this->karyawan)->postJson('/absen/masuk', $lokasi)
        ->assertUnprocessable()->assertJsonValidationErrors(['foto']);

    $this->actingAs($this->karyawan)->post('/absen/masuk', $lokasi + ['foto' => UploadedFile::fake()->image('selfie.jpg')], ['Accept' => 'application/json'])
        ->assertOk();

    Storage::disk('public')->assertExists(Absensi::first()->foto_masuk);
});

test('absen ditolak di hari minggu dan hari libur', function () use ($lokasi) {
    $this->travelTo(Carbon::parse('2026-11-22 08:00')); // Minggu
    $this->actingAs($this->karyawan)->postJson('/absen/masuk', $lokasi)->assertUnprocessable();

    HariLibur::create(['tanggal' => '2026-11-23', 'keterangan' => 'Libur']);
    $this->travelTo(Carbon::parse('2026-11-23 08:00')); // Senin libur
    $this->actingAs($this->karyawan)->postJson('/absen/masuk', $lokasi)->assertUnprocessable();

    $this->assertDatabaseCount('absensi', 0);
});

test('absen masuk hanya sekali per hari', function () use ($lokasi) {
    $this->actingAs($this->karyawan)->postJson('/absen/masuk', $lokasi)->assertOk();
    $this->actingAs($this->karyawan)->postJson('/absen/masuk', $lokasi)
        ->assertUnprocessable()->assertJson(['message' => 'Anda sudah absen masuk hari ini.']);

    $this->assertDatabaseCount('absensi', 1);
});

test('absen masuk ditolak bila hari ini sudah tercatat izin', function () use ($lokasi) {
    Absensi::create(['user_id' => $this->karyawan->id, 'tanggal' => '2026-11-16', 'status' => 'izin']);

    $this->actingAs($this->karyawan)->postJson('/absen/masuk', $lokasi)
        ->assertUnprocessable()->assertJson(['message' => 'Hari ini Anda tercatat izin.']);
});

test('absen pulang butuh absen masuk dan hanya sekali', function () use ($lokasi) {
    $this->actingAs($this->karyawan)->postJson('/absen/pulang', $lokasi)
        ->assertUnprocessable()->assertJson(['message' => 'Anda belum absen masuk hari ini.']);

    $this->actingAs($this->karyawan)->postJson('/absen/masuk', $lokasi)->assertOk();
    $this->travelTo(Carbon::parse('2026-11-16 17:05:00'));
    $this->actingAs($this->karyawan)->postJson('/absen/pulang', $lokasi)
        ->assertOk()->assertJson(['message' => 'Absen pulang 17:05 tercatat.']);
    $this->actingAs($this->karyawan)->postJson('/absen/pulang', $lokasi)->assertUnprocessable();

    expect(Absensi::first()->jam_pulang)->toBe('17:05:00');
});

test('absen ditolak bila cabang belum punya pengaturan', function () use ($lokasi) {
    $this->pengaturan->delete();

    $this->actingAs($this->karyawan)->get('/absen')->assertOk()->assertSee('belum dibuat');
    $this->actingAs($this->karyawan)->postJson('/absen/masuk', $lokasi)
        ->assertUnprocessable()->assertJson(['message' => 'Pengaturan absensi cabang Anda belum dibuat. Hubungi HR.']);
});

test('riwayat hanya menampilkan absensi milik sendiri', function () {
    Absensi::create(['user_id' => $this->karyawan->id, 'tanggal' => '2026-11-13', 'status' => 'hadir']);
    Absensi::create(['user_id' => User::factory()->create()->id, 'tanggal' => '2026-11-13', 'status' => 'hadir']);

    $this->actingAs($this->karyawan)->getJson('/absen/data?bulan=2026-11')
        ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.user_id', $this->karyawan->id);
});
