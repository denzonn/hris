@extends('layouts.app')

@section('title', 'Pegawai')
@section('page_title', 'User / Pegawai')
@section('page_description', 'Akun login & data kepegawaian')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="flex flex-col gap-3 border-b border-slate-100 p-5 xl:flex-row xl:items-center xl:justify-between">
            <div class="grid w-full gap-3 md:grid-cols-4">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="search" placeholder="Cari nama / email / NIK..."
                        class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-11 text-sm focus:border-primary focus:ring-primary">
                </div>
                <x-select name="filter_cabang" placeholder="Semua Cabang">
                    @foreach ($cabang as $c)
                        <option value="{{ $c->id }}">{{ $c->nama }}</option>
                    @endforeach
                </x-select>
                <x-select name="filter_divisi" placeholder="Semua Divisi">
                    @foreach ($divisi as $d)
                        <option value="{{ $d->id }}">{{ $d->nama }}</option>
                    @endforeach
                </x-select>
                <x-select name="filter_aktif" placeholder="Semua Status">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </x-select>
            </div>

            <div class="flex gap-2">
                <button type="button" id="btnRefresh"
                    class="h-10 w-10 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100" title="Refresh">
                    <i class="fa-solid fa-rotate"></i>
                </button>
                <button type="button" onclick="tambahUser()"
                    class="flex shrink-0 items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary_hover">
                    <i class="fa-solid fa-plus"></i> Tambah Pegawai
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-4 w-12">No</th>
                        <th class="px-6 py-4">Pegawai</th>
                        <th class="px-6 py-4">Cabang / Divisi / Jabatan</th>
                        <th class="px-6 py-4">Status Kerja</th>
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

        function fotoHtml(item) {
            if (item.foto) {
                return `<img src="${storageUrl}/${esc(item.foto)}" class="h-11 w-11 rounded-full object-cover" alt="">`;
            }
            return `<div class="flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 font-semibold text-slate-500">${esc(item.name.charAt(0).toUpperCase())}</div>`;
        }

        function loadData(page = 1) {
            ajaxRequest({
                url: @json(route('user.data')),
                data: {
                    page,
                    search: $('#search').val(),
                    cabang_id: $('#filter_cabang').val(),
                    divisi_id: $('#filter_divisi').val(),
                    aktif: $('#filter_aktif').val(),
                },
                before: () => loadingSkeleton('#tableData', 5, skeleton.table),
                success(res) {
                    rows = {};
                    if (!res.data.length) {
                        $('#tableData').html(emptyTable(6, 'Belum ada pegawai', 'Tambahkan pegawai baru.'));
                    } else {
                        $('#tableData').html(res.data.map((item, i) => {
                            rows[item.id] = item;
                            const toggle = item.id === authId ? '' : `
                                <button type="button" onclick="toggleAktif(${item.id})" title="${item.aktif ? 'Nonaktifkan' : 'Aktifkan'}"
                                    class="h-10 w-10 rounded-xl ${item.aktif ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'}">
                                    <i class="fa-solid ${item.aktif ? 'fa-user-slash' : 'fa-user-check'}"></i>
                                </button>`;
                            return `
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4">${res.from + i}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            ${fotoHtml(item)}
                                            <div>
                                                <div class="font-semibold">${esc(item.name)}</div>
                                                <div class="text-xs text-slate-400">${esc(item.email)}</div>
                                                <div class="text-xs text-slate-400">NIK: ${esc(item.nik || '-')} · ${esc(item.otoritas?.nama ?? '-')}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div>${esc(item.cabang?.nama ?? '-')}</div>
                                        <div class="text-xs text-slate-400">${esc(item.divisi?.nama ?? '-')} / ${esc(item.jabatan?.nama ?? '-')}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div>${capitalize(item.status_kerja)}</div>
                                        <div class="text-xs text-slate-400">Masuk: ${formatTanggal(item.tanggal_masuk)}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-block rounded-lg px-3 py-1 text-[11px] font-semibold ${item.aktif ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600'}">
                                            ${item.aktif ? 'Aktif' : 'Nonaktif'}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-center gap-2">
                                            <button type="button" onclick="editUser(${item.id})" title="Edit"
                                                class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            ${toggle}
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
            $('#filter_cabang, #filter_divisi, #filter_aktif').on('change', () => loadData());
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
