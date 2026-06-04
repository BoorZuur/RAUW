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

Local seeders may create deterministic demo accounts for manual testing. These credentials are local development fixtures only and are not production behavior.

| Actor type | Email | Password |
|------------|-------|----------|
| `user` | `demo.user@example.com` | `password` |
| `officer` | `demo.officer@example.com` | `password` |
| `manager` | `demo.manager@example.com` | `password` |

Supported `actor_type` values are `user`, `officer`, and `manager`. The local demo manager is seeded as the main manager and can create other managers. Production environments must provision the initial main manager through trusted operational setup, not through the public API.

Local seeders also create canonical departments and districts. Managers are assigned one or more departments through `department_ids` / the `department_manager` pivot. Officers are assigned one or more departments through `department_ids` / the `department_officer` pivot. Manager and officer district assignments are many-to-many: managers use `district_ids` / `district_manager`, and officers use `district_ids` / `district_officer`.

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
| `access_token` | blank | Filled automatically after a successful login, user registration, or officer registration request. |
| `issue_id` | `1` | Filled automatically after **Issues / Create Issue**. Used by show, update, attachment, and delete examples. |
| `attachment_id` | `1` | Filled automatically after **Issues / Upload Attachments**. Used by authenticated download and delete. |
| `attachment_download_url` | blank | Filled automatically after **Issues / Upload Attachments** for reference. |
| `district_id` | `1` | Example required district ID and list filter. |
| `category_id` | `1` | Example category ID and list filter. |
| `department_filter` | `wijkbeheer` | Example value for the issue list query param `department` (department code). Any-match against rows in `department_issue`. Not sent in create/update bodies. |
| `page` | `1` | Example issue list page. |
| `per_page` | `20` | Example issue list page size. Backend caps this at 100. |

## Recommended Request Order

1. Run **Auth / Register User** or **Auth / Register Officer** to create a public actor and auto-login, or run **Auth / Login** with an existing demo account. Officer registration requires at least one existing department ID; local seeded departments normally include IDs `1` and `2`.
2. Run **Auth / Current Profile** to inspect the actor attached to the stored token.
3. Run **Districts / List Districts** and **Departments / List Departments** to find local IDs for assignment examples.
4. To test manager creation, district assignment, or district/department mutations locally, run **Auth / Login** with `demo.manager@example.com` and password `password`. This stores a main-manager token.
5. Run **Managers / List Main Managers** to verify main-manager listing, then **Managers / Create Manager**. Create Manager returns only the created manager profile and does not replace the stored `access_token`.
6. Run **Managers / Update My Districts** or **Officers / Update My Districts** for implemented self-service district assignments, or **Managers / Update Officer Districts** for manager-admin officer assignments.
7. Run **Districts / Create District**, **Update District**, and **Delete District** with an active manager token. District deletion returns `409 Conflict` while the district is assigned to managers/officers or referenced by issues.
8. Run **Categories / List Categories** to find existing category IDs. Category reads work for any authenticated actor; create/update/disable/delete require an active manager token.
9. Run **Auth / Login** with `demo.user@example.com` and password `password`, then use **Issues / Create Issue**. This stores `issue_id` for **Show Issue**, **Update Own Issue**, **Upload Attachments**, **Download Attachment**, **Delete Attachment**, and **Hard Delete Own Issue**.
10. Use **Issues / List Issues - Filtered Paginated** to combine `district_id`, `department` (env `department_filter`), and `category_id`. The `department` query param accepts a department code and uses any-match semantics on `department_issue`; pagination is backend-driven by `page` and `per_page`.
11. If desired, run **Auth / Login** with a newly created ordinary manager's email and password to test category and district management without main-manager privileges.
12. Run **Auth / Logout** when finished.

The collection stores the returned `access_token` automatically after a successful login, user registration, or officer registration. Manager creation intentionally does not update `access_token` because it returns only the created manager profile. If you disable collection scripts or the token is not stored, copy the `access_token` value from the auth response into the active Postman environment's `access_token` variable before calling protected endpoints.

Issue examples also store `issue_id` after issue creation and `attachment_id` / `attachment_download_url` after attachment upload. Attachment upload returns a `data: [...]` wrapper, and the collection stores these variables from `response.data[0]`. Attachment upload uses local, non-public development storage. Downloads and deletes require `Authorization: Bearer <token>` and use authenticated API routes.

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
  "district_ids": [1]
}
```

`department_ids` is required, must contain at least one existing department ID, and cannot contain duplicates. `district_ids` is optional, may be empty or omitted, must contain active existing district IDs when present, and cannot contain duplicates. Use **Departments / List Departments** and **Districts / List Districts** while authenticated to inspect available IDs.

Successful response shape:

```json
{
  "token_type": "Bearer",
  "access_token": "<token>",
  "actor_type": "officer",
  "profile": {
    "id": 1,
    "username": "new-officer",
    "email": "new.officer@example.com",
    "badge_number": "BOA-1234",
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
        "name": "Middelharnis",
        "postal_prefix": "3241"
      }
    ]
  }
}
```

Common error response:

- `422 Unprocessable Entity` with validation errors when required fields are missing, `email` is invalid, `password` is shorter than 8 characters, `confirm_password` does not match `password`, `department_ids` is missing, empty, duplicated, or references unknown departments, `district_ids` is duplicated or references inactive/unknown districts, `username` or `badge_number` already exists in the officers table, or `email` already exists for any user, officer, or manager.

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
- `422 Unprocessable Entity` with validation errors when required fields are missing, `email` is invalid, `password` is shorter than 8 characters, `confirm_password` does not match `password`, `username` already exists in the users table, or `email` already exists for any user, officer, or manager.

### Login

`POST {{base_url}}/api/auth/login`

Request body uses email and password only:

```json
{
  "email": "demo.user@example.com",
  "password": "password"
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
    "username": "demo.user",
    "email": "demo.user@example.com"
  }
}
```

Auth response metadata lives on the top-level wrapper. `actor_type` is not duplicated inside `profile`, and auth profiles omit internal fields such as `is_active`, `email_verified_at`, actor-side `district_id`, and `created_by_manager_id`. Officer and manager auth profiles include role-specific safe fields such as `badge_number`, `departments`, `is_main_manager`, and compact `districts` arrays when available. Passwords and secrets are never returned.

Common error responses:

- `401 Unauthorized` with `{"message":"Invalid credentials."}` for invalid, inactive, ambiguous, or unknown accounts.
- `422 Unprocessable Entity` with validation errors when `email` or `password` is missing or invalid.

### Current Profile

`GET {{base_url}}/api/auth/me`

Requires `Authorization: Bearer <token>`.

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

`department_ids` is required, must contain at least one existing department ID, and cannot contain duplicates. Manager department assignments are stored through the `department_manager` pivot. Optional `district_ids` assigns active districts through the `district_manager` pivot and cannot contain duplicates. Use **Departments / List Departments** and **Districts / List Districts** to find valid IDs.

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
      "name": "Middelharnis",
      "postal_prefix": "3241"
    }
  ]
}
```

This endpoint does not auto-login the created manager and does not return an `access_token`. Use **Auth / Login** with the created manager's email and password if you want to authenticate as that manager.

Common error responses:

- `401 Unauthorized` when the bearer token is missing, invalid, or revoked.
- `403 Forbidden` when the authenticated actor is not an active main manager.
- `422 Unprocessable Entity` with validation errors when required fields are missing, `email` is invalid, `password` is shorter than 8 characters, `confirm_password` does not match `password`, `department_ids` is missing, empty, duplicated, or references unknown departments, `district_ids` is duplicated or references inactive/unknown districts, `username` already exists in the managers table, or `email` already exists for any user, officer, or manager.

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

`department_ids` must be present and must be an array. Empty `[]` is valid for PATCH (unlike create/register, which require at least one department). IDs must exist in the `departments` table; duplicates are rejected.

The request replaces the target officer's full department assignment set through the `department_officer` pivot and returns `{ "actor_type": "officer", "profile": { ... } }` with a refreshed `departments` array.

Common error responses:

- `401 Unauthorized` when the bearer token is missing, invalid, or revoked.
- `403 Forbidden` when the authenticated actor is not an active main manager.
- `404 Not Found` when the officer route ID does not exist or the officer is soft-deleted.
- `422 Unprocessable Entity` when `department_ids` is missing, not an array, contains duplicates, or references unknown departments.

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

### Districts

District endpoints use the `districts` table. Managers are assigned to districts through the `district_manager` pivot and officers through the `district_officer` pivot. Issue district handling is separate: `issues.district_id` remains a singular issue location/reference field.

Authenticated actors can list and show districts. District mutations require `Authorization: Bearer <token>` for an authenticated active manager. Users, officers, inactive managers, and unauthenticated requests cannot create, update, or delete districts.

Common requests:

- `GET {{base_url}}/api/districts` — list districts with manager/officer assignment counts and issue reference counts.
- `GET {{base_url}}/api/districts/{id}` — show one district.
- `POST {{base_url}}/api/districts` — create a district as an active manager.
- `PATCH {{base_url}}/api/districts/{id}` — update a district as an active manager.
- `DELETE {{base_url}}/api/districts/{id}` — hard delete an eligible district as an active manager.

Create body example:

```json
{
  "name": "Nieuwe Wijk",
  "postal_prefix": "3248",
  "center_lat": 51.75,
  "center_lng": 4.16,
  "radius_meters": 2500,
  "is_active": true
}
```

District deletion is blocked until all manager assignments, officer assignments, and issue references are removed or changed. The delete endpoint returns `409 Conflict` instead of deleting a district still used by actor pivots or `issues.district_id`.

Common error responses:

- `401 Unauthorized` when the bearer token is missing, invalid, or revoked.
- `403 Forbidden` when the authenticated actor is not an active manager for mutations.
- `422 Unprocessable Entity` for validation failures, including duplicate district names or invalid coordinates.
- `409 Conflict` when deleting a district that is still assigned to one or more managers, assigned to one or more officers, or referenced by one or more issues.

### Categories

Category reads (`GET /api/categories`, `GET /api/categories/{id}`) require `Authorization: Bearer <token>` for any authenticated actor (user, officer, or manager). Category mutations require an authenticated, active manager. Ordinary managers may create, update, disable, and hard delete eligible categories. Users, officers, inactive managers, and unauthenticated requests receive `403` on mutations.

Categories belong to one or more departments through the `category_department` many-to-many pivot. Use `department_ids` in create/update requests to attach existing departments.

Main categories have `parent_id: null` and use `priority` for ordering. Subcategories reference an active main category with `parent_id` and use `weight` for ordering. Lower `priority` and `weight` numbers mean higher priority and sort first. Nested subcategories are rejected.

Common requests:

- `GET {{base_url}}/api/categories` — list main categories with children and departments.
- `GET {{base_url}}/api/categories/{id}` — show one category.
- `POST {{base_url}}/api/categories` — create a main category or subcategory.
- `PATCH {{base_url}}/api/categories/{id}` — update fields and, when `department_ids` is present, replace department assignments.
- `PATCH {{base_url}}/api/categories/{id}/disable` — standard safe removal path. Sets `is_active` to `false` without deleting the row or removing historical issue context.
- `DELETE {{base_url}}/api/categories/{id}` — guarded hard delete for eligible records only.

Main category body example:

```json
{
  "name": "Openbare ruimte",
  "parent_id": null,
  "department_ids": [1],
  "priority": 10,
  "is_active": true
}
```

Subcategory body example:

```json
{
  "name": "Losliggende stoeptegel",
  "parent_id": 1,
  "department_ids": [1, 2],
  "weight": 5,
  "is_active": true
}
```

Common error responses:

- `401 Unauthorized` when the bearer token is missing, invalid, or revoked.
- `403 Forbidden` when the authenticated actor is not an active manager.
- `422 Unprocessable Entity` for validation failures, including missing departments, invalid parent categories, nested subcategories, using `priority` on subcategories, or using `weight` on main categories.
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
- `PATCH {{base_url}}/api/departments/{id}` — update a department as a main manager.
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

### Issue Attachments

Attachment endpoints require `Authorization: Bearer <token>`. Upload and delete are owner-only for the authenticated active regular user who owns the issue. Downloads are authenticated and require the attachment to belong to the issue in the route.

Common requests:

- `POST {{base_url}}/api/issues/{issue}/attachments` — upload 1-5 files. Each file must be 5 MB or smaller and one of `jpg`, `jpeg`, `png`, `gif`, `webp`, or `pdf`.
- `GET {{base_url}}/api/issues/{issue}/attachments/{attachment}/download` — stream the file from non-public local storage through the authenticated API route.
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
- `403 Forbidden` when uploading or deleting as a non-owner, officer, manager, or inactive user.
- `404 Not Found` when the issue or attachment does not exist, the attachment does not belong to the route issue, or a download backing file is missing.
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

Logout revokes only the bearer token used for the current request. Other issued tokens for the same actor remain valid.

Common error response:

- `401 Unauthorized` when the bearer token is missing, invalid, or revoked.
