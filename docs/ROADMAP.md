# Roadmap HRIS – Acas Variasi

Stack: Laravel 12 · Blade · Tailwind · jQuery (AJAX + helper di `resources/js/helpers`) · SweetAlert2 · Select2 · Flatpickr · MySQL · Pest.

Urutan fase mengikuti dependensi: **Fondasi → Master → Pengaturan → Absensi → Payroll → Laporan → Modul tambahan**.
Jangan lompat fase. Payroll butuh absensi, absensi butuh master + pengaturan.

---

## Fase 0 – Perbaikan Base Project (WAJIB sebelum fitur)

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

---

## Fase 1 – Master Data

| Step | Tabel | Kolom utama | Catatan |
|------|-------|-------------|---------|
| 1.1 | `cabang` | nama, alamat, latitude, longitude, radius_meter | lat/long/radius dipakai validasi lokasi absensi |
| 1.2 | `divisi` | nama | |
| 1.3 | `jabatan` | nama, divisi_id, tunjangan_jabatan | |
| 1.4 | `jobdesk` | jabatan_id, deskripsi | |
| 1.5 | `users` (Pegawai) | + nik, cabang_id, divisi_id, jabatan_id, no_hp, alamat, tanggal_masuk, status_kerja (tetap/kontrak/magang), tanggal_akhir_kontrak, gaji_pokok, bank, no_rekening, npwp, status_ptkp, foto, aktif | Satu tabel `users` = akun + data pegawai (cukup untuk skala UKM). Pisah tabel kalau nanti ada pegawai tanpa akun login |

Per step: migration → model + relasi → FormRequest → controller (pola Fase 0.7) → view → route → aktifkan menu sidebar → 1 feature test (CRUD jalan + karyawan biasa ditolak).

Catatan: **Pegawai tidak boleh di-hard delete** (riwayat absensi & payroll ikut hilang) → pakai kolom `aktif` / SoftDeletes.

---

## Fase 2 – Pengaturan Absensi

| Step | Fitur | Tabel | Isi |
|------|-------|-------|-----|
| 2.1 | Pengaturan jam kerja | `pengaturan_absensi` (per cabang) | jam_masuk, jam_pulang, toleransi_telat_menit, hari_kerja (Senin–Sabtu, simpan JSON), wajib_foto, wajib_lokasi |
| 2.2 | Hari Libur | `hari_libur` | tanggal (unique), keterangan |
| 2.3 | Perintah Lembur | `lembur` | user_id, tanggal, jam_mulai, jam_selesai, keterangan, status (pending/disetujui/ditolak), disetujui_oleh |

Mulai dengan **1 jadwal per cabang**. Shift (pagi/malam/rotasi) baru ditambah kalau memang ada karyawan shift.

---

## Fase 3 – Absensi

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

---

## Fase 4 – Payroll

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

---

## Fase 5 – Dashboard & Portal Karyawan

- **Admin/HR:** total karyawan aktif, hadir / telat / izin / alpha hari ini, pengajuan pending, kontrak yang akan habis 30 hari lagi.
- **Karyawan (otoritas 3):** tombol absen masuk/pulang, riwayat absensi sendiri, ajukan izin/cuti + sisa cuti, slip gaji sendiri, ganti password.
- Sidebar dipisah per otoritas (pola `@if otoritas_id` yang sudah ada).

---

## Fase 6 – Rekrutmen (sudah ada di sidebar)

1. Lowongan: judul, divisi, jabatan, deskripsi, kualifikasi, tanggal tutup, aktif.
2. Landing page publik (tanpa login, pakai `layouts/landing.blade.php`): daftar lowongan + form lamaran (CV upload, validasi `mimes:pdf|max:2048`).
3. Pelamar: daftar per lowongan, status (baru/interview/diterima/ditolak).
4. Pelamar diterima → tombol "Jadikan Pegawai" (prefill form pegawai).

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
