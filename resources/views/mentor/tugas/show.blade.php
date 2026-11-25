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
            <a href="{{ route('mentor.tugas.edit', $tugas) }}" class="btn btn-garis btn-kecil">
                <x-ikon nama="ubah" /> Ubah Tugas &amp; Tenggat
            </a>
            <span class="chip">Progres ke-{{ $urutanAktif }} dari {{ $tugas->jumlah_progres }}</span>
            <span class="status {{ $kelasStatus }}">{{ $tugas->status }}</span>
        </div>
    </div>

    <div style="margin-top: 10px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; background: var(--permukaan-2); padding: 14px 18px; border-radius: var(--radius-kecil); margin-bottom: 16px;">
        <div>
            <span class="redup" style="font-size: 13px; display: block;">Tenggat Waktu:</span>
            <b><x-ikon nama="kalender" /> {{ $tugas->tenggat?->format('d F Y') }}</b>
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
                            <small class="redup"><x-ikon nama="kalender" /> Dikirim: {{ $p->waktu_kirim?->format('d M Y, H:i') }}</small>
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

                        <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; font-size: 13.5px;">
                            @if ($p->tautan_hasil)
                                <a href="{{ $p->tautan_hasil }}" target="_blank" rel="noopener noreferrer" class="btn btn-garis btn-kecil">
                                    <x-ikon nama="panah-kanan" /> Buka Tautan Hasil
                                </a>
                            @endif
                            @if ($p->path_berkas)
                                <a href="{{ route('tugas.progres.berkas', $p) }}" class="btn btn-garis btn-kecil">
                                    <x-ikon nama="berkas" /> Unduh Berkas
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
    <div class="kartu">
        <div class="kartu-kepala">
            <div class="kartu-judul">
                <span class="ikon-bulat kuning"><x-ikon nama="papan-centang" /></span>
                <div>
                    <h2>Pemeriksaan Progres Terakhir (Progres ke-{{ $progresTerakhir->urutan }})</h2>
                    <p>Tentukan keputusan hasil pemeriksaan dan berikan umpan balik atau catatan revisi.</p>
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
@endsection
