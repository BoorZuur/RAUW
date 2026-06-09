<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## RAUW Backend API

This Laravel application exposes the backend API for RAUW. Local API development is documented in the repository-level [local development guide](../../docs/local-development.md).

## Backend Authentication

The backend uses bearer token authentication for API consumers. Officer registration and the shared login endpoint return Laravel Sanctum bearer tokens plus safe profile payloads. These endpoints are API-only and do not create session authentication.

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/api/auth/register/user` | None | Register a user and return an immediately usable bearer token. |
| `POST` | `/api/auth/register/officer` | None | Register an officer and return an immediately usable bearer token. |
| `POST` | `/api/auth/login` | None | Authenticate a user, officer, or manager. |
| `GET` | `/api/auth/me` | `Authorization: Bearer <token>` | Return the current actor type and profile. |
| `PATCH` | `/api/auth/me` | `Authorization: Bearer <token>` | Update own username, email, and password; officers may also update badge number. Returns refreshed profile. Departments are not self-service on this path. |
| `PATCH` | `/api/auth/me/districts` | `Authorization: Bearer <token>` | Replace district assignments for the current active manager or active officer. Users receive `403`. |
| `POST` | `/api/auth/start-shift` | `Authorization: Bearer <token>` | Start the officer's shared shift when at the assigned hub (requires `latitude`/`longitude`). |
| `POST` | `/api/auth/logout` | `Authorization: Bearer <token>` | Revoke the current bearer token and close its `officer_sessions` row; shared shift is preserved. |

Managers may end an officer's shared shift via `PATCH /api/officers/{officer}/end-shift` (active manager only; does not revoke tokens).

### Officer Shared Shift

Officers share one shift clock across all devices via `officers.hub_active_until`. Workflow access (Tier C) is gated on **`is_active` and a future `hub_active_until`**, not on the bearer token's Sanctum `hub-active` ability (audit-only). Officers authenticate through `POST /api/auth/login` with device GPS coordinates (`latitude`, `longitude`). Users and managers ignore these fields.

| Scenario | Login response | Shared shift (`hub_active_until`) | Workflow (Tier C) |
|----------|----------------|-----------------------------------|-------------------|
| Hub login, no active shift | `hub_active: true`, `hub_active_until` ~now+10h | Started | Enabled |
| Hub login, shift already active | `hub_active: true`, existing `hub_active_until` (not extended) | Unchanged | Enabled |
| Outside login, active shift | `hub_active: true` (shared shift) | Unchanged | Enabled |
| Outside login, no shift | `hub_active: false` | Unchanged (null) | Blocked |
| No `hub_id` assigned | **403** `hub_not_assigned` | — | Login blocked |
| Inactive hub or missing hub coordinates | Login succeeds; shift not started remotely | Unchanged unless hub-eligible | Blocked without shift |

**Start shift:** `POST /api/auth/start-shift` with `latitude`/`longitude` starts the shared shift when the officer is at the assigned hub and no shift is active. Returns **422** `shift_already_active` when a shift is already active; **403** `outside_hub_radius` when coordinates are outside the hub; **403** `hub_not_assigned` when `hub_id` is null.

**Registration** (`POST /api/auth/register/officer`) requires `latitude`/`longitude` for validation and audit session rows but **does not start a shift** — response is always `hub_active: false`, `hub_active_until: null`. Registration does not require `hub_id`; officers without a hub cannot log in until a manager assigns one via `PATCH /api/officers/{officer}/hub`.

**Logout:** revokes only the current token and closes that token's open `officer_sessions` row; other devices and the shared shift remain valid.

**Hub reassignment / manager end-shift:** `PATCH /api/officers/{officer}/hub` and `PATCH /api/officers/{officer}/end-shift` clear `hub_active_until` without revoking tokens. **Officer disable** revokes all tokens, closes all sessions, and ends the shift.

**Middleware:** `officer.hub-active` gates Tier C workflow routes by reading `hub_active_until` on the officer row. **Tier B** (browse without an active shift): `GET /api/issues` (embeds `officer_resolution` when present; omits `status_history`), `GET /api/issues/{issue}` (same `officer_resolution` plus `status_history` for officers/managers), `GET /api/issues/{issue}/officer-resolution`, and authenticated attachment downloads (`GET .../attachments/.../download`, `GET .../officer-resolution/attachments/.../download`). **Tier C** (hub-active required): `POST .../assign-self`, `POST .../unassign-self`, `PATCH .../status`, and officer-resolution `POST`/`PATCH`. Also whitelisted without a shift: profile/auth (`GET/PATCH /api/auth/me`, logout, `POST /api/auth/start-shift`, district self-service), reference reads (hubs, districts, departments, categories), and `GET /api/officer-sessions` (authorization still requires an active manager).

**Error codes** (`message` + `code`):

| Code | HTTP | When |
|------|------|------|
| `hub_active_required` | 403 | Officer on Tier C without active shared shift (`hub_active_until` null or past) |
| `hub_not_assigned` | 403 | Officer login or start-shift when `hub_id` is null |
| `outside_hub_radius` | 403 | `POST /api/auth/start-shift` when coords outside hub radius / ineligible hub |
| `shift_already_active` | 422 | `POST /api/auth/start-shift` when `hub_active_until` is already in the future |
| `account_inactive` | 403 | Authenticated inactive actor on a non-whitelisted route |

**Environment:**

| Variable | Default | Purpose |
|----------|---------|---------|
| `OFFICER_HUB_ACTIVE_TTL_HOURS` | `10` | Shared shift TTL after hub-radius login or start-shift |
| `SANCTUM_TOKEN_EXPIRATION` | `10080` | Sanctum token lifetime in minutes (7 days) |

Configure in `apps/backend/.env`; see `config/officer.php` and `config/sanctum.php`.

**GPS trust (v1):** Coordinates are client-reported only. There is no mock-location detection, no background geofence, and no per-request re-check. Hub-active is operational policy, not cryptographic proof of presence. Consumer GPS accuracy (±5–20 m) is absorbed by the default 100 m hub radius; main managers may adjust per-hub `radius_meters` (10–5000) on hub create/update. Login coordinates are audited on `officer_sessions` (`start_lat`, `start_lng`, `distance_meters_at_login`).

**Managers** may list officer login sessions via `GET /api/officer-sessions` (paginated, filters: `officer_id`, `hub_id`, `is_hub_active`).

### Manual Token Flow

1. Send either a JSON officer registration request:

   ```bash
   curl -X POST http://127.0.0.1:8001/api/auth/register/officer \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -d '{"username":"new-officer","email":"new.officer@example.com","password":"password123","confirm_password":"password123","badge_number":"BOA-1234","department_ids":[1],"latitude":51.9106846,"longitude":4.4814932}'
   ```

   Or send a JSON login request. Users and managers use `email` and `password` only:

   ```bash
   curl -X POST http://127.0.0.1:8001/api/auth/login \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -d '{"email":"demo.user@example.com","password":"password"}'
   ```

   Officers must include device coordinates:

   ```bash
   curl -X POST http://127.0.0.1:8001/api/auth/login \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -d '{"email":"demo.officer@example.com","password":"password","latitude":51.9106846,"longitude":4.4814932}'
   ```

2. Copy the `access_token` from the successful response.
3. Send protected requests with `Authorization: Bearer <access_token>`:

   ```bash
   curl http://127.0.0.1:8001/api/auth/me \
     -H "Accept: application/json" \
     -H "Authorization: Bearer <access_token>"
   ```

4. Revoke the current token when finished:

   ```bash
   curl -X POST http://127.0.0.1:8001/api/auth/logout \
     -H "Accept: application/json" \
     -H "Authorization: Bearer <access_token>"
   ```

Successful registration and login responses include:

```json
{
  "token_type": "Bearer",
  "access_token": "<token>",
  "actor_type": "user",
  "profile": {
    "id": 1,
    "username": "demo.user",
    "email": "demo.user@example.com"
  }
}
```

Supported `actor_type` values are `user`, `officer`, and `manager`. Officer login and registration responses also include `hub_active` and `hub_active_until` at the top level and inside `profile`.

Officer registration always returns `actor_type: "officer"` and uses the same safe officer profile serializer as login, including fields such as `username`, `email`, `badge_number`, `departments`, `hub_active`, `hub_active_until`, and a `districts` array of compact district objects when assignments are loaded. `department_ids` is required during registration, must contain at least one existing department ID, and cannot contain duplicates. `latitude` and `longitude` are required. Optional `district_ids` may be supplied to attach one or more active districts through the `district_officer` pivot. Passwords and secrets are never returned. Registration validates `username` and `badge_number` uniqueness within the officers table and validates `email` uniqueness across users, officers, and managers.

Hubs are Rotterdam BOA cluster locations. Each hub has a `radius_meters` column (default 100) for officer hub-active login evaluation — distinct from `districts.radius_meters` used for district auto-assignment. Officers and managers have a home hub via `hub_id` and return `hub_id` plus a compact `hub` object (including `radius_meters`) in profile and list responses when the relation is loaded. Hub reads (`GET /api/hubs`, `GET /api/hubs/{hub}`) are available to any authenticated actor. Hub mutations require an authenticated active main manager. Hub create requires `latitude` and `longitude` (not geocoded server-side). Optional `radius_meters` (10–5000) may be set on create/update.

New hubs default to inactive on create (`is_active=false`); the seeder activates the four Rotterdam cluster hubs. Deactivate or reactivate hubs via `PATCH` with `is_active`; there is no dedicated `/disable` route. Deactivation returns `422` when active districts or active officers remain assigned (managers assigned to the hub do not block deactivation). Deleting a hub is blocked with `409 Conflict` while districts, officers, or managers still reference it. Setting an actor's hub via `PATCH /api/officers/{officer}/hub`, `PATCH /api/managers/{manager}/hub`, or `PATCH /api/main-managers/{manager}/hub` requires an active hub (`is_active=true`) and clears their district pivot so assignments can be re-established within the new hub. Officer hub reassignment also ends the shared shift without revoking tokens.

Manager creation uses the `departments` table through `department_ids`. Each manager must have at least one valid department, assignments are stored in the `department_manager` pivot table, and manager auth profiles return a `departments` array of compact objects (`id`, `code`, `name`). Officers keep the same `department_ids` / `department_officer` behavior and auth profiles also return departments in a `departments` array. Managers use the `district_manager` pivot, officers use the `district_officer` pivot, and both actor profile types return `districts` arrays of compact objects (`id`, `name`, `postal_prefix`) instead of a singular actor-side `district_id` or `district` object. When an actor has `hub_id`, manager-driven district assignment endpoints only accept districts in that hub; cross-hub IDs return `422`. Active managers and active officers can replace their own district assignments with `PATCH /api/auth/me/districts` and a JSON body such as `{"district_ids":[1,2]}`; an empty array clears all assignments. Active managers can also replace any officer's assignments with `PATCH /api/officers/{officer}/districts` using the same request body. Users do not have district assignments and receive `403 Forbidden` for self-service district updates. Department assignments are not self-service; use manager-protected department assignment endpoints instead.

District records are managed through `/api/districts`. Each district belongs to one hub (`hub_id` required on create and must reference an active hub). District create requires `center_lat` and `center_lng` (not geocoded server-side). Authenticated actors can list and show districts. Only active main managers can create, update, or delete districts. Deactivate or reactivate districts via `PATCH` with `is_active` (no `/disable` route). Deleting a district is blocked with `409 Conflict` while it is assigned to managers, assigned to officers, or referenced by issues.

Issue district handling is intentionally unchanged. `issues.district_id` remains a singular issue location/reference field and is not updated by actor district assignment endpoints or district CRUD.

Department deletion is blocked while a department is assigned to any manager or officer. Reassign those actors first; category pivot rows are still cleaned up automatically when an otherwise unused department is deleted.

Categories are readable by any authenticated actor (`GET /api/categories`, `GET /api/categories/{category}`). Create, update, deactivate or reactivate via `PATCH` with `is_active`, and hard delete require an authenticated active main manager; users, officers, ordinary managers, and inactive managers receive `403`. There is no dedicated `/disable` route. Main categories use `priority` for ordering (lower number = higher urgency). Subcategories inherit the parent main category's `priority` for issue urgency and are listed in alphabetical order by `name`. The removed `weight` field is rejected with `422`.

Issues are listed and shown to any authenticated actor. Create, update, and delete require the authenticated active user who owns the issue (`issues.user_id`). Issue departments are derived server-side from the selected category and returned as a read-only `departments` array; clients must not send department values in create or update bodies. Issue `priority` is a nullable unsigned integer on the same scale as main category `priority` (lower number = higher urgency). The server copies the main category's `priority` on create and whenever `category_id` changes; subcategory issues use the parent category's `priority`. Clients must not POST or PATCH `priority`.

Issue attachments may be uploaded or deleted only by the issue owner (active user). Downloads use visibility-only authorization (`IssueVisibilityQuery::canViewIssue`): any actor who may view the issue may download. Users probing hidden issues they do not own receive `404`; other unauthorized actors receive `403`. Files are served only through the authenticated download endpoint, not via public URLs.

### Issue duplicates and participants

Citizens can link a new report to an existing canonical issue instead of creating a standalone duplicate thread. The flow is: **similar-check** → optional **create with `duplicate_of_id`** → optional **join/leave** on the canonical.

**Similar-check** (`POST /api/issues/similar-check`, active users only) scores up to 50 open or `in_behandeling` canonical issues in the requested district and returns the top five in `own_matches` (actor-owned, `linkable: false`) and `matches` (other users, `linkable: true`). Scoring uses category match, recency, postal prefix, and optional GPS distance bands.

**Create duplicate** (`POST /api/issues` with optional `duplicate_of_id`) creates a **hidden** child issue owned by the author (`visibility = hidden`, `duplicate_of_id` set). The canonical `duplicate_count` increments. When the author is not already a participant, a row is added on the canonical with `joined_via = duplicate` and `via_issue_id` pointing at the child; `participant_count` increments. Targets must be open or `in_behandeling` canonical issues visible to the author. Normal creates (no `duplicate_of_id`) add a creator participant row (`joined_via = creator`) with `participant_count = 1`.

**Join / leave** (`POST /api/issues/{issue}/join`, `DELETE /api/issues/{issue}/leave`, active users only) manage manual participation on a **canonical** issue. Join on a duplicate child id returns **422** `cannot_join_duplicate_child`. First join returns **201**; repeat joins are idempotent (**200**). Leave resolves child route ids to the canonical parent, removes the participation row, and decrements `participant_count`; not participating returns **422** `not_participant`.

**List filters:** `participating=1` (users only) lists owned duplicate children where the user still participates on the canonical. `include_duplicates=1` (officers/managers only) includes duplicate child rows. Default browse hides other users' duplicate children for citizens and hides all children for officers/managers unless `include_duplicates=1`. `mine` and `participating` are mutually exclusive.

**Participant visibility:** Users who participate on a canonical they do not own receive status, resolution, counters, and participation context while title, content, location, author, and attachments are redacted (`author: { is_participant: true }`). Owners always see full payloads for issues they own; officers and managers are never redacted.

**Delete semantics** (`DELETE /api/issues/{issue}`, optional JSON body `{ "leave_participation": false }`):

| Target | Behavior |
|--------|----------|
| Duplicate child | Decrements canonical `duplicate_count`. `leave_participation: false` (default) keeps canonical participation; `true` removes the participation row and decrements `participant_count`. |
| Canonical with children | Promotes oldest child to canonical, re-parents siblings, migrates participants, recalculates counters. |
| Canonical without children | Simple hard delete. |

**Officer duplicates list** (`GET /api/issues/{canonical}/duplicates`, officers and managers only) paginates duplicate children oldest-first with full `IssueResource` payloads. Child route ids return **422** `issue_not_canonical`; users receive **403**.

**Structured error codes (duplicates and participants)**

| Code | HTTP | When |
|------|------|------|
| `duplicate_target_not_found` | 404 | `duplicate_of_id` target missing or not visible to author |
| `issue_not_matchable` | 422 | Target canonical not `open` or `in_behandeling` |
| `cannot_duplicate_self` | 422 | Author owns the canonical target |
| `cannot_join_duplicate_child` | 422 | Join attempted on duplicate child id |
| `not_participant` | 422 | Leave when not participating on canonical |
| `issue_not_canonical` | 422 | Officer duplicates list on duplicate child id |
| `issue_is_duplicate_child` | 422 | Operation requires canonical issue |

### Officer issue workflows

Officers may **browse** issues and resolution attachments without an active shared shift (**Tier B**). Assignment, status changes, and field-report writes are **Tier C**: active officers need `hub_active_until` in the future or they receive **403** `hub_active_required`. Users and managers are not subject to hub-active gating but cannot call officer-only write endpoints (they receive **403** `This action is unauthorized.`).

Officer write paths use a **validate-after-lock** pattern: cheap visibility and district checks run before the transaction; assignee, transition, assignability, and duplicate-resolution checks run on the row after `lockForUpdate()`. Officer-resolution attachment uploads (POST/PATCH) enforce the cumulative cap (max **3**) and perform disk I/O inside the same locked transaction so concurrent requests cannot exceed the limit.

**District scoping:** An officer may act only on issues whose `district_id` matches one of their `district_officer` pivot assignments. Otherwise **403** `officer_not_in_district`. The demo officer is seeded in the **Cool** wijk (`district_id: 1`).

**Self-assign / unassign**

| Method | Path | Who | Notes |
|--------|------|-----|-------|
| `POST` | `/api/issues/{issue}/assign-self` | Active officer | Idempotent when already assigned to self. **409** `issue_already_assigned` when another officer owns it (no takeover). **422** `issue_not_assignable` when status is `opgelost` or `gesloten`. Open issues also transition to `in_behandeling` with one status history row. |
| `POST` | `/api/issues/{issue}/unassign-self` | Current assignee only | Clears `assigned_officer_id`; status unchanged. **403** `not_assigned_officer` for non-assignees. |

`assigned_officer_id` is read-only on user-owned create/update; use assign-self/unassign-self instead.

**Status PATCH**

| Method | Path | Who | Notes |
|--------|------|-----|-------|
| `PATCH` | `/api/issues/{issue}/status` | Assigned active officer | Body: `{ "status": "...", "note": "..." }`. Directed transitions only: `open` → `in_behandeling`; `in_behandeling` → `opgelost`; `opgelost` → `gesloten`. Same status or invalid transitions → **422**. Sets `resolved_at` on first transition to `opgelost`. Appends one status history row (no GPS coordinates). |

**Officer resolution (field report)**

Distinct from user satisfaction feedback in `issue_resolutions`. At most **one** officer report per issue (`officer_issue_resolutions.issue_id` unique).

| Method | Path | Who | Notes |
|--------|------|-----|-------|
| `GET` | `/api/issues/{issue}/officer-resolution` | Any actor who can view the issue | **404** when no report exists. |
| `POST` | `/api/issues/{issue}/officer-resolution` | Current assignee (multipart) | **409** `officer_resolution_exists` on duplicate; use PATCH to update. **422** `issue_closed` when status is `gesloten`; `opgelost` remains writable. Attachment cap enforcement and upload disk I/O run inside the locked transaction. |
| `PATCH` | `/api/issues/{issue}/officer-resolution` | Current assignee (multipart) | Update title/content; optional `remove_attachment_ids` and new `files`. `officer_id` is overwritten with the editing officer (last editor). **422** `issue_closed` when status is `gesloten`; `opgelost` remains writable. **422** on `remove_attachment_ids` when ids do not belong to the resolution. Attachment cap enforcement and upload disk I/O run inside the locked transaction. |
| `GET` | `/api/issues/{issue}/officer-resolution/attachments/{attachment}/download` | Any actor who can view the issue (Tier B) | Visibility-only auth (`IssueVisibilityQuery::canViewIssue`, Q8 / D15-A) in `authorize()`; users probing hidden issues they do not own receive **404**; other unauthorized actors receive **403**. Attachment must belong to the route resolution; missing backing file → **404**. Streams from non-public local storage. |

Attachment limits: up to **3** images (`jpg`, `jpeg`, `png`, `gif`, `webp`) per resolution, **5 MB** each. PATCH validates `existing − removals + new_files ≤ 3` under row lock (including upload disk I/O). Uploads are content-validated (Symfony MIME sniff for images; issue user attachments also accept PDF via `%PDF-` magic bytes). Invalid `remove_attachment_ids` (not owned by the resolution) return **422** with a field error on `remove_attachment_ids`.

**Issue embeds:** `GET /api/issues` includes `officer_resolution` (with officer and attachments when present) and omits `status_history`. `GET /api/issues/{issue}` includes the same `officer_resolution` embed plus `status_history` for officers and managers only (newest first, no lat/lon); regular users never receive `status_history`. Prefer the dedicated GET path for resolution-only reads.

**Structured error codes (officer workflows)**

| Code | HTTP | When |
|------|------|------|
| `hub_active_required` | 403 | Officer on Tier C without active shared shift |
| `officer_not_in_district` | 403 | Officer workflow on issue outside assigned districts |
| `not_assigned_officer` | 403 | Status/unassign/resolution write without assignee ownership |
| `issue_already_assigned` | 409 | Self-assign when another officer already owns the issue |
| `officer_resolution_exists` | 409 | Duplicate POST on officer resolution |
| `issue_not_assignable` | 422 | Self-assign on `opgelost` or `gesloten` issues |
| `issue_closed` | 422 | Resolution POST/PATCH when issue status is `gesloten` |

Common auth status codes are:

- `201 Created` for successful officer registration.
- `200 OK` for successful login, profile read/update, district self-service, and logout requests.
- `403 Forbidden` when an authenticated actor is not permitted (for example, users on district self-service, inactive actors on profile PATCH with `code: account_inactive`, non-managers on category mutations, non-owners on issue writes, officers without an active shared shift on workflow routes with `code: hub_active_required`, officers without `hub_id` on login or start-shift with `code: hub_not_assigned`, start-shift outside hub radius with `code: outside_hub_radius`).
- `401 Unauthorized` for invalid credentials, inactive or ambiguous accounts, missing tokens, invalid tokens, and revoked tokens.
- `422 Unprocessable Entity` when auth validation fails, including missing or invalid login fields, missing officer coordinates, missing or invalid registration fields, password confirmation mismatch, invalid or missing officer or manager `department_ids`, duplicate officer username/email/badge number, prohibited `hub_id`/`hub_active_until` on profile PATCH, or `shift_already_active` on start-shift.

### Manual Shared Shift Test Checklist

After `php artisan migrate:fresh --seed` and starting the server on port 8001:

1. **Hub login starts shift** — Login as `demo.officer@example.com` at Cluster Centrum coords. Expect `hub_active: true`, `hub_active_until` ~10h ahead. `GET /api/issues` → 200.
2. **Re-login at hub does not extend** — Note `hub_active_until`. Hub login again immediately. Expect same `hub_active_until` (not extended).
3. **Outside login preserves shift** — With active shift, login from distant coords. Expect `hub_active: true` (shared shift). `GET /api/issues` → 200. `GET /api/auth/me` → `hub_active: true`.
4. **Outside login without shift** — Officer with expired/null shift, remote login. Expect `hub_active: false`. `GET /api/issues` → 200 (Tier B browse). `POST /api/issues/{id}/assign-self` → 403 `hub_active_required`.
5. **Registration does not start shift** — `POST /api/auth/register/officer` at hub coords. Expect `hub_active: false`, `hub_active_until: null`.
6. **Start shift route** — Authenticated officer at hub, no active shift: `POST /api/auth/start-shift` with coords → 200. Repeat → 422 `shift_already_active`. Remote coords → 403 `outside_hub_radius`. Officer with no `hub_id` → 403 `hub_not_assigned`.
7. **Logout preserves shift** — Device A starts shift. Device B logs in (any coords). Logout device B. Device A `GET /api/issues` still 200 until TTL.
8. **Logout scope** — Logout revokes only that token; `officer_sessions` row for that token closed; other sessions remain open.
9. **Hub reassignment ends shift** — Manager `PATCH /api/officers/{id}/hub`. Expect `hub_active_until` cleared; existing tokens still authenticate but Tier C → 403 until new shift started.
10. **Manager end-shift** — `PATCH /api/officers/{id}/end-shift`. Shift cleared; tokens not revoked; Tier C blocked.
11. **Disable full revoke** — Manager disables officer. All tokens invalid; shift cleared; all sessions closed.
12. **Hub deactivated mid-shift** — Deactivate hub while shift active. Officer workflows still work until `hub_active_until` expires.
13. **Manager session list** — `GET /api/officer-sessions` still works; audit fields populated; response does **not** include `personal_access_token_id`.
14. **Inactive actor structured 403** — Disable an officer but retain a stale token. `GET /api/issues` → 403 with `code: account_inactive`. Whitelisted `GET /api/auth/me` still works.
15. **Profile PATCH hub fields rejected** — `PATCH /api/auth/me` with `hub_id` or `hub_active_until` → 422 (prohibited).

### Manual Officer Issue Workflow Checklist

After hub login as `demo.officer@example.com` (Cool wijk / `district_id: 1`):

1. **Assign-self** — `POST /api/issues/{unassigned_open_issue}/assign-self` → 200, `assigned_officer_id` set; open issues also become `in_behandeling`.
2. **District block** — Officer without issue district → 403 `officer_not_in_district`.
3. **Conflict** — Second officer assigns same issue → 409 `issue_already_assigned`.
3b. **Terminal assign block** — `POST .../assign-self` on `opgelost`/`gesloten` issue → 422 `issue_not_assignable`.
4. **Unassign** — Current assignee `POST .../unassign-self` → 200, assignee cleared, status unchanged.
5. **Status** — `PATCH /api/issues/{id}/status` with `{ "status": "opgelost", "note": "Fixed" }` → 200, history row, `resolved_at` set.
6. **Invalid transition** — Direct `open` → `opgelost` → 422. Direct `in_behandeling` → `gesloten` → 422.
7. **Not assigned** — Another officer PATCH status → 403 `not_assigned_officer`.
8. **List vs show embeds** — `GET /api/issues` includes `officer_resolution` when present and omits `status_history`. Officer/manager `GET /api/issues/{id}` adds `status_history` (newest first, no lat/lon); user GET omits `status_history`.
9. **Resolution create** — Multipart POST with title, content, images → 201; second POST → 409.
10. **Resolution update** — PATCH with new title/content, remove one attachment, add one → 200, ≤3 attachments total.
10b. **Resolution blocked on gesloten** — PATCH (or POST) on issue with status `gesloten` → 422 `issue_closed`; `opgelost` issues remain writable.
11. **Resolution read** — User, officer, manager who can view issue → GET `/officer-resolution` 200; hidden issue → 404.
12. **Download** — Same visibility as show.
13. **Browse without shift** — Officer with expired shift → `GET /api/issues` and `GET /api/issues/{id}` still 200; Tier C writes → 403 `hub_active_required`.

For manual API testing, import the Postman collection and local environment from [`../../docs/postman`](../../docs/postman/README.md):

- [`rauw-backend.postman_collection.json`](../../docs/postman/rauw-backend.postman_collection.json)
- [`rauw-local.postman_environment.json`](../../docs/postman/rauw-local.postman_environment.json)

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
