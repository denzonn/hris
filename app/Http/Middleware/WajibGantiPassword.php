<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Akun dengan password dari admin hanya boleh membuka halaman profil (ganti password) & logout. */
class WajibGantiPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->wajib_ganti_password || $request->routeIs('profile', 'profile.password', 'logout')) {
            return $next($request);
        }

        $pesan = 'Silakan ganti password Anda terlebih dahulu.';

        return $request->expectsJson()
            ? response()->json(['message' => $pesan], 403)
            : redirect()->route('profile')->with('peringatan', $pesan);
    }
}
