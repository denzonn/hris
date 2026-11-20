@extends('layouts.app')

@section('title', $isKaryawan ? 'Izin, Sakit, Cuti' : 'Persetujuan Izin & Lembur')
@section('page_title', $isKaryawan ? 'Izin, Sakit, Cuti' : 'Persetujuan Izin, Cuti & Lembur')
@section('page_description', $isKaryawan ? 'Ajukan izin, sakit, atau cuti' : 'Verifikasi permohonan izin, sakit, cuti, serta lembur pegawai')

@php
    $kartu = $isKaryawan ? [
        ['fa-umbrella-beach', 'bg-sky-50 text-sky-700', 'Tahun '.now()->year, $sisaCuti, 'Sisa Cuti (hari)'],
    ] : [
        ['fa-hourglass-half', 'bg-amber-50 text-amber-700', 'Menunggu', $kpi['pending_izin'] + $kpi['pending_lembur'], 'Antrean Persetujuan'],
        ['fa-calendar-xmark', 'bg-sky-50 text-sky-700', 'Bulan Ini', $kpi['izin_bulan_ini'], 'Pengajuan Izin & Cuti'],
        ['fa-clock', 'bg-red-50 text-red-700', 'Total Jam', str_replace('.', ',', (string) $kpi['jam_lembur']), 'Jam Lembur Disetujui'],
        ['fa-circle-check', 'bg-emerald-50 text-emerald-700', 'Selesai', $kpi['disetujui_bulan_ini'], 'Disetujui '.now()->translatedFormat('F Y')],
    ];
@endphp

@section('content')
    <div class="space-y-6">
        {{-- KPI --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 {{ $isKaryawan ? '' : 'lg:grid-cols-4' }}">
            @foreach ($kartu as [$ikon, $warna, $tag, $nilai, $label])
                <div class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $warna }}">
                            <i class="fa-solid {{ $ikon }}"></i>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $warna }}">{{ $tag }}</span>
                    </div>
                    <p class="text-3xl font-bold tabular-nums text-slate-800">{{ $nilai }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $label }}</p>
                </div>
            @endforeach
        </div>

        {{-- TAB & FILTER --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                @unless ($isKaryawan)
                    <div class="inline-flex self-start rounded-lg border border-slate-200 bg-slate-100 p-1" role="tablist">
                        <button type="button" data-tab="izin" role="tab"
                            class="tab rounded-md px-4 py-2 text-xs font-semibold transition-colors">Izin, Sakit & Cuti ({{ $kpi['pending_izin'] }})</button>
                        <button type="button" data-tab="lembur" role="tab"
                            class="tab rounded-md px-4 py-2 text-xs font-semibold transition-colors">Perintah Lembur ({{ $kpi['pending_lembur'] }})</button>
                    </div>
                @endunless

                <div class="flex flex-wrap items-center gap-3 {{ $isKaryawan ? 'w-full justify-between' : '' }}">
                    <div class="flex flex-wrap items-center gap-3">
                        @unless ($isKaryawan)
                            <div class="relative min-w-[220px]">
                                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                <input type="text" id="search" placeholder="Cari nama pegawai..."
                                    class="w-full rounded-lg border-slate-200 bg-slate-50 py-2 pl-10 text-sm focus:border-primary focus:ring-primary">
                            </div>
                        @endunless
                        <select id="filter_status"
                            class="rounded-lg border-slate-200 bg-slate-50 py-2 text-sm text-slate-700 focus:border-primary focus:ring-primary">
                            <option value="">Semua Status</option>
                            <option value="pending">Menunggu Persetujuan</option>
                            <option value="disetujui">Disetujui</option>
                            <option value="ditolak">Ditolak</option>
                        </select>
                    </div>
                    <button type="button" id="btnBuat"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary_hover">
                        <i class="fa-solid fa-plus"></i> <span>Buat Pengajuan</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- TABEL --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr id="kepalaTabel"></tr>
                    </thead>
                    <tbody id="tableData" class="divide-y divide-slate-100 text-slate-700"></tbody>
                </table>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-100 px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                <div id="info" class="text-sm text-slate-500"></div>
                <div id="pagination" class="flex flex-wrap gap-2"></div>
            </div>
        </section>
    </div>

    {{-- MODAL TAMBAH / EDIT IZIN --}}
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
                <p class="text-xs text-slate-500">Hanya hari kerja (bukan Minggu/hari libur) yang dihitung.
                    @unless ($isKaryawan) Pengajuan dari HR langsung disetujui. @endunless</p>

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
        const izinUrl = @json(url('izin_karyawan'));
        const lemburUrl = @json(url('lembur'));
        const isKaryawan = @json($isKaryawan);
        const token = $('meta[name="csrf-token"]').attr('content');
        let tab = 'izin';
        let rows = {};
        let editId = null;

        const esc = (v) => $('<div>').text(v ?? '').html();
        const jam = (v) => String(v ?? '').slice(0, 5);
        const tglPendek = (t) => formatTanggalIndo(t).replace(/^\w+, /, ''); // "Kamis, 19 Nov 2026" → "19 Nov 2026"

        const JENIS = {
            izin: ['Izin', 'bg-amber-50 text-amber-700'],
            sakit: ['Sakit', 'bg-red-50 text-red-700'],
            cuti: ['Cuti', 'bg-sky-50 text-sky-700'],
        };
        const STATUS = {
            pending: ['Menunggu', 'bg-amber-50 text-amber-700', 'bg-amber-500'],
            disetujui: ['Disetujui', 'bg-emerald-50 text-emerald-700', 'bg-emerald-600'],
            ditolak: ['Ditolak', 'bg-red-50 text-red-700', 'bg-red-600'],
        };
        const KOLOM = {
            izin: ['Pegawai', 'Jenis & Tanggal', 'Alasan / Keterangan', 'Status', isKaryawan ? 'Aksi' : 'Tindakan HR'],
            lembur: ['Pegawai', 'Jadwal & Durasi', 'Tugas Lembur', 'Status', 'Tindakan HR'],
        };

        function pegawai(user) {
            return `
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold ${warnaKategori(user?.divisi_id).avatar}">
                        ${esc(inisial(user?.name))}
                    </div>
                    <div class="min-w-0">
                        <p class="whitespace-nowrap font-semibold text-slate-800">${esc(user?.name)}</p>
                        <p class="whitespace-nowrap text-xs text-slate-500">${esc(user?.jabatan?.nama ?? '-')} • ${esc(user?.divisi?.nama ?? '-')}</p>
                    </div>
                </div>`;
        }

        function badgeStatus(status) {
            const [label, cls, dot] = STATUS[status];
            return `<span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold ${cls}">
                <span class="h-1.5 w-1.5 rounded-full ${dot}"></span>${label}</span>`;
        }

        function tindakan(item) {
            if (item.status !== 'pending') {
                return `<span class="text-xs text-slate-500">${STATUS[item.status][0]} oleh ${esc(item.penyetuju?.name ?? '-')}</span>`;
            }
            const ikon = (fn, title, icon, cls) => `<button type="button" onclick="${fn}(${item.id})" title="${title}"
                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white ${cls}"><i class="fa-solid ${icon} text-xs"></i></button>`;
            const setujui = isKaryawan ? '' : `
                <button type="button" onclick="putuskan(${item.id}, 'disetujui')"
                    class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                    <i class="fa-solid fa-check"></i> Setujui</button>
                <button type="button" onclick="putuskan(${item.id}, 'ditolak')"
                    class="inline-flex items-center gap-1 rounded-md bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">
                    <i class="fa-solid fa-xmark"></i> Tolak</button>`;
            // edit/hapus hanya untuk pengajuan izin (lembur diubah di menu Perintah Lembur)
            const ubah = tab === 'izin'
                ? ikon('editIzin', 'Edit', 'fa-pen', 'text-amber-600 hover:bg-amber-50') + ikon('hapusIzin', 'Hapus', 'fa-trash', 'text-red-600 hover:bg-red-50')
                : '';
            return `<div class="inline-flex flex-wrap items-center justify-end gap-2">${setujui}${ubah}</div>`;
        }

        function barisIzin(item) {
            const hari = Math.round((new Date(item.tanggal_selesai) - new Date(item.tanggal_mulai)) / 864e5) + 1;
            const tanggal = item.tanggal_mulai === item.tanggal_selesai
                ? tglPendek(item.tanggal_mulai)
                : `${tglPendek(item.tanggal_mulai)} – ${tglPendek(item.tanggal_selesai)}`;
            const [jenis, warna] = JENIS[item.jenis];
            const lampiran = item.ada_lampiran
                ? `<a href="${izinUrl}/${item.id}/lampiran" target="_blank" class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline">
                        <i class="fa-solid fa-paperclip"></i> Lampiran</a>` : '';
            return `
                <td class="px-5 py-4">${pegawai(item.user)}</td>
                <td class="px-5 py-4">
                    <span class="mb-1 inline-block rounded px-2 py-0.5 text-[11px] font-semibold ${warna}">${jenis}</span>
                    <p class="whitespace-nowrap tabular-nums text-slate-800">${tanggal} <span class="text-slate-500">(${hari} Hari)</span></p>
                </td>
                <td class="max-w-xs px-5 py-4"><p class="text-slate-600">${esc(item.alasan)}</p>${lampiran}</td>`;
        }

        function barisLembur(item) {
            const menit = (Date.parse(`1970-01-01T${item.jam_selesai}`) - Date.parse(`1970-01-01T${item.jam_mulai}`)) / 6e4;
            return `
                <td class="px-5 py-4">${pegawai(item.user)}</td>
                <td class="px-5 py-4">
                    <p class="whitespace-nowrap font-medium tabular-nums text-slate-800">${tglPendek(item.tanggal)} • ${jam(item.jam_mulai)} – ${jam(item.jam_selesai)}</p>
                    <p class="text-xs font-semibold tabular-nums text-primary">${(menit / 60).toLocaleString('id-ID', { maximumFractionDigits: 1 })} Jam Lembur</p>
                </td>
                <td class="max-w-xs px-5 py-4"><p class="text-slate-600">${esc(item.keterangan ?? '-')}</p></td>`;
        }

        function loadData(page = 1) {
            const kolom = KOLOM[tab];
            $('#kepalaTabel').html(kolom.map((k, i) =>
                `<th class="px-5 py-3.5 ${i === kolom.length - 1 ? 'text-right' : ''}">${k}</th>`).join(''));

            ajaxRequest({
                url: tab === 'izin' ? @json(route('izin_karyawan.data')) : @json(route('lembur.data')),
                data: { page, search: $('#search').val(), status: $('#filter_status').val() },
                before: () => loadingSkeleton('#tableData', 5, skeleton.table),
                success(res) {
                    rows = {};
                    $('#tableData').html(!res.data.length
                        ? emptyTable(5, 'Tidak ada pengajuan', 'Tidak ada data pada filter ini.')
                        : res.data.map((item) => {
                            rows[item.id] = item;
                            return `<tr class="transition-colors hover:bg-slate-50">
                                ${tab === 'izin' ? barisIzin(item) : barisLembur(item)}
                                <td class="px-5 py-4">${badgeStatus(item.status)}</td>
                                <td class="px-5 py-4 text-right">${tindakan(item)}</td>
                            </tr>`;
                        }).join(''));
                    renderInfo('#info', res);
                    renderPagination(res, 'loadData');
                },
                error: () => $('#tableData').html(tableError(5)),
            });
        }

        function gantiTab(baru) {
            tab = baru;
            $('.tab').each(function() {
                const aktif = $(this).data('tab') === tab;
                $(this).attr('aria-selected', aktif)
                    .toggleClass('bg-white text-slate-800 shadow-sm', aktif)
                    .toggleClass('text-slate-500 hover:text-slate-800', !aktif);
            });
            $('#btnBuat span').text(tab === 'izin' ? 'Buat Pengajuan' : 'Buat Perintah Lembur');
            loadData();
        }

        function putuskan(id, status) {
            const item = rows[id];
            const url = tab === 'izin' ? izinUrl : lemburUrl;
            Swal.fire({
                title: status === 'disetujui' ? 'Setujui?' : 'Tolak?',
                html: `${tab === 'izin' ? JENIS[item.jenis][0] : 'Lembur'} <b>${esc(item.user?.name)}</b>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: status === 'disetujui' ? '#059669' : '#dc2626',
                confirmButtonText: status === 'disetujui' ? 'Ya, Setujui' : 'Ya, Tolak',
                cancelButtonText: 'Batal',
            }).then((r) => {
                if (!r.isConfirmed) return;
                ajaxRequest({
                    url: `${url}/${id}/status`,
                    type: 'POST',
                    data: { _token: token, _method: 'PATCH', status },
                    // reload supaya angka KPI & jumlah di tab ikut segar
                    success: (res) => Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1200, showConfirmButton: false })
                        .then(() => location.reload()),
                });
            });
        }

        function resetForm() {
            $('#formIzin')[0].reset();
            $('#formIzin [data-error]').addClass('hidden').text('');
        }

        function tambahIzin() {
            editId = null;
            resetForm();
            $('#modalTitle').text(isKaryawan ? 'Ajukan Izin / Sakit / Cuti' : 'Tambah Pengajuan');
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
            hapusData({ url: `${izinUrl}/${id}`, name: `pengajuan ${esc(rows[id].jenis)} ${esc(rows[id].user?.name)}`, reload: () => loadData() });
        }

        $(function() {
            isKaryawan ? loadData() : gantiTab('izin');
            $('.tab').on('click', function() { gantiTab($(this).data('tab')); });
            debounceSearch('#search', () => loadData());
            $('#filter_status').on('change', () => loadData());
            $('#btnBuat').on('click', () => tab === 'izin' ? tambahIzin() : (location.href = lemburUrl));

            // Form ini juga memicu App.submitLoading() global (layouts/app), loading ditutup di success/error.
            $('#formIzin').on('submit', function(e) {
                e.preventDefault();
                const fd = new FormData(this);
                if (editId) fd.append('_method', 'PUT');

                ajaxRequest({
                    url: editId ? `${izinUrl}/${editId}` : izinUrl,
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
