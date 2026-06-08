<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\ProfileDistrictController;
use App\Http\Controllers\Auth\ProfileUpdateController;
use App\Http\Controllers\Auth\RegisterOfficerController;
use App\Http\Controllers\Auth\RegisterUserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\HubController;
use App\Http\Controllers\MainManagerHubController;
use App\Http\Controllers\ManagerHubController;
use App\Http\Controllers\OfficerHubController;
use App\Http\Controllers\IssueAttachmentController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\MainManagerController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\ManagerDepartmentController;
use App\Http\Controllers\ManagerDistrictController;
use App\Http\Controllers\OfficerController;
use App\Http\Controllers\OfficerDepartmentController;
use App\Http\Controllers\OfficerDistrictController;
use App\Http\Controllers\OfficerSessionController;
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
// and Manager tokenable models alike. `actor.active` runs after Sanctum so
// inactive actors are blocked except on the whitelisted auth.me routes below.
Route::middleware(['auth:sanctum', 'actor.active', 'officer.hub-active'])->prefix('auth')->group(function (): void {
    Route::get('me', ProfileController::class)->name('auth.me');
    Route::post('logout', LogoutController::class)->name('auth.logout');

    // Self-service district assignment updates. Only active managers and active
    // officers carry district assignments, so authorization is narrowed inside
    // UpdateOwnDistrictsRequest: users and any unsupported actor receive a 403.
    // The authenticated actor's own districts are synced wholesale from the
    // validated `district_ids` array and the refreshed auth profile is returned.
    Route::patch('me/districts', ProfileDistrictController::class)->name('auth.me.districts.update');

    // Self-service identity updates. Active users, officers, and managers may
    // PATCH their own username, email, and password; officers may also update
    // badge_number. Department, district, and privileged fields are rejected
    // by UpdateProfileRequest validation rather than accepted on this path.
    Route::patch('me', ProfileUpdateController::class)->name('auth.me.update');
});

// Protected manager creation. The route lives outside the `/api/auth` prefix
// and requires a valid Sanctum bearer token. Authorization is further narrowed
// inside StoreManagerRequest to an active main manager only; users, officers,
// non-main managers, and inactive managers all receive a 403. The endpoint is
// rate-limited to mitigate abuse. No login token is issued for the created
// manager, who must authenticate via `POST /api/auth/login`.
Route::middleware(['auth:sanctum', 'actor.active', 'officer.hub-active', 'throttle:30,1'])->group(function (): void {
    // Main-manager-protected main manager listing. Authorization is narrowed
    // inside IndexMainManagerRequest to an authenticated, active main manager
    // only; users, officers, non-main managers, and inactive managers all
    // receive a 403. Results include only rows with `is_main_manager = true`,
    // ordered by username ascending, with departments and districts eager loaded.
    Route::get('main-managers', [MainManagerController::class, 'index'])->name('main-managers.index');
    // Main-manager-protected main manager update, disable, and enable. Authorization
    // is narrowed inside the main-manager FormRequests to an authenticated, active
    // main manager only. Route binding limits `{manager}` to rows with
    // `is_main_manager = true` (non-main or unknown ids return 404). Update
    // accepts partial username, email, and password only; disable sets
    // `is_active = false` and rejects deactivating the last active main manager;
    // enable sets `is_active = true` and is idempotent.
    Route::patch('main-managers/{manager}', [MainManagerController::class, 'update'])->name('main-managers.update');
    Route::patch('main-managers/{manager}/disable', [MainManagerController::class, 'disable'])->name('main-managers.disable');
    Route::patch('main-managers/{manager}/enable', [MainManagerController::class, 'enable'])->name('main-managers.enable');
    // Main-manager-protected main manager hub assignment. Authorization is narrowed
    // inside UpdateManagerHubRequest to an authenticated, active main manager only.
    // Setting a main manager's hub clears their district_manager pivot so district
    // assignments can be re-established within the new hub.
    Route::patch('main-managers/{manager}/hub', [MainManagerHubController::class, 'update'])->name('main-managers.hub.update');

    // Main-manager-protected ordinary manager listing. Authorization is narrowed
    // inside IndexManagerRequest to an authenticated, active main manager only;
    // users, officers, non-main managers, and inactive managers all receive a
    // 403. Results include only rows with `is_main_manager = false`, ordered by
    // username ascending, with departments and districts eager loaded.
    Route::get('managers', [ManagerController::class, 'index'])->name('managers.index');
    Route::post('managers', [ManagerController::class, 'store'])->name('managers.store');
    // Main-manager-protected ordinary manager update, disable, and enable.
    // Authorization is narrowed inside the manager FormRequests to an
    // authenticated, active main manager only. Route binding limits `{manager}`
    // to rows with `is_main_manager = false` (main managers and unknown ids
    // return 404). Update accepts partial username, email, and password only;
    // disable sets `is_active = false`; enable sets `is_active = true` and is
    // idempotent.
    Route::patch('managers/{manager}', [ManagerController::class, 'update'])->name('managers.update');
    Route::patch('managers/{manager}/disable', [ManagerController::class, 'disable'])->name('managers.disable');
    Route::patch('managers/{manager}/enable', [ManagerController::class, 'enable'])->name('managers.enable');
    // Main-manager-protected ordinary manager hub assignment. Authorization is
    // narrowed inside UpdateManagerHubRequest to an authenticated, active main
    // manager only. Setting a manager's hub clears their district_manager pivot.
    Route::patch('managers/{manager}/hub', [ManagerHubController::class, 'update'])->name('managers.hub.update');

    // Officer session listing. Authorization is narrowed inside
    // IndexOfficerSessionRequest to an authenticated, active manager only;
    // users, officers, and inactive managers receive a 403. Optional
    // `officer_id`, `hub_id`, and `is_hub_active` filters narrow the result
    // set. Results are ordered newest-first by shift_start.
    Route::get('officer-sessions', [OfficerSessionController::class, 'index'])->name('officer-sessions.index');

    // Officer listing. Authorization is narrowed inside IndexOfficerRequest to
    // an authenticated, active officer or manager; users and inactive actors
    // receive a 403. Results exclude soft-deleted officers and default to active
    // officers only when `is_active` is omitted. Optional district_id and
    // department_id filters narrow the result set through officer pivots.
    Route::get('officers', [OfficerController::class, 'index'])->name('officers.index');

    // Manager-protected officer disable and enable. Authorization is narrowed
    // inside DisableOfficerRequest and EnableOfficerRequest to an authenticated,
    // active manager; users, officers, and inactive managers receive a 403. Any
    // active manager may toggle the target officer's is_active flag without
    // soft-deleting or restoring the row. Soft-deleted officers return 404.
    Route::patch('officers/{officer}/disable', [OfficerController::class, 'disable'])->name('officers.disable');
    Route::patch('officers/{officer}/enable', [OfficerController::class, 'enable'])->name('officers.enable');

    // Manager-protected officer district assignment. Authorization is narrowed
    // inside UpdateOfficerDistrictsRequest to an authenticated, active manager;
    // users, officers, and inactive managers receive a 403. Any active manager
    // may sync the target officer's districts wholesale from the validated
    // `district_ids` array. The endpoint only touches the officer-side district
    // pivot and never reassigns `issues.district_id`.
    Route::patch('officers/{officer}/districts', [OfficerDistrictController::class, 'update'])->name('officers.districts.update');
    // Manager-protected officer hub assignment. Authorization is narrowed inside
    // UpdateOfficerHubRequest to an authenticated, active manager. Setting an
    // officer's hub clears their district_officer pivot.
    Route::patch('officers/{officer}/hub', [OfficerHubController::class, 'update'])->name('officers.hub.update');

    // Main-manager-protected actor department assignment. Authorization is narrowed
    // inside the department assignment FormRequests to an authenticated, active
    // main manager only; users, officers, non-main managers, and inactive managers
    // receive a 403. Any active main manager may sync the target officer's or
    // manager's departments wholesale from the validated `department_ids` array,
    // including an empty array to clear all assignments. These endpoints only touch
    // the department_officer / department_manager pivots.
    Route::patch('officers/{officer}/departments', [OfficerDepartmentController::class, 'update'])->name('officers.departments.update');
    Route::patch('managers/{manager}/departments', [ManagerDepartmentController::class, 'update'])->name('managers.departments.update');

    // Main-manager-protected manager district assignment. Authorization is narrowed
    // inside UpdateManagerDistrictsRequest to an authenticated, active main manager
    // only; users, officers, non-main managers, and inactive managers receive a
    // 403. Any active main manager may sync the target manager's districts wholesale
    // from the validated `district_ids` array, including an empty array to clear
    // all assignments. Route binding limits `{manager}` to rows with
    // `is_main_manager = false` (main managers and unknown ids return 404). This
    // endpoint only touches the district_manager pivot and never modifies
    // issues.district_id or officer district pivots.
    Route::patch('managers/{manager}/districts', [ManagerDistrictController::class, 'update'])->name('managers.districts.update');

    // Main-manager-protected category management. Authorization is narrowed
    // inside the category FormRequests to an authenticated, active main
    // manager; users, officers, non-main managers, and inactive managers all
    // receive a 403. Deactivation and
    // reactivation use generic PATCH with `is_active` (same as districts),
    // while hard delete (DELETE `/categories/{category}`) is guarded against
    // main categories that still have subcategories and against rows still
    // referenced by existing issues.
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
    Route::match(['put', 'patch'], 'categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Main-manager-protected department management. Authorization is narrowed
    // inside the department FormRequests to an authenticated, active main
    // manager; users, officers, non-main managers, and inactive managers all
    // receive a 403. Activate and deactivate use generic PATCH with `is_active`
    // only. Deleting a department (DELETE `/departments/{department}`) hard
    // deletes the row and relies on the `category_department` pivot's
    // foreign-key cascade to remove category assignments automatically, leaving
    // the category records themselves intact.
    Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::get('departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');
    Route::match(['put', 'patch'], 'departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

    // Hub management. Reads are available to any authenticated actor; writes are
    // authorized inside the hub FormRequests to an authenticated, active main
    // manager. Deleting a hub is blocked when districts, officers, or managers
    // still reference it.
    Route::get('hubs', [HubController::class, 'index'])->name('hubs.index');
    Route::post('hubs', [HubController::class, 'store'])->name('hubs.store');
    Route::get('hubs/{hub}', [HubController::class, 'show'])->name('hubs.show');
    Route::match(['put', 'patch'], 'hubs/{hub}', [HubController::class, 'update'])->name('hubs.update');
    Route::delete('hubs/{hub}', [HubController::class, 'destroy'])->name('hubs.destroy');

    // District management. Reads preserve the documented district list/show
    // contract, while writes are authorized inside the district FormRequests to
    // an authenticated, active main manager; users, officers, non-main managers,
    // and inactive managers all receive a 403. Deleting a district is blocked when
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
    // Show returns 404 when the issue is not visible to the actor (e.g. hidden
    // and not owned by an active user); officers and managers may view all issues.
    Route::get('issues/{issue}', [IssueController::class, 'show'])->name('issues.show');
    Route::match(['put', 'patch'], 'issues/{issue}', [IssueController::class, 'update'])->name('issues.update');
    // Visibility writes are officer/manager-only and authorized inside
    // UpdateIssueVisibilityRequest; the controller enforces visibility scope
    // (404 when the issue is not viewable, matching show).
    Route::patch('issues/{issue}/visibility', [IssueController::class, 'updateVisibility'])->name('issues.visibility.update');
    Route::delete('issues/{issue}', [IssueController::class, 'destroy'])->name('issues.destroy');

    // Issue attachments. Uploads are authorized inside StoreIssueAttachmentRequest
    // to the authenticated, active regular user who owns the issue, mirroring the
    // owner-only edit/delete model so an author can add or replace files while
    // managing their report. Files are stored on a non-public local disk for
    // development (never served from `public`), capped at 5 files of up to 5 MB
    // each, and an issue may hold at most 5 attachments in total. Downloads
    // (GET `/issues/{issue}/attachments/{attachment}/download`) are authorized
    // inside DownloadIssueAttachmentRequest to the issue owner (active user),
    // any active officer, or any active manager; the controller confirms the
    // attachment belongs to the route issue (404 otherwise) and streams from
    // the non-public local disk, so files are never exposed through a public URL.
    // Deletes (DELETE `/issues/{issue}/attachments/{attachment}`) are authorized
    // inside DeleteIssueAttachmentRequest to the authenticated, active regular
    // user who owns the route issue; the controller confirms the attachment
    // belongs to that issue (404 otherwise), removes the backing file from the
    // non-public local disk, and hard deletes the attachment row.
    Route::post('issues/{issue}/attachments', [IssueAttachmentController::class, 'store'])->name('issues.attachments.store');
    Route::get('issues/{issue}/attachments/{attachment}/download', [IssueAttachmentController::class, 'download'])->name('issues.attachments.download');
    Route::delete('issues/{issue}/attachments/{attachment}', [IssueAttachmentController::class, 'destroy'])->name('issues.attachments.destroy');
});
