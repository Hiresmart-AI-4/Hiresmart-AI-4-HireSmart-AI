<?php

namespace App\Gateways;

use Illuminate\Http\JsonResponse;

class ApiGateway
{
    public function success(array $payload, int $status = 200): JsonResponse
    {
        return response()->json(array_merge([
            'success' => true,
            'gateway' => 'HireSmart API Gateway',
        ], $payload), $status);
    }

    public function validationErrors($errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'gateway' => 'HireSmart API Gateway',
            'errors' => $errors,
        ], 422);
    }

    public function routeMap(): array
    {
        return [
            'ddsbe_user_service_one' => [
                'folder' => 'microservices/ddsbe',
                'POST /api/auth/register',
                'POST /api/auth/login',
                'POST /api/auth/validate-email',
                'POST /api/site1/register',
                'POST /api/site1/login',
                'POST /api/site1/validate-email',
            ],
            'ddsbe2_user_service_two' => [
                'folder' => 'microservices/ddsbe2',
                'GET /api/users/profile',
                'PUT /api/users/profile',
                'POST /api/auth/logout',
                'GET /api/site2/users/profile',
                'PUT /api/site2/users/profile',
                'POST /api/site2/logout',
            ],
            'ddsgateway' => [
                'folder' => 'microservices/ddsgateway',
                'GET /api/gateway/routes',
                'POST /api/site1/register -> ddsbe',
                'POST /api/site1/login -> ddsbe',
                'GET /api/site2/users/profile -> ddsbe2',
            ],
            'resume_analysis_service' => [
                'POST /api/resumes/upload',
                'POST /api/analyze',
            ],
            'job_matching_service' => [
                'POST /api/jobs/{id}/match',
                'GET /api/jobs/live',
                'GET /api/geo/geocode',
            ],
        ];
    }
}
