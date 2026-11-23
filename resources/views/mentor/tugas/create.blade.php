@extends('layouts.app')
@section('judul', 'Buat Tugas')
@section('konten')
<div class="kepala-halaman">
    <div>
        <a class="tautan-kembali" href="{{ route('mentor.tugas.index') }}"><x-ikon nama="kembali" /> Kembali ke daftar tugas</a>
        <h1>Buat Tugas Baru</h1>
        <p class="sub-judul">Berikan penugasan atau proyek kerja kepada peserta bimbingan dan tentukan target tahapan progres.</p>
    </div>
</div>

<form class="kartu form form-kartu" method="POST" action="{{ route('mentor.tugas.store') }}">
    @csrf

    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat"><x-ikon nama="pengguna" /></span> Peserta Bimbingan</h2>
            <p>Pilih peserta magang aktif yang akan menerima tugas ini.</p>
        </div>
        <div class="form-bagian-isi">
            <label>Peserta Magang
                <select name="id_peserta_magang" required>
                    <option value="">-- Pilih Peserta Bimbingan --</option>
                    @foreach ($pesertaBimbingan as $p)
                        @php
                            $nama = $p->calonPeserta()?->nama_lengkap ?? $p->pengguna?->nama_lengkap ?? 'Peserta #'.$p->id_peserta_magang;
                            $institusi = $p->calonPeserta()?->institusi_asal ?? 'Institusi';
                        @endphp
                        <option value="{{ $p->id_peserta_magang }}" @selected(old('id_peserta_magang') == $p->id_peserta_magang)>
                            {{ $nama }} ({{ $institusi }})
                        </option>
                    @endforeach
                </select>
                @error('id_peserta_magang')<span class="pesan-salah">{{ $message }}</span>@enderror
            </label>
        </div>
    </div>

    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat kuning"><x-ikon nama="tugas" /></span> Rincian Penugasan</h2>
            <p>Judul proyek, batas waktu penyelesaian, dan target jumlah progres kerja.</p>
        </div>
        <div class="form-bagian-isi">
            <label>Judul Tugas / Proyek
                <input type="text" name="judul" value="{{ old('judul') }}" maxlength="180" placeholder="mis. Pengembangan Fitur Modul C" required>
                @error('judul')<span class="pesan-salah">{{ $message }}</span>@enderror
            </label>

            <div class="dua-kolom">
                <label>Tenggat Waktu
                    <input type="date" name="tenggat" value="{{ old('tenggat', now()->addDays(7)->format('Y-m-d')) }}" min="{{ date('Y-m-d') }}" required>
                    @error('tenggat')<span class="pesan-salah">{{ $message }}</span>@enderror
                </label>
            </div>

            <label>Deskripsi Tugas
                <textarea name="deskripsi" rows="4" placeholder="Jelaskan instruksi, tujuan, dan kriteria tugas ini secara rinci..." required>{{ old('deskripsi') }}</textarea>
                @error('deskripsi')<span class="pesan-salah">{{ $message }}</span>@enderror
            </label>

            {{-- Tahapan Progres --}}
            <div style="margin-top: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <div>
                        <b>Tahapan Progres</b>
                        <small class="redup" style="display: block; margin-top: 2px;">Tentukan judul target untuk setiap tahapan progres yang harus diselesaikan peserta.</small>
                    </div>
                    <button type="button" class="btn btn-halus btn-kecil" id="btn-tambah-progres" onclick="tambahProgres()">
                        <x-ikon nama="plus" /> Tambah Tahapan
                    </button>
                </div>

                @error('judul_progres')<span class="pesan-salah" style="margin-bottom:8px; display:block;">{{ $message }}</span>@enderror
                @error('judul_progres.*')<span class="pesan-salah" style="margin-bottom:8px; display:block;">{{ $message }}</span>@enderror

                <ol id="daftar-progres" style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px;">
                    @php $progresLama = old('judul_progres', ['']); @endphp
                    @foreach($progresLama as $i => $judul)
                    <li class="item-progres" style="display: flex; gap: 10px; align-items: center; background: var(--permukaan-2); border: 1px solid var(--garis); border-radius: var(--radius-kecil); padding: 10px 14px;">
                        <span style="font-weight: 700; color: var(--aksen); min-width: 28px; text-align: center; font-size: 15px;">{{ $i + 1 }}</span>
                        <input type="text" name="judul_progres[]" value="{{ $judul }}" placeholder="mis. Perancangan & Desain UI" maxlength="150" required style="flex: 1; margin: 0;">
                        <button type="button" onclick="hapusProgres(this)" title="Hapus tahapan ini" style="background: none; border: none; cursor: pointer; color: var(--gagal); padding: 4px 6px; border-radius: 4px; display: flex; align-items: center;">
                            <x-ikon nama="silang" />
                        </button>
                    </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>

    <div class="form-kaki">
        <a class="btn btn-halus" href="{{ route('mentor.tugas.index') }}">Batal</a>
        <button class="btn" type="submit"><x-ikon nama="centang-saja" /> Simpan Tugas</button>
    </div>
</form>

<script>
function tambahProgres() {
    const ol = document.getElementById('daftar-progres');
    const idx = ol.children.length + 1;
    const li = document.createElement('li');
    li.className = 'item-progres';
    li.style.cssText = 'display:flex;gap:10px;align-items:center;background:var(--permukaan-2);border:1px solid var(--garis);border-radius:var(--radius-kecil);padding:10px 14px;';
    li.innerHTML = `
        <span style="font-weight:700;color:var(--aksen);min-width:28px;text-align:center;font-size:15px;">${idx}</span>
        <input type="text" name="judul_progres[]" placeholder="mis. Implementasi & Pengujian" maxlength="150" required style="flex:1;margin:0;">
        <button type="button" onclick="hapusProgres(this)" title="Hapus tahapan ini" style="background:none;border:none;cursor:pointer;color:var(--gagal);padding:4px 6px;border-radius:4px;display:flex;align-items:center;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>`;
    ol.appendChild(li);
    nomorUlang();
    li.querySelector('input').focus();
}

function hapusProgres(btn) {
    const ol = document.getElementById('daftar-progres');
    if (ol.children.length <= 1) {
        alert('Minimal harus ada 1 tahapan progres.');
        return;
    }
    btn.closest('li').remove();
    nomorUlang();
}

function nomorUlang() {
    document.querySelectorAll('#daftar-progres .item-progres span').forEach((span, i) => {
        span.textContent = i + 1;
    });
}
</script>
@endsection
