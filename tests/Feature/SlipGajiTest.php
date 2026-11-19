<?php

use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->karyawan = User::factory()->create();
    $this->final = Payroll::create(['periode' => '2026-10', 'tanggal_mulai' => '2026-09-26', 'tanggal_selesai' => '2026-10-25', 'status' => 'final']);
    $this->draft = Payroll::create(['periode' => '2026-11', 'tanggal_mulai' => '2026-10-26', 'tanggal_selesai' => '2026-11-25']);
    $slip = fn (Payroll $p, User $u) => PayrollDetail::create([
        'payroll_id' => $p->id, 'user_id' => $u->id, 'nama' => $u->name, 'gaji_bersih' => 4000000, 'rincian' => [],
    ]);
    $this->slipFinal = $slip($this->final, $this->karyawan);
    $this->slipDraft = $slip($this->draft, $this->karyawan);
    $this->slipLain = $slip($this->final, User::factory()->create());
});

test('karyawan hanya melihat slip miliknya dari payroll final', function () {
    $this->actingAs($this->karyawan)->get('/slip_gaji')->assertOk();
    $this->actingAs($this->karyawan)->getJson('/slip_gaji/data')
        ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $this->slipFinal->id)
        ->assertJsonPath('data.0.payroll.periode', '2026-10');
});

test('karyawan bisa membuka slip final miliknya', function () {
    $this->actingAs($this->karyawan)->get("/slip_gaji/{$this->slipFinal->id}")
        ->assertOk()->assertSee($this->karyawan->name)->assertSee('Rp. 4.000.000')->assertDontSee('DRAFT');
});

test('slip milik orang lain ditolak dan slip draft tidak ditemukan', function () {
    $this->actingAs($this->karyawan)->get("/slip_gaji/{$this->slipLain->id}")->assertForbidden();
    $this->actingAs($this->karyawan)->get("/slip_gaji/{$this->slipDraft->id}")->assertNotFound();
});

test('profil tampil untuk semua otoritas', function (int $otoritas) {
    $this->actingAs(User::factory()->otoritas($otoritas)->create(['name' => 'Rina']))->get('/profile')->assertOk()->assertSee('Rina');
})->with([1, 2, 3]);

test('ganti password butuh password lama yang benar dan konfirmasi', function () {
    $this->actingAs($this->karyawan)->putJson('/profile/password', [
        'password_lama' => 'salah', 'password' => 'rahasia123', 'password_confirmation' => 'beda',
    ])->assertUnprocessable()->assertJsonValidationErrors(['password_lama', 'password']);

    $this->actingAs($this->karyawan)->putJson('/profile/password', [
        'password_lama' => 'password', 'password' => 'pendek', 'password_confirmation' => 'pendek',
    ])->assertUnprocessable()->assertJsonValidationErrors(['password']);

    $this->actingAs($this->karyawan)->putJson('/profile/password', [
        'password_lama' => 'password', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
    ])->assertOk()->assertJson(['message' => 'Password berhasil diganti.']);

    expect(Hash::check('rahasia123', $this->karyawan->fresh()->password))->toBeTrue();
});
