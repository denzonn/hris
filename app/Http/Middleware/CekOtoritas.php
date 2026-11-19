<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Pemakaian: ->middleware('otoritas:1,2') */
class CekOtoritas
{
    public function handle(Request $request, Closure $next, string ...$otoritas): Response
    {
        abort_unless(in_array((string) $request->user()?->otoritas_id, $otoritas, true), 403);

        return $next($request);
    }
}
