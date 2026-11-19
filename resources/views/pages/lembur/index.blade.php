@extends('layouts.app')

@section('title', 'Lembur')
@section('page_title', $isKaryawan ? 'Pengajuan Lembur' : 'Perintah Lembur')
@section('page_description', $isKaryawan ? 'Ajukan & pantau lembur Anda' : 'Perintah lembur & persetujuan pengajuan lembur')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="flex flex-col gap-3 border-b border-slate-100 p-5 md:flex-row md:items-center md:justify-between">
            <div class="flex w-full flex-col gap-3 md:flex-row">
                @unless ($isKaryawan)
                    <div class="relative w-full md:w-72">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="search" placeholder="Cari nama pegawai..."
                            class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-11 text-sm focus:border-primary focus:ring-primary">
                    </div>
                @endunless
                <div class="w-full md:w-44">
                    <input type="month" id="filter_bulan" value="{{ now()->format('Y-m') }}"
                        class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-700 focus:border-primary focus:ring-primary">
                </div>
                <div class="w-full md:w-44">
                    <x-select name="filter_status" placeholder="Semua Status">
                        <option value="pending">Pending</option>
                        <option value="disetujui">Disetujui</option>
                        <option value="ditolak">Ditolak</option>
                    </x-select>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="button" id="btnRefresh"
                    class="h-10 w-10 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100" title="Refresh">
                    <i class="fa-solid fa-rotate"></i>
                </button>
                <button type="button" onclick="tambahLembur()"
                    class="flex shrink-0 items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary_hover">
                    <i class="fa-solid fa-plus"></i> {{ $isKaryawan ? 'Ajukan Lembur' : 'Tambah Lembur' }}
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-4 w-12">No</th>
                        <th class="px-6 py-4">Pegawai</th>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4">Jam</th>
                        <th class="px-6 py-4">Durasi</th>
                        <th class="px-6 py-4">Keterangan</th>
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

    {{-- MODAL TAMBAH / EDIT --}}
    <div id="modalLembur"
        class="modal-overlay fixed inset-0 z-[60] hidden bg-black/40 p-4 opacity-0 transition-opacity duration-300">
        <div class="modal-content modal-box w-full max-w-lg -translate-y-6 scale-95 rounded-2xl bg-white shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 id="modalTitle" class="font-semibold text-slate-800">Tambah Lembur</h3>
                <button type="button" onclick="closeModal('modalLembur')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="formLembur" class="space-y-4 p-6">
                @csrf
                @unless ($isKaryawan)
                    <x-select name="user_id" label="Pegawai" placeholder="Pilih pegawai" required>
                        @foreach ($pegawai as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}{{ $p->nik ? " ({$p->nik})" : '' }}</option>
                        @endforeach
                    </x-select>
                @endunless
                <x-input name="tanggal" label="Tanggal" type="date" required />
                <div class="grid grid-cols-2 gap-4">
                    <x-input name="jam_mulai" label="Jam Mulai" type="time" required />
                    <x-input name="jam_selesai" label="Jam Selesai" type="time" required />
                </div>
                <x-input name="keterangan" label="Keterangan" placeholder="Pekerjaan yang dilemburkan" />

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('modalLembur')"
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
        const baseUrl = @json(url('lembur'));
        const isKaryawan = @json($isKaryawan);
        let rows = {};
        let editId = null;

        const esc = (v) => $('<div>').text(v ?? '').html();
        const jam = (v) => String(v ?? '').slice(0, 5);
        const menit = (v) => { const [h, m] = jam(v).split(':').map(Number); return h * 60 + m; };

        function tombol(item) {
            const btn = (fn, title, icon, cls) => `
                <button type="button" onclick="${fn}(${item.id})" title="${title}"
                    class="h-10 w-10 rounded-xl ${cls}"><i class="fa-solid ${icon}"></i></button>`;
            const bisaUbah = !isKaryawan || item.status === 'pending';
            return [
                !isKaryawan && item.status !== 'disetujui' ? btn('setujuiLembur', 'Setujui', 'fa-check', 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100') : '',
                !isKaryawan && item.status !== 'ditolak' ? btn('tolakLembur', 'Tolak', 'fa-ban', 'bg-slate-100 text-slate-600 hover:bg-slate-200') : '',
                bisaUbah ? btn('editLembur', 'Edit', 'fa-pen', 'bg-amber-50 text-amber-600 hover:bg-amber-100') : '',
                bisaUbah ? btn('hapusLembur', 'Hapus', 'fa-trash', 'bg-red-50 text-red-600 hover:bg-red-100') : '',
            ].join('') || '-';
        }

        function loadData(page = 1) {
            ajaxRequest({
                url: @json(route('lembur.data')),
                data: { page, search: $('#search').val(), bulan: $('#filter_bulan').val(), status: $('#filter_status').val() },
                before: () => loadingSkeleton('#tableData', 8, skeleton.table),
                success(res) {
                    rows = {};
                    if (!res.data.length) {
                        $('#tableData').html(emptyTable(8, 'Belum ada lembur', 'Tidak ada data lembur pada filter ini.'));
                    } else {
                        $('#tableData').html(res.data.map((item, i) => {
                            rows[item.id] = item;
                            const oleh = item.penyetuju ? `<div class="mt-1 text-xs text-slate-400">oleh ${esc(item.penyetuju.name)}</div>` : '';
                            return `
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4">${res.from + i}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold">${esc(item.user?.name)}</div>
                                        <div class="text-xs text-slate-400">${esc(item.user?.nik ?? '')}</div>
                                    </td>
                                    <td class="px-6 py-4">${formatTanggalIndo(item.tanggal)}</td>
                                    <td class="px-6 py-4">${jam(item.jam_mulai)} – ${jam(item.jam_selesai)}</td>
                                    <td class="px-6 py-4">${formatDurasi(menit(item.jam_selesai) - menit(item.jam_mulai))}</td>
                                    <td class="px-6 py-4">${esc(item.keterangan ?? '-')}</td>
                                    <td class="px-6 py-4">${formatBadgeStatus(capitalize(item.status))}${oleh}</td>
                                    <td class="px-6 py-4"><div class="flex justify-center gap-2">${tombol(item)}</div></td>
                                </tr>`;
                        }).join(''));
                    }
                    renderInfo('#info', res);
                    renderPagination(res, 'loadData');
                },
                error: () => $('#tableData').html(tableError(8)),
            });
        }

        function resetForm() {
            $('#formLembur')[0].reset();
            $('#formLembur [data-error]').addClass('hidden').text('');
        }

        function tambahLembur() {
            editId = null;
            resetForm();
            $('#modalTitle').text(isKaryawan ? 'Ajukan Lembur' : 'Tambah Perintah Lembur');
            showModal('modalLembur');
        }

        function editLembur(id) {
            const item = rows[id];
            editId = id;
            resetForm();
            $('#modalTitle').text('Edit Lembur');
            $('#user_id').val(item.user_id);
            $('#tanggal').val(item.tanggal);
            $('#jam_mulai').val(jam(item.jam_mulai));
            $('#jam_selesai').val(jam(item.jam_selesai));
            $('#keterangan').val(item.keterangan);
            showModal('modalLembur');
        }

        function hapusLembur(id) {
            hapusData({
                url: `${baseUrl}/${id}`,
                name: `lembur ${esc(rows[id].user?.name)} ${formatTanggalIndo(rows[id].tanggal)}`,
                reload: () => loadData(),
            });
        }

        function ubahStatus(id, status) {
            Swal.fire({
                title: status === 'disetujui' ? 'Setujui lembur?' : 'Tolak lembur?',
                html: `Lembur <b>${esc(rows[id].user?.name)}</b> ${formatTanggalIndo(rows[id].tanggal)}`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: status === 'disetujui' ? '#059669' : '#dc2626',
                confirmButtonText: status === 'disetujui' ? 'Ya, Setujui' : 'Ya, Tolak',
                cancelButtonText: 'Batal',
            }).then((r) => {
                if (!r.isConfirmed) return;
                ajaxRequest({
                    url: `${baseUrl}/${id}/status`,
                    type: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content'), _method: 'PATCH', status },
                    success(res) {
                        showToast(res.message);
                        loadData();
                    },
                });
            });
        }

        const setujuiLembur = (id) => ubahStatus(id, 'disetujui');
        const tolakLembur = (id) => ubahStatus(id, 'ditolak');

        $(function() {
            loadData();
            debounceSearch('#search', () => loadData());
            refreshData('#btnRefresh', '#search', () => loadData());
            $('#filter_bulan, #filter_status').on('change', () => loadData());

            // Form ini juga memicu App.submitLoading() global (layouts/app), loading ditutup di success/error.
            $('#formLembur').on('submit', function(e) {
                e.preventDefault();
                const data = $(this).serializeArray();
                if (editId) data.push({ name: '_method', value: 'PUT' });

                ajaxRequest({
                    url: editId ? `${baseUrl}/${editId}` : baseUrl,
                    type: 'POST',
                    data: $.param(data),
                    success(res) {
                        App.loading.hide();
                        closeModal('modalLembur');
                        showToast(res.message);
                        loadData();
                    },
                    error(xhr) {
                        App.loading.hide();
                        if (xhr.status !== 422 || !xhr.responseJSON?.errors) {
                            return Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.' });
                        }
                        $('#formLembur [data-error]').addClass('hidden').text('');
                        $.each(xhr.responseJSON.errors, (field, msg) => {
                            $(`#formLembur [data-error="${field}"]`).removeClass('hidden').text(msg[0]);
                        });
                    },
                });
            });
        });
    </script>
@endpush
