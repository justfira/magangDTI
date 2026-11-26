@use('App\Services\AlurPenerimaanService', 'Alur')
@extends('layouts.app')

@section('judul', 'Status Pendaftaran')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/penerimaan.css') }}">
@endpush

@section('konten')
<div class="kepala-halaman">
    <div>
        <p class="kicker">Pendaftaran</p>
        <h1>Status Pendaftaran Rekrutmen</h1>
        <p class="sub-judul">Pantau pemeriksaan berkas, alur persetujuan, dan hasil penerimaan magang Anda di Direktorat Teknologi Informasi.</p>
    </div>
</div>

@if ($daftarPengajuan->isEmpty())
    <div class="kartu" style="text-align: center; padding: 48px 24px;">
        <span class="ikon-bulat" style="width: 56px; height: 56px; margin: 0 auto 16px;">
            <x-ikon nama="berkas" />
        </span>
        <h2>{{ Alur::PESAN_KOSONG }}</h2>
        <p class="redup" style="max-width: 460px; margin: 8px auto 20px;">
            Anda belum memiliki pengajuan pendaftaran rekrutmen magang. Silakan lihat program magang yang sedang dibuka untuk mendaftar.
        </p>
        <a class="btn btn-kuning" href="{{ route('informasi-program') }}">
            Lihat Program Magang <x-ikon nama="panah-kanan" />
        </a>
    </div>
@else
    @foreach ($daftarPengajuan as $pengajuan)
        @php
            $calon = $pengajuan->calonPeserta->first();
            $keputusan = $calon?->keputusanPenerimaan;
            $tidakSesuai = $pengajuan->dokumenPengajuan->where('hasil_pemeriksaan', 'Tidak Sesuai');
            $statusClass = Alur::kelasStatus($pengajuan->status_pengajuan);
        @endphp

        <div class="kartu" style="margin-bottom: 24px;">
            <div class="kartu-kepala">
                <div class="kartu-judul">
                    <span class="ikon-bulat"><x-ikon nama="koper" /></span>
                    <div>
                        <h2>{{ $pengajuan->program?->nama_program ?? 'Program Rekrutmen DTI' }}</h2>
                        <p>Kode Pengajuan: <b>{{ $pengajuan->kode_pengajuan }}</b> · Diajukan pada {{ $pengajuan->tanggal_pengajuan?->translatedFormat('d F Y, H:i') ?? '-' }} WIB</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <span class="status {{ $statusClass }}">{{ $pengajuan->status_pengajuan }}</span>
                    <a href="{{ route('peserta.pendaftaran.cetak', $pengajuan) }}" target="_blank" class="btn btn-garis btn-kecil" title="Cetak atau simpan bukti pendaftaran sebagai PDF">
                        <x-ikon nama="berkas" /> Cetak Bukti Pendaftaran
                    </a>
                </div>
            </div>

            {{-- Status Keputusan / Hasil Verifikasi (Vania + Shafa) --}}
            @if ($keputusan?->keputusan === 'Diterima')
                <div class="pn-hasil diterima" style="margin: 16px 0 20px;">
                    <x-ikon nama="medali" />
                    <div>
                        <b>Selamat, Anda diterima!</b>
                        <p>Informasi penempatan seksi dan mentor akan muncul setelah ditetapkan Kepala Seksi.</p>
                    </div>
                </div>
            @elseif ($keputusan?->keputusan === 'Ditolak')
                <div class="pn-hasil ditolak" style="margin: 16px 0 20px;">
                    <x-ikon nama="info" />
                    <div>
                        <b>Mohon maaf, pengajuan Anda belum dapat diterima.</b>
                        @if ($keputusan->catatan_keputusan)
                            <p>{{ $keputusan->catatan_keputusan }}</p>
                        @endif
                    </div>
                </div>
            @elseif ($pengajuan->status_pengajuan === 'Perlu Perbaikan')
                <div class="alert alert-gagal" style="margin: 16px 0 20px;">
                    <x-ikon nama="peringatan" />
                    <div style="flex: 1;">
                        <b style="font-size: 15px;">Catatan Perbaikan dari Superadmin:</b>
                        <p style="margin: 6px 0 8px; white-space: pre-line;">{{ $pengajuan->catatan_perbaikan }}</p>
                        @if ($tidakSesuai->isNotEmpty())
                            <ul style="margin: 0 0 12px 18px; padding: 0;">
                                @foreach ($tidakSesuai as $d)
                                    <li><b>{{ $d->syarat?->nama_syarat ?? Alur::labelJenisDokumen($d->jenis_dokumen) }}</b>{{ $d->catatan_pemeriksaan ? ': ' . $d->catatan_pemeriksaan : '' }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <a href="{{ route('peserta.pendaftaran.edit', $pengajuan) }}" class="btn btn-kuning btn-kecil">
                            <x-ikon nama="ubah" /> Perbaiki Pengajuan
                        </a>
                    </div>
                </div>
            @else
                <div class="pn-hasil proses" style="margin: 16px 0 20px;">
                    <x-ikon nama="jam" />
                    <div>
                        <b>Pengajuan sedang diproses</b>
                        <p>Kami akan memberi tahu Anda lewat notifikasi setiap ada perubahan status.</p>
                    </div>
                </div>
            @endif

            {{-- Alur Persetujuan (Modul A/F Vania) --}}
            <div style="padding: 16px; border: 1px solid var(--garis); border-radius: 12px; margin-bottom: 20px;">
                <h3 style="margin: 0 0 12px; font-size: 14.5px; display: flex; align-items: center; gap: 8px;">
                    <x-ikon nama="jam" /> Alur Persetujuan
                </h3>
                @include('penerimaan._alur', ['langkah' => Alur::langkahAlur($pengajuan)])
            </div>

            {{-- Ringkasan Data Diri --}}
            <div style="padding: 16px; background: var(--permukaan-2); border-radius: 12px; margin-bottom: 20px;">
                <h3 style="margin: 0 0 12px; font-size: 14.5px; display: flex; align-items: center; gap: 8px;">
                    <x-ikon nama="pengguna" /> Data Calon Peserta
                </h3>
                <div class="dua-kolom" style="gap: 8px 24px; font-size: 13.5px;">
                    <div><span class="redup">Nama Lengkap:</span> <b>{{ $calon?->nama_lengkap ?? '-' }}</b></div>
                    <div><span class="redup">Email:</span> <b>{{ $calon?->email ?? '-' }}</b></div>
                    <div><span class="redup">NIM / No. Induk:</span> <b>{{ $calon?->nomor_induk_pendidikan ?? '-' }}</b></div>
                    <div><span class="redup">Nomor HP / WA:</span> <b>{{ $calon?->no_hp ?? '-' }}</b></div>
                    <div><span class="redup">Institusi Asal:</span> <b>{{ $calon?->institusi_asal ?? '-' }}</b></div>
                    <div><span class="redup">Program Studi:</span> <b>{{ $calon?->jurusan_prodi ?? '-' }}</b></div>
                </div>
            </div>

            {{-- Tabel Dokumen Pengajuan --}}
            <h3 style="margin: 18px 0 10px; font-size: 14.5px; display: flex; align-items: center; gap: 8px;">
                <x-ikon nama="berkas" /> Dokumen yang Diunggah
            </h3>
            <div class="tabel-wadah">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Syarat</th>
                            <th>Berkas</th>
                            <th>Ukuran</th>
                            <th>Waktu Unggah</th>
                            <th>Hasil Pemeriksaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pengajuan->dokumenPengajuan as $idx => $dok)
                            @php
                                $hasilClass = match ($dok->hasil_pemeriksaan) {
                                    'Sesuai' => 'status-sukses',
                                    'Tidak Sesuai' => 'status-gagal',
                                    default => 'status-netral',
                                };
                            @endphp
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td><b>{{ $dok->jenis_dokumen }}</b></td>
                                <td>
                                    <a class="tautan" href="{{ route('pengajuan.dokumen.unduh', $dok) }}" title="Unduh berkas">
                                        <x-ikon nama="berkas" /> {{ $dok->nama_berkas }}
                                    </a>
                                </td>
                                <td>{{ $dok->ukuran_bytes ? number_format($dok->ukuran_bytes / 1024, 0, ',', '.') . ' KB' : '-' }}</td>
                                <td>{{ $dok->uploaded_at?->translatedFormat('d M Y, H:i') ?? '-' }}</td>
                                <td>
                                    <span class="status {{ $hasilClass }}">{{ $dok->hasil_pemeriksaan ?? 'Menunggu Verifikasi' }}</span>
                                    @if ($dok->catatan_pemeriksaan)
                                        <p class="redup" style="margin: 4px 0 0; font-size: 12px; color: var(--gagal);">
                                            Catatan: {{ $dok->catatan_pemeriksaan }}
                                        </p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center;" class="redup">Belum ada dokumen yang diunggah.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
@endif
@endsection
