<?php

namespace Database\Seeders;

use App\Models\Peran;
use Illuminate\Database\Seeder;

/** 5 peran sesuai A2 bagian 2.1. */
class PeranSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['SUPERADMIN', 'Superadmin'],
            ['MENTOR', 'Mentor DTI'],
            ['KEPALA_SEKSI', 'Kepala Seksi DTI'],
            ['DIREKTUR', 'Direktur DTI'],
            ['PESERTA', 'Calon/Peserta Magang'],
        ] as [$kode, $nama]) {
            Peran::updateOrCreate(['kode_peran' => $kode], ['nama_peran' => $nama]);
        }
    }
}
