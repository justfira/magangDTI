@extends('layouts.app')
@section('judul', 'Tugas Peserta Bimbingan')
@section('konten')
<div class="kepala-halaman">
    <div>
        <h1>Kelola Penugasan Peserta</h1>
        <p class="sub-judul">Daftar proyek dan penugasan kerja untuk mahasiswa bimbingan Anda di Direktorat Teknologi Informasi.</p>
    </div>
    <div class="aksi-kepala">
        <a class="btn btn-kuning" href="{{ route('mentor.tugas.create') }}">
            <x-ikon nama="plus" /> Buat Tugas Baru
        </a>
    </div>
</div>

{{-- Kartu Ringkasan Statistik --}}
<div class="grid-kartu" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="kartu" style="margin: 0; padding: 18px 20px;">
        <span class="redup" style="font-size: 13px;">Total Seluruh Penugasan</span>
        <h2 style="font-size: 28px; margin: 4px 0 0;">{{ $totalTugas }}</h2>
    </div>
    <div class="kartu" style="margin: 0; padding: 18px 20px; border-left: 4px solid var(--peringatan);">
        <span class="redup" style="font-size: 13px;">Menunggu Review (Diserahkan)</span>
        <h2 style="font-size: 28px; margin: 4px 0 0; color: #b78103;">{{ $tugasDiserahkan }}</h2>
    </div>
    <div class="kartu" style="margin: 0; padding: 18px 20px; border-left: 4px solid var(--gagal);">
        <span class="redup" style="font-size: 13px;">Perlu Perbaikan</span>
        <h2 style="font-size: 28px; margin: 4px 0 0; color: var(--gagal);">{{ $tugasPerluPerbaikan }}</h2>
    </div>
    <div class="kartu" style="margin: 0; padding: 18px 20px; border-left: 4px solid var(--sukses);">
        <span class="redup" style="font-size: 13px;">Tugas Selesai</span>
        <h2 style="font-size: 28px; margin: 4px 0 0; color: var(--sukses);">{{ $tugasSelesai }}</h2>
    </div>
</div>

{{-- Form Pencarian & Filter Status --}}
<div class="kartu" style="padding: 16px 20px; margin-bottom: 20px;">
    <form method="GET" action="{{ route('mentor.tugas.index') }}" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 240px; position: relative;">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul proyek atau nama mahasiswa bimbingan..." style="margin: 0; padding-left: 36px;">
            <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--redup); pointer-events: none;">
                <x-ikon nama="cari" />
            </span>
        </div>
        <select name="status" style="margin: 0; width: auto; min-width: 170px;">
            <option value="">Semua Status</option>
            @foreach (\App\Models\Tugas::STATUS as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>Status: {{ $s }}</option>
            @endforeach
        </select>
        <button class="btn btn-garis btn-kecil" type="submit">
            <x-ikon nama="filter" /> Filter
        </button>
        @if (request()->hasAny(['q', 'status']))
            <a class="btn btn-halus btn-kecil" href="{{ route('mentor.tugas.index') }}">Reset</a>
        @endif
    </form>
</div>

{{-- Antarmuka Berdasarkan Judul Tugas (Dikelompokkan Terlebih Dahulu) --}}
@if ($kelompokTugas->isEmpty())
    <div class="kartu" style="text-align: center; padding: 48px 24px;">
        <div class="ilustrasi" style="margin-bottom: 12px;"><x-ikon nama="tugas" /></div>
        <h2 style="margin: 0 0 6px;">Belum Ada Tugas Diberikan</h2>
        <p class="redup" style="margin: 0 0 16px;">Belum ada penugasan atau proyek yang cocok dengan filter saat ini.</p>
        <a class="btn btn-kuning" href="{{ route('mentor.tugas.create') }}"><x-ikon nama="plus" /> Buat Tugas Baru</a>
    </div>
@else
    <div style="display: flex; flex-direction: column; gap: 16px;">
        @foreach ($kelompokTugas as $judulTugas => $kumpulanTugas)
            @php
                $contoh = $kumpulanTugas->first();
                $jumlahPeserta = $kumpulanTugas->count();
                $countDiserahkan = $kumpulanTugas->where('status', 'Diserahkan')->count();
                $countPerbaikan = $kumpulanTugas->where('status', 'Perlu Perbaikan')->count();
                $countSelesai = $kumpulanTugas->where('status', 'Selesai')->count();
                $countDitugaskan = $kumpulanTugas->where('status', 'Ditugaskan')->count();
                $groupId = 'tugas-group-' . $loop->index;
            @endphp

            <div class="kartu" style="margin: 0; padding: 0; overflow: hidden; border: 1px solid var(--garis);">
                {{-- Kepala Judul Tugas --}}
                <div style="padding: 18px 22px; background: #fff; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; cursor: pointer;" onclick="toggleKelompok('{{ $groupId }}')">
                    <div style="display: flex; gap: 14px; align-items: flex-start; flex: 1; min-width: 280px;">
                        <span class="ikon-bulat" style="width: 44px; height: 44px; border-radius: 10px; margin-top: 2px;">
                            <x-ikon nama="tugas" />
                        </span>
                        <div>
                            <h2 style="margin: 0 0 6px; font-size: 17px; color: var(--teks); font-weight: 700;">
                                {{ $judulTugas }}
                            </h2>
                            <div style="display: flex; gap: 16px; font-size: 13px; color: var(--redup); flex-wrap: wrap;">
                                <span><x-ikon nama="kalender" /> Tenggat: <b>{{ $contoh->tenggat?->format('d M Y, H:i') }}</b></span>
                                <span><x-ikon nama="papan-centang" /> Target: <b>{{ $contoh->jumlah_progres }} Tahapan</b></span>
                                <span><x-ikon nama="pengguna" /> Penerima: <b>{{ $jumlahPeserta }} Mahasiswa</b></span>
                            </div>
                        </div>
                    </div>

                    {{-- Ringkasan Status & Tombol Expand --}}
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        @if ($countDiserahkan > 0)
                            <span class="status status-peringatan" style="font-size: 12px;">{{ $countDiserahkan }} Butuh Review</span>
                        @endif
                        @if ($countPerbaikan > 0)
                            <span class="status status-gagal" style="font-size: 12px;">{{ $countPerbaikan }} Perbaikan</span>
                        @endif
                        @if ($countSelesai > 0)
                            <span class="status status-sukses" style="font-size: 12px;">{{ $countSelesai }} Selesai</span>
                        @endif

                        <button type="button" class="btn btn-garis btn-kecil" style="pointer-events: none;" id="btn-icon-{{ $groupId }}">
                            Daftar Mahasiswa ({{ $jumlahPeserta }}) <x-ikon nama="chevron-bawah" />
                        </button>
                    </div>
                </div>

                {{-- Daftar Mahasiswa Penerima Tugas Ini (Expandable / Terbuka) --}}
                <div id="{{ $groupId }}" style="border-top: 1px solid var(--garis); background: var(--permukaan-2); padding: 0;">
                    <div class="tabel-wadah" style="margin: 0;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--garis); font-size: 12.5px; color: var(--redup);">
                                    <th style="padding: 10px 18px; text-align: left; width: 40px;">No</th>
                                    <th style="padding: 10px 18px; text-align: left;">Nama Mahasiswa</th>
                                    <th style="padding: 10px 18px; text-align: left;">Progres Pengerjaan</th>
                                    <th style="padding: 10px 18px; text-align: left;">Status Tugas</th>
                                    <th style="padding: 10px 18px; text-align: left;">Nilai Akhir</th>
                                    <th style="padding: 10px 18px; text-align: right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($kumpulanTugas as $idx => $tugasPeserta)
                                    @php
                                        $peserta = $tugasPeserta->pesertaMagang;
                                        $namaMhs = $peserta?->calonPeserta()?->nama_lengkap ?? $peserta?->pengguna?->nama_lengkap ?? 'Peserta #'.$peserta?->id_peserta_magang;
                                        $institusiMhs = $peserta?->calonPeserta()?->institusi_asal ?? 'DTI Unand';
                                        $urutanAktif = $tugasPeserta->urutanProgresTerakhir();
                                        $kelasStatus = match ($tugasPeserta->status) {
                                            'Diserahkan' => 'status-peringatan',
                                            'Selesai' => 'status-sukses',
                                            'Perlu Perbaikan' => 'status-gagal',
                                            default => 'status-netral',
                                        };
                                    @endphp
                                    <tr style="border-bottom: 1px solid var(--garis); background: #fff;">
                                        <td style="padding: 12px 18px; font-size: 13px; color: var(--redup);">{{ $idx + 1 }}</td>
                                        <td style="padding: 12px 18px;">
                                            <div style="font-weight: 600; font-size: 13.5px; color: var(--teks);">{{ $namaMhs }}</div>
                                            <small class="redup" style="font-size: 12px;">{{ $institusiMhs }}</small>
                                        </td>
                                        <td style="padding: 12px 18px;">
                                            <span class="chip" style="font-size: 12px;">
                                                Tahap {{ $urutanAktif }} dari {{ $tugasPeserta->jumlah_progres }}
                                            </span>
                                            @if ($tugasPeserta->progresTerakhir())
                                                <small class="redup" style="display: block; margin-top: 3px; font-size: 11.5px;">
                                                    Terkirim: {{ $tugasPeserta->progresTerakhir()->waktu_kirim?->format('d/m/Y H:i') }}
                                                </small>
                                            @endif
                                        </td>
                                        <td style="padding: 12px 18px;">
                                            <span class="status {{ $kelasStatus }}" style="font-size: 12px;">{{ $tugasPeserta->status }}</span>
                                        </td>
                                        <td style="padding: 12px 18px;">
                                            @if ($tugasPeserta->nilai !== null)
                                                <b style="color: var(--sukses); font-size: 14px;">{{ $tugasPeserta->nilai }}</b><small class="redup">/100</small>
                                            @elseif ($tugasPeserta->status === 'Selesai')
                                                <span class="chip" style="background:#fff3e0;color:#e65100;font-size:11px;">Belum Dinilai</span>
                                            @else
                                                <span class="redup" style="font-size: 12px;">-</span>
                                            @endif
                                        </td>
                                        <td style="padding: 12px 18px; text-align: right;">
                                            <div style="display: inline-flex; gap: 6px; align-items: center;">
                                                <a class="btn btn-kuning btn-kecil" href="{{ route('mentor.tugas.show', $tugasPeserta) }}" title="Lihat detail tugas & progres mahasiswa ini">
                                                    <x-ikon nama="mata" /> {{ $tugasPeserta->status === 'Diserahkan' ? 'Review Progres' : 'Detail' }}
                                                </a>
                                                @if ($tugasPeserta->status !== 'Selesai')
                                                    <a class="btn btn-garis btn-kecil" href="{{ route('mentor.tugas.edit', $tugasPeserta) }}" title="Ubah tugas mahasiswa ini">
                                                        <x-ikon nama="ubah" /> Ubah
                                                    </a>
                                                    <form method="POST" action="{{ route('mentor.tugas.destroy', $tugasPeserta) }}" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus tugas untuk mahasiswa ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-garis-bahaya btn-kecil" title="Hapus tugas">
                                                            <x-ikon nama="silang" />
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div style="margin-top: 20px;">
        {{ $daftarTugas->links() }}
    </div>
@endif

<script>
function toggleKelompok(id) {
    const el = document.getElementById(id);
    if (!el) return;
    const isHidden = el.style.display === 'none';
    el.style.display = isHidden ? 'block' : 'none';
}
</script>
@endsection
