<?php

use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\AdministratorPayrollController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SiteSettingController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\SwimmerController;
use App\Http\Controllers\SwimmerFileController;
use App\Http\Controllers\TrainerAdvanceController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\TrainerFileController;
use App\Http\Controllers\TrainerHourController;
use App\Http\Controllers\TrainerPaymentWeekController;
use App\Http\Controllers\TrainerPayrollController;
use App\Http\Controllers\TrainingGroupController;
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
    Route::resource('trainer-hours', TrainerHourController::class)
        ->except(['create', 'show']);
    Route::resource('trainer-advances', TrainerAdvanceController::class)
        ->except(['create', 'show']);

    Route::middleware('manager')->group(function (): void {
        Route::resource('administrators', AdministratorController::class)->except(['create', 'show']);
        Route::get('administrator-payrolls', [AdministratorPayrollController::class, 'index'])->name('administrator-payrolls.index');
        Route::post('administrator-payrolls', [AdministratorPayrollController::class, 'store'])->name('administrator-payrolls.store');
        Route::resource('branches', BranchController::class)->except(['create', 'show']);
        Route::resource('training-groups', TrainingGroupController::class)->except(['create', 'show']);
        Route::resource('swimmers', SwimmerController::class)->except(['create', 'show']);
        Route::resource('swimmers.files', SwimmerFileController::class)
            ->parameters(['files' => 'trainerFile'])
            ->except(['create', 'show']);
        Route::resource('trainers', TrainerController::class)->except(['create', 'show']);
        Route::resource('trainers.files', TrainerFileController::class)
            ->parameters(['files' => 'trainerFile'])
            ->except(['create', 'show']);
        Route::resource('users', UserController::class)->except(['create', 'show']);
        Route::get('trainer-payrolls', [TrainerPayrollController::class, 'index'])->name('trainer-payrolls.index');
        Route::post('trainer-payrolls', [TrainerPayrollController::class, 'store'])->name('trainer-payrolls.store');
        Route::post('trainer-payrolls/{trainerPayroll}/release', [TrainerPayrollController::class, 'release'])->name('trainer-payrolls.release');
        Route::get('trainer-payment-week', [TrainerPaymentWeekController::class, 'edit'])->name('trainer-payment-week.edit');
        Route::put('trainer-payment-week', [TrainerPaymentWeekController::class, 'update'])->name('trainer-payment-week.update');
        Route::get('site-settings', [SiteSettingController::class, 'edit'])->name('site-settings.edit');
        Route::put('site-settings', [SiteSettingController::class, 'update'])->name('site-settings.update');
    });
});
