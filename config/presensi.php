<?php

/**
 * Parameter geofence kantor DTI (pengganti tabel lokasi_kantor — kantor hanya satu).
 * Nilai radius & akurasi masih menunggu konfirmasi DTI; ubah di file .env.
 */
return [
    'kantor' => [
        'nama' => env('KANTOR_NAMA', 'Kantor DTI Universitas Andalas'),
        'latitude' => env('KANTOR_LAT', -0.9147),
        'longitude' => env('KANTOR_LONG', 100.4583),
        'radius_meter' => env('KANTOR_RADIUS_METER', 100),
        'batas_akurasi_meter' => env('KANTOR_BATAS_AKURASI_METER', 50),
    ],
];
