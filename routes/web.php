<?php

use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\SpaAuthController;
use Illuminate\Support\Facades\Route;

Route::post('/forgot-password', [PasswordResetController::class, 'send'])->middleware('throttle:5,1');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1');

Route::post('/login', [SpaAuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/register', [SpaAuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/logout', [SpaAuthController::class, 'logout'])->middleware('auth');
Route::view('/{path?}', 'spa')->where('path', '^(?!api|sanctum|up)(?:.*)$');
