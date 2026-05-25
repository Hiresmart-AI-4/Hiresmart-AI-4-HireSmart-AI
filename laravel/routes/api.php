<?php

use App\Gateways\ApiGateway;
use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\ResumeController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

Route::post('/debug/400', function (Request $request) {
    if (! $request->isJson()) {
        return response()->json([
            'message' => 'Bad Request: Content-Type application/json is required.',
        ], 400);
    }

    $rawBody = $request->getContent();

    if ($rawBody !== '' && json_decode($rawBody, true) === null && json_last_error() !== JSON_ERROR_NONE) {
        return response()->json([
            'message' => 'Bad Request: malformed JSON body.',
        ], 400);
    }

    return response()->json([
        'message' => 'Request is valid JSON.',
    ]);
});

Route::get('/debug/401', function () {
    return response()->json([
        'message' => 'Unauthenticated.',
    ], 401);
});

Route::match(['GET', 'POST', 'PUT', 'DELETE'], '/debug/422/{example?}', function (?string $example = null) {
    $errors = match ($example) {
        'duplicate-email' => ['email' => ['The email has already been taken.']],
        'invalid-credentials' => ['email' => ['The provided credentials are incorrect.']],
        'missing-password' => ['password' => ['The password field is required.']],
        'invalid-file' => ['resume' => ['The resume field must be a file of type: pdf, docx.']],
        default => ['field' => ['The field is required or invalid.']],
    };

    return response()->json([
        'errors' => $errors,
    ], 422);
});

Route::get('/debug/403', function () {
    return response()->json([
        'message' => 'Unauthorized',
    ], 403);
});

Route::get('/debug/404', function () {
    return response()->json([
        'message' => 'No query results for model.',
    ], 404);
});

Route::get('/debug/500', function () {
    return response()->json([
        'message' => 'Server error. Check Laravel logs and .env configuration.',
    ], 500);
});

Route::get('/gateway/routes', function (ApiGateway $gateway) {
    return $gateway->success([
        'routes' => $gateway->routeMap(),
    ]);
});

Route::post('/auth/register', [UserController::class, 'register']);
Route::post('/auth/login', [UserController::class, 'login']);
Route::post('/auth/validate-email', [UserController::class, 'validateEmail']);

Route::prefix('site1')->group(function () {
    Route::post('/register', [UserController::class, 'register']);
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/validate-email', [UserController::class, 'validateEmail']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users/profile', [UserController::class, 'profile']);
    Route::put('/users/profile', [UserController::class, 'updateProfile']);
    Route::post('/auth/logout', [UserController::class, 'logout']);

    Route::prefix('site2')->group(function () {
        Route::get('/users/profile', [UserController::class, 'profile']);
        Route::put('/users/profile', [UserController::class, 'updateProfile']);
        Route::post('/logout', [UserController::class, 'logout']);
    });

    Route::post('/resumes/upload', [ResumeController::class, 'upload']);
    Route::get('/resumes', [ResumeController::class, 'index']);
    Route::get('/resumes/compare/{originalId}/{improvedId}', [ResumeController::class, 'compare']);
    Route::get('/resumes/{id}', [ResumeController::class, 'show']);
    Route::put('/resumes/{id}', [ResumeController::class, 'update']);
    Route::delete('/resumes/{id}', [ResumeController::class, 'destroy']);
    Route::post('/resumes/{id}/activate', [ResumeController::class, 'activate']);
    Route::get('/resumes/{id}/download', [ResumeController::class, 'download']);

    Route::post('/analyze', [AnalysisController::class, 'analyze']);
    Route::get('/analysis/dashboard', [AnalysisController::class, 'dashboard']);
    Route::get('/analysis/{resumeId}', [AnalysisController::class, 'show']);

    Route::get('/recommendations/resumes/{resumeId}', [RecommendationController::class, 'resume']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/email', [NotificationController::class, 'sendEmail']);

    Route::get('/geo/geocode', [JobController::class, 'geocode']);
    Route::get('/jobs/live', [JobController::class, 'live']);

    Route::get('/jobs', [JobController::class, 'index']);
    Route::post('/jobs', [JobController::class, 'store']);
    Route::get('/jobs/{id}', [JobController::class, 'show']);
    Route::put('/jobs/{id}', [JobController::class, 'update']);
    Route::delete('/jobs/{id}', [JobController::class, 'destroy']);
    Route::post('/jobs/{id}/match', [JobController::class, 'match']);
});

Route::get('/ping', function() {
    return response()->json([
        'message' => 'pong',
        'timestamp' => now()
    ]);
});
