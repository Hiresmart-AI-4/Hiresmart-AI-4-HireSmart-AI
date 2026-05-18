<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;

trait ConsumesExternalService
{
    public function performRequest(string $method, string $baseUri, string $path, array $data = [], ?string $token = null)
    {
        $request = Http::acceptJson();

        if ($token) {
            $request = $request->withToken(str_replace('Bearer ', '', $token));
        }

        return $request->send($method, rtrim($baseUri, '/') . $path, [
            'json' => $data,
        ])->json();
    }
}
