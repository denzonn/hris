<?php

namespace App\Http\Controllers;

use App\Http\Requests\GantiPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(Request $request): View
    {
        return view('pages.profile.index', [
            'user' => $request->user()->load(['otoritas', 'cabang:id,nama', 'divisi:id,nama', 'jabatan:id,nama']),
        ]);
    }

    public function password(GantiPasswordRequest $request): JsonResponse
    {
        $request->user()->update(['password' => $request->validated('password'), 'wajib_ganti_password' => false]); // cast 'hashed'

        return response()->json(['message' => 'Password berhasil diganti.']);
    }
}
