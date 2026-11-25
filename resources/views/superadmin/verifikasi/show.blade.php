@extends('layouts.app')
@section('judul', 'Periksa Pengajuan — ' . $pengajuan->kode_pengajuan)
@section('konten')

{{-- ===== KEPALA HALAMAN ===== --}}
<div class="kepala-halaman">
    <div>
        <p class="kicker">
            <a href="{{ route('superadmin.verifikasi.index') }}" class="tautan-kembali">
                <x-ikon nama="kembali" /> Kembali ke Daftar
            </a>
        </p>
        <h1>
            Periksa Pengajuan
            @php
                $kelasStatusHead = match ($pengajuan->status_pengajuan) {
                    'Diajukan'           => 'status-peringatan',
                    'Perlu Perbaikan'    => 'status-gagal',
                    'Lengkap'            => 'status-sukses',
                    'Selesai Diputuskan' => 'status-netral',
                    default              => 'status-netral',
                };
            @endphp
            <span class="status {{ $kelasStatusHead }}" style="font-size:14px;vertical-align:middle;margin-left:10px">
                {{ $pengajuan->status_pengajuan }}
            </span>
        </h1>
        <p class="sub-judul">
            Kode: <b style="font-family:monospace">{{ $pengajuan->kode_pengajuan }}</b>
            &middot; Jalur: <b>{{ $pengajuan->jalur }}</b>
            &middot; Diajukan: {{ $pengajuan->tanggal_pengajuan?->translatedFormat('d M Y') ?? '—' }}
        </p>
    </div>
</div>

{{-- Alert perbaikan sebelumnya --}}
@if ($pengajuan->status_pengajuan === 'Perlu Perbaikan' && $pengajuan->catatan_perbaikan)
    <div class="alert alert-gagal">
        <x-ikon nama="peringatan" />
        <div>
            <b>Catatan Perbaikan Sebelumnya:</b>
            {{ $pengajuan->catatan_perbaikan }}
        </div>
    </div>
@endif

{{-- ===== BAGIAN 1: INFORMASI PENGAJUAN ===== --}}
<div class="kartu">
    <div class="kartu-kepala">
        <div class="kartu-judul">
            <span class="ikon-bulat"><x-ikon nama="berkas" /></span>
            <div>
                <h2>Informasi Pengajuan</h2>
                <p>Detail pengajuan dan informasi pemohon</p>
            </div>
        </div>
    </div>

    <div class="dua-kolom" style="padding:0 22px 20px">
        {{-- Kolom kiri: data umum --}}
        <div>
            <table class="tabel-info">
                <tr>
                    <th>Kode Pengajuan</th>
                    <td><code>{{ $pengajuan->kode_pengajuan }}</code></td>
                </tr>
                <tr>
                    <th>Jalur</th>
                    <td>
                        <span class="chip {{ $pengajuan->jalur === 'DTI' ? 'chip-hijau' : '' }}">
                            {{ $pengajuan->jalur }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Tanggal Pengajuan</th>
                    <td>{{ $pengajuan->tanggal_pengajuan?->translatedFormat('l, d M Y') ?? '—' }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td><span class="status {{ $kelasStatusHead }}">{{ $pengajuan->status_pengajuan }}</span></td>
                </tr>
                @if ($pengajuan->pemeriksa?->pengguna)
                    <tr>
                        <th>Diperiksa Oleh</th>
                        <td>{{ $pengajuan->pemeriksa->pengguna->nama_lengkap }}</td>
                    </tr>
                    <tr>
                        <th>Waktu Pemeriksaan</th>
                        <td>{{ $pengajuan->waktu_pemeriksaan?->translatedFormat('d M Y, H:i') ?? '—' }}</td>
                    </tr>
                @endif
            </table>
        </div>

        {{-- Kolom kanan: data sesuai jalur --}}
        <div>
            @if ($pengajuan->jalur === 'INSTITUSI')
                <table class="tabel-info">
                    <tr>
                        <th>Nama Institusi</th>
                        <td>{{ $pengajuan->nama_institusi ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Nomor Surat</th>
                        <td>{{ $pengajuan->nomor_surat ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Tanggal Surat</th>
                        <td>{{ $pengajuan->tanggal_surat?->translatedFormat('d M Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Kontak</th>
                        <td>{{ $pengajuan->kontak_institusi ?? '—' }}</td>
                    </tr>
                    @if ($pengajuan->dicatatOleh?->pengguna)
                        <tr>
                            <th>Dicatat Oleh</th>
                            <td>{{ $pengajuan->dicatatOleh->pengguna->nama_lengkap }}</td>
                        </tr>
                    @endif
                </table>
            @else
                <table class="tabel-info">
                    <tr>
                        <th>Pendaftar</th>
                        <td>{{ $pengajuan->diajukanOleh?->nama_lengkap ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Email Pendaftar</th>
                        <td>{{ $pengajuan->diajukanOleh?->email ?? '—' }}</td>
                    </tr>
                    @if ($pengajuan->program)
                        <tr>
                            <th>Program</th>
                            <td>{{ $pengajuan->program->nama_program }}</td>
                        </tr>
                        <tr>
                            <th>Periode</th>
                            <td>
                                {{ $pengajuan->program->tanggal_buka->format('d/m/Y') }}
                                –
                                {{ $pengajuan->program->tanggal_tutup->format('d/m/Y') }}
                            </td>
                        </tr>
                    @endif
                </table>
            @endif
        </div>
    </div>
</div>

{{-- ===== BAGIAN 2: CALON PESERTA ===== --}}
<div class="kartu kartu-tabel">
    <div class="kartu-kepala">
        <div class="kartu-judul">
            <span class="ikon-bulat"><x-ikon nama="pengguna" /></span>
            <div>
                <h2>Calon Peserta</h2>
                <p>{{ $pengajuan->calonPeserta->count() }} calon peserta terdaftar dalam pengajuan ini</p>
            </div>
        </div>
    </div>
    <div class="tabel-wadah">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>No. HP</th>
                    <th>NIM / NIS</th>
                    <th>Institusi Asal</th>
                    <th>Program / Prodi</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($pengajuan->calonPeserta as $calon)
                <tr>
                    <td class="redup">{{ $loop->iteration }}</td>
                    <td><b>{{ $calon->nama_lengkap }}</b></td>
                    <td>{{ $calon->email ?? '—' }}</td>
                    <td>{{ $calon->no_hp ?? '—' }}</td>
                    <td><code>{{ $calon->nomor_induk_pendidikan ?? '—' }}</code></td>
                    <td>{{ $calon->institusi_asal ?? '—' }}</td>
                    <td>{{ $calon->jurusan_prodi ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="kosong">
                            <span class="ikon-besar"><x-ikon nama="pengguna" /></span>
                            <b>Tidak ada calon peserta.</b>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ===== BAGIAN 3: FORM PEMERIKSAAN DOKUMEN ===== --}}
@php
    $sudahFinal    = $pengajuan->status_pengajuan === 'Selesai Diputuskan';
    $adaDokumen    = $pengajuan->dokumenPengajuan->isNotEmpty();
@endphp

@if ($sudahFinal)
    <div class="alert alert-info">
        <x-ikon nama="info" />
        <div>Pengajuan ini telah <b>Selesai Diputuskan</b> dan tidak dapat diperiksa ulang.</div>
    </div>
@endif

<form
    id="form-pemeriksaan"
    method="POST"
    action="{{ route('superadmin.verifikasi.periksa', $pengajuan) }}"
>
@csrf

{{-- --- Daftar Dokumen --- --}}
<div class="kartu">
    <div class="kartu-kepala">
        <div class="kartu-judul">
            <span class="ikon-bulat"><x-ikon nama="papan-centang" /></span>
            <div>
                <h2>Checklist Dokumen</h2>
                <p>Periksa setiap dokumen dan tandai hasilnya. Dokumen yang Tidak Sesuai wajib diberi catatan.</p>
            </div>
        </div>
    </div>

    @if ($adaDokumen)
        <div class="tabel-wadah">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Jenis Dokumen</th>
                        <th>Syarat / Keterangan</th>
                        <th>Ukuran</th>
                        <th>Diunggah</th>
                        <th>Lihat</th>
                        <th style="min-width:160px">Hasil Pemeriksaan</th>
                        <th style="min-width:220px">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($pengajuan->dokumenPengajuan as $dok)
                    @php
                        $kelasDokStatus = match ($dok->hasil_pemeriksaan) {
                            'Sesuai'      => 'status-sukses',
                            'Tidak Sesuai'=> 'status-gagal',
                            default       => 'status-netral',
                        };
                        $hasilLama  = old("hasil.{$dok->id_dokumen}", $dok->hasil_pemeriksaan);
                        $catatanLama= old("catatan.{$dok->id_dokumen}", $dok->catatan_pemeriksaan);
                        $ukuranKb   = $dok->ukuran_bytes ? number_format($dok->ukuran_bytes / 1024, 1) . ' KB' : '—';
                        $wajib      = $dok->syarat?->wajib ?? false;
                    @endphp
                    <tr id="baris-{{ $dok->id_dokumen }}">
                        <td class="redup">{{ $loop->iteration }}</td>

                        <td>
                            <div>
                                <b>{{ $dok->jenis_dokumen }}</b>
                                @if ($wajib)
                                    <span class="chip" style="font-size:10px;padding:1px 6px;margin-left:4px">Wajib</span>
                                @endif
                            </div>
                            <small class="redup">{{ $dok->nama_berkas }}</small>
                        </td>

                        <td>{{ $dok->syarat?->nama_syarat ?? '—' }}</td>

                        <td class="redup">{{ $ukuranKb }}</td>

                        <td>
                            <small>{{ $dok->uploaded_at?->translatedFormat('d M Y, H:i') ?? '—' }}</small>
                        </td>

                        <td>
                            @if ($dok->path_berkas)
                                <a
                                    class="btn btn-garis btn-kecil"
                                    href="{{ route('superadmin.verifikasi.dokumen', [$pengajuan, $dok]) }}"
                                    target="_blank"
                                    title="Lihat / unduh dokumen"
                                >
                                    <x-ikon nama="berkas" /> Lihat
                                </a>
                            @else
                                <span class="redup">—</span>
                            @endif
                        </td>

                        <td>
                            @if ($sudahFinal)
                                <span class="status {{ $kelasDokStatus }}">
                                    {{ $dok->hasil_pemeriksaan ?? 'Belum diperiksa' }}
                                </span>
                            @else
                                <select
                                    name="hasil[{{ $dok->id_dokumen }}]"
                                    id="hasil-{{ $dok->id_dokumen }}"
                                    class="select-hasil"
                                    data-id="{{ $dok->id_dokumen }}"
                                    {{ $sudahFinal ? 'disabled' : '' }}
                                >
                                    <option value="">— Pilih —</option>
                                    @foreach (\App\Models\DokumenPengajuan::HASIL as $h)
                                        <option value="{{ $h }}" @selected($hasilLama === $h)>{{ $h }}</option>
                                    @endforeach
                                </select>
                                @error("hasil.{$dok->id_dokumen}")
                                    <small class="teks-gagal">{{ $message }}</small>
                                @enderror
                            @endif
                        </td>

                        <td>
                            @if ($sudahFinal)
                                <span class="redup">{{ $dok->catatan_pemeriksaan ?? '—' }}</span>
                            @else
                                <textarea
                                    name="catatan[{{ $dok->id_dokumen }}]"
                                    id="catatan-{{ $dok->id_dokumen }}"
                                    rows="2"
                                    placeholder="Opsional, wajib jika Tidak Sesuai"
                                    {{ $sudahFinal ? 'disabled' : '' }}
                                >{{ $catatanLama }}</textarea>
                                @error("catatan.{$dok->id_dokumen}")
                                    <small class="teks-gagal">{{ $message }}</small>
                                @enderror
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="kosong" style="padding:36px 22px">
            <div class="ilustrasi"><x-ikon nama="berkas" /></div>
            <b>Tidak ada dokumen yang diunggah.</b>
            Pengajuan ini belum memiliki dokumen yang perlu diperiksa.
        </div>
    @endif
</div>

{{-- --- Kesimpulan Pemeriksaan --- --}}
@if (!$sudahFinal && $adaDokumen)
<div class="kartu">
    <div class="kartu-kepala">
        <div class="kartu-judul">
            <span class="ikon-bulat"><x-ikon nama="centang" /></span>
            <div>
                <h2>Kesimpulan Pemeriksaan</h2>
                <p>Tentukan status akhir pengajuan berdasarkan hasil pemeriksaan dokumen.</p>
            </div>
        </div>
    </div>

    <div class="form-bagian" style="border-top:1px solid var(--garis)">
        <div class="form-bagian-ket">
            <h2>Status Akhir</h2>
            <p>
                Pilih <b>Lengkap</b> jika semua dokumen wajib sesuai.<br>
                Pilih <b>Perlu Perbaikan</b> jika ada dokumen yang perlu diperbaiki.
            </p>
        </div>
        <div class="form-bagian-isi">
            <label>
                Kesimpulan <span class="label-wajib">*</span>
                <select name="kesimpulan" id="select-kesimpulan" required>
                    <option value="">— Pilih kesimpulan —</option>
                    <option value="Lengkap" @selected(old('kesimpulan') === 'Lengkap')>Lengkap</option>
                    <option value="Perlu Perbaikan" @selected(old('kesimpulan') === 'Perlu Perbaikan')>Perlu Perbaikan</option>
                </select>
                @error('kesimpulan')
                    <small class="teks-gagal">{{ $message }}</small>
                @enderror
            </label>

            <div id="area-catatan-perbaikan" style="display:none">
                <label>
                    Catatan Perbaikan <span class="label-wajib">*</span>
                    <textarea
                        name="catatan_perbaikan"
                        id="catatan-perbaikan"
                        rows="4"
                        placeholder="Jelaskan dokumen mana yang perlu diperbaiki dan apa yang perlu dilengkapi..."
                    >{{ old('catatan_perbaikan') }}</textarea>
                    @error('catatan_perbaikan')
                        <small class="teks-gagal">{{ $message }}</small>
                    @enderror
                </label>
            </div>
        </div>
    </div>

    <div class="form-kaki">
        <a class="btn btn-garis" href="{{ route('superadmin.verifikasi.index') }}">
            <x-ikon nama="kembali" /> Batal
        </a>
        <button type="submit" class="btn" id="tombol-simpan">
            <x-ikon nama="centang" /> Simpan Pemeriksaan
        </button>
    </div>
</div>
@elseif (!$sudahFinal && !$adaDokumen)
    <div class="info-kotak">
        <x-ikon nama="info" class="ikon" />
        <p>Pengajuan ini tidak memiliki dokumen. Tidak ada yang perlu diperiksa.</p>
    </div>
@endif

</form>

{{-- ===== JAVASCRIPT: show/hide catatan perbaikan + highlight Tidak Sesuai ===== --}}
@if (!$sudahFinal)
<script>
(function () {
    // Tampilkan / sembunyikan area catatan perbaikan
    const selKesimpulan = document.getElementById('select-kesimpulan');
    const areaCatatan   = document.getElementById('area-catatan-perbaikan');
    const txtCatatan    = document.getElementById('catatan-perbaikan');

    function toggleCatatan() {
        const perlu = selKesimpulan && selKesimpulan.value === 'Perlu Perbaikan';
        if (areaCatatan) areaCatatan.style.display = perlu ? '' : 'none';
        if (txtCatatan)  txtCatatan.required = perlu;
    }

    if (selKesimpulan) {
        selKesimpulan.addEventListener('change', toggleCatatan);
        toggleCatatan(); // inisialisasi saat halaman dimuat (untuk old() value)
    }

    // Sorot baris dokumen yang bertanda Tidak Sesuai
    document.querySelectorAll('.select-hasil').forEach(function (sel) {
        function sorotBaris() {
            const id    = sel.dataset.id;
            const baris = document.getElementById('baris-' + id);
            if (!baris) return;
            if (sel.value === 'Tidak Sesuai') {
                baris.style.background = 'rgba(220, 38, 38, 0.04)';
            } else if (sel.value === 'Sesuai') {
                baris.style.background = 'rgba(2, 154, 76, 0.04)';
            } else {
                baris.style.background = '';
            }
        }
        sel.addEventListener('change', sorotBaris);
        sorotBaris(); // inisialisasi
    });
})();
</script>
@endif

<style>
/* Tabel info dua kolom (kiri: label, kanan: nilai) */
.tabel-info { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.tabel-info th,
.tabel-info td { padding: 7px 0; vertical-align: top; }
.tabel-info th { width: 38%; color: var(--redup); font-weight: 500; padding-right: 14px; }
.tabel-info td { color: var(--teks); }
.tabel-info tr + tr th,
.tabel-info tr + tr td { border-top: 1px solid var(--garis); padding-top: 8px; }

/* Teks error per field */
.teks-gagal { color: var(--gagal); display: block; margin-top: 3px; font-size: 12px; }

/* Label wajib */
.label-wajib { color: var(--gagal); }

/* Tautan kembali */
.tautan-kembali {
    display: inline-flex; align-items: center; gap: 5px;
    color: var(--redup); text-decoration: none; font-size: 13px;
    transition: color .15s;
}
.tautan-kembali:hover { color: var(--utama); }
</style>

@endsection
