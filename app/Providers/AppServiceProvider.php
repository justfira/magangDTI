<?php

namespace App\Providers;

use App\Support\Peran;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Menu samping & jumlah notifikasi belum dibaca untuk layout
        View::composer('layouts.app', function ($view) {
            $pengguna = auth()->user();
            $kunci = $pengguna?->kodePeran();
            if ($kunci === Peran::PESERTA && ! $pengguna->pesertaMagangBerjalan()) {
                $kunci = 'CALON';
            }
            $view->with('menu', config("menu.$kunci", []));
            $view->with('jumlahNotifikasi', $pengguna ? $pengguna->notifikasi()->where('sudah_dibaca', false)->count() : 0);
        });
    }
}
