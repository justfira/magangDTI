<?php

namespace Database\Seeders;

use App\Models\Seksi;
use Illuminate\Database\Seeder;

/** Contoh seksi di DTI — sesuaikan dengan struktur organisasi DTI yang sebenarnya. */
class SeksiSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Pengembangan Sistem Informasi', 'Infrastruktur dan Jaringan', 'Layanan dan Dukungan TI'] as $nama) {
            Seksi::updateOrCreate(['nama_seksi' => $nama]);
        }
    }
}
