<?php

use App\Models\Absensi;
use App\Models\Cabang;
use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\KomponenGaji;
use App\Models\Lembur;
use App\Models\Payroll;
use App\Models\PengajuanIzin;
use App\Models\PengaturanPayroll;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-26 09:00'));
    $this->hr = User::factory()->otoritas(2)->create();
    PengaturanPayroll::aktif()->update(['tanggal_cutoff' => 25, 'potongan_per_menit_telat' => 1000, 'potongan_per_alpha' => 100000]);

    $cabang = Cabang::create(['nama' => 'Pusat', 'radius_meter' => 100]);
    $cabang->pengaturanAbsensi()->create(['jam_masuk' => '08:00', 'jam_pulang' => '17:00', 'hari_kerja' => [1, 2, 3, 4, 5, 6]]);
    $jabatan = Jabatan::create(['nama' => 'Teknisi', 'divisi_id' => Divisi::create(['nama' => 'Ops'])->id, 'tunjangan_jabatan' => 300000]);

    $this->budi = User::factory()->create([
        'name' => 'Budi', 'nik' => 'P1', 'cabang_id' => $cabang->id, 'jabatan_id' => $jabatan->id, 'gaji_pokok' => 3000000,
    ]);
    $this->budi->komponenGaji()->attach([
        KomponenGaji::create(['nama' => 'Transport', 'tipe' => 'tunjangan', 'sifat' => 'tetap'])->id => ['nominal' => 160000],
        KomponenGaji::create(['nama' => 'Uang Makan', 'tipe' => 'tunjangan', 'sifat' => 'per_hari_hadir'])->id => ['nominal' => 25000],
    ]);

    // Periode 2026-11 (cut-off 25) = 26 Okt – 25 Nov
    $absen = fn ($tgl, $status, $telat = 0) => Absensi::create(['user_id' => $this->budi->id, 'tanggal' => $tgl, 'status' => $status, 'menit_telat' => $telat]);
    $absen('2026-10-25', 'alpha');        // sebelum periode
    $absen('2026-10-26', 'hadir');
    $absen('2026-10-27', 'telat', 30);
    $absen('2026-11-02', 'telat', 15);
    $absen('2026-11-03', 'alpha');
    $absen('2026-11-04', 'sakit');
    $absen('2026-11-26', 'alpha');        // sesudah periode
    Lembur::create(['user_id' => $this->budi->id, 'tanggal' => '2026-11-02', 'jam_mulai' => '17:00', 'jam_selesai' => '19:00', 'status' => 'disetujui']);
    Lembur::create(['user_id' => $this->budi->id, 'tanggal' => '2026-11-03', 'jam_mulai' => '17:00', 'jam_selesai' => '19:00', 'status' => 'pending']);
});

function buatPayroll($test): Payroll
{
    $test->actingAs($test->hr)->postJson('/payroll', ['periode' => '2026-11'])->assertOk();

    return Payroll::firstWhere('periode', '2026-11');
}

test('admin dan hr bisa membuka halaman payroll', function (int $otoritas) {
    $this->actingAs(User::factory()->otoritas($otoritas)->create())->get('/payroll')->assertOk()->assertSee('Buat Payroll');
})->with([1, 2]);

test('generate payroll menghitung dari absensi & lembur dalam periode cut-off', function () {
    $this->actingAs($this->hr)->postJson('/payroll', ['periode' => '2026-11'])
        ->assertOk()->assertJson(['message' => 'Payroll berhasil dibuat untuk 2 pegawai.']); // budi + hr

    $payroll = Payroll::first();
    expect($payroll)->status->toBe('draft')
        ->and($payroll->tanggal_mulai->toDateString())->toBe('2026-10-26')
        ->and($payroll->tanggal_selesai->toDateString())->toBe('2026-11-25');

    $d = $payroll->detail()->where('user_id', $this->budi->id)->first();
    // hari kerja 27 (26 Okt – 25 Nov = 31 hari − 4 Minggu: 1, 8, 15, 22 Nov)
    // hadir 3 (1 hadir + 2 telat), telat 45 menit, lembur disetujui 120 menit
    // alpha = 1 tercatat + 22 hari kerja tanpa catatan (27 − 5 hari yang tercatat) = 23
    // upah sebulan = 3.000.000 + 300.000 + 160.000 = 3.460.000 → sejam 20.000 → lembur 2 jam = 30.000 + 40.000
    // tunjangan = 160.000 + 25.000 × 3 = 235.000
    // bersih = 3.000.000 + 300.000 + 235.000 + 70.000 − 45.000 − 23 × 100.000 = 1.260.000
    expect($d->only(['nama', 'nik', 'jabatan', 'cabang', 'hari_hadir', 'menit_telat', 'jumlah_alpha', 'menit_lembur']))->toBe([
        'nama' => 'Budi', 'nik' => 'P1', 'jabatan' => 'Teknisi', 'cabang' => 'Pusat',
        'hari_hadir' => 3, 'menit_telat' => 45, 'jumlah_alpha' => 23, 'menit_lembur' => 120,
    ])->and($d->hari_kerja)->toBe(27)
        ->and($d->total_tunjangan)->toBe('235000.00')
        ->and($d->upah_lembur)->toBe('70000.00')
        ->and($d->potongan_telat)->toBe('45000.00')
        ->and($d->potongan_alpha)->toBe('2300000.00')
        ->and($d->gaji_bersih)->toBe('1260000.00');
});

test('super admin, pegawai nonaktif, dan pegawai yang masuk setelah periode tidak ikut payroll', function () {
    User::factory()->otoritas(1)->create();
    User::factory()->nonaktif()->create();
    User::factory()->create(['tanggal_masuk' => '2026-11-26']);

    expect(buatPayroll($this)->detail()->count())->toBe(2);
});

test('periode payroll unik', function () {
    buatPayroll($this);

    $this->actingAs($this->hr)->postJson('/payroll', ['periode' => '2026-11'])
        ->assertUnprocessable()->assertJsonValidationErrors(['periode']);
});

test('detail adalah snapshot: master berubah tidak mengubah payroll sampai di-generate ulang', function () {
    $payroll = buatPayroll($this);
    $this->budi->update(['gaji_pokok' => 5000000]);

    $detail = fn () => $payroll->detail()->where('user_id', $this->budi->id)->value('gaji_pokok');
    expect($detail())->toBe('3000000.00');

    $this->actingAs($this->hr)->postJson("/payroll/{$payroll->id}/generate")->assertOk();
    expect($detail())->toBe('5000000.00')
        ->and($payroll->detail()->count())->toBe(2); // tidak dobel
});

test('payroll final tidak bisa di-generate ulang, dihapus, atau difinalkan lagi', function () {
    $payroll = buatPayroll($this);

    $this->actingAs($this->hr)->patchJson("/payroll/{$payroll->id}/final")
        ->assertOk()->assertJson(['message' => 'Payroll 2026-11 sudah final dan dikunci.']);
    expect($payroll->fresh())->status->toBe('final')->difinalkan_oleh->toBe($this->hr->id);

    $this->actingAs($this->hr)->postJson("/payroll/{$payroll->id}/generate")->assertUnprocessable();
    $this->actingAs($this->hr)->deleteJson("/payroll/{$payroll->id}")->assertUnprocessable();
    $this->actingAs($this->hr)->patchJson("/payroll/{$payroll->id}/final")->assertUnprocessable();
    $this->assertDatabaseHas('payroll', ['id' => $payroll->id]);
});

test('payroll draft bisa dihapus beserta detailnya', function () {
    $payroll = buatPayroll($this);

    $this->actingAs($this->hr)->deleteJson("/payroll/{$payroll->id}")->assertOk();
    $this->assertDatabaseCount('payroll_detail', 0);
});

test('periode final mengunci absensi, lembur, izin, dan alpha otomatis di rentangnya', function () {
    $payroll = buatPayroll($this);
    $payroll->update(['status' => 'final']);
    $absensi = Absensi::where('tanggal', '2026-11-03')->first();
    $lembur = Lembur::where('status', 'pending')->first();
    $pesan = 'Tanggal ini masuk periode payroll yang sudah final, data tidak bisa diubah.';

    $this->actingAs($this->hr)->postJson('/absensi_karyawan', ['user_id' => $this->budi->id, 'tanggal' => '2026-11-05', 'status' => 'izin'])
        ->assertUnprocessable()->assertJson(['message' => $pesan]);
    $this->actingAs($this->hr)->putJson("/absensi_karyawan/{$absensi->id}", ['user_id' => $this->budi->id, 'tanggal' => '2026-11-03', 'status' => 'izin'])
        ->assertUnprocessable();
    $this->actingAs($this->hr)->deleteJson("/absensi_karyawan/{$absensi->id}")->assertUnprocessable();
    $this->actingAs($this->hr)->patchJson("/lembur/{$lembur->id}/status", ['status' => 'disetujui'])->assertUnprocessable();
    $this->actingAs($this->hr)->postJson('/lembur', ['user_id' => $this->budi->id, 'tanggal' => '2026-11-10', 'jam_mulai' => '17:00', 'jam_selesai' => '18:00'])
        ->assertUnprocessable();
    $this->actingAs($this->hr)->postJson('/izin_karyawan', [
        'user_id' => $this->budi->id, 'jenis' => 'izin', 'tanggal_mulai' => '2026-11-24', 'tanggal_selesai' => '2026-11-27', 'alasan' => 'x',
    ])->assertUnprocessable()->assertJson(['message' => $pesan]);
    $this->artisan('absensi:alpha', ['tanggal' => '2026-11-06'])->expectsOutput('Tanggal 2026-11-06 masuk payroll final, dilewati.');

    expect($absensi->fresh()->status)->toBe('alpha')
        ->and($lembur->fresh()->status)->toBe('pending')
        ->and(PengajuanIzin::count())->toBe(0);

    // di luar periode final tetap bisa
    $this->actingAs($this->hr)->postJson('/absensi_karyawan', ['user_id' => $this->budi->id, 'tanggal' => '2026-11-26', 'status' => 'izin'])
        ->assertUnprocessable()->assertJsonValidationErrors(['tanggal']); // 26 Nov sudah ada (alpha) → unik, bukan terkunci
});

test('slip gaji tampil dan detail harus milik payroll tersebut', function () {
    $payroll = buatPayroll($this);
    $d = $payroll->detail()->where('user_id', $this->budi->id)->first();

    $this->actingAs($this->hr)->get("/payroll/{$payroll->id}/slip/{$d->id}")
        ->assertOk()->assertSee('Budi')->assertSee('Rp. 1.260.000')->assertSee('Uang Makan')->assertSee('DRAFT');

    $lain = Payroll::create(['periode' => '2026-12', 'tanggal_mulai' => '2026-11-26', 'tanggal_selesai' => '2026-12-25']);
    $this->actingAs($this->hr)->get("/payroll/{$lain->id}/slip/{$d->id}")->assertNotFound();
});

test('halaman detail dan data detail payroll', function () {
    $payroll = buatPayroll($this);

    $this->actingAs($this->hr)->get("/payroll/{$payroll->id}")->assertOk()->assertSee('Finalkan');
    $this->actingAs($this->hr)->getJson("/payroll/{$payroll->id}/data?search=budi")
        ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.gaji_bersih', '1260000.00');
    $this->actingAs($this->hr)->getJson('/payroll/data')
        ->assertJsonPath('data.0.detail_count', 2);
});

test('karyawan tidak bisa akses payroll', function () {
    $payroll = buatPayroll($this);
    $d = $payroll->detail()->first();

    $this->actingAs($this->budi)->get('/payroll')->assertForbidden();
    $this->actingAs($this->budi)->postJson('/payroll', ['periode' => '2026-12'])->assertForbidden();
    $this->actingAs($this->budi)->get("/payroll/{$payroll->id}/slip/{$d->id}")->assertForbidden();
    $this->actingAs($this->budi)->patchJson("/payroll/{$payroll->id}/final")->assertForbidden();
});
