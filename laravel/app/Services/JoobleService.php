<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JoobleService
{
    public function searchJobs(array $filters): array
    {
        if (! $this->isConfigured()) {
            return [
                'provider' => 'jooble',
                'configured' => false,
                'jobs' => [],
                'raw' => [],
                'message' => 'Jooble API key is not configured.',
            ];
        }

        $endpoint = rtrim((string) config('services.jooble.base_url', 'https://jooble.org/api'), '/')
            . '/' . (string) config('services.jooble.api_key');

        $payload = [
            'keywords' => (string) ($filters['what'] ?? ''),
            'location' => (string) ($filters['where'] ?? ''),
            'page' => max(1, (int) ($filters['page'] ?? 1)),
        ];

        try {
            $response = Http::timeout((int) config('services.jooble.timeout', 20))
                ->acceptJson()
                ->post($endpoint, $payload);

            if ($response->failed()) {
                Log::warning('Jooble API request failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'provider' => 'jooble',
                    'configured' => true,
                    'jobs' => [],
                    'raw' => [],
                    'message' => 'Unable to fetch live jobs from Jooble.',
                ];
            }

            $responsePayload = $response->json() ?? [];
            $jobs = is_array($responsePayload['jobs'] ?? null) ? $responsePayload['jobs'] : [];
            $limit = max(1, min(50, (int) ($filters['results_per_page'] ?? 20)));

            return [
                'provider' => 'jooble',
                'configured' => true,
                'jobs' => array_slice(array_map([$this, 'normalizeJob'], $jobs), 0, $limit),
                'raw' => $responsePayload,
                'message' => null,
            ];
        } catch (\Throwable $exception) {
            Log::warning('Jooble API request threw an exception.', [
                'error' => $exception->getMessage(),
            ]);

            return [
                'provider' => 'jooble',
                'configured' => true,
                'jobs' => [],
                'raw' => [],
                'message' => 'Live jobs provider is currently unavailable.',
            ];
        }
    }

    private function normalizeJob(array $job): array
    {
        return [
            'external_id' => data_get($job, 'id'),
            'title' => data_get($job, 'title'),
            'company' => data_get($job, 'company'),
            'location' => data_get($job, 'location'),
            'salary_min' => null,
            'salary_max' => null,
            'description' => data_get($job, 'snippet'),
            'redirect_url' => data_get($job, 'link'),
            'latitude' => data_get($job, 'latitude'),
            'longitude' => data_get($job, 'longitude'),
            'created' => data_get($job, 'updated'),
            'contract_time' => null,
            'contract_type' => data_get($job, 'type'),
        ];
    }

    private function isConfigured(): bool
    {
        return filled(config('services.jooble.api_key'));
    }
}
