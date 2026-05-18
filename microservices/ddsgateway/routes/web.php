<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->get('/health', function () {
    return response()->json([
        'service' => 'ddsgateway',
        'name' => 'HireSmart API Gateway',
        'status' => 'online',
    ]);
});

$router->get('/api/gateway/routes', 'GatewayController@routes');

$router->group(['prefix' => 'api/site1'], function () use ($router) {
    $router->post('/register', 'GatewayController@registerViaServiceOne');
    $router->post('/login', 'GatewayController@loginViaServiceOne');
});

$router->group(['prefix' => 'api/site2', 'middleware' => 'auth'], function () use ($router) {
    $router->get('/users/profile', 'GatewayController@profileViaServiceTwo');
    $router->put('/users/profile', 'GatewayController@updateProfileViaServiceTwo');
    $router->post('/logout', 'GatewayController@logoutViaServiceTwo');
});
