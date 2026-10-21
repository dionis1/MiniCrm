<?php

use App\Http\Controllers\Api\V1\LeadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('v1')->group(function (): void {
    Route::apiResource('leads', LeadController::class);
    Route::patch('/leads/{lead}/stage', [LeadController::class, 'stage']);
    Route::post('/leads/{lead}/convert', [LeadController::class, 'convert']);
    Route::get('/leads/{lead}/activities', [LeadController::class, 'activities']);
    Route::get('/me', fn (Request $request) => response()->json([
        'data' => ['id' => $request->user()->id, 'name' => $request->user()->name, 'email' => $request->user()->email],
    ]));
});
