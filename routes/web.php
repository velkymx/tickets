<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\Kb\KbAdminController;
use App\Http\Controllers\Kb\KbController;
use App\Http\Controllers\Kb\KbVersionController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\NotesController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\ReleaseController;
use App\Http\Controllers\TicketPulseController;
use App\Http\Controllers\TicketsController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group.
|
*/

// --- Public Routes ---
Route::get('/', function () {
    return view('welcome');
});

// --- Authenticated Routes Group ---
Route::middleware(['auth', 'throttle:60,1'])->group(function () {

    // Home
    Route::get('/home', [TicketsController::class, 'home'])->name('home');
    Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
    Route::post('/activity/read/{id}', [ActivityController::class, 'read'])->name('activity.read');
    Route::post('/activity/read-all', [ActivityController::class, 'readAll'])->name('activity.read-all');

    // --- Tickets Routes (CRITICAL: Static routes must come before dynamic routes) ---

    // Pulse Route (New)
    Route::get('/tickets/{ticket}/pulse', [TicketPulseController::class, 'show'])->name('tickets.pulse');

    // Static Routes (Creation, Import, API Access)
    Route::get('/ticket/create', [TicketsController::class, 'create'])->name('tickets.create');
    Route::get('/tickets/import', [ImportController::class, 'index'])->name('tickets.import'); // FIX: MOVED UP
    Route::post('/tickets/api/{id}', [TicketsController::class, 'api'])->name('tickets.api');

    // Action/Modifier Routes
    Route::get('/tickets/clone/{id}', [TicketsController::class, 'clone'])->name('tickets.clone');
    Route::get('/tickets/edit/{id}', [TicketsController::class, 'edit'])->name('tickets.edit');

    // Base/Index Route
    Route::get('/tickets', [TicketsController::class, 'index'])->name('tickets.list');
    Route::get('/tickets/fetch', [TicketsController::class, 'fetch'])->name('tickets.fetch');
    Route::get('/tickets/board', [TicketsController::class, 'board'])->name('tickets.board');

    // Dynamic Show Route (Must be last under the /tickets prefix)
    Route::get('/tickets/{id}', [TicketsController::class, 'show'])->name('tickets.show');

    // --- Milestone Routes ---
    Route::get('/milestone', [MilestoneController::class, 'index'])->name('milestone.list');
    Route::get('/milestone/create', [MilestoneController::class, 'create'])->name('milestone.create');
    Route::get('/milestone/edit/{id}', [MilestoneController::class, 'edit'])->name('milestone.edit');
    Route::get('/milestone/print/{id}', [MilestoneController::class, 'print'])->name('milestone.print');
    Route::get('/milestone/show/{id}', [MilestoneController::class, 'getShow'])->name('milestone.show');
    Route::post('/milestone/watch/{id}', [MilestoneController::class, 'toggleWatcher'])->name('milestone.watch');
    Route::get('/milestone/report/{id}', [MilestoneController::class, 'report'])->name('milestone.report');

    // --- Notes Routes ---
    Route::post('/notes/hide/{id}', [NotesController::class, 'hide'])->name('notes.hide');
    Route::post('/notes/{id}/promote', [NotesController::class, 'promote'])->name('notes.promote');
    Route::post('/notes/{id}/react', [NotesController::class, 'toggleReaction'])->name('notes.react');
    Route::post('/notes/reply', [NotesController::class, 'reply'])->name('notes.reply');
    Route::put('/notes/{id}', [NotesController::class, 'update'])->name('notes.update');
    Route::post('/notes/{id}/pin', [NotesController::class, 'togglePin'])->name('notes.pin');
    Route::post('/notes/{id}/resolve', [NotesController::class, 'resolve'])->name('notes.resolve');
    Route::post('/notes/{id}/attachments', [NotesController::class, 'attach'])->name('notes.attach');
    Route::post('/tickets/{ticketId}/presence', [PresenceController::class, 'heartbeat'])->name('tickets.presence.heartbeat');
    Route::get('/tickets/{ticketId}/presence', [PresenceController::class, 'show'])->name('tickets.presence.show');

    // --- Projects Routes ---
    Route::get('/projects', [ProjectsController::class, 'index'])->name('projects.list');
    Route::get('/projects/create', [ProjectsController::class, 'create'])->name('projects.create');
    Route::get('/projects/edit/{id}', [ProjectsController::class, 'edit'])->name('projects.edit');
    Route::get('/projects/show/{id}', [ProjectsController::class, 'show'])->name('projects.show');

    // --- Release Routes ---
    Route::get('/releases', [ReleaseController::class, 'index'])->name('releases.list');
    Route::get('/releases/create', [ReleaseController::class, 'create'])->name('release.create');
    Route::get('/release/{id}', [ReleaseController::class, 'show'])->name('release.show');
    Route::get('/release/edit/{id}', [ReleaseController::class, 'edit'])->name('release.edit');

    // --- Users Routes ---
    Route::get('/user/edit', [UsersController::class, 'edit'])->name('user.edit');
    Route::get('/users/{id}', [UsersController::class, 'show'])->name('user.show');

    // --- POST/PUT/PATCH Routes ---

    // Tickets
    Route::post('/tickets', [TicketsController::class, 'store'])->name('tickets.store');
    Route::post('/tickets/claim/{id}', [TicketsController::class, 'claim'])->name('tickets.claim');
    Route::post('/tickets/watch/{id}', [TicketsController::class, 'toggleWatcher'])->name('tickets.watch');
    Route::post('/tickets/estimate/{ticket_id}', [TicketsController::class, 'estimate'])->name('tickets.estimate');
    Route::post('/tickets/batch', [TicketsController::class, 'batch'])->name('tickets.batch')->middleware('throttle:uploads');
    Route::post('/tickets/import', [ImportController::class, 'create'])->name('tickets.storeimport')->middleware('throttle:uploads');
    Route::put('/tickets/update/{id}', [TicketsController::class, 'update'])->name('tickets.update');
    Route::post('/tickets/upload', [TicketsController::class, 'upload'])->name('tickets.upload')->middleware('throttle:uploads');

    // Milestone
    Route::post('/milestone/store/{id}', [MilestoneController::class, 'store'])->name('milestone.store');
    Route::put('/milestone/update/{id}', [MilestoneController::class, 'update'])->name('milestone.update');

    // Notes
    Route::post('/notes', [TicketsController::class, 'note'])->name('notes.store');

    // Projects
    Route::post('/projects/store/{id}', [ProjectsController::class, 'store'])->name('projects.update');

    // Release
    Route::put('/release/edit/{id}', [ReleaseController::class, 'put'])->name('release.update');
    Route::post('/release/store', [ReleaseController::class, 'store'])->name('release.store');

    // User
    Route::post('/user/update', [UsersController::class, 'update'])->name('user.update');
    Route::post('/user/api-token', [UsersController::class, 'generateApiToken'])->name('user.api-token');
    Route::delete('/user/api-token', [UsersController::class, 'revokeApiToken'])->name('user.api-token.revoke');
});

// --- Knowledge Base Routes ---

// Public KB routes (auth optional)
Route::prefix('kb')->group(function () {
    Route::get('/', [KbController::class, 'index'])->name('kb.index');
    Route::get('/search', [KbController::class, 'search'])->name('kb.search');
    Route::get('/category/{slug}', [KbController::class, 'category'])->name('kb.category');
    Route::get('/tag/{slug}', [KbController::class, 'tag'])->name('kb.tag');
});

// KB Admin routes (must be before wildcard {slug} routes)
Route::middleware(['auth'])->prefix('kb/admin')->group(function () {
    Route::get('/categories', [KbAdminController::class, 'categories'])->name('kb.admin.categories');
    Route::post('/categories', [KbAdminController::class, 'storeCategory'])->name('kb.admin.categories.store');
    Route::put('/categories/{id}', [KbAdminController::class, 'updateCategory'])->name('kb.admin.categories.update');
    Route::delete('/categories/{id}', [KbAdminController::class, 'destroyCategory'])->name('kb.admin.categories.destroy');
    Route::get('/tags', [KbAdminController::class, 'tags'])->name('kb.admin.tags');
    Route::post('/tags', [KbAdminController::class, 'storeTag'])->name('kb.admin.tags.store');
    Route::put('/tags/{id}', [KbAdminController::class, 'updateTag'])->name('kb.admin.tags.update');
    Route::delete('/tags/{id}', [KbAdminController::class, 'destroyTag'])->name('kb.admin.tags.destroy');
    Route::get('/trashed', [KbAdminController::class, 'trashed'])->name('kb.admin.trashed');
    Route::post('/trashed/{id}/restore', [KbAdminController::class, 'restoreArticle'])->name('kb.admin.trashed.restore');
});

// Authenticated KB routes
Route::middleware(['auth'])->prefix('kb')->group(function () {
    Route::get('/create', [KbController::class, 'create'])->name('kb.create');
    Route::post('/', [KbController::class, 'store'])->name('kb.store');

    Route::get('/{slug}/edit', [KbController::class, 'edit'])->name('kb.edit')
        ->where('slug', '^(?!create$|search$|category$|tag$|admin$).*');
    Route::put('/{slug}', [KbController::class, 'update'])->name('kb.update')
        ->where('slug', '^(?!create$|search$|category$|tag$|admin$).*');
    Route::delete('/{slug}', [KbController::class, 'destroy'])->name('kb.destroy')
        ->where('slug', '^(?!create$|search$|category$|tag$|admin$).*');

    Route::get('/{slug}/history', [KbVersionController::class, 'index'])->name('kb.history');
    Route::get('/{slug}/history/{version}', [KbVersionController::class, 'show'])->name('kb.history.show');
    Route::get('/{slug}/diff/{from}/{to}', [KbVersionController::class, 'diff'])->name('kb.diff');
    Route::post('/{slug}/restore/{version}', [KbVersionController::class, 'restore'])->name('kb.restore');

    Route::post('/{slug}/attachments', [KbController::class, 'uploadAttachment'])->name('kb.attach')->middleware('throttle:uploads');
    Route::delete('/{slug}/attachments/{id}', [KbController::class, 'deleteAttachment'])->name('kb.detach');
});

// Public KB show route (MUST be last — wildcard)
Route::get('/kb/{slug}', [KbController::class, 'show'])->name('kb.show')
    ->where('slug', '^(?!create$|search$|category$|tag$|admin$).*');

// Authentication routes
require __DIR__.'/auth.php';
