<?php

use App\Models\Absensi;
use App\Models\Cabang;
use App\Models\HariLibur;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-19 23:59')); // Kamis
    $this->cabang = Cabang::create(['nama' => 'Pusat', 'radius_meter' => 100]);
    $this->cabang->pengaturanAbsensi()->create([
        'jam_masuk' => '08:00', 'jam_pulang' => '17:00', 'hari_kerja' => [1, 2, 3, 4, 5, 6],
    ]);
    $this->pegawai = fn (array $attr = []) => User::factory()->create($attr + ['cabang_id' => $this->cabang->id]);
});

test('pegawai yang tidak absen di hari kerja ditandai alpha', function () {
    $bolos = ($this->pegawai)();

    $this->artisan('absensi:alpha')->expectsOutput('1 pegawai ditandai alpha pada 2026-11-19.')->assertSuccessful();

    expect(Absensi::where('user_id', $bolos->id)->first())
        ->status->toBe('alpha')->tanggal->toDateString()->toBe('2026-11-19');
});

test('alpha otomatis tidak menimpa izin, cuti, atau hadir', function () {
    foreach (['izin', 'cuti', 'hadir'] as $status) {
        Absensi::create(['user_id' => ($this->pegawai)()->id, 'tanggal' => '2026-11-19', 'status' => $status]);
    }

    $this->artisan('absensi:alpha')->expectsOutput('0 pegawai ditandai alpha pada 2026-11-19.');

    expect(Absensi::pluck('status')->sort()->values()->all())->toBe(['cuti', 'hadir', 'izin']);
});

test('tidak ada alpha di hari minggu dan hari libur', function () {
    ($this->pegawai)();
    HariLibur::create(['tanggal' => '2026-11-23', 'keterangan' => 'Libur']);

    $this->artisan('absensi:alpha', ['tanggal' => '2026-11-22'])->assertSuccessful(); // Minggu
    $this->artisan('absensi:alpha', ['tanggal' => '2026-11-23'])->assertSuccessful(); // Senin libur

    $this->assertDatabaseCount('absensi', 0);
});

test('pegawai nonaktif, tanpa cabang/pengaturan, atau belum masuk kerja dilewati', function () {
    ($this->pegawai)(['aktif' => false]);
    ($this->pegawai)(['tanggal_masuk' => '2026-11-20']);
    User::factory()->create(['cabang_id' => null]);
    User::factory()->create(['cabang_id' => Cabang::create(['nama' => 'Tanpa Pengaturan', 'radius_meter' => 100])->id]);

    $this->artisan('absensi:alpha')->expectsOutput('0 pegawai ditandai alpha pada 2026-11-19.');
});

test('menjalankan ulang tidak membuat data dobel', function () {
    ($this->pegawai)();

    $this->artisan('absensi:alpha');
    $this->artisan('absensi:alpha')->expectsOutput('0 pegawai ditandai alpha pada 2026-11-19.');

    $this->assertDatabaseCount('absensi', 1);
});

test('command terjadwal setiap hari jam 23:59', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($e) => str_contains($e->command, 'absensi:alpha'));

    expect($event?->expression)->toBe('59 23 * * *');
});
