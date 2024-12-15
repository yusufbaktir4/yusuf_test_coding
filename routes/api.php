<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;

Route::post('get-token', [AuthController::class, 'getToken']);

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('/companies', [CompanyController::class, 'create']);
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::get('/companies/{id}', [CompanyController::class, 'detail']);
    Route::delete('/companies/{id}', [CompanyController::class, 'delete']);

    Route::post('/users', [UserController::class, 'create']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'detail']);
    Route::delete('/users/{id}', [UserController::class, 'delete']);
});

