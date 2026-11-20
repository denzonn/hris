@extends('layouts.app')

@section('title', 'Divisi')
@section('page_title', 'Divisi')
@section('page_description', 'Master data divisi')

@section('content')
    <div class="space-y-6">
        {{-- TOOLBAR --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative w-full sm:max-w-md">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="search" placeholder="Cari nama divisi..."
                        class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-11 text-sm focus:border-primary focus:ring-primary">
                </div>

                <div class="flex shrink-0 gap-2">
                    <button type="button" id="btnRefresh"
                        class="h-10 w-10 shrink-0 rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-100" title="Refresh">
                        <i class="fa-solid fa-rotate"></i>
                    </button>
                    <button type="button" onclick="tambahDivisi()"
                        class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary_hover">
                        <i class="fa-solid fa-plus"></i> Tambah Divisi
                    </button>
                </div>
            </div>
        </div>

        {{-- TABEL --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="w-20 px-6 py-3.5">No</th>
                            <th class="px-6 py-3.5">Nama Divisi</th>
                            <th class="px-6 py-3.5">Total Pegawai</th>
                            <th class="w-36 px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tableData" class="divide-y divide-slate-100 text-slate-700"></tbody>
                </table>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div id="info" class="text-sm text-slate-500"></div>
                <div id="pagination" class="flex flex-wrap gap-2"></div>
            </div>
        </div>
    </div>

    {{-- MODAL TAMBAH / EDIT --}}
    <div id="modalDivisi"
        class="modal-overlay fixed inset-0 z-[60] hidden bg-black/40 p-4 opacity-0 transition-opacity duration-300">
        <div class="modal-content modal-box w-full max-w-lg -translate-y-6 scale-95 rounded-2xl bg-white shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 id="modalTitle" class="font-semibold text-slate-800">Tambah Divisi</h3>
                <button type="button" onclick="closeModal('modalDivisi')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="formDivisi" class="space-y-4 p-6">
                @csrf
                <x-input name="nama" label="Nama Divisi" required />

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('modalDivisi')"
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
        const baseUrl = @json(url('divisi'));
        let rows = {};
        let editId = null;

        const esc = (v) => $('<div>').text(v ?? '').html();

        function loadData(page = 1) {
            ajaxRequest({
                url: @json(route('divisi.data')),
                data: { page, search: $('#search').val() },
                before: () => loadingSkeleton('#tableData', 5, skeleton.table),
                success(res) {
                    rows = {};
                    if (!res.data.length) {
                        $('#tableData').html(emptyTable(4, 'Belum ada divisi', 'Tambahkan divisi baru.'));
                    } else {
                        $('#tableData').html(res.data.map((item, i) => {
                            rows[item.id] = item;
                            const warna = warnaKategori(item.id);
                            return `
                                <tr class="transition-colors hover:bg-slate-50">
                                    <td class="px-6 py-4 tabular-nums text-slate-500">${res.from + i}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-xs font-bold ${warna.avatar}">
                                                ${esc(inisial(item.nama))}
                                            </div>
                                            <div>
                                                <div class="font-semibold text-slate-800">${esc(item.nama)}</div>
                                                <div class="text-xs text-slate-400">${item.jabatan_count} jabatan</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                                            <span class="h-1.5 w-1.5 rounded-full ${warna.dot}"></span>
                                            ${item.pegawai_count} Pegawai
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="inline-flex gap-2">
                                            <button type="button" onclick="editDivisi(${item.id})" title="Edit"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-amber-600 hover:bg-amber-50">
                                                <i class="fa-solid fa-pen text-xs"></i>
                                            </button>
                                            <button type="button" onclick="hapusDivisi(${item.id})" title="Hapus"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-red-600 hover:bg-red-50">
                                                <i class="fa-solid fa-trash text-xs"></i>
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
            $('#formDivisi')[0].reset();
            $('#formDivisi [data-error]').addClass('hidden').text('');
        }

        function tambahDivisi() {
            editId = null;
            resetForm();
            $('#modalTitle').text('Tambah Divisi');
            showModal('modalDivisi');
        }

        function editDivisi(id) {
            editId = id;
            resetForm();
            $('#modalTitle').text('Edit Divisi');
            $('#nama').val(rows[id].nama);
            showModal('modalDivisi');
        }

        function hapusDivisi(id) {
            hapusData({
                url: `${baseUrl}/${id}`,
                name: esc(rows[id].nama),
                reload: () => loadData(),
            });
        }

        $(function() {
            loadData();
            debounceSearch('#search', () => loadData());
            refreshData('#btnRefresh', '#search', () => loadData());

            // Form ini juga memicu App.submitLoading() global (layouts/app), loading ditutup di success/error.
            $('#formDivisi').on('submit', function(e) {
                e.preventDefault();
                let data = $(this).serialize();
                if (editId) data += '&_method=PUT';

                ajaxRequest({
                    url: editId ? `${baseUrl}/${editId}` : baseUrl,
                    type: 'POST',
                    data,
                    success(res) {
                        App.loading.hide();
                        closeModal('modalDivisi');
                        showToast(res.message);
                        loadData();
                    },
                    error(xhr) {
                        App.loading.hide();
                        if (xhr.status !== 422) {
                            return Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.' });
                        }
                        $('#formDivisi [data-error]').addClass('hidden').text('');
                        $.each(xhr.responseJSON.errors, (field, msg) => {
                            $(`#formDivisi [data-error="${field}"]`).removeClass('hidden').text(msg[0]);
                        });
                    },
                });
            });
        });
    </script>
@endpush
