@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_description', 'Selamat datang, '.auth()->user()->name.' • '.now()->translatedFormat('l, d F Y'))

@if ($bisaAbsen)
    @push('header-action')
        <a href="{{ route('absen') }}"
            class="hidden items-center gap-2 rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-primary_hover sm:inline-flex">
            <i class="fa-solid fa-fingerprint"></i> Absen Saya
        </a>
    @endpush
@endif

@php
    // [label, nilai, ikon, warna ikon & tag, tag, link]
    $kartu = [
        ['Karyawan Aktif', $totalAktif, 'fa-users', 'bg-slate-100 text-slate-700', 'Total', route('user')],
        ['Hadir Hari Ini', $hariIni['hadir'], 'fa-circle-check', 'bg-emerald-50 text-emerald-700', 'Tepat Waktu', route('absensi_karyawan')],
        ['Telat', $hariIni['telat'], 'fa-clock', 'bg-amber-50 text-amber-700', 'Hari Ini', route('absensi_karyawan')],
        ['Izin / Sakit / Cuti', $hariIni['izin'], 'fa-calendar-day', 'bg-sky-50 text-sky-700', 'Disetujui', route('absensi_karyawan')],
        ['Alpha', $hariIni['alpha'], 'fa-user-xmark', 'bg-red-50 text-red-700', 'Tanpa Keterangan', route('absensi_karyawan')],
        ['Belum Absen', $hariIni['belum'], 'fa-hourglass-half', 'bg-slate-100 text-slate-600', 'Menunggu', route('absensi_karyawan')],
    ];
    $persetujuan = [
        ['Izin, Sakit, Cuti', 'Permohonan absensi', 'fa-calendar-xmark', $izinPending],
        ['Lembur', 'Pengajuan lembur pegawai', 'fa-clock', $lemburPending],
    ];
    $pintasan = [
        ['Master Divisi', $jumlah['divisi'].' divisi terdaftar', 'fa-building-columns', 'bg-red-50 text-red-700', route('divisi')],
        ['Master Jabatan', $jumlah['jabatan'].' jabatan', 'fa-id-badge', 'bg-sky-50 text-sky-700', route('jabatan')],
        ['Master Jobdesk', $jumlah['jobdesk'].' uraian tugas', 'fa-list-check', 'bg-emerald-50 text-emerald-700', route('jobdesk')],
        ['Data Pegawai', $totalAktif.' pegawai aktif', 'fa-users', 'bg-amber-50 text-amber-700', route('user')],
    ];
@endphp

@section('content')
    <div class="space-y-6">

        {{-- KPI --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($kartu as [$label, $nilai, $ikon, $warna, $tag, $url])
                <a href="{{ $url }}"
                    class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-colors hover:border-slate-300">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm {{ $warna }}">
                            <i class="fa-solid {{ $ikon }}"></i>
                        </div>
                        <span class="truncate rounded px-1.5 py-0.5 text-[10px] font-semibold {{ $warna }}">{{ $tag }}</span>
                    </div>
                    <p class="text-3xl font-bold tabular-nums text-slate-800">{{ $nilai }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ $label }}</p>
                </a>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">

            {{-- MENUNGGU PERSETUJUAN --}}
            <div class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-5">
                <div>
                    <div class="flex items-center justify-between border-b border-slate-200 pb-4">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-list-check text-primary"></i>
                            <h3 class="whitespace-nowrap font-semibold text-slate-800">Menunggu Persetujuan</h3>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
                            {{ $izinPending + $lemburPending }} Permohonan
                        </span>
                    </div>

                    <div class="mt-4 space-y-3">
                        @foreach ($persetujuan as [$judul, $sub, $ikon, $jumlahPending])
                            <a href="{{ route('izin_karyawan') }}"
                                class="flex items-center justify-between rounded-lg border border-slate-200/60 bg-slate-50 p-3.5 transition-colors hover:bg-slate-100">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700">
                                        <i class="fa-solid {{ $ikon }} text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-800">{{ $judul }}</p>
                                        <p class="text-xs text-slate-500">{{ $sub }}</p>
                                    </div>
                                </div>
                                <span class="flex h-8 w-8 items-center justify-center rounded-full border text-sm font-bold tabular-nums
                                    {{ $jumlahPending ? 'border-primary bg-primary text-white' : 'border-slate-200 bg-white text-slate-800' }}">
                                    {{ $jumlahPending }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 border-t border-slate-200 pt-4">
                    <a href="{{ route('izin_karyawan') }}" class="flex items-center justify-center gap-1 text-xs font-semibold text-primary hover:underline">
                        Kelola Semua Persetujuan <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            {{-- KONTRAK HABIS --}}
            <div class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-7">
                <div>
                    <div class="flex items-center justify-between gap-3 border-b border-slate-200 pb-4">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-bell text-amber-600"></i>
                            <h3 class="font-semibold text-slate-800">Kontrak Habis ≤ 30 Hari</h3>
                        </div>
                        <span class="hidden text-xs text-slate-500 sm:inline">Pantauan masa kerja pegawai kontrak</span>
                    </div>

                    <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-slate-200 bg-slate-50 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-4 py-2.5">Pegawai</th>
                                    <th class="px-4 py-2.5">Status</th>
                                    <th class="px-4 py-2.5">Akhir Kontrak</th>
                                    <th class="px-4 py-2.5 text-right">Sisa Hari</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse ($kontrak as $p)
                                    @php($sisa = (int) today()->diffInDays($p->tanggal_akhir_kontrak, false))
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-slate-800">{{ $p->name }}</div>
                                            <div class="text-xs text-slate-500">{{ $p->nik ?? '-' }} · {{ $p->jabatan?->nama ?? '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3">{{ ucfirst($p->status_kerja) }}</td>
                                        <td class="whitespace-nowrap px-4 py-3">{{ $p->tanggal_akhir_kontrak->translatedFormat('d M Y') }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold {{ $sisa < 0 ? 'text-red-600' : ($sisa <= 7 ? 'text-amber-600' : 'text-slate-700') }}">
                                            {{ $sisa < 0 ? 'Lewat '.abs($sisa).' hari' : ($sisa === 0 ? 'Hari ini' : $sisa.' hari') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-10 text-center">
                                            <i class="fa-solid fa-circle-check text-3xl text-slate-300"></i>
                                            <p class="mt-2 font-medium text-slate-800">Tidak ada kontrak kerja yang akan habis</p>
                                            <p class="text-xs text-slate-500">Seluruh kontrak pegawai aktif lebih dari 30 hari ke depan.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-between border-t border-slate-200 pt-4 text-xs text-slate-500">
                    <span>Peringatan H-30 aktif</span>
                    <a href="{{ route('user') }}" class="font-semibold text-primary hover:underline">Lihat Pegawai →</a>
                </div>
            </div>
        </div>

        {{-- AKSES CEPAT --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="mb-3 font-semibold text-slate-800">Akses Cepat Master Data</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                @foreach ($pintasan as [$judul, $sub, $ikon, $warna, $url])
                    <a href="{{ $url }}"
                        class="group flex items-center gap-3 rounded-lg border border-slate-200 p-4 transition-all hover:border-primary/50 hover:bg-slate-50">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg transition-transform group-hover:scale-105 {{ $warna }}">
                            <i class="fa-solid {{ $ikon }}"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">{{ $judul }}</p>
                            <p class="text-xs text-slate-500">{{ $sub }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

    </div>
@endsection
