@extends('layouts.app')

@section('title', 'Gaji Pegawai')
@section('page_title', 'Gaji Pegawai')
@section('page_description', 'Nominal tunjangan & potongan per pegawai')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="flex flex-col gap-3 border-b border-slate-100 p-5 md:flex-row md:items-center md:justify-between">
            <div class="relative w-full md:w-80">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" id="search" placeholder="Cari nama / NIK..."
                    class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-11 text-sm focus:border-primary focus:ring-primary">
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
                        <th class="px-6 py-4 w-12">No</th>
                        <th class="px-6 py-4">Pegawai</th>
                        <th class="px-6 py-4">Gaji Pokok</th>
                        <th class="px-6 py-4">Tunjangan Jabatan</th>
                        <th class="px-6 py-4">Komponen</th>
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
    <div id="modalGaji"
        class="modal-overlay fixed inset-0 z-[60] hidden bg-black/40 p-4 opacity-0 transition-opacity duration-300">
        <div class="modal-content modal-box w-full max-w-lg -translate-y-6 scale-95 rounded-2xl bg-white shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 id="modalTitle" class="font-semibold text-slate-800">Komponen Gaji</h3>
                <button type="button" onclick="closeModal('modalGaji')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="formGaji" class="space-y-4 p-6">
                @csrf
                @forelse ($komponen as $k)
                    <x-input name="komponen[{{ $k->id }}]" id="komponen_{{ $k->id }}" inputmode="numeric" placeholder="0" class="nominal"
                        label="{{ $k->nama }} ({{ $k->tipe }}{{ $k->sifat === 'per_hari_hadir' ? ', per hari hadir' : '' }})" />
                @empty
                    <p class="text-sm text-slate-500">Belum ada komponen gaji. Tambahkan dulu di menu Komponen Gaji.</p>
                @endforelse
                <p class="text-xs text-slate-500">Kosong / 0 = pegawai tidak mendapat komponen tersebut.</p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('modalGaji')"
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
        const baseUrl = @json(url('gaji_pegawai'));
        let rows = {};
        let editId = null;

        const esc = (v) => $('<div>').text(v ?? '').html();
        const rupiah = (v) => formatRupiah(Math.round(v ?? 0));

        function loadData(page = 1) {
            ajaxRequest({
                url: @json(route('gaji_pegawai.data')),
                data: { page, search: $('#search').val() },
                before: () => loadingSkeleton('#tableData', 6, skeleton.table),
                success(res) {
                    rows = {};
                    if (!res.data.length) {
                        $('#tableData').html(emptyTable(6, 'Tidak ada pegawai', 'Tambahkan pegawai di menu User / Pegawai.'));
                    } else {
                        $('#tableData').html(res.data.map((item, i) => {
                            rows[item.id] = item;
                            const komponen = item.komponen_gaji.map((k) => `
                                <div class="text-xs"><span class="${k.tipe === 'potongan' ? 'text-red-600' : 'text-emerald-700'}">${k.tipe === 'potongan' ? '−' : '+'}</span>
                                    ${esc(k.nama)}: ${rupiah(k.pivot.nominal)}${k.sifat === 'per_hari_hadir' ? '/hari' : ''}</div>`).join('') || '-';
                            return `
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4">${res.from + i}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold">${esc(item.name)}</div>
                                        <div class="text-xs text-slate-400">${esc(item.nik ?? '-')} · ${esc(item.jabatan?.nama ?? '-')}</div>
                                    </td>
                                    <td class="px-6 py-4">${rupiah(item.gaji_pokok)}</td>
                                    <td class="px-6 py-4">${rupiah(item.jabatan?.tunjangan_jabatan)}</td>
                                    <td class="px-6 py-4">${komponen}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-center">
                                            <button type="button" onclick="editGaji(${item.id})" title="Atur"
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
                error: () => $('#tableData').html(tableError(6)),
            });
        }

        function editGaji(id) {
            const item = rows[id];
            editId = id;
            $('#formGaji')[0].reset();
            $('#formGaji [data-error]').addClass('hidden').text('');
            $('#modalTitle').text(`Komponen Gaji – ${item.name}`);
            item.komponen_gaji.forEach((k) => $(`#komponen_${k.id}`).val(formatRupiah(Math.round(k.pivot.nominal), false)));
            showModal('modalGaji');
        }

        $(function() {
            loadData();
            debounceSearch('#search', () => loadData());
            refreshData('#btnRefresh', '#search', () => loadData());

            $('.nominal').on('input', function() {
                $(this).val(formatRupiah(getNominal($(this).val()), false));
            });

            // Form ini juga memicu App.submitLoading() global (layouts/app), loading ditutup di success/error.
            $('#formGaji').on('submit', function(e) {
                e.preventDefault();
                const data = $(this).serializeArray();
                data.push({ name: '_method', value: 'PUT' });

                ajaxRequest({
                    url: `${baseUrl}/${editId}`,
                    type: 'POST',
                    data: $.param(data),
                    success(res) {
                        App.loading.hide();
                        closeModal('modalGaji');
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
