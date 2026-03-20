<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['api.token', 'throttle:api'])->group(function () {
    Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('api.v1.health');
});
