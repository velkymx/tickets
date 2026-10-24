<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SsoController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    if (config('auth.registration_enabled')) {
        Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
        Route::post('register', [RegisterController::class, 'register'])->middleware('throttle:5,1');
    }

    if (config('auth.password_login_enabled')) {
        Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('login', [LoginController::class, 'login']);

        Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
        Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
            ->middleware('throttle:5,1')
            ->name('password.email');

        Route::get('reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
        Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
    }

    Route::get('auth/{provider}/redirect', [SsoController::class, 'redirect'])
        ->whereIn('provider', ['github', 'google', 'gitlab', 'azure'])
        ->name('sso.redirect');
    Route::get('auth/{provider}/callback', [SsoController::class, 'callback'])
        ->whereIn('provider', ['github', 'google', 'gitlab', 'azure'])
        ->name('sso.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
});
