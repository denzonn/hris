<?php

use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

// ---------- Paksa ganti password ----------

test('pegawai baru dari admin wajib ganti password sebelum memakai aplikasi', function () {
    $hr = User::factory()->otoritas(2)->create();
    $this->actingAs($hr)->postJson('/user', [
        'name' => 'Budi', 'email' => 'budi@hris.test', 'password' => 'rahasia123', 'otoritas_id' => 3, 'jatah_cuti' => 12,
    ])->assertOk();
    $budi = User::firstWhere('email', 'budi@hris.test');
    expect($budi->wajib_ganti_password)->toBeTrue();

    $this->actingAs($budi)->get('/dashboard')->assertRedirect('/profile')->assertSessionHas('peringatan');
    $this->actingAs($budi)->getJson('/lembur/data')->assertForbidden()->assertJson(['message' => 'Silakan ganti password Anda terlebih dahulu.']);
    $this->actingAs($budi)->get('/profile')->assertOk()->assertSee('Ganti password terlebih dahulu');

    $this->actingAs($budi)->putJson('/profile/password', [
        'password_lama' => 'rahasia123', 'password' => 'punyaBudi99', 'password_confirmation' => 'punyaBudi99',
    ])->assertOk();

    expect($budi->fresh()->wajib_ganti_password)->toBeFalse();
    $this->actingAs($budi->fresh())->get('/dashboard')->assertOk();
});

test('reset password oleh admin mewajibkan ganti, kecuali mengubah akun sendiri', function () {
    $hr = User::factory()->otoritas(2)->create();
    $karyawan = User::factory()->create();
    $data = fn (User $u) => ['name' => $u->name, 'email' => $u->email, 'password' => 'passwordBaru1', 'otoritas_id' => $u->otoritas_id, 'jatah_cuti' => 12];

    $this->actingAs($hr)->putJson("/user/{$karyawan->id}", $data($karyawan))->assertOk();
    $this->actingAs($hr)->putJson("/user/{$hr->id}", $data($hr))->assertOk();

    expect($karyawan->fresh()->wajib_ganti_password)->toBeTrue()
        ->and($hr->fresh()->wajib_ganti_password)->toBeFalse();
});

test('ubah data pegawai tanpa password tidak mengubah status wajib ganti', function () {
    $hr = User::factory()->otoritas(2)->create();
    $karyawan = User::factory()->create();

    $this->actingAs($hr)->putJson("/user/{$karyawan->id}", [
        'name' => 'Nama Baru', 'email' => $karyawan->email, 'otoritas_id' => 3, 'jatah_cuti' => 12,
    ])->assertOk();

    expect($karyawan->fresh()->wajib_ganti_password)->toBeFalse();
});

test('halaman karir & login tetap bisa dibuka tanpa login', function () {
    $this->get('/karir')->assertOk();
    $this->get('/login')->assertOk();
});

// ---------- Backup database ----------

function pakaiMysql(callable $fn): void
{
    $default = config('database.default');
    config(['database.default' => 'mysql', 'database.connections.mysql.password' => 'rahasia']);
    try {
        $fn();
    } finally {
        config(['database.default' => $default]);
    }
}

// storage test diarahkan ke folder temp supaya backup asli di storage/app/backup tidak tersentuh
beforeEach(function () {
    $this->app->useStoragePath(sys_get_temp_dir().'/hris-test-storage-'.getmypid());
    File::deleteDirectory(storage_path('app/backup'));
});
afterEach(fn () => File::deleteDirectory(storage_path()));

test('backup menjalankan mysqldump ke file gzip tanpa password di argumen', function () {
    // Fake Process tidak memanggil callback output, jadi isi dump tidak bisa dicek di sini
    // (sudah diuji manual ke MySQL Laragon). Yang dicek: file .gz terbentuk & argumen aman.
    Process::fake();

    pakaiMysql(fn () => $this->artisan('backup:database')->assertSuccessful());

    $file = File::glob(storage_path('app/backup/hris-*.sql.gz'));
    expect($file)->toHaveCount(1)
        ->and(gzdecode(File::get($file[0])))->toBe('');

    Process::assertRan(fn (PendingProcess $p) => in_array('--single-transaction', $p->command, true)
        && $p->environment['MYSQL_PWD'] === 'rahasia'
        && ! str_contains(implode(' ', $p->command), 'rahasia'));
});

test('backup gagal tidak meninggalkan file setengah jadi', function () {
    Process::fake(['*' => Process::result(errorOutput: 'Access denied', exitCode: 2)]);

    pakaiMysql(fn () => $this->artisan('backup:database')->expectsOutputToContain('Backup gagal: Access denied')->assertFailed());

    expect(File::glob(storage_path('app/backup/*.gz')))->toBeEmpty();
});

test('backup lama melewati batas hari dihapus', function () {
    Process::fake(['*' => Process::result(output: 'dump')]);
    File::ensureDirectoryExists(storage_path('app/backup'));
    $lama = storage_path('app/backup/hris-20260101-010000.sql.gz');
    $baru = storage_path('app/backup/hris-20261118-010000.sql.gz');
    File::put($lama, 'x');
    File::put($baru, 'x');
    touch($lama, now()->subDays(15)->getTimestamp());
    touch($baru, now()->subDays(1)->getTimestamp());

    pakaiMysql(fn () => $this->artisan('backup:database', ['--keep' => 14])->expectsOutputToContain('1 backup lama dihapus'));

    expect(File::exists($lama))->toBeFalse()->and(File::exists($baru))->toBeTrue();
});

test('backup ditolak untuk koneksi selain mysql', function () {
    $this->artisan('backup:database')->expectsOutput('Backup hanya untuk koneksi MySQL/MariaDB.')->assertFailed();
});

test('backup terjadwal setiap hari jam 01:00', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($e) => str_contains($e->command, 'backup:database'));

    expect($event?->expression)->toBe('0 1 * * *');
});
