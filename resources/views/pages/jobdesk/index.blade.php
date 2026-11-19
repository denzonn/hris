@extends('layouts.app')

@section('title', 'Jobdesk')
@section('page_title', 'Jobdesk')
@section('page_description', 'Uraian tugas per jabatan')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="flex flex-col gap-3 border-b border-slate-100 p-5 md:flex-row md:items-center md:justify-between">
            <div class="flex w-full flex-col gap-3 md:flex-row">
                <div class="relative w-full md:w-80">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="search" placeholder="Cari deskripsi..."
                        class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-11 text-sm focus:border-primary focus:ring-primary">
                </div>
                <div class="w-full md:w-64">
                    <x-select name="filter_jabatan" placeholder="Semua Jabatan">
                        @foreach ($jabatan as $j)
                            <option value="{{ $j->id }}">{{ $j->nama }} ({{ $j->divisi?->nama }})</option>
                        @endforeach
                    </x-select>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="button" id="btnRefresh"
                    class="h-10 w-10 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100" title="Refresh">
                    <i class="fa-solid fa-rotate"></i>
                </button>
                <button type="button" onclick="tambahJobdesk()"
                    class="flex shrink-0 items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary_hover">
                    <i class="fa-solid fa-plus"></i> Tambah Jobdesk
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-4 w-12">No</th>
                        <th class="px-6 py-4 w-56">Jabatan</th>
                        <th class="px-6 py-4">Deskripsi</th>
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
    <div id="modalJobdesk"
        class="modal-overlay fixed inset-0 z-[60] hidden bg-black/40 p-4 opacity-0 transition-opacity duration-300">
        <div class="modal-content modal-box w-full max-w-lg -translate-y-6 scale-95 rounded-2xl bg-white shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 id="modalTitle" class="font-semibold text-slate-800">Tambah Jobdesk</h3>
                <button type="button" onclick="closeModal('modalJobdesk')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="formJobdesk" class="space-y-4 p-6">
                @csrf
                <x-select name="jabatan_id" label="Jabatan" placeholder="Pilih jabatan" required>
                    @foreach ($jabatan as $j)
                        <option value="{{ $j->id }}">{{ $j->nama }} ({{ $j->divisi?->nama }})</option>
                    @endforeach
                </x-select>
                <div>
                    <label for="deskripsi" class="mb-1.5 block text-sm font-medium text-slate-700">Deskripsi</label>
                    <textarea name="deskripsi" id="deskripsi" rows="5" required
                        class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-700 focus:border-primary focus:bg-white focus:ring-primary"></textarea>
                    <p data-error="deskripsi" class="mt-1 hidden text-xs text-red-600"></p>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('modalJobdesk')"
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
        const baseUrl = @json(url('jobdesk'));
        let rows = {};
        let editId = null;

        const esc = (v) => $('<div>').text(v ?? '').html();

        function loadData(page = 1) {
            ajaxRequest({
                url: @json(route('jobdesk.data')),
                data: { page, search: $('#search').val(), jabatan_id: $('#filter_jabatan').val() },
                before: () => loadingSkeleton('#tableData', 5, skeleton.table),
                success(res) {
                    rows = {};
                    if (!res.data.length) {
                        $('#tableData').html(emptyTable(4, 'Belum ada jobdesk', 'Tambahkan jobdesk baru.'));
                    } else {
                        $('#tableData').html(res.data.map((item, i) => {
                            rows[item.id] = item;
                            return `
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 align-top">${res.from + i}</td>
                                    <td class="px-6 py-4 align-top">
                                        <div class="font-semibold">${esc(item.jabatan?.nama ?? '-')}</div>
                                        <div class="text-xs text-slate-400">${esc(item.jabatan?.divisi?.nama ?? '-')}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-pre-line">${esc(item.deskripsi)}</td>
                                    <td class="px-6 py-4 align-top">
                                        <div class="flex justify-center gap-2">
                                            <button type="button" onclick="editJobdesk(${item.id})" title="Edit"
                                                class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button type="button" onclick="hapusJobdesk(${item.id})" title="Hapus"
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
            $('#formJobdesk')[0].reset();
            $('#formJobdesk [data-error]').addClass('hidden').text('');
        }

        function tambahJobdesk() {
            editId = null;
            resetForm();
            $('#jabatan_id').val($('#filter_jabatan').val());
            $('#modalTitle').text('Tambah Jobdesk');
            showModal('modalJobdesk');
        }

        function editJobdesk(id) {
            const item = rows[id];
            editId = id;
            resetForm();
            $('#modalTitle').text('Edit Jobdesk');
            $('#jabatan_id').val(item.jabatan_id);
            $('#deskripsi').val(item.deskripsi);
            showModal('modalJobdesk');
        }

        function hapusJobdesk(id) {
            hapusData({
                url: `${baseUrl}/${id}`,
                name: 'jobdesk ' + esc(rows[id].jabatan?.nama ?? ''),
                reload: () => loadData(),
            });
        }

        $(function() {
            loadData();
            debounceSearch('#search', () => loadData());
            refreshData('#btnRefresh', '#search', () => loadData());
            $('#filter_jabatan').on('change', () => loadData());

            // Form ini juga memicu App.submitLoading() global (layouts/app), loading ditutup di success/error.
            $('#formJobdesk').on('submit', function(e) {
                e.preventDefault();
                let data = $(this).serialize();
                if (editId) data += '&_method=PUT';

                ajaxRequest({
                    url: editId ? `${baseUrl}/${editId}` : baseUrl,
                    type: 'POST',
                    data,
                    success(res) {
                        App.loading.hide();
                        closeModal('modalJobdesk');
                        showToast(res.message);
                        loadData();
                    },
                    error(xhr) {
                        App.loading.hide();
                        if (xhr.status !== 422) {
                            return Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.' });
                        }
                        $('#formJobdesk [data-error]').addClass('hidden').text('');
                        $.each(xhr.responseJSON.errors, (field, msg) => {
                            $(`#formJobdesk [data-error="${field}"]`).removeClass('hidden').text(msg[0]);
                        });
                    },
                });
            });
        });
    </script>
@endpush
