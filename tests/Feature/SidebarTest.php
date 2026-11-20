<?php

use App\Models\User;

function menuSidebar(User $user, string $url = '/profile'): array
{
    $html = test()->actingAs($user)->get($url)->assertOk()->getContent();
    preg_match('/<nav[^>]*>(.*?)<\/nav>/s', $html, $nav);
    preg_match_all('/<span>([^<]+)<\/span>/', $nav[1], $label);

    return $label[1];
}

test('menu super admin', function () {
    expect(menuSidebar(User::factory()->otoritas(1)->create()))->toBe([
        'Dashboard',
        'Cabang', 'Divisi', 'Jabatan', 'Jobdesk', 'User / Pegawai',
        'Pengaturan', 'Hari Libur', 'Perintah Lembur', 'Input Absensi', 'Izin, Sakit, Cuti', 'Rekap Absensi',
        'Komponen Gaji', 'Gaji Pegawai', 'Pengaturan Payroll', 'Payroll',
        'Lowongan Kerja', 'Data Pelamar',
        'Profil',
    ]);
});

test('menu hr: tanpa cabang, dengan absen & slip gaji', function () {
    expect(menuSidebar(User::factory()->otoritas(2)->create()))->toBe([
        'Dashboard',
        'Divisi', 'Jabatan', 'Jobdesk', 'User / Pegawai',
        'Pengaturan', 'Hari Libur', 'Perintah Lembur', 'Absen', 'Input Absensi', 'Izin, Sakit, Cuti', 'Rekap Absensi',
        'Komponen Gaji', 'Gaji Pegawai', 'Pengaturan Payroll', 'Payroll',
        'Lowongan Kerja', 'Data Pelamar',
        'Slip Gaji', 'Profil',
    ]);
});

test('menu karyawan', function () {
    expect(menuSidebar(User::factory()->otoritas(3)->create()))->toBe([
        'Dashboard', 'Absen', 'Lembur', 'Izin, Sakit, Cuti', 'Slip Gaji', 'Profil',
    ]);
});

test('menu aktif tepat: Absen tidak ikut aktif di Input Absensi', function () {
    $html = $this->actingAs(User::factory()->otoritas(2)->create())->get('/absensi_karyawan')->getContent();

    preg_match_all('/<a href="[^"]*\/([a-z_]+)"\s+aria-current="page"/', $html, $aktif);
    expect($aktif[1])->toBe(['absensi_karyawan']);
});
