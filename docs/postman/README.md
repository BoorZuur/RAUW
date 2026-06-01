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

Supported `actor_type` values are `user`, `officer`, and `manager`.

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
| `access_token` | blank | Filled automatically after a successful login request. |

## Recommended Request Order

1. Run **Auth / Login**.
2. Run **Auth / Current Profile**.
3. Run **Auth / Logout**.

The collection stores the returned `access_token` automatically after a successful login. If you disable collection scripts or the token is not stored, copy the `access_token` value from the login response into the active Postman environment's `access_token` variable before calling protected endpoints.

Protected endpoints use this header:

```http
Authorization: Bearer <token>
```

## Endpoint Contract

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
