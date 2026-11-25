@extends('layouts.app')
@section('judul', 'Informasi Penempatan')
@section('konten')

{{-- ===== KEPALA HALAMAN ===== --}}
<div class="kepala-halaman">
    <div>
        <p class="kicker">Peserta Magang</p>
        <h1>Informasi Penempatan</h1>
        <p class="sub-judul">
            Lihat seksi dan mentor pembimbing yang telah ditetapkan untuk magang Anda.
        </p>
    </div>
</div>

@if ($daftarPeserta->isEmpty())
    {{-- ===== EMPTY STATE: Belum ada data peserta_magang ===== --}}
    <div class="kartu">
        <div class="empty-state" style="padding: 48px 24px">
            <x-ikon nama="orang" />
            <p>
                Data magang Anda belum tersedia.
                <small class="redup" style="display:block;margin-top:4px">
                    Pastikan Superadmin sudah mengaktifkan akun peserta Anda setelah keputusan penerimaan.
                </small>
            </p>
        </div>
    </div>

@else
    @foreach ($daftarPeserta as $pm)
        @php
            $calon   = $pm->keputusan?->calon;
            $pengaj  = $calon?->pengajuan;
            $sudahDitempatkan = $pm->id_seksi !== null && $pm->id_mentor !== null;
            $adaSeksi  = $pm->id_seksi !== null;
            $adaMentor = $pm->id_mentor !== null;

            $kelasStatus = match ($pm->status_magang) {
                'Belum Mulai' => 'status-netral',
                'Aktif'       => 'status-sukses',
                'Selesai'     => 'status-sukses',
                'Dihentikan'  => 'status-gagal',
                default       => 'status-netral',
            };
        @endphp

        <div class="kartu kartu-penempatan" style="margin-bottom: 20px">

            {{-- ── Kepala kartu: identitas & status ── --}}
            <div class="kartu-kepala">
                <div class="kartu-judul">
                    <span class="inisial v{{ abs(crc32($calon?->nama_lengkap ?? '')) % 5 }} inisial-besar">
                        {{ mb_strtoupper(mb_substr($calon?->nama_lengkap ?? '?', 0, 1)) }}
                    </span>
                    <div>
                        <h2 style="margin:0;font-size:17px">
                            {{ $calon?->nama_lengkap ?? auth()->user()?->nama_lengkap ?? '—' }}
                        </h2>
                        @if ($calon?->nomor_induk_pendidikan)
                            <small class="redup">{{ $calon->nomor_induk_pendidikan }}</small>
                        @endif
                    </div>
                </div>
                <span class="status {{ $kelasStatus }}">{{ $pm->status_magang }}</span>
            </div>

            <div class="kartu-isi">

                {{-- ── Info identitas & periode ── --}}
                <div class="grid-info" style="margin-bottom: 20px">
                    @if ($calon?->institusi_asal || $pengaj?->nama_institusi)
                        <div class="info-item">
                            <span class="info-label"><x-ikon nama="berkas" /> Institusi</span>
                            <span class="info-nilai">
                                {{ $calon?->institusi_asal ?? $pengaj?->nama_institusi ?? '—' }}
                                @if ($calon?->jurusan_prodi)
                                    <small class="redup" style="display:block">{{ $calon->jurusan_prodi }}</small>
                                @endif
                            </span>
                        </div>
                    @endif

                    @if ($pengaj?->jalur)
                        <div class="info-item">
                            <span class="info-label"><x-ikon nama="tugas" /> Jalur</span>
                            <span class="info-nilai">
                                <span class="status {{ $pengaj->jalur === 'INSTITUSI' ? 'status-info' : 'status-netral' }}">
                                    {{ $pengaj->jalur }}
                                </span>
                            </span>
                        </div>
                    @endif

                    <div class="info-item">
                        <span class="info-label"><x-ikon nama="papan-centang" /> Periode Magang</span>
                        <span class="info-nilai">
                            {{ $pm->tanggal_mulai?->format('d M Y') ?? '—' }}
                            <span class="redup">&ndash;</span>
                            {{ $pm->tanggal_selesai?->format('d M Y') ?? '—' }}
                        </span>
                    </div>

                    @if ($pengaj?->kode_pengajuan)
                        <div class="info-item">
                            <span class="info-label"><x-ikon nama="kode" /> Kode Pengajuan</span>
                            <span class="info-nilai redup">{{ $pengaj->kode_pengajuan }}</span>
                        </div>
                    @endif
                </div>

                {{-- ── Pemisah ── --}}
                <hr class="pemisah">

                {{-- ── Status penempatan ── --}}
                <h3 class="subjudul-seksi">
                    <x-ikon nama="tambah-orang" /> Penempatan Seksi &amp; Mentor
                </h3>

                @if ($sudahDitempatkan)
                    {{-- ✅ Sudah ditempatkan sepenuhnya --}}
                    <div class="grid-penempatan">

                        {{-- Seksi --}}
                        <div class="kartu-info-penempatan kartu-penempatan-sukses">
                            <div class="kartu-info-ikon">
                                <x-ikon nama="perisai" />
                            </div>
                            <div class="kartu-info-isi">
                                <div class="kartu-info-judul">Seksi Penempatan</div>
                                <div class="kartu-info-nilai">
                                    {{ $pm->seksi?->nama_seksi ?? '—' }}
                                </div>
                                @if ($pm->seksi?->deskripsi)
                                    <div class="kartu-info-sub redup">
                                        {{ $pm->seksi->deskripsi }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Mentor --}}
                        <div class="kartu-info-penempatan kartu-penempatan-sukses">
                            <div class="kartu-info-ikon">
                                <x-ikon nama="orang" />
                            </div>
                            <div class="kartu-info-isi">
                                <div class="kartu-info-judul">Mentor Pembimbing</div>
                                <div class="kartu-info-nilai">
                                    {{ $pm->mentor?->pengguna?->nama_lengkap ?? '—' }}
                                </div>
                                @if ($pm->mentor?->jabatan)
                                    <div class="kartu-info-sub redup">
                                        {{ $pm->mentor->jabatan }}
                                    </div>
                                @endif
                                @if ($pm->mentor?->pengguna?->email)
                                    <div class="kartu-info-sub redup" style="font-size:11.5px">
                                        {{ $pm->mentor->pengguna->email }}
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>

                    {{-- Ditetapkan oleh & tanggal --}}
                    @if ($pm->tanggal_penempatan || $pm->ditetapkanOleh?->pengguna)
                        <div class="ditetapkan-info">
                            <x-ikon nama="centang-lingkaran" />
                            <span>
                                Ditetapkan
                                @if ($pm->tanggal_penempatan)
                                    pada <b>{{ $pm->tanggal_penempatan->format('d M Y') }}</b>
                                @endif
                                @if ($pm->ditetapkanOleh?->pengguna)
                                    oleh <b>{{ $pm->ditetapkanOleh->pengguna->nama_lengkap }}</b>
                                @endif
                            </span>
                        </div>
                    @endif

                @elseif ($adaSeksi && ! $adaMentor)
                    {{-- ⚠️ Ada seksi, belum ada mentor --}}
                    <div class="grid-penempatan">
                        <div class="kartu-info-penempatan kartu-penempatan-sukses">
                            <div class="kartu-info-ikon"><x-ikon nama="perisai" /></div>
                            <div class="kartu-info-isi">
                                <div class="kartu-info-judul">Seksi Penempatan</div>
                                <div class="kartu-info-nilai">{{ $pm->seksi?->nama_seksi ?? '—' }}</div>
                                @if ($pm->seksi?->deskripsi)
                                    <div class="kartu-info-sub redup">{{ $pm->seksi->deskripsi }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="kartu-info-penempatan kartu-penempatan-menunggu">
                            <div class="kartu-info-ikon"><x-ikon nama="orang" /></div>
                            <div class="kartu-info-isi">
                                <div class="kartu-info-judul">Mentor Pembimbing</div>
                                <div class="kartu-info-nilai redup">Belum ditetapkan</div>
                                <div class="kartu-info-sub redup">
                                    Kepala Seksi akan segera menetapkan mentor untuk Anda.
                                </div>
                            </div>
                        </div>
                    </div>

                @else
                    {{-- ⏳ Belum ada penempatan sama sekali --}}
                    <div class="banner-menunggu">
                        <span class="ikon-bulat"><x-ikon nama="tugas" /></span>
                        <div>
                            <div style="font-weight:600;font-size:14px;margin-bottom:2px">
                                Menunggu Penempatan
                            </div>
                            <div style="font-size:13px;color:var(--redup)">
                                Kepala Seksi akan menetapkan seksi dan mentor pembimbing Anda.
                                Pastikan status magang Anda sudah <b>Belum Mulai</b>.
                            </div>
                        </div>
                    </div>
                @endif

            </div>{{-- /kartu-isi --}}
        </div>{{-- /kartu --}}

    @endforeach
@endif

{{-- ===== CSS TAMBAHAN ===== --}}
<style>
/* Kartu identitas */
.kartu-judul {
    display: flex;
    align-items: center;
    gap: 14px;
}
.inisial-besar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 700;
    flex-shrink: 0;
}

/* Grid info identitas */
.grid-info {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 14px 24px;
}
.info-item {
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.info-label {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 600;
    color: var(--redup, #6b7280);
    text-transform: uppercase;
    letter-spacing: .4px;
}
.info-nilai {
    font-size: 14px;
    font-weight: 500;
    color: var(--teks, #111);
}

/* Pemisah */
.pemisah {
    border: none;
    border-top: 1px solid var(--batas, #e5e7eb);
    margin: 0 0 18px;
}

/* Subjudul seksi */
.subjudul-seksi {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 14px;
    font-weight: 700;
    color: var(--teks, #111);
    margin: 0 0 14px;
}

/* Grid penempatan */
.grid-penempatan {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 14px;
}
@media (max-width: 640px) {
    .grid-penempatan { grid-template-columns: 1fr; }
}

/* Kartu info penempatan */
.kartu-info-penempatan {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px 18px;
    border-radius: 10px;
    border: 1.5px solid;
}
.kartu-penempatan-sukses {
    border-color: rgba(22,101,52,.2);
    background: rgba(22,101,52,.04);
}
.kartu-penempatan-menunggu {
    border-color: rgba(234,179,8,.3);
    background: rgba(234,179,8,.05);
}
.kartu-info-ikon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(22,101,52,.1);
    color: var(--utama, #166534);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.kartu-penempatan-menunggu .kartu-info-ikon {
    background: rgba(234,179,8,.15);
    color: #92400e;
}
.kartu-info-isi { flex: 1; min-width: 0; }
.kartu-info-judul {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--redup, #6b7280);
    text-transform: uppercase;
    letter-spacing: .4px;
    margin-bottom: 4px;
}
.kartu-info-nilai {
    font-size: 15px;
    font-weight: 700;
    color: var(--teks, #111);
    word-break: break-word;
}
.kartu-info-sub {
    font-size: 12px;
    margin-top: 2px;
}

/* Ditetapkan info */
.ditetapkan-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
    color: var(--redup, #6b7280);
    padding: 8px 12px;
    background: rgba(22,101,52,.05);
    border-radius: 6px;
}

/* Banner menunggu */
.banner-menunggu {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px 18px;
    background: rgba(234,179,8,.06);
    border: 1.5px solid rgba(234,179,8,.25);
    border-radius: 10px;
}

/* Status info (jalur INSTITUSI) */
.status-info { background: rgba(37,99,235,.1); color: #1d4ed8; }
</style>

@endsection
