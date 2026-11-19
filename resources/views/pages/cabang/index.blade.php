@extends('layouts.app')

@section('title', 'Cabang')
@section('page_title', 'Cabang')
@section('page_description', 'Master data cabang & radius lokasi absensi')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="flex flex-col gap-3 border-b border-slate-100 p-5 md:flex-row md:items-center md:justify-between">
            <div class="relative w-full md:w-80">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" id="search" placeholder="Cari nama / alamat..."
                    class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-11 text-sm focus:border-primary focus:ring-primary">
            </div>

            <div class="flex gap-2">
                <button type="button" id="btnRefresh"
                    class="h-10 w-10 shrink-0 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100" title="Refresh">
                    <i class="fa-solid fa-rotate"></i>
                </button>
                <button type="button" onclick="tambahCabang()"
                    class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary_hover">
                    <i class="fa-solid fa-plus"></i> Tambah Cabang
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-4 w-12">No</th>
                        <th class="px-6 py-4">Nama / Alamat</th>
                        <th class="px-6 py-4">Koordinat</th>
                        <th class="px-6 py-4">Radius</th>
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
    <div id="modalCabang"
        class="modal-overlay fixed inset-0 z-[60] hidden bg-black/40 p-4 opacity-0 transition-opacity duration-300">
        <div class="modal-content modal-box w-full max-w-lg -translate-y-6 scale-95 rounded-2xl bg-white shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 id="modalTitle" class="font-semibold text-slate-800">Tambah Cabang</h3>
                <button type="button" onclick="closeModal('modalCabang')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="formCabang" class="space-y-4 p-6">
                @csrf
                <x-input name="nama" label="Nama Cabang" required />
                <x-input name="alamat" label="Alamat" />
                <div class="grid grid-cols-2 gap-4">
                    <x-input name="latitude" label="Latitude" type="number" step="any" placeholder="-6.2000000" />
                    <x-input name="longitude" label="Longitude" type="number" step="any" placeholder="106.8166667" />
                </div>
                <x-input name="radius_meter" label="Radius Absensi (meter)" type="number" min="1" value="100" required />

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('modalCabang')"
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
        const baseUrl = @json(url('cabang'));
        let rows = {};
        let editId = null;

        const esc = (v) => $('<div>').text(v ?? '').html();

        function loadData(page = 1) {
            ajaxRequest({
                url: @json(route('cabang.data')),
                data: { page, search: $('#search').val() },
                before: () => loadingSkeleton('#tableData', 5, skeleton.table),
                success(res) {
                    rows = {};
                    if (!res.data.length) {
                        $('#tableData').html(emptyTable(5, 'Belum ada cabang', 'Tambahkan cabang baru.'));
                    } else {
                        $('#tableData').html(res.data.map((item, i) => {
                            rows[item.id] = item;
                            const koordinat = item.latitude && item.longitude ?
                                `${item.latitude}, ${item.longitude}` : '-';
                            return `
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4">${res.from + i}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold">${esc(item.nama)}</div>
                                        <div class="text-xs text-slate-400">${esc(item.alamat || '-')}</div>
                                    </td>
                                    <td class="px-6 py-4">${koordinat}</td>
                                    <td class="px-6 py-4">${formatAngka(item.radius_meter)} m</td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-center gap-2">
                                            <button type="button" onclick="editCabang(${item.id})" title="Edit"
                                                class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button type="button" onclick="hapusCabang(${item.id})" title="Hapus"
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
            $('#formCabang')[0].reset();
            $('#formCabang [data-error]').addClass('hidden').text('');
        }

        function tambahCabang() {
            editId = null;
            resetForm();
            $('#modalTitle').text('Tambah Cabang');
            showModal('modalCabang');
        }

        function editCabang(id) {
            const item = rows[id];
            editId = id;
            resetForm();
            $('#modalTitle').text('Edit Cabang');
            ['nama', 'alamat', 'latitude', 'longitude', 'radius_meter'].forEach((f) => $(`#${f}`).val(item[f]));
            showModal('modalCabang');
        }

        function hapusCabang(id) {
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
            $('#formCabang').on('submit', function(e) {
                e.preventDefault();
                let data = $(this).serialize();
                if (editId) data += '&_method=PUT';

                ajaxRequest({
                    url: editId ? `${baseUrl}/${editId}` : baseUrl,
                    type: 'POST',
                    data,
                    success(res) {
                        App.loading.hide();
                        closeModal('modalCabang');
                        showToast(res.message);
                        loadData();
                    },
                    error(xhr) {
                        App.loading.hide();
                        if (xhr.status !== 422) {
                            return Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.' });
                        }
                        $('#formCabang [data-error]').addClass('hidden').text('');
                        $.each(xhr.responseJSON.errors, (field, msg) => {
                            $(`#formCabang [data-error="${field}"]`).removeClass('hidden').text(msg[0]);
                        });
                    },
                });
            });
        });
    </script>
@endpush
