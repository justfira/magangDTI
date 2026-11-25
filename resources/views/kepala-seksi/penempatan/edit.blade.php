@extends('layouts.app')
@section('judul', 'Atur Penempatan Peserta')
@section('konten')

{{-- ===== KEPALA HALAMAN ===== --}}
<div class="kepala-halaman">
    <div>
        <p class="kicker">UC-07 &mdash; Seksi {{ $seksiKasi?->nama_seksi ?? '—' }}</p>
        <h1>Atur Penempatan Peserta</h1>
        <p class="sub-judul">
            Pilih mentor pembimbing dari
            <b>{{ $seksiKasi?->nama_seksi ?? 'seksi Anda' }}</b>
            untuk peserta berikut.
        </p>
    </div>
    <div>
        <a class="btn btn-garis" href="{{ route('kasi.penempatan.index') }}">
            <x-ikon nama="atur-ulang" /> Kembali ke Daftar
        </a>
    </div>
</div>

{{-- ===== FLASH MESSAGE ===== --}}
@if (session('sukses'))
    <div class="alert alert-sukses" role="alert">{{ session('sukses') }}</div>
@endif
@if (session('gagal'))
    <div class="alert alert-gagal" role="alert">{{ session('gagal') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-gagal" role="alert">
        <b>Terdapat kesalahan:</b>
        <ul style="margin:6px 0 0 18px;padding:0">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $calon  = $peserta->keputusan?->calon;
    $pengaj = $calon?->pengajuan;
    $jalur  = $pengaj?->jalur ?? '—';

    $labelSumber = $jalur === 'INSTITUSI'
        ? ($pengaj?->nama_institusi ?? $calon?->institusi_asal ?? '—')
        : ($pengaj?->diajukanOleh?->nama_lengkap ?? '—');
@endphp

{{-- ===== LAYOUT DUA KOLOM ===== --}}
<div class="layout-dua-kolom">

    {{-- ── KOLOM KIRI: Informasi Peserta ── --}}
    <div class="kolom-kiri">
        <div class="kartu">
            <div class="kartu-kepala">
                <div class="kartu-judul">
                    <span class="ikon-bulat"><x-ikon nama="orang" /></span>
                    <div>
                        <h2>Informasi Peserta</h2>
                        <p class="redup" style="margin:0;font-size:12.5px">Data calon peserta magang</p>
                    </div>
                </div>
            </div>
            <div class="kartu-isi">

                {{-- Avatar + nama --}}
                <div class="profil-ringkas">
                    <span class="inisial v{{ abs(crc32($calon?->nama_lengkap ?? '')) % 5 }} inisial-besar">
                        {{ mb_strtoupper(mb_substr($calon?->nama_lengkap ?? '?', 0, 1)) }}
                    </span>
                    <div>
                        <div class="nama-peserta">{{ $calon?->nama_lengkap ?? '—' }}</div>
                        <div class="redup" style="font-size:13px">{{ $peserta->pengguna?->email ?? '—' }}</div>
                        <span class="status status-netral" style="margin-top:4px;display:inline-block">
                            {{ $peserta->status_magang }}
                        </span>
                    </div>
                </div>

                <div class="pemisah"></div>

                {{-- Detail peserta --}}
                <dl class="detail-list">
                    <dt><x-ikon nama="berkas" /> Nomor Induk</dt>
                    <dd>{{ $calon?->nomor_induk_pendidikan ?? '—' }}</dd>

                    <dt><x-ikon nama="kode" /> Institusi / Program</dt>
                    <dd>
                        {{ $labelSumber }}
                        @if ($calon?->jurusan_prodi)
                            <small class="redup" style="display:block">{{ $calon->jurusan_prodi }}</small>
                        @endif
                    </dd>

                    <dt><x-ikon nama="tugas" /> Jalur Pendaftaran</dt>
                    <dd>
                        <span class="status {{ $jalur === 'INSTITUSI' ? 'status-info' : 'status-netral' }}">
                            {{ $jalur }}
                        </span>
                    </dd>

                    @if ($calon?->no_hp)
                        <dt><x-ikon nama="orang" /> Nomor HP</dt>
                        <dd>{{ $calon->no_hp }}</dd>
                    @endif

                    <dt><x-ikon nama="papan-centang" /> Periode Magang</dt>
                    <dd>
                        {{ $peserta->tanggal_mulai?->format('d M Y') ?? '—' }}
                        <span class="redup">&ndash;</span>
                        {{ $peserta->tanggal_selesai?->format('d M Y') ?? '—' }}
                    </dd>
                </dl>

            </div>
        </div>
    </div>

    {{-- ── KOLOM KANAN: Form Penempatan ── --}}
    <div class="kolom-kanan">
        <div class="kartu">
            <div class="kartu-kepala">
                <div class="kartu-judul">
                    <span class="ikon-bulat"><x-ikon nama="tambah-orang" /></span>
                    <div>
                        <h2>Penempatan Mentor</h2>
                        <p class="redup" style="margin:0;font-size:12.5px">
                            Mentor dari Seksi {{ $seksiKasi?->nama_seksi ?? '—' }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="kartu-isi">

                {{-- Info seksi (read-only) --}}
                <div class="panel-rekomendasi panel-rek-kepala" style="margin-bottom:16px">
                    <div class="panel-rek-isi">
                        <span class="ikon-bulat" style="flex-shrink:0"><x-ikon nama="perisai" /></span>
                        <div>
                            <div style="font-size:13px;font-weight:600">
                                Seksi Tujuan: {{ $seksiKasi?->nama_seksi ?? '—' }}
                            </div>
                            <div style="font-size:12px;color:var(--redup)">
                                Peserta akan ditempatkan ke seksi Anda secara otomatis.
                            </div>
                        </div>
                    </div>
                </div>

                @if ($daftarMentor->isEmpty())
                    {{-- Empty state: tidak ada mentor --}}
                    <div class="empty-state" style="padding:32px 0">
                        <x-ikon nama="orang" />
                        <p>
                            Tidak ada mentor aktif di
                            <b>{{ $seksiKasi?->nama_seksi ?? 'seksi Anda' }}</b> saat ini.
                            <small class="redup" style="display:block;margin-top:4px">
                                Pastikan pegawai dengan peran Mentor di seksi ini sudah didaftarkan dan akunnya aktif.
                                Hubungi Superadmin untuk menambahkan mentor.
                            </small>
                        </p>
                    </div>

                    <div style="margin-top:16px">
                        <a class="btn btn-garis" href="{{ route('kasi.penempatan.index') }}">
                            <x-ikon nama="atur-ulang" /> Kembali ke Daftar
                        </a>
                    </div>

                @else
                    {{-- Form penempatan --}}
                    <form
                        id="form-penempatan"
                        method="POST"
                        action="{{ route('kasi.penempatan.update', $peserta) }}"
                    >
                        @csrf
                        @method('PUT')

                        {{-- Pilih mentor --}}
                        <div class="grup-form {{ $errors->has('id_mentor') ? 'grup-form-error' : '' }}">
                            <label for="id_mentor" class="label-form">
                                Mentor Pembimbing <span class="wajib">*</span>
                            </label>
                            <select
                                id="id_mentor"
                                name="id_mentor"
                                class="input-form"
                                required
                            >
                                <option value="" disabled {{ old('id_mentor') ? '' : 'selected' }}>
                                    — Pilih mentor —
                                </option>
                                @foreach ($daftarMentor as $mentor)
                                    <option
                                        value="{{ $mentor->id_pegawai }}"
                                        {{ old('id_mentor') == $mentor->id_pegawai ? 'selected' : '' }}
                                    >
                                        {{ $mentor->pengguna?->nama_lengkap ?? '—' }}
                                        @if ($mentor->jabatan)
                                            ({{ $mentor->jabatan }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('id_mentor')
                                <p class="pesan-error">{{ $message }}</p>
                            @enderror
                            <p class="bantuan-form">
                                Hanya menampilkan pegawai berperan Mentor dari
                                {{ $seksiKasi?->nama_seksi ?? 'seksi Anda' }} dengan akun aktif.
                            </p>
                        </div>

                        {{-- Konfirmasi & aksi --}}
                        <div class="panel-konfirmasi" style="margin-top:8px">
                            <div class="panel-rek-isi" style="font-size:13px;color:var(--redup)">
                                <x-ikon nama="perisai" />
                                <span>
                                    Setelah disimpan, penempatan ini <b>tidak dapat diubah</b>
                                    melalui halaman ini.
                                </span>
                            </div>
                        </div>

                        <div class="grup-aksi" style="margin-top:20px">
                            <a class="btn btn-garis" href="{{ route('kasi.penempatan.index') }}">
                                <x-ikon nama="atur-ulang" /> Batal
                            </a>
                            <button type="submit" class="btn" id="btn-simpan">
                                <x-ikon nama="centang-saja" /> Simpan Penempatan
                            </button>
                        </div>

                    </form>
                @endif

            </div>
        </div>
    </div>

</div>

{{-- ===== CSS TAMBAHAN ===== --}}
<style>
/* Layout dua kolom */
.layout-dua-kolom {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    align-items: start;
}
@media (max-width: 860px) {
    .layout-dua-kolom { grid-template-columns: 1fr; }
    .kolom-kiri { order: 2; }
    .kolom-kanan { order: 1; }
}

/* Alert */
.alert {
    padding: 12px 18px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 14px;
    line-height: 1.6;
}
.alert-sukses {
    background: rgba(22,101,52,.08);
    border-left: 4px solid var(--utama, #166534);
    color: #14532d;
}
.alert-gagal {
    background: rgba(220,38,38,.07);
    border-left: 4px solid var(--gagal, #dc2626);
    color: #991b1b;
}

/* Profil ringkas */
.profil-ringkas {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 16px;
}
.nama-peserta {
    font-size: 16px;
    font-weight: 700;
    color: var(--teks, #111);
}
.inisial-besar {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 700;
    flex-shrink: 0;
}

/* Pemisah */
.pemisah {
    border: none;
    border-top: 1px solid var(--batas, #e5e7eb);
    margin: 14px 0;
}

/* Detail list */
.detail-list {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 10px 16px;
    font-size: 13.5px;
    margin: 0;
}
.detail-list dt {
    display: flex;
    align-items: center;
    gap: 5px;
    color: var(--redup, #6b7280);
    font-weight: 500;
    white-space: nowrap;
}
.detail-list dd {
    margin: 0;
    color: var(--teks, #111);
    font-weight: 500;
    word-break: break-word;
}

/* Status info (jalur INSTITUSI) */
.status-info { background: rgba(37,99,235,.1); color: #1d4ed8; }

/* Panel rekomendasi (reuse dari komponen yang ada) */
.panel-konfirmasi {
    background: rgba(234,179,8,.06);
    border: 1px solid rgba(234,179,8,.25);
    border-radius: 8px;
    padding: 10px 14px;
}
.panel-rek-isi {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

/* Form */
.grup-form {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-bottom: 16px;
}
.grup-form-error .input-form {
    border-color: var(--gagal, #dc2626);
}
.label-form {
    font-size: 13px;
    font-weight: 600;
    color: var(--teks, #111);
}
.wajib { color: var(--gagal, #dc2626); }
.input-form {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid var(--batas, #d1d5db);
    border-radius: 8px;
    font-size: 14px;
    background: #fff;
    color: var(--teks, #111);
    box-sizing: border-box;
    transition: border-color .15s;
}
.input-form:focus {
    outline: none;
    border-color: var(--utama, #166534);
    box-shadow: 0 0 0 3px rgba(22,101,52,.12);
}
.pesan-error {
    font-size: 12.5px;
    color: var(--gagal, #dc2626);
    margin: 0;
}
.bantuan-form {
    font-size: 12px;
    color: var(--redup, #6b7280);
    margin: 0;
}

/* Grup aksi */
.grup-aksi {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
</style>

{{-- ===== JS: Konfirmasi submit ===== --}}
<script>
document.getElementById('form-penempatan')?.addEventListener('submit', function (e) {
    var select  = document.getElementById('id_mentor');
    var label   = select.options[select.selectedIndex]?.text ?? '';
    var nama    = @json($calon?->nama_lengkap ?? '—');
    var msg = 'Konfirmasi Penempatan\n\n'
            + 'Peserta  : ' + nama + '\n'
            + 'Mentor   : ' + label.trim() + '\n\n'
            + 'Penempatan ini tidak dapat diubah setelah disimpan.\n'
            + 'Lanjutkan?';
    if (!confirm(msg)) {
        e.preventDefault();
    }
});
</script>

@endsection
