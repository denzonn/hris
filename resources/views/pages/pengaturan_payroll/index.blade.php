@extends('layouts.app')

@section('title', 'Pengaturan Payroll')
@section('page_title', 'Pengaturan Payroll')
@section('page_description', 'Periode cut-off & tarif potongan')

@php($rp = fn ($v) => number_format((float) $v, 0, ',', '.'))

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <form id="formPengaturan" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 lg:col-span-2">
            @csrf
            <x-input name="tanggal_cutoff" label="Tanggal Cut-off (1–28, kosong = akhir bulan)" type="number" min="1" max="28"
                value="{{ $pengaturan->tanggal_cutoff }}" placeholder="Akhir bulan" />
            <x-input name="potongan_per_menit_telat" label="Potongan per Menit Telat (Rp)" inputmode="numeric" class="nominal"
                value="{{ $rp($pengaturan->potongan_per_menit_telat) }}" />
            <x-input name="potongan_per_alpha" label="Potongan per Hari Alpha (Rp)" inputmode="numeric" class="nominal"
                value="{{ $rp($pengaturan->potongan_per_alpha) }}" />

            <div class="flex justify-end pt-2">
                <button type="submit"
                    class="rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary_hover">Simpan</button>
            </div>
        </form>

        <div class="space-y-3 rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600">
            <h3 class="font-semibold text-slate-800">Cara hitung</h3>
            <p><b>Periode:</b> cut-off 25 → gaji November = absensi 26 Okt – 25 Nov. Tanpa cut-off → 1 – akhir bulan.</p>
            <p><b>Upah lembur</b> (Kepmenaker 102/2004): upah sejam = 1/173 × (gaji pokok + tunjangan jabatan + tunjangan tetap). Jam ke-1 × 1,5, jam berikutnya × 2. Hanya lembur yang disetujui.</p>
            <p><b>Potongan telat</b> = total menit telat × tarif. <b>Potongan alpha</b> = jumlah alpha × tarif.</p>
        </div>
    </div>
@endsection

@push('addon-script')
    <script>
        $(function() {
            $('.nominal').on('input', function() {
                $(this).val(formatRupiah(getNominal($(this).val()), false));
            });

            // Form ini juga memicu App.submitLoading() global (layouts/app), loading ditutup di success/error.
            $('#formPengaturan').on('submit', function(e) {
                e.preventDefault();
                const data = $(this).serializeArray();
                data.push({ name: '_method', value: 'PUT' });

                ajaxRequest({
                    url: @json(route('pengaturan_payroll.update')),
                    type: 'POST',
                    data: $.param(data),
                    success(res) {
                        App.loading.hide();
                        $('#formPengaturan [data-error]').addClass('hidden').text('');
                        showToast(res.message);
                    },
                    error(xhr) {
                        App.loading.hide();
                        if (xhr.status !== 422 || !xhr.responseJSON?.errors) {
                            return Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.' });
                        }
                        $('#formPengaturan [data-error]').addClass('hidden').text('');
                        $.each(xhr.responseJSON.errors, (field, msg) => {
                            $(`#formPengaturan [data-error="${field}"]`).removeClass('hidden').text(msg[0]);
                        });
                    },
                });
            });
        });
    </script>
@endpush
