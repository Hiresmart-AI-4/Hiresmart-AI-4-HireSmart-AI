<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function index()
    {
        return response()->json([
            'notifications' => [],
        ]);
    }

    public function sendEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to_email' => ['required', 'email'],
            'to_name' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $payload = $validator->validated();
        $result = $this->notificationService->sendEmail(
            (string) $payload['to_email'],
            (string) $payload['subject'],
            (string) $payload['message'],
            $payload['to_name'] ?? null
        );

        return response()->json([
            'message' => 'Notification request completed.',
            'result' => $result,
        ], 201);
    }
}
