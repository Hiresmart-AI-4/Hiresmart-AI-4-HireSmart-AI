<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class NotificationService
{
    public function sendEmail(string $toEmail, string $subject, string $message, ?string $toName = null): array
    {
        $apiKey = (string) config('services.brevo.api_key');
        if (! filled($apiKey)) {
            throw ValidationException::withMessages([
                'brevo' => ['Brevo API key is missing.'],
            ]);
        }

        $fromEmail = (string) config('services.brevo.from_email');
        $fromName = (string) config('services.brevo.from_name');
        $endpoint = rtrim((string) config('services.brevo.base_url', 'https://api.brevo.com/v3'), '/') . '/smtp/email';

        $payload = [
            'sender' => [
                'email' => $fromEmail,
                'name' => $fromName,
            ],
            'to' => [[
                'email' => $toEmail,
                'name' => $toName,
            ]],
            'subject' => $subject,
            'textContent' => $message,
        ];

        $response = Http::timeout((int) config('services.brevo.timeout', 15))
            ->withHeaders(['api-key' => $apiKey])
            ->acceptJson()
            ->post($endpoint, $payload);

        if ($response->failed()) {
            Log::warning('Brevo notification request failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw ValidationException::withMessages([
                'brevo' => ['Failed to send email notification.'],
            ]);
        }

        return [
            'provider' => 'brevo',
            'status' => 'sent',
            'message' => 'Email notification sent successfully.',
            'provider_response' => $response->json(),
        ];
    }
}
