<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/branch/switch', [DashboardController::class, 'switchBranch'])->name('branch.switch');
    Route::post('/theme', [ThemeController::class, 'update'])->name('theme.update');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::middleware('manager')->group(function (): void {
        Route::resource('branches', BranchController::class)->except(['create', 'show']);
        Route::resource('users', UserController::class)->except(['create', 'show']);
    });
});
