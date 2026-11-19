@extends('layouts.app')

@section('title', 'Input Absensi')
@section('page_title', 'Input Absensi')
@section('page_description', 'Data absensi karyawan, tambah & koreksi manual')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="flex flex-col gap-3 border-b border-slate-100 p-5 xl:flex-row xl:items-center xl:justify-between">
            <div class="grid w-full gap-3 md:grid-cols-4">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="search" placeholder="Cari nama pegawai..."
                        class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-11 text-sm focus:border-primary focus:ring-primary">
                </div>
                <input type="text" id="filter_tanggal" placeholder="Rentang tanggal" readonly
                    class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-700 focus:border-primary focus:ring-primary">
                <x-select name="filter_cabang" placeholder="Semua Cabang">
                    @foreach ($cabang as $c)
                        <option value="{{ $c->id }}">{{ $c->nama }}</option>
                    @endforeach
                </x-select>
                <x-select name="filter_status" placeholder="Semua Status">
                    @foreach (\App\Models\Absensi::STATUS as $s)
                        <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                    @endforeach
                </x-select>
            </div>

            <div class="flex gap-2">
                <button type="button" id="btnRefresh"
                    class="h-10 w-10 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100" title="Refresh">
                    <i class="fa-solid fa-rotate"></i>
                </button>
                <button type="button" onclick="tambahAbsensi()"
                    class="flex shrink-0 items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary_hover">
                    <i class="fa-solid fa-plus"></i> Tambah Absensi
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
                        <th class="px-6 py-4">Masuk</th>
                        <th class="px-6 py-4">Pulang</th>
                        <th class="px-6 py-4">Status</th>
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
    <div id="modalAbsensi"
        class="modal-overlay fixed inset-0 z-[60] hidden bg-black/40 p-4 opacity-0 transition-opacity duration-300">
        <div class="modal-content modal-box w-full max-w-lg -translate-y-6 scale-95 rounded-2xl bg-white shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 id="modalTitle" class="font-semibold text-slate-800">Tambah Absensi</h3>
                <button type="button" onclick="closeModal('modalAbsensi')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="formAbsensi" class="space-y-4 p-6">
                @csrf
                <x-select name="user_id" label="Pegawai" placeholder="Pilih pegawai" required>
                    @foreach ($pegawai as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}{{ $p->nik ? " ({$p->nik})" : '' }}</option>
                    @endforeach
                </x-select>
                <div class="grid grid-cols-2 gap-4">
                    <x-input name="tanggal" label="Tanggal" type="date" max="{{ today()->toDateString() }}" required />
                    <x-select name="status" label="Status" required>
                        <option value="hadir">Hadir (telat dihitung otomatis)</option>
                        @foreach (['izin', 'sakit', 'cuti', 'alpha', 'libur'] as $s)
                            <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div id="grupJam" class="grid grid-cols-2 gap-4">
                    <x-input name="jam_masuk" label="Jam Masuk" type="time" />
                    <x-input name="jam_pulang" label="Jam Pulang" type="time" />
                </div>
                <x-input name="keterangan" label="Keterangan" placeholder="mis. Lupa absen, HP rusak" />

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('modalAbsensi')"
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
        const baseUrl = @json(url('absensi_karyawan'));
        const storageUrl = @json(asset('storage'));
        let rows = {};
        let editId = null;
        let periode = { mulai: '', selesai: '' };

        const esc = (v) => $('<div>').text(v ?? '').html();
        const jam = (v) => v ? String(v).slice(0, 5) : '-';
        const foto = (path) => path
            ? ` <a href="${storageUrl}/${path}" target="_blank" class="text-slate-400 hover:text-primary" title="Lihat foto"><i class="fa-solid fa-camera"></i></a>` : '';

        function loadData(page = 1) {
            ajaxRequest({
                url: @json(route('absensi_karyawan.data')),
                data: {
                    page, search: $('#search').val(), cabang_id: $('#filter_cabang').val(), status: $('#filter_status').val(),
                    tanggal_mulai: periode.mulai, tanggal_selesai: periode.selesai,
                },
                before: () => loadingSkeleton('#tableData', 8, skeleton.table),
                success(res) {
                    rows = {};
                    if (!res.data.length) {
                        $('#tableData').html(emptyTable(8, 'Belum ada absensi', 'Tidak ada data pada filter ini.'));
                    } else {
                        $('#tableData').html(res.data.map((item, i) => {
                            rows[item.id] = item;
                            const oleh = item.pengubah ? `<div class="mt-1 text-xs text-slate-400">diubah ${esc(item.pengubah.name)}</div>` : '';
                            return `
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4">${res.from + i}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold">${esc(item.user?.name)}</div>
                                        <div class="text-xs text-slate-400">${esc(item.user?.cabang?.nama ?? '-')}</div>
                                    </td>
                                    <td class="px-6 py-4">${formatTanggalIndo(item.tanggal)}</td>
                                    <td class="px-6 py-4">${jam(item.jam_masuk)}${foto(item.foto_masuk)}</td>
                                    <td class="px-6 py-4">${jam(item.jam_pulang)}${foto(item.foto_pulang)}</td>
                                    <td class="px-6 py-4">${formatBadgeStatus(capitalize(item.status))}
                                        ${item.menit_telat ? `<div class="mt-1 text-xs text-amber-600">${formatDurasi(item.menit_telat)}</div>` : ''}</td>
                                    <td class="px-6 py-4">${esc(item.keterangan ?? '-')}${oleh}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-center gap-2">
                                            <button type="button" onclick="editAbsensi(${item.id})" title="Edit"
                                                class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button type="button" onclick="hapusAbsensi(${item.id})" title="Hapus"
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
                error: () => $('#tableData').html(tableError(8)),
            });
        }

        function toggleJam() {
            $('#grupJam').toggleClass('hidden', $('#status').val() !== 'hadir');
        }

        function resetForm() {
            $('#formAbsensi')[0].reset();
            $('#formAbsensi [data-error]').addClass('hidden').text('');
        }

        function tambahAbsensi() {
            editId = null;
            resetForm();
            $('#modalTitle').text('Tambah Absensi');
            toggleJam();
            showModal('modalAbsensi');
        }

        function editAbsensi(id) {
            const item = rows[id];
            editId = id;
            resetForm();
            $('#modalTitle').text('Edit Absensi');
            $('#user_id').val(item.user_id);
            $('#tanggal').val(item.tanggal);
            $('#status').val(item.status === 'telat' ? 'hadir' : item.status);
            $('#jam_masuk').val(item.jam_masuk ? jam(item.jam_masuk) : '');
            $('#jam_pulang').val(item.jam_pulang ? jam(item.jam_pulang) : '');
            $('#keterangan').val(item.keterangan);
            toggleJam();
            showModal('modalAbsensi');
        }

        function hapusAbsensi(id) {
            hapusData({
                url: `${baseUrl}/${id}`,
                name: `absensi ${esc(rows[id].user?.name)} ${formatTanggalIndo(rows[id].tanggal)}`,
                reload: () => loadData(),
            });
        }

        $(function() {
            loadData();
            debounceSearch('#search', () => loadData());
            refreshData('#btnRefresh', '#search', () => loadData());
            $('#filter_cabang, #filter_status').on('change', () => loadData());
            initDateRangePicker('#filter_tanggal', (mulai, selesai) => {
                periode = { mulai, selesai };
                if (!mulai || selesai) loadData();
            });
            $('#status').on('change', toggleJam);

            // Form ini juga memicu App.submitLoading() global (layouts/app), loading ditutup di success/error.
            $('#formAbsensi').on('submit', function(e) {
                e.preventDefault();
                const data = $(this).serializeArray();
                if (editId) data.push({ name: '_method', value: 'PUT' });

                ajaxRequest({
                    url: editId ? `${baseUrl}/${editId}` : baseUrl,
                    type: 'POST',
                    data: $.param(data),
                    success(res) {
                        App.loading.hide();
                        closeModal('modalAbsensi');
                        showToast(res.message);
                        loadData();
                    },
                    error(xhr) {
                        App.loading.hide();
                        if (xhr.status !== 422 || !xhr.responseJSON?.errors) {
                            return Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.' });
                        }
                        $('#formAbsensi [data-error]').addClass('hidden').text('');
                        $.each(xhr.responseJSON.errors, (field, msg) => {
                            $(`#formAbsensi [data-error="${field}"]`).removeClass('hidden').text(msg[0]);
                        });
                    },
                });
            });
        });
    </script>
@endpush
