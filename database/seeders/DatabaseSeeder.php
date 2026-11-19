<?php

namespace Database\Seeders;

use App\Models\Cabang;
use App\Models\Divisi;
use App\Models\HariLibur;
use App\Models\Jabatan;
use App\Models\Jobdesk;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(OtoritasSeeder::class);

        User::updateOrCreate(['email' => 'admin@hris.test'], [
            'name' => 'Super Admin',
            'password' => 'password',
            'otoritas_id' => 1,
            'email_verified_at' => now(),
        ]);

        $this->seedContoh();
    }

    /** Data contoh master supaya halaman tidak kosong saat dicoba. */
    private function seedContoh(): void
    {
        $pusat = Cabang::updateOrCreate(['nama' => 'Cabang Pusat'], [
            'alamat' => 'Jl. A. P. Pettarani No. 1, Makassar',
            'latitude' => -5.1476651,
            'longitude' => 119.4327314,
            'radius_meter' => 100,
        ]);
        Cabang::updateOrCreate(['nama' => 'Cabang Gowa'], [
            'alamat' => 'Jl. Sultan Hasanuddin, Gowa',
            'latitude' => -5.2050000,
            'longitude' => 119.4560000,
            'radius_meter' => 150,
        ]);

        $pusat->pengaturanAbsensi()->updateOrCreate([], [
            'jam_masuk' => '08:00',
            'jam_pulang' => '17:00',
            'toleransi_telat_menit' => 15,
            'hari_kerja' => [1, 2, 3, 4, 5, 6],
        ]);

        foreach (['2026-12-25' => 'Hari Raya Natal', '2027-01-01' => 'Tahun Baru Masehi'] as $tanggal => $keterangan) {
            HariLibur::updateOrCreate(['tanggal' => $tanggal], ['keterangan' => $keterangan]);
        }

        $jabatan = [];
        foreach ([
            'Operasional' => ['Kepala Bengkel' => 1500000, 'Teknisi' => 500000],
            'Keuangan' => ['Kasir' => 300000, 'Staff Keuangan' => 400000],
            'HRD' => ['Staff HRD' => 400000],
        ] as $namaDivisi => $daftar) {
            $divisi = Divisi::firstOrCreate(['nama' => $namaDivisi]);
            foreach ($daftar as $nama => $tunjangan) {
                $jabatan[$nama] = Jabatan::updateOrCreate(
                    ['nama' => $nama, 'divisi_id' => $divisi->id],
                    ['tunjangan_jabatan' => $tunjangan],
                );
            }
        }

        Jobdesk::firstOrCreate(['jabatan_id' => $jabatan['Teknisi']->id, 'deskripsi' => 'Memasang dan merawat variasi kendaraan sesuai order.']);
        Jobdesk::firstOrCreate(['jabatan_id' => $jabatan['Kasir']->id, 'deskripsi' => 'Menerima pembayaran dan membuat laporan kas harian.']);

        foreach ([
            ['hr@hris.test', 'Rina HRD', 2, 'Staff HRD', 'tetap', 5500000],
            ['teknisi@hris.test', 'Budi Teknisi', 3, 'Teknisi', 'kontrak', 4000000],
            ['kasir@hris.test', 'Sari Kasir', 3, 'Kasir', 'magang', 2500000],
        ] as $i => [$email, $nama, $otoritas, $namaJabatan, $status, $gaji]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $nama,
                'password' => 'password',
                'otoritas_id' => $otoritas,
                'email_verified_at' => now(),
                'nik' => sprintf('AV%03d', $i + 1),
                'cabang_id' => $pusat->id,
                'divisi_id' => $jabatan[$namaJabatan]->divisi_id,
                'jabatan_id' => $jabatan[$namaJabatan]->id,
                'tanggal_masuk' => '2025-01-02',
                'status_kerja' => $status,
                'tanggal_akhir_kontrak' => $status === 'tetap' ? null : '2026-12-31',
                'gaji_pokok' => $gaji,
                'status_ptkp' => 'TK/0',
            ]);
        }
    }
}
