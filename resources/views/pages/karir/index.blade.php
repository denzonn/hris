@extends('layouts.landing')

@section('title', 'Karir – HUMORA')

@section('content')
    <div class="min-h-screen bg-slate-50">
        <header class="bg-gradient-to-br from-[#620E0E] via-[#8F1111] to-[#B91C1C] text-white">
            <div class="mx-auto max-w-5xl px-6 py-14">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold uppercase tracking-widest text-white/70">HUMORA</p>
                    <a href="{{ route('login') }}" class="text-sm text-white/80 hover:text-white">Login Karyawan</a>
                </div>
                <h1 class="mt-6 text-3xl font-bold md:text-4xl">Bergabung bersama kami</h1>
                <p class="mt-2 max-w-xl text-white/80">Lihat lowongan yang sedang dibuka dan kirim lamaran Anda secara online.</p>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-6 py-10">
            <h2 class="text-lg font-semibold text-slate-800">Lowongan Tersedia ({{ $lowongan->count() }})</h2>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                @forelse ($lowongan as $l)
                    <a href="{{ route('karir.show', $l) }}" class="rounded-2xl border border-slate-200 bg-white p-6 transition hover:border-primary hover:shadow-md">
                        <h3 class="text-lg font-semibold text-slate-800">{{ $l->judul }}</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            <i class="fa-solid fa-building-columns mr-1"></i> {{ $l->divisi?->nama ?? 'Umum' }}
                            @if ($l->tanggal_tutup)
                                · <i class="fa-regular fa-calendar mr-1"></i> Tutup {{ $l->tanggal_tutup->translatedFormat('d M Y') }}
                            @endif
                        </p>
                        <p class="mt-3 line-clamp-3 text-sm text-slate-600">{{ $l->deskripsi }}</p>
                        <p class="mt-4 text-sm font-semibold text-primary">Lihat & Lamar <i class="fa-solid fa-arrow-right ml-1"></i></p>
                    </a>
                @empty
                    <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-500 md:col-span-2">
                        <i class="fa-solid fa-briefcase text-4xl text-slate-300"></i>
                        <p class="mt-3">Belum ada lowongan yang dibuka saat ini.</p>
                    </div>
                @endforelse
            </div>
        </main>
    </div>
@endsection
