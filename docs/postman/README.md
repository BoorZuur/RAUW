# RAUW Backend Postman Collection

Use this Postman collection to test the backend-only authentication API against a local RAUW backend or another backend URL.

## Files

- `rauw-backend.postman_collection.json` — importable Postman Collection v2.1 file.
- `rauw-local.postman_environment.json` — local environment with `base_url` set to `http://127.0.0.1:8001` and an empty `access_token` variable.

## Required Local Backend Setup

From `apps/backend`, install dependencies, configure the Laravel app, run migrations and seed local data, then serve the API:

```bash
cd apps/backend
composer i
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
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

## Recommended Request Order

1. Run **Auth / Register User** or **Auth / Register Officer** to create a public actor and auto-login, or run **Auth / Login** with an existing demo account.
2. Run **Auth / Current Profile** to inspect the actor attached to the stored token.
3. To test manager creation locally, run **Auth / Login** with `demo.manager@example.com` and password `password`. This stores a main-manager token.
4. Run **Managers / Create Manager**. This creates an ordinary manager but does not replace the stored `access_token`.
5. If desired, run **Auth / Login** with the newly created manager's email and password to authenticate as that manager.
6. Run **Auth / Logout** when finished.

The collection stores the returned `access_token` automatically after a successful login, user registration, or officer registration. Manager creation intentionally does not update `access_token` because it returns only the created manager profile. If you disable collection scripts or the token is not stored, copy the `access_token` value from the auth response into the active Postman environment's `access_token` variable before calling protected endpoints.

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
  "badge_number": "BOA-1234"
}
```

Successful response shape:

```json
{
  "token_type": "Bearer",
  "access_token": "<token>",
  "actor_type": "officer",
  "profile": {
    "actor_type": "officer",
    "id": 1,
    "username": "new-officer",
    "email": "new.officer@example.com",
    "badge_number": "BOA-1234",
    "district_id": null,
    "is_active": true,
    "district": null
  }
}
```

Common error response:

- `422 Unprocessable Entity` with validation errors when required fields are missing, `email` is invalid, `password` is shorter than 8 characters, `confirm_password` does not match `password`, `username` or `badge_number` already exists in the officers table, or `email` already exists for any user, officer, or manager.

### Register User

`POST {{base_url}}/api/auth/register/user`

Register a public API user account. This creates a `User`, immediately issues a Laravel Sanctum bearer token, and returns the same auth response shape and safe user profile serializer used by login. It does not create an Officer or Manager row.

Request body:

```json
{
  "name": "New User",
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
    "actor_type": "user",
    "id": 1,
    "name": "New User",
    "username": "new-user",
    "email": "new.user@example.com",
    "email_verified_at": null,
    "is_active": true
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
    "actor_type": "user",
    "id": 1,
    "name": "Demo User",
    "username": "demo.user",
    "email": "demo.user@example.com",
    "email_verified_at": null,
    "is_active": true
  }
}
```

Officer and manager profiles include role-specific safe fields such as `badge_number`, `department`, `district_id`, and compact `district` data when available. Passwords and secrets are never returned.

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
    "actor_type": "user",
    "id": 1,
    "email": "demo.user@example.com"
  }
}
```

Common error response:

- `401 Unauthorized` when the bearer token is missing, invalid, or revoked.

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
  "department": "wijkbeheer",
  "district_id": null
}
```

Allowed `department` values are `wijkbeheer`, `boa_jeugd`, and `beide`. `district_id` may be `null` or an existing district ID.

Successful response shape:

```json
{
  "actor_type": "manager",
  "id": 2,
  "username": "new-manager",
  "email": "new.manager@example.com",
  "department": "wijkbeheer",
  "district_id": null,
  "is_active": true,
  "is_main_manager": false,
  "created_by_manager_id": 1,
  "district": null
}
```

This endpoint does not auto-login the created manager and does not return an `access_token`. Use **Auth / Login** with the created manager's email and password if you want to authenticate as that manager.

Common error responses:

- `401 Unauthorized` when the bearer token is missing, invalid, or revoked.
- `403 Forbidden` when the authenticated actor is not an active main manager.
- `422 Unprocessable Entity` with validation errors when required fields are missing, `email` is invalid, `password` is shorter than 8 characters, `confirm_password` does not match `password`, `department` is not allowed, `district_id` does not exist, `username` already exists in the managers table, or `email` already exists for any user, officer, or manager.

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
