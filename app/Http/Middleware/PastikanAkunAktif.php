<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Jika akun dinonaktifkan Superadmin saat masih login, sesi langsung diakhiri (UC-17 A4). */
class PastikanAkunAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->isAktif()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Akun nonaktif, hubungi Superadmin.']);
        }

        return $next($request);
    }
}
