<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserServiceTwoController extends Controller
{
    public function profile(Request $request)
    {
        return response()->json([
            'service' => 'ddsbe2',
            'action' => 'profile',
            'message' => 'Forward this to the main HireSmart profile logic.',
        ]);
    }

    public function updateProfile(Request $request)
    {
        return response()->json([
            'service' => 'ddsbe2',
            'action' => 'update-profile',
            'message' => 'Forward this to the main HireSmart profile update logic.',
            'payload' => $request->only(['name', 'phone', 'location', 'bio']),
        ]);
    }

    public function logout(Request $request)
    {
        return response()->json([
            'service' => 'ddsbe2',
            'action' => 'logout',
            'message' => 'Forward this to the main HireSmart logout logic.',
        ]);
    }
}
