@extends('layouts.app')

@section('title', 'Hari Libur')
@section('page_title', 'Hari Libur')
@section('page_description', 'Daftar tanggal merah & libur perusahaan')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="flex flex-col gap-3 border-b border-slate-100 p-5 md:flex-row md:items-center md:justify-between">
            <div class="flex w-full flex-col gap-3 md:flex-row">
                <div class="relative w-full md:w-80">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="search" placeholder="Cari keterangan..."
                        class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-11 text-sm focus:border-primary focus:ring-primary">
                </div>
                <div class="w-full md:w-40">
                    <x-select name="filter_tahun" placeholder="Semua Tahun">
                        @foreach (range(now()->year + 1, now()->year - 2) as $tahun)
                            <option value="{{ $tahun }}" @selected($tahun === now()->year)>{{ $tahun }}</option>
                        @endforeach
                    </x-select>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="button" id="btnRefresh"
                    class="h-10 w-10 shrink-0 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100" title="Refresh">
                    <i class="fa-solid fa-rotate"></i>
                </button>
                <button type="button" onclick="tambahLibur()"
                    class="flex shrink-0 items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary_hover">
                    <i class="fa-solid fa-plus"></i> Tambah Hari Libur
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-4 w-12">No</th>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4">Keterangan</th>
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

    {{-- MODAL TAMBAH / EDIT --}}
    <div id="modalLibur"
        class="modal-overlay fixed inset-0 z-[60] hidden bg-black/40 p-4 opacity-0 transition-opacity duration-300">
        <div class="modal-content modal-box w-full max-w-lg -translate-y-6 scale-95 rounded-2xl bg-white shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 id="modalTitle" class="font-semibold text-slate-800">Tambah Hari Libur</h3>
                <button type="button" onclick="closeModal('modalLibur')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="formLibur" class="space-y-4 p-6">
                @csrf
                <x-input name="tanggal" label="Tanggal" type="date" required />
                <x-input name="keterangan" label="Keterangan" placeholder="mis. Hari Kemerdekaan RI" required />

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('modalLibur')"
                        class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-100">Batal</button>
                    <button type="submit"
                        class="rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary_hover">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('addon-script')
    <script>
        const baseUrl = @json(url('hari_libur'));
        let rows = {};
        let editId = null;

        const esc = (v) => $('<div>').text(v ?? '').html();

        function loadData(page = 1) {
            ajaxRequest({
                url: @json(route('hari_libur.data')),
                data: { page, search: $('#search').val(), tahun: $('#filter_tahun').val() },
                before: () => loadingSkeleton('#tableData', 4, skeleton.table),
                success(res) {
                    rows = {};
                    if (!res.data.length) {
                        $('#tableData').html(emptyTable(4, 'Belum ada hari libur', 'Tambahkan hari libur baru.'));
                    } else {
                        $('#tableData').html(res.data.map((item, i) => {
                            rows[item.id] = item;
                            return `
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4">${res.from + i}</td>
                                    <td class="px-6 py-4 font-semibold">${formatTanggalIndo(item.tanggal)}</td>
                                    <td class="px-6 py-4">${esc(item.keterangan)}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-center gap-2">
                                            <button type="button" onclick="editLibur(${item.id})" title="Edit"
                                                class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button type="button" onclick="hapusLibur(${item.id})" title="Hapus"
                                                class="h-10 w-10 rounded-xl bg-red-50 text-red-600 hover:bg-red-100">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>`;
                        }).join(''));
                    }
                    renderInfo('#info', res);
                    renderPagination(res, 'loadData');
                },
                error: () => $('#tableData').html(tableError(4)),
            });
        }

        function resetForm() {
            $('#formLibur')[0].reset();
            $('#formLibur [data-error]').addClass('hidden').text('');
        }

        function tambahLibur() {
            editId = null;
            resetForm();
            $('#modalTitle').text('Tambah Hari Libur');
            showModal('modalLibur');
        }

        function editLibur(id) {
            const item = rows[id];
            editId = id;
            resetForm();
            $('#modalTitle').text('Edit Hari Libur');
            $('#tanggal').val(item.tanggal);
            $('#keterangan').val(item.keterangan);
            showModal('modalLibur');
        }

        function hapusLibur(id) {
            hapusData({
                url: `${baseUrl}/${id}`,
                name: `${formatTanggalIndo(rows[id].tanggal)} (${esc(rows[id].keterangan)})`,
                reload: () => loadData(),
            });
        }

        $(function() {
            loadData();
            debounceSearch('#search', () => loadData());
            refreshData('#btnRefresh', '#search', () => loadData());
            $('#filter_tahun').on('change', () => loadData());

            // Form ini juga memicu App.submitLoading() global (layouts/app), loading ditutup di success/error.
            $('#formLibur').on('submit', function(e) {
                e.preventDefault();
                const data = $(this).serializeArray();
                if (editId) data.push({ name: '_method', value: 'PUT' });

                ajaxRequest({
                    url: editId ? `${baseUrl}/${editId}` : baseUrl,
                    type: 'POST',
                    data: $.param(data),
                    success(res) {
                        App.loading.hide();
                        closeModal('modalLibur');
                        showToast(res.message);
                        loadData();
                    },
                    error(xhr) {
                        App.loading.hide();
                        if (xhr.status !== 422) {
                            return Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.' });
                        }
                        $('#formLibur [data-error]').addClass('hidden').text('');
                        $.each(xhr.responseJSON.errors, (field, msg) => {
                            $(`#formLibur [data-error="${field}"]`).removeClass('hidden').text(msg[0]);
                        });
                    },
                });
            });
        });
    </script>
@endpush
