@extends('layouts.app')

@section('title', 'Jabatan')
@section('page_title', 'Jabatan')
@section('page_description', 'Master data jabatan & tunjangan jabatan')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="flex flex-col gap-3 border-b border-slate-100 p-5 md:flex-row md:items-center md:justify-between">
            <div class="flex w-full flex-col gap-3 md:flex-row">
                <div class="relative w-full md:w-80">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="search" placeholder="Cari nama jabatan..."
                        class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-11 text-sm focus:border-primary focus:ring-primary">
                </div>
                <div class="w-full md:w-56">
                    <x-select name="filter_divisi" placeholder="Semua Divisi">
                        @foreach ($divisi as $d)
                            <option value="{{ $d->id }}">{{ $d->nama }}</option>
                        @endforeach
                    </x-select>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="button" id="btnRefresh"
                    class="h-10 w-10 shrink-0 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100" title="Refresh">
                    <i class="fa-solid fa-rotate"></i>
                </button>
                <button type="button" onclick="tambahJabatan()"
                    class="flex shrink-0 items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary_hover">
                    <i class="fa-solid fa-plus"></i> Tambah Jabatan
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-4 w-12">No</th>
                        <th class="px-6 py-4">Nama Jabatan</th>
                        <th class="px-6 py-4">Divisi</th>
                        <th class="px-6 py-4">Tunjangan Jabatan</th>
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
    <div id="modalJabatan"
        class="modal-overlay fixed inset-0 z-[60] hidden bg-black/40 p-4 opacity-0 transition-opacity duration-300">
        <div class="modal-content modal-box w-full max-w-lg -translate-y-6 scale-95 rounded-2xl bg-white shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 id="modalTitle" class="font-semibold text-slate-800">Tambah Jabatan</h3>
                <button type="button" onclick="closeModal('modalJabatan')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="formJabatan" class="space-y-4 p-6">
                @csrf
                <x-input name="nama" label="Nama Jabatan" required />
                <x-select name="divisi_id" label="Divisi" placeholder="Pilih divisi" required>
                    @foreach ($divisi as $d)
                        <option value="{{ $d->id }}">{{ $d->nama }}</option>
                    @endforeach
                </x-select>
                <x-input name="tunjangan_jabatan" label="Tunjangan Jabatan (Rp)" inputmode="numeric" placeholder="0" />

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('modalJabatan')"
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
        const baseUrl = @json(url('jabatan'));
        let rows = {};
        let editId = null;

        const esc = (v) => $('<div>').text(v ?? '').html();

        function loadData(page = 1) {
            ajaxRequest({
                url: @json(route('jabatan.data')),
                data: { page, search: $('#search').val(), divisi_id: $('#filter_divisi').val() },
                before: () => loadingSkeleton('#tableData', 5, skeleton.table),
                success(res) {
                    rows = {};
                    if (!res.data.length) {
                        $('#tableData').html(emptyTable(5, 'Belum ada jabatan', 'Tambahkan jabatan baru.'));
                    } else {
                        $('#tableData').html(res.data.map((item, i) => {
                            rows[item.id] = item;
                            return `
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4">${res.from + i}</td>
                                    <td class="px-6 py-4 font-semibold">${esc(item.nama)}</td>
                                    <td class="px-6 py-4">${esc(item.divisi?.nama ?? '-')}</td>
                                    <td class="px-6 py-4">${formatRupiah(Math.round(item.tunjangan_jabatan))}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-center gap-2">
                                            <button type="button" onclick="editJabatan(${item.id})" title="Edit"
                                                class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button type="button" onclick="hapusJabatan(${item.id})" title="Hapus"
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
                error: () => $('#tableData').html(tableError(5)),
            });
        }

        function resetForm() {
            $('#formJabatan')[0].reset();
            $('#formJabatan [data-error]').addClass('hidden').text('');
        }

        function tambahJabatan() {
            editId = null;
            resetForm();
            $('#modalTitle').text('Tambah Jabatan');
            showModal('modalJabatan');
        }

        function editJabatan(id) {
            const item = rows[id];
            editId = id;
            resetForm();
            $('#modalTitle').text('Edit Jabatan');
            $('#nama').val(item.nama);
            $('#divisi_id').val(item.divisi_id);
            $('#tunjangan_jabatan').val(formatRupiah(Math.round(item.tunjangan_jabatan), false));
            showModal('modalJabatan');
        }

        function hapusJabatan(id) {
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
            $('#filter_divisi').on('change', () => loadData());

            $('#tunjangan_jabatan').on('input', function() {
                $(this).val(formatRupiah(getNominal($(this).val()), false));
            });

            // Form ini juga memicu App.submitLoading() global (layouts/app), loading ditutup di success/error.
            $('#formJabatan').on('submit', function(e) {
                e.preventDefault();
                const data = $(this).serializeArray().map((f) =>
                    f.name === 'tunjangan_jabatan' ? { ...f, value: getNominal(f.value) } : f);
                if (editId) data.push({ name: '_method', value: 'PUT' });

                ajaxRequest({
                    url: editId ? `${baseUrl}/${editId}` : baseUrl,
                    type: 'POST',
                    data: $.param(data),
                    success(res) {
                        App.loading.hide();
                        closeModal('modalJabatan');
                        showToast(res.message);
                        loadData();
                    },
                    error(xhr) {
                        App.loading.hide();
                        if (xhr.status !== 422) {
                            return Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.' });
                        }
                        $('#formJabatan [data-error]').addClass('hidden').text('');
                        $.each(xhr.responseJSON.errors, (field, msg) => {
                            $(`#formJabatan [data-error="${field}"]`).removeClass('hidden').text(msg[0]);
                        });
                    },
                });
            });
        });
    </script>
@endpush
