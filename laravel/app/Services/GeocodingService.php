<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    public function geocode(string $address): ?array
    {
        if (! $this->isConfigured() || trim($address) === '') {
            return null;
        }

        try {
            $response = Http::timeout((int) config('services.geoapify.timeout', 10))
                ->acceptJson()
                ->get((string) config('services.geoapify.base_url'), [
                    'text' => $address,
                    'apiKey' => (string) config('services.geoapify.api_key'),
                ]);

            if ($response->failed()) {
                Log::warning('Geoapify geocoding request failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $payload = $response->json() ?? [];
            $first = $payload['features'][0] ?? null;
            if (! is_array($first)) {
                return null;
            }

            $lat = data_get($first, 'properties.lat');
            $lng = data_get($first, 'properties.lon');
            if (! is_numeric($lat) || ! is_numeric($lng)) {
                return null;
            }

            return [
                'formatted_address' => data_get($first, 'properties.formatted', $address),
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
                'place_id' => data_get($first, 'properties.place_id'),
            ];
        } catch (\Throwable $exception) {
            Log::warning('Geoapify geocoding request threw an exception.', [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c, 2);
    }

    private function isConfigured(): bool
    {
        return filled(config('services.geoapify.api_key'));
    }
}
