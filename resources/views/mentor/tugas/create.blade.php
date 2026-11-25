@extends('layouts.app')
@section('judul', 'Buat Tugas')
@section('konten')
<div class="kepala-halaman">
    <div>
        <a class="tautan-kembali" href="{{ route('mentor.tugas.index') }}"><x-ikon nama="kembali" /> Kembali ke daftar tugas</a>
        <h1>Buat Tugas Baru</h1>
        <p class="sub-judul">Berikan penugasan atau proyek kerja kepada satu atau beberapa peserta bimbingan sekaligus.</p>
    </div>
</div>

<form class="kartu form form-kartu" method="POST" action="{{ route('mentor.tugas.store') }}">
    @csrf

    {{-- 01. Peserta Bimbingan (Bisa Pilih Banyak / Multi-Select) --}}
    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat"><x-ikon nama="pengguna" /></span> Peserta Bimbingan</h2>
            <p>Pilih satu atau beberapa peserta magang aktif yang akan menerima tugas ini.</p>
        </div>
        <div class="form-bagian-isi">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <label style="margin-bottom: 0;"><b>Pilih Peserta Penerima Tugas:</b> <span class="teks-wajib" style="color:var(--gagal)">*</span></label>
                <button type="button" class="btn btn-halus btn-kecil" id="btn-toggle-semua" onclick="togglePilihSemua()">
                    <x-ikon nama="centang" /> Pilih Semua
                </button>
            </div>

            @if ($pesertaBimbingan->isEmpty())
                <div class="info-kotak" style="background: var(--permukaan-2); padding: 14px; border-radius: 8px;">
                    <x-ikon nama="info" />
                    <p style="margin: 0;">Saat ini tidak ada peserta bimbingan berstatus <b>Aktif</b>.</p>
                </div>
            @else
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 10px; max-height: 240px; overflow-y: auto; padding: 12px; background: var(--permukaan-2); border-radius: var(--radius-kecil); border: 1px solid var(--garis);">
                    @foreach ($pesertaBimbingan as $p)
                        @php
                            $nama = $p->calonPeserta()?->nama_lengkap ?? $p->pengguna?->nama_lengkap ?? 'Peserta #'.$p->id_peserta_magang;
                            $institusi = $p->calonPeserta()?->institusi_asal ?? 'Institusi';
                            $prodi = $p->calonPeserta()?->jurusan_prodi ?? '';
                            $isChecked = is_array(old('id_peserta_magang'))
                                ? in_array($p->id_peserta_magang, old('id_peserta_magang'))
                                : old('id_peserta_magang') == $p->id_peserta_magang;
                        @endphp
                        <label style="display: flex; align-items: flex-start; gap: 10px; margin: 0; padding: 10px 12px; background: #fff; border: 1px solid var(--garis); border-radius: 8px; cursor: pointer;">
                            <input type="checkbox" name="id_peserta_magang[]" value="{{ $p->id_peserta_magang }}" class="cb-peserta" @checked($isChecked) style="margin-top: 3px;">
                            <div style="flex: 1; line-height: 1.35;">
                                <b style="font-size: 13.5px; display: block;">{{ $nama }}</b>
                                <small class="redup" style="font-size: 12px;">{{ $institusi }}{{ $prodi ? ' · '.$prodi : '' }}</small>
                            </div>
                        </label>
                    @endforeach
                </div>
            @endif
            @error('id_peserta_magang')<span class="pesan-salah">{{ $message }}</span>@enderror
        </div>
    </div>

    {{-- 02. Rincian Penugasan --}}
    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat kuning"><x-ikon nama="tugas" /></span> Rincian Penugasan</h2>
            <p>Judul proyek, batas waktu penyelesaian, dan target jumlah tahapan progres.</p>
        </div>
        <div class="form-bagian-isi">
            <label>Judul Tugas / Proyek <span class="teks-wajib" style="color:var(--gagal)">*</span>
                <input type="text" name="judul" value="{{ old('judul') }}" maxlength="180" placeholder="mis. Pengembangan Fitur Modul C" required class="@error('judul') salah @enderror">
                @error('judul')<span class="pesan-salah">{{ $message }}</span>@enderror
            </label>

            <div class="dua-kolom">
                <label>Tenggat Waktu <span class="teks-wajib" style="color:var(--gagal)">*</span>
                    <input type="date" name="tenggat" value="{{ old('tenggat', now()->addDays(7)->format('Y-m-d')) }}" min="{{ date('Y-m-d') }}" required class="@error('tenggat') salah @enderror">
                    @error('tenggat')<span class="pesan-salah">{{ $message }}</span>@enderror
                </label>
            </div>

            <label>Deskripsi Tugas <span class="teks-wajib" style="color:var(--gagal)">*</span>
                <textarea name="deskripsi" rows="4" placeholder="Jelaskan instruksi, tujuan, dan kriteria tugas ini secara rinci..." required class="@error('deskripsi') salah @enderror">{{ old('deskripsi') }}</textarea>
                @error('deskripsi')<span class="pesan-salah">{{ $message }}</span>@enderror
            </label>

            {{-- Tahapan Progres --}}
            <div style="margin-top: 12px; padding-top: 14px; border-top: 1px solid var(--garis);">
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
        <button class="btn btn-kuning" type="submit"><x-ikon nama="centang-saja" /> Simpan &amp; Berikan Tugas</button>
    </div>
</form>

<script>
function togglePilihSemua() {
    const cbs = document.querySelectorAll('.cb-peserta');
    const allChecked = Array.from(cbs).every(cb => cb.checked);
    cbs.forEach(cb => cb.checked = !allChecked);
    document.getElementById('btn-toggle-semua').innerText = allChecked ? 'Pilih Semua' : 'Batal Pilih Semua';
}

function tambahProgres() {
    const ol = document.getElementById('daftar-progres');
    const idx = ol.children.length + 1;
    const li = document.createElement('li');
    li.className = 'item-progres';
    li.style.cssText = 'display:flex;gap:10px;align-items:center;background:var(--permukaan-2);border:1px solid var(--garis);border-radius:var(--radius-kecil);padding:10px 14px;';
    li.innerHTML = `
        <span style="font-weight:700;color:var(--aksen);min-width:28px;text-align:center;font-size:15px;">${idx}</span>
        <input type="text" name="judul_progres[]" placeholder="mis. Implementasi Tahapan Lanjutan" maxlength="150" required style="flex:1;margin:0;">
        <button type="button" onclick="hapusProgres(this)" title="Hapus tahapan ini" style="background:none;border:none;cursor:pointer;color:var(--gagal);padding:4px 6px;border-radius:4px;display:flex;align-items:center;">
            <svg class="ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM9 9l6 6M15 9l-6 6"/></svg>
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
