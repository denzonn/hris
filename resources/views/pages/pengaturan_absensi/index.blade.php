@extends('layouts.app')

@section('title', 'Pengaturan Absensi')
@section('page_title', 'Pengaturan Absensi')
@section('page_description', 'Jam kerja, toleransi telat & hari kerja per cabang')

@php($namaHari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'])

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="flex flex-col gap-3 border-b border-slate-100 p-5 md:flex-row md:items-center md:justify-between">
            <div class="relative w-full md:w-80">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" id="search" placeholder="Cari nama cabang..."
                    class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-11 text-sm focus:border-primary focus:ring-primary">
            </div>

            <button type="button" id="btnRefresh"
                class="h-10 w-10 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100" title="Refresh">
                <i class="fa-solid fa-rotate"></i>
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-4 w-12">No</th>
                        <th class="px-6 py-4">Cabang</th>
                        <th class="px-6 py-4">Jam Kerja</th>
                        <th class="px-6 py-4">Toleransi</th>
                        <th class="px-6 py-4">Hari Kerja</th>
                        <th class="px-6 py-4">Wajib</th>
                        <th class="px-6 py-4 text-center w-24">Aksi</th>
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

    {{-- MODAL EDIT --}}
    <div id="modalPengaturan"
        class="modal-overlay fixed inset-0 z-[60] hidden bg-black/40 p-4 opacity-0 transition-opacity duration-300">
        <div class="modal-content modal-box w-full max-w-lg -translate-y-6 scale-95 rounded-2xl bg-white shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 id="modalTitle" class="font-semibold text-slate-800">Pengaturan Absensi</h3>
                <button type="button" onclick="closeModal('modalPengaturan')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="formPengaturan" class="space-y-4 p-6">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <x-input name="jam_masuk" label="Jam Masuk" type="time" required />
                    <x-input name="jam_pulang" label="Jam Pulang" type="time" required />
                </div>
                <x-input name="toleransi_telat_menit" label="Toleransi Telat (menit)" type="number" min="0" max="240" />

                <div>
                    <p class="mb-1.5 text-sm font-medium text-slate-700">Hari Kerja</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($namaHari as $no => $hari)
                            <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                <input type="checkbox" name="hari_kerja[]" value="{{ $no }}"
                                    class="rounded border-slate-300 text-primary focus:ring-primary">
                                {{ $hari }}
                            </label>
                        @endforeach
                    </div>
                    <p data-error="hari_kerja" class="mt-1 hidden text-xs text-red-600"></p>
                </div>

                <div class="flex flex-wrap gap-6">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="wajib_foto" id="wajib_foto" value="1"
                            class="rounded border-slate-300 text-primary focus:ring-primary">
                        Wajib foto
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="wajib_lokasi" id="wajib_lokasi" value="1"
                            class="rounded border-slate-300 text-primary focus:ring-primary">
                        Wajib lokasi (radius cabang)
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('modalPengaturan')"
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
        const baseUrl = @json(url('pengaturan_absensi'));
        const namaHari = @json($namaHari);
        const defaultPengaturan = { jam_masuk: '08:00', jam_pulang: '17:00', toleransi_telat_menit: 0, hari_kerja: [1, 2, 3, 4, 5, 6], wajib_foto: true, wajib_lokasi: true };
        let rows = {};
        let editId = null;

        const esc = (v) => $('<div>').text(v ?? '').html();
        const jam = (v) => String(v ?? '').slice(0, 5);
        const ikon = (aktif, label) => aktif
            ? `<span class="mr-1 inline-block rounded-lg bg-emerald-50 px-2 py-1 text-xs text-emerald-700"><i class="fa-solid fa-check"></i> ${label}</span>`
            : '';

        function loadData(page = 1) {
            ajaxRequest({
                url: @json(route('pengaturan_absensi.data')),
                data: { page, search: $('#search').val() },
                before: () => loadingSkeleton('#tableData', 7, skeleton.table),
                success(res) {
                    rows = {};
                    if (!res.data.length) {
                        $('#tableData').html(emptyTable(7, 'Belum ada cabang', 'Tambahkan cabang di menu Master Cabang.'));
                    } else {
                        $('#tableData').html(res.data.map((item, i) => {
                            rows[item.id] = item;
                            const p = item.pengaturan_absensi;
                            const isi = p ? `
                                <td class="px-6 py-4">${jam(p.jam_masuk)} – ${jam(p.jam_pulang)}</td>
                                <td class="px-6 py-4">${p.toleransi_telat_menit} menit</td>
                                <td class="px-6 py-4">${p.hari_kerja.map((h) => namaHari[h]).join(', ')}</td>
                                <td class="px-6 py-4">${ikon(p.wajib_foto, 'Foto')}${ikon(p.wajib_lokasi, 'Lokasi')}${!p.wajib_foto && !p.wajib_lokasi ? '-' : ''}</td>`
                                : `<td colspan="4" class="px-6 py-4 italic text-amber-600">Belum diatur</td>`;
                            return `
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4">${res.from + i}</td>
                                    <td class="px-6 py-4 font-semibold">${esc(item.nama)}</td>
                                    ${isi}
                                    <td class="px-6 py-4">
                                        <div class="flex justify-center">
                                            <button type="button" onclick="editPengaturan(${item.id})" title="Atur"
                                                class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>`;
                        }).join(''));
                    }
                    renderInfo('#info', res);
                    renderPagination(res, 'loadData');
                },
                error: () => $('#tableData').html(tableError(7)),
            });
        }

        function editPengaturan(id) {
            const item = rows[id];
            const p = item.pengaturan_absensi ?? defaultPengaturan;
            editId = id;
            $('#formPengaturan')[0].reset();
            $('#formPengaturan [data-error]').addClass('hidden').text('');
            $('#modalTitle').text(`Pengaturan Absensi – ${item.nama}`);
            $('#jam_masuk').val(jam(p.jam_masuk));
            $('#jam_pulang').val(jam(p.jam_pulang));
            $('#toleransi_telat_menit').val(p.toleransi_telat_menit);
            $('[name="hari_kerja[]"]').each(function() {
                $(this).prop('checked', p.hari_kerja.includes(Number(this.value)));
            });
            $('#wajib_foto').prop('checked', p.wajib_foto);
            $('#wajib_lokasi').prop('checked', p.wajib_lokasi);
            showModal('modalPengaturan');
        }

        $(function() {
            loadData();
            debounceSearch('#search', () => loadData());
            refreshData('#btnRefresh', '#search', () => loadData());

            // Form ini juga memicu App.submitLoading() global (layouts/app), loading ditutup di success/error.
            $('#formPengaturan').on('submit', function(e) {
                e.preventDefault();
                const data = $(this).serializeArray();
                data.push({ name: '_method', value: 'PUT' });

                ajaxRequest({
                    url: `${baseUrl}/${editId}`,
                    type: 'POST',
                    data: $.param(data),
                    success(res) {
                        App.loading.hide();
                        closeModal('modalPengaturan');
                        showToast(res.message);
                        loadData();
                    },
                    error(xhr) {
                        App.loading.hide();
                        if (xhr.status !== 422) {
                            return Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.' });
                        }
                        $('#formPengaturan [data-error]').addClass('hidden').text('');
                        $.each(xhr.responseJSON.errors, (field, msg) => {
                            $(`#formPengaturan [data-error="${field.split('.')[0]}"]`).removeClass('hidden').text(msg[0]);
                        });
                    },
                });
            });
        });
    </script>
@endpush
