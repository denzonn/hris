@extends('layouts.app')

@section('title', 'Slip Gaji')
@section('page_title', 'Slip Gaji')
@section('page_description', 'Slip gaji Anda dari payroll yang sudah final')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Periode</th>
                        <th class="px-6 py-4">Rentang Absensi</th>
                        <th class="px-6 py-4">Hadir</th>
                        <th class="px-6 py-4 text-right">Gaji Bersih</th>
                        <th class="px-6 py-4 text-center w-24">Slip</th>
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
        const slipUrl = @json(url('slip_gaji'));
        const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        function loadData(page = 1) {
            ajaxRequest({
                url: @json(route('slip_gaji.data')),
                data: { page },
                before: () => loadingSkeleton('#tableData', 5, skeleton.table),
                success(res) {
                    $('#tableData').html(!res.data.length ? emptyTable(5, 'Belum ada slip gaji', 'Slip muncul setelah payroll difinalkan HR.')
                        : res.data.map((d) => `
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 font-semibold">${bulan[Number(d.payroll.periode.slice(5, 7)) - 1]} ${d.payroll.periode.slice(0, 4)}</td>
                                <td class="px-6 py-4">${formatTanggal(d.payroll.tanggal_mulai)} – ${formatTanggal(d.payroll.tanggal_selesai)}</td>
                                <td class="px-6 py-4">${d.hari_hadir}/${d.hari_kerja} hari</td>
                                <td class="px-6 py-4 text-right font-semibold">${formatRupiah(Math.round(d.gaji_bersih))}</td>
                                <td class="px-6 py-4 text-center">
                                    <a href="${slipUrl}/${d.id}" target="_blank" class="text-slate-500 hover:text-primary" title="Lihat slip">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                </td>
                            </tr>`).join(''));
                    renderInfo('#info', res);
                    renderPagination(res, 'loadData');
                },
                error: () => $('#tableData').html(tableError(5)),
            });
        }

        $(function() {
            loadData();
        });
    </script>
@endpush
