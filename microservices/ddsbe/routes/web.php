<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->get('/health', function () {
    return response()->json([
        'service' => 'ddsbe',
        'name' => 'HireSmart User Service 1',
        'status' => 'online',
    ]);
});

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('/register', 'UserServiceOneController@register');
    $router->post('/login', 'UserServiceOneController@login');
});
