<?php

namespace App\Services;

use App\Traits\ConsumesExternalService;

class User1Service
{
    use ConsumesExternalService;

    public function register(array $data)
    {
        return $this->performRequest('POST', config('services.users1.base_uri'), '/api/register', $data);
    }

    public function login(array $data)
    {
        return $this->performRequest('POST', config('services.users1.base_uri'), '/api/login', $data);
    }
}
