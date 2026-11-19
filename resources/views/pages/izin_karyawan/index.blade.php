@extends('layouts.app')

@section('title', 'Izin, Sakit, Cuti')
@section('page_title', 'Izin, Sakit, Cuti')
@section('page_description', $isKaryawan ? 'Ajukan izin, sakit, atau cuti' : 'Pengajuan izin, sakit & cuti karyawan')

@section('content')
    @if ($isKaryawan)
        <div class="mb-6 flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                <i class="fa-solid fa-umbrella-beach text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-slate-500">Sisa cuti tahun {{ now()->year }}</p>
                <p class="text-2xl font-bold text-slate-800">{{ $sisaCuti }} hari</p>
            </div>
        </div>
    @endif

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
                <div class="w-full md:w-40">
                    <x-select name="filter_jenis" placeholder="Semua Jenis">
                        @foreach (\App\Models\PengajuanIzin::JENIS as $j)
                            <option value="{{ $j }}">{{ ucfirst($j) }}</option>
                        @endforeach
                    </x-select>
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
                <button type="button" onclick="tambahIzin()"
                    class="flex shrink-0 items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary_hover">
                    <i class="fa-solid fa-plus"></i> {{ $isKaryawan ? 'Ajukan' : 'Tambah Pengajuan' }}
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-4 w-12">No</th>
                        <th class="px-6 py-4">Pegawai</th>
                        <th class="px-6 py-4">Jenis</th>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4">Alasan</th>
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
    <div id="modalIzin"
        class="modal-overlay fixed inset-0 z-[60] hidden bg-black/40 p-4 opacity-0 transition-opacity duration-300">
        <div class="modal-content modal-box w-full max-w-lg -translate-y-6 scale-95 rounded-2xl bg-white shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 id="modalTitle" class="font-semibold text-slate-800">Pengajuan</h3>
                <button type="button" onclick="closeModal('modalIzin')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="formIzin" class="space-y-4 p-6">
                @csrf
                @unless ($isKaryawan)
                    <x-select name="user_id" label="Pegawai" placeholder="Pilih pegawai" required>
                        @foreach ($pegawai as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}{{ $p->nik ? " ({$p->nik})" : '' }}</option>
                        @endforeach
                    </x-select>
                @endunless
                <x-select name="jenis" label="Jenis" required>
                    @foreach (\App\Models\PengajuanIzin::JENIS as $j)
                        <option value="{{ $j }}">{{ ucfirst($j) }}</option>
                    @endforeach
                </x-select>
                <div class="grid grid-cols-2 gap-4">
                    <x-input name="tanggal_mulai" label="Tanggal Mulai" type="date" required />
                    <x-input name="tanggal_selesai" label="Tanggal Selesai" type="date" required />
                </div>
                <x-input name="alasan" label="Alasan" required />
                <div>
                    <label for="lampiran" class="mb-1.5 block text-sm font-medium text-slate-700">Lampiran (opsional, jpg/png/pdf maks 2 MB)</label>
                    <input type="file" name="lampiran" id="lampiran" accept=".jpg,.jpeg,.png,.pdf"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-700 file:mr-3 file:rounded-l-xl file:border-0 file:bg-slate-200 file:px-4 file:py-2.5">
                    <p data-error="lampiran" class="mt-1 hidden text-xs text-red-600"></p>
                </div>
                <p class="text-xs text-slate-500">Hanya hari kerja (bukan Minggu/hari libur) yang dihitung.</p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('modalIzin')"
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
        const baseUrl = @json(url('izin_karyawan'));
        const isKaryawan = @json($isKaryawan);
        let rows = {};
        let editId = null;

        const esc = (v) => $('<div>').text(v ?? '').html();

        function tombol(item) {
            if (item.status !== 'pending') return '-';
            const btn = (fn, title, icon, cls) => `
                <button type="button" onclick="${fn}(${item.id})" title="${title}"
                    class="h-10 w-10 rounded-xl ${cls}"><i class="fa-solid ${icon}"></i></button>`;
            return [
                isKaryawan ? '' : btn('setujuiIzin', 'Setujui', 'fa-check', 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'),
                isKaryawan ? '' : btn('tolakIzin', 'Tolak', 'fa-ban', 'bg-slate-100 text-slate-600 hover:bg-slate-200'),
                btn('editIzin', 'Edit', 'fa-pen', 'bg-amber-50 text-amber-600 hover:bg-amber-100'),
                btn('hapusIzin', 'Hapus', 'fa-trash', 'bg-red-50 text-red-600 hover:bg-red-100'),
            ].join('');
        }

        function loadData(page = 1) {
            ajaxRequest({
                url: @json(route('izin_karyawan.data')),
                data: { page, search: $('#search').val(), jenis: $('#filter_jenis').val(), status: $('#filter_status').val() },
                before: () => loadingSkeleton('#tableData', 7, skeleton.table),
                success(res) {
                    rows = {};
                    if (!res.data.length) {
                        $('#tableData').html(emptyTable(7, 'Belum ada pengajuan', 'Tidak ada pengajuan pada filter ini.'));
                    } else {
                        $('#tableData').html(res.data.map((item, i) => {
                            rows[item.id] = item;
                            const tgl = item.tanggal_mulai === item.tanggal_selesai ? formatTanggalIndo(item.tanggal_mulai)
                                : `${formatTanggalIndo(item.tanggal_mulai)}<br><span class="text-xs text-slate-400">s.d.</span> ${formatTanggalIndo(item.tanggal_selesai)}`;
                            const lampiran = item.ada_lampiran
                                ? `<a href="${baseUrl}/${item.id}/lampiran" target="_blank" class="mt-1 block text-xs text-primary hover:underline"><i class="fa-solid fa-paperclip"></i> Lampiran</a>` : '';
                            const oleh = item.penyetuju ? `<div class="mt-1 text-xs text-slate-400">oleh ${esc(item.penyetuju.name)}</div>` : '';
                            return `
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4">${res.from + i}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold">${esc(item.user?.name)}</div>
                                        <div class="text-xs text-slate-400">${esc(item.user?.nik ?? '')}</div>
                                    </td>
                                    <td class="px-6 py-4">${formatBadgeStatus(capitalize(item.jenis))}</td>
                                    <td class="px-6 py-4">${tgl}</td>
                                    <td class="px-6 py-4">${esc(item.alasan)}${lampiran}</td>
                                    <td class="px-6 py-4">${formatBadgeStatus(capitalize(item.status))}${oleh}</td>
                                    <td class="px-6 py-4"><div class="flex justify-center gap-2">${tombol(item)}</div></td>
                                </tr>`;
                        }).join(''));
                    }
                    renderInfo('#info', res);
                    renderPagination(res, 'loadData');
                },
                error: () => $('#tableData').html(tableError(7)),
            });
        }

        function resetForm() {
            $('#formIzin')[0].reset();
            $('#formIzin [data-error]').addClass('hidden').text('');
        }

        function tambahIzin() {
            editId = null;
            resetForm();
            $('#modalTitle').text(isKaryawan ? 'Ajukan Izin / Sakit / Cuti' : 'Tambah Pengajuan (langsung disetujui)');
            showModal('modalIzin');
        }

        function editIzin(id) {
            const item = rows[id];
            editId = id;
            resetForm();
            $('#modalTitle').text('Edit Pengajuan');
            $('#user_id').val(item.user_id);
            $('#jenis').val(item.jenis);
            $('#tanggal_mulai').val(item.tanggal_mulai);
            $('#tanggal_selesai').val(item.tanggal_selesai);
            $('#alasan').val(item.alasan);
            showModal('modalIzin');
        }

        function hapusIzin(id) {
            hapusData({
                url: `${baseUrl}/${id}`,
                name: `pengajuan ${esc(rows[id].jenis)} ${esc(rows[id].user?.name)}`,
                reload: () => loadData(),
            });
        }

        function ubahStatus(id, status) {
            const item = rows[id];
            Swal.fire({
                title: status === 'disetujui' ? 'Setujui pengajuan?' : 'Tolak pengajuan?',
                html: `${capitalize(item.jenis)} <b>${esc(item.user?.name)}</b>`,
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

        const setujuiIzin = (id) => ubahStatus(id, 'disetujui');
        const tolakIzin = (id) => ubahStatus(id, 'ditolak');

        $(function() {
            loadData();
            debounceSearch('#search', () => loadData());
            refreshData('#btnRefresh', '#search', () => loadData());
            $('#filter_jenis, #filter_status').on('change', () => loadData());

            // Form ini juga memicu App.submitLoading() global (layouts/app), loading ditutup di success/error.
            $('#formIzin').on('submit', function(e) {
                e.preventDefault();
                const fd = new FormData(this);
                if (editId) fd.append('_method', 'PUT');

                ajaxRequest({
                    url: editId ? `${baseUrl}/${editId}` : baseUrl,
                    type: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false,
                    success(res) {
                        App.loading.hide();
                        closeModal('modalIzin');
                        showToast(res.message);
                        loadData();
                    },
                    error(xhr) {
                        App.loading.hide();
                        if (xhr.status !== 422 || !xhr.responseJSON?.errors) {
                            return Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.' });
                        }
                        $('#formIzin [data-error]').addClass('hidden').text('');
                        $.each(xhr.responseJSON.errors, (field, msg) => {
                            $(`#formIzin [data-error="${field}"]`).removeClass('hidden').text(msg[0]);
                        });
                    },
                });
            });
        });
    </script>
@endpush
