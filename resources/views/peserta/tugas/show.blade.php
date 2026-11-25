@extends('layouts.app')
@section('judul', 'Detail Tugas')
@section('konten')
<div class="kepala-halaman">
    <div>
        <a class="tautan-kembali" href="{{ route('peserta.tugas.index') }}"><x-ikon nama="kembali" /> Kembali ke daftar tugas</a>
        <h1>{{ $tugas->judul }}</h1>
        <p class="sub-judul">Instruksi penugasan mentor dan pengiriman progres pekerjaan Anda.</p>
    </div>
</div>

@php
    $mentor = $tugas->pesertaMagang?->mentor;
    $namaMentor = $mentor?->nama_lengkap ?? 'Mentor Pembimbing';
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
                <h2>Informasi Penugasan</h2>
                <p>Mentor: <b>{{ $namaMentor }}</b></p>
            </div>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
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
            <span class="redup" style="font-size: 13px; display: block;">Tanggal Ditugaskan:</span>
            <b>{{ $tugas->created_at?->format('d/m/Y H:i') ?? '-' }}</b>
        </div>
    </div>

    <div>
        <h3 style="margin-bottom: 6px;">Deskripsi / Petunjuk Pengerjaan:</h3>
        <p style="white-space: pre-line; color: var(--teks);">{{ $tugas->deskripsi }}</p>
    </div>

    @if ($tugas->path_dokumen)
        <div style="margin-top: 14px; padding: 12px 16px; background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: var(--radius-kecil); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="ikon-bulat" style="background: #2563eb; color: #fff; width: 36px; height: 36px;"><x-ikon nama="berkas" /></span>
                <div>
                    <b style="font-size: 13.5px; display: block; color: var(--teks);">Dokumen Instruksi / Modul Pengerjaan dari Mentor</b>
                    <small class="redup">{{ basename($tugas->path_dokumen) }} · Unduh sebagai panduan pengerjaan tugas</small>
                </div>
            </div>
            <a href="{{ route('tugas.dokumen', $tugas) }}" class="btn btn-garis btn-kecil">
                <x-ikon nama="berkas" /> Unduh Dokumen Instruksi
            </a>
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
                        @elseif ($progresNo)
                            <span class="status status-peringatan" style="font-size: 12px;">Dalam Proses</span>
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
                <h2>Riwayat Progres & Hasil Pemeriksaan</h2>
                <p>Perkembangan tahapan yang telah Anda serahkan beserta evaluasi dari mentor.</p>
            </div>
        </div>
    </div>

    @if ($tugas->progresTugas->isEmpty())
        <div class="kosong">
            <div class="ilustrasi"><x-ikon nama="jam" /></div>
            <b>Belum Ada Progres Dikirim</b>
            Silakan kerjakan dan kirimkan progres pertama Anda menggunakan formulir di bawah.
        </div>
    @else
        <ul class="langkah">
            @foreach ($tugas->progresTugas as $p)
                @php
                    $isTerakhir = ($p->id_progres === $tugas->progresTerakhir()?->id_progres);
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
                                <span class="status status-peringatan">Sedang Diperiksa Mentor</span>
                            @endif
                        </div>
                    </div>

                    <div style="margin-top: 10px; background: var(--permukaan-2); border: 1px solid var(--garis); padding: 12px 16px; border-radius: var(--radius-kecil);">
                        <p style="white-space: pre-line; margin-bottom: 10px;">{{ $p->catatan_progres }}</p>

                        <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; font-size: 13.5px; padding-top: 10px; border-top: 1px dashed var(--garis);">
                            @if ($p->path_berkas)
                                <a href="{{ route('tugas.progres.berkas', $p) }}" class="btn btn-garis btn-kecil">
                                    <x-ikon nama="berkas" /> Unduh Berkas Bukti ({{ basename($p->path_berkas) }})
                                </a>
                            @endif
                            @if ($p->tautan_hasil)
                                <a href="{{ $p->tautan_hasil }}" target="_blank" rel="noopener noreferrer" class="btn btn-garis btn-kecil">
                                    <x-ikon nama="panah-kanan" /> Tautan Hasil Pengerjaan
                                </a>
                            @endif
                        </div>

                        @if ($p->hasil_review)
                            <div style="margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--garis);">
                                <small class="redup" style="display:block; margin-bottom:4px;">
                                    Catatan Review dari Mentor ({{ $p->waktu_review?->format('d M Y, H:i') }}):
                                </small>
                                <b>{{ $p->catatan_review ?: 'Hasil pekerjaan diterima tanpa catatan tambahan.' }}</b>
                            </div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>

@if ($bisaKirim)
    <div class="kartu">
        <div class="kartu-kepala">
            <div class="kartu-judul">
                <span class="ikon-bulat kuning"><x-ikon nama="plus" /></span>
                <div>
                    <h2>Kirim Progres ke-{{ $urutanBerikutnya }} dari {{ $tugas->jumlah_progres }}</h2>
                    <p>
                        @if ($tugas->status === 'Perlu Perbaikan')
                            <span style="color: var(--gagal); font-weight: 600;">Status tugas: Perlu Perbaikan.</span> Silakan kirimkan revisi progres Anda berikut ini.
                        @else
                            Kirimkan catatan kemajuan dan lampiran hasil pekerjaan untuk tahapan ini.
                        @endif
                    </p>
                    @php $judulTahap = $tugas->judulProgresKe($urutanBerikutnya); @endphp
                    @if (!str_starts_with($judulTahap, 'Progres ke-'))
                        <p style="margin-top: 6px; font-weight: 600; color: var(--aksen);">🎯 Target tahapan ini: {{ $judulTahap }}</p>
                    @endif
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('peserta.tugas.progres', $tugas) }}" enctype="multipart/form-data">
            @csrf

            <label>Catatan Progres
                <textarea name="catatan_progres" rows="4" placeholder="Jelaskan progres yang telah diselesaikan, poin perbaikan, atau catatan teknis..." required>{{ old('catatan_progres') }}</textarea>
                <small class="redup">Wajib diisi dengan uraian kemajuan pengerjaan tugas.</small>
                @error('catatan_progres')<span class="pesan-salah">{{ $message }}</span>@enderror
            </label>

            <label>Tautan Hasil (URL)
                <input type="url" name="tautan_hasil" value="{{ old('tautan_hasil') }}" placeholder="https://github.com/... atau https://drive.google.com/..." required>
                <small class="redup">Wajib diisi dengan tautan repositori, live demo, atau dokumen hasil pengerjaan.</small>
                @error('tautan_hasil')<span class="pesan-salah">{{ $message }}</span>@enderror
            </label>

            <label>Berkas Hasil Tugas
                <input type="file" name="berkas" required>
                <small class="redup">Wajib diunggah. Format yang didukung: PDF, ZIP, RAR, DOC, DOCX, PNG, JPG, JPEG (Maks. 10 MB).</small>
                @error('berkas')<span class="pesan-salah">{{ $message }}</span>@enderror
            </label>

            <div class="aksi" style="margin-top: 18px;">
                <button class="btn" type="submit"><x-ikon nama="centang-saja" /> Kirim Progres</button>
            </div>
        </form>
    </div>
@elseif ($tugas->status === 'Diserahkan')
    <div class="alert alert-info" style="margin-bottom: 20px;">
        <x-ikon nama="info" />
        <div>
            <b>Progres sedang ditinjau mentor.</b>
            Progres terakhir Anda telah diserahkan dan saat ini sedang menunggu hasil review dari mentor pembimbing sebelum Anda dapat mengirimkan progres berikutnya.
        </div>
    </div>
@elseif ($tugas->status === 'Selesai')
    <div class="kartu" style="border-left: 4px solid var(--sukses); margin-bottom: 20px;">
        <div class="kartu-kepala">
            <div class="kartu-judul">
                <span class="ikon-bulat hijau"><x-ikon nama="medali" /></span>
                <div>
                    <h2>Tugas Ini Telah Selesai!</h2>
                    <p>Seluruh target tahapan progres telah diselesaikan dan diterima dengan baik oleh mentor.</p>
                </div>
            </div>
            @if ($tugas->nilai !== null)
                <div>
                    <span class="chip" style="font-size: 16px; font-weight: 700; background: rgba(34, 197, 94, 0.15); color: var(--sukses); padding: 6px 14px; border-radius: 999px;">
                        Nilai: {{ $tugas->nilai }} / 100
                    </span>
                </div>
            @endif
        </div>
        @if ($tugas->catatan_nilai)
            <div style="margin-top: 12px; background: var(--permukaan-2); padding: 12px 16px; border-radius: var(--radius-kecil); border: 1px solid var(--garis);">
                <small class="redup" style="display: block; margin-bottom: 4px;">Catatan Penilaian Mentor:</small>
                <b style="color: var(--teks);">{{ $tugas->catatan_nilai }}</b>
            </div>
        @endif
    </div>
@endif
@endsection
