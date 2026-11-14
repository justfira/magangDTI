{{--
    Ambil lokasi perangkat sebelum form dikirim (UC-08 langkah 4–6, UC-09 langkah 6).
    Pakai: tambahkan atribut data-butuh-lokasi pada <form>, dan sertakan @include('peserta.presensi._lokasi').
    Catatan: browser hanya mengizinkan lokasi di HTTPS atau localhost/127.0.0.1.
--}}
<p class="pr-catatan-lokasi" id="info-lokasi" hidden></p>
<script>
(function () {
    const info = document.getElementById('info-lokasi');
    const tampil = (pesan) => { info.hidden = false; info.textContent = pesan; };

    document.querySelectorAll('form[data-butuh-lokasi]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (form.dataset.lokasiSiap === '1') return;   // lokasi sudah diisi -> kirim
            e.preventDefault();

            if (!window.isSecureContext || !navigator.geolocation) {
                tampil('Browser tidak mengizinkan akses lokasi. Buka sistem lewat HTTPS (atau http://127.0.0.1) lalu coba lagi.');
                return;
            }
            const tombol = form.querySelector('button');
            tombol.disabled = true;
            tampil('Mengambil lokasi Anda…');

            navigator.geolocation.getCurrentPosition((pos) => {
                const isi = { latitude: pos.coords.latitude, longitude: pos.coords.longitude, akurasi: pos.coords.accuracy };
                Object.entries(isi).forEach(([nama, nilai]) => {
                    let input = form.querySelector(`input[name="${nama}"]`);
                    if (!input) { input = document.createElement('input'); input.type = 'hidden'; input.name = nama; form.appendChild(input); }
                    input.value = nilai;
                });
                tampil(`Lokasi didapat (akurasi ± ${Math.round(pos.coords.accuracy)} m). Mengirim…`);
                form.dataset.lokasiSiap = '1';
                form.submit();
            }, (err) => {
                tombol.disabled = false;
                tampil(err.code === err.PERMISSION_DENIED
                    ? 'Lokasi diperlukan untuk melakukan presensi. Izinkan akses lokasi di browser Anda.'
                    : 'Lokasi tidak dapat diperoleh. Pastikan GPS/layanan lokasi aktif lalu coba lagi.');
            }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
        });
    });
})();
</script>
