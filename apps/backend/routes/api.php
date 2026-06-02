<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RegisterOfficerController;
use App\Http\Controllers\Auth\RegisterUserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ManagerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the application and prefixed with "/api".
| They return JSON responses and are stateless (no session cookies).
| Authentication endpoints and other backend API endpoints live here.
|
*/

// Login is rate-limited to mitigate credential brute-force attempts. The
// limiter uses Laravel's built-in throttle middleware (attempts,minutes).
Route::middleware('throttle:10,1')->prefix('auth')->group(function (): void {
    Route::post('login', LoginController::class)->name('auth.login');

    // Public, rate-limited officer self-registration. The badge-number field
    // unambiguously maps the request to the Officer actor table (rather than
    // User or Manager), and a Sanctum token is issued on success.
    Route::post('register/officer', RegisterOfficerController::class)->name('auth.register.officer');

    // Public, rate-limited user self-registration. The route unambiguously
    // maps the request to the User actor table (rather than Officer or
    // Manager), and a Sanctum token is issued on success.
    Route::post('register/user', RegisterUserController::class)->name('auth.register.user');
});

// Authenticated auth endpoints. `auth:sanctum` resolves the bearer token
// against the personal_access_tokens table and works for User, Officer,
// and Manager tokenable models alike.
Route::middleware('auth:sanctum')->prefix('auth')->group(function (): void {
    Route::get('me', ProfileController::class)->name('auth.me');
    Route::post('logout', LogoutController::class)->name('auth.logout');
});

// Protected manager creation. The route lives outside the `/api/auth` prefix
// and requires a valid Sanctum bearer token. Authorization is further narrowed
// inside StoreManagerRequest to an active main manager only; users, officers,
// non-main managers, and inactive managers all receive a 403. The endpoint is
// rate-limited to mitigate abuse. No login token is issued for the created
// manager, who must authenticate via `POST /api/auth/login`.
Route::middleware(['auth:sanctum', 'throttle:30,1'])->group(function (): void {
    Route::post('managers', [ManagerController::class, 'store'])->name('managers.store');

    // Manager-protected category management. Authorization is narrowed inside
    // the category FormRequests to an authenticated, active manager; users,
    // officers, and inactive managers receive a 403. Disabling (PATCH
    // `/categories/{category}/disable`) is the standard safe removal path,
    // while hard delete (DELETE `/categories/{category}`) is guarded against
    // main categories that still have subcategories and against rows still
    // referenced by existing issues.
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
    Route::match(['put', 'patch'], 'categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::patch('categories/{category}/disable', [CategoryController::class, 'disable'])->name('categories.disable');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Main-manager-protected department management. Authorization is narrowed
    // inside the department FormRequests to an authenticated, active main
    // manager; users, officers, non-main managers, and inactive managers all
    // receive a 403. Deleting a department (DELETE `/departments/{department}`)
    // hard deletes the row and relies on the `category_department` pivot's
    // foreign-key cascade to remove category assignments automatically, leaving
    // the category records themselves intact.
    Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::get('departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');
    Route::match(['put', 'patch'], 'departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
});
