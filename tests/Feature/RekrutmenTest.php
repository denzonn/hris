<?php

use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\Lowongan;
use App\Models\Pelamar;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-19 10:00'));
    Storage::fake('local');
    $this->hr = User::factory()->otoritas(2)->create();
    $this->divisi = Divisi::create(['nama' => 'Operasional']);
    $this->jabatan = Jabatan::create(['nama' => 'Teknisi', 'divisi_id' => $this->divisi->id]);
    $this->lowongan = Lowongan::create([
        'judul' => 'Teknisi Audio', 'divisi_id' => $this->divisi->id, 'jabatan_id' => $this->jabatan->id,
        'deskripsi' => 'Memasang audio mobil', 'kualifikasi' => 'Min. SMK', 'tanggal_tutup' => '2026-11-30',
    ]);
});

function lamaran(array $override = []): array
{
    return array_merge([
        'nama' => 'Andi', 'email' => 'andi@mail.test', 'no_hp' => '0812 3456 7890', 'pendidikan_terakhir' => 'SMA/SMK',
        'cv' => UploadedFile::fake()->create('cv.pdf', 300, 'application/pdf'),
    ], $override);
}

function buatPelamar(Lowongan $l, array $attr = []): Pelamar
{
    return $l->pelamar()->create($attr + [
        'nama' => 'Andi', 'email' => fake()->unique()->safeEmail(), 'no_hp' => '0812', 'pendidikan_terakhir' => 'S1', 'cv' => 'cv/x.pdf',
    ]);
}

// ---------- Lowongan (HR) ----------

test('hr bisa crud lowongan dengan validasi jabatan sesuai divisi', function () {
    $this->actingAs($this->hr)->get('/lowongan')->assertOk()->assertSee('Tambah Lowongan');

    $this->actingAs($this->hr)->postJson('/lowongan', [])
        ->assertUnprocessable()->assertJsonValidationErrors(['judul', 'deskripsi', 'kualifikasi']);

    $lain = Divisi::create(['nama' => 'Keuangan']);
    $this->actingAs($this->hr)->postJson('/lowongan', [
        'judul' => 'Kasir', 'divisi_id' => $lain->id, 'jabatan_id' => $this->jabatan->id, 'deskripsi' => 'x', 'kualifikasi' => 'y',
    ])->assertUnprocessable()->assertJsonValidationErrors(['jabatan_id']);

    $this->actingAs($this->hr)->postJson('/lowongan', ['judul' => 'Kasir', 'deskripsi' => 'x', 'kualifikasi' => 'y'])
        ->assertOk()->assertJson(['message' => 'Lowongan berhasil ditambahkan.']);
    $kasir = Lowongan::firstWhere('judul', 'Kasir');
    expect($kasir->aktif)->toBeFalse(); // checkbox tidak dikirim

    $this->actingAs($this->hr)->putJson("/lowongan/{$kasir->id}", ['judul' => 'Kasir', 'deskripsi' => 'x', 'kualifikasi' => 'y', 'aktif' => '1'])->assertOk();
    expect($kasir->fresh()->aktif)->toBeTrue();

    $this->actingAs($this->hr)->getJson('/lowongan/data?search=kasir')->assertJsonPath('total', 1)->assertJsonPath('data.0.pelamar_count', 0);
    $this->actingAs($this->hr)->deleteJson("/lowongan/{$kasir->id}")->assertOk();
});

test('lowongan yang sudah punya pelamar tidak bisa dihapus', function () {
    buatPelamar($this->lowongan);

    $this->actingAs($this->hr)->deleteJson("/lowongan/{$this->lowongan->id}")->assertUnprocessable();
});

// ---------- Karir publik ----------

test('halaman karir publik hanya menampilkan lowongan aktif dan belum tutup', function () {
    Lowongan::create(['judul' => 'Lowongan Nonaktif', 'deskripsi' => 'x', 'kualifikasi' => 'y', 'aktif' => false]);
    $tutup = Lowongan::create(['judul' => 'Lowongan Lewat', 'deskripsi' => 'x', 'kualifikasi' => 'y', 'tanggal_tutup' => '2026-11-18']);

    $this->get('/karir')->assertOk()->assertSee('Teknisi Audio')->assertDontSee('Lowongan Nonaktif')->assertDontSee('Lowongan Lewat');
    $this->get("/karir/{$this->lowongan->id}")->assertOk()->assertSee('Memasang audio mobil')->assertSee('Kirim Lamaran');
    $this->get("/karir/{$tutup->id}")->assertNotFound();
});

test('tamu bisa melamar dengan CV pdf yang disimpan private', function () {
    $this->post("/karir/{$this->lowongan->id}", lamaran(), ['Accept' => 'application/json'])
        ->assertOk()->assertJsonFragment(['message' => 'Lamaran Anda berhasil dikirim. Kami akan menghubungi Anda bila lolos seleksi.']);

    $p = Pelamar::first();
    expect($p)->nama->toBe('Andi')->status->toBe('baru')->lowongan_id->toBe($this->lowongan->id);
    Storage::disk('local')->assertExists($p->cv);
    expect($p->toArray())->not->toHaveKey('cv');
});

test('validasi lamaran: cv wajib pdf maks 2MB dan satu email sekali per lowongan', function () {
    $this->post("/karir/{$this->lowongan->id}", lamaran(['cv' => UploadedFile::fake()->create('cv.docx', 100)]), ['Accept' => 'application/json'])
        ->assertUnprocessable()->assertJsonValidationErrors(['cv']);
    $this->post("/karir/{$this->lowongan->id}", lamaran(['cv' => UploadedFile::fake()->create('cv.pdf', 3000, 'application/pdf')]), ['Accept' => 'application/json'])
        ->assertUnprocessable()->assertJsonValidationErrors(['cv']);
    $this->post("/karir/{$this->lowongan->id}", lamaran(['no_hp' => 'abc']), ['Accept' => 'application/json'])
        ->assertUnprocessable()->assertJsonValidationErrors(['no_hp']);

    $this->post("/karir/{$this->lowongan->id}", lamaran(), ['Accept' => 'application/json'])->assertOk();
    $this->post("/karir/{$this->lowongan->id}", lamaran(), ['Accept' => 'application/json'])
        ->assertUnprocessable()->assertJsonValidationErrors(['email' => 'Email ini sudah melamar di lowongan ini.']);
});

test('lamaran ke lowongan tutup ditolak dan dibatasi rate limit', function () {
    $this->lowongan->update(['aktif' => false]);
    $this->post("/karir/{$this->lowongan->id}", lamaran(), ['Accept' => 'application/json'])->assertNotFound();

    $this->lowongan->update(['aktif' => true]);
    foreach (range(1, 5) as $i) {
        $this->post("/karir/{$this->lowongan->id}", lamaran(['email' => "a{$i}@mail.test"]), ['Accept' => 'application/json']);
    }
    $this->post("/karir/{$this->lowongan->id}", lamaran(['email' => 'a6@mail.test']), ['Accept' => 'application/json'])->assertStatus(429);
});

// ---------- Pelamar (HR) ----------

test('hr melihat, memfilter, memproses pelamar dan membuka CV', function () {
    $p = buatPelamar($this->lowongan, ['cv' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf')->store('cv', 'local')]);
    buatPelamar(Lowongan::create(['judul' => 'Kasir', 'deskripsi' => 'x', 'kualifikasi' => 'y']));

    $this->actingAs($this->hr)->get('/pelamar')->assertOk();
    $this->actingAs($this->hr)->getJson("/pelamar/data?lowongan_id={$this->lowongan->id}")->assertJsonPath('total', 1);

    $this->actingAs($this->hr)->patchJson("/pelamar/{$p->id}/status", ['status' => 'interview', 'catatan' => 'Senin 10:00'])
        ->assertOk()->assertJson(['message' => 'Status Andi diubah menjadi interview.']);
    expect($p->fresh())->status->toBe('interview')->catatan->toBe('Senin 10:00');

    $this->actingAs($this->hr)->patchJson("/pelamar/{$p->id}/status", ['status' => 'lolos'])->assertUnprocessable();
    $this->actingAs($this->hr)->get("/pelamar/{$p->id}/cv")->assertOk();
});

test('jadikan pegawai: form pegawai terisi dari pelamar dan pelamar tertaut ke akun baru', function () {
    $p = buatPelamar($this->lowongan, ['status' => 'diterima', 'email' => 'andi@mail.test']);

    $this->actingAs($this->hr)->get("/user?pelamar={$p->id}")
        ->assertOk()->assertSee('Tambah Pegawai dari Pelamar')->assertSee('andi@mail.test', false);

    $this->actingAs($this->hr)->postJson('/user', [
        'name' => 'Andi', 'email' => 'andi@mail.test', 'password' => 'rahasia123', 'otoritas_id' => 3,
        'divisi_id' => $this->divisi->id, 'jabatan_id' => $this->jabatan->id, 'status_kerja' => 'kontrak',
        'gaji_pokok' => 3000000, 'jatah_cuti' => 12, 'pelamar_id' => $p->id,
    ])->assertOk();

    $user = User::firstWhere('email', 'andi@mail.test');
    expect($p->fresh()->user_id)->toBe($user->id);

    // pelamar yang sudah jadi pegawai: tidak bisa dipakai lagi, dihapus, atau diturunkan statusnya
    $this->actingAs($this->hr)->postJson('/user', ['pelamar_id' => $p->id])->assertJsonValidationErrors(['pelamar_id']);
    $this->actingAs($this->hr)->deleteJson("/pelamar/{$p->id}")->assertUnprocessable();
    $this->actingAs($this->hr)->patchJson("/pelamar/{$p->id}/status", ['status' => 'ditolak'])->assertUnprocessable();
});

test('pelamar yang belum diterima tidak bisa dijadikan pegawai', function () {
    $p = buatPelamar($this->lowongan, ['status' => 'interview']);

    $this->actingAs($this->hr)->get("/user?pelamar={$p->id}")->assertOk()->assertDontSee('Tambah Pegawai dari Pelamar');
    $this->actingAs($this->hr)->postJson('/user', ['pelamar_id' => $p->id])->assertJsonValidationErrors(['pelamar_id']);
});

test('hapus pelamar ikut menghapus file CV', function () {
    $p = buatPelamar($this->lowongan, ['cv' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf')->store('cv', 'local')]);

    $this->actingAs($this->hr)->deleteJson("/pelamar/{$p->id}")->assertOk();
    Storage::disk('local')->assertMissing($p->cv);
});

test('karyawan dan tamu tidak bisa akses data rekrutmen', function () {
    $p = buatPelamar($this->lowongan);
    $karyawan = User::factory()->create();

    foreach (['/lowongan', '/pelamar', "/pelamar/{$p->id}/cv"] as $url) {
        $this->actingAs($karyawan)->get($url)->assertForbidden();
    }
    auth()->logout();
    $this->get('/pelamar')->assertRedirect('/login');
    $this->get("/pelamar/{$p->id}/cv")->assertRedirect('/login');
});
