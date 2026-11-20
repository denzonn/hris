# Deploy HUMORA – Acas Variasi

Checklist memasang aplikasi di server production (VPS Linux / hosting dengan akses SSH & cron).

## 1. Kebutuhan server

- PHP 8.2+ dengan ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`, `zlib`, `bcmath`
- MySQL 8 / MariaDB 10.6+, beserta `mysqldump` (untuk backup)
- Composer 2, Node 20+ (hanya untuk build asset; bisa build di lokal lalu upload `public/build`)
- Web server (Nginx/Apache) dengan document root ke folder **`public/`**
- **HTTPS wajib**: kamera & lokasi (GPS) di HP tidak jalan tanpa HTTPS. Pakai Let's Encrypt (certbot) atau SSL dari hosting.

## 2. `.env` production

Salin dari `.env.example`, lalu ubah minimal:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://hris.domain-anda.com
APP_TIMEZONE=Asia/Jakarta

DB_DATABASE=hris
DB_USERNAME=hris_user        # user khusus, bukan root
DB_PASSWORD=password-kuat
DB_DUMP_BINARY=mysqldump     # isi path lengkap bila tidak ada di PATH

SESSION_SECURE_COOKIE=true   # cookie sesi hanya lewat HTTPS
LOG_LEVEL=warning
```

`APP_ENV=production` juga membuat: semua URL dipaksa `https`, dan seeder **tidak** mengisi data contoh.

## 3. Pemasangan pertama

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build          # atau upload folder public/build hasil build lokal
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force      # otoritas + akun Super Admin
php artisan storage:link         # foto pegawai & foto absensi (disk public)
php artisan optimize             # cache config, route, view
```

Izin folder: user web server (mis. `www-data`) harus bisa menulis `storage/` dan `bootstrap/cache/`.

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

**Akun awal:** `admin@hris.test` / `password`. Di production akun ini **wajib ganti password** saat pertama login. Setelah login, ubah juga email akun ini lewat menu Pegawai.

## 4. Cron (wajib)

Scheduler menjalankan:

| Jam | Command | Fungsi |
|-----|---------|--------|
| 23:59 | `absensi:alpha` | Pegawai yang tidak absen di hari kerja → alpha |
| 01:00 | `backup:database` | Backup MySQL ke `storage/app/backup` (disimpan 14 hari) |

Tambahkan satu baris cron (`crontab -e` sebagai user web server):

```cron
* * * * * cd /path/ke/hris && php artisan schedule:run >> /dev/null 2>&1
```

Cek jadwal: `php artisan schedule:list`. Uji backup manual: `php artisan backup:database`.

Queue worker (`queue:work` + supervisor) **belum diperlukan**, karena belum ada job yang memakai antrian.

## 5. Backup

- File: `storage/app/backup/hris-YYYYmmdd-His.sql.gz`. Folder ini tidak bisa diakses lewat web dan tidak masuk git.
- Backup ini ada di server yang sama. **Salin juga ke luar server** (rclone ke Google Drive/S3, atau unduh rutin). Kalau server rusak, backup lokal ikut hilang.
- Restore: `gunzip -c hris-xxxx.sql.gz | mysql -u USER -p hris`
- Yang tidak ikut dalam backup DB: file upload di `storage/app/public` (foto) dan `storage/app/private` (CV, lampiran izin). Ikutkan dua folder itu ke backup file harian.

## 6. Update aplikasi

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan up
```

## 7. Catatan keamanan

- Login dibatasi 5 percobaan (per email + IP). Form lamaran publik dibatasi 5 kiriman/menit per IP.
- Akun yang dibuat/di-reset oleh HR wajib ganti password sebelum bisa memakai menu lain.
- CV pelamar & lampiran izin disimpan **private** (`storage/app/private`), hanya bisa dibuka lewat aplikasi oleh yang berhak.
- Foto pegawai & foto absensi ada di disk `public`. Nama filenya acak, tapi siapa pun yang tahu URL-nya bisa membuka. Pindahkan ke private bila dianggap sensitif.
- Jangan pernah set `APP_DEBUG=true` di production, karena data `.env` bisa terlihat di halaman error.
