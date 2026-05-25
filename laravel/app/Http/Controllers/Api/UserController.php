<?php

namespace App\Http\Controllers\Api;

use App\Gateways\ApiGateway;
use App\Http\Controllers\Controller;
use App\Services\EmailValidationService;
use App\Services\UserServiceOne;
use App\Services\UserServiceTwo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct(
        private ApiGateway $gateway,
        private UserServiceOne $userServiceOne,
        private UserServiceTwo $userServiceTwo,
        private EmailValidationService $emailValidationService
    ) {
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['sometimes', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->gateway->validationErrors($validator->errors());
        }

        return $this->gateway->success(
            $this->userServiceOne->register($validator->validated()),
            201
        );
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->gateway->validationErrors($validator->errors());
        }

        return $this->gateway->success(
            $this->userServiceOne->login($validator->validated())
        );
    }

    public function validateEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return $this->gateway->validationErrors($validator->errors());
        }

        $result = $this->emailValidationService->validate((string) $validator->validated()['email']);

        return $this->gateway->success([
            'message' => 'Email verification completed.',
            'result' => $result,
            'is_blocked' => $this->emailValidationService->shouldBlock($result),
        ]);
    }

    public function profile(Request $request)
    {
        return $this->gateway->success([
            'user' => $this->userServiceTwo->profile($request->user()),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bio' => ['sometimes', 'nullable', 'string'],
            'profile_picture' => ['sometimes', 'nullable', 'string', 'max:255'],
            'preferences' => ['sometimes', 'nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return $this->gateway->validationErrors($validator->errors());
        }

        return $this->gateway->success([
            'message' => 'Profile updated',
            'user' => $this->userServiceTwo->updateProfile($request->user(), $validator->validated()),
        ]);
    }

    public function logout(Request $request)
    {
        $this->userServiceTwo->logout($request->user());

        return $this->gateway->success([
            'message' => 'Logged out successfully',
        ]);
    }
}
