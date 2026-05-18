<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserServiceOne
{
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? 'job_seeker',
        ]);

        return [
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $user->createToken('postman')->plainTextToken,
        ];
    }

    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return [
            'message' => 'Login successful',
            'user' => $user,
            'token' => $user->createToken('postman')->plainTextToken,
        ];
    }
}
