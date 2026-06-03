<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\ProfileDistrictController;
use App\Http\Controllers\Auth\RegisterOfficerController;
use App\Http\Controllers\Auth\RegisterUserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\IssueAttachmentController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\OfficerDistrictController;
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

    // Self-service district assignment updates. Only active managers and active
    // officers carry district assignments, so authorization is narrowed inside
    // UpdateOwnDistrictsRequest: users and any unsupported actor receive a 403.
    // The authenticated actor's own districts are synced wholesale from the
    // validated `district_ids` array and the refreshed auth profile is returned.
    Route::patch('me/districts', ProfileDistrictController::class)->name('auth.me.districts.update');
});

// Protected manager creation. The route lives outside the `/api/auth` prefix
// and requires a valid Sanctum bearer token. Authorization is further narrowed
// inside StoreManagerRequest to an active main manager only; users, officers,
// non-main managers, and inactive managers all receive a 403. The endpoint is
// rate-limited to mitigate abuse. No login token is issued for the created
// manager, who must authenticate via `POST /api/auth/login`.
Route::middleware(['auth:sanctum', 'throttle:30,1'])->group(function (): void {
    Route::post('managers', [ManagerController::class, 'store'])->name('managers.store');

    // Manager-protected officer district assignment. Authorization is narrowed
    // inside UpdateOfficerDistrictsRequest to an authenticated, active manager;
    // users, officers, and inactive managers receive a 403. Any active manager
    // may sync the target officer's districts wholesale from the validated
    // `district_ids` array. The endpoint only touches the officer-side district
    // pivot and never reassigns `issues.district_id`.
    Route::patch('officers/{officer}/districts', [OfficerDistrictController::class, 'update'])->name('officers.districts.update');

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

    // District management. Reads preserve the documented district list/show
    // contract, while writes are authorized inside the district FormRequests to
    // authenticated, active managers only. Deleting a district is blocked when
    // manager/officer assignments or issue references still exist; district CRUD
    // never reassigns or rewrites `issues.district_id`.
    Route::get('districts', [DistrictController::class, 'index'])->name('districts.index');
    Route::post('districts', [DistrictController::class, 'store'])->name('districts.store');
    Route::get('districts/{district}', [DistrictController::class, 'show'])->name('districts.show');
    Route::match(['put', 'patch'], 'districts/{district}', [DistrictController::class, 'update'])->name('districts.update');
    Route::delete('districts/{district}', [DistrictController::class, 'destroy'])->name('districts.destroy');

    // Issue management. Listing and reads are available to any authenticated
    // actor, while writes are authorized inside the issue FormRequests to the
    // authenticated, active regular user who owns the issue. Issues remain
    // user-owned through `issues.user_id` even when reported anonymously, so the
    // author can keep managing their own report; anonymous reports are displayed
    // through a stable, server-generated `anonymous_alias`. Deleting an issue
    // (DELETE `/issues/{issue}`) is a hard delete that relies on the attachment
    // foreign-key cascade to remove the issue's attachments.
    Route::get('issues', [IssueController::class, 'index'])->name('issues.index');
    Route::post('issues', [IssueController::class, 'store'])->name('issues.store');
    Route::get('issues/{issue}', [IssueController::class, 'show'])->name('issues.show');
    Route::match(['put', 'patch'], 'issues/{issue}', [IssueController::class, 'update'])->name('issues.update');
    Route::delete('issues/{issue}', [IssueController::class, 'destroy'])->name('issues.destroy');

    // Issue attachments. Uploads are authorized inside StoreIssueAttachmentRequest
    // to the authenticated, active regular user who owns the issue, mirroring the
    // owner-only edit/delete model so an author can add or replace files while
    // managing their report. Files are stored on a non-public local disk for
    // development (never served from `public`), capped at 5 files of up to 5 MB
    // each, and an issue may hold at most 5 attachments in total. Downloads
    // (GET `/issues/{issue}/attachments/{attachment}/download`) require a valid
    // bearer token via this group's `auth:sanctum` middleware and stream the file
    // from local storage only after confirming the attachment belongs to the
    // route issue, so files are never exposed through a public storage URL.
    Route::post('issues/{issue}/attachments', [IssueAttachmentController::class, 'store'])->name('issues.attachments.store');
    Route::get('issues/{issue}/attachments/{attachment}/download', [IssueAttachmentController::class, 'download'])->name('issues.attachments.download');
});
