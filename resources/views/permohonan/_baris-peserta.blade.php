{{-- Satu baris peserta. $i = indeks (angka) atau '__I__' untuk templat JS; $b = nilai lama. --}}
@php
    $kolomPeserta = [
        'nama_lengkap' => ['Nama lengkap', 'text', 'mis. Rani Putri', 150, true],
        'nomor_induk_pendidikan' => ['NIM/NIS', 'text', 'mis. 2311521001', 40, true],
        'jurusan_prodi' => ['Jurusan/Prodi', 'text', 'mis. Sistem Informasi', 150, true],
        'email' => ['Email', 'email', 'nama@kampus.ac.id', 191, false],
        'no_hp' => ['No. HP/WA', 'tel', '08xxxxxxxxxx', 20, false],
    ];
@endphp
<tr data-baris>
    <td class="tp-no" data-no>{{ is_int($i) ? $i + 1 : '' }}</td>
    @foreach ($kolomPeserta as $kolom => [$label, $tipe, $contoh, $maks, $wajib])
        @php $kunci = "peserta.$i.$kolom"; @endphp
        <td data-label="{{ $label }}{{ $wajib ? ' *' : '' }}">
            <input type="{{ $tipe }}" name="peserta[{{ $i }}][{{ $kolom }}]" value="{{ $b[$kolom] ?? '' }}"
                   maxlength="{{ $maks }}" placeholder="{{ $contoh }}" aria-label="{{ $label }}"
                   @if ($tipe === 'tel') inputmode="tel" @endif
                   @class(['salah' => $errors->has($kunci)])>
            @error($kunci)<span class="pesan-salah">{{ $message }}</span>@enderror
        </td>
    @endforeach
    <td class="tp-aksi">
        <button type="button" class="tp-hapus" data-hapus-baris aria-label="Hapus baris peserta" title="Hapus baris">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </td>
</tr>
