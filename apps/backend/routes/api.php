<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\StartOfficerShiftController;
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
use App\Http\Controllers\IssueChatController;
use App\Http\Controllers\IssueChatMessageController;
use App\Http\Controllers\IssueCommentController;
use App\Http\Controllers\IssueMessageAttachmentController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\IssueDuplicateController;
use App\Http\Controllers\IssueParticipantController;
use App\Http\Controllers\IssueSimilarCheckController;
use App\Http\Controllers\IssueStatusHistoryController;
use App\Http\Controllers\IssueOfficerAssignmentController;
use App\Http\Controllers\IssueOfficerStatusController;
use App\Http\Controllers\OfficerIssueResolutionAttachmentController;
use App\Http\Controllers\OfficerIssueResolutionController;
use App\Http\Controllers\OfficerIssueUpdateController;
use App\Http\Controllers\OfficerIssueUpdateAttachmentController;
use App\Http\Controllers\MainManagerController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\ManagerDepartmentController;
use App\Http\Controllers\ManagerDistrictController;
use App\Http\Controllers\OfficerController;
use App\Http\Controllers\OfficerEndShiftController;
use App\Http\Controllers\OfficerDepartmentController;
use App\Http\Controllers\OfficerDistrictController;
use App\Http\Controllers\OfficerSessionController;
use App\Http\Controllers\IssueFeedbackController;
use App\Http\Controllers\OfficerMeFeedbackController;
use App\Http\Controllers\NotificationBulkReadController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationMarkAllReadController;
use App\Http\Controllers\NotificationUnreadCountController;
use App\Http\Controllers\User\UserSettingsController;
use App\Http\Controllers\OfficerMeNotificationBulkReadController;
use App\Http\Controllers\OfficerMeNotificationController;
use App\Http\Controllers\OfficerMeNotificationMarkAllReadController;
use App\Http\Controllers\OfficerMeNotificationUnreadCountController;
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

// Public department reads for registration and reference lookups. Active
// departments only for unauthenticated callers; authenticated active main
// managers see inactive rows as well (scoped in DepartmentController).
Route::middleware('throttle:30,1')->group(function (): void {
    Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::get('departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');
});

// Authenticated auth endpoints. `auth:sanctum` resolves the bearer token
// against the personal_access_tokens table and works for User, Officer,
// and Manager tokenable models alike. `actor.active` runs after Sanctum so
// inactive actors are blocked except on the whitelisted auth.me routes below.
Route::middleware(['auth:sanctum', 'actor.active', 'officer.hub-active'])->prefix('auth')->group(function (): void {
    Route::get('me', ProfileController::class)->name('auth.me');
    Route::post('logout', LogoutController::class)->name('auth.logout');

    // Start a shared hub shift when at the assigned hub. Officers without an
    // active shift may call this while authenticated; geo evaluation mirrors
    // login. Whitelisted in officer.hub-active middleware (Tier A).
    Route::post('start-shift', StartOfficerShiftController::class)->name('auth.start-shift');

    // Self-service district assignment updates. Only active managers and active
    // officers carry district assignments, so authorization is narrowed inside
    // UpdateOwnDistrictsRequest: users and any unsupported actor receive a 403.
    // The authenticated actor's own districts are synced wholesale from the
    // validated `district_ids` array and the refreshed auth profile is returned.
    Route::patch('me/districts', ProfileDistrictController::class)->name('auth.me.districts.update');

    // Self-service feed districts update for users.
    Route::patch('me/feed-districts', [\App\Http\Controllers\Auth\UserFeedDistrictController::class, 'update'])->name('user-feed-districts.update');

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
Route::middleware(['auth:sanctum', 'actor.active', 'officer.hub-active', 'throttle:120,1'])->group(function (): void {
    // Main-manager-protected main manager listing. Authorization is narrowed
    // inside IndexMainManagerRequest to an authenticated, active main manager
    // only; users, officers, non-main managers, and inactive managers all
    // receive a 403. Results include only rows with `is_main_manager = true`,
    // ordered by username ascending, with departments and districts eager loaded.
    Route::get('main-managers', [MainManagerController::class, 'index'])->name('main-managers.index');
    // Officer/manager show for main managers. Authorization is narrowed inside
    // ShowMainManagerRequest to an authenticated, active officer or manager.
    // Route binding limits `{manager}` to rows with `is_main_manager = true`.
    // Hub scoping returns 404 when actor and target are not in the same hub
    // (no main-manager city-wide bypass).
    Route::get('main-managers/{manager}', [MainManagerController::class, 'show'])->name('main-managers.show');
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
    // Officer/manager show for ordinary managers. Authorization is narrowed
    // inside ShowManagerRequest to an authenticated, active officer or manager.
    // Route binding limits `{manager}` to rows with `is_main_manager = false`.
    // Hub scoping returns 404 when actor and target are not in the same hub.
    Route::get('managers/{manager}', [ManagerController::class, 'show'])->name('managers.show');
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
    // receive a 403. Results are hub-scoped for officers and ordinary managers
    // (same hub only; null hub yields no rows); main managers see all officers
    // city-wide. Results exclude soft-deleted officers and default to active
    // officers only when `is_active` is omitted. Optional district_id and
    // department_id filters intersect with hub scope through officer pivots.
    Route::get('officers', [OfficerController::class, 'index'])->name('officers.index');
    // Officer show is available to any authenticated active actor (Tier B).
    // Results are city-wide with no hub scoping. Soft-deleted officers return
    // 404 from route model binding.
    Route::get('officers/{officer}', [OfficerController::class, 'show'])->name('officers.show');

    // Manager-protected officer disable and enable. Authorization is narrowed
    // inside DisableOfficerRequest and EnableOfficerRequest to an authenticated,
    // active manager; users, officers, and inactive managers receive 403. The
    // controller enforces hub scoping via ManagerOfficerHubAccess: ordinary
    // managers may only administer officers in the same hub (both hub_id
    // non-null and equal); hub mismatch or null hub on either side returns 404.
    // Main managers may toggle any officer city-wide. Soft-deleted officers
    // return 404 from route model binding.
    Route::patch('officers/{officer}/disable', [OfficerController::class, 'disable'])->name('officers.disable');
    Route::patch('officers/{officer}/enable', [OfficerController::class, 'enable'])->name('officers.enable');

    // Manager-protected officer end-shift. Authorization is narrowed inside
    // EndOfficerShiftRequest to an authenticated, active manager (403 for wrong
    // actor type). The controller enforces hub scoping via
    // ManagerOfficerHubAccess (404 on hub mismatch or null hub; main managers
    // city-wide). Clears the shared shift clock without revoking tokens.
    Route::patch('officers/{officer}/end-shift', OfficerEndShiftController::class)->name('officers.end-shift');

    // Manager-protected officer district assignment. Authorization is narrowed
    // inside UpdateOfficerDistrictsRequest to an authenticated, active manager
    // (403 for wrong actor type). The controller enforces hub scoping via
    // ManagerOfficerHubAccess (404 on hub mismatch or null hub; main managers
    // city-wide). Syncs the target officer's districts wholesale from the
    // validated `district_ids` array. The endpoint only touches the officer-side
    // district pivot and never reassigns `issues.district_id`.
    Route::patch('officers/{officer}/districts', [OfficerDistrictController::class, 'update'])->name('officers.districts.update');
    // Manager-protected officer hub assignment. Authorization is narrowed inside
    // UpdateOfficerHubRequest to an authenticated, active manager. Setting an
    // officer's hub clears their district_officer pivot.
    Route::patch('officers/{officer}/hub', [OfficerHubController::class, 'update'])->name('officers.hub.update');

    // Officer self-service reads (Tier B whitelist).
    Route::get('officers/me/feedback', [OfficerMeFeedbackController::class, 'index'])->name('officers.me.feedback.index');

    // User notifications. Active users only; officers and managers receive 403.
    Route::get('user/settings', [UserSettingsController::class, 'show'])->name('user.settings.show');
    Route::patch('user/settings', [UserSettingsController::class, 'update'])->name('user.settings.update');
    Route::get('notifications/unread-count', [NotificationUnreadCountController::class, 'show'])->name('notifications.unread-count');
    Route::patch('notifications/bulk-read', [NotificationBulkReadController::class, 'update'])->name('notifications.bulk-read');
    Route::post('notifications/mark-all-read', [NotificationMarkAllReadController::class, 'store'])->name('notifications.mark-all-read');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{notification}', [NotificationController::class, 'update'])->name('notifications.update');

    // Officer self-service notifications (Tier B whitelist).
    Route::get('officers/me/notifications/unread-count', [OfficerMeNotificationUnreadCountController::class, 'show'])->name('officers.me.notifications.unread-count');
    Route::patch('officers/me/notifications/bulk-read', [OfficerMeNotificationBulkReadController::class, 'update'])->name('officers.me.notifications.bulk-read');
    Route::post('officers/me/notifications/mark-all-read', [OfficerMeNotificationMarkAllReadController::class, 'store'])->name('officers.me.notifications.mark-all-read');
    Route::get('officers/me/notifications', [OfficerMeNotificationController::class, 'index'])->name('officers.me.notifications.index');
    Route::patch('officers/me/notifications/{notification}', [OfficerMeNotificationController::class, 'update'])->name('officers.me.notifications.update');

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
    Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store');
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
    // actor; officers may browse without a hub-active shift (Tier B whitelist
    // in officer.hub-active). List/show visibility is district-scoped for
    // officers and ordinary managers (assigned districts only, including hidden
    // issues therein); main managers see all issues city-wide. Writes are
    // authorized inside the issue FormRequests to the authenticated, active
    // regular user who owns the issue.
    // Issues remain
    // user-owned through `issues.user_id` even when reported anonymously, so the
    // author can keep managing their own report; anonymous reports are displayed
    // through a stable, server-generated `anonymous_alias`. Deleting an issue
    // (DELETE `/issues/{issue}`) is a hard delete that relies on the attachment
    // foreign-key cascade to remove the issue's attachments.
    Route::get('issues', [IssueController::class, 'index'])->name('issues.index');
    Route::post('issues', [IssueController::class, 'store'])->name('issues.store');
    Route::post('issues/similar-check', [IssueSimilarCheckController::class, 'store'])->name('issues.similar-check');
    // Participant join/leave are active-user-only and authorized inside
    // JoinIssueRequest and LeaveIssueRequest. Join requires a canonical issue id
    // (422 cannot_join_duplicate_child on duplicate children); first join returns
    // 201, repeat join is idempotent (200). Leave removes the actor's row on the
    // canonical issue and decrements participant_count; child route ids resolve to
    // the canonical parent. Not participating returns 422 not_participant.
    Route::post('issues/{issue}/join', [IssueParticipantController::class, 'join'])->name('issues.join');
    Route::delete('issues/{issue}/leave', [IssueParticipantController::class, 'leave'])->name('issues.leave');
    // Duplicate children list is officer/manager-only and authorized inside
    // IndexIssueDuplicatesRequest. The target must be a canonical issue (422
    // issue_not_canonical on duplicate children). Children are paginated
    // oldest-first with full IssueResource payloads.
    Route::get('issues/{issue}/duplicates', [IssueDuplicateController::class, 'index'])->name('issues.duplicates.index');
    // Mark-duplicate links an existing issue to a canonical target (Tier C:
    // hub-active required for officers). Officers need district access on both
    // child and canonical (403 officer_not_in_district); managers use visibility
    // scope only. Different owners re-parent as hidden duplicate; same owner
    // merges participants and deletes the child. Returns IssueResource for the
    // child (re-parent) or canonical (merge).
    Route::post('issues/{issue}/mark-duplicate', [IssueDuplicateController::class, 'store'])->name('issues.mark-duplicate');
    // Participant list is officer/manager-only and authorized inside
    // IndexIssueParticipantsRequest (Tier B). Duplicate child route ids resolve
    // to the canonical parent. Participants are paginated oldest-first by
    // joined_at with anonymous alias redaction in IssueParticipantResource.
    Route::get('issues/{issue}/participants', [IssueParticipantController::class, 'index'])->name('issues.participants.index');
    // Status history list is officer/manager-only and authorized inside
    // IndexIssueStatusHistoryRequest (Tier B). Duplicate child route ids resolve
    // to the canonical parent. Rows are paginated newest-first by changed_at with
    // IssueStatusHistoryResource payloads (changedByOfficer eager loaded).
    Route::get('issues/{issue}/status-history', [IssueStatusHistoryController::class, 'index'])->name('issues.status-history.index');
    // Show returns 404 when the issue is not visible to the actor (e.g. hidden
    // and not owned by an active user, or outside assigned districts for
    // officers/ordinary managers). Main managers may view any issue city-wide.
    Route::get('issues/{issue}', [IssueController::class, 'show'])->name('issues.show');
    Route::match(['put', 'patch'], 'issues/{issue}', [IssueController::class, 'update'])->name('issues.update');
    // Visibility writes are officer/manager-only and authorized inside
    // UpdateIssueVisibilityRequest; the controller enforces visibility scope
    // (404 when the issue is not viewable, matching show).
    Route::patch('issues/{issue}/visibility', [IssueController::class, 'updateVisibility'])->name('issues.visibility.update');
    // Officer self-assign and unassign (Tier C: hub-active required). Authorization
    // is narrowed inside AssignIssueToOfficerRequest and UnassignIssueFromOfficerRequest
    // to active officers only; users, managers, and inactive officers receive 403.
    // The controller enforces visibility scope (404 when not viewable), district
    // scoping via OfficerIssueDistrictAccess (403 officer_not_in_district), and
    // assignment rules: self-assign is idempotent when already assigned to the
    // requesting officer, returns 409 issue_already_assigned when assigned to
    // another officer (no takeover), and open issues transition to in_behandeling
    // with one status history row. Unassign clears assigned_officer_id only;
    // status is unchanged. Only the current assignee may unassign (403
    // not_assigned_officer otherwise); already-unassigned is idempotent.
    Route::post('issues/{issue}/assign-self', [IssueOfficerAssignmentController::class, 'store'])->name('issues.assign-self');
    Route::post('issues/{issue}/unassign-self', [IssueOfficerAssignmentController::class, 'destroy'])->name('issues.unassign-self');
    // Officer status updates (Tier C: hub-active required). Authorization is
    // narrowed inside UpdateIssueStatusRequest to active officers only with
    // field rules (status, note). District access (403 officer_not_in_district),
    // assignee checks (403 not_assigned_officer), and directed transitions (422)
    // are enforced in IssueOfficerStatusController: district access before
    // locking; assignee and transition validation on the locked row via
    // OfficerIssueRowLock. The controller enforces visibility scope (404 when not
    // viewable), persists one status history row per change without coordinates,
    // and sets resolved_at on the first transition to opgelost.
    Route::patch('issues/{issue}/status', [IssueOfficerStatusController::class, 'update'])->name('issues.status.update');
    // Officer resolution. Show and attachment download are Tier B (browse without
    // shift); POST/PATCH are Tier C (hub-active required). One resolution per
    // issue; create is assignee-only with district scoping (403
    // officer_not_in_district), duplicate POST returns 409
    // officer_resolution_exists; update via PATCH. Show and attachment download
    // use IssueVisibilityQuery (404 when not viewable).
    Route::get('issues/{issue}/officer-resolution', [OfficerIssueResolutionController::class, 'show'])->name('issues.officer-resolution.show');
    Route::post('issues/{issue}/officer-resolution', [OfficerIssueResolutionController::class, 'store'])->name('issues.officer-resolution.store');
    Route::patch('issues/{issue}/officer-resolution', [OfficerIssueResolutionController::class, 'update'])->name('issues.officer-resolution.update');
    Route::get('issues/{issue}/officer-resolution/attachments/{attachment}/download', [OfficerIssueResolutionAttachmentController::class, 'download'])->name('issues.officer-resolution.attachments.download');
    // Officer resolution attachment delete (Tier C: hub-active required).
    // Assignee-only authorization on the locked row (403 not_assigned_officer);
    // district scoping (403 officer_not_in_district); attachment must belong to
    // the route resolution (404 otherwise). Repeat delete → 404 once the row is
    // removed. Returns 204 with row and backing file removed.
    Route::delete('issues/{issue}/officer-resolution/attachments/{attachment}', [OfficerIssueResolutionAttachmentController::class, 'destroy'])->name('issues.officer-resolution.attachments.destroy');

    // Officer updates. Index and attachment download are Tier B (can browse without active shift);
    // store, update, and destroy are Tier C (require active shift). Writes return 403
    // not_assigned_officer / not_update_author and 422 issue_closed when applicable.
    Route::get('issues/{issue}/officer-updates', [OfficerIssueUpdateController::class, 'index'])->name('issues.officer-updates.index');
    Route::post('issues/{issue}/officer-updates', [OfficerIssueUpdateController::class, 'store'])->name('issues.officer-updates.store');
    Route::patch('issues/{issue}/officer-updates/{officer_update}', [OfficerIssueUpdateController::class, 'update'])->name('issues.officer-updates.update');
    Route::delete('issues/{issue}/officer-updates/{officer_update}', [OfficerIssueUpdateController::class, 'destroy'])->name('issues.officer-updates.destroy');
    Route::get('issues/{issue}/officer-updates/attachments/{attachment}/download', [OfficerIssueUpdateAttachmentController::class, 'download'])->name('issues.officer-updates.attachments.download');

    Route::delete('issues/{issue}', [IssueController::class, 'destroy'])->name('issues.destroy');

    // Issue attachments. Uploads are authorized inside StoreIssueAttachmentRequest
    // to the authenticated, active regular user who owns the issue, mirroring the
    // owner-only edit/delete model so an author can add or replace files while
    // managing their report. Files are stored on a non-public local disk for
    // development (never served from `public`), capped at 5 files of up to 5 MB
    // each, and an issue may hold at most 5 attachments in total. Downloads
    // (GET `/issues/{issue}/attachments/{attachment}/download`) are Tier B for
    // officers (browse without shift). Authorized inside
    // DownloadIssueAttachmentRequest to the issue owner (active user), any active
    // officer, or any active manager; the controller confirms the
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

    // Issue chats. Index and message listing are Tier B (can browse without active shift);
    // open, close, send, and mark-read are Tier C (require active shift). Duplicate child
    // route ids resolve to the canonical parent via ResolveCanonicalIssue in controllers.
    Route::get('issues/{issue}/chats', [IssueChatController::class, 'index'])->name('issues.chats.index');
    Route::patch('issues/{issue}/chats/open', [IssueChatController::class, 'open'])->name('issues.chats.open');
    Route::patch('issues/{issue}/chats/{chat}/close', [IssueChatController::class, 'close'])->name('issues.chats.close');
    Route::get('issues/{issue}/chats/{chat}/messages', [IssueChatMessageController::class, 'index'])->name('issues.chats.messages.index');
    Route::post('issues/{issue}/chats/{chat}/messages', [IssueChatMessageController::class, 'store'])->name('issues.chats.messages.store');
    Route::post('issues/{issue}/chats/{chat}/messages/mark-read', [IssueChatMessageController::class, 'markRead'])->name('issues.chats.messages.mark-read');
    Route::get('issues/{issue}/chats/{chat}/messages/{message}/attachments/{attachment}/download', [IssueMessageAttachmentController::class, 'download'])->name('issues.chats.messages.attachments.download');

    // Issue comments. Listing (Index) is Tier B (can browse without active shift);
    // store, update, destroy, and visibility update are Tier C (require active shift).
    Route::get('issues/{issue}/comments', [IssueCommentController::class, 'index'])->name('issues.comments.index');
    Route::post('issues/{issue}/comments', [IssueCommentController::class, 'store'])->name('issues.comments.store');
    Route::patch('issues/{issue}/comments/{comment}', [IssueCommentController::class, 'update'])->name('issues.comments.update');
    Route::delete('issues/{issue}/comments/{comment}', [IssueCommentController::class, 'destroy'])->name('issues.comments.destroy');
    Route::patch('issues/{issue}/comments/{comment}/visibility', [IssueCommentController::class, 'updateVisibility'])->name('issues.comments.visibility.update');

    // Issue feedback. Listing (Index) is Tier B (can browse without active shift);
    // store, update, and destroy are Tier C (require active shift for officers, though usually for users).
    Route::get('issues/{issue}/feedback', [IssueFeedbackController::class, 'index'])->name('issues.feedback.index');
    Route::post('issues/{issue}/feedback', [IssueFeedbackController::class, 'store'])->name('issues.feedback.store');
    Route::patch('issues/{issue}/feedback/{feedback}', [IssueFeedbackController::class, 'update'])->name('issues.feedback.update');
    Route::delete('issues/{issue}/feedback/{feedback}', [IssueFeedbackController::class, 'destroy'])->name('issues.feedback.destroy');
    // Community Posts CRUD. Listing and reads are available to any authenticated actor
    // and rely on CommunityPostFeedQuery / CommunityPostVisibilityQuery for scoping.
    // Users require an active district subscription (feedDistricts) matching the post.
    // Officers use their district_officer scope automatically. Managers browse city-wide.
    // Writes (create/update/delete) are restricted to active officers with an active shared
    // shift (Tier C). Ordinary managers can update visibility in their districts; main
    // managers can update visibility city-wide.
    Route::get('community-posts', [\App\Http\Controllers\CommunityPostController::class, 'index'])->name('community-posts.index');
    Route::post('community-posts', [\App\Http\Controllers\CommunityPostController::class, 'store'])->name('community-posts.store');
    Route::get('community-posts/{community_post}', [\App\Http\Controllers\CommunityPostController::class, 'show'])->name('community-posts.show');
    Route::match(['put', 'patch'], 'community-posts/{community_post}', [\App\Http\Controllers\CommunityPostController::class, 'update'])->name('community-posts.update');
    Route::delete('community-posts/{community_post}', [\App\Http\Controllers\CommunityPostController::class, 'destroy'])->name('community-posts.destroy');
    Route::patch('community-posts/{community_post}/visibility', [\App\Http\Controllers\CommunityPostController::class, 'updateVisibility'])->name('community-posts.visibility.update');

    // Community Post Attachments. Same rules as issue attachments (max 5 files, 5 MB each).
    // Download uses visibility-only authorization (Tier B).
    // Upload/Delete are Tier C (hub-active required) and restricted to the authoring officer
    // or officers assigned to the post's district.
    Route::post('community-posts/{community_post}/attachments', [\App\Http\Controllers\CommunityPostAttachmentController::class, 'store'])->name('community-posts.attachments.store');
    Route::get('community-posts/{community_post}/attachments/{attachment}/download', [\App\Http\Controllers\CommunityPostAttachmentController::class, 'download'])->name('community-posts.attachments.download');
    Route::delete('community-posts/{community_post}/attachments/{attachment}', [\App\Http\Controllers\CommunityPostAttachmentController::class, 'destroy'])->name('community-posts.attachments.destroy');

    // Saved Community Posts (User only). Users can save visible posts in their feed districts.
    // Indexing saved posts uses `GET /api/community-posts?saved_only=1`.
    Route::post('community-posts/{community_post}/save', [\App\Http\Controllers\Auth\UserSavedCommunityPostController::class, 'store'])->name('community-posts.save');
    Route::delete('community-posts/{community_post}/save', [\App\Http\Controllers\Auth\UserSavedCommunityPostController::class, 'destroy'])->name('community-posts.unsave');
});
