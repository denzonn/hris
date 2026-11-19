@extends('layouts.app')

@section('title', 'Rekap Absensi')
@section('page_title', 'Rekap Absensi')
@section('page_description', 'Rekap kehadiran, telat, izin & lembur per karyawan')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="flex flex-col gap-3 border-b border-slate-100 p-5 xl:flex-row xl:items-center xl:justify-between">
            <div class="grid w-full min-w-0 gap-3 md:grid-cols-4">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="search" placeholder="Cari nama / NIK..."
                        class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-11 text-sm focus:border-primary focus:ring-primary">
                </div>
                <input type="text" id="filter_tanggal" placeholder="Periode" readonly
                    class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-700 focus:border-primary focus:ring-primary">
                <x-select name="filter_cabang" placeholder="Semua Cabang">
                    @foreach ($cabang as $c)
                        <option value="{{ $c->id }}">{{ $c->nama }}</option>
                    @endforeach
                </x-select>
                <x-select name="filter_divisi" placeholder="Semua Divisi">
                    @foreach ($divisi as $d)
                        <option value="{{ $d->id }}">{{ $d->nama }}</option>
                    @endforeach
                </x-select>
            </div>

            <div class="flex shrink-0 gap-2">
                <button type="button" id="btnRefresh"
                    class="h-10 w-10 shrink-0 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100" title="Refresh">
                    <i class="fa-solid fa-rotate"></i>
                </button>
                <button type="button" id="btnExport"
                    class="flex shrink-0 items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary_hover">
                    <i class="fa-solid fa-file-csv"></i> Export CSV
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-4 w-12">No</th>
                        <th class="px-4 py-4">Pegawai</th>
                        <th class="px-4 py-4 text-center">Hari Kerja</th>
                        <th class="px-4 py-4 text-center">Hadir</th>
                        <th class="px-4 py-4 text-center">Telat</th>
                        <th class="px-4 py-4 text-center">Izin</th>
                        <th class="px-4 py-4 text-center">Sakit</th>
                        <th class="px-4 py-4 text-center">Cuti</th>
                        <th class="px-4 py-4 text-center">Alpha</th>
                        <th class="px-4 py-4 text-center">Lembur</th>
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
        const esc = (v) => $('<div>').text(v ?? '').html();
        let periode = { mulai: @json(now()->startOfMonth()->toDateString()), selesai: @json(now()->endOfMonth()->toDateString()) };

        const filter = () => ({
            search: $('#search').val(), cabang_id: $('#filter_cabang').val(), divisi_id: $('#filter_divisi').val(),
            tanggal_mulai: periode.mulai, tanggal_selesai: periode.selesai,
        });

        function loadData(page = 1) {
            ajaxRequest({
                url: @json(route('rekap_absensi.data')),
                data: { page, ...filter() },
                before: () => loadingSkeleton('#tableData', 10, skeleton.table),
                success(res) {
                    $('#tableData').html(!res.data.length ? emptyTable(10, 'Tidak ada pegawai', 'Ubah filter untuk melihat rekap.')
                        : res.data.map((r, i) => `
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-4">${res.from + i}</td>
                                <td class="px-4 py-4">
                                    <div class="font-semibold">${esc(r.name)}</div>
                                    <div class="text-xs text-slate-400">${esc(r.nik ?? '-')} · ${esc(r.cabang ?? '-')} · ${esc(r.divisi ?? '-')}</div>
                                </td>
                                <td class="px-4 py-4 text-center">${r.hari_kerja}</td>
                                <td class="px-4 py-4 text-center font-semibold text-emerald-600">${r.hadir}</td>
                                <td class="px-4 py-4 text-center">${r.telat}x
                                    ${r.menit_telat ? `<div class="text-xs text-amber-600">${formatDurasi(r.menit_telat)}</div>` : ''}</td>
                                <td class="px-4 py-4 text-center">${r.izin}</td>
                                <td class="px-4 py-4 text-center">${r.sakit}</td>
                                <td class="px-4 py-4 text-center">${r.cuti}</td>
                                <td class="px-4 py-4 text-center font-semibold ${r.alpha ? 'text-red-600' : ''}">${r.alpha}</td>
                                <td class="px-4 py-4 text-center">${formatDurasi(r.lembur_menit)}</td>
                            </tr>`).join(''));
                    renderInfo('#info', res);
                    renderPagination(res, 'loadData');
                },
                error: (xhr) => xhr.status === 422
                    ? Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message })
                    : $('#tableData').html(tableError(10)),
            });
        }

        $(function() {
            loadData();
            debounceSearch('#search', () => loadData());
            refreshData('#btnRefresh', '#search', () => loadData());
            $('#filter_cabang, #filter_divisi').on('change', () => loadData());
            initDateRangePicker('#filter_tanggal', (mulai, selesai) => {
                if (!selesai) return;
                periode = { mulai, selesai };
                loadData();
            }, { defaultDate: [new Date(`${periode.mulai}T00:00`), new Date(`${periode.selesai}T00:00`)] });
            $('#btnExport').on('click', () => {
                window.location = @json(route('rekap_absensi.export')) + '?' + $.param(filter());
            });
        });
    </script>
@endpush
