@extends('layouts.app')

@section('title', 'Komponen Gaji')
@section('page_title', 'Komponen Gaji')
@section('page_description', 'Master tunjangan & potongan gaji')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="flex flex-col gap-3 border-b border-slate-100 p-5 md:flex-row md:items-center md:justify-between">
            <div class="flex w-full flex-col gap-3 md:flex-row">
                <div class="relative w-full md:w-80">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="search" placeholder="Cari nama komponen..."
                        class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-11 text-sm focus:border-primary focus:ring-primary">
                </div>
                <div class="w-full md:w-44">
                    <x-select name="filter_tipe" placeholder="Semua Tipe">
                        <option value="tunjangan">Tunjangan</option>
                        <option value="potongan">Potongan</option>
                    </x-select>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="button" id="btnRefresh"
                    class="h-10 w-10 shrink-0 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100" title="Refresh">
                    <i class="fa-solid fa-rotate"></i>
                </button>
                <button type="button" onclick="tambahKomponen()"
                    class="flex shrink-0 items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary_hover">
                    <i class="fa-solid fa-plus"></i> Tambah Komponen
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-4 w-12">No</th>
                        <th class="px-6 py-4">Nama</th>
                        <th class="px-6 py-4">Tipe</th>
                        <th class="px-6 py-4">Sifat</th>
                        <th class="px-6 py-4">Dipakai</th>
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
    <div id="modalKomponen"
        class="modal-overlay fixed inset-0 z-[60] hidden bg-black/40 p-4 opacity-0 transition-opacity duration-300">
        <div class="modal-content modal-box w-full max-w-lg -translate-y-6 scale-95 rounded-2xl bg-white shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 id="modalTitle" class="font-semibold text-slate-800">Tambah Komponen</h3>
                <button type="button" onclick="closeModal('modalKomponen')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="formKomponen" class="space-y-4 p-6">
                @csrf
                <x-input name="nama" label="Nama Komponen" placeholder="mis. Uang Makan" required />
                <div class="grid grid-cols-2 gap-4">
                    <x-select name="tipe" label="Tipe" required>
                        <option value="tunjangan">Tunjangan</option>
                        <option value="potongan">Potongan</option>
                    </x-select>
                    <x-select name="sifat" label="Sifat" required>
                        <option value="tetap">Tetap per bulan</option>
                        <option value="per_hari_hadir">Per hari hadir</option>
                    </x-select>
                </div>
                <p class="text-xs text-slate-500">Nominal diisi per pegawai di menu Gaji Pegawai. "Per hari hadir" = nominal × jumlah hari hadir.</p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('modalKomponen')"
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
        const baseUrl = @json(url('komponen_gaji'));
        const labelSifat = { tetap: 'Tetap per bulan', per_hari_hadir: 'Per hari hadir' };
        let rows = {};
        let editId = null;

        const esc = (v) => $('<div>').text(v ?? '').html();
        const badgeTipe = (t) => t === 'tunjangan'
            ? '<span class="inline-block rounded-lg bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Tunjangan</span>'
            : '<span class="inline-block rounded-lg bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">Potongan</span>';

        function loadData(page = 1) {
            ajaxRequest({
                url: @json(route('komponen_gaji.data')),
                data: { page, search: $('#search').val(), tipe: $('#filter_tipe').val() },
                before: () => loadingSkeleton('#tableData', 6, skeleton.table),
                success(res) {
                    rows = {};
                    if (!res.data.length) {
                        $('#tableData').html(emptyTable(6, 'Belum ada komponen gaji', 'Tambahkan tunjangan atau potongan.'));
                    } else {
                        $('#tableData').html(res.data.map((item, i) => {
                            rows[item.id] = item;
                            return `
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4">${res.from + i}</td>
                                    <td class="px-6 py-4 font-semibold">${esc(item.nama)}</td>
                                    <td class="px-6 py-4">${badgeTipe(item.tipe)}</td>
                                    <td class="px-6 py-4">${labelSifat[item.sifat]}</td>
                                    <td class="px-6 py-4">${item.pegawai_count} pegawai</td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-center gap-2">
                                            <button type="button" onclick="editKomponen(${item.id})" title="Edit"
                                                class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button type="button" onclick="hapusKomponen(${item.id})" title="Hapus"
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
                error: () => $('#tableData').html(tableError(6)),
            });
        }

        function resetForm() {
            $('#formKomponen')[0].reset();
            $('#formKomponen [data-error]').addClass('hidden').text('');
        }

        function tambahKomponen() {
            editId = null;
            resetForm();
            $('#modalTitle').text('Tambah Komponen');
            showModal('modalKomponen');
        }

        function editKomponen(id) {
            const item = rows[id];
            editId = id;
            resetForm();
            $('#modalTitle').text('Edit Komponen');
            $('#nama').val(item.nama);
            $('#tipe').val(item.tipe);
            $('#sifat').val(item.sifat);
            showModal('modalKomponen');
        }

        function hapusKomponen(id) {
            hapusData({ url: `${baseUrl}/${id}`, name: esc(rows[id].nama), reload: () => loadData() });
        }

        $(function() {
            loadData();
            debounceSearch('#search', () => loadData());
            refreshData('#btnRefresh', '#search', () => loadData());
            $('#filter_tipe').on('change', () => loadData());

            // Form ini juga memicu App.submitLoading() global (layouts/app), loading ditutup di success/error.
            $('#formKomponen').on('submit', function(e) {
                e.preventDefault();
                const data = $(this).serializeArray();
                if (editId) data.push({ name: '_method', value: 'PUT' });

                ajaxRequest({
                    url: editId ? `${baseUrl}/${editId}` : baseUrl,
                    type: 'POST',
                    data: $.param(data),
                    success(res) {
                        App.loading.hide();
                        closeModal('modalKomponen');
                        showToast(res.message);
                        loadData();
                    },
                    error(xhr) {
                        App.loading.hide();
                        if (xhr.status !== 422 || !xhr.responseJSON?.errors) {
                            return Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.' });
                        }
                        $('#formKomponen [data-error]').addClass('hidden').text('');
                        $.each(xhr.responseJSON.errors, (field, msg) => {
                            $(`#formKomponen [data-error="${field}"]`).removeClass('hidden').text(msg[0]);
                        });
                    },
                });
            });
        });
    </script>
@endpush
