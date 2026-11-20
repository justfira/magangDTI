@extends('layouts.app')
@section('judul', $pengguna->exists ? 'Ubah Akun' : 'Tambah Akun Pegawai')
@section('konten')
@php
    $kodeTerpilih = old('kode_peran', $pengguna->kodePeran());
    $idSeksiTerpilih = old('id_seksi', $pengguna->pegawai?->id_seksi);
@endphp
<div class="kepala-halaman">
    <div>
        <a class="tautan-kembali" href="{{ route('superadmin.akun.index') }}"><x-ikon nama="kembali" /> Kembali ke daftar akun</a>
        <h1>{{ $pengguna->exists ? 'Ubah Akun' : 'Tambah Akun Pegawai' }}</h1>
        <p class="sub-judul">{{ $pengguna->exists ? 'Perbarui data, kata sandi, atau peran akun ini.' : 'Buat akun untuk pegawai DTI dan tentukan perannya di sistem.' }}</p>
    </div>
</div>
<form class="kartu form form-kartu" method="POST" autocomplete="off"
      action="{{ $pengguna->exists ? route('superadmin.akun.update', $pengguna) : route('superadmin.akun.store') }}">
    @csrf
    @if ($pengguna->exists) @method('PUT') @endif

    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat"><x-ikon nama="pengguna" /></span> Data akun</h2>
            <p>Nama dan email dipakai untuk login serta ditampilkan di seluruh sistem.</p>
        </div>
        <div class="form-bagian-isi">
            <label>Nama lengkap
                <input name="nama_lengkap" value="{{ old('nama_lengkap', $pengguna->nama_lengkap) }}" maxlength="150" placeholder="Nama lengkap pegawai">
            </label>
            <label>Email
                <span class="input-ikon">
                    <x-ikon nama="surel" />
                    <input type="email" name="email" value="{{ old('email', $pengguna->email) }}" maxlength="191" autocomplete="off" placeholder="nama@dti.unand.ac.id">
                </span>
            </label>
            <div class="dua-kolom">
                <label>Kata sandi
                    <span class="input-ikon"><x-ikon nama="kunci" /><input type="password" name="password" autocomplete="new-password"></span>
                    @if ($pengguna->exists)<small class="redup">Kosongkan jika tidak diganti.</small>@endif
                </label>
                <label>Konfirmasi kata sandi
                    <span class="input-ikon"><x-ikon nama="kunci" /><input type="password" name="password_confirmation" autocomplete="new-password"></span>
                </label>
            </div>
        </div>
    </div>

    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat kuning"><x-ikon nama="perisai" /></span> Peran &amp; penempatan</h2>
            <p>Peran menentukan menu dan fitur yang bisa diakses. Seksi wajib untuk Mentor dan Kepala Seksi.</p>
        </div>
        <div class="form-bagian-isi">
            <label>Peran (hak akses)
                @if ($akunSendiri)
                    <input type="hidden" name="kode_peran" value="{{ $pengguna->kodePeran() }}">
                    <select disabled><option>{{ $pengguna->peran->nama_peran }}</option></select>
                    <small class="redup">Peran akun Anda sendiri tidak dapat diubah.</small>
                @else
                    <select name="kode_peran" id="kode_peran">
                        <option value="">— pilih peran —</option>
                        @foreach ($peranPegawai as $p)
                            <option value="{{ $p->kode_peran }}" @selected($kodeTerpilih === $p->kode_peran)>{{ $p->nama_peran }}</option>
                        @endforeach
                    </select>
                    <small class="redup">Lihat <a href="{{ route('superadmin.hak-akses') }}">daftar hak akses</a> untuk rincian tiap peran.</small>
                @endif
            </label>

            <div id="blok-seksi">
                <label>Seksi
                    <select name="id_seksi">
                        <option value="">— pilih seksi —</option>
                        @foreach ($seksi as $s)
                            <option value="{{ $s->id_seksi }}" @selected((string) $idSeksiTerpilih === (string) $s->id_seksi)>{{ $s->nama_seksi }}</option>
                        @endforeach
                    </select>
                    <small class="redup">Wajib untuk Mentor dan Kepala Seksi.</small>
                </label>
            </div>

            <div class="dua-kolom">
                <label>NIP
                    <input name="nip" value="{{ old('nip', $pengguna->pegawai?->nip) }}" maxlength="30" placeholder="Opsional">
                </label>
                <label>Jabatan
                    <input name="jabatan" value="{{ old('jabatan', $pengguna->pegawai?->jabatan) }}" maxlength="60" placeholder="Opsional">
                </label>
            </div>
        </div>
    </div>

    <div class="form-kaki">
        <a class="btn btn-halus" href="{{ route('superadmin.akun.index') }}">Batal</a>
        <button class="btn"><x-ikon nama="centang-saja" /> Simpan</button>
    </div>
</form>

<script>
// Tampilkan pilihan seksi hanya untuk peran yang membutuhkannya.
(function () {
    var perlu = @json($perluSeksi);
    var pilih = document.getElementById('kode_peran');
    var blok = document.getElementById('blok-seksi');
    function atur() {
        var kode = pilih ? pilih.value : @json($pengguna->kodePeran());
        blok.style.display = perlu.indexOf(kode) >= 0 ? '' : 'none';
    }
    if (pilih) { pilih.addEventListener('change', atur); }
    atur();
})();
</script>
@endsection
