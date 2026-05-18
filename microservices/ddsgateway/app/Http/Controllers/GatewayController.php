<?php

namespace App\Http\Controllers;

use App\Services\User1Service;
use App\Services\User2Service;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    public function __construct(
        private readonly User1Service $user1Service,
        private readonly User2Service $user2Service
    ) {
    }

    public function routes()
    {
        return response()->json([
            'gateway' => 'ddsgateway',
            'site1' => [
                'POST /api/site1/register -> ddsbe /api/register',
                'POST /api/site1/login -> ddsbe /api/login',
            ],
            'site2' => [
                'GET /api/site2/users/profile -> ddsbe2 /api/users/profile',
                'PUT /api/site2/users/profile -> ddsbe2 /api/users/profile',
                'POST /api/site2/logout -> ddsbe2 /api/logout',
            ],
        ]);
    }

    public function registerViaServiceOne(Request $request)
    {
        return response()->json($this->user1Service->register($request->all()), 201);
    }

    public function loginViaServiceOne(Request $request)
    {
        return response()->json($this->user1Service->login($request->all()));
    }

    public function profileViaServiceTwo(Request $request)
    {
        return response()->json($this->user2Service->profile($request->header('Authorization')));
    }

    public function updateProfileViaServiceTwo(Request $request)
    {
        return response()->json($this->user2Service->updateProfile($request->all(), $request->header('Authorization')));
    }

    public function logoutViaServiceTwo(Request $request)
    {
        return response()->json($this->user2Service->logout($request->header('Authorization')));
    }
}
