<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Lonceng notifikasi — SUDAH DIIMPLEMENTASIKAN. Buat notifikasi lewat App\Support\Notifikasi::kirim(). */
class NotifikasiController extends Controller
{
    public function index(Request $request): View
    {
        $notifikasi = $request->user()->notifikasi()->latest('id_notifikasi')->paginate(15);

        return view('notifikasi.index', compact('notifikasi'));
    }

    public function baca(Request $request, Notifikasi $notifikasi): RedirectResponse
    {
        abort_unless($notifikasi->id_pengguna === $request->user()->id_pengguna, 403);
        $notifikasi->update(['sudah_dibaca' => true]);

        return back();
    }

    public function bacaSemua(Request $request): RedirectResponse
    {
        $request->user()->notifikasi()->where('sudah_dibaca', false)->update(['sudah_dibaca' => true]);

        return back()->with('sukses', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
