@php
    $otoritas = auth()->user()->otoritas_id;
    $admin = in_array($otoritas, [1, 2]); // Super Admin & HR

    // [route, ikon Font Awesome, label] per section; section kosong tidak ditampilkan
    $menu = [
        'Dashboard' => [['dashboard', 'fa-house', 'Dashboard']],
        'Master Data' => $admin ? array_filter([
            $otoritas === 1 ? ['cabang', 'fa-building', 'Cabang'] : null,
            ['divisi', 'fa-building-columns', 'Divisi'],
            ['jabatan', 'fa-id-badge', 'Jabatan'],
            ['jobdesk', 'fa-list-check', 'Jobdesk'],
            ['user', 'fa-users', 'User / Pegawai'],
        ]) : [],
        'Absensi' => $admin ? array_filter([
            ['pengaturan_absensi', 'fa-gear', 'Pengaturan'],
            ['hari_libur', 'fa-calendar-day', 'Hari Libur'],
            ['lembur', 'fa-clock', 'Perintah Lembur'],
            $otoritas === 2 ? ['absen', 'fa-fingerprint', 'Absen'] : null,
            ['absensi_karyawan', 'fa-calendar-check', 'Input Absensi'],
            ['izin_karyawan', 'fa-calendar-xmark', 'Izin, Sakit, Cuti'],
            ['rekap_absensi', 'fa-clipboard-list', 'Rekap Absensi'],
        ]) : [
            ['absen', 'fa-fingerprint', 'Absen'],
            ['lembur', 'fa-clock', 'Lembur'],
            ['izin_karyawan', 'fa-calendar-xmark', 'Izin, Sakit, Cuti'],
        ],
        'Payroll' => $admin ? [
            ['komponen_gaji', 'fa-money-bills', 'Komponen Gaji'],
            ['gaji_pegawai', 'fa-money-check-dollar', 'Gaji Pegawai'],
            ['pengaturan_payroll', 'fa-sliders', 'Pengaturan Payroll'],
            ['payroll', 'fa-wallet', 'Payroll'],
        ] : [],
        'Rekrutmen' => $admin ? [
            ['lowongan', 'fa-briefcase', 'Lowongan Kerja'],
            ['pelamar', 'fa-user-tie', 'Data Pelamar'],
        ] : [],
        'Akun' => array_filter([
            $otoritas !== 1 ? ['slip_gaji', 'fa-file-invoice-dollar', 'Slip Gaji'] : null,
            ['profile', 'fa-user', 'Profil'],
        ]),
    ];
@endphp

<div class="flex h-full flex-col">

    {{-- BRAND --}}
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 px-6">
        <img src="{{ asset('images/logo.png') }}" alt="" class="h-10 w-10 rounded-xl shadow-sm">
        <div>
            <h2 class="font-bold tracking-tight text-slate-800">HUMORA</h2>
            <p class="text-xs text-slate-500">Sistem HR</p>
        </div>
    </div>

    {{-- MENU --}}
    <nav class="flex-1 space-y-6 overflow-y-auto p-4">
        @foreach ($menu as $judul => $items)
            @continue(empty($items))
            <div>
                <p class="mb-2 px-3 text-[11px] font-bold uppercase tracking-wider text-slate-500">{{ $judul }}</p>
                <div class="space-y-1">
                    @foreach ($items as [$route, $ikon, $label])
                        {{-- "route.*" bukan "route*": supaya "absen" tidak ikut aktif di "absensi_karyawan" --}}
                        @php($aktif = request()->routeIs($route, $route.'.*'))
                        <a href="{{ route($route) }}" @if ($aktif) aria-current="page" @endif
                            class="flex items-center gap-3 rounded-lg px-3.5 py-2.5 text-sm transition-colors
                                {{ $aktif ? 'bg-primary font-semibold text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100' }}">
                            <i class="fa-solid {{ $ikon }} w-5 text-center {{ $aktif ? '' : 'text-slate-500' }}"></i>
                            <span>{{ $label }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    {{-- LOGOUT --}}
    <div class="shrink-0 border-t border-slate-200 p-4">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button
                class="flex w-full items-center justify-center gap-2 rounded-lg border border-primary/20 px-4 py-2.5 text-sm font-semibold text-primary transition-colors hover:bg-red-50">
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </button>
        </form>
    </div>

</div>
