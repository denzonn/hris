<?php

use App\Models\PengaturanPayroll;
use App\Services\HitungPayroll;

// Upah sebulan 3.460.000 → upah sejam = 3.460.000 / 173 = 20.000 (angka bulat supaya mudah dicek)

test('lembur jam pertama 1,5x upah sejam', function () {
    expect(HitungPayroll::upahLembur(3_460_000, 60))->toBe(30_000); // 1,5 × 20.000
});

test('lembur jam berikutnya 2x upah sejam', function () {
    expect(HitungPayroll::upahLembur(3_460_000, 120))->toBe(70_000)  // 30.000 + 2 × 20.000
        ->and(HitungPayroll::upahLembur(3_460_000, 150))->toBe(90_000); // 30.000 + 1,5 jam × 40.000
});

test('lembur kurang dari 1 jam dihitung proporsional', function () {
    expect(HitungPayroll::upahLembur(3_460_000, 30))->toBe(15_000) // 0,5 × 30.000
        ->and(HitungPayroll::upahLembur(3_460_000, 0))->toBe(0);
});

test('upah lembur dibulatkan ke rupiah terdekat', function () {
    // 3.000.000 / 173 = 17.341,04 × 1,5 = 26.011,56 → 26.012
    expect(HitungPayroll::upahLembur(3_000_000, 60))->toBe(26_012);
});

function dataGaji(array $override = []): array
{
    return array_merge([
        'gaji_pokok' => 3_000_000,
        'tunjangan_jabatan' => 300_000,
        'hari_hadir' => 20,
        'menit_telat' => 45,
        'jumlah_alpha' => 2,
        'komponen' => [
            ['nama' => 'Transport', 'tipe' => 'tunjangan', 'sifat' => 'tetap', 'nominal' => 160_000],
            ['nama' => 'Uang Makan', 'tipe' => 'tunjangan', 'sifat' => 'per_hari_hadir', 'nominal' => 25_000],
            ['nama' => 'Kasbon', 'tipe' => 'potongan', 'sifat' => 'tetap', 'nominal' => 200_000],
        ],
        'lembur_menit' => [120, 60],
        'potongan_per_menit_telat' => 1_000,
        'potongan_per_alpha' => 100_000,
    ], $override);
}

test('komponen per hari hadir dikali jumlah hadir, komponen tetap apa adanya', function () {
    $h = HitungPayroll::hitung(dataGaji());

    expect(array_column($h['rincian'], 'jumlah', 'nama'))->toBe([
        'Transport' => 160_000,
        'Uang Makan' => 500_000, // 25.000 × 20 hari
        'Kasbon' => 200_000,
    ])->and($h['total_tunjangan'])->toBe(660_000)
        ->and($h['total_potongan'])->toBe(200_000);
});

test('upah lembur memakai gaji pokok + tunjangan jabatan + tunjangan tetap sebagai upah sebulan', function () {
    // 3.000.000 + 300.000 + 160.000 (transport) = 3.460.000 → sejam 20.000; uang makan (harian) tidak ikut
    // lembur 120 menit = 70.000, 60 menit = 30.000
    expect(HitungPayroll::hitung(dataGaji())['upah_lembur'])->toBe(100_000);
});

test('potongan telat per menit dan potongan alpha per hari', function () {
    $h = HitungPayroll::hitung(dataGaji());

    expect($h['potongan_telat'])->toBe(45_000)   // 45 menit × 1.000
        ->and($h['potongan_alpha'])->toBe(200_000); // 2 × 100.000
});

test('gaji bersih sesuai rumus', function () {
    // 3.000.000 + 300.000 + 660.000 + 100.000 − 45.000 − 200.000 − 200.000 = 3.615.000
    expect(HitungPayroll::hitung(dataGaji())['gaji_bersih'])->toBe(3_615_000);
});

test('tanpa komponen, telat, alpha, lembur: gaji bersih = gaji pokok + tunjangan jabatan', function () {
    $h = HitungPayroll::hitung(dataGaji(['komponen' => [], 'lembur_menit' => [], 'menit_telat' => 0, 'jumlah_alpha' => 0]));

    expect($h['gaji_bersih'])->toBe(3_300_000);
});

test('rentang periode mengikuti tanggal cut-off', function () {
    $tgl = fn (PengaturanPayroll $p, string $periode) => array_map(fn ($t) => $t->toDateString(), $p->rentang($periode));

    expect($tgl(new PengaturanPayroll(['tanggal_cutoff' => 25]), '2026-11'))->toBe(['2026-10-26', '2026-11-25'])
        ->and($tgl(new PengaturanPayroll(['tanggal_cutoff' => 25]), '2026-01'))->toBe(['2025-12-26', '2026-01-25'])
        ->and($tgl(new PengaturanPayroll(['tanggal_cutoff' => null]), '2026-02'))->toBe(['2026-02-01', '2026-02-28'])
        // cut-off 28 Feb (tahun non kabisat) → periode Maret mulai 1 Maret
        ->and($tgl(new PengaturanPayroll(['tanggal_cutoff' => 28]), '2027-03'))->toBe(['2027-03-01', '2027-03-28']);
});
