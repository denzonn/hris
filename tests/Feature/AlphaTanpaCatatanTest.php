<?php

use App\Models\Absensi;
use App\Models\Cabang;
use App\Models\HariLibur;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-19 10:00')); // Kamis
    $this->cabang = Cabang::create(['nama' => 'Pusat', 'radius_meter' => 100]);
    $this->cabang->pengaturanAbsensi()->create(['jam_masuk' => '08:00', 'jam_pulang' => '17:00', 'hari_kerja' => [1, 2, 3, 4, 5, 6]]);
    HariLibur::create(['tanggal' => '2026-11-11', 'keterangan' => 'Libur']);
    $this->budi = User::factory()->create(['name' => 'Budi', 'cabang_id' => $this->cabang->id]);
    $this->alpha = fn (User $u, string $mulai, string $selesai) => $u->fresh()->jumlahAlpha(
        $u->absensi()->whereBetween('tanggal', [$mulai, $selesai])->get(), Carbon::parse($mulai), Carbon::parse($selesai),
    );
});

// Hari kerja 1–18 Nov (s.d. kemarin): 2–7 (6) + 9–14 tanpa 11 libur (5) + 16–18 (3) = 14

test('hari kerja yang sudah lewat tanpa catatan dihitung alpha, hari ini & ke depan tidak', function () {
    expect(($this->alpha)($this->budi, '2026-11-01', '2026-11-30'))->toBe(14);
});

test('hari yang punya catatan apa pun tidak dihitung lagi, baris alpha tetap dihitung', function () {
    foreach (['2026-11-02' => 'hadir', '2026-11-03' => 'izin', '2026-11-04' => 'alpha'] as $tgl => $status) {
        Absensi::create(['user_id' => $this->budi->id, 'tanggal' => $tgl, 'status' => $status]);
    }

    // 1 baris alpha + (14 − 3 hari tercatat) = 12
    expect(($this->alpha)($this->budi, '2026-11-01', '2026-11-30'))->toBe(12);
});

test('hari sebelum tanggal masuk tidak dihitung', function () {
    $this->budi->update(['tanggal_masuk' => '2026-11-16']);

    expect(($this->alpha)($this->budi, '2026-11-01', '2026-11-30'))->toBe(3); // 16, 17, 18
});

test('periode yang sudah lewat dihitung penuh', function () {
    // Oktober 2026: 31 hari − 4 Minggu (4, 11, 18, 25) = 27
    expect(($this->alpha)($this->budi, '2026-10-01', '2026-10-31'))->toBe(27);
});

test('cabang tanpa pengaturan absensi hanya menghitung baris alpha', function () {
    $tanpa = User::factory()->create(['cabang_id' => Cabang::create(['nama' => 'Baru', 'radius_meter' => 100])->id]);
    Absensi::create(['user_id' => $tanpa->id, 'tanggal' => '2026-11-02', 'status' => 'alpha']);

    expect(($this->alpha)($tanpa, '2026-11-01', '2026-11-30'))->toBe(1);
});

test('rekap absensi memakai aturan alpha yang sama', function () {
    $hr = User::factory()->otoritas(2)->create();

    $this->actingAs($hr)->getJson('/rekap_absensi/data?tanggal_mulai=2026-11-01&tanggal_selesai=2026-11-30&search=Budi')
        ->assertOk()->assertJsonPath('data.0.alpha', 14);
});
