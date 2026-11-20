@extends('layouts.landing')

@section('title', $lowongan->judul.' – Karir HUMORA')

@section('content')
    <div class="min-h-screen bg-slate-50">
        <header class="bg-gradient-to-br from-[#620E0E] via-[#8F1111] to-[#B91C1C] text-white">
            <div class="mx-auto max-w-5xl px-6 py-10">
                <a href="{{ route('karir') }}" class="text-sm text-white/80 hover:text-white"><i class="fa-solid fa-arrow-left mr-1"></i> Semua lowongan</a>
                <h1 class="mt-4 text-3xl font-bold">{{ $lowongan->judul }}</h1>
                <p class="mt-2 text-white/80">
                    {{ $lowongan->divisi?->nama ?? 'Umum' }}{{ $lowongan->jabatan ? ' · '.$lowongan->jabatan->nama : '' }}
                    @if ($lowongan->tanggal_tutup)
                        · Ditutup {{ $lowongan->tanggal_tutup->translatedFormat('d F Y') }}
                    @endif
                </p>
            </div>
        </header>

        <main class="mx-auto grid max-w-5xl gap-6 px-6 py-10 lg:grid-cols-5">
            <div class="space-y-6 lg:col-span-3">
                @foreach (['Deskripsi Pekerjaan' => $lowongan->deskripsi, 'Kualifikasi' => $lowongan->kualifikasi] as $judul => $isi)
                    <section class="rounded-2xl border border-slate-200 bg-white p-6">
                        <h2 class="font-semibold text-slate-800">{{ $judul }}</h2>
                        <div class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $isi }}</div>
                    </section>
                @endforeach
            </div>

            <form id="formLamaran" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 lg:col-span-2" enctype="multipart/form-data">
                @csrf
                <h2 class="font-semibold text-slate-800">Kirim Lamaran</h2>
                <x-input name="nama" label="Nama Lengkap" required />
                <x-input name="email" label="Email" type="email" required />
                <x-input name="no_hp" label="No. HP / WhatsApp" inputmode="tel" required />
                <x-select name="pendidikan_terakhir" label="Pendidikan Terakhir" placeholder="Pilih" required>
                    @foreach ($pendidikan as $p)
                        <option value="{{ $p }}">{{ $p }}</option>
                    @endforeach
                </x-select>
                <div>
                    <label for="pesan" class="mb-1.5 block text-sm font-medium text-slate-700">Pesan (opsional)</label>
                    <textarea name="pesan" id="pesan" rows="3" placeholder="Ceritakan singkat tentang diri Anda"
                        class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-700 focus:border-primary focus:bg-white focus:ring-primary"></textarea>
                    <p data-error="pesan" class="mt-1 hidden text-xs text-red-600"></p>
                </div>
                <div>
                    <label for="cv" class="mb-1.5 block text-sm font-medium text-slate-700">CV (PDF, maks 2 MB)</label>
                    <input type="file" name="cv" id="cv" accept="application/pdf" required
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-700 file:mr-3 file:rounded-l-xl file:border-0 file:bg-slate-200 file:px-4 file:py-2.5">
                    <p data-error="cv" class="mt-1 hidden text-xs text-red-600"></p>
                </div>
                <button type="submit" id="btnKirim" class="w-full rounded-xl bg-primary py-3 font-semibold text-white hover:bg-primary_hover">
                    Kirim Lamaran
                </button>
            </form>
        </main>
    </div>
@endsection

@push('addon-script')
    <script>
        $(function() {
            $('#formLamaran').on('submit', function(e) {
                e.preventDefault();
                const $btn = $('#btnKirim').prop('disabled', true).text('Mengirim...');

                ajaxRequest({
                    url: @json(route('karir.store', $lowongan)),
                    type: 'POST',
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    success: (res) => {
                        this.reset();
                        $('#formLamaran [data-error]').addClass('hidden').text('');
                        Swal.fire({ icon: 'success', title: 'Terima kasih!', text: res.message });
                    },
                    error(xhr) {
                        $('#formLamaran [data-error]').addClass('hidden').text('');
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            return $.each(xhr.responseJSON.errors, (field, msg) => {
                                $(`#formLamaran [data-error="${field}"]`).removeClass('hidden').text(msg[0]);
                            });
                        }
                        Swal.fire({
                            icon: 'error', title: 'Gagal',
                            text: xhr.status === 429 ? 'Terlalu banyak percobaan, coba lagi sebentar.' : (xhr.responseJSON?.message ?? 'Terjadi kesalahan.'),
                        });
                    },
                    complete: () => $btn.prop('disabled', false).text('Kirim Lamaran'),
                });
            });
        });
    </script>
@endpush
