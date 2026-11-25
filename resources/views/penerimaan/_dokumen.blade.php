{{--
    Checklist dokumen persyaratan (hanya-baca) dengan tautan "Lihat".
    Variabel: $checklist dari AlurPenerimaanService::checklistDokumen()
--}}
@php
    $ukuran = fn ($b) => $b ? ($b >= 1048576 ? number_format($b / 1048576, 1, ',', '.').' MB' : number_format(max(1, ceil($b / 1024)), 0, ',', '.').' KB') : null;
@endphp
@if ($checklist->isEmpty())
    <p class="redup kecil">Belum ada dokumen yang diunggah.</p>
@else
    <ul class="pn-dok">
        @foreach ($checklist as $baris)
            @php $d = $baris['dokumen']; @endphp
            <li class="pn-dok-baris">
                <div class="pn-dok-atas">
                    <span class="pn-dok-ikon {{ $d ? '' : 'kosong' }}">{{ $d ? strtoupper(pathinfo($d->nama_berkas, PATHINFO_EXTENSION) ?: 'DOK') : '—' }}</span>
                    <div class="pn-dok-nama">
                        <b>{{ $baris['nama'] }}@if ($baris['wajib']) <span class="wajib">*</span>@endif</b>
                        <small>{{ $d ? $d->nama_berkas.($ukuran($d->ukuran_bytes) ? ' · '.$ukuran($d->ukuran_bytes) : '') : 'Belum diunggah' }}</small>
                    </div>
                    @if ($d?->hasil_pemeriksaan)
                        <span class="status {{ $d->hasil_pemeriksaan === 'Sesuai' ? 'status-sukses' : 'status-gagal' }}">{{ $d->hasil_pemeriksaan }}</span>
                    @endif
                    @if ($d)
                        <a class="btn btn-garis btn-kecil" href="{{ route('pengajuan.dokumen', $d) }}" target="_blank" rel="noopener"><x-ikon nama="mata" /> Lihat</a>
                    @endif
                </div>
                @if ($d?->catatan_pemeriksaan)
                    <p class="pn-dok-catatan">{{ $d->catatan_pemeriksaan }}</p>
                @endif
            </li>
        @endforeach
    </ul>
@endif
