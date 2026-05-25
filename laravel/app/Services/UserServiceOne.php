<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserServiceOne
{
    public function __construct(private EmailValidationService $emailValidationService)
    {
    }

    public function register(array $data): array
    {
        $emailValidation = $this->emailValidationService->validate((string) $data['email']);
        if ($this->emailValidationService->shouldBlock($emailValidation)) {
            throw ValidationException::withMessages([
                'email' => ['Email validation failed: ' . ($emailValidation['reason'] ?? 'Invalid email address.')],
            ]);
        }

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
            'email_validation' => $emailValidation,
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
