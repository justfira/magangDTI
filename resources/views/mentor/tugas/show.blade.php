@extends('layouts.app')
@section('judul', 'Detail Tugas & Progres')
@section('konten')
<div class="kepala-halaman">
    <div>
        <a class="tautan-kembali" href="{{ route('mentor.tugas.index') }}"><x-ikon nama="kembali" /> Kembali ke daftar tugas</a>
        <h1>{{ $tugas->judul }}</h1>
        <p class="sub-judul">Proyek penugasan peserta bimbingan dan riwayat penyerahan progres.</p>
    </div>
</div>

@php
    $peserta = $tugas->pesertaMagang;
    $namaPeserta = $peserta?->calonPeserta()?->nama_lengkap ?? $peserta?->pengguna?->nama_lengkap ?? 'Peserta';
    $urutanAktif = $tugas->urutanProgresTerakhir();
    $kelasStatus = match ($tugas->status) {
        'Diserahkan' => 'status-peringatan',
        'Selesai' => 'status-sukses',
        'Perlu Perbaikan' => 'status-gagal',
        default => 'status-netral',
    };
@endphp

<div class="kartu">
    <div class="kartu-kepala">
        <div class="kartu-judul">
            <span class="ikon-bulat"><x-ikon nama="tugas" /></span>
            <div>
                <h2>Informasi Tugas</h2>
                <p>Penerima: <b>{{ $namaPeserta }}</b> ({{ $peserta?->calonPeserta()?->institusi_asal ?? 'DTI Unand' }})</p>
            </div>
        </div>
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($tugas->status !== 'Selesai')
                <a href="{{ route('mentor.tugas.edit', $tugas) }}" class="btn btn-garis btn-kecil">
                    <x-ikon nama="ubah" /> Ubah Tugas &amp; Progres
                </a>
                <form method="POST" action="{{ route('mentor.tugas.destroy', $tugas) }}" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus tugas ini secara permanen?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-garis-bahaya btn-kecil" title="Hapus tugas ini">
                        <x-ikon nama="silang" /> Hapus
                    </button>
                </form>
            @endif
            <span class="chip">Progres ke-{{ $urutanAktif }} dari {{ $tugas->jumlah_progres }}</span>
            <span class="status {{ $kelasStatus }}">{{ $tugas->status }}</span>
        </div>
    </div>

    <div style="margin-top: 10px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; background: var(--permukaan-2); padding: 14px 18px; border-radius: var(--radius-kecil); margin-bottom: 16px;">
        <div>
            <span class="redup" style="font-size: 13px; display: block;">Tenggat Waktu:</span>
            <b><x-ikon nama="kalender" /> {{ $tugas->tenggat?->format('d F Y, H:i') }} WIB</b>
        </div>
        <div>
            <span class="redup" style="font-size: 13px; display: block;">Target Progres:</span>
            <b>{{ $tugas->jumlah_progres }} Tahapan</b>
        </div>
        <div>
            <span class="redup" style="font-size: 13px; display: block;">Tanggal Pembuatan:</span>
            <b>{{ $tugas->created_at?->format('d/m/Y H:i') ?? '-' }}</b>
        </div>
    </div>

    <div>
        <h3 style="margin-bottom: 6px;">Deskripsi / Instruksi:</h3>
        <p style="white-space: pre-line; color: var(--teks);">{{ $tugas->deskripsi }}</p>
    </div>

    @if ($tugas->path_dokumen)
        <div style="margin-top: 14px; padding: 12px 16px; background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: var(--radius-kecil); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="ikon-bulat" style="background: #2563eb; color: #fff; width: 36px; height: 36px;"><x-ikon nama="berkas" /></span>
                <div>
                    <b style="font-size: 13.5px; display: block; color: var(--teks);">Dokumen Instruksi / Modul Tugas Terlampir</b>
                    <small class="redup">{{ basename($tugas->path_dokumen) }} · Dapat diunduh oleh peserta bimbingan</small>
                </div>
            </div>
            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <a href="{{ route('tugas.dokumen', $tugas) }}" target="_blank" class="btn btn-garis btn-kecil" title="Buka dan lihat dokumen instruksi di tab baru">
                    <x-ikon nama="mata" /> Lihat Dokumen
                </a>
                <a href="{{ route('tugas.dokumen', ['tugas' => $tugas, 'unduh' => 1]) }}" class="btn btn-garis btn-kecil" title="Unduh dokumen instruksi">
                    <x-ikon nama="berkas" /> Unduh
                </a>
            </div>
        </div>
    @endif

    @if ($tugas->judul_progres)
        <div style="margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--garis);">
            <h3 style="margin-bottom: 10px;">Tahapan Progres:</h3>
            <ol style="padding-left: 0; margin: 0; list-style: none; display: flex; flex-direction: column; gap: 8px;">
                @foreach ($tugas->judul_progres as $i => $j)
                    @php
                        $no = $i + 1;
                        $progresNo = $tugas->progresTugas->where('urutan', $no)->first();
                    @endphp
                    <li style="display: flex; gap: 10px; align-items: center; padding: 8px 14px; background: var(--permukaan-2); border: 1px solid var(--garis); border-radius: var(--radius-kecil);{{ $progresNo?->hasil_review === 'Diterima' ? ' border-left: 3px solid var(--sukses);' : ($progresNo ? ' border-left: 3px solid var(--aksen);' : '') }}">
                        <span style="font-weight: 700; color: var(--aksen); min-width: 24px;">{{ $no }}</span>
                        <span style="flex: 1;">{{ $j }}</span>
                        @if ($progresNo?->hasil_review === 'Diterima')
                            <span class="status status-sukses" style="font-size: 12px;">Diterima</span>
                        @elseif ($progresNo?->hasil_review === 'Perlu Perbaikan')
                            <span class="status status-gagal" style="font-size: 12px;">Perlu Perbaikan</span>
                        @elseif ($progresNo)
                            <span class="status status-peringatan" style="font-size: 12px;">Menunggu Review</span>
                        @else
                            <span class="status" style="font-size: 12px; opacity: .6;">Belum dimulai</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    @endif
</div>

<div class="kartu">
    <div class="kartu-kepala">
        <div class="kartu-judul">
            <span class="ikon-bulat olive"><x-ikon nama="jam" /></span>
            <div>
                <h2>Riwayat Progres Tugas</h2>
                <p>Seluruh catatan, lampiran berkas, tautan hasil, serta catatan review mentor.</p>
            </div>
        </div>
    </div>

    @if ($tugas->progresTugas->isEmpty())
        <div class="kosong">
            <div class="ilustrasi"><x-ikon nama="jam" /></div>
            <b>Belum Ada Progres Dikirim</b>
            Peserta belum mengirimkan progres atau hasil pengerjaan untuk tugas ini.
        </div>
    @else
        <ul class="langkah">
            @foreach ($tugas->progresTugas as $p)
                @php
                    $isTerakhir = ($p->id_progres === $progresTerakhir?->id_progres);
                    $kelasLangkah = match ($p->hasil_review) {
                        'Diterima' => 'selesai',
                        'Perlu Perbaikan' => 'gagal',
                        default => ($isTerakhir && $tugas->status === 'Diserahkan' ? 'aktif' : ''),
                    };
                @endphp
                <li class="{{ $kelasLangkah }}">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
                        <div>
                            <b>Progres ke-{{ $p->urutan }} dari {{ $tugas->jumlah_progres }}: {{ $tugas->judulProgresKe($p->urutan) }}</b>
                            <div style="display: flex; gap: 8px; align-items: center; margin-top: 4px; flex-wrap: wrap;">
                                <small class="redup"><x-ikon nama="kalender" /> Dikirim: {{ $p->waktu_kirim?->format('d M Y, H:i') }}</small>
                                @if ($p->apakahTerlambat())
                                    <span class="chip" style="background: rgba(239, 68, 68, 0.12); color: var(--gagal); font-weight: 600; font-size: 11.5px; padding: 2px 8px; border-radius: 999px;">
                                        Terlambat ({{ $p->selisihKeterlambatan() }})
                                    </span>
                                @else
                                    <span class="chip" style="background: rgba(34, 197, 94, 0.12); color: var(--sukses); font-weight: 600; font-size: 11.5px; padding: 2px 8px; border-radius: 999px;">
                                        Tepat Waktu
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div>
                            @if ($p->hasil_review)
                                <span class="status {{ $p->hasil_review === 'Diterima' ? 'status-sukses' : 'status-gagal' }}">
                                    {{ $p->hasil_review }}
                                </span>
                            @else
                                <span class="status status-peringatan">Menunggu Review</span>
                            @endif
                        </div>
                    </div>

                    <div style="margin-top: 10px; background: var(--permukaan-2); border: 1px solid var(--garis); padding: 12px 16px; border-radius: var(--radius-kecil);">
                        <p style="white-space: pre-line; margin-bottom: 10px;">{{ $p->catatan_progres }}</p>

                        <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; font-size: 13.5px; padding-top: 10px; border-top: 1px dashed var(--garis);">
                            @if ($p->path_berkas)
                                <div style="display: inline-flex; gap: 6px; align-items: center;">
                                    <a href="{{ route('tugas.progres.berkas', $p) }}" target="_blank" class="btn btn-garis btn-kecil" title="Buka dan lihat berkas di tab baru">
                                        <x-ikon nama="mata" /> Lihat Bukti ({{ basename($p->path_berkas) }})
                                    </a>
                                    <a href="{{ route('tugas.progres.berkas', ['progres' => $p, 'unduh' => 1]) }}" class="btn btn-garis btn-kecil" title="Unduh berkas ke perangkat">
                                        <x-ikon nama="berkas" /> Unduh
                                    </a>
                                </div>
                            @else
                                <span class="redup" style="font-size: 12.5px; display: inline-flex; align-items: center; gap: 4px;">
                                    <x-ikon nama="info" style="width: 14px; height: 14px;" /> Tidak ada berkas diunggah
                                </span>
                            @endif
                            @if ($p->tautan_hasil)
                                <a href="{{ $p->tautan_hasil }}" target="_blank" rel="noopener noreferrer" class="btn btn-garis btn-kecil">
                                    <x-ikon nama="panah-kanan" /> Buka Tautan Hasil
                                </a>
                            @endif
                        </div>

                        @if ($p->hasil_review)
                            <div style="margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--garis);">
                                <small class="redup" style="display:block; margin-bottom:4px;">
                                    Catatan Review Mentor ({{ $p->waktu_review?->format('d M Y, H:i') }}):
                                </small>
                                <b>{{ $p->catatan_review ?: 'Tidak ada catatan tambahan.' }}</b>
                            </div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>

@if ($bisaReview)
    <div class="kartu" id="form-review" style="border-left: 4px solid var(--aksen);">
        <div class="kartu-kepala">
            <div class="kartu-judul">
                <span class="ikon-bulat kuning"><x-ikon nama="papan-centang" /></span>
                <div>
                    <h2>Pemeriksaan Progres ke-{{ $progresTerakhir->urutan }}: {{ $tugas->judulProgresKe($progresTerakhir->urutan) }}</h2>
                    <p>Tentukan keputusan hasil pemeriksaan dan berikan umpan balik atau catatan revisi untuk peserta.</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('mentor.progres.review', $progresTerakhir) }}">
            @csrf

            <div style="margin-bottom: 16px;">
                <label style="font-weight: 600; margin-bottom: 8px; display: block;">Keputusan Review</label>
                <div style="display: flex; gap: 24px; flex-wrap: wrap; margin-top: 8px;">
                    <label style="display: inline-flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
                        <input type="radio" name="hasil_review" value="Diterima" @checked(old('hasil_review') === 'Diterima') required>
                        <b>Diterima</b> (Progres disetujui)
                    </label>
                    <label style="display: inline-flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
                        <input type="radio" name="hasil_review" value="Perlu Perbaikan" @checked(old('hasil_review') === 'Perlu Perbaikan') required>
                        <b style="color: var(--gagal);">Perlu Perbaikan</b> (Peserta wajib mengirim revisi)
                    </label>
                </div>
                @error('hasil_review')<span class="pesan-salah">{{ $message }}</span>@enderror
            </div>

            <label>Catatan Review
                <textarea name="catatan_review" rows="3" placeholder="Wajib diisi jika memilih 'Perlu Perbaikan'. Berikan petunjuk perbaikan bagi peserta...">{{ old('catatan_review') }}</textarea>
                <small class="redup">Wajib diisi apabila keputusan adalah "Perlu Perbaikan".</small>
                @error('catatan_review')<span class="pesan-salah">{{ $message }}</span>@enderror
            </label>

            <div class="aksi" style="margin-top: 16px;">
                <button class="btn" type="submit"><x-ikon nama="centang-saja" /> Simpan Hasil Pemeriksaan</button>
            </div>
        </form>
    </div>
@endif

@if ($tugas->status === 'Selesai')
    <div class="kartu" style="border-left: 4px solid var(--sukses);">
        <div class="kartu-kepala">
            <div class="kartu-judul">
                <span class="ikon-bulat hijau"><x-ikon nama="medali" /></span>
                <div>
                    <h2>Penilaian Akhir Tugas</h2>
                    <p>{{ $tugas->nilai !== null ? 'Tugas ini telah dinilai oleh Anda sebagai mentor pembimbing.' : 'Semua progres telah diterima. Berikan nilai akhir dan catatan evaluasi kepada peserta.' }}</p>
                </div>
            </div>
            @if ($tugas->nilai !== null)
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="chip" style="font-size: 16px; font-weight: 700; background: rgba(34, 197, 94, 0.15); color: var(--sukses); padding: 6px 14px; border-radius: 999px;">
                        Nilai: {{ $tugas->nilai }} / 100
                    </span>
                    <button type="button" class="btn btn-garis btn-kecil" onclick="toggleFormUbahNilai()" id="btn-toggle-ubah-nilai">
                        <x-ikon nama="pensil" /> Ubah Nilai
                    </button>
                </div>
            @endif
        </div>

        @if ($tugas->nilai !== null)
            <div id="kotak-tampilan-nilai" style="background: var(--permukaan-2); padding: 14px 18px; border-radius: var(--radius-kecil); border: 1px solid var(--garis); margin-top: 10px;">
                <small class="redup" style="display: block; margin-bottom: 4px;">Catatan Penilaian:</small>
                <p style="white-space: pre-line; margin: 0; font-weight: 600;">{{ $tugas->catatan_nilai ?: 'Tidak ada catatan tambahan.' }}</p>
            </div>
        @endif

        <div id="form-penilaian-akhir" style="{{ $tugas->nilai !== null ? 'display: none; margin-top: 16px; padding-top: 16px; border-top: 1px dashed var(--garis);' : '' }}">
            <form method="POST" action="{{ route('mentor.tugas.nilai', $tugas) }}">
                @csrf
                <div class="tata-form" style="grid-template-columns: 180px 1fr; gap: 16px; margin-top: 12px;">
                    <label>Nilai (0 - 100) <span class="wajib">*</span>
                        <input type="number" name="nilai" min="0" max="100" value="{{ old('nilai', $tugas->nilai) }}" placeholder="Contoh: 90" required>
                        @error('nilai')<span class="pesan-salah">{{ $message }}</span>@enderror
                    </label>
                    <label>Catatan / Feedback Penilaian (Opsional)
                        <textarea name="catatan_nilai" rows="2" placeholder="Tuliskan apresiasi, masukan, atau evaluasi untuk peserta...">{{ old('catatan_nilai', $tugas->catatan_nilai) }}</textarea>
                        @error('catatan_nilai')<span class="pesan-salah">{{ $message }}</span>@enderror
                    </label>
                </div>
                <div class="aksi" style="margin-top: 16px; display: flex; gap: 8px;">
                    <button class="btn btn-kuning" type="submit">
                        <x-ikon nama="centang-saja" /> {{ $tugas->nilai !== null ? 'Simpan Perubahan Nilai' : 'Simpan Nilai Akhir' }}
                    </button>
                    @if ($tugas->nilai !== null)
                        <button type="button" class="btn btn-garis" onclick="toggleFormUbahNilai()">Batal</button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if ($tugas->nilai !== null)
        <script>
            function toggleFormUbahNilai() {
                const formBox = document.getElementById('form-penilaian-akhir');
                const viewBox = document.getElementById('kotak-tampilan-nilai');
                const btn = document.getElementById('btn-toggle-ubah-nilai');
                if (formBox.style.display === 'none' || formBox.style.display === '') {
                    formBox.style.display = 'block';
                    if (viewBox) viewBox.style.display = 'none';
                    if (btn) btn.style.display = 'none';
                } else {
                    formBox.style.display = 'none';
                    if (viewBox) viewBox.style.display = 'block';
                    if (btn) btn.style.display = 'inline-flex';
                }
            }
        </script>
    @endif
@endif
@endsection
