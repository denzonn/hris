@extends('layouts.app')

@section('title', 'Pegawai')
@section('page_title', 'User / Pegawai')
@section('page_description', 'Akun login & data kepegawaian')

@section('content')
    <div class="space-y-6">
        {{-- TOOLBAR --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 flex-1 flex-wrap items-center gap-3 lg:flex-nowrap">
                    <div class="relative min-w-[160px] flex-1">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="search" placeholder="Cari nama / NIK..."
                            class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-11 text-sm focus:border-primary focus:ring-primary">
                    </div>
                    <div class="w-full sm:w-40">
                        <x-select name="filter_cabang" placeholder="Semua Cabang">
                            @foreach ($cabang as $c)
                                <option value="{{ $c->id }}">{{ $c->nama }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <div class="w-full sm:w-36">
                        <x-select name="filter_divisi" placeholder="Semua Divisi">
                            @foreach ($divisi as $d)
                                <option value="{{ $d->id }}">{{ $d->nama }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <div class="w-full sm:w-36">
                        <x-select name="filter_status_kerja" placeholder="Status Kerja">
                            <option value="tetap">Tetap</option>
                            <option value="kontrak">Kontrak</option>
                            <option value="magang">Magang</option>
                        </x-select>
                    </div>
                    <div class="w-full sm:w-36">
                        <x-select name="filter_aktif" placeholder="Semua Status">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </x-select>
                    </div>
                </div>

                <div class="flex shrink-0 gap-2">
                    <button type="button" id="btnRefresh"
                        class="h-10 w-10 shrink-0 rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-100" title="Refresh">
                        <i class="fa-solid fa-rotate"></i>
                    </button>
                    <button type="button" onclick="tambahUser()"
                        class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary_hover">
                        <i class="fa-solid fa-user-plus"></i> Tambah Pegawai
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
            {{-- TABEL --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-7 xl:col-span-8">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="w-12 px-4 py-3.5 text-center">No</th>
                                <th class="min-w-[200px] px-4 py-3.5">Pegawai</th>
                                <th class="px-4 py-3.5">Divisi & Jabatan</th>
                                <th class="px-4 py-3.5">Status</th>
                                <th class="w-32 px-4 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tableData" class="divide-y divide-slate-100 text-slate-700"></tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div id="info" class="text-sm text-slate-500"></div>
                    <div id="pagination" class="flex flex-wrap gap-2"></div>
                </div>
            </div>

            {{-- PROFIL CEPAT --}}
            <div id="profilCepat" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-md lg:sticky lg:top-24 lg:col-span-5 xl:col-span-4">
                <div class="p-10 text-center text-sm text-slate-400">Pilih pegawai untuk melihat profil.</div>
            </div>
        </div>
    </div>

    {{-- MODAL TAMBAH / EDIT --}}
    <div id="modalUser"
        class="modal-overlay fixed inset-0 z-[60] hidden bg-black/40 p-4 opacity-0 transition-opacity duration-300">
        <div class="modal-content modal-box w-full max-w-3xl -translate-y-6 scale-95 rounded-2xl bg-white shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 id="modalTitle" class="font-semibold text-slate-800">Tambah Pegawai</h3>
                <button type="button" onclick="closeModal('modalUser')" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="formUser" class="space-y-6 p-6" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="pelamar_id" id="pelamar_id">
                <div>
                    <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-slate-400">Akun</p>
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input name="name" label="Nama Lengkap" required />
                        <x-input name="email" label="Email" type="email" required />
                        <x-input name="password" label="Password" type="password" autocomplete="new-password" />
                        <x-select name="otoritas_id" label="Otoritas" required>
                            @foreach ($otoritas as $o)
                                <option value="{{ $o->id }}" @selected($o->id === 3)>{{ $o->nama }}</option>
                            @endforeach
                        </x-select>
                    </div>
                </div>

                <div>
                    <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-slate-400">Kepegawaian</p>
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input name="nik" label="NIK Pegawai" />
                        <x-select name="cabang_id" label="Cabang" placeholder="Pilih cabang">
                            @foreach ($cabang as $c)
                                <option value="{{ $c->id }}">{{ $c->nama }}</option>
                            @endforeach
                        </x-select>
                        <x-select name="divisi_id" label="Divisi" placeholder="Pilih divisi">
                            @foreach ($divisi as $d)
                                <option value="{{ $d->id }}">{{ $d->nama }}</option>
                            @endforeach
                        </x-select>
                        <x-select name="jabatan_id" label="Jabatan" placeholder="Pilih jabatan" />
                        <x-select name="status_kerja" label="Status Kerja" required>
                            <option value="tetap">Tetap</option>
                            <option value="kontrak">Kontrak</option>
                            <option value="magang">Magang</option>
                        </x-select>
                        <x-input name="tanggal_masuk" label="Tanggal Masuk" type="date" />
                        <x-input name="tanggal_akhir_kontrak" label="Tanggal Akhir Kontrak" type="date" />
                        <x-input name="jatah_cuti" label="Jatah Cuti / Tahun (hari)" type="number" min="0" max="255" value="12" />
                    </div>
                </div>

                <div>
                    <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-slate-400">Gaji & Pajak</p>
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input name="gaji_pokok" label="Gaji Pokok (Rp)" inputmode="numeric" placeholder="0" />
                        <x-select name="status_ptkp" label="Status PTKP" placeholder="Pilih status PTKP">
                            @foreach ($statusPtkp as $p)
                                <option value="{{ $p }}">{{ $p }}</option>
                            @endforeach
                        </x-select>
                        <x-input name="bank" label="Bank" />
                        <x-input name="no_rekening" label="No. Rekening" />
                        <x-input name="npwp" label="NPWP" />
                    </div>
                </div>

                <div>
                    <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-slate-400">Kontak & Foto</p>
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input name="no_hp" label="No. HP" />
                        <x-input name="alamat" label="Alamat" />
                        <div class="flex items-center gap-4 md:col-span-2">
                            <img id="previewFoto" src="" alt="" class="hidden h-16 w-16 rounded-full object-cover">
                            <div class="flex-1">
                                <x-input name="foto" label="Foto (maks. 2MB)" type="file" accept="image/*" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('modalUser')"
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
        const baseUrl = @json(url('user'));
        const storageUrl = @json(asset('storage'));
        const authId = @json(auth()->id());
        const jabatanList = @json($jabatan);
        let rows = {};
        let editId = null;

        const esc = (v) => $('<div>').text(v ?? '').html();

        let pilihId = null; // pegawai yang tampil di Profil Cepat

        const STATUS_KERJA = {
            tetap: ['Tetap', 'bg-emerald-50 text-emerald-700', 'bg-emerald-600'],
            kontrak: ['Kontrak', 'bg-red-50 text-red-700', 'bg-red-600'],
            magang: ['Magang', 'bg-amber-50 text-amber-700', 'bg-amber-500'],
        };

        function avatar(item, ukuran = 'h-10 w-10 text-sm') {
            if (item.foto) {
                return `<img src="${storageUrl}/${esc(item.foto)}" class="${ukuran} shrink-0 rounded-full object-cover" alt="">`;
            }
            return `<div class="flex ${ukuran} shrink-0 items-center justify-center rounded-full font-bold ${warnaKategori(item.divisi_id).avatar}">${esc(inisial(item.name))}</div>`;
        }

        function badgeStatusKerja(status) {
            const [label, cls, dot] = STATUS_KERJA[status] ?? ['-', 'bg-slate-100 text-slate-600', 'bg-slate-400'];
            return `<span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold ${cls}">
                <span class="h-1.5 w-1.5 rounded-full ${dot}"></span>${label}</span>`;
        }

        function baris(item, no) {
            const dipilih = item.id === pilihId;
            return `
                <tr onclick="pilihPegawai(${item.id})"
                    class="cursor-pointer border-l-4 transition-colors ${dipilih ? 'border-primary bg-red-50/40' : 'border-transparent hover:bg-slate-50'}">
                    <td class="px-4 py-3.5 text-center tabular-nums text-slate-500">${no}</td>
                    <td class="px-4 py-3.5">
                        <div class="flex items-center gap-3">
                            ${avatar(item)}
                            <div class="min-w-0">
                                <div class="whitespace-nowrap font-semibold text-slate-800">${esc(item.name)}</div>
                                <div class="whitespace-nowrap font-mono text-xs text-slate-400">${esc(item.nik || '-')}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3.5">
                        <div class="whitespace-nowrap font-medium text-slate-700">${esc(item.jabatan?.nama ?? '-')}</div>
                        <div class="text-xs font-semibold ${warnaKategori(item.divisi_id).badge.split(' ')[1]}">${item.divisi ? 'Divisi ' + esc(item.divisi.nama) : '-'}</div>
                    </td>
                    <td class="px-4 py-3.5">
                        ${badgeStatusKerja(item.status_kerja)}
                        ${item.aktif ? '' : '<div class="mt-1 text-xs font-semibold text-slate-400">Nonaktif</div>'}
                    </td>
                    <td class="px-4 py-3.5 text-right" onclick="event.stopPropagation()">
                        <div class="inline-flex gap-1.5">
                            <button type="button" onclick="pilihPegawai(${item.id})" title="Lihat profil"
                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white ${dipilih ? 'text-primary' : 'text-slate-500'} hover:bg-red-50">
                                <i class="fa-solid fa-eye text-xs"></i>
                            </button>
                            <button type="button" onclick="editUser(${item.id})" title="Edit"
                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-amber-600 hover:bg-amber-50">
                                <i class="fa-solid fa-pen text-xs"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
        }

        function profil(item) {
            if (!item) {
                return '<div class="p-10 text-center text-sm text-slate-400">Pilih pegawai untuk melihat profil.</div>';
            }
            const info = (label, isi) => `
                <div>
                    <span class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">${label}</span>
                    <span class="font-semibold text-slate-800">${isi}</span>
                </div>`;
            const kontak = (ikon, label, isi) => `
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 py-2">
                    <span class="flex shrink-0 items-center gap-2 text-slate-500"><i class="fa-solid ${ikon} w-4 text-center text-xs"></i>${label}</span>
                    <span class="truncate text-right text-slate-800">${isi}</span>
                </div>`;
            const jobdesk = item.jabatan?.jobdesk?.length
                ? item.jabatan.jobdesk.map((j) => `<li class="flex gap-2"><i class="fa-solid fa-circle-check mt-0.5 text-xs text-emerald-600"></i><span>${esc(j.deskripsi)}</span></li>`).join('')
                : '<li class="italic text-slate-400">Belum ada jobdesk untuk jabatan ini.</li>';
            const toggle = item.id === authId ? '' : `
                <button type="button" onclick="toggleAktif(${item.id})"
                    class="rounded-xl border px-3.5 py-2 text-sm font-medium ${item.aktif
                        ? 'border-red-200 bg-white text-red-600 hover:bg-red-50'
                        : 'border-emerald-200 bg-white text-emerald-600 hover:bg-emerald-50'}">
                    ${item.aktif ? 'Nonaktifkan' : 'Aktifkan'}
                </button>`;

            return `
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 p-5">
                    <div>
                        <h3 class="font-bold text-slate-800">Profil Cepat Pegawai</h3>
                        <p class="text-xs text-slate-500">Detail penugasan & akun login</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold ${item.aktif ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-200 text-slate-600'}">
                        ${item.aktif ? 'Aktif' : 'Nonaktif'}
                    </span>
                </div>

                <div class="space-y-6 p-6">
                    <div class="flex items-center gap-4">
                        ${avatar(item, 'h-16 w-16 text-xl')}
                        <div class="min-w-0">
                            <h4 class="truncate text-lg font-bold text-slate-800">${esc(item.name)}</h4>
                            <p class="text-sm text-slate-500">${esc(item.jabatan?.nama ?? '-')}</p>
                            <p class="mt-0.5 font-mono text-xs font-semibold text-primary">NIK: ${esc(item.nik || '-')}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">
                        ${info('Divisi', esc(item.divisi?.nama ?? '-'))}
                        ${info('Status Kerja', esc(STATUS_KERJA[item.status_kerja]?.[0] ?? '-'))}
                        ${info('Tanggal Masuk', item.tanggal_masuk ? formatTanggalIndo(item.tanggal_masuk).replace(/^\w+, /, '') : '-')}
                        ${info('Hak Akses', `<span class="text-primary">${esc(item.otoritas?.nama ?? '-')}</span>`)}
                        ${item.status_kerja !== 'tetap' && item.tanggal_akhir_kontrak
                            ? info('Akhir Kontrak', formatTanggalIndo(item.tanggal_akhir_kontrak).replace(/^\w+, /, '')) : ''}
                    </div>

                    <div class="space-y-1 text-sm">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Kontak & Akses Sistem</p>
                        ${kontak('fa-envelope', 'Email', `<span class="font-mono text-xs">${esc(item.email)}</span>`)}
                        ${kontak('fa-phone', 'No. HP', esc(item.no_hp || '-'))}
                        ${kontak('fa-location-dot', 'Penempatan', esc(item.cabang?.nama ?? '-'))}
                    </div>

                    <div class="space-y-2">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Jobdesk Ditugaskan</p>
                        <ul class="space-y-1.5 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs leading-relaxed text-slate-600">${jobdesk}</ul>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 p-4">
                    ${toggle}
                    <button type="button" onclick="editUser(${item.id})"
                        class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary_hover">
                        Edit Pegawai
                    </button>
                </div>`;
        }

        function pilihPegawai(id) {
            pilihId = id;
            renderTabel();
            $('#profilCepat').html(profil(rows[id]));
            // layar kecil: profil ada di bawah tabel → gulir ke sana
            if (window.innerWidth < 1024) $('#profilCepat')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        let halaman = null; // respons terakhir, untuk render ulang saat ganti pilihan
        function renderTabel() {
            $('#tableData').html(halaman.data.map((item, i) => baris(item, halaman.from + i)).join(''));
        }

        function loadData(page = 1) {
            ajaxRequest({
                url: @json(route('user.data')),
                data: {
                    page,
                    search: $('#search').val(),
                    cabang_id: $('#filter_cabang').val(),
                    divisi_id: $('#filter_divisi').val(),
                    status_kerja: $('#filter_status_kerja').val(),
                    aktif: $('#filter_aktif').val(),
                },
                before: () => loadingSkeleton('#tableData', 5, skeleton.table),
                success(res) {
                    rows = {};
                    halaman = res;
                    res.data.forEach((item) => rows[item.id] = item);
                    if (!rows[pilihId]) pilihId = res.data[0]?.id ?? null; // pilihan lama tidak ada di halaman ini

                    if (!res.data.length) {
                        $('#tableData').html(emptyTable(5, 'Belum ada pegawai', 'Tambahkan pegawai baru.'));
                    } else {
                        renderTabel();
                    }
                    $('#profilCepat').html(profil(rows[pilihId]));
                    renderInfo('#info', res);
                    renderPagination(res, 'loadData');
                },
                error: () => $('#tableData').html(tableError(5)),
            });
        }

        // Opsi jabatan mengikuti divisi terpilih (filter di client).
        function renderJabatan(divisiId, selected = '') {
            const list = jabatanList.filter((j) => !divisiId || j.divisi_id == divisiId);
            $('#jabatan_id').html('<option value="">Pilih jabatan</option>' +
                list.map((j) => `<option value="${j.id}">${esc(j.nama)}</option>`).join(''));
            $('#jabatan_id').val(list.some((j) => j.id == selected) ? selected : '');
        }

        function resetForm() {
            $('#formUser')[0].reset();
            $('#formUser [data-error]').addClass('hidden').text('');
            $('#previewFoto').addClass('hidden').attr('src', '');
            $('#pelamar_id').val(''); // hidden input tidak ikut di-reset form.reset()
            renderJabatan('');
        }

        function tambahUser() {
            editId = null;
            resetForm();
            $('#modalTitle').text('Tambah Pegawai');
            $('#password').attr('placeholder', 'Minimal 8 karakter');
            showModal('modalUser');
        }

        function editUser(id) {
            const item = rows[id];
            editId = id;
            resetForm();
            $('#modalTitle').text('Edit Pegawai');
            $('#password').attr('placeholder', 'Kosongkan jika tidak diubah');
            ['name', 'email', 'otoritas_id', 'nik', 'cabang_id', 'divisi_id', 'status_kerja', 'tanggal_masuk',
                'tanggal_akhir_kontrak', 'jatah_cuti', 'status_ptkp', 'bank', 'no_rekening', 'npwp', 'no_hp', 'alamat',
            ].forEach((f) => $(`#${f}`).val(item[f] ?? ''));
            renderJabatan(item.divisi_id, item.jabatan_id);
            $('#gaji_pokok').val(formatRupiah(Math.round(item.gaji_pokok), false));
            if (item.foto) $('#previewFoto').removeClass('hidden').attr('src', `${storageUrl}/${item.foto}`);
            showModal('modalUser');
        }

        function toggleAktif(id) {
            const item = rows[id];
            const aksi = item.aktif ? 'menonaktifkan' : 'mengaktifkan';
            Swal.fire({
                title: item.aktif ? 'Nonaktifkan Pegawai?' : 'Aktifkan Pegawai?',
                html: `Yakin ingin ${aksi} <b>${esc(item.name)}</b>?` + (item.aktif ? '<br>Pegawai nonaktif tidak bisa login.' : ''),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: item.aktif ? '#dc2626' : '#059669',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (!result.isConfirmed) return;
                ajaxRequest({
                    url: `${baseUrl}/${id}/aktif`,
                    type: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content'), _method: 'PATCH' },
                    success(res) {
                        showToast(res.message);
                        loadData();
                    },
                });
            });
        }

        $(function() {
            loadData();

            @if ($pelamar)
                // dari tombol "Jadikan Pegawai" di data pelamar
                tambahUser();
                $('#modalTitle').text('Tambah Pegawai dari Pelamar');
                $('#pelamar_id').val(@json($pelamar->id));
                $('#name').val(@json($pelamar->nama));
                $('#email').val(@json($pelamar->email));
                $('#no_hp').val(@json($pelamar->no_hp));
                $('#tanggal_masuk').val(@json(today()->toDateString()));
                $('#divisi_id').val(@json($pelamar->lowongan?->divisi_id));
                renderJabatan(@json($pelamar->lowongan?->divisi_id), @json($pelamar->lowongan?->jabatan_id));
            @endif

            debounceSearch('#search', () => loadData());
            refreshData('#btnRefresh', '#search', () => loadData());
            $('#filter_cabang, #filter_divisi, #filter_status_kerja, #filter_aktif').on('change', () => loadData());
            $('#divisi_id').on('change', function() {
                renderJabatan($(this).val(), $('#jabatan_id').val());
            });

            $('#gaji_pokok').on('input', function() {
                $(this).val(formatRupiah(getNominal($(this).val()), false));
            });

            $('#foto').on('change', function() {
                const file = this.files[0];
                if (file) $('#previewFoto').removeClass('hidden').attr('src', URL.createObjectURL(file));
            });

            // Form ini juga memicu App.submitLoading() global (layouts/app), loading ditutup di success/error.
            $('#formUser').on('submit', function(e) {
                e.preventDefault();
                const data = new FormData(this);
                data.set('gaji_pokok', getNominal(data.get('gaji_pokok')));
                if (!$('#foto').val()) data.delete('foto');
                if (editId) data.append('_method', 'PUT');

                ajaxRequest({
                    url: editId ? `${baseUrl}/${editId}` : baseUrl,
                    type: 'POST',
                    data,
                    processData: false,
                    contentType: false,
                    success(res) {
                        App.loading.hide();
                        closeModal('modalUser');
                        showToast(res.message);
                        loadData();
                    },
                    error(xhr) {
                        App.loading.hide();
                        if (xhr.status !== 422) {
                            return Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.' });
                        }
                        $('#formUser [data-error]').addClass('hidden').text('');
                        $.each(xhr.responseJSON.errors, (field, msg) => {
                            $(`#formUser [data-error="${field}"]`).removeClass('hidden').text(msg[0]);
                        });
                    },
                });
            });
        });
    </script>
@endpush
