<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PeranSeeder::class,
            SeksiSeeder::class,
            DemoSeeder::class,   // data contoh untuk mencoba sistem — hapus sebelum dipakai DTI
        ]);
    }
}
