<?php

use App\Models\Absensi;
use App\Models\Cabang;
use App\Models\HariLibur;
use App\Models\Lembur;
use App\Models\PengajuanIzin;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-19 10:00'));
    $this->hr = User::factory()->otoritas(2)->create();
    $cabang = Cabang::create(['nama' => 'Pusat', 'radius_meter' => 100]);
    $cabang->pengaturanAbsensi()->create([
        'jam_masuk' => '08:00', 'jam_pulang' => '17:00', 'hari_kerja' => [1, 2, 3, 4, 5, 6],
    ]);
    $this->karyawan = User::factory()->create(['cabang_id' => $cabang->id, 'jatah_cuti' => 12]);
});

function izinPayload(array $override = []): array
{
    // Sab 21 – Sel 24 Nov 2026: Minggu 22 bukan hari kerja → 3 hari efektif (21, 23, 24)
    return array_merge([
        'jenis' => 'cuti', 'tanggal_mulai' => '2026-11-21', 'tanggal_selesai' => '2026-11-24', 'alasan' => 'Acara keluarga',
    ], $override);
}

function buatIzin(User $user, array $override = []): PengajuanIzin
{
    return PengajuanIzin::create(izinPayload($override) + ['user_id' => $user->id]);
}

test('semua otoritas bisa membuka halaman izin', function (int $otoritas) {
    $this->actingAs(User::factory()->otoritas($otoritas)->create())->get('/izin_karyawan')->assertOk();
})->with([1, 2, 3]);

test('karyawan mengajukan untuk dirinya sendiri dan berstatus pending', function () {
    $lain = User::factory()->create();

    $this->actingAs($this->karyawan)->postJson('/izin_karyawan', izinPayload(['user_id' => $lain->id]))
        ->assertOk()->assertJson(['message' => 'Pengajuan berhasil dikirim.']);

    $this->assertDatabaseHas('pengajuan_izin', ['user_id' => $this->karyawan->id, 'status' => 'pending']);
    $this->assertDatabaseCount('absensi', 0); // belum disetujui → absensi belum terisi
});

test('disetujui mengisi absensi hanya di hari kerja dan mengurangi sisa cuti', function () {
    HariLibur::create(['tanggal' => '2026-11-23', 'keterangan' => 'Libur']);
    $izin = buatIzin($this->karyawan); // efektif: 21 & 24 (22 Minggu, 23 libur)

    $this->actingAs($this->hr)->patchJson("/izin_karyawan/{$izin->id}/status", ['status' => 'disetujui'])
        ->assertOk()->assertJson(['message' => 'Pengajuan berhasil disetujui.']);

    expect(Absensi::orderBy('tanggal')->pluck('tanggal')->map->toDateString()->all())->toBe(['2026-11-21', '2026-11-24'])
        ->and(Absensi::pluck('status')->unique()->all())->toBe(['cuti'])
        ->and($izin->fresh())->status->toBe('disetujui')->disetujui_oleh->toBe($this->hr->id)
        ->and($this->karyawan->sisaCuti())->toBe(10); // 12 − 2
});

test('persetujuan menimpa alpha tetapi tidak menimpa absen hadir', function () {
    Absensi::create(['user_id' => $this->karyawan->id, 'tanggal' => '2026-11-21', 'status' => 'alpha']);
    Absensi::create(['user_id' => $this->karyawan->id, 'tanggal' => '2026-11-23', 'status' => 'hadir', 'jam_masuk' => '08:00']);
    $izin = buatIzin($this->karyawan, ['jenis' => 'sakit']);

    $this->actingAs($this->hr)->patchJson("/izin_karyawan/{$izin->id}/status", ['status' => 'disetujui'])->assertOk();

    expect(Absensi::where('tanggal', '2026-11-21')->value('status'))->toBe('sakit')
        ->and(Absensi::where('tanggal', '2026-11-23')->value('status'))->toBe('hadir')
        ->and(Absensi::where('tanggal', '2026-11-24')->value('status'))->toBe('sakit');
});

test('pengajuan dari hr langsung disetujui', function () {
    $this->actingAs($this->hr)->postJson('/izin_karyawan', izinPayload(['jenis' => 'izin', 'user_id' => $this->karyawan->id]))
        ->assertOk()->assertJson(['message' => 'Pengajuan berhasil ditambahkan dan disetujui.']);

    expect(PengajuanIzin::first()->status)->toBe('disetujui')
        ->and(Absensi::where('status', 'izin')->count())->toBe(3);
});

test('cuti ditolak bila sisa cuti tidak cukup', function () {
    $this->karyawan->update(['jatah_cuti' => 2]);

    $this->actingAs($this->karyawan)->postJson('/izin_karyawan', izinPayload())
        ->assertUnprocessable()->assertJson(['message' => 'Sisa cuti tidak cukup (butuh 3 hari, sisa 2 hari).']);

    // izin/sakit tidak memotong saldo
    $this->actingAs($this->karyawan)->postJson('/izin_karyawan', izinPayload(['jenis' => 'sakit']))->assertOk();
});

test('pengajuan bentrok dengan pengajuan lain ditolak, yang ditolak tidak dihitung', function () {
    buatIzin($this->karyawan, ['jenis' => 'izin', 'tanggal_mulai' => '2026-11-24', 'tanggal_selesai' => '2026-11-25']);

    $this->actingAs($this->karyawan)->postJson('/izin_karyawan', izinPayload())
        ->assertUnprocessable()->assertJson(['message' => 'Sudah ada pengajuan lain di rentang tanggal ini.']);

    PengajuanIzin::query()->update(['status' => 'ditolak']);
    $this->actingAs($this->karyawan)->postJson('/izin_karyawan', izinPayload())->assertOk();
});

test('rentang tanpa hari kerja dan tanggal terbalik ditolak', function () {
    $this->actingAs($this->karyawan)->postJson('/izin_karyawan', izinPayload(['tanggal_mulai' => '2026-11-22', 'tanggal_selesai' => '2026-11-22']))
        ->assertUnprocessable()->assertJson(['message' => 'Rentang tanggal tidak berisi hari kerja.']);

    $this->actingAs($this->karyawan)->postJson('/izin_karyawan', izinPayload(['tanggal_selesai' => '2026-11-20']))
        ->assertUnprocessable()->assertJsonValidationErrors(['tanggal_selesai']);
});

test('lampiran disimpan private dan hanya bisa diunduh pemilik atau hr', function () {
    Storage::fake('local');

    $this->actingAs($this->karyawan)->post('/izin_karyawan', izinPayload(['jenis' => 'sakit', 'lampiran' => UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf')]), ['Accept' => 'application/json'])
        ->assertOk();
    $izin = PengajuanIzin::first();
    Storage::disk('local')->assertExists($izin->lampiran);

    $this->actingAs($this->karyawan)->getJson('/izin_karyawan/data')
        ->assertJsonPath('data.0.ada_lampiran', true)->assertJsonMissingPath('data.0.lampiran');
    $this->actingAs($this->karyawan)->get("/izin_karyawan/{$izin->id}/lampiran")->assertOk();
    $this->actingAs($this->hr)->get("/izin_karyawan/{$izin->id}/lampiran")->assertOk();
    $this->actingAs(User::factory()->create())->get("/izin_karyawan/{$izin->id}/lampiran")->assertForbidden();
});

test('karyawan hanya bisa ubah/hapus pengajuan pending miliknya', function () {
    $milikSendiri = buatIzin($this->karyawan);
    $milikLain = buatIzin(User::factory()->create());
    $disetujui = buatIzin($this->karyawan, ['tanggal_mulai' => '2026-12-01', 'tanggal_selesai' => '2026-12-01', 'status' => 'disetujui']);

    $this->actingAs($this->karyawan)->putJson("/izin_karyawan/{$milikSendiri->id}", izinPayload(['alasan' => 'Ubah']))->assertOk();
    expect($milikSendiri->fresh()->alasan)->toBe('Ubah');

    $this->actingAs($this->karyawan)->putJson("/izin_karyawan/{$milikLain->id}", izinPayload())->assertForbidden();
    $this->actingAs($this->karyawan)->deleteJson("/izin_karyawan/{$disetujui->id}")->assertUnprocessable();
    $this->actingAs($this->karyawan)->deleteJson("/izin_karyawan/{$milikSendiri->id}")->assertOk();
});

test('pengajuan hanya bisa diproses sekali dan karyawan tidak bisa memproses', function () {
    $izin = buatIzin($this->karyawan);

    $this->actingAs($this->karyawan)->patchJson("/izin_karyawan/{$izin->id}/status", ['status' => 'disetujui'])->assertForbidden();
    $this->actingAs($this->hr)->patchJson("/izin_karyawan/{$izin->id}/status", ['status' => 'ditolak'])->assertOk();
    $this->actingAs($this->hr)->patchJson("/izin_karyawan/{$izin->id}/status", ['status' => 'disetujui'])
        ->assertUnprocessable()->assertJson(['message' => 'Pengajuan ini sudah diproses.']);

    $this->assertDatabaseCount('absensi', 0);
});

test('karyawan hanya melihat pengajuan miliknya', function () {
    buatIzin($this->karyawan);
    buatIzin(User::factory()->create());

    $this->actingAs($this->karyawan)->getJson('/izin_karyawan/data')->assertJsonPath('total', 1);
    $this->actingAs($this->hr)->getJson('/izin_karyawan/data')->assertJsonPath('total', 2);
});

test('kpi persetujuan menghitung antrean, izin bulan ini, jam lembur & yang disetujui', function () {
    buatIzin($this->karyawan);                                   // pending, 21 Nov
    buatIzin($this->karyawan, ['tanggal_mulai' => '2026-11-02', 'tanggal_selesai' => '2026-11-02', 'status' => 'disetujui']);
    buatIzin($this->karyawan, ['tanggal_mulai' => '2026-10-01', 'tanggal_selesai' => '2026-10-01']); // bulan lalu, pending
    $lembur = fn ($tgl, $mulai, $selesai, $status) => Lembur::create([
        'user_id' => $this->karyawan->id, 'tanggal' => $tgl, 'jam_mulai' => $mulai, 'jam_selesai' => $selesai, 'status' => $status,
    ]);
    $lembur('2026-11-10', '17:00', '20:00', 'disetujui'); // 3 jam
    $lembur('2026-11-11', '17:00', '18:30', 'disetujui'); // 1,5 jam
    $lembur('2026-11-12', '17:00', '19:00', 'pending');
    $lembur('2026-10-30', '17:00', '19:00', 'disetujui'); // bulan lalu

    $kpi = $this->actingAs($this->hr)->get('/izin_karyawan')->assertOk()->viewData('kpi');

    expect($kpi)->toBe([
        'pending_izin' => 2,
        'pending_lembur' => 1,
        'izin_bulan_ini' => 2,          // 21 Nov & 2 Nov
        'jam_lembur' => 4.5,            // 3 + 1,5 (November, disetujui)
        'disetujui_bulan_ini' => 3,     // 1 izin + 2 lembur
    ]);
    expect($this->actingAs($this->karyawan)->get('/izin_karyawan')->viewData('kpi'))->toBeNull();
});
