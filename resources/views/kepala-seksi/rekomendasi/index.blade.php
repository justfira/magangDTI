@extends('layouts.app')
@section('judul', 'Rekomendasi Penerimaan')
@section('konten')

{{-- ===== KEPALA HALAMAN ===== --}}
<div class="kepala-halaman">
    <div>
        <p class="kicker">Modul Penerimaan</p>
        <h1>
            Rekomendasi Penerimaan
            @if ($jumlahMenunggu > 0)
                <span class="angka-pil" title="{{ $jumlahMenunggu }} calon menunggu rekomendasi">{{ $jumlahMenunggu }}</span>
            @endif
        </h1>
        <p class="sub-judul">
            Tinjau calon peserta dari pengajuan yang sudah dinyatakan <b>Lengkap</b> oleh Superadmin,
            lalu berikan rekomendasi <b>Terima</b> atau <b>Tolak</b> kepada Direktur.
        </p>
    </div>
</div>

{{-- ===== TAB ===== --}}
<div class="tab-bar">
    @foreach ($daftarTab as $kunci => $label)
        <a class="tab {{ $tab === $kunci ? 'aktif' : '' }}"
           href="{{ route('kasi.rekomendasi.index', array_filter(['tab' => $kunci, 'cari' => $cari])) }}">
            {{ $label }}
            @if ($kunci === 'menunggu' && $jumlahMenunggu > 0)
                <span class="badge-tab">{{ $jumlahMenunggu }}</span>
            @endif
        </a>
    @endforeach
</div>

{{-- ===== PENCARIAN ===== --}}
<form class="kartu filter-bar" method="GET" action="{{ route('kasi.rekomendasi.index') }}">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <label>Cari calon / pengajuan
        <span class="input-ikon">
            <x-ikon nama="cari" />
            <input name="cari"
                   value="{{ $cari }}"
                   placeholder="Nama, NIM, kode pengajuan, atau institusi"
                   autocomplete="off">
        </span>
    </label>
    <div class="aksi">
        @if ($cari !== '')
            <a class="btn btn-halus"
               href="{{ route('kasi.rekomendasi.index', ['tab' => $tab]) }}">
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
                        {{ $calon->total() }} calon ditemukan untuk
                        &ldquo;<b>{{ $cari }}</b>&rdquo;
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
                    <th>NIM / Kontak</th>
                    <th>Pengajuan</th>
                    <th>Jalur</th>
                    <th>Dinyatakan Lengkap</th>
                    @if ($tab === 'riwayat')
                        <th>Rekomendasi</th>
                        <th>Oleh / Waktu</th>
                    @else
                        <th>Status</th>
                    @endif
                    <th class="aksi-tabel">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($calon as $c)
                @php
                    $k     = $c->keputusanPenerimaan;
                    $p     = $c->pengajuan;
                    $kode  = $p->kode_pengajuan ?? '—';
                    $jalur = $p->jalur ?? '—';
                    $labelSumber = $jalur === 'INSTITUSI'
                        ? ($p->nama_institusi ?? '—')
                        : ($p->diajukanOleh?->nama_lengkap ?? '—');

                    $kelasRek = match ($k?->rekomendasi) {
                        'Terima' => 'status-sukses',
                        'Tolak'  => 'status-gagal',
                        default  => 'status-netral',
                    };
                    $kelasKeputusan = match ($k?->keputusan) {
                        'Diterima' => 'status-sukses',
                        'Ditolak'  => 'status-gagal',
                        default    => 'status-peringatan',
                    };
                    $modalId = 'modal-rek-' . $c->id_calon;
                @endphp
                <tr>
                    {{-- Identitas calon --}}
                    <td>
                        <div class="sel-utama">
                            <span class="inisial">{{ mb_strtoupper(mb_substr($c->nama_lengkap, 0, 1)) }}</span>
                            <div>
                                <b>{{ $c->nama_lengkap }}</b>
                                @if ($c->jurusan_prodi)
                                    <small class="redup">{{ $c->jurusan_prodi }}</small>
                                @endif
                                @if ($c->institusi_asal)
                                    <small class="redup">{{ $c->institusi_asal }}</small>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- NIM / Kontak --}}
                    <td>
                        @if ($c->nomor_induk_pendidikan)
                            <span class="sel-ikon"><x-ikon nama="pengguna" />{{ $c->nomor_induk_pendidikan }}</span>
                        @endif
                        @if ($c->email)
                            <small class="redup" style="display:block;padding-left:{{ $c->nomor_induk_pendidikan ? '20' : '0' }}px">
                                {{ $c->email }}
                            </small>
                        @elseif ($c->no_hp)
                            <small class="redup" style="display:block;padding-left:{{ $c->nomor_induk_pendidikan ? '20' : '0' }}px">
                                {{ $c->no_hp }}
                            </small>
                        @endif
                        @if (!$c->nomor_induk_pendidikan && !$c->email && !$c->no_hp)
                            <span class="redup">—</span>
                        @endif
                    </td>

                    {{-- Pengajuan --}}
                    <td>
                        <b style="font-family:monospace;font-size:12.5px;letter-spacing:.4px">{{ $kode }}</b>
                        <small class="redup" style="display:block;margin-top:2px">{{ $labelSumber }}</small>
                    </td>

                    {{-- Jalur --}}
                    <td>
                        <span class="status {{ $jalur === 'INSTITUSI' ? 'status-netral' : 'status-info' }}">
                            {{ $jalur }}
                        </span>
                    </td>

                    {{-- Waktu Lengkap --}}
                    <td>
                        @if ($p->waktu_pemeriksaan)
                            <span class="sel-ikon">
                                <x-ikon nama="kalender" />
                                {{ $p->waktu_pemeriksaan->translatedFormat('d M Y') }}
                            </span>
                            <small class="redup" style="display:block;margin-top:2px;padding-left:20px">
                                {{ $p->waktu_pemeriksaan->diffForHumans() }}
                            </small>
                        @else
                            <span class="redup">—</span>
                        @endif
                    </td>

                    {{-- Kolom kondisional --}}
                    @if ($tab === 'riwayat')
                        <td>
                            <span class="status {{ $kelasRek }}">{{ $k?->rekomendasi ?? '—' }}</span>
                            @if ($k?->keputusan)
                                <br>
                                <span class="status {{ $kelasKeputusan }}" style="margin-top:4px">
                                    {{ $k->keputusan }} <small>(Direktur)</small>
                                </span>
                            @elseif ($k?->rekomendasi)
                                <small class="redup" style="display:block;margin-top:4px">Menunggu Direktur</small>
                            @endif
                            @if ($k?->catatan_rekomendasi)
                                <small class="redup" style="display:block;margin-top:3px;font-style:italic">
                                    &ldquo;{{ Str::limit($k->catatan_rekomendasi, 70) }}&rdquo;
                                </small>
                            @endif
                        </td>
                        <td>
                            @if ($k?->kepalaSeksi?->pengguna)
                                <div class="sel-ikon">
                                    <x-ikon nama="pengguna" />
                                    {{ $k->kepalaSeksi->pengguna->nama_lengkap }}
                                </div>
                                @if ($k->waktu_rekomendasi)
                                    <small class="redup" style="display:block;margin-top:2px;padding-left:20px">
                                        {{ $k->waktu_rekomendasi->diffForHumans() }}
                                    </small>
                                @endif
                            @else
                                <span class="redup">—</span>
                            @endif
                        </td>
                    @else
                        <td>
                            <span class="status status-peringatan">Menunggu Rekomendasi</span>
                        </td>
                    @endif

                    {{-- Aksi --}}
                    <td class="aksi-tabel">
                        @if ($tab === 'menunggu')
                            <button type="button"
                                    class="btn btn-kecil"
                                    onclick="bukaModal('{{ $modalId }}')"
                                    title="Beri rekomendasi untuk {{ $c->nama_lengkap }}">
                                <x-ikon nama="papan-centang" /> Rekomendasikan
                            </button>
                        @else
                            <span class="redup" style="font-size:12px">Sudah diproses</span>
                        @endif
                    </td>
                </tr>

                {{-- ── Modal rekomendasi (hanya tab menunggu) ── --}}
                @if ($tab === 'menunggu')
                <tr class="baris-modal" id="baris-{{ $modalId }}" style="display:none">
                    <td colspan="7" style="padding:0">
                        <div class="panel-rekomendasi">
                            <form method="POST"
                                  action="{{ route('kasi.rekomendasi.store', $c) }}"
                                  id="form-{{ $modalId }}"
                                  onsubmit="return validasiFormRek(this, '{{ $modalId }}')">
                                @csrf
                                <div class="panel-rek-kepala">
                                    <span class="ikon-bulat kuning"><x-ikon nama="papan-centang" /></span>
                                    <div>
                                        <b>Rekomendasi untuk {{ $c->nama_lengkap }}</b>
                                        <small class="redup">
                                            {{ $kode }} &mdash; {{ $labelSumber }}
                                        </small>
                                    </div>
                                    <button type="button"
                                            class="btn-tutup-modal"
                                            onclick="tutupModal('{{ $modalId }}')"
                                            title="Tutup">
                                        <x-ikon nama="silang" />
                                    </button>
                                </div>

                                <div class="panel-rek-isi">
                                    <label>
                                        Rekomendasi <span style="color:var(--gagal)">*</span>
                                        <div class="pilih-rek" id="pilih-rek-{{ $modalId }}">
                                            @foreach ($daftarRek as $rek)
                                                <label class="opsi-rek opsi-{{ strtolower($rek) }}">
                                                    <input type="radio"
                                                           name="rekomendasi"
                                                           value="{{ $rek }}"
                                                           form="form-{{ $modalId }}"
                                                           onchange="hapusErrRek('{{ $modalId }}')">
                                                    <span>
                                                        @if ($rek === 'Terima')
                                                            <x-ikon nama="centang-saja" />
                                                        @else
                                                            <x-ikon nama="silang" />
                                                        @endif
                                                        {{ $rek }}
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <p class="err-pilih" id="err-rek-{{ $modalId }}"
                                           role="alert" aria-live="polite">
                                            Silakan pilih rekomendasi Terima atau Tolak terlebih dahulu.
                                        </p>
                                    </label>

                                    <label>
                                        Catatan <small class="redup">(opsional)</small>
                                        <textarea name="catatan_rekomendasi"
                                                  form="form-{{ $modalId }}"
                                                  rows="3"
                                                  maxlength="2000"
                                                  placeholder="Alasan atau catatan tambahan untuk Direktur…"></textarea>
                                    </label>
                                </div>

                                <div class="panel-rek-kaki">
                                    <button type="button"
                                            class="btn btn-halus"
                                            onclick="tutupModal('{{ $modalId }}')">
                                        Batal
                                    </button>
                                    <button type="submit"
                                            class="btn"
                                            form="form-{{ $modalId }}">
                                        <x-ikon nama="centang-saja" /> Simpan Rekomendasi
                                    </button>
                                </div>
                            </form>
                        </div>
                    </td>
                </tr>
                @endif
            @empty
                <tr>
                    <td colspan="{{ $tab === 'riwayat' ? 8 : 7 }}">
                        <div class="kosong">
                            <div class="ilustrasi"><x-ikon nama="papan-centang" /></div>
                            @if ($cari !== '')
                                <b>Tidak ada calon yang cocok.</b>
                                Coba ubah kata kunci pencarian.
                                <br>
                                <a class="btn btn-garis"
                                   href="{{ route('kasi.rekomendasi.index', ['tab' => $tab]) }}"
                                   style="margin-top:14px">
                                    <x-ikon nama="atur-ulang" /> Atur ulang
                                </a>
                            @elseif ($tab === 'menunggu')
                                <b>Tidak ada calon yang perlu direkomendasikan.</b>
                                Semua pengajuan yang sudah <b>Lengkap</b> telah mendapat rekomendasi,
                                atau belum ada pengajuan yang dinyatakan Lengkap oleh Superadmin.
                            @else
                                <b>Belum ada riwayat rekomendasi.</b>
                                Rekomendasi yang sudah Anda berikan akan muncul di sini.
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($calon->hasPages())
        <div style="padding:12px 22px 16px;border-top:1px solid var(--garis)">
            {{ $calon->links() }}
        </div>
    @endif
</div>

<p class="petunjuk">
    <x-ikon nama="info" style="width:14px;height:14px;vertical-align:middle" />
    Tab <b>Menunggu</b>: calon dari pengajuan <b>Lengkap</b> yang belum direkomendasikan.
    Setelah disimpan, rekomendasi diteruskan ke Direktur dan calon pindah ke <b>Riwayat</b>.
</p>

{{-- ===== STYLES ===== --}}
<style>
/* Panel rekomendasi inline di bawah baris tabel */
.panel-rekomendasi {
    background: var(--bg, #fafaf8);
    border-top: 2px solid var(--utama, #166534);
    border-bottom: 2px solid var(--utama, #166534);
    animation: geser-turun .18s ease;
}
@keyframes geser-turun {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.panel-rek-kepala {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 22px 10px;
    border-bottom: 1px solid var(--garis, #e5e7eb);
}
.panel-rek-kepala .ikon-bulat { flex-shrink: 0; }
.panel-rek-kepala > div { flex: 1; }
.panel-rek-kepala > div b { display: block; font-size: 14px; }
.panel-rek-kepala > div small { font-size: 12px; }
.btn-tutup-modal {
    display: inline-flex; align-items: center; justify-content: center;
    width: 28px; height: 28px;
    border: 1px solid var(--garis); border-radius: 6px;
    background: transparent; cursor: pointer; color: var(--redup);
    transition: background .15s, color .15s;
}
.btn-tutup-modal:hover { background: rgba(220,38,38,.08); color: var(--gagal); border-color: var(--gagal); }
.btn-tutup-modal .ikon { width: 14px; height: 14px; }

.panel-rek-isi {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 16px;
    padding: 16px 22px;
    align-items: start;
}
@media (max-width: 680px) {
    .panel-rek-isi { grid-template-columns: 1fr; }
}

/* Pilihan rekomendasi — dua tombol besar */
.pilih-rek {
    display: flex;
    gap: 10px;
    margin-top: 8px;
}
.opsi-rek {
    cursor: pointer;
}
.opsi-rek input[type="radio"] { display: none; }
.opsi-rek span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 20px;
    border: 2px solid var(--garis, #d1d5db);
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    background: #fff;
    transition: border-color .15s, background .15s, color .15s;
    user-select: none;
}
.opsi-rek span .ikon { width: 16px; height: 16px; }
.opsi-rek.opsi-terima input:checked + span {
    border-color: var(--utama, #166534);
    background: rgba(22,101,52,.08);
    color: var(--utama, #166534);
}
.opsi-rek.opsi-tolak input:checked + span {
    border-color: var(--gagal, #dc2626);
    background: rgba(220,38,38,.07);
    color: var(--gagal, #dc2626);
}
.opsi-rek:hover span { border-color: var(--utama, #166534); }
.opsi-rek.opsi-tolak:hover span { border-color: var(--gagal, #dc2626); }

.panel-rek-kaki {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 12px 22px 16px;
    border-top: 1px solid var(--garis);
}

/* Badge tab */
.badge-tab {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px; height: 18px;
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

/* Pesan validasi pilihan rekomendasi */
.err-pilih {
    display: none;
    color: var(--gagal, #dc2626);
    font-size: 12.5px;
    font-weight: 500;
    margin: 6px 0 0;
    padding: 6px 10px;
    background: rgba(220,38,38,.07);
    border-left: 3px solid var(--gagal, #dc2626);
    border-radius: 0 4px 4px 0;
    animation: geser-turun .15s ease;
}
.pilih-rek.err-aktif .opsi-rek span,
.pilih-keputusan.err-aktif .opsi-kep span {
    border-color: var(--gagal, #dc2626);
}
</style>

{{-- ===== JAVASCRIPT ===== --}}
<script>
function bukaModal(id) {
    document.querySelectorAll('.baris-modal').forEach(function (el) {
        el.style.display = 'none';
    });
    var baris = document.getElementById('baris-' + id);
    if (baris) {
        baris.style.display = '';
        baris.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}
function tutupModal(id) {
    var baris = document.getElementById('baris-' + id);
    if (baris) baris.style.display = 'none';
}

/** Tampilkan error jika tidak ada pilihan rekomendasi, kembalikan false untuk batalkan submit. */
function validasiFormRek(form, id) {
    var radio = form.querySelector('input[name="rekomendasi"]:checked');
    if (!radio) {
        var container = document.getElementById('pilih-rek-' + id);
        var err       = document.getElementById('err-rek-' + id);
        if (container) container.classList.add('err-aktif');
        if (err)       err.style.display = 'block';
        return false;
    }
    return true;
}

/** Hapus indikator error saat pengguna memilih opsi. */
function hapusErrRek(id) {
    var container = document.getElementById('pilih-rek-' + id);
    var err       = document.getElementById('err-rek-' + id);
    if (container) container.classList.remove('err-aktif');
    if (err)       err.style.display = 'none';
}

// Pasang submit handler ke semua form rekomendasi.
// Sudah ditangani via onsubmit langsung di tag <form>, tidak perlu DOMContentLoaded.

// ESC menutup semua panel
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.baris-modal').forEach(function (el) {
            el.style.display = 'none';
        });
    }
});
</script>

@endsection
