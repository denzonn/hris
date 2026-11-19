@extends('layouts.app')

@section('title', 'Lowongan Kerja')
@section('page_title', 'Lowongan Kerja')
@section('page_description', 'Lowongan yang tampil di halaman karir publik')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="flex flex-col gap-3 border-b border-slate-100 p-5 md:flex-row md:items-center md:justify-between">
            <div class="flex w-full flex-col gap-3 md:flex-row">
                <div class="relative w-full md:w-80">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="search" placeholder="Cari judul lowongan..."
                        class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-11 text-sm focus:border-primary focus:ring-primary">
                </div>
                <div class="w-full md:w-44">
                    <x-select name="filter_aktif" placeholder="Semua Status">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </x-select>
                </div>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('karir') }}" target="_blank" title="Lihat halaman karir"
                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </a>
                <button type="button" id="btnRefresh"
                    class="h-10 w-10 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100" title="Refresh">
                    <i class="fa-solid fa-rotate"></i>
                </button>
                <button type="button" onclick="tambahLowongan()"
                    class="flex shrink-0 items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary_hover">
                    <i class="fa-solid fa-plus"></i> Tambah Lowongan
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-4 w-12">No</th>
                        <th class="px-6 py-4">Lowongan</th>
                        <th class="px-6 py-4">Tutup</th>
                        <th class="px-6 py-4">Pelamar</th>
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

    {{-- MODAL TAMBAH / EDIT --}}
    <div id="modalLowongan"
        class="modal-overlay fixed inset-0 z-[60] hidden bg-black/40 p-4 opacity-0 transition-opacity duration-300">
        <div class="modal-content modal-box w-full max-w-2xl -translate-y-6 scale-95 rounded-2xl bg-white shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 id="modalTitle" class="font-semibold text-slate-800">Tambah Lowongan</h3>
                <button type="button" onclick="closeModal('modalLowongan')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="formLowongan" class="space-y-4 p-6">
                @csrf
                <x-input name="judul" label="Judul" placeholder="mis. Teknisi Audio Mobil" required />
                <div class="grid gap-4 md:grid-cols-3">
                    <x-select name="divisi_id" label="Divisi" placeholder="-">
                        @foreach ($divisi as $d)
                            <option value="{{ $d->id }}">{{ $d->nama }}</option>
                        @endforeach
                    </x-select>
                    <x-select name="jabatan_id" label="Jabatan" placeholder="-" />
                    <x-input name="tanggal_tutup" label="Tanggal Tutup" type="date" />
                </div>
                @foreach (['deskripsi' => 'Deskripsi Pekerjaan', 'kualifikasi' => 'Kualifikasi'] as $field => $label)
                    <div>
                        <label for="{{ $field }}" class="mb-1.5 block text-sm font-medium text-slate-700">{{ $label }}</label>
                        <textarea name="{{ $field }}" id="{{ $field }}" rows="4" required placeholder="Satu poin per baris"
                            class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-700 focus:border-primary focus:bg-white focus:ring-primary"></textarea>
                        <p data-error="{{ $field }}" class="mt-1 hidden text-xs text-red-600"></p>
                    </div>
                @endforeach
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="aktif" id="aktif" value="1" checked class="rounded border-slate-300 text-primary focus:ring-primary">
                    Tampilkan di halaman karir
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('modalLowongan')"
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
        const baseUrl = @json(url('lowongan'));
        const karirUrl = @json(url('karir'));
        const jabatanList = @json($jabatan);
        let rows = {};
        let editId = null;

        const esc = (v) => $('<div>').text(v ?? '').html();

        function renderJabatan(divisiId, selected = '') {
            const list = jabatanList.filter((j) => !divisiId || j.divisi_id == divisiId);
            $('#jabatan_id').html('<option value="">-</option>' + list.map((j) => `<option value="${j.id}">${esc(j.nama)}</option>`).join(''));
            $('#jabatan_id').val(list.some((j) => j.id == selected) ? selected : '');
        }

        function loadData(page = 1) {
            ajaxRequest({
                url: @json(route('lowongan.data')),
                data: { page, search: $('#search').val(), aktif: $('#filter_aktif').val() },
                before: () => loadingSkeleton('#tableData', 6, skeleton.table),
                success(res) {
                    rows = {};
                    if (!res.data.length) {
                        $('#tableData').html(emptyTable(6, 'Belum ada lowongan', 'Tambahkan lowongan baru.'));
                    } else {
                        $('#tableData').html(res.data.map((item, i) => {
                            rows[item.id] = item;
                            const lewat = item.tanggal_tutup && item.tanggal_tutup < new Date().toISOString().slice(0, 10);
                            const status = !item.aktif ? formatBadgeStatus('Nonaktif')
                                : (lewat ? '<span class="inline-block rounded-lg bg-slate-200 px-3 py-1 text-[11px] font-semibold text-slate-600">Ditutup</span>'
                                    : '<span class="inline-block rounded-lg bg-emerald-600 px-3 py-1 text-[11px] font-semibold text-white">Dibuka</span>');
                            return `
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4">${res.from + i}</td>
                                    <td class="px-6 py-4">
                                        <a href="${karirUrl}/${item.id}" target="_blank" class="font-semibold hover:text-primary">${esc(item.judul)}</a>
                                        <div class="text-xs text-slate-400">${esc(item.divisi?.nama ?? '-')} · ${esc(item.jabatan?.nama ?? '-')}</div>
                                    </td>
                                    <td class="px-6 py-4">${item.tanggal_tutup ? formatTanggalIndo(item.tanggal_tutup) : '-'}</td>
                                    <td class="px-6 py-4"><a href="${@json(route('pelamar'))}?lowongan_id=${item.id}" class="hover:text-primary">${item.pelamar_count} orang</a></td>
                                    <td class="px-6 py-4">${status}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-center gap-2">
                                            <button type="button" onclick="editLowongan(${item.id})" title="Edit"
                                                class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button type="button" onclick="hapusLowongan(${item.id})" title="Hapus"
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
            $('#formLowongan')[0].reset();
            $('#formLowongan [data-error]').addClass('hidden').text('');
            renderJabatan('');
        }

        function tambahLowongan() {
            editId = null;
            resetForm();
            $('#modalTitle').text('Tambah Lowongan');
            showModal('modalLowongan');
        }

        function editLowongan(id) {
            const item = rows[id];
            editId = id;
            resetForm();
            $('#modalTitle').text('Edit Lowongan');
            ['judul', 'divisi_id', 'tanggal_tutup', 'deskripsi', 'kualifikasi'].forEach((f) => $(`#${f}`).val(item[f] ?? ''));
            renderJabatan(item.divisi_id, item.jabatan_id);
            $('#aktif').prop('checked', item.aktif);
            showModal('modalLowongan');
        }

        function hapusLowongan(id) {
            hapusData({ url: `${baseUrl}/${id}`, name: esc(rows[id].judul), reload: () => loadData() });
        }

        $(function() {
            loadData();
            debounceSearch('#search', () => loadData());
            refreshData('#btnRefresh', '#search', () => loadData());
            $('#filter_aktif').on('change', () => loadData());
            $('#divisi_id').on('change', function() {
                renderJabatan($(this).val(), $('#jabatan_id').val());
            });

            // Form ini juga memicu App.submitLoading() global (layouts/app), loading ditutup di success/error.
            $('#formLowongan').on('submit', function(e) {
                e.preventDefault();
                const data = $(this).serializeArray();
                if (editId) data.push({ name: '_method', value: 'PUT' });

                ajaxRequest({
                    url: editId ? `${baseUrl}/${editId}` : baseUrl,
                    type: 'POST',
                    data: $.param(data),
                    success(res) {
                        App.loading.hide();
                        closeModal('modalLowongan');
                        showToast(res.message);
                        loadData();
                    },
                    error(xhr) {
                        App.loading.hide();
                        if (xhr.status !== 422 || !xhr.responseJSON?.errors) {
                            return Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.' });
                        }
                        $('#formLowongan [data-error]').addClass('hidden').text('');
                        $.each(xhr.responseJSON.errors, (field, msg) => {
                            $(`#formLowongan [data-error="${field}"]`).removeClass('hidden').text(msg[0]);
                        });
                    },
                });
            });
        });
    </script>
@endpush
