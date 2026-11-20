@php
    $rp = fn ($v) => 'Rp. '.number_format((float) $v, 0, ',', '.');
    $periode = \Carbon\Carbon::createFromFormat('Y-m-d', $payroll->periode.'-01')->translatedFormat('F Y');
    $tunjangan = collect($d->rincian)->where('tipe', 'tunjangan');
    $potongan = collect($d->rincian)->where('tipe', 'potongan');
    $ket = fn ($k) => $k['sifat'] === 'per_hari_hadir' ? ' ('.$d->hari_hadir.' hari × '.number_format($k['nominal'], 0, ',', '.').')' : '';
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Slip Gaji {{ $d->nama }} – {{ $periode }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .slip { box-shadow: none !important; border: 0 !important; margin: 0 !important; }
        }
        @page { size: A4; margin: 15mm; }
    </style>
</head>

<body class="bg-slate-100 py-8 text-sm text-slate-800">
    <div class="no-print mx-auto mb-4 flex max-w-2xl justify-end">
        <button onclick="window.print()" class="rounded-xl bg-primary px-4 py-2.5 font-medium text-white hover:bg-primary_hover">
            Cetak / Simpan PDF
        </button>
    </div>

    <div class="slip mx-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="flex items-start justify-between border-b border-slate-200 pb-4">
            <div>
                <h1 class="text-xl font-bold">HUMORA</h1>
                <p class="text-slate-500">Slip Gaji – {{ $periode }}</p>
            </div>
            <div class="text-right text-xs text-slate-500">
                Periode absensi<br>
                {{ $payroll->tanggal_mulai->format('d/m/Y') }} – {{ $payroll->tanggal_selesai->format('d/m/Y') }}
                @unless ($payroll->isFinal())
                    <p class="mt-1 font-semibold text-amber-600">DRAFT</p>
                @endunless
            </div>
        </div>

        <table class="mt-4 w-full">
            <tr><td class="w-32 py-0.5 text-slate-500">Nama</td><td>{{ $d->nama }}</td>
                <td class="w-32 text-slate-500">Hari kerja</td><td>{{ $d->hari_kerja }}</td></tr>
            <tr><td class="py-0.5 text-slate-500">NIK</td><td>{{ $d->nik ?? '-' }}</td>
                <td class="text-slate-500">Hadir</td><td>{{ $d->hari_hadir }}</td></tr>
            <tr><td class="py-0.5 text-slate-500">Jabatan</td><td>{{ $d->jabatan ?? '-' }}</td>
                <td class="text-slate-500">Telat</td><td>{{ $d->menit_telat }} menit</td></tr>
            <tr><td class="py-0.5 text-slate-500">Cabang</td><td>{{ $d->cabang ?? '-' }}</td>
                <td class="text-slate-500">Alpha</td><td>{{ $d->jumlah_alpha }} hari</td></tr>
            <tr><td class="py-0.5 text-slate-500">Rekening</td><td>{{ trim(($d->bank ?? '').' '.($d->no_rekening ?? '')) ?: '-' }}</td>
                <td class="text-slate-500">Lembur</td><td>{{ intdiv($d->menit_lembur, 60) }} jam {{ $d->menit_lembur % 60 }} menit</td></tr>
        </table>

        <div class="mt-6 grid gap-6 md:grid-cols-2 print:grid-cols-2">
            <div>
                <h2 class="mb-2 border-b border-slate-200 pb-1 font-semibold text-emerald-700">Pendapatan</h2>
                <table class="w-full">
                    <tr><td class="py-0.5">Gaji Pokok</td><td class="text-right">{{ $rp($d->gaji_pokok) }}</td></tr>
                    <tr><td class="py-0.5">Tunjangan Jabatan</td><td class="text-right">{{ $rp($d->tunjangan_jabatan) }}</td></tr>
                    @foreach ($tunjangan as $k)
                        <tr><td class="py-0.5">{{ $k['nama'] }}<span class="text-xs text-slate-400">{{ $ket($k) }}</span></td><td class="text-right">{{ $rp($k['jumlah']) }}</td></tr>
                    @endforeach
                    <tr><td class="py-0.5">Upah Lembur</td><td class="text-right">{{ $rp($d->upah_lembur) }}</td></tr>
                </table>
            </div>
            <div>
                <h2 class="mb-2 border-b border-slate-200 pb-1 font-semibold text-red-700">Potongan</h2>
                <table class="w-full">
                    <tr><td class="py-0.5">Telat</td><td class="text-right">{{ $rp($d->potongan_telat) }}</td></tr>
                    <tr><td class="py-0.5">Alpha</td><td class="text-right">{{ $rp($d->potongan_alpha) }}</td></tr>
                    @foreach ($potongan as $k)
                        <tr><td class="py-0.5">{{ $k['nama'] }}<span class="text-xs text-slate-400">{{ $ket($k) }}</span></td><td class="text-right">{{ $rp($k['jumlah']) }}</td></tr>
                    @endforeach
                </table>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-between rounded-xl bg-slate-100 px-4 py-3">
            <span class="font-semibold">Gaji Bersih (Take Home Pay)</span>
            <span class="text-lg font-bold">{{ $rp($d->gaji_bersih) }}</span>
        </div>

        <p class="mt-8 text-xs text-slate-400">Dokumen ini dibuat otomatis oleh HUMORA.</p>
    </div>
</body>

</html>
