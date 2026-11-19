@extends('layouts.app')

@section('title', 'Absen')
@section('page_title', 'Absen')
@section('page_description', 'Absen masuk & pulang serta riwayat absensi Anda')

@php($jam = fn ($v) => $v ? substr($v, 0, 5) : '-')

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 lg:col-span-1">
            <p class="text-sm text-slate-500">{{ now()->translatedFormat('l, d F Y') }}</p>
            <p id="jamSekarang" class="mt-1 text-4xl font-bold text-slate-800">{{ now()->format('H:i') }}</p>

            @if (! $pengaturan)
                <div class="mt-6 rounded-xl bg-amber-50 p-4 text-sm text-amber-700">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                    {{ auth()->user()->cabang_id ? 'Pengaturan absensi cabang Anda belum dibuat. Hubungi HR.' : 'Akun Anda belum terdaftar di cabang mana pun.' }}
                </div>
            @else
                <p class="mt-4 text-sm text-slate-600">
                    Jadwal: <b>{{ $jam($pengaturan->jam_masuk) }} – {{ $jam($pengaturan->jam_pulang) }}</b>
                    (toleransi {{ $pengaturan->toleransi_telat_menit }} menit)
                </p>

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
                    <p class="mt-3 text-sm text-slate-600">Status: <span id="statusHariIni"></span>
                        @if ($hariIni->menit_telat) <span class="text-amber-600">({{ $hariIni->menit_telat }} menit)</span> @endif
                    </p>
                @endif

                @php($aksi = ! $hariIni ? 'masuk' : ($hariIni->jam_masuk && ! $hariIni->jam_pulang ? 'pulang' : null))

                @if ($aksi)
                    <div class="mt-6 space-y-4">
                        @if ($pengaturan->wajib_foto)
                            <div>
                                <label for="foto" class="mb-1.5 block text-sm font-medium text-slate-700">Foto Selfie</label>
                                <input type="file" id="foto" accept="image/*" capture="user"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-700 file:mr-3 file:rounded-l-xl file:border-0 file:bg-slate-200 file:px-4 file:py-2.5">
                            </div>
                        @endif
                        @if ($pengaturan->wajib_lokasi)
                            <p class="text-xs text-slate-500"><i class="fa-solid fa-location-dot mr-1"></i> Lokasi Anda akan dicek terhadap radius cabang.</p>
                        @endif
                        <button type="button" id="btnAbsen" data-url="{{ route('absen.'.$aksi) }}"
                            class="w-full rounded-xl bg-primary py-3 font-semibold text-white hover:bg-primary_hover">
                            <i class="fa-solid {{ $aksi === 'masuk' ? 'fa-right-to-bracket' : 'fa-right-from-bracket' }} mr-2"></i>
                            Absen {{ ucfirst($aksi) }}
                        </button>
                    </div>
                @elseif ($hariIni?->jam_pulang)
                    <p class="mt-6 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-700"><i class="fa-solid fa-circle-check mr-1"></i> Absensi hari ini sudah lengkap.</p>
                @endif
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white lg:col-span-2">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 p-5">
                <h3 class="font-semibold text-slate-800">Riwayat Absensi</h3>
                <input type="month" id="filter_bulan" value="{{ now()->format('Y-m') }}"
                    class="rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-700 focus:border-primary focus:ring-primary">
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-6 py-4">Tanggal</th>
                            <th class="px-6 py-4">Masuk</th>
                            <th class="px-6 py-4">Pulang</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody id="tableData" class="divide-y divide-slate-100 text-slate-700"></tbody>
                </table>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-100 p-5 md:flex-row md:items-center md:justify-between">
                <div id="info" class="text-sm text-slate-500"></div>
                <div id="pagination" class="flex flex-wrap gap-2"></div>
            </div>
        </div>
    </div>
@endsection

@push('addon-script')
    <script>
        const wajibLokasi = @json((bool) $pengaturan?->wajib_lokasi);
        const esc = (v) => $('<div>').text(v ?? '').html();
        const jam = (v) => v ? String(v).slice(0, 5) : '-';

        function loadData(page = 1) {
            ajaxRequest({
                url: @json(route('absen.data')),
                data: { page, bulan: $('#filter_bulan').val() },
                before: () => loadingSkeleton('#tableData', 5, skeleton.table),
                success(res) {
                    $('#tableData').html(!res.data.length ? emptyTable(5, 'Belum ada absensi', 'Tidak ada data pada bulan ini.')
                        : res.data.map((item) => `
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4">${formatTanggalIndo(item.tanggal)}</td>
                                <td class="px-6 py-4">${jam(item.jam_masuk)}</td>
                                <td class="px-6 py-4">${jam(item.jam_pulang)}</td>
                                <td class="px-6 py-4">${formatBadgeStatus(capitalize(item.status))}
                                    ${item.menit_telat ? `<div class="mt-1 text-xs text-amber-600">${formatDurasi(item.menit_telat)}</div>` : ''}</td>
                                <td class="px-6 py-4">${esc(item.keterangan ?? '-')}</td>
                            </tr>`).join(''));
                    renderInfo('#info', res);
                    renderPagination(res, 'loadData');
                },
                error: () => $('#tableData').html(tableError(5)),
            });
        }

        function gagal(text) {
            App.loading.hide();
            Swal.fire({ icon: 'error', title: 'Gagal', text });
        }

        // Perkecil foto kamera HP (bisa 3–8 MB) jadi JPEG maks 1280px supaya upload cepat & storage hemat.
        function kompresFoto(file, maks = 1280, kualitas = 0.7) {
            return new Promise((resolve) => {
                const img = new Image();
                img.onload = () => {
                    const skala = Math.min(1, maks / Math.max(img.width, img.height));
                    const canvas = document.createElement('canvas');
                    canvas.width = Math.round(img.width * skala);
                    canvas.height = Math.round(img.height * skala);
                    canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                    URL.revokeObjectURL(img.src);
                    canvas.toBlob((blob) => resolve(blob ?? file), 'image/jpeg', kualitas);
                };
                img.onerror = () => resolve(file); // format tak terbaca browser → kirim asli, server tetap memvalidasi
                img.src = URL.createObjectURL(file);
            });
        }

        async function kirimAbsen(url) {
            const fd = new FormData();
            fd.append('_token', $('meta[name="csrf-token"]').attr('content'));
            const foto = $('#foto')[0]?.files[0];
            if (foto) fd.append('foto', await kompresFoto(foto), 'foto.jpg');

            const kirim = () => ajaxRequest({
                url, type: 'POST', data: fd, processData: false, contentType: false,
                success(res) {
                    App.loading.hide();
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message }).then(() => location.reload());
                },
                error(xhr) {
                    const errors = xhr.responseJSON?.errors;
                    gagal(errors ? Object.values(errors)[0][0] : (xhr.responseJSON?.message ?? 'Terjadi kesalahan.'));
                },
            });

            App.loading.show();
            if (!wajibLokasi) return kirim();
            if (!navigator.geolocation) return gagal('Browser tidak mendukung lokasi.');

            navigator.geolocation.getCurrentPosition((pos) => {
                fd.append('latitude', pos.coords.latitude);
                fd.append('longitude', pos.coords.longitude);
                kirim();
            }, () => gagal('Lokasi tidak bisa diambil. Izinkan akses lokasi lalu coba lagi.'), { enableHighAccuracy: true, timeout: 15000 });
        }

        $(function() {
            loadData();
            $('#filter_bulan').on('change', () => loadData());
            $('#statusHariIni').html(formatBadgeStatus(capitalize(@json($hariIni?->status ?? ''))));
            $('#btnAbsen').on('click', function() { kirimAbsen($(this).data('url')); });
            setInterval(() => $('#jamSekarang').text(new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })), 10000);
        });
    </script>
@endpush
