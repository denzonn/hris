<?php

use App\Models\Lembur;
use App\Models\User;

beforeEach(function () {
    $this->hr = User::factory()->otoritas(2)->create();
    $this->karyawan = User::factory()->otoritas(3)->create();
});

function lemburPayload(array $override = []): array
{
    return array_merge([
        'tanggal' => '2026-11-20',
        'jam_mulai' => '17:00',
        'jam_selesai' => '20:00',
        'keterangan' => 'Pemasangan audio',
    ], $override);
}

function buatLembur(User $user, string $status = 'pending'): Lembur
{
    return Lembur::create(lemburPayload(['user_id' => $user->id, 'status' => $status]));
}

test('semua otoritas bisa membuka halaman lembur', function (int $otoritas) {
    $user = User::factory()->otoritas($otoritas)->create();

    $this->actingAs($user)->get('/lembur')->assertOk();
})->with([1, 2, 3]);

test('perintah lembur dari hr langsung disetujui', function () {
    $this->actingAs($this->hr)->postJson('/lembur', lemburPayload(['user_id' => $this->karyawan->id]))
        ->assertOk()->assertJson(['message' => 'Perintah lembur berhasil ditambahkan.']);

    $this->assertDatabaseHas('lembur', [
        'user_id' => $this->karyawan->id, 'status' => 'disetujui', 'disetujui_oleh' => $this->hr->id,
    ]);
});

test('pengajuan karyawan selalu pending untuk dirinya sendiri', function () {
    $lain = User::factory()->create();

    $this->actingAs($this->karyawan)->postJson('/lembur', lemburPayload(['user_id' => $lain->id, 'status' => 'disetujui']))
        ->assertOk()->assertJson(['message' => 'Pengajuan lembur berhasil dikirim.']);

    $this->assertDatabaseHas('lembur', ['user_id' => $this->karyawan->id, 'status' => 'pending', 'disetujui_oleh' => null]);
    $this->assertDatabaseMissing('lembur', ['user_id' => $lain->id]);
});

test('validasi lembur', function () {
    $this->actingAs($this->hr)->postJson('/lembur', [])
        ->assertUnprocessable()->assertJsonValidationErrors(['user_id', 'tanggal', 'jam_mulai', 'jam_selesai']);

    $this->actingAs($this->hr)->postJson('/lembur', lemburPayload([
        'user_id' => User::factory()->nonaktif()->create()->id, 'jam_selesai' => '16:00',
    ]))->assertUnprocessable()->assertJsonValidationErrors(['user_id', 'jam_selesai']);
});

test('karyawan hanya melihat lembur miliknya', function () {
    buatLembur($this->karyawan);
    buatLembur(User::factory()->create());

    $this->actingAs($this->karyawan)->getJson('/lembur/data')
        ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.user_id', $this->karyawan->id);
    $this->actingAs($this->hr)->getJson('/lembur/data')->assertOk()->assertJsonPath('total', 2);
});

test('data lembur bisa difilter bulan dan status', function () {
    buatLembur($this->karyawan, 'disetujui');
    Lembur::create(lemburPayload(['user_id' => $this->karyawan->id, 'tanggal' => '2026-10-05']));

    $this->actingAs($this->hr)->getJson('/lembur/data?bulan=2026-11')->assertJsonPath('total', 1);
    $this->actingAs($this->hr)->getJson('/lembur/data?status=pending')
        ->assertJsonPath('total', 1)->assertJsonPath('data.0.tanggal', '2026-10-05');
});

test('karyawan bisa ubah dan hapus lembur sendiri selama pending', function () {
    $lembur = buatLembur($this->karyawan);

    $this->actingAs($this->karyawan)->putJson("/lembur/{$lembur->id}", lemburPayload(['jam_selesai' => '21:00']))->assertOk();
    expect($lembur->fresh()->jam_selesai)->toStartWith('21:00');

    $this->actingAs($this->karyawan)->deleteJson("/lembur/{$lembur->id}")->assertOk();
    $this->assertDatabaseMissing('lembur', ['id' => $lembur->id]);
});

test('karyawan tidak bisa ubah lembur yang sudah diproses atau milik orang lain', function () {
    $disetujui = buatLembur($this->karyawan, 'disetujui');
    $milikLain = buatLembur(User::factory()->create());

    $this->actingAs($this->karyawan)->putJson("/lembur/{$disetujui->id}", lemburPayload())->assertUnprocessable();
    $this->actingAs($this->karyawan)->deleteJson("/lembur/{$disetujui->id}")->assertUnprocessable();
    $this->actingAs($this->karyawan)->putJson("/lembur/{$milikLain->id}", lemburPayload())->assertForbidden();
    $this->actingAs($this->karyawan)->deleteJson("/lembur/{$milikLain->id}")->assertForbidden();
});

test('hr bisa menyetujui dan menolak pengajuan lembur', function () {
    $lembur = buatLembur($this->karyawan);

    $this->actingAs($this->hr)->patchJson("/lembur/{$lembur->id}/status", ['status' => 'disetujui'])
        ->assertOk()->assertJson(['message' => 'Lembur berhasil disetujui.']);
    expect($lembur->fresh())->status->toBe('disetujui')->disetujui_oleh->toBe($this->hr->id);

    $this->actingAs($this->hr)->patchJson("/lembur/{$lembur->id}/status", ['status' => 'ditolak'])->assertOk();
    expect($lembur->fresh()->status)->toBe('ditolak');

    $this->actingAs($this->hr)->patchJson("/lembur/{$lembur->id}/status", ['status' => 'pending'])
        ->assertUnprocessable()->assertJsonValidationErrors(['status']);
});

test('karyawan tidak bisa menyetujui lembur', function () {
    $lembur = buatLembur($this->karyawan);

    $this->actingAs($this->karyawan)->patchJson("/lembur/{$lembur->id}/status", ['status' => 'disetujui'])->assertForbidden();
    expect($lembur->fresh()->status)->toBe('pending');
});
