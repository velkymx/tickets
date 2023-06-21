<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketsController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\NotesController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\ReleaseController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\UsersController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/home', [TicketsController::class, 'home'])->name('home');
    Route::get('/milestone', [MilestoneController::class, 'index'])->name('milestone.list');
    Route::get('/milestone/create', [MilestoneController::class, 'create'])->name('milestone.create');
    Route::get('/milestone/edit/{id}', [MilestoneController::class, 'edit'])->name('milestone.edit');
    Route::get('/milestone/print/{id}', [MilestoneController::class, 'print'])->name('milestone.print');
    Route::get('/milestone/show/{id}', [MilestoneController::class, 'getShow'])->name('milestone.show');
    Route::get('/notes/hide/{id}', [NotesController::class, 'hide'])->name('notes.hide');
    Route::get('/projects', [ProjectsController::class, 'index'])->name('projects.list');
    Route::get('/projects/create', [ProjectsController::class, 'create'])->name('projects.create');
    Route::get('/projects/edit/{id}', [ProjectsController::class, 'edit'])->name('projects.edit');
    Route::get('/projects/show/{id}', [ProjectsController::class, 'show'])->name('projects.show');
    Route::get('/release/{id}', [ReleaseController::class, 'show'])->name('release.show');   
    Route::get('/releases/create', [ReleaseController::class, 'create'])->name('release.create');
    Route::get('/release/edit/{id}', [ReleaseController::class, 'edit'])->name('release.edit');
    Route::get('/releases', [ReleaseController::class, 'index'])->name('releases.list');
    Route::get('/tickets', [TicketsController::class, 'index'])->name('tickets.list');
    Route::get('/tickets/ai', [TicketsController::class, 'ai'])->name('tickets.ai');
    Route::post('/tickets/ai/process', [TicketsController::class, 'ai_process'])->name('tickets.ai_process');
    Route::get('/tickets/import', [ImportController::class, 'index'])->name('tickets.import');
    Route::get('/tickets/{id}', [TicketsController::class, 'show'])->name('tickets.show');
    Route::get('/tickets/api/{id}', [TicketsController::class, 'api'])->name('tickets.api');
    Route::get('/tickets/claim/{id}', [TicketsController::class, 'claim'])->name('tickets.claim');
    Route::get('/tickets/clone/{id}', [TicketsController::class, 'clone'])->name('tickets.clone');
    Route::get('/ticket/create', [TicketsController::class, 'create'])->name('tickets.create');
    Route::get('/tickets/edit/{id}', [TicketsController::class, 'edit'])->name('tickets.edit');    
    Route::get('/user/edit', [UsersController::class, 'edit'])->name('user.edit');
    Route::get('/users/{id}', [UsersController::class, 'show'])->name('user.show');
    Route::get('/users/watch/{id}', [UsersController::class, 'watch'])->name('users.watch');
    
    Route::post('/tickets', [TicketsController::class, 'store'])->name('tickets.store');
    Route::post('/tickets/estimate/{ticket_id}', [TicketsController::class, 'estimate'])->name('tickets.estimate');
    Route::post('/milestone/store/{id}', [MilestoneController::class, 'store'])->name('milestone.update');
    Route::post('/notes', [TicketsController::class, 'note'])->name('notes.store');
    Route::post('/projects/store/{id}', [ProjectsController::class, 'store'])->name('projects.update');
    Route::post('/release/edit/{id}', [ReleaseController::class, 'put'])->name('release.update');
    Route::post('/release/store', [ReleaseController::class, 'store'])->name('release.store'); 
    Route::post('/tickets/batch', [TicketsController::class, 'batch'])->name('tickets.batch');
    Route::post('/tickets/import', [ImportController::class, 'create'])->name('tickets.storeimport');
    Route::post('/tickets/update/{id}', [TicketsController::class, 'update'])->name('tickets.update');
    Route::post('/tickets/upload', [TicketsController::class, 'upload'])->name('tickets.upload');
    Route::post('/user/update', [UsersController::class, 'update'])->name('user.update');
    
    
});

require __DIR__.'/auth.php';
