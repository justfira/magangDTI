<?php

namespace Database\Seeders;

use App\Models\Pegawai;
use App\Models\Pengguna;
use App\Models\Peran;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun Superadmin awal (untuk produksi, tanpa data demo).
 * Isi di .env lalu jalankan:  php artisan db:seed --class=SuperadminSeeder
 *   SUPERADMIN_EMAIL=admin@unand.ac.id
 *   SUPERADMIN_PASSWORD=<kata sandi kuat, minimal 8 karakter>
 *   SUPERADMIN_NAMA="Superadmin DTI"      (opsional)
 * Superadmin inilah yang kemudian membuat akun Mentor, Kepala Seksi, dan Direktur lewat menu Manajemen Akun.
 * Aman dijalankan berulang: akun dengan email yang sama hanya diperbarui, tidak digandakan.
 */
class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPERADMIN_EMAIL');
        $password = env('SUPERADMIN_PASSWORD');

        if (! $email || ! $password || strlen($password) < 8) {
            $this->command?->warn('SuperadminSeeder dilewati: isi SUPERADMIN_EMAIL dan SUPERADMIN_PASSWORD (min. 8 karakter) di .env.');

            return;
        }

        $peran = Peran::firstOrCreate(['kode_peran' => 'SUPERADMIN'], ['nama_peran' => 'Superadmin']);

        $pengguna = Pengguna::updateOrCreate(['email' => $email], [
            'id_peran' => $peran->id_peran,
            'nama_lengkap' => env('SUPERADMIN_NAMA', 'Superadmin DTI'),
            'password_hash' => Hash::make($password),
            'status_akun' => 'Aktif',
        ]);
        Pegawai::firstOrCreate(['id_pengguna' => $pengguna->id_pengguna], ['id_seksi' => null]);

        $this->command?->info("Superadmin siap: {$email}");
    }
}
