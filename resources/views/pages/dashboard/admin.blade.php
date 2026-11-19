@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_description', 'Selamat datang, '.auth()->user()->name.' · '.now()->translatedFormat('l, d F Y'))

@php
    $kartu = [
        ['Karyawan Aktif', $totalAktif, 'fa-users', 'bg-slate-100 text-slate-600', route('user')],
        ['Hadir Hari Ini', $hariIni['hadir'], 'fa-circle-check', 'bg-emerald-50 text-emerald-600', route('absensi_karyawan')],
        ['Telat', $hariIni['telat'], 'fa-clock', 'bg-amber-50 text-amber-600', route('absensi_karyawan')],
        ['Izin / Sakit / Cuti', $hariIni['izin'], 'fa-calendar-xmark', 'bg-blue-50 text-blue-600', route('absensi_karyawan')],
        ['Alpha', $hariIni['alpha'], 'fa-user-xmark', 'bg-red-50 text-red-600', route('absensi_karyawan')],
        ['Belum Absen', $hariIni['belum'], 'fa-hourglass-half', 'bg-slate-100 text-slate-500', route('absensi_karyawan')],
    ];
@endphp

@section('content')
    @if ($bisaAbsen)
        <div class="mb-6 flex justify-end">
            <a href="{{ route('absen') }}" class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary_hover">
                <i class="fa-solid fa-fingerprint"></i> Absen Saya
            </a>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
        @foreach ($kartu as [$label, $nilai, $ikon, $warna, $url])
            <a href="{{ $url }}" class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:shadow-md">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl {{ $warna }}">
                    <i class="fa-solid {{ $ikon }}"></i>
                </div>
                <p class="mt-4 text-2xl font-bold text-slate-800">{{ $nilai }}</p>
                <p class="text-sm text-slate-500">{{ $label }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6">
            <h3 class="font-semibold text-slate-800">Menunggu Persetujuan</h3>
            <a href="{{ route('izin_karyawan') }}" class="flex items-center justify-between rounded-xl bg-slate-50 p-4 hover:bg-slate-100">
                <span class="text-sm text-slate-600"><i class="fa-solid fa-calendar-xmark mr-2"></i> Izin, Sakit, Cuti</span>
                <span class="rounded-lg {{ $izinPending ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-500' }} px-3 py-1 text-sm font-semibold">{{ $izinPending }}</span>
            </a>
            <a href="{{ route('lembur') }}" class="flex items-center justify-between rounded-xl bg-slate-50 p-4 hover:bg-slate-100">
                <span class="text-sm text-slate-600"><i class="fa-solid fa-clock mr-2"></i> Lembur</span>
                <span class="rounded-lg {{ $lemburPending ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-500' }} px-3 py-1 text-sm font-semibold">{{ $lemburPending }}</span>
            </a>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white lg:col-span-2">
            <div class="border-b border-slate-100 p-5">
                <h3 class="font-semibold text-slate-800">Kontrak Habis ≤ 30 Hari</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-6 py-3">Pegawai</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Akhir Kontrak</th>
                            <th class="px-6 py-3">Sisa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($kontrak as $p)
                            @php($sisa = (int) today()->diffInDays($p->tanggal_akhir_kontrak, false))
                            <tr>
                                <td class="px-6 py-3">
                                    <div class="font-semibold">{{ $p->name }}</div>
                                    <div class="text-xs text-slate-400">{{ $p->nik ?? '-' }} · {{ $p->jabatan?->nama ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-3">{{ ucfirst($p->status_kerja) }}</td>
                                <td class="px-6 py-3">{{ $p->tanggal_akhir_kontrak->translatedFormat('d M Y') }}</td>
                                <td class="px-6 py-3 font-semibold {{ $sisa < 0 ? 'text-red-600' : ($sisa <= 7 ? 'text-amber-600' : 'text-slate-700') }}">
                                    {{ $sisa < 0 ? 'Lewat '.abs($sisa).' hari' : ($sisa === 0 ? 'Hari ini' : $sisa.' hari') }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-8 text-center text-slate-400">Tidak ada kontrak yang akan habis.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
