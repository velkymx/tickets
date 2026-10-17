<?php

use App\Http\Controllers\Api\KbController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['api.token', 'throttle:api'])->group(function () {
    Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('api.v1.health');
    Route::get('/lookups', [TicketController::class, 'lookups'])->name('api.v1.lookups');

    Route::get('/kb/lookups', [KbController::class, 'lookups'])->name('api.v1.kb.lookups');
    Route::get('/kb/articles', [KbController::class, 'index'])->name('api.v1.kb.index');
    Route::post('/kb/articles', [KbController::class, 'store'])->name('api.v1.kb.store');
    Route::get('/kb/articles/{idOrSlug}', [KbController::class, 'show'])->name('api.v1.kb.show');
    Route::match(['put', 'patch'], '/kb/articles/{id}', [KbController::class, 'update'])->name('api.v1.kb.update');
    Route::get('/tickets', [TicketController::class, 'index'])->name('api.v1.tickets.index');
    Route::post('/tickets', [TicketController::class, 'store'])->name('api.v1.tickets.store');
    Route::get('/tickets/{id}', [TicketController::class, 'show'])->name('api.v1.tickets.show');
    Route::match(['put', 'patch'], '/tickets/{id}', [TicketController::class, 'update'])->name('api.v1.tickets.update');
    Route::post('/tickets/{id}/note', [TicketController::class, 'note'])->name('api.v1.tickets.note');
    Route::post('/tickets/{id}/notes/{noteId}/react', [TicketController::class, 'react'])->name('api.v1.tickets.notes.react');
    Route::post('/tickets/{id}/notes/{noteId}/reply', [TicketController::class, 'reply'])->name('api.v1.tickets.notes.reply');
    Route::put('/tickets/{id}/notes/{noteId}', [TicketController::class, 'editNote'])->name('api.v1.tickets.notes.edit');
    Route::post('/tickets/{id}/notes/{noteId}/resolve', [TicketController::class, 'resolveNote'])->name('api.v1.tickets.notes.resolve');
    Route::post('/tickets/{id}/notes/{noteId}/moderate', [TicketController::class, 'moderateNote'])->name('api.v1.tickets.notes.moderate');
    Route::post('/tickets/{id}/notes/{noteId}/promote', [TicketController::class, 'promoteNote'])->name('api.v1.tickets.notes.promote');
    Route::post('/tickets/{id}/watch', [TicketController::class, 'watch'])->name('api.v1.tickets.watch');
    Route::get('/tickets/{id}/pulse', [TicketController::class, 'pulse'])->name('api.v1.tickets.pulse');
});
