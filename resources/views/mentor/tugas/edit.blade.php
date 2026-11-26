@extends('layouts.app')
@section('judul', 'Ubah Tugas & Tahapan Progres')
@section('konten')
<div class="kepala-halaman">
    <div>
        <a class="tautan-kembali" href="{{ route('mentor.tugas.show', $tugas) }}"><x-ikon nama="kembali" /> Kembali ke detail tugas</a>
        <h1>Ubah Tugas &amp; Tahapan Progres</h1>
        <p class="sub-judul">Perbarui rincian tugas, tenggat waktu penyelesaian, dan tambah/hapus tahapan progres kerja.</p>
    </div>
</div>

@php
    $peserta = $tugas->pesertaMagang;
    $namaPeserta = $peserta?->calonPeserta()?->nama_lengkap ?? $peserta?->pengguna?->nama_lengkap ?? 'Peserta';
    $institusi = $peserta?->calonPeserta()?->institusi_asal ?? 'DTI Unand';
    $progresTerkirim = $tugas->progresTugas()->count();
    $daftarProgres = old('judul_progres', $tugas->judul_progres ?? ['']);
    if (empty($daftarProgres)) {
        $daftarProgres = [''];
    }
@endphp

@if (session('gagal'))
    <div class="alert alert-gagal" style="margin-bottom: 20px;">
        <x-ikon nama="peringatan" />
        <div>{{ session('gagal') }}</div>
    </div>
@endif

<form class="kartu form form-kartu" method="POST" action="{{ route('mentor.tugas.update', $tugas) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    {{-- 01. Peserta Bimbingan (Read-only) --}}
    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat"><x-ikon nama="pengguna" /></span> Peserta Bimbingan</h2>
            <p>Penerima tugas ini tidak dapat dipindahkan ke peserta lain.</p>
        </div>
        <div class="form-bagian-isi">
            <label>Peserta Magang
                <input type="text" value="{{ $namaPeserta }} ({{ $institusi }})" disabled>
            </label>
        </div>
    </div>

    {{-- 02. Rincian Penugasan --}}
    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat kuning"><x-ikon nama="tugas" /></span> Rincian Penugasan</h2>
            <p>Perbarui judul proyek, batas waktu (tenggat), atau instruksi kerja.</p>
        </div>
        <div class="form-bagian-isi">
            <label>Judul Tugas / Proyek <span class="teks-wajib" style="color:var(--gagal)">*</span>
                <input type="text" name="judul" value="{{ old('judul', $tugas->judul) }}" maxlength="180" placeholder="mis. Pengembangan Fitur Modul C" required class="@error('judul') salah @enderror">
                @error('judul')<span class="pesan-salah">{{ $message }}</span>@enderror
            </label>

            <div class="dua-kolom">
                <label>Tenggat Waktu &amp; Jam <span class="teks-wajib" style="color:var(--gagal)">*</span>
                    <input type="datetime-local" name="tenggat" value="{{ old('tenggat', $tugas->tenggat?->format('Y-m-d\TH:i')) }}" min="{{ now()->format('Y-m-d\TH:i') }}" required class="@error('tenggat') salah @enderror">
                    @error('tenggat')<span class="pesan-salah">{{ $message }}</span>@enderror
                    <small class="redup">Ubah batas tanggal dan jam pengumpulan tugas bila diperlukan perpanjangan waktu.</small>
                </label>
                <label>Status Tugas Saat Ini
                    <input type="text" value="{{ $tugas->status }}" disabled>
                </label>
            </div>

            <label>Deskripsi Tugas <span class="teks-wajib" style="color:var(--gagal)">*</span>
                <textarea name="deskripsi" rows="4" placeholder="Jelaskan instruksi, tujuan, dan kriteria tugas ini secara rinci..." required class="@error('deskripsi') salah @enderror">{{ old('deskripsi', $tugas->deskripsi) }}</textarea>
                @error('deskripsi')<span class="pesan-salah">{{ $message }}</span>@enderror
            </label>

            <label>Dokumen / Lampiran Instruksi Tugas (Opsional)
                @if ($tugas->path_dokumen)
                    <div id="wadah-dokumen-saat-ini" style="margin-bottom: 10px; padding: 12px 16px; background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: var(--radius-kecil); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="ikon-bulat" style="background: #2563eb; color: #fff; width: 32px; height: 32px;"><x-ikon nama="berkas" /></span>
                            <div>
                                <b style="font-size: 13.5px; display: block;" id="label-nama-berkas">{{ basename($tugas->path_dokumen) }}</b>
                                <small class="redup" id="sub-nama-berkas">Berkas instruksi tugas saat ini</small>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <a href="{{ route('tugas.dokumen', $tugas) }}" target="_blank" class="btn btn-garis btn-kecil" title="Buka dan lihat berkas di tab baru">
                                <x-ikon nama="mata" /> Lihat
                            </a>
                            <a href="{{ route('tugas.dokumen', ['tugas' => $tugas, 'unduh' => 1]) }}" class="btn btn-garis btn-kecil" title="Unduh berkas ke perangkat">
                                <x-ikon nama="berkas" /> Unduh
                            </a>
                            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: var(--gagal); cursor: pointer; margin: 0; padding: 5px 10px; border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 6px; background: #fff;">
                                <input type="checkbox" name="hapus_dokumen" value="1" id="cb-hapus-dokumen" onchange="toggleHapusDokumen(this)" style="margin: 0;">
                                <x-ikon nama="silang" style="width: 13px; height: 13px;" /> Hapus Berkas
                            </label>
                        </div>
                    </div>
                @endif
                <div class="dua-kolom" style="margin-top: 8px;">
                    <div>
                        <input type="file" name="dokumen" accept=".pdf,.doc,.docx,.zip,.rar,.png,.jpg,.jpeg">
                        <small class="redup">{{ $tugas->path_dokumen ? 'Pilih berkas baru jika ingin mengganti dokumen yang lama (Maks. 10 MB).' : 'Unggah modul panduan atau instruksi jika ada (Maks. 10 MB).' }}</small>
                        @error('dokumen')<span class="pesan-salah">{{ $message }}</span>@enderror
                    </div>
                    <div>
                        <input type="text" name="nama_kustom_dokumen" value="{{ old('nama_kustom_dokumen') }}" maxlength="100" placeholder="Nama file kustom (opsional)">
                        <small class="redup">Ubah nama file sebelum disimpan jika diinginkan.</small>
                        @error('nama_kustom_dokumen')<span class="pesan-salah">{{ $message }}</span>@enderror
                    </div>
                </div>
            </label>

            {{-- 03. Tahapan Progres Dinamis --}}
            <div style="margin-top: 14px; padding-top: 16px; border-top: 1px solid var(--garis);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <b style="font-size: 14.5px;">Kelola Tahapan Progres</b>
                        <small class="redup" style="display: block; margin-top: 2px;">
                            Tambah tahapan baru atau hapus tahapan yang belum diserahkan. (Saat ini sudah ada <b>{{ $progresTerkirim }}</b> progres diserahkan peserta).
                        </small>
                    </div>
                    <button type="button" class="btn btn-halus btn-kecil" id="btn-tambah-progres" onclick="tambahProgres()">
                        <x-ikon nama="plus" /> Tambah Tahapan
                    </button>
                </div>

                @error('judul_progres')<span class="pesan-salah" style="margin-bottom:8px; display:block;">{{ $message }}</span>@enderror
                @error('judul_progres.*')<span class="pesan-salah" style="margin-bottom:8px; display:block;">{{ $message }}</span>@enderror

                <ol id="daftar-progres" style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px;">
                    @foreach($daftarProgres as $i => $judul)
                        @php
                            $nomorTahapan = $i + 1;
                            $sudahAdaLaporan = $nomorTahapan <= $progresTerkirim;
                        @endphp
                        <li class="item-progres" data-terkirim="{{ $sudahAdaLaporan ? '1' : '0' }}" style="display: flex; gap: 10px; align-items: center; background: var(--permukaan-2); border: 1px solid var(--garis); border-radius: var(--radius-kecil); padding: 10px 14px;">
                            <span style="font-weight: 700; color: var(--aksen); min-width: 28px; text-align: center; font-size: 15px;">{{ $nomorTahapan }}</span>
                            <input type="text" name="judul_progres[]" value="{{ $judul }}" placeholder="mis. Perancangan & Desain UI" maxlength="150" required style="flex: 1; margin: 0;" class="@error("judul_progres.{$i}") salah @enderror">
                            @if ($sudahAdaLaporan)
                                <span class="chip" title="Progres ini sudah diserahkan peserta" style="font-size: 11px;">Sudah Diserahkan</span>
                            @else
                                <button type="button" onclick="hapusProgres(this)" title="Hapus tahapan ini" style="background: none; border: none; cursor: pointer; color: var(--gagal); padding: 4px 6px; border-radius: 4px; display: flex; align-items: center;">
                                    <x-ikon nama="silang" />
                                </button>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>

    {{-- Kaki Formulir --}}
    <div class="form-kaki">
        <a class="btn btn-halus" href="{{ route('mentor.tugas.show', $tugas) }}">Batal</a>
        <button class="btn btn-kuning" type="submit">
            <x-ikon nama="centang-saja" /> Simpan Perubahan Tugas
        </button>
    </div>
</form>

<script>
const progresTerkirimMinimal = {{ $progresTerkirim }};

function tambahProgres() {
    const ol = document.getElementById('daftar-progres');
    const idx = ol.children.length + 1;
    const li = document.createElement('li');
    li.className = 'item-progres';
    li.dataset.terkirim = '0';
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
    const li = btn.closest('li');

    if (li.dataset.terkirim === '1') {
        alert('Tahapan ini tidak dapat dihapus karena progres sudah diserahkan oleh peserta.');
        return;
    }

    if (ol.children.length <= 1) {
        alert('Minimal harus ada 1 tahapan progres.');
        return;
    }

    if (ol.children.length <= progresTerkirimMinimal) {
        alert('Jumlah tahapan tidak boleh kurang dari ' + progresTerkirimMinimal + ' karena peserta sudah menyerahkan ' + progresTerkirimMinimal + ' progres.');
        return;
    }

    li.remove();
    nomorUlang();
}

function nomorUlang() {
    document.querySelectorAll('#daftar-progres .item-progres span:first-child').forEach((span, i) => {
        span.textContent = i + 1;
    });
}

function toggleHapusDokumen(cb) {
    const wadah = document.getElementById('wadah-dokumen-saat-ini');
    const label = document.getElementById('label-nama-berkas');
    const sub = document.getElementById('sub-nama-berkas');
    if (!wadah) return;
    if (cb.checked) {
        wadah.style.background = 'rgba(239, 68, 68, 0.08)';
        wadah.style.borderColor = 'rgba(239, 68, 68, 0.35)';
        label.style.textDecoration = 'line-through';
        label.style.color = 'var(--gagal)';
        sub.textContent = 'Berkas ini akan dihapus permanen saat Anda menyimpan perubahan';
        sub.style.color = 'var(--gagal)';
    } else {
        wadah.style.background = 'rgba(59, 130, 246, 0.08)';
        wadah.style.borderColor = 'rgba(59, 130, 246, 0.25)';
        label.style.textDecoration = 'none';
        label.style.color = 'var(--teks)';
        sub.textContent = 'Berkas instruksi tugas saat ini';
        sub.style.color = '';
    }
}
</script>
@endsection
