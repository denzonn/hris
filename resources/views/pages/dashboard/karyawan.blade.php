@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_description', 'Halo, '.auth()->user()->name.' · '.now()->translatedFormat('l, d F Y'))

@php
    $jam = fn ($v) => $v ? substr($v, 0, 5) : '-';
    $rekap = [
        ['Hadir', ($bulanIni['hadir'] ?? 0) + ($bulanIni['telat'] ?? 0), 'text-emerald-600'],
        ['Telat', $bulanIni['telat'] ?? 0, 'text-amber-600'],
        ['Izin / Sakit / Cuti', ($bulanIni['izin'] ?? 0) + ($bulanIni['sakit'] ?? 0) + ($bulanIni['cuti'] ?? 0), 'text-blue-600'],
        ['Alpha', $bulanIni['alpha'] ?? 0, 'text-red-600'],
    ];
@endphp

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <h3 class="font-semibold text-slate-800">Absensi Hari Ini</h3>
            @if ($pengaturan)
                <p class="mt-1 text-sm text-slate-500">Jadwal {{ $jam($pengaturan->jam_masuk) }} – {{ $jam($pengaturan->jam_pulang) }}</p>
            @endif

            <div class="mt-4 grid grid-cols-2 gap-3 text-center">
                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="text-xs text-slate-500">Masuk</p>
                    <p class="text-lg font-semibold text-slate-800">{{ $jam($hariIni?->jam_masuk) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="text-xs text-slate-500">Pulang</p>
                    <p class="text-lg font-semibold text-slate-800">{{ $jam($hariIni?->jam_pulang) }}</p>
                </div>
            </div>
            @if ($hariIni)
                <p class="mt-3 text-sm text-slate-600">Status: <b>{{ ucfirst($hariIni->status) }}</b>
                    @if ($hariIni->menit_telat) <span class="text-amber-600">({{ $hariIni->menit_telat }} menit)</span> @endif
                </p>
            @endif

            <a href="{{ route('absen') }}" class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3 font-semibold text-white hover:bg-primary_hover">
                <i class="fa-solid fa-fingerprint"></i>
                {{ ! $hariIni ? 'Absen Masuk' : ($hariIni->jam_masuk && ! $hariIni->jam_pulang ? 'Absen Pulang' : 'Lihat Absensi') }}
            </a>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 lg:col-span-2">
            <h3 class="font-semibold text-slate-800">Bulan {{ now()->translatedFormat('F Y') }}</h3>
            <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($rekap as [$label, $nilai, $warna])
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-2xl font-bold {{ $warna }}">{{ $nilai }}</p>
                        <p class="text-sm text-slate-500">{{ $label }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <a href="{{ route('izin_karyawan') }}" class="rounded-xl border border-slate-200 p-4 hover:bg-slate-50">
                    <p class="text-sm text-slate-500"><i class="fa-solid fa-umbrella-beach mr-1"></i> Sisa Cuti</p>
                    <p class="mt-1 text-xl font-bold text-slate-800">{{ $sisaCuti }} hari</p>
                </a>
                <a href="{{ route('izin_karyawan') }}" class="rounded-xl border border-slate-200 p-4 hover:bg-slate-50">
                    <p class="text-sm text-slate-500"><i class="fa-solid fa-hourglass-half mr-1"></i> Pengajuan Pending</p>
                    <p class="mt-1 text-xl font-bold text-slate-800">{{ $pending }}</p>
                </a>
                <a href="{{ $slipTerakhir ? route('slip_gaji.show', $slipTerakhir) : route('slip_gaji') }}" class="rounded-xl border border-slate-200 p-4 hover:bg-slate-50">
                    <p class="text-sm text-slate-500"><i class="fa-solid fa-wallet mr-1"></i> Slip Terakhir</p>
                    <p class="mt-1 text-xl font-bold text-slate-800">
                        {{ $slipTerakhir ? \Carbon\Carbon::createFromFormat('Y-m-d', $slipTerakhir->payroll->periode.'-01')->translatedFormat('F Y') : '-' }}
                    </p>
                </a>
            </div>
        </div>
    </div>
@endsection
