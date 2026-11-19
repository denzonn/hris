@extends('layouts.app')

@php($namaPeriode = \Carbon\Carbon::createFromFormat('Y-m-d', $payroll->periode.'-01')->translatedFormat('F Y'))

@section('title', 'Payroll '.$namaPeriode)
@section('page_title', 'Payroll '.$namaPeriode)
@section('page_description', 'Absensi '.$payroll->tanggal_mulai->translatedFormat('d M Y').' – '.$payroll->tanggal_selesai->translatedFormat('d M Y'))

@section('content')
    <div class="mb-6 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-wrap items-center gap-6">
            <a href="{{ route('payroll') }}" class="text-sm text-slate-500 hover:text-primary"><i class="fa-solid fa-arrow-left mr-1"></i> Kembali</a>
            <div>
                <p class="text-xs text-slate-500">Status</p>
                @if ($payroll->isFinal())
                    <p class="font-semibold text-emerald-600"><i class="fa-solid fa-lock"></i> Final</p>
                    <p class="text-xs text-slate-400">oleh {{ $payroll->penutup?->name ?? '-' }}, {{ $payroll->difinalkan_pada?->translatedFormat('d M Y H:i') }}</p>
                @else
                    <p class="font-semibold text-amber-600">Draft</p>
                @endif
            </div>
            <div>
                <p class="text-xs text-slate-500">Total Gaji Bersih</p>
                <p class="text-lg font-bold text-slate-800">Rp. {{ number_format((float) $payroll->total_gaji, 0, ',', '.') }}</p>
            </div>
        </div>

        @unless ($payroll->isFinal())
            <div class="flex gap-2">
                <button type="button" id="btnGenerate"
                    class="flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-100">
                    <i class="fa-solid fa-rotate"></i> Generate Ulang
                </button>
                <button type="button" id="btnFinal"
                    class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary_hover">
                    <i class="fa-solid fa-lock"></i> Finalkan
                </button>
            </div>
        @endunless
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 p-5">
            <div class="relative w-full md:w-80">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" id="search" placeholder="Cari nama / NIK..."
                    class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-11 text-sm focus:border-primary focus:ring-primary">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-4">Pegawai</th>
                        <th class="px-4 py-4 text-center">Hadir</th>
                        <th class="px-4 py-4 text-center">Telat</th>
                        <th class="px-4 py-4 text-center">Alpha</th>
                        <th class="px-4 py-4 text-right">Gaji + Tunjangan</th>
                        <th class="px-4 py-4 text-right">Lembur</th>
                        <th class="px-4 py-4 text-right">Potongan</th>
                        <th class="px-4 py-4 text-right">Gaji Bersih</th>
                        <th class="px-4 py-4 text-center">Slip</th>
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
@endsection

@push('addon-script')
    <script>
        const payrollUrl = @json(route('payroll.show', $payroll));
        const token = $('meta[name="csrf-token"]').attr('content');
        const esc = (v) => $('<div>').text(v ?? '').html();
        const rp = (v) => formatRupiah(Math.round(v ?? 0));

        function loadData(page = 1) {
            ajaxRequest({
                url: @json(route('payroll.detail', $payroll)),
                data: { page, search: $('#search').val() },
                before: () => loadingSkeleton('#tableData', 9, skeleton.table),
                success(res) {
                    $('#tableData').html(!res.data.length ? emptyTable(9, 'Belum ada data', 'Klik Generate Ulang.')
                        : res.data.map((d) => {
                            const potongan = Number(d.potongan_telat) + Number(d.potongan_alpha) + Number(d.total_potongan);
                            return `
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-4">
                                        <div class="font-semibold">${esc(d.nama)}</div>
                                        <div class="text-xs text-slate-400">${esc(d.nik ?? '-')} · ${esc(d.jabatan ?? '-')}</div>
                                    </td>
                                    <td class="px-4 py-4 text-center">${d.hari_hadir}/${d.hari_kerja}</td>
                                    <td class="px-4 py-4 text-center">${d.menit_telat ? formatDurasi(d.menit_telat) : '-'}</td>
                                    <td class="px-4 py-4 text-center">${d.jumlah_alpha}</td>
                                    <td class="px-4 py-4 text-right">${rp(Number(d.gaji_pokok) + Number(d.tunjangan_jabatan) + Number(d.total_tunjangan))}</td>
                                    <td class="px-4 py-4 text-right">${rp(d.upah_lembur)}</td>
                                    <td class="px-4 py-4 text-right text-red-600">${potongan ? '− ' + rp(potongan) : '-'}</td>
                                    <td class="px-4 py-4 text-right font-semibold">${rp(d.gaji_bersih)}</td>
                                    <td class="px-4 py-4 text-center">
                                        <a href="${payrollUrl}/slip/${d.id}" target="_blank" title="Slip gaji" class="text-slate-500 hover:text-primary">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                    </td>
                                </tr>`;
                        }).join(''));
                    renderInfo('#info', res);
                    renderPagination(res, 'loadData');
                },
                error: () => $('#tableData').html(tableError(9)),
            });
        }

        function aksi(url, method, konfirmasi) {
            Swal.fire({ ...konfirmasi, icon: 'question', showCancelButton: true, confirmButtonColor: '#B91C1C', cancelButtonText: 'Batal' })
                .then((r) => {
                    if (!r.isConfirmed) return;
                    App.loading.show();
                    ajaxRequest({
                        url, type: 'POST', data: { _token: token, _method: method },
                        success(res) {
                            App.loading.hide();
                            Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message }).then(() => location.reload());
                        },
                        error(xhr) {
                            App.loading.hide();
                            Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.' });
                        },
                    });
                });
        }

        $(function() {
            loadData();
            debounceSearch('#search', () => loadData());
            $('#btnGenerate').on('click', () => aksi(@json(route('payroll.generate', $payroll)), 'POST', {
                title: 'Generate ulang?', text: 'Semua angka dihitung ulang dari absensi, lembur & master gaji saat ini.', confirmButtonText: 'Ya, Generate',
            }));
            $('#btnFinal').on('click', () => aksi(@json(route('payroll.final', $payroll)), 'PATCH', {
                title: 'Finalkan payroll?', text: 'Setelah final, payroll serta absensi/lembur/izin di periode ini tidak bisa diubah.', confirmButtonText: 'Ya, Finalkan',
            }));
        });
    </script>
@endpush
