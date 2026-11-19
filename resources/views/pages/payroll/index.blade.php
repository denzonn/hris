@extends('layouts.app')

@section('title', 'Payroll')
@section('page_title', 'Payroll')
@section('page_description', 'Generate gaji per periode, finalkan untuk mengunci')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="flex flex-col gap-3 border-b border-slate-100 p-5 md:flex-row md:items-center md:justify-between">
            <div class="w-full md:w-40">
                <x-select name="filter_tahun" placeholder="Semua Tahun">
                    @foreach (range(now()->year + 1, now()->year - 3) as $tahun)
                        <option value="{{ $tahun }}" @selected($tahun === now()->year)>{{ $tahun }}</option>
                    @endforeach
                </x-select>
            </div>

            <div class="flex gap-2">
                <button type="button" id="btnRefresh"
                    class="h-10 w-10 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100" title="Refresh">
                    <i class="fa-solid fa-rotate"></i>
                </button>
                <button type="button" onclick="showModal('modalPayroll')"
                    class="flex shrink-0 items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary_hover">
                    <i class="fa-solid fa-plus"></i> Buat Payroll
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Periode</th>
                        <th class="px-6 py-4">Rentang Absensi</th>
                        <th class="px-6 py-4">Pegawai</th>
                        <th class="px-6 py-4">Total Gaji Bersih</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-center w-32">Aksi</th>
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

    {{-- MODAL BUAT --}}
    <div id="modalPayroll"
        class="modal-overlay fixed inset-0 z-[60] hidden bg-black/40 p-4 opacity-0 transition-opacity duration-300">
        <div class="modal-content modal-box w-full max-w-md -translate-y-6 scale-95 rounded-2xl bg-white shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 class="font-semibold text-slate-800">Buat Payroll</h3>
                <button type="button" onclick="closeModal('modalPayroll')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="formPayroll" class="space-y-4 p-6">
                @csrf
                <x-input name="periode" label="Bulan Gajian" type="month" value="{{ now()->format('Y-m') }}" required />
                <p class="text-xs text-slate-500">Rentang absensi mengikuti tanggal cut-off di Pengaturan Payroll. Payroll dibuat sebagai draft dan masih bisa di-generate ulang.</p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('modalPayroll')"
                        class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-100">Batal</button>
                    <button type="submit"
                        class="rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary_hover">Generate</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('addon-script')
    <script>
        const baseUrl = @json(url('payroll'));
        const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const namaPeriode = (p) => `${bulan[Number(p.slice(5, 7)) - 1]} ${p.slice(0, 4)}`;
        let rows = {};

        function loadData(page = 1) {
            ajaxRequest({
                url: @json(route('payroll.data')),
                data: { page, tahun: $('#filter_tahun').val() },
                before: () => loadingSkeleton('#tableData', 6, skeleton.table),
                success(res) {
                    rows = {};
                    $('#tableData').html(!res.data.length ? emptyTable(6, 'Belum ada payroll', 'Klik Buat Payroll untuk generate gaji.')
                        : res.data.map((item) => {
                            rows[item.id] = item;
                            const status = item.status === 'final'
                                ? '<span class="inline-block rounded-lg bg-emerald-600 px-3 py-1 text-[11px] font-semibold text-white"><i class="fa-solid fa-lock"></i> Final</span>'
                                : '<span class="inline-block rounded-lg bg-amber-500 px-3 py-1 text-[11px] font-semibold text-white">Draft</span>';
                            return `
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 font-semibold">${namaPeriode(item.periode)}</td>
                                    <td class="px-6 py-4">${formatTanggal(item.tanggal_mulai)} – ${formatTanggal(item.tanggal_selesai)}</td>
                                    <td class="px-6 py-4">${item.detail_count}</td>
                                    <td class="px-6 py-4">${formatRupiah(Math.round(item.total_gaji ?? 0))}</td>
                                    <td class="px-6 py-4">${status}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-center gap-2">
                                            <a href="${baseUrl}/${item.id}" title="Detail"
                                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            ${item.status === 'draft' ? `
                                            <button type="button" onclick="hapusPayroll(${item.id})" title="Hapus"
                                                class="h-10 w-10 rounded-xl bg-red-50 text-red-600 hover:bg-red-100">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>` : ''}
                                        </div>
                                    </td>
                                </tr>`;
                        }).join(''));
                    renderInfo('#info', res);
                    renderPagination(res, 'loadData');
                },
                error: () => $('#tableData').html(tableError(6)),
            });
        }

        function hapusPayroll(id) {
            hapusData({ url: `${baseUrl}/${id}`, name: `payroll ${namaPeriode(rows[id].periode)}`, reload: () => loadData() });
        }

        $(function() {
            loadData();
            refreshData('#btnRefresh', null, () => loadData());
            $('#filter_tahun').on('change', () => loadData());

            // Form ini juga memicu App.submitLoading() global (layouts/app), loading ditutup di success/error.
            $('#formPayroll').on('submit', function(e) {
                e.preventDefault();
                ajaxRequest({
                    url: baseUrl,
                    type: 'POST',
                    data: $(this).serialize(),
                    success(res) {
                        App.loading.hide();
                        closeModal('modalPayroll');
                        showToast(res.message);
                        loadData();
                    },
                    error(xhr) {
                        App.loading.hide();
                        $('#formPayroll [data-error]').addClass('hidden').text('');
                        if (xhr.status !== 422 || !xhr.responseJSON?.errors) {
                            return Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.' });
                        }
                        $.each(xhr.responseJSON.errors, (field, msg) => {
                            $(`#formPayroll [data-error="${field}"]`).removeClass('hidden').text(msg[0]);
                        });
                    },
                });
            });
        });
    </script>
@endpush
