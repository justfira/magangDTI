@extends('layouts.app')
@section('judul', 'Aktivasi Peserta Magang')
@section('konten')

{{-- ===== KEPALA HALAMAN ===== --}}
<div class="kepala-halaman">
    <div>
        <p class="kicker">Manajemen Akun</p>
        <h1>
            Aktivasi Peserta Magang
            @if ($jumlahMenunggu > 0)
                <span class="angka-pil" title="{{ $jumlahMenunggu }} menunggu aktivasi">{{ $jumlahMenunggu }}</span>
            @endif
        </h1>
        <p class="sub-judul">
            Aktifkan akun peserta yang sudah mendapat keputusan <b>Diterima</b> dari Direktur,
            lalu tentukan tanggal mulai dan selesai magang.
        </p>
    </div>
    <div class="kepala-aksi">
        <a class="btn btn-garis" href="{{ route('superadmin.akun.index') }}">
            <x-ikon nama="orang" /> Kelola Akun Pegawai
        </a>
    </div>
</div>

{{-- ===== FLASH MESSAGE ===== --}}
@if (session('sukses'))
    <div class="alert alert-sukses" role="alert">
        {!! nl2br(e(session('sukses'))) !!}
        {{-- password sementara ditampilkan sekali di sini; sudah di-escape kecuali tag <code> yang aman --}}
        @if (str_contains(session('sukses'), '<code>'))
            @php
                // Tampilkan bagian password yang sudah disisipkan sebagai HTML aman
            @endphp
        @endif
    </div>
@endif
@if (session('gagal'))
    <div class="alert alert-gagal" role="alert">{{ session('gagal') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-gagal" role="alert">
        <ul style="margin:0;padding-left:18px">
            @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
        </ul>
    </div>
@endif

{{-- ===== TAB ===== --}}
<div class="tab-bar">
    @foreach ($daftarTab as $kunci => $label)
        <a class="tab {{ $tab === $kunci ? 'aktif' : '' }}"
           href="{{ route('superadmin.akun.diterima', array_filter(['tab' => $kunci, 'cari' => $cari])) }}">
            {{ $label }}
            @if ($kunci === 'menunggu' && $jumlahMenunggu > 0)
                <span class="badge-tab">{{ $jumlahMenunggu }}</span>
            @endif
        </a>
    @endforeach
</div>

{{-- ===== PENCARIAN ===== --}}
<form class="kartu filter-bar" method="GET" action="{{ route('superadmin.akun.diterima') }}">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <label>Cari peserta
        <span class="input-ikon">
            <x-ikon nama="cari" />
            <input name="cari"
                   value="{{ $cari }}"
                   placeholder="Nama, email, atau institusi/program"
                   autocomplete="off">
        </span>
    </label>
    <div class="aksi">
        @if ($cari !== '')
            <a class="btn btn-halus" href="{{ route('superadmin.akun.diterima', ['tab' => $tab]) }}">
                <x-ikon nama="atur-ulang" /> Atur ulang
            </a>
        @endif
        <button class="btn"><x-ikon nama="cari" /> Cari</button>
    </div>
</form>

{{-- ===== TABEL ===== --}}
<div class="kartu kartu-tabel">

    @if ($cari !== '')
        <div class="kartu-kepala" style="padding:12px 22px 0">
            <div class="kartu-judul">
                <span class="ikon-bulat"><x-ikon nama="cari" /></span>
                <div>
                    <h2 style="font-size:14px;margin:0">Hasil pencarian</h2>
                    <p style="margin:0;font-size:12.5px;color:var(--redup)">
                        {{ $keputusan->total() }} peserta ditemukan
                        untuk &ldquo;<b>{{ $cari }}</b>&rdquo;
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="tabel-wadah">
        <table>
            <thead>
                <tr>
                    <th>Calon Peserta</th>
                    <th>Email</th>
                    <th>Institusi / Program</th>
                    <th>Jalur</th>
                    <th>Tanggal Keputusan</th>
                    @if ($tab === 'riwayat')
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Status Magang</th>
                    @else
                        <th class="aksi-tabel">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>

            @forelse ($keputusan as $k)
                @php
                    $c     = $k->calon;
                    $p     = $c?->pengajuan;
                    $jalur = $p?->jalur ?? '—';
                    $pm    = $k->pesertaMagang;

                    $labelSumber = $jalur === 'INSTITUSI'
                        ? ($p?->nama_institusi ?? '—')
                        : ($p?->diajukanOleh?->nama_lengkap ?? '—');

                    $modalId = 'modal-aktivasi-' . $k->id_keputusan;

                    $kelasStatus = match ($pm?->status_magang) {
                        'Belum Mulai' => 'status-netral',
                        'Aktif'       => 'status-sukses',
                        'Selesai'     => 'status-sukses',
                        'Dihentikan'  => 'status-gagal',
                        default       => 'status-netral',
                    };
                @endphp

                {{-- ── Baris utama ── --}}
                <tr>
                    {{-- Calon --}}
                    <td>
                        <div class="sel-utama">
                            <span class="inisial v{{ abs(crc32($c?->nama_lengkap ?? '')) % 5 }}">
                                {{ mb_strtoupper(mb_substr($c?->nama_lengkap ?? '?', 0, 1)) }}
                            </span>
                            <div>
                                <b>{{ $c?->nama_lengkap ?? '—' }}</b>
                                @if ($c?->nomor_induk_pendidikan)
                                    <small class="redup">{{ $c->nomor_induk_pendidikan }}</small>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- Email --}}
                    <td>
                        <span style="font-size:13px">{{ $c?->email ?? '—' }}</span>
                        @if ($tab === 'riwayat' && $pm?->pengguna)
                            <small class="redup" style="display:block">Akun: {{ $pm->pengguna->email }}</small>
                        @endif
                    </td>

                    {{-- Institusi / Program --}}
                    <td>
                        <span>{{ $labelSumber }}</span>
                        @if ($c?->jurusan_prodi)
                            <small class="redup" style="display:block">{{ $c->jurusan_prodi }}</small>
                        @endif
                    </td>

                    {{-- Jalur --}}
                    <td>
                        <span class="status {{ $jalur === 'INSTITUSI' ? 'status-info' : 'status-netral' }}">
                            {{ $jalur }}
                        </span>
                    </td>

                    {{-- Tanggal Keputusan --}}
                    <td>
                        <span style="font-size:13px">
                            {{ $k->waktu_keputusan?->format('d M Y') ?? '—' }}
                        </span>
                    </td>

                    @if ($tab === 'riwayat')
                        {{-- Tanggal Mulai --}}
                        <td>{{ $pm?->tanggal_mulai?->format('d M Y') ?? '—' }}</td>

                        {{-- Tanggal Selesai --}}
                        <td>{{ $pm?->tanggal_selesai?->format('d M Y') ?? '—' }}</td>

                        {{-- Status Magang --}}
                        <td>
                            <span class="status {{ $kelasStatus }}">
                                {{ $pm?->status_magang ?? '—' }}
                            </span>
                        </td>

                    @else
                        {{-- Tombol Aktifkan --}}
                        <td class="aksi-tabel">
                            <button type="button"
                                    class="btn btn-kecil"
                                    onclick="bukaPanel('{{ $modalId }}')">
                                <x-ikon nama="centang-saja" /> Aktifkan
                            </button>
                        </td>
                    @endif
                </tr>

                {{-- ── Panel form aktivasi (hanya tab menunggu) ── --}}
                @if ($tab === 'menunggu')
                <tr class="baris-panel" id="baris-{{ $modalId }}" style="display:none">
                    <td colspan="6" style="padding:0">
                        <div class="panel-rekomendasi">

                            {{-- Header panel --}}
                            <div class="panel-rek-kepala">
                                <span class="ikon-bulat hijau"><x-ikon nama="centang-saja" /></span>
                                <div>
                                    <b>Aktivasi untuk {{ $c?->nama_lengkap }}</b>
                                    <small class="redup">
                                        {{ $p?->kode_pengajuan ?? '—' }}
                                        &mdash;
                                        @if ($jalur === 'INSTITUSI')
                                            Institusi: {{ $p?->nama_institusi ?? '—' }}
                                        @else
                                            Pendaftar: {{ $p?->diajukanOleh?->nama_lengkap ?? '—' }}
                                        @endif
                                        &mdash; Jalur <b>{{ $jalur }}</b>
                                    </small>
                                </div>
                                <button type="button"
                                        class="btn-tutup-modal"
                                        onclick="tutupPanel('{{ $modalId }}')"
                                        title="Tutup">
                                    <x-ikon nama="silang" />
                                </button>
                            </div>

                            {{-- Peringatan jalur INSTITUSI --}}
                            @if ($jalur === 'INSTITUSI')
                                <div class="kartu-info" style="margin:12px 20px 0;padding:10px 14px;background:rgba(234,179,8,.08);border-left:3px solid #ca8a04;border-radius:0 4px 4px 0;font-size:13px">
                                    <b>Catatan jalur INSTITUSI:</b> Jika email <code>{{ $c?->email }}</code>
                                    belum terdaftar, sistem akan membuat akun baru dengan peran PESERTA
                                    dan menampilkan password sementara sekali setelah aktivasi.
                                    Sampaikan kredensial kepada peserta secara langsung.
                                </div>
                            @endif

                            {{-- Form isi tanggal --}}
                            <form method="POST"
                                  id="form-{{ $modalId }}"
                                  action="{{ route('superadmin.akun.aktivasi', $k) }}"
                                  onsubmit="return konfirmasiAktivasi(this, '{{ $c?->nama_lengkap ?? 'peserta ini' }}')">
                                @csrf

                                <div class="panel-rek-isi">
                                    <label>
                                        Tanggal Mulai Magang <span style="color:var(--gagal)">*</span>
                                        <input type="date"
                                               name="tanggal_mulai"
                                               value="{{ old('tanggal_mulai') }}"
                                               min="{{ date('Y-m-d') }}"
                                               onchange="updateMinSelesai('{{ $modalId }}', this.value)"
                                               required>
                                        @error('tanggal_mulai')
                                            <p class="err-pilih" style="display:block">{{ $message }}</p>
                                        @enderror
                                    </label>

                                    <label>
                                        Tanggal Selesai Magang <span style="color:var(--gagal)">*</span>
                                        <input type="date"
                                               name="tanggal_selesai"
                                               id="selesai-{{ $modalId }}"
                                               value="{{ old('tanggal_selesai') }}"
                                               min="{{ date('Y-m-d') }}"
                                               required>
                                        @error('tanggal_selesai')
                                            <p class="err-pilih" style="display:block">{{ $message }}</p>
                                        @enderror
                                    </label>
                                </div>

                                <div class="panel-rek-kaki">
                                    <button type="button"
                                            class="btn btn-garis"
                                            onclick="tutupPanel('{{ $modalId }}')">
                                        Batal
                                    </button>
                                    <button type="submit" class="btn">
                                        <x-ikon nama="centang-saja" /> Simpan &amp; Aktifkan
                                    </button>
                                </div>
                            </form>

                        </div>
                    </td>
                </tr>
                @endif

            @empty
                <tr>
                    <td colspan="{{ $tab === 'riwayat' ? 8 : 6 }}" class="kosong">
                        <div class="empty-state">
                            <x-ikon nama="{{ $tab === 'menunggu' ? 'centang-lingkaran' : 'berkas' }}" />
                            <p>
                                @if ($cari !== '')
                                    Tidak ada peserta ditemukan untuk pencarian &ldquo;<b>{{ $cari }}</b>&rdquo;.
                                @elseif ($tab === 'menunggu')
                                    Semua peserta yang diterima sudah diaktifkan.
                                @else
                                    Belum ada peserta yang sudah diaktifkan.
                                @endif
                            </p>
                        </div>
                    </td>
                </tr>
            @endforelse

            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($keputusan->hasPages())
        <div class="pagination-bar">
            {{ $keputusan->links() }}
        </div>
    @endif

</div>

{{-- ===== CSS TAMBAHAN ===== --}}
<style>
/* Warisi .panel-rekomendasi dari kepala-seksi/rekomendasi/index.blade.php */
.panel-rekomendasi {
    background: var(--permukaan, #f9fafb);
    border-top: 2px solid var(--utama, #166534);
    padding: 0 0 16px;
}
.panel-rek-kepala {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    border-bottom: 1px solid var(--batas, #e5e7eb);
}
.panel-rek-kepala .btn-tutup-modal {
    margin-left: auto;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--redup, #6b7280);
    padding: 4px;
    display: flex;
    align-items: center;
}
.panel-rek-isi {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    padding: 16px 20px 8px;
}
@media (max-width: 600px) {
    .panel-rek-isi { grid-template-columns: 1fr; }
}
.panel-rek-isi label { display: flex; flex-direction: column; gap: 6px; font-size: 13.5px; font-weight: 500; }
.panel-rek-isi input[type=date] {
    border: 1.5px solid var(--batas, #d1d5db);
    border-radius: 6px;
    padding: 7px 10px;
    font-size: 14px;
    width: 100%;
}
.panel-rek-kaki {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 12px 20px 0;
}
.ikon-bulat.hijau { background: rgba(22,101,52,.12); color: var(--utama, #166534); }
.err-pilih {
    display: none;
    color: var(--gagal, #dc2626);
    font-size: 12.5px;
    font-weight: 500;
    margin: 4px 0 0;
    padding: 5px 10px;
    background: rgba(220,38,38,.07);
    border-left: 3px solid var(--gagal, #dc2626);
    border-radius: 0 4px 4px 0;
}
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
.status-info { background: rgba(37,99,235,.1); color: #1d4ed8; }
.badge-tab {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 18px;
    padding: 0 5px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    background: var(--utama, #166534);
    color: #fff;
    margin-left: 6px;
    vertical-align: middle;
}
.tab.aktif .badge-tab { background: #fff; color: var(--utama, #166534); }
</style>

{{-- ===== JAVASCRIPT ===== --}}
<script>
function bukaPanel(id) {
    document.querySelectorAll('.baris-panel').forEach(function (el) {
        el.style.display = 'none';
    });
    var baris = document.getElementById('baris-' + id);
    if (baris) {
        baris.style.display = '';
        baris.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function tutupPanel(id) {
    var baris = document.getElementById('baris-' + id);
    if (baris) baris.style.display = 'none';
}

/** Sesuaikan min tanggal_selesai ketika tanggal_mulai berubah. */
function updateMinSelesai(modalId, tanggalMulai) {
    var inp = document.getElementById('selesai-' + modalId);
    if (inp) {
        inp.min = tanggalMulai;
        if (inp.value && inp.value < tanggalMulai) {
            inp.value = tanggalMulai;
        }
    }
}

/** Konfirmasi sebelum submit form aktivasi. */
function konfirmasiAktivasi(form, nama) {
    var mulai   = form.querySelector('[name="tanggal_mulai"]')?.value;
    var selesai = form.querySelector('[name="tanggal_selesai"]')?.value;
    if (!mulai || !selesai) return true;   // biarkan HTML5 required menangani
    return confirm(
        'Aktifkan peserta ' + nama + '?\n\n'
        + 'Tanggal mulai  : ' + mulai + '\n'
        + 'Tanggal selesai: ' + selesai + '\n\n'
        + 'Data ini tidak dapat diubah setelah disimpan.'
    );
}

// ESC menutup semua panel
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.baris-panel').forEach(function (el) {
            el.style.display = 'none';
        });
    }
});
</script>

@endsection
