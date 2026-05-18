<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserServiceOneController extends Controller
{
    public function register(Request $request)
    {
        return response()->json([
            'service' => 'ddsbe',
            'action' => 'register',
            'message' => 'Forward this to the main HireSmart register logic.',
            'payload' => $request->only(['name', 'email']),
        ], 201);
    }

    public function login(Request $request)
    {
        return response()->json([
            'service' => 'ddsbe',
            'action' => 'login',
            'message' => 'Forward this to the main HireSmart login logic.',
            'payload' => $request->only(['email']),
        ]);
    }
}
