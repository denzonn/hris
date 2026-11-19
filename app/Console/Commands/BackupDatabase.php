<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Backup MySQL via mysqldump → storage/app/backup/hris-YYYYmmdd-His.sql.gz, simpan N hari terakhir.
 * ponytail: backup lokal di server yang sama; salin ke luar server (rclone/Drive/S3) bila butuh tahan bencana.
 */
class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--keep=14 : jumlah hari backup yang disimpan}';

    protected $description = 'Backup database MySQL ke storage/app/backup';

    public function handle(): int
    {
        $db = config('database.connections.'.config('database.default'));
        if (! in_array($db['driver'], ['mysql', 'mariadb'], true)) {
            $this->error('Backup hanya untuk koneksi MySQL/MariaDB.');

            return self::FAILURE;
        }

        $folder = storage_path('app/backup');
        File::ensureDirectoryExists($folder);
        $file = $folder.'/hris-'.now()->format('Ymd-His').'.sql.gz';

        $gz = gzopen($file, 'wb6');
        $hasil = Process::timeout(1800)
            ->env(['MYSQL_PWD' => (string) $db['password']]) // jangan taruh password di argumen (terlihat di daftar proses)
            ->run([
                $db['dump_binary'] ?? 'mysqldump',
                '--no-defaults', // abaikan my.ini/my.cnf (mis. Laragon) — koneksi sudah lengkap dari argumen
                '--host='.$db['host'], '--port='.$db['port'], '--user='.$db['username'],
                '--single-transaction', '--quick', '--routines', '--no-tablespaces',
                $db['database'],
            ], fn (string $tipe, string $output) => $tipe === 'out' && gzwrite($gz, $output));
        gzclose($gz);

        if ($hasil->failed()) {
            File::delete($file);
            $this->error('Backup gagal: '.trim($hasil->errorOutput()));

            return self::FAILURE;
        }

        $batas = now()->subDays((int) $this->option('keep'))->getTimestamp();
        $dihapus = collect(File::glob($folder.'/hris-*.sql.gz'))
            ->filter(fn ($f) => File::lastModified($f) < $batas)
            ->each(fn ($f) => File::delete($f))
            ->count();

        $this->info('Backup tersimpan: '.basename($file).($dihapus ? " ({$dihapus} backup lama dihapus)" : ''));

        return self::SUCCESS;
    }
}
