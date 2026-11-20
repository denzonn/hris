@extends('layouts.app')

@section('title', 'Jobdesk')
@section('page_title', 'Jobdesk')
@section('page_description', 'Uraian tugas pokok & tanggung jawab per jabatan')

@section('content')
    <div class="space-y-6">
        {{-- TOOLBAR --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex w-full min-w-0 flex-col gap-3 sm:max-w-xl sm:flex-row">
                    <div class="relative flex-1">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="search" placeholder="Cari tugas atau nama jabatan..."
                            class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-11 text-sm focus:border-primary focus:ring-primary">
                    </div>
                    <div class="sm:w-56">
                        <x-select name="filter_jabatan" placeholder="Semua Jabatan">
                            @foreach ($jabatan as $j)
                                <option value="{{ $j->id }}">{{ $j->nama }} ({{ $j->divisi?->nama }})</option>
                            @endforeach
                        </x-select>
                    </div>
                </div>

                <div class="flex shrink-0 gap-2">
                    <button type="button" id="btnRefresh"
                        class="h-10 w-10 shrink-0 rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-100" title="Refresh">
                        <i class="fa-solid fa-rotate"></i>
                    </button>
                    <button type="button" onclick="tambahJobdesk()"
                        class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary_hover">
                        <i class="fa-solid fa-plus"></i> Tambah Jobdesk
                    </button>
                </div>
            </div>
        </div>

        {{-- KARTU PER JABATAN --}}
        <div id="kartuJobdesk" class="grid grid-cols-1 gap-6 md:grid-cols-2"></div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div id="info" class="text-sm text-slate-500"></div>
            <div id="pagination" class="flex flex-wrap gap-2"></div>
        </div>
    </div>

    {{-- MODAL TAMBAH / EDIT --}}
    <div id="modalJobdesk"
        class="modal-overlay fixed inset-0 z-[60] hidden bg-black/40 p-4 opacity-0 transition-opacity duration-300">
        <div class="modal-content modal-box w-full max-w-lg -translate-y-6 scale-95 rounded-2xl bg-white shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 id="modalTitle" class="font-semibold text-slate-800">Tambah Jobdesk</h3>
                <button type="button" onclick="closeModal('modalJobdesk')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="formJobdesk" class="space-y-4 p-6">
                @csrf
                <x-select name="jabatan_id" label="Jabatan" placeholder="Pilih jabatan" required>
                    @foreach ($jabatan as $j)
                        <option value="{{ $j->id }}">{{ $j->nama }} ({{ $j->divisi?->nama }})</option>
                    @endforeach
                </x-select>
                <div>
                    <label for="deskripsi" class="mb-1.5 block text-sm font-medium text-slate-700">Tugas / Tanggung Jawab</label>
                    <textarea name="deskripsi" id="deskripsi" rows="4" required placeholder="mis. Memasang head unit, subwoofer, dan peredam suara kendaraan."
                        class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-700 focus:border-primary focus:bg-white focus:ring-primary"></textarea>
                    <p data-error="deskripsi" class="mt-1 hidden text-xs text-red-600"></p>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('modalJobdesk')"
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
        const baseUrl = @json(url('jobdesk'));
        let tugas = {}; // id jobdesk → item
        let editId = null;

        const esc = (v) => $('<div>').text(v ?? '').html();

        function kartu(jabatan) {
            const warna = warnaKategori(jabatan.divisi_id);
            const daftar = jabatan.jobdesk.map((t) => {
                tugas[t.id] = t;
                return `
                    <li class="group flex items-start gap-2">
                        <i class="fa-solid fa-circle-check mt-0.5 shrink-0 text-primary"></i>
                        <span class="flex-1 whitespace-pre-line">${esc(t.deskripsi)}</span>
                        <span class="flex shrink-0 gap-1 opacity-100 transition-opacity md:opacity-0 md:group-hover:opacity-100">
                            <button type="button" onclick="editJobdesk(${t.id})" title="Edit"
                                class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 bg-white text-amber-600 hover:bg-amber-50">
                                <i class="fa-solid fa-pen text-[11px]"></i>
                            </button>
                            <button type="button" onclick="hapusJobdesk(${t.id})" title="Hapus"
                                class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 bg-white text-red-600 hover:bg-red-50">
                                <i class="fa-solid fa-trash text-[11px]"></i>
                            </button>
                        </span>
                    </li>`;
            }).join('');

            return `
                <div class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
                    <div>
                        <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-4">
                            <div class="min-w-0">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${warna.badge}">${esc(jabatan.divisi?.nama ?? '-')}</span>
                                <h3 class="mt-1.5 truncate text-base font-bold text-slate-800">${esc(jabatan.nama)}</h3>
                            </div>
                            <button type="button" onclick="tambahJobdesk(${jabatan.id})" title="Tambah tugas"
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-primary hover:bg-red-50">
                                <i class="fa-solid fa-plus text-xs"></i>
                            </button>
                        </div>

                        <div class="space-y-2.5 py-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Tanggung Jawab Utama</p>
                            ${daftar
                                ? `<ul class="space-y-2 text-sm text-slate-700">${daftar}</ul>`
                                : '<p class="text-sm italic text-slate-400">Belum ada jobdesk untuk jabatan ini.</p>'}
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-100 pt-4 text-xs text-slate-500">
                        <span>${jabatan.jobdesk_max_updated_at ? 'Diperbarui: ' + formatTanggalIndo(String(jabatan.jobdesk_max_updated_at).replace(' ', 'T')) : '&nbsp;'}</span>
                        <span class="font-semibold text-primary">${jabatan.pegawai_count} Pegawai Ditugaskan</span>
                    </div>
                </div>`;
        }

        function loadData(page = 1) {
            ajaxRequest({
                url: @json(route('jobdesk.data')),
                data: { page, search: $('#search').val(), jabatan_id: $('#filter_jabatan').val() },
                before: () => $('#kartuJobdesk').html(Array(4).fill(
                    '<div class="h-56 animate-pulse rounded-2xl border border-slate-200 bg-white"></div>').join('')),
                success(res) {
                    tugas = {};
                    $('#kartuJobdesk').html(res.data.length ? res.data.map(kartu).join('') : `
                        <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-500 md:col-span-2">
                            <i class="fa-solid fa-list-check text-4xl text-slate-300"></i>
                            <p class="mt-3 font-semibold text-slate-700">Tidak ada jabatan</p>
                            <p class="text-sm">Ubah pencarian atau tambahkan jabatan di menu Jabatan.</p>
                        </div>`);
                    renderInfo('#info', res);
                    renderPagination(res, 'loadData');
                },
                error: () => $('#kartuJobdesk').html(
                    '<div class="rounded-2xl border border-red-200 bg-red-50 p-6 text-center text-sm text-red-600 md:col-span-2">Gagal memuat data.</div>'),
            });
        }

        function resetForm() {
            $('#formJobdesk')[0].reset();
            $('#formJobdesk [data-error]').addClass('hidden').text('');
        }

        function tambahJobdesk(jabatanId = '') {
            editId = null;
            resetForm();
            $('#modalTitle').text('Tambah Jobdesk');
            $('#jabatan_id').val(jabatanId);
            showModal('modalJobdesk');
        }

        function editJobdesk(id) {
            const item = tugas[id];
            editId = id;
            resetForm();
            $('#modalTitle').text('Edit Jobdesk');
            $('#jabatan_id').val(item.jabatan_id);
            $('#deskripsi').val(item.deskripsi);
            showModal('modalJobdesk');
        }

        function hapusJobdesk(id) {
            const teks = tugas[id].deskripsi;
            hapusData({
                url: `${baseUrl}/${id}`,
                name: esc(teks.length > 60 ? teks.slice(0, 60) + '…' : teks),
                reload: () => loadData(),
            });
        }

        $(function() {
            loadData();
            debounceSearch('#search', () => loadData());
            refreshData('#btnRefresh', '#search', () => loadData());
            $('#filter_jabatan').on('change', () => loadData());

            // Form ini juga memicu App.submitLoading() global (layouts/app), loading ditutup di success/error.
            $('#formJobdesk').on('submit', function(e) {
                e.preventDefault();
                let data = $(this).serialize();
                if (editId) data += '&_method=PUT';

                ajaxRequest({
                    url: editId ? `${baseUrl}/${editId}` : baseUrl,
                    type: 'POST',
                    data,
                    success(res) {
                        App.loading.hide();
                        closeModal('modalJobdesk');
                        showToast(res.message);
                        loadData();
                    },
                    error(xhr) {
                        App.loading.hide();
                        if (xhr.status !== 422) {
                            return Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.' });
                        }
                        $('#formJobdesk [data-error]').addClass('hidden').text('');
                        $.each(xhr.responseJSON.errors, (field, msg) => {
                            $(`#formJobdesk [data-error="${field}"]`).removeClass('hidden').text(msg[0]);
                        });
                    },
                });
            });
        });
    </script>
@endpush
