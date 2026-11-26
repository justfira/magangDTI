{{--
    Tabel data peserta (bisa lebih dari satu) — dipakai form Superadmin & form publik institusi.
    Variabel: $maksPeserta (int), opsional $pesertaAwal (array baris) untuk form perbaikan
    Baris tanpa isi sama sekali diabaikan server, jadi aman bila ada baris kosong tersisa.
--}}
@php
    $barisLama = old('peserta', $pesertaAwal ?? null);   // $pesertaAwal: isi awal saat form perbaikan
    $barisLama = is_array($barisLama) && $barisLama !== [] ? array_values($barisLama) : [[]];
@endphp
<div class="tp" data-tabel-peserta data-maks="{{ $maksPeserta }}">
    <div class="tp-gulir">
        <table class="tp-tabel">
            <thead>
                <tr>
                    <th class="tp-no" scope="col">#</th>
                    <th scope="col">Nama lengkap <span class="wajib">*</span></th>
                    <th scope="col">NIM/NIS <span class="wajib">*</span></th>
                    <th scope="col">Jurusan/Prodi <span class="wajib">*</span></th>
                    <th scope="col">Email</th>
                    <th scope="col">No. HP/WA</th>
                    <th class="tp-aksi" scope="col"><span class="sr-only">Aksi</span></th>
                </tr>
            </thead>
            <tbody data-wadah-baris>
                @foreach ($barisLama as $i => $b)
                    @include('permohonan._baris-peserta', ['i' => $i, 'b' => is_array($b) ? $b : []])
                @endforeach
            </tbody>
        </table>
    </div>
    <template data-templat-baris>
        @include('permohonan._baris-peserta', ['i' => '__I__', 'b' => []])
    </template>
    <div class="tp-kaki">
        <button type="button" class="btn btn-garis btn-kecil" data-tambah-baris><x-ikon nama="tambah-orang" /> Tambah peserta</button>
        <span class="tp-hitung" data-hitung aria-live="polite"></span>
    </div>
    @error('peserta')<span class="pesan-salah">{{ $message }}</span>@enderror
</div>

@once
<script>
/* Tabel peserta dinamis: tambah / hapus baris tanpa framework. */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-tabel-peserta]').forEach(function (tp) {
        var wadah = tp.querySelector('[data-wadah-baris]');
        var templat = tp.querySelector('[data-templat-baris]');
        var tombolTambah = tp.querySelector('[data-tambah-baris]');
        var hitung = tp.querySelector('[data-hitung]');
        var maks = parseInt(tp.dataset.maks || '20', 10);
        var indeks = wadah.querySelectorAll('[data-baris]').length;

        function segarkan() {
            var baris = wadah.querySelectorAll('[data-baris]');
            baris.forEach(function (tr, n) { tr.querySelector('[data-no]').textContent = n + 1; });
            tombolTambah.disabled = baris.length >= maks;
            hitung.textContent = baris.length + ' dari maks. ' + maks + ' peserta';
        }

        tombolTambah.addEventListener('click', function () {
            if (wadah.querySelectorAll('[data-baris]').length >= maks) { return; }
            var html = templat.innerHTML.replace(/__I__/g, String(indeks++));
            wadah.insertAdjacentHTML('beforeend', html.trim());
            segarkan();
            var baru = wadah.lastElementChild.querySelector('input');
            if (baru) { baru.focus(); }
        });

        wadah.addEventListener('click', function (e) {
            var tombol = e.target.closest('[data-hapus-baris]');
            if (! tombol) { return; }
            var tr = tombol.closest('[data-baris]');
            if (wadah.querySelectorAll('[data-baris]').length === 1) {
                tr.querySelectorAll('input').forEach(function (inp) { inp.value = ''; });   // sisakan satu baris kosong
            } else {
                tr.remove();
            }
            segarkan();
        });

        segarkan();
    });

    /* Peringatan ukuran/format berkas surat sebelum dikirim (validasi tetap dilakukan di server). */
    document.querySelectorAll('[data-cek-berkas]').forEach(function (inp) {
        var info = document.getElementById(inp.dataset.cekBerkas);
        var maksKb = parseInt(inp.dataset.maksKb || '2048', 10);
        inp.addEventListener('change', function () {
            var f = inp.files && inp.files[0];
            if (! f || ! info) { return; }
            var kb = Math.round(f.size / 1024);
            var ok = /\.(pdf|jpe?g|png)$/i.test(f.name) && kb <= maksKb;
            info.textContent = f.name + ' · ' + (kb >= 1024 ? (kb / 1024).toFixed(1) + ' MB' : kb + ' KB')
                + (ok ? '' : ' — format harus PDF/JPG/PNG dan maksimal ' + (maksKb / 1024) + ' MB');
            info.classList.toggle('pesan-salah', ! ok);
            inp.classList.toggle('salah', ! ok);
        });
    });
});
</script>
@endonce
