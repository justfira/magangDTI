<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Pemakaian di route: ->middleware('peran:SUPERADMIN,KEPALA_SEKSI'). Penolakan terjadi di sisi server (UC-17 E2). */
class CekPeran
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        abort_unless($request->user()?->punyaPeran(...$peran), 403, 'Anda tidak memiliki akses.');

        return $next($request);
    }
}
