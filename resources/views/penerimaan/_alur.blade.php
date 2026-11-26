{{-- Timeline alur persetujuan UC-05. Variabel: $langkah dari AlurPenerimaanService::langkahAlur() --}}
<ol class="pn-alur">
    @foreach ($langkah as $l)
        <li class="{{ $l['keadaan'] }}"><b>{{ $l['judul'] }}</b><span>{{ $l['ket'] }}</span></li>
    @endforeach
</ol>
