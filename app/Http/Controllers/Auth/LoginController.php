<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * UC-01 Melakukan Login dan Logout — SUDAH DIIMPLEMENTASIKAN.
 * Catatan login/logout ditulis ke file log (storage/logs/aktivitas-*.log), bukan ke database.
 */
class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        // A1 – input tidak lengkap
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Email dan password wajib diisi.',
            'password.required' => 'Email dan password wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $pengguna = Pengguna::with('peran')->where('email', $data['email'])->first();

        // A2 – kredensial salah (password dibandingkan dengan hash)
        if (! $pengguna || ! Hash::check($data['password'], $pengguna->password_hash)) {
            Log::channel('aktivitas')->warning('login_gagal', ['email' => $data['email'], 'ip' => $request->ip()]);

            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        // A3 – akun nonaktif
        if (! $pengguna->isAktif()) {
            return back()->withErrors(['email' => 'Akun nonaktif, hubungi Superadmin.'])->onlyInput('email');
        }

        Auth::login($pengguna);
        $request->session()->regenerate();
        Log::channel('aktivitas')->info('login', ['id_pengguna' => $pengguna->id_pengguna, 'peran' => $pengguna->kodePeran(), 'ip' => $request->ip()]);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Log::channel('aktivitas')->info('logout', ['id_pengguna' => $request->user()?->id_pengguna]);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda sudah keluar.');
    }
}
