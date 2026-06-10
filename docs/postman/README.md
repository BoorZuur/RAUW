# RAUW Backend Postman Collection

Use this Postman collection to test the backend-only authentication API against a local RAUW backend or another backend URL.

## Files

- `rauw-backend.postman_collection.json` — importable Postman Collection v2.1 file.
- `rauw-local.postman_environment.json` — local environment with `base_url` set to `http://127.0.0.1:8001`, an empty `access_token` variable, and default issue/filter variables for issue examples.

## Required Local Backend Setup

From `apps/backend`, install dependencies, configure the Laravel app, run migrations and seed local data, then serve the API:

```bash
cd apps/backend
composer i
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php -S 127.0.0.1:8001 -t public
```

The documented local backend URL is:

```text
http://127.0.0.1:8001
```

### CORS (browser / SPA clients)

The API does not use wildcard CORS origins. In `apps/backend/.env`, set:

| Variable | Purpose |
|----------|---------|
| `FRONTEND_URL` | Allowed origin for local development (default in `config/cors.php`: `http://localhost:5173`) |
| `FRONTEND_URL_PRODUCTION` | Optional additional production SPA origin |

Postman and server-side clients are not subject to CORS. Browser-based frontends must call the API from a configured origin or preflight requests will fail.

### Sanctum token lifetime

Personal access tokens expire after **7 days** (10080 minutes). Configure via `SANCTUM_TOKEN_EXPIRATION` in `.env` (see `config/sanctum.php`).

### Officer shared shift TTL

Shared shift workflow access lasts **10 hours** by default after hub-eligible login or `POST /api/auth/start-shift`. Configure via `OFFICER_HUB_ACTIVE_TTL_HOURS` in `.env` (see `config/officer.php`). All devices share one `hub_active_until` clock. This is separate from the 7-day Sanctum token expiry: tokens may remain valid while Tier C routes are blocked once the shift expires.

Local seeders may create deterministic demo accounts for manual testing. These credentials are local development fixtures only and are not production behavior.

| Actor type | Email | Password |
|------------|-------|----------|
| `user` | `demo.user@example.com` | `password` |
| `officer` | `demo.officer@example.com` | `password` |
| `manager` | `demo.manager@example.com` | `password` |

Supported `actor_type` values are `user`, `officer`, and `manager`. The local demo manager is seeded as the main manager and can create other managers. Production environments must provision the initial main manager through trusted operational setup, not through the public API.

Local seeders create four active Rotterdam cluster hubs (Cluster Zuid, Cluster Buitengebieden, Cluster Centrum, Cluster Noord), 67 Rotterdam wijken, and BOA/Jeugd categories. New hubs created via the API default to inactive until activated. The demo officer and demo manager belong to **Cluster Centrum** hub and the **Cool** wijk. Managers are assigned one or more departments through `department_ids` / the `department_manager` pivot. Officers are assigned one or more departments through `department_ids` / the `department_officer` pivot. Manager and officer district assignments are many-to-many: managers use `district_ids` / `district_manager`, and officers use `district_ids` / `district_officer`. District assignments must stay within the actor's home hub when `hub_id` is set.

Actor emails must be unique across users, officers, and managers. This prevents a shared-login email from matching more than one actor table.

## Import Into Postman

1. Open Postman.
2. Select **Import**.
3. Import `docs/postman/rauw-backend.postman_collection.json`.
4. Import `docs/postman/rauw-local.postman_environment.json`.
5. Select the **RAUW Local Backend** environment.
6. Confirm these variables are available:

| Variable | Local value | Notes |
|----------|-------------|-------|
| `base_url` | `http://127.0.0.1:8001` | Change this to point at another backend without editing each request. |
| `access_token` | blank | Filled automatically after **Auth / Login**, **Register User**, or **Register Officer**. Default token for users, officers, and general protected routes. |
| `officer_hub_active_token` | blank | Filled by **Auth / Login Officer at Hub** when `hub_active: true`. |
| `officer_remote_token` | blank | Filled by **Auth / Login Officer Remote** when `hub_active: false`. Used by **Officer Workflow Blocked (403 Smoke)**. |
| `main_manager_access_token` | blank | Filled by **Auth / Login as Main Manager** (`demo.manager@example.com` locally). Required for **Managers** folder, **Officers / List Officer Sessions**, and department create/update/deactivate/delete. |
| `manager_access_token` | blank | Set manually after logging in as an ordinary manager (`is_main_manager: false`). Use for officer district assignment and to verify category/district/department mutations return **403** for non-main managers. |
| `inactive_access_token` | blank | Copy a token before deactivating an actor in the database; used by **Auth / Inactive Actor - List Issues (403 Smoke)**. |
| `manager_id` | `2` | Ordinary manager path ID; overwritten by **Managers / Create Manager**. |
| `department_id` | `1` | Department path ID for update, deactivate (PATCH `is_active`), and delete examples. |
| `issue_id` | `1` | Filled automatically after **Issues / Create Issue**. Used by show, update, attachment, and delete examples. |
| `comment_id` | `1` | Filled automatically after **Comments / Add Comment**. Used by update, delete, and visibility examples. |
| `attachment_id` | `1` | Filled automatically after **Issues / Upload Attachments**. Used by authenticated download and delete. |
| `attachment_download_url` | blank | Filled automatically after **Issues / Upload Attachments** for reference. |
| `officer_resolution_attachment_id` | `1` | Filled automatically after **Issues / Officer workflows / Create Officer Resolution**. Used by update and download examples. |
| `officer_update_id` | `1` | Filled automatically after **Issues / Officer workflows / Create Officer Update**. Used by update and delete examples. |
| `officer_update_attachment_id` | `1` | Filled automatically after **Issues / Officer workflows / Create Officer Update** when the response includes attachments. Used by download example. |
| `hub_id` | `3` | Cluster Centrum hub ID after seeding (used by hub and district create examples). |
| `district_id` | `1` | Cool wijk ID after seeding (Cluster Centrum). |
| `category_id` | `1` | Parkeeroverlast main category ID after seeding. |
| `department_filter` | `wijkbeheer` | Example value for the issue list query param `department` (department code). Any-match against rows in `department_issue`. Not sent in create/update bodies. |
| `page` | `1` | Example issue list page. |
| `per_page` | `20` | Example issue list page size. Backend caps this at 100. |

## Recommended Request Order

1. Run **Auth / Register User** or **Auth / Register Officer** to create a public actor and auto-login, or run **Auth / Login** with an existing demo account. Officer registration requires at least one existing department ID, **latitude**, and **longitude**; local seeded departments normally include IDs `1` and `2`.
2. Run **Auth / Current Profile** to inspect the actor attached to the stored token.
3. **Officer shared shift flow:** Run **Auth / Login Officer at Hub** to start the shared shift, then **Issues / Officer workflows** writes (assign-self, status, resolution). **Tier B browse** (`GET /api/issues`, show, officer-resolution show, comments index, attachment downloads) works without an active shift. Run **Auth / Start Shift** when logged in without an active shift. Run **Auth / Login Officer Remote** (no active shift) then **Auth / Officer Workflow Blocked (403 Smoke)** — expects `hub_active_required` on **Tier C** `POST .../assign-self`, not on issue list. **Managers / End Officer Shift** clears the shift without revoking tokens. Profile, start-shift, and reference reads work without an active shift.
4. Run **Hubs / List Hubs**, **Districts / List Districts**, and **Departments / List Departments** to find local IDs for assignment examples.
5. Run **Auth / Login as Main Manager** (`demo.manager@example.com` / `password`) to populate `main_manager_access_token` and `access_token` for manager administration.
6. Run **Officers / List Officer Sessions** to inspect login audit rows (manager only).
7. Run **Auth / Register User - Duplicate Email (Generic 422)** to confirm duplicate registration returns the generic message (not Laravel “already been taken” wording).
8. Run **Managers / List Main Managers**, then **Managers / Create Manager** (stores `manager_id`). Create Manager does not issue a login token for the new manager.
9. For inactive-actor middleware: log in, copy the token to `inactive_access_token`, deactivate the actor in the database, then run **Auth / Current Profile** (200) and **Auth / Inactive Actor - List Issues (403 Smoke)** (403).
10. As a user, run **Issues / List My Issues** (`mine=1`). As an officer or manager, run **Issues / List Issues - Hidden Filter** (`visibility=hidden`).
11. Run **Departments / Deactivate Department** (main manager token, `PATCH` with `is_active: false`) before hard delete when testing department lifecycle.
12. Run **Managers / Update My Districts** or **Officers / Update My Districts** for self-service district assignments, or **Managers / Update Officer Districts** for manager-admin officer assignments.
13. Run **Districts / Create District**, **Update District**, and **Delete District** with **Auth / Login as Main Manager** (`main_manager_access_token`). Ordinary managers receive `403` on district writes. District deletion returns `409 Conflict` while the district is assigned to managers/officers or referenced by issues.
14. Run **Categories / List Categories** to find existing category IDs. Category reads work for any authenticated actor; mutations require an active main manager (`main_manager_access_token`).
15. Run **Auth / Login** with `demo.user@example.com` and password `password`, then **Issues / Create Issue** (stores `issue_id`).
15b. **Duplicates & participants:** Run **Issues / Duplicates & participants / Similar Check**, set `canonical_issue_id` to another user's open canonical issue, then **Create Issue as Duplicate** (stores `duplicate_child_issue_id`). Optionally run **Join Canonical Issue**, **List Participating Issues** (`participating=1`), and **Show Canonical as Participant** for redaction smoke tests. As an officer, run **List Issues - Include Duplicates** and **List Duplicate Children**.
16. Use **Issues / List Issues - Filtered Paginated** to combine `district_id`, `department` (env `department_filter`), and `category_id`.
17. **Officer issue workflows:** Run **Auth / Login Officer at Hub**, then **Issues / Create Issue** as a user (or pick a seeded issue in Cool / `district_id: 1`). Run **Issues / Officer workflows / Assign Self to Issue**, then **Update Issue Status**, **Create Officer Resolution**, **Show Officer Resolution**, **Update Officer Resolution**, and **Download Officer Resolution Attachment** in that order.
18. **Officer issue updates:** After assign-self (step 17), run **Create Officer Update** → **List Officer Updates** → **Update Officer Update** → **Download Officer Update Attachment** → **Delete Officer Update**. `officer_update_id` and `officer_update_attachment_id` are set automatically by **Create Officer Update** when the collection test script runs. Tier C writes require hub login; list and download work without an active shift (Tier B).
19. To test ordinary-manager privileges, log in as a created manager and copy the token to `manager_access_token` before **Managers / Update Officer Districts** or to confirm category/district/department mutations return **403** (not for successful category writes).
20. Run **Auth / Logout** when finished.

The collection stores the returned `access_token` automatically after a successful login, user registration, or officer registration. Manager creation intentionally does not update `access_token` because it returns only the created manager profile. If you disable collection scripts or the token is not stored, copy the `access_token` value from the auth response into the active Postman environment's `access_token` variable before calling protected endpoints.

Issue examples also store `issue_id` after issue creation, `comment_id` after **Comments / Add Comment**, and `attachment_id` / `attachment_download_url` after attachment upload. Attachment upload returns a `data: [...]` wrapper, and the collection stores these variables from `response.data[0]`. Attachment upload uses local, non-public development storage. Downloads and deletes require `Authorization: Bearer <token>` and use authenticated API routes.

Protected endpoints use this header:

```http
Authorization: Bearer <token>
```

## Endpoint Contract

### Register Officer

`POST {{base_url}}/api/auth/register/officer`

Register a backend API-only officer account. This creates an `Officer`, immediately issues a Laravel Sanctum bearer token, and returns the same auth response shape and safe officer profile serializer used by login. It does not create a session.

Request body:

```json
{
  "username": "new-officer",
  "email": "new.officer@example.com",
  "password": "password123",
  "confirm_password": "password123",
  "badge_number": "BOA-1234",
  "department_ids": [1],
  "district_ids": [1],
  "latitude": 51.9106846,
  "longitude": 4.4814932
}
```

`latitude` and `longitude` are **required** device GPS coordinates for validation and audit. Registration **does not start a shift** — always `hub_active: false`, `hub_active_until: null`. Registration does not require `hub_id`; officers without a hub cannot use login until a manager assigns one.

`department_ids` is required, must contain at least one **active** department ID (`is_active=true`), and cannot contain duplicates; inactive or unknown IDs return `422`. `district_ids` is optional, may be empty or omitted, must contain active existing district IDs when present, and cannot contain duplicates. Use **Departments / List Departments** and **Districts / List Districts** while authenticated to inspect available IDs.

Successful response shape:

```json
{
  "token_type": "Bearer",
  "access_token": "<token>",
  "actor_type": "officer",
  "hub_active": false,
  "hub_active_until": null,
  "profile": {
    "id": 1,
    "username": "new-officer",
    "email": "new.officer@example.com",
    "badge_number": "BOA-1234",
    "hub_id": 3,
    "hub_active": false,
    "hub_active_until": null,
    "departments": [
      {
        "id": 1,
        "code": "wijkbeheer",
        "name": "Wijkbeheer"
      }
    ],
    "districts": [
      {
        "id": 1,
        "name": "Cool",
        "postal_prefix": "3012"
      }
    ]
  }
}
```

Common error response:

- `422 Unprocessable Entity` with validation errors when required fields are missing, `email` is invalid, `password` is shorter than 8 characters, `confirm_password` does not match `password`, `department_ids` is missing, empty, duplicated, or references inactive/unknown departments, `latitude`/`longitude` is missing or invalid, `district_ids` is duplicated or references inactive/unknown districts, `username` or `badge_number` already exists in the officers table, or `email` already exists for any user, officer, or manager.

### Register User

`POST {{base_url}}/api/auth/register/user`

Register a public API user account. This creates a `User`, immediately issues a Laravel Sanctum bearer token, and returns the same auth response shape and safe user profile serializer used by login. It does not create an Officer or Manager row.

Request body:

```json
{
  "username": "new-user",
  "email": "new.user@example.com",
  "password": "password123",
  "confirm_password": "password123"
}
```

Successful response shape:

```json
{
  "token_type": "Bearer",
  "access_token": "<token>",
  "actor_type": "user",
  "profile": {
    "id": 1,
    "username": "new-user",
    "email": "new.user@example.com"
  }
}
```

Common error responses:

- `401 Unauthorized` and `403 Forbidden` are not expected for this public endpoint because no bearer token or main-manager authorization is required.
- `422 Unprocessable Entity` with validation errors when required fields are missing, `email` is invalid, `password` is shorter than 8 characters, `confirm_password` does not match `password`, or registration cannot complete. Duplicate `username` or `email` (across users, officers, and managers) returns the generic message `The provided credentials could not be registered.` — not field-specific “already been taken” text. Use **Auth / Register User - Duplicate Email (Generic 422)** in Postman to verify.

### Login

`POST {{base_url}}/api/auth/login`

Request body for users and managers uses email and password only:

```json
{
  "email": "demo.user@example.com",
  "password": "password"
}
```

**Officers** must also send device GPS coordinates:

```json
{
  "email": "demo.officer@example.com",
  "password": "password",
  "latitude": 51.9106846,
  "longitude": 4.4814932
}
```

Missing or invalid officer coordinates return `422`. Officers without `hub_id` receive `403` with `code: hub_not_assigned`. Hub-eligible login starts or joins the shared shift (`hub_active: true`, `hub_active_until`); re-login at hub does not extend an existing shift. Outside-radius login preserves an active shared shift; without a shift, `hub_active: false` and **Tier C** workflow writes return `403` `hub_active_required` while **Tier B** issue browse reads remain available. Use **Auth / Start Shift** to start a shift without re-login.

Successful response shape:

```json
{
  "token_type": "Bearer",
  "access_token": "<token>",
  "actor_type": "officer",
  "hub_active": true,
  "hub_active_until": "2026-06-08T22:00:00+00:00",
  "profile": {
    "id": 1,
    "username": "demo-officer",
    "email": "demo.officer@example.com",
    "badge_number": "BOA-0001",
    "hub_active": true,
    "hub_active_until": "2026-06-08T22:00:00+00:00",
    "departments": [],
    "districts": []
  }
}
```

Auth response metadata lives on the top-level wrapper. `actor_type` is not duplicated inside `profile`, and auth profiles omit internal fields such as `is_active`, `email_verified_at`, actor-side `district_id`, and `created_by_manager_id`. Officer and manager auth profiles include role-specific safe fields such as `badge_number`, `departments`, `is_main_manager`, and compact `districts` arrays when available. Passwords and secrets are never returned.

Common error responses:

- `401 Unauthorized` with `{"message":"Invalid credentials."}` for invalid, inactive, ambiguous, or unknown accounts.
- `403 Forbidden` with `{"message":"Officer hub assignment required before login.","code":"hub_not_assigned"}` when an officer has no `hub_id`.
- `403 Forbidden` with `{"message":"Hub-active session required.","code":"hub_active_required"}` on Tier C workflow routes when the officer has no active shared shift (not on Tier B issue browse reads).
- `403 Forbidden` with `code: outside_hub_radius` or `shift_already_active` (422) on start-shift.
- `422 Unprocessable Entity` with validation errors when `email` or `password` is missing or invalid, or when officer `latitude`/`longitude` is missing or out of range.

### Current Profile

`GET {{base_url}}/api/auth/me`

Requires `Authorization: Bearer <token>`.

Inactive actors may still call this route (whitelisted). Compare with **Inactive Actor - List Issues (403 Smoke)** in the Postman collection.

Successful response shape:

```json
{
  "actor_type": "user",
  "profile": {
    "id": 1,
    "username": "demo.user",
    "email": "demo.user@example.com"
  }
}
```

Common error response:

- `401 Unauthorized` when the bearer token is missing, invalid, or revoked.

### Update My Profile

`PATCH {{base_url}}/api/auth/me`

Self-service identity update for users, officers, and managers. Send only fields to change. **Active:** `username`, `email`, `password`; officers may also send `badge_number`. **Inactive:** `username` and `password` only (`email` and `badge_number` return `422`). When `password` is present, `confirm_password` must match. Department, district, `hub_id`, `hub_active_until`, and other privileged fields are rejected with `422`.

### Inactive actor middleware

Valid bearer tokens for deactivated actors (`is_active = false`) receive `403 Forbidden` with `{"message":"This account is inactive.","code":"account_inactive"}` on most protected routes. Whitelisted while inactive: `GET` and `PATCH` `/api/auth/me` (identity only), `POST` `/api/auth/logout`. `PATCH /api/auth/me/districts` and operational APIs such as `GET /api/issues` return `403`. Postman: copy a pre-deactivation token into `inactive_access_token`, then run **Auth / Inactive Actor - List Issues (403 Smoke)**.

Manager auth profiles return `departments` and `districts` arrays of compact objects:

```json
{
  "actor_type": "manager",
  "profile": {
    "id": 1,
    "username": "demo-manager",
    "email": "demo.manager@example.com",
    "departments": [
      {
        "id": 1,
        "code": "wijkbeheer",
        "name": "Wijkbeheer"
      }
    ],
    "is_main_manager": true,
    "districts": []
  }
}
```

Officer auth profiles also return a `departments` array because officers must belong to one or more departments, plus a `districts` array for assigned districts:

```json
{
  "actor_type": "officer",
  "profile": {
    "id": 1,
    "username": "demo-officer",
    "email": "demo.officer@example.com",
    "badge_number": "BOA-0001",
    "departments": [
      {
        "id": 1,
        "code": "wijkbeheer",
        "name": "Wijkbeheer"
      },
      {
        "id": 2,
        "code": "boa_jeugd",
        "name": "BOA Jeugd"
      }
    ],
    "districts": []
  }
}
```

### Self-Service District Assignment Updates

Self-service district assignment updates are implemented for active managers and active officers.

#### Update My Districts

`PATCH {{base_url}}/api/auth/me/districts`

Requires `Authorization: Bearer <token>` for an active manager or active officer. Users do not have district assignments and receive `403 Forbidden`.

Request body replaces the full current assignment set:

```json
{
  "district_ids": [1, 2]
}
```

Use an empty array to clear all assignments. Every ID must reference an active district and duplicates are rejected. The response is the refreshed auth profile with a `districts` array. This self-service endpoint only updates the authenticated manager's `district_manager` assignments or authenticated officer's `district_officer` assignments and never changes `issues.district_id`.

### Officer District Assignment Updates

#### Update My Districts

`PATCH {{base_url}}/api/auth/me/districts`

This officer self-service flow is implemented by the same `PATCH /api/auth/me/districts` route used for manager self-service.

Request body:

```json
{
  "district_ids": [1, 2]
}
```

The request replaces the authenticated officer's full district assignment set through the `district_officer` pivot and returns the refreshed auth profile with `districts`. It does not reassign issues or modify `issues.district_id`.

### List Main Managers

`GET {{base_url}}/api/main-managers`

Requires `Authorization: Bearer <token>` for an authenticated, active manager whose profile has `is_main_manager: true`. Users, officers, ordinary managers, inactive managers, and unauthenticated requests receive `403`.

Optional query params: `page` (default `1`), `per_page` (default `20`, max `100`). Results include only managers with `is_main_manager: true`, ordered by username ascending, with `departments` and `districts` compact arrays. The response uses Laravel pagination (`data`, `links`, `meta`).

### Update Main Manager

`PATCH {{base_url}}/api/main-managers/{managerId}`

Requires `Authorization: Bearer <token>` for an authenticated, active manager whose profile has `is_main_manager: true`. The path target must also be a main manager (`is_main_manager: true`); ordinary manager IDs return `404`.

Request body (send only fields to change):

```json
{
  "username": "updated-main",
  "email": "updated.main@example.com",
  "password": "newpassword123",
  "confirm_password": "newpassword123"
}
```

`is_main_manager`, `is_active`, `created_by_manager_id`, `department_ids`, and `district_ids` are rejected with `422`. Returns the updated `Manager` resource (no token fields).

### Disable Main Manager

`PATCH {{base_url}}/api/main-managers/{managerId}/disable`

Requires the same main-manager authorization as update. Sets `is_active` to `false` on the target main manager and returns the updated `Manager` resource. Returns `409` when the target is the last active main manager. Ordinary manager IDs return `404`.

### Enable Main Manager

`PATCH {{base_url}}/api/main-managers/{managerId}/enable`

Requires the same main-manager authorization as disable. Sets `is_active` to `true` on the target main manager and returns the updated `Manager` resource. Re-enabling an already active main manager is idempotent. Ordinary manager IDs return `404`.

### List Managers

`GET {{base_url}}/api/managers`

Requires `Authorization: Bearer <token>` for an authenticated, active manager whose profile has `is_main_manager: true`. Users, officers, ordinary managers, inactive managers, and unauthenticated requests receive `403`.

Optional query params: `page` (default `1`), `per_page` (default `20`, max `100`). Results include only managers with `is_main_manager: false`, ordered by username ascending, with `departments` and `districts` compact arrays. The response uses Laravel pagination (`data`, `links`, `meta`).

### Create Manager

`POST {{base_url}}/api/managers`

Requires `Authorization: Bearer <token>` for an authenticated, active manager whose profile has `is_main_manager: true`. Users, officers, ordinary managers, inactive managers, and unauthenticated requests cannot create managers.

The local `demo.manager@example.com` account is the seeded main manager for manual testing. In production, provision the initial main manager through trusted operational setup before using this endpoint.

Request body:

```json
{
  "username": "new-manager",
  "email": "new.manager@example.com",
  "password": "password123",
  "confirm_password": "password123",
  "department_ids": [1],
  "district_ids": [1]
}
```

`department_ids` is required, must contain at least one **active** department ID (`is_active=true`), and cannot contain duplicates; inactive or unknown IDs return `422`. Manager department assignments are stored through the `department_manager` pivot. Optional `district_ids` assigns active districts through the `district_manager` pivot and cannot contain duplicates. Use **Departments / List Departments** and **Districts / List Districts** to find valid IDs.

Successful response shape:

```json
{
  "actor_type": "manager",
  "id": 2,
  "username": "new-manager",
  "email": "new.manager@example.com",
  "is_active": true,
  "is_main_manager": false,
  "created_by_manager_id": 1,
  "departments": [
    {
      "id": 1,
      "code": "wijkbeheer",
      "name": "Wijkbeheer"
    }
  ],
  "districts": [
    {
      "id": 1,
      "name": "Cool",
      "postal_prefix": "3012"
    }
  ]
}
```

This endpoint does not auto-login the created manager and does not return an `access_token`. Use **Auth / Login** with the created manager's email and password if you want to authenticate as that manager.

Common error responses:

- `401 Unauthorized` when the bearer token is missing, invalid, or revoked.
- `403 Forbidden` when the authenticated actor is not an active main manager.
- `422 Unprocessable Entity` with validation errors when required fields are missing, `email` is invalid, `password` is shorter than 8 characters, `confirm_password` does not match `password`, `department_ids` is missing, empty, duplicated, or references inactive/unknown departments, `district_ids` is duplicated or references inactive/unknown districts, `username` already exists in the managers table, or `email` already exists for any user, officer, or manager.

### Update Manager

`PATCH {{base_url}}/api/managers/{managerId}`

Requires `Authorization: Bearer <token>` for an authenticated, active manager whose profile has `is_main_manager: true`. The path target must be an ordinary manager (`is_main_manager: false`); main manager IDs return `404`.

Request body (send only fields to change):

```json
{
  "username": "updated-manager",
  "email": "updated.manager@example.com",
  "password": "newpassword123",
  "confirm_password": "newpassword123"
}
```

`is_main_manager`, `is_active`, `created_by_manager_id`, `department_ids`, and `district_ids` are rejected with `422`. Returns the updated `Manager` resource (no token fields).

### Disable Manager

`PATCH {{base_url}}/api/managers/{managerId}/disable`

Requires the same main-manager authorization as update. Sets `is_active` to `false` on the target ordinary manager and returns the updated `Manager` resource. Main manager IDs return `404`. There is no last-manager guard for ordinary managers.

### Enable Manager

`PATCH {{base_url}}/api/managers/{managerId}/enable`

Requires the same main-manager authorization as disable. Sets `is_active` to `true` on the target ordinary manager and returns the updated `Manager` resource. Re-enabling an already active manager is idempotent. Main manager IDs return `404`.

#### Update Officer Districts

`PATCH {{base_url}}/api/officers/{officer}/districts`

Requires `Authorization: Bearer <token>` for an authenticated active manager. Users, officers, inactive managers, and unauthenticated requests cannot update officer districts.

Request body:

```json
{
  "district_ids": [1, 2]
}
```

The request replaces the target officer's full district assignment set through the `district_officer` pivot and returns that officer's refreshed profile with `districts`. It does not reassign issues or modify `issues.district_id`.

Common error responses:

- `401 Unauthorized` when the bearer token is missing, invalid, or revoked.
- `403 Forbidden` when the authenticated actor is not an active manager.
- `404 Not Found` when the officer route ID does not exist.
- `422 Unprocessable Entity` when `district_ids` is missing, not an array, contains duplicates, or references inactive/unknown districts.

#### Update Officer Departments

`PATCH {{base_url}}/api/officers/{officer}/departments`

Requires `Authorization: Bearer <token>` for an authenticated **active main manager** (`is_main_manager: true`). Users, officers, ordinary managers, inactive managers, inactive main managers, and unauthenticated requests receive `403 Forbidden`. This is stricter than **Update Officer Districts**, which allows any authenticated active manager.

Request body:

```json
{
  "department_ids": [1, 2]
}
```

Use an empty array to clear all assignments:

```json
{
  "department_ids": []
}
```

`department_ids` must be present and must be an array. Empty `[]` is valid for PATCH (unlike create/register, which require at least one department). IDs must reference **active** departments (`is_active=true`); duplicates, inactive, and unknown IDs are rejected with `422`.

The request replaces the target officer's full department assignment set through the `department_officer` pivot and returns `{ "actor_type": "officer", "profile": { ... } }` with a refreshed `departments` array.

Common error responses:

- `401 Unauthorized` when the bearer token is missing, invalid, or revoked.
- `403 Forbidden` when the authenticated actor is not an active main manager.
- `404 Not Found` when the officer route ID does not exist or the officer is soft-deleted.
- `422 Unprocessable Entity` when `department_ids` is missing, not an array, contains duplicates, or references inactive/unknown departments.

#### Update Manager Departments

`PATCH {{base_url}}/api/managers/{manager}/departments`

Requires `Authorization: Bearer <token>` for an authenticated **active main manager**. A main manager may update any manager, including other main managers.

Request body example:

```json
{
  "department_ids": [1]
}
```

Clear all manager department assignments with `"department_ids": []`.

Returns the flat `Manager` resource shape (same as **Create Manager**), including `departments` and `districts` compact arrays. Soft-deleted managers return `404`.

Common error responses match officer department PATCH: `401`, `403` (not active main manager), `404`, `422`.

#### Sync Manager Districts

`PATCH {{base_url}}/api/managers/{manager}/districts`

Requires `Authorization: Bearer <token>` for an authenticated **active main manager**. The path target must be an ordinary manager (`is_main_manager: false`); main manager IDs return `404`.

Request body example:

```json
{
  "district_ids": [1, 2]
}
```

Use `"district_ids": []` to clear all manager district assignments. IDs must reference active districts; duplicates, inactive, and unknown IDs return `422`. Returns the flat `Manager` resource with refreshed `districts`. This only updates `district_manager` and never modifies `issues.district_id`.

### Hubs

Hub endpoints manage Rotterdam BOA cluster hubs. Any authenticated actor can list and show hubs. Create, update, and delete require an authenticated active main manager. Hub create requires `latitude` and `longitude` (not geocoded server-side). New hubs default to `is_active=false` on create; send `is_active: true` to create an already-active hub. Seeded cluster hubs are active. Deactivate or reactivate via `PATCH` with `is_active` (no `/disable` route). Deactivation returns `422` when active districts or active officers remain assigned; managers assigned to the hub do not block deactivation. Deleting a hub returns `409 Conflict` while districts, officers, or managers still reference it.

Common requests:

- `GET {{base_url}}/api/hubs` — list hubs with reference counts.
- `GET {{base_url}}/api/hubs/{id}` — show one hub.
- `POST {{base_url}}/api/hubs` — create a hub (main manager); requires `latitude` and `longitude`; defaults inactive.
- `PATCH {{base_url}}/api/hubs/{id}` — update a hub (main manager); send `{"is_active": false}` to deactivate or `{"is_active": true}` to reactivate.
- `DELETE {{base_url}}/api/hubs/{id}` — delete an eligible hub (main manager).

Hub assignment (clears district pivot on change; `hub_id` must reference an active hub):

- `PATCH {{base_url}}/api/officers/{officer}/hub` — active manager; body `{"hub_id": 3}`.
- `PATCH {{base_url}}/api/managers/{manager}/hub` — main manager; ordinary managers only.
- `PATCH {{base_url}}/api/main-managers/{manager}/hub` — main manager; main managers only.

### Districts

District endpoints use the `districts` table. Each district belongs to one hub via required `hub_id`, which must reference an active hub (`is_active=true`). District create requires `center_lat` and `center_lng` (not geocoded server-side). Managers are assigned to districts through the `district_manager` pivot and officers through the `district_officer` pivot; when an actor has `hub_id`, district assignments must stay within that hub (cross-hub IDs return `422`). Issue district handling is separate: `issues.district_id` remains a singular issue location/reference field.

Authenticated actors can list and show districts. District mutations (`POST`, `PATCH`, `PUT`, `DELETE`) require `Authorization: Bearer <token>` for an authenticated **active main manager** (`is_main_manager=true`). Users, officers, ordinary managers, inactive managers, and unauthenticated requests receive `403 Forbidden` on district writes.

Common requests:

- `GET {{base_url}}/api/districts` — list districts with manager/officer assignment counts and issue reference counts.
- `GET {{base_url}}/api/districts/{id}` — show one district.
- `POST {{base_url}}/api/districts` — create a district as an active main manager.
- `PATCH {{base_url}}/api/districts/{id}` — update a district as an active main manager; send `{"is_active": false}` to deactivate or `{"is_active": true}` to reactivate (no `/disable` route).
- `DELETE {{base_url}}/api/districts/{id}` — hard delete an eligible district as an active main manager.

Create body example:

```json
{
  "name": "Nieuwe Wijk",
  "hub_id": 3,
  "postal_prefix": "3017",
  "center_lat": 51.91,
  "center_lng": 4.48,
  "radius_meters": 1200,
  "is_active": true
}
```

District deletion is blocked until all manager assignments, officer assignments, and issue references are removed or changed. The delete endpoint returns `409 Conflict` instead of deleting a district still used by actor pivots or `issues.district_id`.

Common error responses:

- `401 Unauthorized` when the bearer token is missing, invalid, or revoked.
- `403 Forbidden` when the authenticated actor is not an active main manager for district mutations.
- `422 Unprocessable Entity` for validation failures, including duplicate district names or invalid coordinates.
- `409 Conflict` when deleting a district that is still assigned to one or more managers, assigned to one or more officers, or referenced by one or more issues.

### Categories

Category reads (`GET /api/categories`, `GET /api/categories/{id}`) require `Authorization: Bearer <token>` for any authenticated actor (user, officer, or manager). Category mutations require an authenticated, active main manager (`is_main_manager: true`). Users, officers, ordinary managers, inactive managers, and unauthenticated requests receive `403` on mutations.

Categories belong to one or more departments through the `category_department` many-to-many pivot. Use `department_ids` in create/update requests to attach existing departments.

Main categories have `parent_id: null` and use `priority` for ordering (lower number = higher urgency). Subcategories reference an active main category with `parent_id` and sort alphabetically by name. Nested subcategories are rejected. The legacy `weight` field is rejected with 422.

Common requests:

- `GET {{base_url}}/api/categories` — list main categories with children and departments.
- `GET {{base_url}}/api/categories/{id}` — show one category.
- `POST {{base_url}}/api/categories` — create a main category or subcategory.
- `PATCH {{base_url}}/api/categories/{id}` — update fields and, when `department_ids` is present, replace department assignments; send `{"is_active": false}` to deactivate or `{"is_active": true}` to reactivate (no `/disable` route).
- `DELETE {{base_url}}/api/categories/{id}` — guarded hard delete for eligible records only.

Main category body example:

```json
{
  "name": "Parkeeroverlast",
  "parent_id": null,
  "department_ids": [2],
  "priority": 1,
  "is_active": true
}
```

Subcategory body example:

```json
{
  "name": "Parkeren op stoep",
  "parent_id": 1,
  "department_ids": [2],
  "is_active": true
}
```

Common error responses:

- `401 Unauthorized` when the bearer token is missing, invalid, or revoked.
- `403 Forbidden` when the authenticated actor is not an active manager.
- `422 Unprocessable Entity` for validation failures, including missing departments, invalid parent categories, nested subcategories, using `priority` on subcategories, or sending the removed `weight` field.
- `409 Conflict` when hard deleting a main category that still has subcategories: `Cannot delete a main category while it still has subcategories.`
- `409 Conflict` when hard deleting a category that is still referenced by existing issues: `Cannot delete this category because it is still referenced by existing records. Disable it instead.`

### Departments

Department endpoints use the `departments` table. Categories are attached through the `category_department` pivot, so a category can belong to multiple departments. Managers reference this table through the `department_manager` pivot and must have one or more departments; officers reference it through the `department_officer` pivot and must have one or more departments.

Issue departments are derived from the selected category on create and whenever `category_id` changes, persisted only through the `department_issue` pivot (there is no `issues.department` column). Clients must not send issue `department` in create or update bodies. Issue responses expose a read-only `departments` array only. List filtering uses query param `department` with a department code (`department_filter` in the Postman environment); any-match semantics apply across pivot assignments.

Department mutations require `Authorization: Bearer <token>` for an authenticated, active main manager (`is_main_manager: true`). Ordinary managers, users, officers, inactive managers, and unauthenticated requests cannot create, update, or delete departments.

Common requests:

- `GET {{base_url}}/api/departments` — list departments with `categories_count`.
- `GET {{base_url}}/api/departments/{id}` — show one department.
- `POST {{base_url}}/api/departments` — create a department as a main manager.
- `PATCH {{base_url}}/api/departments/{id}` — update a department as a main manager; send `{"is_active": false}` to deactivate or `{"is_active": true}` to reactivate (no `/disable` route). Deactivation returns `409` when the department is still referenced by existing records.
- `DELETE {{base_url}}/api/departments/{id}` — hard delete a department as a main manager.

Create body example:

```json
{
  "code": "groenbeheer",
  "name": "Groenbeheer",
  "is_active": true
}
```

Departments assigned to any manager or officer cannot be deleted until those actor assignments are changed. After no actors reference the department, deleting it automatically removes its `category_department` and `department_issue` assignments through database-level cascading. It does not delete category or issue records.

**Carve-out (actor department PATCH):** Create, register, and the delete guard above assume managers and officers keep at least one department while pivots still reference a row. `PATCH /api/officers/{officer}/departments` and `PATCH /api/managers/{manager}/departments` (main manager only) intentionally allow `"department_ids": []` to clear all assignments. That can leave an actor with zero departments and is valid for PATCH even though empty `department_ids` is rejected on create/register. Clearing assignments first is the supported way to unblock department deletion when the delete guard cites remaining actor pivots.

Common error responses:

- `401 Unauthorized` when the bearer token is missing, invalid, or revoked.
- `403 Forbidden` when the authenticated actor is not an active main manager.
- `422 Unprocessable Entity` for validation failures, including duplicate department `code` values.
- `409 Conflict` when deleting a department that is still assigned to one or more managers or officers.

### Officers

Officer listing and enable/disable require an authenticated active officer or manager (list) or an authenticated active manager (disable/enable).

#### List Officer Sessions

`GET {{base_url}}/api/officer-sessions`

Requires `Authorization: Bearer <token>` for an authenticated, active manager. Users, officers, and inactive managers receive `403`.

Optional query params: `officer_id`, `hub_id`, `is_hub_active` (truthy/falsy), `page` (default `1`), `per_page` (default `20`, max `100`). Returns paginated login audit rows ordered newest-first by `shift_start`, including compact `officer` and `hub` summaries, login coordinates (`start_lat`, `start_lng`), `distance_meters_at_login`, `is_hub_active`, and `hub_active_until`. Internal `personal_access_token_id` is not exposed; use session `id` as the public identifier.

Common requests:

- `GET {{base_url}}/api/officers` — list officers ordered by username. Optional filters: `district_id`, `department_id`, `is_active` (omit for active-only default). Pagination: `page`, `per_page` (max 100).
- `PATCH {{base_url}}/api/officers/{officer}/disable` — deactivate an officer (`is_active=false`). Manager only.
- `PATCH {{base_url}}/api/officers/{officer}/enable` — reactivate an officer (`is_active=true`). Manager only; idempotent when already active.
- `PATCH {{base_url}}/api/auth/me/districts` — officer self-service district sync (see **Officer District Assignment Updates**).

### Issues

Issue CRUD is owner-only for writes (active user who owns `issues.user_id`). Listing and show are available to any authenticated actor with visibility scoping.

List query params (composable, AND semantics):

- `district_id`, `department` (code), `category_id` — all actors.
- `status` — `open`, `in_behandeling`, `opgelost`, or `gesloten`; all actors.
- `mine` — users only; restricts to owned issues.
- `visibility` — officers and managers only; `visible` or `hidden`.
- `assigned_officer_id` — officers and managers only.
- `unassigned` — officers and managers only (`unassigned=1`); mutually exclusive with `assigned_officer_id`.

Visibility write (officers and managers only):

`PATCH {{base_url}}/api/issues/{issue}/visibility`

```json
{
  "visibility": "hidden"
}
```

Returns `404` when the issue is not viewable by the caller (same rules as show). User-owned `PATCH /api/issues/{issue}` cannot change visibility.

Issue responses include read-only `status`, `assigned_officer_id`, and `visibility` fields. Officers and managers receive `status_history` on show (no GPS coordinates) and embedded `officer_resolution` when a report exists.

### Officer issue workflows

**Tier B** (browse without shift): `GET /api/issues`, `GET /api/issues/{issue}`, `GET .../officer-resolution`, `GET .../officer-updates`, `GET .../comments`, and attachment downloads (issue, resolution, and officer-update). **Tier C** (hub-active required): assign-self, unassign-self, status PATCH, resolution POST/PATCH, officer-update POST/PATCH/DELETE. Tier C without shift → **403** `hub_active_required`. District scoping on workflow writes: officer must be in the issue's district via `district_officer` or **403** `officer_not_in_district`. Officer writes use validate-after-lock (mutable checks after `lockForUpdate`). Resolution and officer-update attachment uploads enforce the cumulative cap and perform disk I/O inside the same locked transaction.

**Self-assign / unassign**

- `POST {{base_url}}/api/issues/{issue}/assign-self` — active officer; idempotent when already assigned to self; **409** `issue_already_assigned` when another officer owns it; **422** `issue_not_assignable` on `opgelost`/`gesloten`; open issues transition to `in_behandeling`.
- `POST {{base_url}}/api/issues/{issue}/unassign-self` — current assignee only; **403** `not_assigned_officer` otherwise.

**Status update**

`PATCH {{base_url}}/api/issues/{issue}/status`

```json
{
  "status": "opgelost",
  "note": "Tegel vervangen."
}
```

Directed transitions only: `open` → `in_behandeling`; `in_behandeling` → `opgelost`; `opgelost` → `gesloten`. Assigned officer only. **403** `not_assigned_officer`, `officer_not_in_district`, or `hub_active_required` when applicable.

**Officer resolution (field report)**

Distinct from user satisfaction in `issue_resolutions`. One report per issue.

- `GET {{base_url}}/api/issues/{issue}/officer-resolution` — any actor who can view the issue; **404** when none exists.
- `POST {{base_url}}/api/issues/{issue}/officer-resolution` — multipart; current assignee only; **403** `hub_active_required`, `officer_not_in_district`, or `not_assigned_officer`; **409** `officer_resolution_exists` on duplicate; **422** `issue_closed` when status is `gesloten` (`opgelost` remains writable). Attachment cap and upload I/O run under row lock.
- `PATCH {{base_url}}/api/issues/{issue}/officer-resolution` — multipart; optional `remove_attachment_ids` and new `files` (max 3 total); `officer_id` updated to last editor; **403** `hub_active_required`, `officer_not_in_district`, or `not_assigned_officer`; **422** `issue_closed` when status is `gesloten`; **422** on `remove_attachment_ids` when ids do not belong to the resolution. Cap enforcement and upload I/O run under row lock.
- `GET {{base_url}}/api/issues/{issue}/officer-resolution/attachments/{attachment}/download` — visibility-only auth (`IssueVisibilityQuery::canViewIssue`, Q8 / D15-A); Tier B. Users probing hidden issues they do not own receive **404**; other unauthorized actors receive **403**. Attachment must belong to the route resolution; missing backing file → **404**.

Up to **3** images (`jpg`, `jpeg`, `png`, `gif`, `webp`), **5 MB** each. Uploads are content-sniffed after extension rules (issue user attachments also accept PDF via magic bytes).

**Officer issue updates (progress log)**

Multiple updates per issue; PATCH/DELETE restricted to the authoring officer (who must also be the current assignee).

- `GET {{base_url}}/api/issues/{issue}/officer-updates` — any actor who can view the issue; Tier B; paginated, chronological.
- `POST {{base_url}}/api/issues/{issue}/officer-updates` — multipart; current assignee only; **403** `hub_active_required`, `officer_not_in_district`, or `not_assigned_officer`; **422** `issue_closed` when status is `gesloten` (`opgelost` remains writable). Up to 3 images, 5 MB each. Cap enforcement and upload I/O run under row lock.
- `PATCH {{base_url}}/api/issues/{issue}/officer-updates/{officer_update}` — multipart; authoring assignee only; **403** `hub_active_required`, `officer_not_in_district`, `not_assigned_officer`, or `not_update_author` (assignee but not author); **422** `issue_closed` when status is `gesloten`. Optional `remove_attachment_ids` and new `files` (max 3 total).
- `DELETE {{base_url}}/api/issues/{issue}/officer-updates/{officer_update}` — authoring assignee only; same **403** and **422** codes as PATCH.
- `GET {{base_url}}/api/issues/{issue}/officer-updates/attachments/{attachment}/download` — visibility-only auth (`IssueVisibilityQuery::canViewIssue`); Tier B. Users probing hidden issues they do not own receive **404**; other unauthorized actors receive **403**.

Postman folder: **Issues / Officer workflows**. Run **Auth / Login Officer at Hub** before Tier C writes; browse requests work after **Auth / Login Officer Remote**. Use an issue in the officer's district (demo: Cool / `district_id: 1`). Run **Assign Self to Issue** before officer-update writes. **Create Officer Update** auto-fills `officer_update_id` and `officer_update_attachment_id`.

### Issue Attachments

Attachment endpoints require `Authorization: Bearer <token>`. Upload and delete are owner-only for the authenticated active regular user who owns the issue. Downloads use visibility-only authorization: any actor who can view the parent issue may download. Users probing hidden issues they do not own receive `404`; other unauthorized actors receive `403`. The attachment must belong to the issue in the route.

Common requests:

- `POST {{base_url}}/api/issues/{issue}/attachments` — upload 1-5 files. Each file must be 5 MB or smaller and one of `jpg`, `jpeg`, `png`, `gif`, `webp`, or `pdf`. Server content-sniffs images and verifies PDF magic bytes (`%PDF-`).
- `GET {{base_url}}/api/issues/{issue}/attachments/{attachment}/download` — visibility-only auth (any actor who can view the issue); Tier B for officers; stream from non-public local storage.
- `DELETE {{base_url}}/api/issues/{issue}/attachments/{attachment}` — owner-only hard delete for one attachment. Use this before uploading replacement files.

Successful upload response shape:

```json
{
  "data": [
    {
      "id": 1,
      "issue_id": 1,
      "original_name": "photo.jpg",
      "file_type": "image/jpeg",
      "file_size": 12345,
      "uploaded_at": "2026-06-03T12:00:00.000000Z",
      "download_url": "http://127.0.0.1:8001/api/issues/1/attachments/1/download"
    }
  ]
}
```

Common error responses:

- `401 Unauthorized` when the bearer token is missing, invalid, or revoked.
- `403 Forbidden` when uploading or deleting as a non-owner, officer, manager, or inactive user; or when downloading without view permission (non-user actors).
- `404 Not Found` when the issue or attachment does not exist, the attachment does not belong to the route issue, a download backing file is missing, or a user probes a hidden issue they do not own.
- `422 Unprocessable Entity` when upload validation fails.

### Logout

`POST {{base_url}}/api/auth/logout`

Requires `Authorization: Bearer <token>`.

Successful response shape:

```json
{
  "message": "Logged out."
}
```

Logout revokes **only the current bearer token**. For officers, the shared shift is preserved and other devices remain authenticated. The open `officer_sessions` row for this token is closed.

Common error response:

- `401 Unauthorized` when the bearer token is missing, invalid, or revoked.

### Issue Comments

Comment endpoints require `Authorization: Bearer <token>`. Listing comments is available to any authenticated active actor who has permission to view the parent issue. Writing, updating, deleting, or moderating comments is restricted based on actor roles and shift status.

Common requests:

- `GET {{base_url}}/api/issues/{issue}/comments` — list comments. Eager-loads author profiles. Sorted oldest first. Paginated. Users see visible comments + comments they authored. Officers/Managers see all. Officers may call this without a hub-active shift (Tier B).
- `POST {{base_url}}/api/issues/{issue}/comments` — add a comment. Active users, active officers, and active managers. Content max 2000 characters. Officers require active shift (Tier C); users and managers do not.
- `PATCH {{base_url}}/api/issues/{issue}/comments/{comment}` — update comment content. Only the comment author may perform this. Requires active shift for officers (Tier C).
- `DELETE {{base_url}}/api/issues/{issue}/comments/{comment}` — delete a comment. Only the author or any active manager may perform this action. Requires active shift for officers (Tier C).
- `PATCH {{base_url}}/api/issues/{issue}/comments/{comment}/visibility` — change comment visibility (`visible` or `hidden`). Active officers and active managers only. Requires active shift for officers (Tier C).

Successful comment response shape (non-anonymous user author):

```json
{
  "id": 1,
  "issue_id": 1,
  "author_type": "user",
  "content": "Dit is een nieuwe opmerking op de melding.",
  "is_flagged": false,
  "visibility": "visible",
  "author": {
    "is_anonymous": false,
    "id": 1,
    "username": "demo.user",
    "display_name": "demo.user"
  },
  "created_at": "2026-06-09T11:45:00.000000Z",
  "updated_at": "2026-06-09T11:45:00.000000Z"
}
```

When the issue owner comments on an anonymous issue, the response redacts the author automatically (no request flag). All viewers see the same alias:

```json
{
  "id": 2,
  "issue_id": 1,
  "author_type": "user",
  "content": "Nog een opmerking van de melder.",
  "is_flagged": false,
  "visibility": "visible",
  "author": {
    "is_anonymous": true,
    "display_name": "Melder#A1B2C3D4"
  },
  "created_at": "2026-06-09T12:00:00.000000Z",
  "updated_at": "2026-06-09T12:00:00.000000Z"
}
```

Officer and manager comments always expose real identity with `is_anonymous: false`.

