# CLAUDE.md

HRIS (absensi, payroll, master pegawai, rekrutmen) untuk **Acas Variasi**. Bahasa UI & penamaan domain: **Bahasa Indonesia**.

## Dokumen

- [docs/ROADMAP.md](docs/ROADMAP.md) — fase & step pembuatan. Kerjakan berurutan, update checklist saat selesai.
- `docs/` — semua dokumen project (spesifikasi modul, rumus payroll, dll) disimpan di sini.

## Stack

- Laravel 12, PHP 8.2+, MySQL (Laragon), Pest
- Blade + Tailwind 3 (warna: `primary` #B91C1C, `primary_hover`, `secondary`) + Font Awesome 6
- jQuery + AJAX, SweetAlert2, Select2, Flatpickr (via CDN di `resources/views/includes/script.blade.php`)
- **Tidak pakai** Livewire / Inertia / Vue / React. Jangan tambah package baru tanpa alasan kuat.

## Perintah

```bash
composer dev          # serve + queue + logs + vite
php artisan test      # Pest
php artisan migrate:fresh --seed
./vendor/bin/pint     # format PHP
```

## Pola CRUD (wajib diikuti semua modul)

| Route | Method | Return |
|-------|--------|--------|
| `/{modul}` | GET | view Blade |
| `/{modul}/data` | GET | JSON `paginate()` (support `search`, filter) |
| `/{modul}` | POST | JSON `{message}` |
| `/{modul}/{id}` | PUT | JSON `{message}` |
| `/{modul}/{id}` | DELETE | JSON `{message}` |

- Validasi selalu via `FormRequest`.
- Nama route = nama modul snake_case (`absensi_karyawan`, `rekap_absensi`) — sidebar pakai `request()->routeIs('modul*')`.
- Proteksi akses via middleware otoritas (`users.otoritas_id`: 1 Super Admin, 2 HR/Admin Cabang, 3 Karyawan).
- Pegawai & data yang punya riwayat **tidak di-hard delete** (pakai `aktif` / SoftDeletes). Pola: route `PATCH /{modul}/{id}/aktif` toggle kolom `aktif`.
- Hapus master yang masih dipakai data lain → cek relasi di controller, balas JSON 422 `{message}` (jangan andalkan error FK).

## Helper JS global (pakai, jangan buat ulang)

Ada di `resources/js/helpers/*` dan `resources/js/app.js`:

- Request & tabel: `ajaxRequest`, `renderPagination(res, callback)`, `renderInfo`, `loadingSkeleton`, `emptyTable`, `tableError`, `refreshData`, `debounceSearch`, `hapusData({url, name, reload})`
- Modal & notifikasi: `showModal(id)`, `closeModal(id)`, `showToast`, `App.loading.show/hide`
- Format: `formatRupiah`, `formatAngka`, `toNumber`, `getNominal`, `formatTanggal`, `formatTanggalIndo`, `formatTanggalJam`, `formatJam`, `formatDurasi`, `formatBadgeStatus`
- Input: `initDateRangePicker` (Flatpickr), Select2 helper
- Blade component: `<x-input>`, `<x-select name label placeholder>` (option lewat slot); keduanya punya `<p data-error="name">` untuk pesan 422
- Upload file: kirim `new FormData(form)` via `ajaxRequest({..., processData: false, contentType: false})`, update pakai `POST` + `_method=PUT`
- Uang di input: tampil `formatRupiah(v, false)` saat ketik, kirim `getNominal(v)`

## Aturan domain

- Timezone `Asia/Jakarta`. Semua waktu absensi dihitung di server, bukan dari jam HP.
- Validasi lokasi absensi (radius cabang, haversine) **di server**.
- Uang: `decimal(15,2)` / integer rupiah, jangan `float`.
- Payroll menyimpan **snapshot** angka di `payroll_detail`; periode `final` tidak boleh diubah.
- Logika hitungan (telat, lembur, payroll) wajib punya Pest test dengan angka hitung manual.

## Layout

- `layouts/app.blade.php` (admin, sidebar `includes/sidebar.blade.php`), `layouts/landing.blade.php` (publik/rekrutmen)
- Halaman admin: `@section('title')`, `@section('page_title')`, `@section('page_description')`, `@section('content')`, `@push('addon-style')`, `@push('addon-script')`.
