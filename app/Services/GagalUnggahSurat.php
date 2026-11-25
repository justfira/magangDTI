<?php

namespace App\Services;

use RuntimeException;

/** UC-04 E2 — berkas surat pengajuan gagal diunggah/disimpan (mis. koneksi terputus, disk penuh). */
class GagalUnggahSurat extends RuntimeException
{
}
