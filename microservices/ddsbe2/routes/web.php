<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->get('/health', function () {
    return response()->json([
        'service' => 'ddsbe2',
        'name' => 'HireSmart User Service 2',
        'status' => 'online',
    ]);
});

$router->group(['prefix' => 'api', 'middleware' => 'auth'], function () use ($router) {
    $router->get('/users/profile', 'UserServiceTwoController@profile');
    $router->put('/users/profile', 'UserServiceTwoController@updateProfile');
    $router->post('/logout', 'UserServiceTwoController@logout');
});
