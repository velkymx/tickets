<?php

use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['api.token', 'throttle:api'])->group(function () {
    Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('api.v1.health');
    Route::get('/tickets', [TicketController::class, 'index'])->name('api.v1.tickets.index');
    Route::post('/tickets', [TicketController::class, 'store'])->name('api.v1.tickets.store');
    Route::get('/tickets/{id}', [TicketController::class, 'show'])->name('api.v1.tickets.show');
    Route::post('/tickets/{id}/note', [TicketController::class, 'note'])->name('api.v1.tickets.note');
});
