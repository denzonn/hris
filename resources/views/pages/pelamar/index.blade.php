@extends('layouts.app')

@section('title', 'Data Pelamar')
@section('page_title', 'Data Pelamar')
@section('page_description', 'Lamaran dari halaman karir, proses seleksi & jadikan pegawai')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="flex flex-col gap-3 border-b border-slate-100 p-5 md:flex-row md:items-center md:justify-between">
            <div class="flex w-full flex-col gap-3 md:flex-row">
                <div class="relative w-full md:w-72">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="search" placeholder="Cari nama / email..."
                        class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-11 text-sm focus:border-primary focus:ring-primary">
                </div>
                <div class="w-full md:w-64">
                    <x-select name="filter_lowongan" placeholder="Semua Lowongan">
                        @foreach ($lowongan as $l)
                            <option value="{{ $l->id }}" @selected(request()->integer('lowongan_id') === $l->id)>{{ $l->judul }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div class="w-full md:w-44">
                    <x-select name="filter_status" placeholder="Semua Status">
                        @foreach (\App\Models\Pelamar::STATUS as $s)
                            <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                        @endforeach
                    </x-select>
                </div>
            </div>

            <button type="button" id="btnRefresh"
                class="h-10 w-10 shrink-0 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100" title="Refresh">
                <i class="fa-solid fa-rotate"></i>
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Pelamar</th>
                        <th class="px-6 py-4">Lowongan</th>
                        <th class="px-6 py-4">Pendidikan</th>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-center w-44">Aksi</th>
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

    {{-- MODAL STATUS --}}
    <div id="modalStatus"
        class="modal-overlay fixed inset-0 z-[60] hidden bg-black/40 p-4 opacity-0 transition-opacity duration-300">
        <div class="modal-content modal-box w-full max-w-lg -translate-y-6 scale-95 rounded-2xl bg-white shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 id="modalTitle" class="font-semibold text-slate-800">Proses Pelamar</h3>
                <button type="button" onclick="closeModal('modalStatus')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="formStatus" class="space-y-4 p-6">
                @csrf
                <div id="detailPelamar" class="space-y-1 rounded-xl bg-slate-50 p-4 text-sm text-slate-600"></div>
                <x-select name="status" label="Status" required>
                    @foreach (\App\Models\Pelamar::STATUS as $s)
                        <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                    @endforeach
                </x-select>
                <x-input name="catatan" label="Catatan Internal" placeholder="mis. Interview Senin 10:00" />

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('modalStatus')"
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
        const baseUrl = @json(url('pelamar'));
        const userUrl = @json(route('user'));
        let rows = {};
        let editId = null;

        const esc = (v) => $('<div>').text(v ?? '').html();

        function tombol(item) {
            const btn = (onclick, title, icon, cls) => `<button type="button" onclick="${onclick}" title="${title}"
                class="h-10 w-10 rounded-xl ${cls}"><i class="fa-solid ${icon}"></i></button>`;
            return [
                `<a href="${baseUrl}/${item.id}/cv" target="_blank" title="Lihat CV"
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200"><i class="fa-solid fa-file-pdf"></i></a>`,
                btn(`prosesPelamar(${item.id})`, 'Proses', 'fa-pen', 'bg-amber-50 text-amber-600 hover:bg-amber-100'),
                item.status === 'diterima' && !item.user_id
                    ? `<a href="${userUrl}?pelamar=${item.id}" title="Jadikan Pegawai"
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 hover:bg-emerald-100"><i class="fa-solid fa-user-plus"></i></a>` : '',
                item.user_id ? '' : btn(`hapusPelamar(${item.id})`, 'Hapus', 'fa-trash', 'bg-red-50 text-red-600 hover:bg-red-100'),
            ].join('');
        }

        function loadData(page = 1) {
            ajaxRequest({
                url: @json(route('pelamar.data')),
                data: { page, search: $('#search').val(), lowongan_id: $('#filter_lowongan').val(), status: $('#filter_status').val() },
                before: () => loadingSkeleton('#tableData', 6, skeleton.table),
                success(res) {
                    rows = {};
                    if (!res.data.length) {
                        $('#tableData').html(emptyTable(6, 'Belum ada pelamar', 'Pelamar masuk dari halaman karir.'));
                    } else {
                        $('#tableData').html(res.data.map((item) => {
                            rows[item.id] = item;
                            const pegawai = item.user ? `<div class="mt-1 text-xs text-emerald-600"><i class="fa-solid fa-user-check"></i> Pegawai: ${esc(item.user.name)}</div>` : '';
                            const catatan = item.catatan ? `<div class="mt-1 text-xs text-slate-400">${esc(item.catatan)}</div>` : '';
                            return `
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4">
                                        <div class="font-semibold">${esc(item.nama)}</div>
                                        <div class="text-xs text-slate-400">${esc(item.email)} · ${esc(item.no_hp)}</div>
                                    </td>
                                    <td class="px-6 py-4">${esc(item.lowongan?.judul)}</td>
                                    <td class="px-6 py-4">${esc(item.pendidikan_terakhir)}</td>
                                    <td class="px-6 py-4">${formatTanggal(item.created_at)}</td>
                                    <td class="px-6 py-4">${formatBadgeStatus(capitalize(item.status))}${catatan}${pegawai}</td>
                                    <td class="px-6 py-4"><div class="flex justify-center gap-2">${tombol(item)}</div></td>
                                </tr>`;
                        }).join(''));
                    }
                    renderInfo('#info', res);
                    renderPagination(res, 'loadData');
                },
                error: () => $('#tableData').html(tableError(6)),
            });
        }

        function prosesPelamar(id) {
            const item = rows[id];
            editId = id;
            $('#formStatus [data-error]').addClass('hidden').text('');
            $('#modalTitle').text(`Proses – ${item.nama}`);
            $('#detailPelamar').html(`
                <div><b>Lowongan:</b> ${esc(item.lowongan?.judul)}</div>
                <div><b>Pendidikan:</b> ${esc(item.pendidikan_terakhir)}</div>
                <div><b>Pesan:</b> ${esc(item.pesan ?? '-')}</div>`);
            $('#status').val(item.status);
            $('#catatan').val(item.catatan ?? '');
            showModal('modalStatus');
        }

        function hapusPelamar(id) {
            hapusData({ url: `${baseUrl}/${id}`, name: esc(rows[id].nama), reload: () => loadData() });
        }

        $(function() {
            loadData();
            debounceSearch('#search', () => loadData());
            refreshData('#btnRefresh', '#search', () => loadData());
            $('#filter_lowongan, #filter_status').on('change', () => loadData());

            // Form ini juga memicu App.submitLoading() global (layouts/app), loading ditutup di success/error.
            $('#formStatus').on('submit', function(e) {
                e.preventDefault();
                const data = $(this).serializeArray();
                data.push({ name: '_method', value: 'PATCH' });

                ajaxRequest({
                    url: `${baseUrl}/${editId}/status`,
                    type: 'POST',
                    data: $.param(data),
                    success(res) {
                        App.loading.hide();
                        closeModal('modalStatus');
                        showToast(res.message);
                        loadData();
                    },
                    error(xhr) {
                        App.loading.hide();
                        Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.' });
                    },
                });
            });
        });
    </script>
@endpush
