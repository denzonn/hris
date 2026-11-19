<?php

use App\Models\Absensi;
use App\Models\Cabang;
use App\Models\HariLibur;
use App\Models\Lembur;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-19 10:00'));
    $this->hr = User::factory()->otoritas(2)->create();
    $this->cabang = Cabang::create(['nama' => 'Pusat', 'radius_meter' => 100]);
    $this->cabang->pengaturanAbsensi()->create([
        'jam_masuk' => '08:00', 'jam_pulang' => '17:00', 'hari_kerja' => [1, 2, 3, 4, 5, 6],
    ]);
    $this->budi = User::factory()->create(['name' => 'Budi', 'nik' => 'P1', 'cabang_id' => $this->cabang->id]);
});

function absen(User $u, string $tanggal, string $status, int $telat = 0): void
{
    Absensi::create(['user_id' => $u->id, 'tanggal' => $tanggal, 'status' => $status, 'menit_telat' => $telat]);
}

test('rekap menghitung per status, telat, hari kerja, dan lembur disetujui', function () {
    // Periode Sen 16 – Min 22 Nov 2026, libur Rabu 18 → hari kerja = 16,17,19,20,21 = 5
    HariLibur::create(['tanggal' => '2026-11-18', 'keterangan' => 'Libur']);
    absen($this->budi, '2026-11-16', 'hadir');
    absen($this->budi, '2026-11-17', 'telat', 20);
    absen($this->budi, '2026-11-19', 'telat', 35);
    absen($this->budi, '2026-11-20', 'sakit');
    absen($this->budi, '2026-11-21', 'alpha');
    absen($this->budi, '2026-11-23', 'alpha'); // di luar periode
    Lembur::create(['user_id' => $this->budi->id, 'tanggal' => '2026-11-16', 'jam_mulai' => '17:00', 'jam_selesai' => '19:30', 'status' => 'disetujui']);
    Lembur::create(['user_id' => $this->budi->id, 'tanggal' => '2026-11-17', 'jam_mulai' => '17:00', 'jam_selesai' => '18:00', 'status' => 'disetujui']);
    Lembur::create(['user_id' => $this->budi->id, 'tanggal' => '2026-11-19', 'jam_mulai' => '17:00', 'jam_selesai' => '20:00', 'status' => 'pending']);

    $this->actingAs($this->hr)->getJson('/rekap_absensi/data?tanggal_mulai=2026-11-16&tanggal_selesai=2026-11-22&search=budi')
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0', [
            'id' => $this->budi->id, 'nik' => 'P1', 'name' => 'Budi', 'cabang' => 'Pusat', 'divisi' => null,
            'hari_kerja' => 5,
            'hadir' => 3,          // hadir + 2× telat
            'telat' => 2,
            'menit_telat' => 55,   // 20 + 35
            'izin' => 0, 'sakit' => 1, 'cuti' => 0,
            'alpha' => 1,          // 23 Nov di luar periode
            'lembur_menit' => 210, // 150 + 60, yang pending tidak dihitung
        ]);
});

test('rekap bisa difilter cabang dan tidak menampilkan super admin / pegawai nonaktif', function () {
    $lain = Cabang::create(['nama' => 'Gowa', 'radius_meter' => 100]);
    User::factory()->create(['cabang_id' => $lain->id]);
    User::factory()->nonaktif()->create(['cabang_id' => $this->cabang->id]);

    $this->actingAs($this->hr)->getJson("/rekap_absensi/data?cabang_id={$this->cabang->id}")
        ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.name', 'Budi');
    $this->actingAs($this->hr)->getJson('/rekap_absensi/data')->assertJsonPath('total', 3); // hr + budi + pegawai gowa
});

test('periode rekap maksimal 3 bulan', function () {
    $this->actingAs($this->hr)->getJson('/rekap_absensi/data?tanggal_mulai=2026-01-01&tanggal_selesai=2026-06-30')
        ->assertUnprocessable()->assertJson(['message' => 'Periode rekap maksimal 3 bulan.']);
});

test('export csv berisi header dan baris rekap', function () {
    absen($this->budi, '2026-11-16', 'telat', 20);

    $csv = $this->actingAs($this->hr)->get('/rekap_absensi/export?tanggal_mulai=2026-11-16&tanggal_selesai=2026-11-16&search=budi')
        ->assertOk()->assertDownload('rekap-absensi-2026-11-16-sd-2026-11-16.csv')
        ->streamedContent();

    $baris = explode("\n", trim(substr($csv, 3))); // buang BOM
    expect($baris[0])->toBe('NIK;Nama;Cabang;Divisi;"Hari Kerja";Hadir;"Telat (x)";"Telat (menit)";Izin;Sakit;Cuti;Alpha;"Lembur (menit)"')
        ->and($baris[1])->toBe('P1;Budi;Pusat;;1;1;1;20;0;0;0;0;0');
});

test('karyawan tidak bisa akses rekap', function () {
    $this->actingAs($this->budi)->get('/rekap_absensi')->assertForbidden();
    $this->actingAs($this->budi)->getJson('/rekap_absensi/data')->assertForbidden();
    $this->actingAs($this->budi)->get('/rekap_absensi/export')->assertForbidden();
});
