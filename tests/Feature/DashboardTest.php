<?php

use App\Models\Absensi;
use App\Models\Cabang;
use App\Models\Lembur;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PengajuanIzin;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-19 10:00'));
    $this->admin = User::factory()->otoritas(1)->create();
    $cabang = Cabang::create(['nama' => 'Pusat', 'radius_meter' => 100]);
    $cabang->pengaturanAbsensi()->create(['jam_masuk' => '08:00', 'jam_pulang' => '17:00', 'hari_kerja' => [1, 2, 3, 4, 5, 6]]);
    $this->karyawan = User::factory()->create(['cabang_id' => $cabang->id]);
});

function hadir(User $u, string $tanggal, string $status): void
{
    Absensi::create(['user_id' => $u->id, 'tanggal' => $tanggal, 'status' => $status]);
}

test('dashboard admin menghitung kehadiran hari ini, pending, dan kontrak habis', function () {
    $lain = User::factory()->count(4)->create();
    hadir($this->karyawan, '2026-11-19', 'hadir');
    hadir($lain[0], '2026-11-19', 'telat');
    hadir($lain[1], '2026-11-19', 'sakit');
    hadir($lain[2], '2026-11-19', 'cuti');
    hadir($lain[3], '2026-11-18', 'alpha'); // kemarin, tidak dihitung
    PengajuanIzin::create(['user_id' => $this->karyawan->id, 'jenis' => 'izin', 'tanggal_mulai' => '2026-11-20', 'tanggal_selesai' => '2026-11-20', 'alasan' => 'x']);
    Lembur::create(['user_id' => $this->karyawan->id, 'tanggal' => '2026-11-19', 'jam_mulai' => '17:00', 'jam_selesai' => '18:00']);
    User::factory()->create(['name' => 'Kontrak Habis', 'status_kerja' => 'kontrak', 'tanggal_akhir_kontrak' => '2026-12-10']);
    User::factory()->create(['name' => 'Kontrak Lama', 'status_kerja' => 'kontrak', 'tanggal_akhir_kontrak' => '2027-06-30']);

    $res = $this->actingAs($this->admin)->get('/dashboard')->assertOk()->assertViewIs('pages.dashboard.admin');

    // aktif (tanpa super admin) = karyawan + 4 lain + 2 kontrak = 7; tercatat hari ini 4 → belum absen 3
    expect($res->viewData('totalAktif'))->toBe(7)
        ->and($res->viewData('hariIni'))->toBe(['hadir' => 1, 'telat' => 1, 'izin' => 2, 'alpha' => 0, 'belum' => 3])
        ->and($res->viewData('izinPending'))->toBe(1)
        ->and($res->viewData('lemburPending'))->toBe(1)
        ->and($res->viewData('kontrak')->pluck('name')->all())->toBe(['Kontrak Habis']);
    $res->assertSee('Kontrak Habis')->assertSee('21 hari');
});

test('hr juga melihat dashboard admin', function () {
    $this->actingAs(User::factory()->otoritas(2)->create())->get('/dashboard')->assertOk()->assertViewIs('pages.dashboard.admin');
});

test('dashboard karyawan menampilkan data dirinya sendiri', function () {
    hadir($this->karyawan, '2026-11-02', 'hadir');
    hadir($this->karyawan, '2026-11-03', 'telat');
    hadir($this->karyawan, '2026-11-04', 'cuti');
    hadir($this->karyawan, '2026-10-30', 'alpha'); // bulan lalu
    hadir(User::factory()->create(), '2026-11-05', 'alpha'); // orang lain
    $payroll = Payroll::create(['periode' => '2026-10', 'tanggal_mulai' => '2026-09-26', 'tanggal_selesai' => '2026-10-25', 'status' => 'final']);
    $slip = PayrollDetail::create(['payroll_id' => $payroll->id, 'user_id' => $this->karyawan->id, 'nama' => 'x', 'rincian' => []]);

    $res = $this->actingAs($this->karyawan)->get('/dashboard')->assertOk()->assertViewIs('pages.dashboard.karyawan');

    expect($res->viewData('bulanIni')->all())->toEqual(['cuti' => 1, 'hadir' => 1, 'telat' => 1])
        ->and($res->viewData('sisaCuti'))->toBe(11)
        ->and($res->viewData('slipTerakhir')->id)->toBe($slip->id);
    $res->assertSee('Absen Masuk');
});
