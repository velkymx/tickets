<?php

use App\Http\Controllers\ImportController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\NotesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\ReleaseController;
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
Route::middleware('auth')->group(function () {

    // Dashboard & Profile
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware('verified')->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Home
    Route::get('/home', [TicketsController::class, 'home'])->name('home');

    // --- Tickets Routes (CRITICAL: Static routes must come before dynamic routes) ---

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
    Route::post('/tickets/batch', [TicketsController::class, 'batch'])->name('tickets.batch');
    Route::post('/tickets/import', [ImportController::class, 'create'])->name('tickets.storeimport');
    Route::put('/tickets/update/{id}', [TicketsController::class, 'update'])->name('tickets.update');
    Route::post('/tickets/upload', [TicketsController::class, 'upload'])->name('tickets.upload');

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
});

// Authentication routes defined by Laravel Breeze/Jetstream
require __DIR__.'/auth.php';
