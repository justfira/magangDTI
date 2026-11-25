@extends('layouts.app')
@section('judul', 'Keputusan Penerimaan')
@section('konten')

{{-- ===== KEPALA HALAMAN ===== --}}
<div class="kepala-halaman">
    <div>
        <p class="kicker">Modul Penerimaan</p>
        <h1>
            Keputusan Penerimaan
            @if ($jumlahMenunggu > 0)
                <span class="angka-pil" title="{{ $jumlahMenunggu }} menunggu keputusan">{{ $jumlahMenunggu }}</span>
            @endif
        </h1>
        <p class="sub-judul">
            Tinjau rekomendasi Kepala Seksi dan berikan keputusan final
            <b>Diterima</b> atau <b>Ditolak</b> untuk setiap calon peserta.
        </p>
    </div>
</div>

{{-- ===== TAB ===== --}}
<div class="tab-bar">
    @foreach ($daftarTab as $kunci => $label)
        <a class="tab {{ $tab === $kunci ? 'aktif' : '' }}"
           href="{{ route('direktur.penerimaan.index', array_filter(['tab' => $kunci, 'cari' => $cari])) }}">
            {{ $label }}
            @if ($kunci === 'menunggu' && $jumlahMenunggu > 0)
                <span class="badge-tab">{{ $jumlahMenunggu }}</span>
            @endif
        </a>
    @endforeach
</div>

{{-- ===== PENCARIAN ===== --}}
<form class="kartu filter-bar" method="GET" action="{{ route('direktur.penerimaan.index') }}">
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
               href="{{ route('direktur.penerimaan.index', ['tab' => $tab]) }}">
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
                        {{ $keputusan->total() }} calon ditemukan
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
                    <th>NIM / Kontak</th>
                    <th>Pengajuan</th>
                    <th>Jalur</th>
                    <th>Rekomendasi KaSi</th>
                    <th>KaSi / Waktu</th>
                    @if ($tab === 'riwayat')
                        <th>Keputusan Direktur</th>
                        <th>Waktu Keputusan</th>
                    @else
                        <th>Status</th>
                        <th class="aksi-tabel">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
            @forelse ($keputusan as $k)
                @php
                    $bagNama  = 'keputusan_' . $k->id_keputusan;
                    $bag      = $errors->getBag($bagNama);
                    $pakaiOld = $bag->any();

                    $c     = $k->calon;
                    $p     = $c?->pengajuan;
                    $kode  = $p?->kode_pengajuan ?? '—';
                    $jalur = $p?->jalur ?? '—';
                    $labelSumber = $jalur === 'INSTITUSI'
                        ? ($p?->nama_institusi ?? '—')
                        : ($p?->diajukanOleh?->nama_lengkap ?? '—');

                    $kelasRek = match ($k->rekomendasi) {
                        'Terima' => 'status-sukses',
                        'Tolak'  => 'status-gagal',
                        default  => 'status-netral',
                    };
                    $kelasKeputusan = match ($k->keputusan) {
                        'Diterima' => 'status-sukses',
                        'Ditolak'  => 'status-gagal',
                        default    => 'status-peringatan',
                    };
                    $modalId  = 'modal-kep-' . $k->id_keputusan;
                    $override = $k->keputusan !== null
                        && (($k->rekomendasi === 'Terima' && $k->keputusan === 'Ditolak')
                            || ($k->rekomendasi === 'Tolak' && $k->keputusan === 'Diterima'));
                @endphp
                <tr>
                    {{-- Calon --}}
                    <td>
                        <div class="sel-utama">
                            <span class="inisial">{{ mb_strtoupper(mb_substr($c?->nama_lengkap ?? '?', 0, 1)) }}</span>
                            <div>
                                <b>{{ $c?->nama_lengkap ?? '—' }}</b>
                                @if ($c?->jurusan_prodi)
                                    <small class="redup">{{ $c->jurusan_prodi }}</small>
                                @endif
                                @if ($c?->institusi_asal)
                                    <small class="redup">{{ $c->institusi_asal }}</small>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- NIM / Kontak --}}
                    <td>
                        @if ($c?->nomor_induk_pendidikan)
                            <span class="sel-ikon"><x-ikon nama="pengguna" />{{ $c->nomor_induk_pendidikan }}</span>
                        @endif
                        @if ($c?->email)
                            <small class="redup" style="display:block;padding-left:{{ $c->nomor_induk_pendidikan ? '20' : '0' }}px">{{ $c->email }}</small>
                        @elseif ($c?->no_hp)
                            <small class="redup" style="display:block;padding-left:{{ $c->nomor_induk_pendidikan ? '20' : '0' }}px">{{ $c->no_hp }}</small>
                        @endif
                        @if (!$c?->nomor_induk_pendidikan && !$c?->email && !$c?->no_hp)
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

                    {{-- Rekomendasi KaSi --}}
                    <td>
                        <span class="status {{ $kelasRek }}">{{ $k->rekomendasi }}</span>
                        @if ($k->catatan_rekomendasi)
                            <small class="redup" style="display:block;margin-top:4px;font-style:italic">
                                &ldquo;{{ Str::limit($k->catatan_rekomendasi, 70) }}&rdquo;
                            </small>
                        @endif
                        @if ($override)
                            <small style="color:var(--kuning-tua,#b45309);display:block;margin-top:4px;font-weight:600;font-size:11.5px">
                                ⚠ Berbeda dari rekomendasi
                            </small>
                        @endif
                    </td>

                    {{-- KaSi / Waktu Rekomendasi --}}
                    <td>
                        @if ($k->kepalaSeksi?->pengguna)
                            <div class="sel-ikon">
                                <x-ikon nama="pengguna" />
                                {{ $k->kepalaSeksi->pengguna->nama_lengkap }}
                            </div>
                        @endif
                        @if ($k->waktu_rekomendasi)
                            <small class="redup" style="display:block;margin-top:2px;padding-left:20px">
                                {{ $k->waktu_rekomendasi->translatedFormat('d M Y H:i') }}<br>
                                {{ $k->waktu_rekomendasi->diffForHumans() }}
                            </small>
                        @endif
                    </td>

                    {{-- Kolom kondisional --}}
                    @if ($tab === 'riwayat')
                        <td>
                            <span class="status {{ $kelasKeputusan }}">{{ $k->keputusan }}</span>
                            @if ($k->catatan_keputusan)
                                <small class="redup" style="display:block;margin-top:4px;font-style:italic">
                                    &ldquo;{{ Str::limit($k->catatan_keputusan, 70) }}&rdquo;
                                </small>
                            @endif
                            @if ($k->direktur?->pengguna)
                                <small class="redup" style="display:block;margin-top:4px">
                                    <x-ikon nama="pengguna" style="width:12px;height:12px;vertical-align:middle" />
                                    {{ $k->direktur->pengguna->nama_lengkap }}
                                </small>
                            @endif
                        </td>
                        <td>
                            @if ($k->waktu_keputusan)
                                {{ $k->waktu_keputusan->translatedFormat('d M Y H:i') }}
                                <small class="redup" style="display:block;margin-top:2px">
                                    {{ $k->waktu_keputusan->diffForHumans() }}
                                </small>
                            @else
                                <span class="redup">—</span>
                            @endif
                        </td>

                    @else
                        <td>
                            <span class="status status-peringatan">Menunggu Keputusan</span>
                        </td>
                        <td class="aksi-tabel">
                            <button type="button"
                                    class="btn btn-kecil"
                                    onclick="bukaPanel('{{ $modalId }}')"
                                    title="Beri keputusan untuk {{ $c?->nama_lengkap }}">
                                <x-ikon nama="papan-centang" /> Putuskan
                            </button>
                        </td>
                    @endif
                </tr>

                {{-- ── Panel keputusan inline ── --}}
                @if ($tab === 'menunggu')
                <tr class="baris-panel" id="baris-{{ $modalId }}"
                    style="{{ $pakaiOld ? '' : 'display:none' }}">
                    <td colspan="8" style="padding:0">
                        <div class="panel-keputusan">

                            {{-- Kepala --}}
                            <div class="panel-kep-kepala">
                                <span class="ikon-bulat kuning"><x-ikon nama="papan-centang" /></span>
                                <div>
                                    <b>Keputusan untuk {{ $c?->nama_lengkap }}</b>
                                    <small class="redup">
                                        {{ $kode }} &mdash; {{ $labelSumber }}
                                        &nbsp;&bull;&nbsp; Rekomendasi KaSi:
                                        <b class="{{ $k->rekomendasi === 'Terima' ? 'teks-sukses' : 'teks-gagal' }}">
                                            {{ $k->rekomendasi }}
                                        </b>
                                    </small>
                                </div>
                                <button type="button"
                                        class="btn-tutup-panel"
                                        onclick="tutupPanel('{{ $modalId }}')"
                                        title="Tutup">
                                    <x-ikon nama="silang" />
                                </button>
                            </div>

                            {{-- Ringkasan calon --}}
                            @if ($c)
                            <div class="panel-kep-info">
                                <div class="info-kotak">
                                    <x-ikon nama="pengguna" />
                                    <div style="font-size:13px;line-height:1.6">
                                        @if ($c->nomor_induk_pendidikan)
                                            <b>NIM/NIS:</b> {{ $c->nomor_induk_pendidikan }}&ensp;
                                        @endif
                                        @if ($c->jurusan_prodi)
                                            <b>Prodi:</b> {{ $c->jurusan_prodi }}&ensp;
                                        @endif
                                        @if ($c->email)
                                            <b>Email:</b> {{ $c->email }}
                                        @endif
                                        @if ($k->catatan_rekomendasi)
                                            <br><b>Catatan KaSi:</b>
                                            <i>&ldquo;{{ $k->catatan_rekomendasi }}&rdquo;</i>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif

                            {{-- Form keputusan (AKTIF) --}}
                            <div class="panel-kep-form">
                                @if ($pakaiOld && $bag->any())
                                    <div class="alert alert-gagal" style="margin-bottom:12px">
                                        <x-ikon nama="peringatan" />
                                        <ul style="margin:0;padding-left:16px">
                                            @foreach ($bag->all() as $e)
                                                <li>{{ $e }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <form method="POST"
                                      action="{{ route('direktur.penerimaan.update', $k) }}"
                                      id="form-{{ $modalId }}"
                                      onsubmit="return konfirmasiKeputusan(this, '{{ $modalId }}')">
                                    @csrf
                                    @method('PUT')

                                    <div class="panel-kep-fields">
                                        {{-- Pilih keputusan --}}
                                        <label>
                                            Keputusan <span style="color:var(--gagal)">*</span>
                                            <div class="pilih-keputusan" id="pilih-kep-{{ $modalId }}">
                                                @foreach (\App\Models\KeputusanPenerimaan::KEPUTUSAN as $kep)
                                                    <label class="opsi-kep opsi-{{ strtolower($kep) }}">
                                                        <input type="radio"
                                                               name="keputusan"
                                                               value="{{ $kep }}"
                                                               form="form-{{ $modalId }}"
                                                               @checked($pakaiOld && old('keputusan') === $kep)
                                                               onchange="hapusErrKep('{{ $modalId }}'); toggleCatatan('{{ $modalId }}', this.value)">
                                                        <span>
                                                            @if ($kep === 'Diterima')
                                                                <x-ikon nama="centang-saja" />
                                                            @else
                                                                <x-ikon nama="silang" />
                                                            @endif
                                                            {{ $kep }}
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <p class="err-pilih" id="err-kep-{{ $modalId }}"
                                               role="alert" aria-live="polite">
                                                Silakan pilih keputusan Diterima atau Ditolak terlebih dahulu.
                                            </p>
                                        </label>

                                        {{-- Catatan keputusan --}}
                                        <label id="label-catatan-{{ $modalId }}">
                                            Catatan
                                            <small class="redup" id="ket-catatan-{{ $modalId }}">
                                                {{ ($pakaiOld && old('keputusan') === 'Ditolak') ? '(wajib)' : '(opsional)' }}
                                            </small>
                                            <textarea name="catatan_keputusan"
                                                      form="form-{{ $modalId }}"
                                                      rows="3"
                                                      maxlength="2000"
                                                      id="textarea-catatan-{{ $modalId }}"
                                                      placeholder="Alasan atau catatan keputusan…">{{ $pakaiOld ? old('catatan_keputusan') : '' }}</textarea>
                                        </label>
                                    </div>

                                    <div class="panel-kep-kaki">
                                        <button type="button"
                                                class="btn btn-halus"
                                                onclick="tutupPanel('{{ $modalId }}')">
                                            Batal
                                        </button>
                                        <button type="submit"
                                                class="btn"
                                                form="form-{{ $modalId }}"
                                                id="submit-{{ $modalId }}">
                                            <x-ikon nama="centang-saja" /> Simpan Keputusan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @endif
            @empty
                <tr>
                    <td colspan="8">
                        <div class="kosong">
                            <div class="ilustrasi"><x-ikon nama="papan-centang" /></div>
                            @if ($cari !== '')
                                <b>Tidak ada calon yang cocok.</b>
                                <br>
                                <a class="btn btn-garis"
                                   href="{{ route('direktur.penerimaan.index', ['tab' => $tab]) }}"
                                   style="margin-top:14px">
                                    <x-ikon nama="atur-ulang" /> Atur ulang
                                </a>
                            @elseif ($tab === 'menunggu')
                                <b>Tidak ada rekomendasi yang menunggu keputusan.</b>
                                Semua rekomendasi Kepala Seksi sudah mendapat keputusan,
                                atau belum ada rekomendasi yang masuk.
                            @else
                                <b>Belum ada riwayat keputusan.</b>
                                Keputusan yang sudah Anda buat akan muncul di sini.
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($keputusan->hasPages())
        <div style="padding:12px 22px 16px;border-top:1px solid var(--garis)">
            {{ $keputusan->links() }}
        </div>
    @endif
</div>

<p class="petunjuk">
    <x-ikon nama="info" style="width:14px;height:14px;vertical-align:middle" />
    Direktur berwenang memutuskan berbeda dari rekomendasi KaSi.
    Calon yang <b>Diterima</b> harus diaktifkan akun pesertanya oleh Superadmin melalui menu
    <b>Aktivasi Peserta</b> sebelum memulai magang.
</p>

{{-- ===== STYLES ===== --}}
<style>
.panel-keputusan {
    background: var(--bg, #fafaf8);
    border-top: 2px solid var(--kuning-tua, #b45309);
    border-bottom: 2px solid var(--kuning-tua, #b45309);
    animation: geser-turun .18s ease;
}
@keyframes geser-turun {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.panel-kep-kepala {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 22px 10px;
    border-bottom: 1px solid var(--garis);
}
.panel-kep-kepala > div { flex: 1; }
.panel-kep-kepala > div b { display: block; font-size: 14px; }
.panel-kep-kepala > div small { font-size: 12px; }
.btn-tutup-panel {
    display: inline-flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; flex-shrink: 0;
    border: 1px solid var(--garis); border-radius: 6px;
    background: transparent; cursor: pointer; color: var(--redup);
    transition: background .15s, color .15s;
}
.btn-tutup-panel:hover { background: rgba(220,38,38,.08); color: var(--gagal); border-color: var(--gagal); }
.btn-tutup-panel .ikon { width: 14px; height: 14px; }
.panel-kep-info { padding: 12px 22px 0; }
.panel-kep-form { padding: 14px 22px 16px; }
.panel-kep-fields {
    display: grid; grid-template-columns: auto 1fr; gap: 16px;
    align-items: start;
}
@media (max-width: 680px) { .panel-kep-fields { grid-template-columns: 1fr; } }
.panel-kep-kaki {
    display: flex; justify-content: flex-end; gap: 10px;
    margin-top: 14px; padding-top: 12px;
    border-top: 1px solid var(--garis);
}
.pilih-keputusan { display: flex; gap: 10px; margin-top: 8px; }
.opsi-kep { cursor: pointer; }
.opsi-kep input[type="radio"] { display: none; }
.opsi-kep span {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 20px; border: 2px solid var(--garis, #d1d5db);
    border-radius: 8px; font-size: 14px; font-weight: 600;
    background: #fff; transition: border-color .15s, background .15s, color .15s;
    user-select: none;
}
.opsi-kep span .ikon { width: 16px; height: 16px; }
.opsi-kep.opsi-diterima input:checked + span {
    border-color: var(--utama, #166534);
    background: rgba(22,101,52,.08); color: var(--utama, #166534);
}
.opsi-kep.opsi-ditolak input:checked + span {
    border-color: var(--gagal, #dc2626);
    background: rgba(220,38,38,.07); color: var(--gagal, #dc2626);
}
.opsi-kep:hover span { border-color: var(--utama, #166534); }
.opsi-kep.opsi-ditolak:hover span { border-color: var(--gagal, #dc2626); }
.teks-sukses { color: var(--utama, #166534); }
.teks-gagal  { color: var(--gagal, #dc2626); }
.badge-tab {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 18px; height: 18px; padding: 0 5px;
    border-radius: 999px; font-size: 11px; font-weight: 700;
    background: var(--utama, #166534); color: #fff;
    margin-left: 6px; vertical-align: middle;
}
.tab.aktif .badge-tab { background: #fff; color: var(--utama, #166534); }

/* Pesan validasi pilihan keputusan */
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
.pilih-keputusan.err-aktif .opsi-kep span {
    border-color: var(--gagal, #dc2626);
}
</style>

{{-- ===== JAVASCRIPT ===== --}}
<script>
function bukaPanel(id) {
    document.querySelectorAll('.baris-panel').forEach(function (el) { el.style.display = 'none'; });
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
function toggleCatatan(id, nilai) {
    var ket = document.getElementById('ket-catatan-' + id);
    if (ket) ket.textContent = nilai === 'Ditolak' ? '(wajib)' : '(opsional)';
}

/** Hapus indikator error saat pengguna memilih opsi. */
function hapusErrKep(id) {
    var container = document.getElementById('pilih-kep-' + id);
    var err       = document.getElementById('err-kep-' + id);
    if (container) container.classList.remove('err-aktif');
    if (err)       err.style.display = 'none';
}

/** Validasi pilihan keputusan + konfirmasi sebelum submit. */
function konfirmasiKeputusan(form, id) {
    var radio = form.querySelector('input[name="keputusan"]:checked');
    if (!radio) {
        // Tampilkan pesan error — jangan biarkan submit berlangsung.
        var container = document.getElementById('pilih-kep-' + id);
        var err       = document.getElementById('err-kep-' + id);
        if (container) container.classList.add('err-aktif');
        if (err)       err.style.display = 'block';
        return false;
    }
    var nilai = radio.value;
    var nama  = (form.closest('tr').previousElementSibling
                   ?.querySelector('.sel-utama b')?.textContent?.trim()) || 'calon ini';
    var pesan = nilai === 'Diterima'
        ? 'Simpan keputusan DITERIMA untuk ' + nama + '?\n\nKeputusan ini tidak dapat diubah.'
        : 'Simpan keputusan DITOLAK untuk ' + nama + '?\n\nKeputusan ini tidak dapat diubah.';
    return confirm(pesan);
}
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.baris-panel').forEach(function (el) { el.style.display = 'none'; });
    }
});
</script>

@endsection
