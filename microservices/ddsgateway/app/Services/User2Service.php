<?php

namespace App\Services;

use App\Traits\ConsumesExternalService;

class User2Service
{
    use ConsumesExternalService;

    public function profile(?string $token)
    {
        return $this->performRequest('GET', config('services.users2.base_uri'), '/api/users/profile', [], $token);
    }

    public function updateProfile(array $data, ?string $token)
    {
        return $this->performRequest('PUT', config('services.users2.base_uri'), '/api/users/profile', $data, $token);
    }

    public function logout(?string $token)
    {
        return $this->performRequest('POST', config('services.users2.base_uri'), '/api/logout', [], $token);
    }
}
