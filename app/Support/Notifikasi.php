<?php

namespace App\Support;

use App\Models\Notifikasi as NotifikasiModel;

/** Helper membuat notifikasi lonceng. Contoh: Notifikasi::kirim($idPengguna, 'REVIEW_TUGAS', 'Progres 1 perlu perbaikan', 'progres_tugas', $progres->id_progres); */
final class Notifikasi
{
    public static function kirim(int $idPengguna, string $jenis, string $pesan, ?string $jenisEntitas = null, ?int $idEntitas = null): NotifikasiModel
    {
        return NotifikasiModel::create([
            'id_pengguna' => $idPengguna,
            'jenis' => $jenis,
            'pesan' => $pesan,
            'jenis_entitas' => $jenisEntitas,
            'id_entitas' => $idEntitas,
            'sudah_dibaca' => false,
        ]);
    }
}
