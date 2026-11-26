<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Pendaftaran - {{ $pengajuan->kode_pengajuan }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1f2937;
            background: #f3f4f6;
            margin: 0;
            padding: 24px;
            line-height: 1.5;
        }
        .toolbar {
            max-width: 800px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid #d1d5db;
            background: #fff;
            color: #111827;
        }
        .btn-utama {
            background: #14532d;
            border-color: #14532d;
            color: #fff;
        }
        .lembar {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 40px 44px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
        .kop {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px solid #14532d;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .kop-kiri h1 {
            margin: 0;
            font-size: 18px;
            color: #14532d;
            letter-spacing: 0.3px;
        }
        .kop-kiri p {
            margin: 2px 0 0;
            font-size: 12.5px;
            color: #4b5563;
        }
        .kop-kanan {
            text-align: right;
        }
        .kode-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #6b7280;
            letter-spacing: 0.8px;
        }
        .kode-nilai {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            font-family: monospace;
        }
        .judul-dokumen {
            text-align: center;
            margin: 0 0 24px;
        }
        .judul-dokumen h2 {
            margin: 0;
            font-size: 16.5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .judul-dokumen span {
            font-size: 13px;
            color: #4b5563;
        }
        .kotak-info {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 16px 20px;
            margin-bottom: 20px;
            background: #fafafa;
        }
        .kotak-info h3 {
            margin: 0 0 12px;
            font-size: 13.5px;
            text-transform: uppercase;
            color: #14532d;
            letter-spacing: 0.4px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 6px;
        }
        table.meta {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }
        table.meta td {
            padding: 5px 4px;
            vertical-align: top;
        }
        table.meta td.label {
            width: 210px;
            color: #4b5563;
        }
        table.meta td.titik {
            width: 14px;
        }
        table.meta td.isi {
            font-weight: 600;
            color: #111827;
        }
        table.dok {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 8px;
        }
        table.dok th, table.dok td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            text-align: left;
        }
        table.dok th {
            background: #f3f4f6;
            font-weight: 600;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            background: #e5e7eb;
            color: #111827;
        }
        .badge-sukses { background: #dcfce7; color: #166534; }
        .badge-peringatan { background: #fef9c3; color: #854d0e; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .kaki {
            margin-top: 28px;
            padding-top: 14px;
            border-top: 1px dashed #d1d5db;
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #6b7280;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .toolbar {
                display: none !important;
            }
            .lembar {
                box-shadow: none;
                padding: 16px 20px;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
@php
    $calon = $pengajuan->calonPeserta->first();
    $badgeKelas = match ($pengajuan->status_pengajuan) {
        'Lengkap', 'Selesai Diputuskan' => 'badge-sukses',
        'Perlu Perbaikan' => 'badge-peringatan',
        'Diajukan' => 'badge-info',
        default => '',
    };
@endphp

<div class="toolbar">
    <a href="{{ route('peserta.pendaftaran.index') }}" class="btn">← Kembali ke Status Pendaftaran</a>
    <button type="button" onclick="window.print()" class="btn btn-utama">Cetak / Simpan PDF</button>
</div>

<div class="lembar">
    <div class="kop">
        <div class="kop-kiri">
            <h1>DIREKTORAT TEKNOLOGI INFORMASI</h1>
            <p>Universitas Andalas · Sistem Pengelolaan Magang DTI</p>
        </div>
        <div class="kop-kanan">
            <div class="kode-label">Kode Pengajuan</div>
            <div class="kode-nilai">{{ $pengajuan->kode_pengajuan }}</div>
        </div>
    </div>

    <div class="judul-dokumen">
        <h2>Bukti Pendaftaran Rekrutmen Magang</h2>
        <span>{{ $pengajuan->program?->nama_program ?? 'Program Magang DTI' }}</span>
    </div>

    <div class="kotak-info">
        <h3>Informasi Pendaftaran</h3>
        <table class="meta">
            <tr>
                <td class="label">Kode Pengajuan</td>
                <td class="titik">:</td>
                <td class="isi">{{ $pengajuan->kode_pengajuan }}</td>
            </tr>
            <tr>
                <td class="label">Program Magang</td>
                <td class="titik">:</td>
                <td class="isi">{{ $pengajuan->program?->nama_program ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Tanggal Pengajuan</td>
                <td class="titik">:</td>
                <td class="isi">{{ $pengajuan->tanggal_pengajuan?->translatedFormat('d F Y, H:i') ?? '-' }} WIB</td>
            </tr>
            <tr>
                <td class="label">Status Pendaftaran</td>
                <td class="titik">:</td>
                <td class="isi"><span class="badge {{ $badgeKelas }}">{{ $pengajuan->status_pengajuan }}</span></td>
            </tr>
        </table>
    </div>

    <div class="kotak-info">
        <h3>Data Calon Peserta</h3>
        <table class="meta">
            <tr>
                <td class="label">Nama Lengkap</td>
                <td class="titik">:</td>
                <td class="isi">{{ $calon?->nama_lengkap ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">NIM / No. Induk Pendidikan</td>
                <td class="titik">:</td>
                <td class="isi">{{ $calon?->nomor_induk_pendidikan ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Alamat Email</td>
                <td class="titik">:</td>
                <td class="isi">{{ $calon?->email ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Nomor HP / WhatsApp</td>
                <td class="titik">:</td>
                <td class="isi">{{ $calon?->no_hp ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Institusi Asal</td>
                <td class="titik">:</td>
                <td class="isi">{{ $calon?->institusi_asal ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Jurusan / Program Studi</td>
                <td class="titik">:</td>
                <td class="isi">{{ $calon?->jurusan_prodi ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="kotak-info">
        <h3>Daftar Dokumen Persyaratan</h3>
        <table class="dok">
            <thead>
                <tr>
                    <th style="width: 36px;">No</th>
                    <th>Nama Dokumen</th>
                    <th>Nama Berkas Diunggah</th>
                    <th>Status Pemeriksaan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pengajuan->dokumenPengajuan as $idx => $dok)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>{{ $dok->jenis_dokumen }}</td>
                        <td>{{ $dok->nama_berkas }}</td>
                        <td>{{ $dok->hasil_pemeriksaan ?? 'Menunggu Verifikasi' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: #6b7280;">Tidak ada dokumen yang diunggah.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="kaki">
        <span>Dicetak pada: {{ now()->translatedFormat('d F Y, H:i') }} WIB</span>
        <span>Dokumen ini dihasilkan secara otomatis oleh Sistem Pengelolaan Magang DTI.</span>
    </div>
</div>
</body>
</html>
