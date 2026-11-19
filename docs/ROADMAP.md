# Roadmap HRIS – Acas Variasi

Stack: Laravel 12 · Blade · Tailwind · jQuery (AJAX + helper di `resources/js/helpers`) · SweetAlert2 · Select2 · Flatpickr · MySQL · Pest.

Urutan fase mengikuti dependensi: **Fondasi → Master → Pengaturan → Absensi → Payroll → Laporan → Modul tambahan**.
Jangan lompat fase. Payroll butuh absensi, absensi butuh master + pengaturan.

## Progress

- [x] Fase 0 – Perbaikan Base Project
- [x] Fase 1 – Master Data
- [x] Fase 2 – Pengaturan Absensi
- [x] Fase 3 – Absensi
- [x] Fase 4 – Payroll
- [x] Fase 5 – Dashboard & Portal Karyawan
- [x] Fase 6 – Rekrutmen
- [ ] Fase 7 – Kendaraan / Booking
- [ ] Fase 8 – Hardening & Deploy

---

## Fase 0 – Perbaikan Base Project (WAJIB sebelum fitur) ✅ Selesai

Kondisi base saat ini yang akan bikin error:

| # | Masalah | Lokasi | Perbaikan |
|---|---------|--------|-----------|
| 1 | Sidebar memanggil route yang belum ada (`dashboard`, `cabang`, `divisi`, `payroll`, dll) → error setelah login | `resources/views/includes/sidebar.blade.php` | Comment menu yang belum dibuat, aktifkan per fase |
| 2 | Kolom `otoritas_id` dipakai sidebar tapi tidak ada di tabel `users` | migration users | Tambah kolom (Fase 0.3) |
| 3 | `routes/auth.php` import controller Breeze yang tidak ada (Register, Password, dll) | `routes/auth.php` | Hapus route register (HRIS: akun dibuat admin), sisakan login/logout |
| 4 | Test Breeze (Registration, PasswordReset, Profile, EmailVerification) pasti gagal | `tests/Feature/Auth` | Hapus test yang fiturnya tidak dipakai |
| 5 | jQuery dimuat 2x (3.6.0 di `style`, 3.7.1 di `script`) | `includes/style.blade.php` | Hapus yang 3.6.0 |
| 6 | `route('dashboard')` dipakai setelah login tapi belum ada | `routes/web.php` | Buat route + view dashboard kosong |

Langkah:

1. `git init` + commit pertama (base bersih).
2. `.env`: `APP_NAME=HRIS`, `DB_DATABASE=hris`, `APP_LOCALE=id`, `APP_FAKER_LOCALE=id_ID`. `config/app.php`: `timezone => 'Asia/Jakarta'` (penting untuk absensi!).
3. Migration `otoritas` (id, nama) + kolom `users.otoritas_id`. Seed: `1 = Super Admin`, `2 = HR / Admin Cabang`, `3 = Karyawan`.
4. Middleware sederhana `otoritas:1,2` (alias di `bootstrap/app.php`) untuk proteksi route.
5. Seeder akun admin default.
6. Dashboard kosong + route group `auth`.
7. **Buat 1 pola CRUD standar** (pakai modul Cabang sebagai contoh), lalu copy pola itu ke modul lain:
   - `GET /cabang` → view
   - `GET /cabang/data` → JSON `paginate()` (dipakai `renderPagination`, `search`, `skeleton`, `emptyTable`)
   - `POST /cabang`, `PUT /cabang/{id}`, `DELETE /cabang/{id}` → JSON, validasi via `FormRequest`
   - Modal tambah/edit pakai `showModal()`, hapus pakai helper `hapus.js`

**Selesai jika:** login → dashboard tanpa error, `php artisan test` hijau.

Catatan pelaksanaan Fase 0:

- Langkah 1–7 selesai. Modul **Cabang** (step 1.1 Fase 1) sudah dibuat sebagai contoh pola CRUD → di Fase 1 tinggal lanjut dari 1.2.
- Data otoritas di-seed via `OtoritasSeeder`; `tests/TestCase.php` menjalankannya otomatis tiap test (FK `users.otoritas_id`).
- Test pakai SQLite in-memory (`phpunit.xml`) → butuh ekstensi `pdo_sqlite` aktif di PHP.
- Middleware: `App\Http\Middleware\CekOtoritas`, alias `otoritas`.
- `auth/login.blade.php` sebelumnya extends `layouts.auth` (tidak ada) → diganti ke `layouts.landing`.
- Component `<x-input>` sebelumnya file kosong → diisi (label + input + `<p data-error>` untuk pesan validasi 422). `<x-select>` masih kosong, isi saat pertama dibutuhkan.
- Script halaman (`@push('addon-script')`) harus memanggil helper di dalam `$(function () { ... })` karena `app.js` (module Vite) dieksekusi setelah script inline.
- `public/images/logo.png` belum ada (dipakai sidebar & favicon).

---

## Fase 1 – Master Data ✅ Selesai

| Step | Tabel | Kolom utama | Catatan |
|------|-------|-------------|---------|
| 1.1 | `cabang` | nama, alamat, latitude, longitude, radius_meter | lat/long/radius dipakai validasi lokasi absensi |
| 1.2 | `divisi` | nama | |
| 1.3 | `jabatan` | nama, divisi_id, tunjangan_jabatan | |
| 1.4 | `jobdesk` | jabatan_id, deskripsi | |
| 1.5 | `users` (Pegawai) | + nik, cabang_id, divisi_id, jabatan_id, no_hp, alamat, tanggal_masuk, status_kerja (tetap/kontrak/magang), tanggal_akhir_kontrak, gaji_pokok, bank, no_rekening, npwp, status_ptkp, foto, aktif | Satu tabel `users` = akun + data pegawai (cukup untuk skala UKM). Pisah tabel kalau nanti ada pegawai tanpa akun login |

Per step: migration → model + relasi → FormRequest → controller (pola Fase 0.7) → view → route → aktifkan menu sidebar → 1 feature test (CRUD jalan + karyawan biasa ditolak).

Catatan: **Pegawai tidak boleh di-hard delete** (riwayat absensi & payroll ikut hilang) → pakai kolom `aktif` / SoftDeletes.

Catatan pelaksanaan Fase 1:

- Akses: Cabang hanya otoritas 1; Divisi/Jabatan/Jobdesk/Pegawai otoritas 1 & 2 (menu sidebar untuk keduanya sudah aktif).
- Pegawai = route `user` (`/user`), `UserController` + `UserRequest`. Tidak ada route DELETE; `PATCH /user/{user}/aktif` toggle kolom `aktif`. User nonaktif ditolak saat login (`LoginRequest`), tapi sesi yang sudah login tidak otomatis ditendang.
- Aturan otoritas: HR tidak bisa memberi otoritas Super Admin (validasi `otoritas_id`) dan tidak bisa mengubah/menonaktifkan akun Super Admin (403). Admin tidak bisa menonaktifkan diri sendiri (422).
- Validasi tambahan: `jabatan_id` harus milik `divisi_id` yang dipilih; `status_ptkp` dibatasi `TK/0–TK/3, K/0–K/3` (`UserRequest::STATUS_PTKP`).
- Hapus master yang masih dipakai → 422 + pesan: divisi yang punya jabatan/pegawai, jabatan/cabang yang dipakai pegawai. Jabatan dihapus → jobdesk ikut terhapus (cascade).
- Foto pegawai di disk `public/pegawai` (`php artisan storage:link` sudah dijalankan; link `public/storage` tidak di-commit, jalankan ulang di mesin lain).
- `ajaxRequest` sekarang menerima `processData`/`contentType` (untuk FormData).
- Seeder menambah data contoh: 2 cabang, 3 divisi, 5 jabatan, 2 jobdesk, 3 pegawai (`hr@hris.test`, `teknisi@hris.test`, `kasir@hris.test`, password `password`).
- Select2 belum dipakai (helper `select2.js` tidak di-import di `app.js`); `<x-select>` masih select biasa.

---

## Fase 2 – Pengaturan Absensi ✅ Selesai

| Step | Fitur | Tabel | Isi |
|------|-------|-------|-----|
| 2.1 | Pengaturan jam kerja | `pengaturan_absensi` (per cabang) | jam_masuk, jam_pulang, toleransi_telat_menit, hari_kerja (Senin–Sabtu, simpan JSON), wajib_foto, wajib_lokasi |
| 2.2 | Hari Libur | `hari_libur` | tanggal (unique), keterangan |
| 2.3 | Perintah Lembur | `lembur` | user_id, tanggal, jam_mulai, jam_selesai, keterangan, status (pending/disetujui/ditolak), disetujui_oleh |

Mulai dengan **1 jadwal per cabang**. Shift (pagi/malam/rotasi) baru ditambah kalau memang ada karyawan shift.

Catatan pelaksanaan Fase 2:

- Route: `pengaturan_absensi`, `hari_libur` (otoritas 1 & 2), `lembur` (semua otoritas). Menu sidebar Absensi aktif untuk Admin & HR (Pengaturan, Hari Libur, Perintah Lembur) dan Karyawan (Lembur).
- **Pengaturan absensi**: tabel berisi 1 baris per cabang (`cabang_id` unique, cascade saat cabang dihapus). Halaman menampilkan semua cabang; tidak ada tambah/hapus, hanya `PUT /pengaturan_absensi/{cabang}` (upsert). Cabang tanpa pengaturan tampil "Belum diatur" → **Fase 3 harus menangani cabang tanpa pengaturan** (tolak absen / pakai default).
- `hari_kerja` = array angka ISO (1 = Senin … 7 = Minggu), cocok dengan `Carbon::dayOfWeekIso`. Disimpan urut.
- **Lembur**: HR/Admin membuat perintah → langsung `disetujui` (`disetujui_oleh` = pembuat). Karyawan mengajukan untuk dirinya sendiri → `pending`; hanya bisa ubah/hapus miliknya selama `pending` (lainnya 403/422). `PATCH /lembur/{id}/status` (setujui/tolak) hanya otoritas 1 & 2; `disetujui_oleh` diisi juga saat ditolak (= yang memproses).
- Lembur belum mendukung lewat tengah malam (`jam_selesai` harus > `jam_mulai`) dan belum cek bentrok jam di tanggal yang sama. Durasi dihitung dari jam (belum disimpan) → payroll Fase 4 hanya pakai lembur `disetujui`.
- `formatBadgeStatus` sekarang mewarnai `disetujui` hijau.
- Seeder: pengaturan Cabang Pusat (08:00–17:00, toleransi 15 menit, Senin–Sabtu) + 2 hari libur contoh.

---

## Fase 3 – Absensi ✅ Selesai

### 3.1 Absen Karyawan (self check-in)
- Tabel `absensi`: user_id, tanggal, jam_masuk, jam_pulang, foto_masuk, foto_pulang, lat_masuk, lng_masuk, lat_pulang, lng_pulang, status (hadir/telat/izin/sakit/cuti/alpha/libur), menit_telat, keterangan. **Unique (user_id, tanggal)**.
- Foto: `<input type="file" accept="image/*" capture="user">` (kamera HP, tanpa library).
- Lokasi: `navigator.geolocation` di browser, **validasi jarak di server** (rumus haversine vs radius cabang) — jangan percaya validasi di JS saja.
- Hitung `menit_telat` di server dari pengaturan cabang.
- Tolak absen di hari libur / hari non-kerja (atau tandai lembur).

### 3.2 Input Absensi Manual (HR)
- HR bisa tambah/koreksi absensi karyawan (lupa absen, HP rusak). Simpan `diubah_oleh` untuk jejak.

### 3.3 Izin, Sakit, Cuti
- Tabel `pengajuan_izin`: user_id, jenis (izin/sakit/cuti), tanggal_mulai, tanggal_selesai, alasan, lampiran, status, disetujui_oleh.
- Saldo cuti: kolom `jatah_cuti` di users (default 12/tahun), sisa = jatah − cuti disetujui tahun ini (hitung, jangan disimpan).
- Saat disetujui → isi baris `absensi` untuk tiap tanggal dengan status izin/sakit/cuti.

### 3.4 Rekap Absensi
- Filter periode + cabang + divisi. Per karyawan: hari kerja, hadir, telat (x kali / total menit), izin, sakit, cuti, alpha, jam lembur.
- Export: CSV dulu (`response()->streamDownload`, tanpa package). Excel (`maatwebsite/excel`) hanya kalau benar-benar diminta.

### 3.5 Otomatis Alpha
- Command terjadwal (`routes/console.php` → `Schedule::command(...)->dailyAt('23:59')`) : karyawan aktif yang tidak absen di hari kerja → status `alpha`.
- Di server: cron `* * * * * php artisan schedule:run`.

**Test wajib:** hitung telat, validasi radius, unique per hari, alpha otomatis tidak menimpa izin/cuti.

Catatan pelaksanaan Fase 3:

- Route: `absen` (absen mandiri, semua otoritas), `absensi_karyawan` & `rekap_absensi` (otoritas 1 & 2), `izin_karyawan` (semua otoritas, karyawan hanya datanya sendiri). Sidebar: Admin → Input Absensi, Izin, Rekap; HR → + Absen; Karyawan → Absen, Lembur, Izin.
- **Aturan telat**: `menit_telat` dihitung dari jam masuk jadwal, toleransi hanya batas (jadwal 08:00, toleransi 15 → 08:15 = 0, 08:16 = **16** menit). Ada di `PengaturanAbsensi::menitTelat()`. Kalau aturan perusahaan ingin dihitung dari batas toleransi (08:16 = 1), ubah di method itu + test-nya.
- Haversine: `Cabang::jarakKe()`. Hari kerja: `PengaturanAbsensi::tanggalKerja()` / `isHariKerja()` (hari_kerja − hari libur) — dipakai absen, izin, rekap, alpha.
- Absen mandiri ditolak bila: tidak punya cabang, cabang belum punya pengaturan, bukan hari kerja/libur (belum ada opsi "tandai lembur"), sudah ada baris absensi hari itu (termasuk izin/cuti), di luar radius. Absen pulang tidak dicek hari kerja dan boleh sebelum jam pulang (pulang cepat belum ditandai).
- Foto absensi di disk `public/absensi/Y-m` (belum dikompres → Fase 8).
- Input manual HR: status `hadir` → server yang menentukan hadir/telat dari jam masuk. Status selain hadir mengosongkan jam & menit telat. Tanggal tidak boleh di masa depan. `diubah_oleh` = HR. **Fase 4: absensi pada periode payroll `final` harus dikunci** (belum ada cek).
- Izin/sakit/cuti: hanya pengajuan `pending` yang bisa diubah/dihapus/diproses (sekali). Disetujui → baris absensi dibuat untuk hari kerja dalam rentang; menimpa `alpha`, tidak menimpa status lain (hadir dll). Pengajuan dari HR langsung disetujui. Bentrok dengan pengajuan pending/disetujui lain ditolak.
- Saldo cuti: `User::sisaCuti($tahun)` = `jatah_cuti` − jumlah baris absensi `cuti` di tahun itu. Cuti lintas tahun dihitung dari saldo tahun `tanggal_mulai`.
- Lampiran izin disimpan di disk **`local` (private)**, diunduh lewat `GET /izin_karyawan/{id}/lampiran` (pemilik atau HR/Admin). Path tidak ikut di JSON (`ada_lampiran` saja).
- Rekap: pegawai aktif (tanpa Super Admin), default bulan berjalan, maksimal 3 bulan. Hari kerja = hari kerja cabang dalam periode penuh (belum memperhitungkan tanggal masuk pegawai). Lembur = lembur `disetujui` saja (menit). CSV pakai pemisah `;` + BOM agar langsung rapi di Excel locale Indonesia.
- Alpha otomatis: `php artisan absensi:alpha {tanggal?}`, terjadwal `23:59` di `routes/console.php`. Melewati pegawai nonaktif, tanpa cabang/pengaturan, dan yang `tanggal_masuk`-nya setelah tanggal itu. Aman dijalankan ulang.

---

## Fase 4 – Payroll ✅ Selesai

| Step | Fitur | Isi |
|------|-------|-----|
| 4.1 | Komponen Gaji | tabel `komponen_gaji`: nama, tipe (tunjangan/potongan), sifat (tetap/per_hari_hadir) |
| 4.2 | Komponen per Karyawan | tabel `komponen_gaji_user`: user_id, komponen_id, nominal |
| 4.3 | Pengaturan Payroll | tanggal cut-off (mis. 26–25), potongan per menit telat / per alpha, tarif lembur |
| 4.4 | Generate Payroll | tabel `payroll` (header: periode, status draft/final) + `payroll_detail` (per karyawan, snapshot semua angka) |
| 4.5 | Slip Gaji | view slip + CSS `@media print` (Print → Save as PDF). `barryvdh/laravel-dompdf` hanya kalau perlu kirim PDF otomatis |
| 4.6 | Kunci Periode | status `final` → tidak bisa di-generate ulang / diedit |

Rumus dasar per karyawan:

```
gaji_bersih = gaji_pokok
            + tunjangan_jabatan + Σ tunjangan tetap + (tunjangan harian × hari hadir)
            + upah lembur
            − potongan telat − potongan alpha − Σ potongan tetap
            − BPJS (opsional) − PPh21 (opsional)
```

- Upah lembur (Kepmenaker 102/2004): upah/jam = 1/173 × upah sebulan; jam ke-1 = 1,5×, jam berikutnya = 2×.
- BPJS & PPh21 (TER, PP 58/2023) masuk **belakangan** sebagai step 4.7 kalau memang dipotong lewat sistem.
- **Simpan snapshot** angka di `payroll_detail` — jangan hitung ulang dari master, karena gaji pokok bisa berubah.
- Uang: kolom `decimal(15,2)` / `bigInteger` rupiah, jangan `float`.

**Test wajib (paling penting di seluruh project):** satu test per komponen rumus (lembur, telat, alpha, total) dengan angka yang dihitung manual.

Catatan pelaksanaan Fase 4:

- Route (otoritas 1 & 2): `komponen_gaji`, `gaji_pegawai` (nominal komponen per pegawai, `PUT /gaji_pegawai/{user}` = sync; nominal 0 = dilepas), `pengaturan_payroll` (1 baris global, `PengaturanPayroll::aktif()`), `payroll` (+ `/payroll/{id}`, `/generate`, `/final`, `/slip/{detail}`). Menu sidebar "Payroll" aktif untuk Admin & HR.
- Tabel `komponen_gaji_user` = pivot `User::komponenGaji()` (withPivot `nominal`). Komponen yang masih dipakai pegawai tidak bisa dihapus (422).
- **Periode**: `periode` = bulan gajian `Y-m` (unik). Cut-off 25 → periode 2026-11 = 26 Okt – 25 Nov; cut-off kosong = 1 bulan kalender (`PengaturanPayroll::rentang()`). Cut-off dibatasi 1–28.
- **Rumus** di `App\Services\HitungPayroll::hitung()` (fungsi murni, integer rupiah):
  - hari hadir = absensi `hadir` + `telat`; komponen `per_hari_hadir` = nominal × hari hadir.
  - upah lembur: upah sebulan = gaji pokok + tunjangan jabatan + tunjangan **tetap** (tunjangan harian tidak ikut); per lembur: jam ke-1 × 1,5, sisanya × 2, proporsional per menit, dibulatkan per lembur. Hanya lembur `disetujui`. Belum membedakan lembur hari libur (×2/×3/×4) dan aturan 75% upah.
  - potongan telat = total menit × tarif; potongan alpha = jumlah alpha × tarif. Gaji bersih bisa negatif (tidak di-clamp) — cek manual kalau terjadi.
- **Generate**: pegawai aktif selain Super Admin dengan `tanggal_masuk` ≤ akhir periode. Semua angka + identitas (nama, NIK, jabatan, cabang, rekening) disalin ke `payroll_detail` (+ `rincian` JSON per komponen). Generate ulang (draft) menghapus & menghitung ulang detail.
- **Final**: payroll final tidak bisa di-generate ulang/dihapus. `Payroll::tolakJikaTerkunci()` menolak (422) input/ubah/hapus absensi manual, lembur (tambah/ubah/hapus/setujui), dan pengajuan izin yang menyentuh tanggal di periode final; `absensi:alpha` melewati tanggal tersebut. Absen mandiri tidak dicek (periode biasanya difinalkan setelah lewat).
- Slip: `payroll/{id}/slip/{detail}` halaman mandiri + `@media print` (Print → Save as PDF), tanda "DRAFT" bila belum final. Slip untuk karyawan sendiri (otoritas 3) → Fase 5.
- BPJS & PPh21 (step 4.7) belum dibuat.
- Seeder: pengaturan payroll (cut-off 25, telat Rp1.000/menit, alpha Rp100.000) + komponen Uang Makan (Rp25.000/hari) & Tunjangan Transport (Rp300.000) untuk 3 pegawai contoh.

---

## Fase 5 – Dashboard & Portal Karyawan

- **Admin/HR:** total karyawan aktif, hadir / telat / izin / alpha hari ini, pengajuan pending, kontrak yang akan habis 30 hari lagi.
- **Karyawan (otoritas 3):** tombol absen masuk/pulang, riwayat absensi sendiri, ajukan izin/cuti + sisa cuti, slip gaji sendiri, ganti password.
- Sidebar dipisah per otoritas (pola `@if otoritas_id` yang sudah ada).

Catatan pelaksanaan Fase 5 ✅ Selesai:

- `DashboardController` memilih view: otoritas 3 → `pages/dashboard/karyawan`, lainnya → `pages/dashboard/admin` (view lama `dashboard.blade.php` dihapus).
- Admin/HR: kartu karyawan aktif (tanpa Super Admin), hadir / telat / izin+sakit+cuti / alpha / belum absen hari ini (belum absen = aktif − baris absensi hari ini; di hari libur angka ini tetap terisi), pengajuan izin & lembur pending, tabel kontrak non-tetap yang habis ≤ 30 hari (termasuk yang sudah lewat tapi masih aktif, maks 10). HR yang punya cabang dapat tombol "Absen Saya".
- Karyawan: absen hari ini + tombol ke `/absen` (absen tetap di halaman itu, tidak diduplikasi), rekap bulan berjalan, sisa cuti, jumlah pengajuan pending, slip final terakhir.
- `slip_gaji` (semua otoritas): daftar & slip milik sendiri, **hanya payroll final** (draft → 404, milik orang lain → 403). Memakai view slip yang sama dengan HR.
- `profile` (semua otoritas): data akun read-only + ganti password (`current_password`, min 8, konfirmasi, harus beda dari yang lama).
- Sidebar: section "Akun" untuk semua (Slip Gaji untuk selain Super Admin, Profil). Menu Jobdesk untuk karyawan masih nonaktif (route jobdesk khusus HR/Admin).
- Paksa ganti password default → tetap di Fase 8.

---

## Fase 6 – Rekrutmen (sudah ada di sidebar)

1. Lowongan: judul, divisi, jabatan, deskripsi, kualifikasi, tanggal tutup, aktif.
2. Landing page publik (tanpa login, pakai `layouts/landing.blade.php`): daftar lowongan + form lamaran (CV upload, validasi `mimes:pdf|max:2048`).
3. Pelamar: daftar per lowongan, status (baru/interview/diterima/ditolak).
4. Pelamar diterima → tombol "Jadikan Pegawai" (prefill form pegawai).

Catatan pelaksanaan Fase 6 ✅ Selesai:

- Route admin (otoritas 1 & 2): `lowongan` (CRUD), `pelamar` (data, `PATCH /pelamar/{id}/status` + catatan internal, `GET /pelamar/{id}/cv`, hapus). Sidebar "Rekrutmen" aktif untuk Admin & HR. Menu "Pengaturan Landing Page" lama **tidak dibuat** (halaman karir cukup statis); tambahkan bila perlu ubah teks/banner tanpa deploy.
- Publik tanpa login: `GET /karir`, `GET /karir/{lowongan}`, `POST /karir/{lowongan}` (throttle 5/menit per IP). Tampil hanya lowongan `aktif` dan `tanggal_tutup` kosong / ≥ hari ini (`Lowongan::dibuka()`); selain itu 404. Link "Lihat lowongan" ada di halaman login. `/` tetap redirect ke login.
- Lamaran: nama, email (unik per lowongan), no HP, pendidikan terakhir, pesan, CV PDF ≤ 2 MB. **CV disimpan di disk `local` (private)**, hanya bisa dibuka HR/Admin; path CV tidak ikut di JSON.
- Lowongan yang sudah punya pelamar tidak bisa dihapus (nonaktifkan). Hapus pelamar ikut menghapus file CV.
- Jadikan Pegawai: tombol muncul untuk pelamar `diterima` yang belum jadi pegawai → `/user?pelamar={id}` membuka form tambah pegawai terisi (nama, email, no HP, divisi & jabatan dari lowongan, tanggal masuk hari ini). `pelamar_id` dikirim hidden dan divalidasi (`diterima` & belum tertaut); setelah simpan `pelamar.user_id` terisi. Pelamar yang sudah jadi pegawai tidak bisa dihapus/diubah status selain `diterima`.
- Belum ada: notifikasi email ke pelamar, captcha (andalkan throttle dulu).
- Seeder: 1 lowongan contoh (Teknisi Audio & Variasi Mobil).

---

## Fase 7 – Kendaraan / Booking (sudah ada di sidebar)

Produk Booking, Booking, Pemasangan. Ini terlihat seperti modul operasional bisnis, bukan HR — **konfirmasi dulu scope-nya** sebelum dikerjakan.

---

## Fase 8 – Hardening & Deploy

- Rate limit login (sudah ada di `LoginRequest`), paksa ganti password default.
- Upload foto/lampiran ke `storage` (disk `public` / private untuk dokumen sensitif) + `php artisan storage:link`.
- Kompres foto absensi sebelum upload (canvas di browser) supaya storage tidak bengkak.
- Backup database harian.
- Cron scheduler + `queue:work` (supervisor) di server.
- `php artisan optimize`, `APP_DEBUG=false`, HTTPS (wajib — geolocation & kamera tidak jalan tanpa HTTPS di HP).

---

## Checklist "Selesai" per Modul

- [ ] Migration + foreign key + index
- [ ] Model + relasi + `$fillable`/`$casts`
- [ ] FormRequest (validasi)
- [ ] Controller (view + JSON) dengan proteksi otoritas
- [ ] View pakai helper yang ada (pagination, search, skeleton, emptyTable, hapus, showModal)
- [ ] Menu sidebar aktif
- [ ] Minimal 1 feature test, `php artisan test` hijau
- [ ] Commit
