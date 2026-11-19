@extends('layouts.app')

@section('title', 'Profil')
@section('page_title', 'Profil')
@section('page_description', 'Data akun & ganti password')

@php
    $info = [
        'Nama' => $user->name,
        'Email' => $user->email,
        'NIK' => $user->nik,
        'Otoritas' => $user->otoritas?->nama,
        'Cabang' => $user->cabang?->nama,
        'Divisi' => $user->divisi?->nama,
        'Jabatan' => $user->jabatan?->nama,
        'Status Kerja' => $user->status_kerja ? ucfirst($user->status_kerja) : null,
        'Tanggal Masuk' => $user->tanggal_masuk?->translatedFormat('d F Y'),
        'Akhir Kontrak' => $user->tanggal_akhir_kontrak?->translatedFormat('d F Y'),
        'No. HP' => $user->no_hp,
        'Rekening' => trim(($user->bank ?? '').' '.($user->no_rekening ?? '')),
    ];
@endphp

@section('content')
    @if ($user->wajib_ganti_password)
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <i class="fa-solid fa-triangle-exclamation mr-1"></i>
            Password Anda dibuat oleh admin. <b>Ganti password terlebih dahulu</b> untuk bisa memakai menu lain.
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 lg:col-span-2">
            <div class="flex items-center gap-4">
                @if ($user->foto)
                    <img src="{{ asset('storage/'.$user->foto) }}" alt="" class="h-16 w-16 rounded-2xl object-cover">
                @else
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-2xl text-slate-400">
                        <i class="fa-solid fa-user"></i>
                    </div>
                @endif
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">{{ $user->name }}</h3>
                    <p class="text-sm text-slate-500">{{ $user->jabatan?->nama ?? $user->otoritas?->nama }}</p>
                </div>
            </div>

            <dl class="mt-6 grid gap-x-6 gap-y-3 text-sm md:grid-cols-2">
                @foreach ($info as $label => $nilai)
                    <div class="border-b border-slate-100 pb-2">
                        <dt class="text-xs text-slate-500">{{ $label }}</dt>
                        <dd class="font-medium text-slate-700">{{ $nilai ?: '-' }}</dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-4 text-xs text-slate-400">Data salah? Hubungi HR untuk perubahan.</p>
        </div>

        <form id="formPassword" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6">
            @csrf
            <h3 class="font-semibold text-slate-800">Ganti Password</h3>
            <x-input name="password_lama" label="Password Lama" type="password" autocomplete="current-password" required />
            <x-input name="password" label="Password Baru (min. 8 karakter)" type="password" autocomplete="new-password" required />
            <x-input name="password_confirmation" label="Ulangi Password Baru" type="password" autocomplete="new-password" required />
            <button type="submit" class="w-full rounded-xl bg-primary py-2.5 text-sm font-medium text-white hover:bg-primary_hover">Simpan Password</button>
        </form>
    </div>
@endsection

@push('addon-script')
    <script>
        $(function() {
            // Form ini juga memicu App.submitLoading() global (layouts/app), loading ditutup di success/error.
            $('#formPassword').on('submit', function(e) {
                e.preventDefault();
                const data = $(this).serializeArray();
                data.push({ name: '_method', value: 'PUT' });

                ajaxRequest({
                    url: @json(route('profile.password')),
                    type: 'POST',
                    data: $.param(data),
                    success(res) {
                        App.loading.hide();
                        $('#formPassword')[0].reset();
                        $('#formPassword [data-error]').addClass('hidden').text('');
                        @if ($user->wajib_ganti_password)
                            Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message }).then(() => location.href = @json(route('dashboard')));
                        @else
                            showToast(res.message);
                        @endif
                    },
                    error(xhr) {
                        App.loading.hide();
                        if (xhr.status !== 422 || !xhr.responseJSON?.errors) {
                            return Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.' });
                        }
                        $('#formPassword [data-error]').addClass('hidden').text('');
                        $.each(xhr.responseJSON.errors, (field, msg) => {
                            $(`#formPassword [data-error="${field}"]`).removeClass('hidden').text(msg[0]);
                        });
                    },
                });
            });
        });
    </script>
@endpush
