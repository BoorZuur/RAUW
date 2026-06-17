# Authentication

RAUW uses **Laravel Sanctum bearer tokens** for API authentication. Registration and login are public; all other `/api/*` routes require `Authorization: Bearer <access_token>` unless documented as public (department reads only).

Cross-cutting rules (inactive accounts, CORS, token lifetime) are in [api-policy.md](../api-policy.md). Request/response schemas are in [openapi.yaml](../openapi.yaml) under the **Auth** tag.

## Public endpoints

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/api/auth/register/user` | Register a citizen user; returns token + profile |
| `POST` | `/api/auth/register/officer` | Register an officer; returns token + profile (no shift started) |
| `POST` | `/api/auth/login` | Login as user, officer, or manager |
| `GET` | `/api/departments` | List departments (public reference; see [reference-data.md](./reference-data.md)) |
| `GET` | `/api/departments/{department}` | Show one department (public reference) |

Login is rate-limited (`throttle:10,1`). Department reads are rate-limited to 30 requests per minute.

## Protected auth endpoints

| Method | Path | Who | Description |
|--------|------|-----|-------------|
| `GET` | `/api/auth/me` | All actors | Current actor type and safe profile |
| `PATCH` | `/api/auth/me` | Active user, officer, manager | Update username, email, password; officers may update `badge_number` |
| `PATCH` | `/api/auth/me/districts` | Active manager or officer | Replace district **assignment** pivot (`district_manager` / `district_officer`) |
| `PATCH` | `/api/auth/me/feed-districts` | Active **user** only | Replace community **feed** subscriptions (`district_user` pivot) |
| `PATCH` | `/api/auth/me/hub` | Active **officer** only | Self-service hub assignment |
| `POST` | `/api/auth/start-shift` | Officer | Start shared shift at assigned hub (GPS required) — see [officer-shift.md](./officer-shift.md) |
| `POST` | `/api/auth/logout` | All actors | Revoke **current** token only |

### Profile field rules

- **Users** expose subscribed feed districts under `profile.districts` (from `feedDistricts`, not officer/manager assignment pivots).
- **Officers and managers** expose assigned districts under `profile.districts` (from `district_officer` / `district_manager`).
- Departments on auth profiles are read-only; use manager endpoints to change department pivots.
- `hub_id`, `hub_active_until`, and privileged fields are **rejected** on `PATCH /api/auth/me` with **422**.
- Password changes require `confirm_password` when `password` is sent.

### District self-service vs feed districts

| Endpoint | Pivot | Actors |
|----------|-------|--------|
| `PATCH /api/auth/me/districts` | `district_officer` / `district_manager` | Active officers and managers only; users receive **403** |
| `PATCH /api/auth/me/feed-districts` | `district_user` | Active users only; officers and managers receive **403** |

Body shape for both: `{ "district_ids": [1, 2] }`. Empty array clears all rows. IDs must reference **active** districts; duplicates and inactive IDs return **422**.

Officers calling `PATCH /api/auth/me/feed-districts` without an active shift receive **403** `hub_active_required` (route is not Tier B whitelisted).

## Login and registration

### Users and managers

Send `email` and `password` only. GPS coordinates are ignored.

### Officers

Send `email`, `password`, `latitude`, and `longitude`. Missing or invalid coordinates return **422**.

| Condition | Result |
|-----------|--------|
| No `hub_id` assigned | **403** `hub_not_assigned` — login blocked |
| Hub-eligible coordinates, no active shift | Shift started; `hub_active: true`, `hub_active_until` ~now + 10h |
| Hub-eligible coordinates, shift already active | `hub_active: true`; `hub_active_until` **not** extended |
| Outside hub, active shift exists | `hub_active: true`; shift preserved |
| Outside hub, no shift | `hub_active: false`; Tier B browse OK; Tier C blocked |

Officer **registration** requires `latitude`/`longitude` and `department_ids` but **does not** start a shift (`hub_active: false`, `hub_active_until: null`). Officers without `hub_id` cannot log in until a manager assigns a hub.

### Successful auth response shape

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

Officer responses add top-level and profile-level `hub_active` and `hub_active_until`. Supported `actor_type` values: `user`, `officer`, `manager`.

### Registration validation highlights

| Rule | User | Officer |
|------|------|---------|
| Password min length | 8 | 8 |
| Duplicate email/username | Generic `The provided credentials could not be registered.` | Same + `badge_number` uniqueness in officers table |
| `department_ids` | N/A | Required, ≥1 active department |
| `district_ids` | N/A | Optional; active districts only |
| GPS | N/A | Required |

## Inactive accounts

Inactive actors receive **403** `account_inactive` on most protected routes. Whitelisted while inactive:

- `GET /api/auth/me`
- `PATCH /api/auth/me` (username and password only; `email` / `badge_number` → **422**)
- `POST /api/auth/logout`

Login returns generic **401** `Invalid credentials.` for inactive credentials.

## Logout

`POST /api/auth/logout` revokes only the current bearer token. For officers:

- Shared shift (`hub_active_until`) is **preserved**
- Other devices remain authenticated
- The open `officer_sessions` row for this token is closed

## Common HTTP status codes

| Code | When |
|------|------|
| `201` | Successful officer registration |
| `200` | Login, profile read/update, district/feed sync, logout |
| `401` | Invalid credentials, missing/invalid/revoked token |
| `403` | Wrong actor type, inactive account (non-whitelisted), users on district self-service |
| `422` | Validation errors, prohibited profile fields, `shift_already_active` on start-shift |

Structured hub/shift codes (`hub_not_assigned`, `outside_hub_radius`, `hub_active_required`, etc.) are listed in [api-policy.md](../api-policy.md) and [officer-shift.md](./officer-shift.md).

## Manual testing with curl

Local API base: `http://127.0.0.1:8001`. After `php artisan migrate:fresh --seed` and starting the server, demo accounts include `demo.user@example.com`, `demo.officer@example.com`, and `demo.manager@example.com` (password: `password`).

### 1. Register or log in

Officer registration:

```bash
curl -X POST http://127.0.0.1:8001/api/auth/register/officer \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"username":"new-officer","email":"new.officer@example.com","password":"password123","confirm_password":"password123","badge_number":"BOA-1234","department_ids":[1],"latitude":51.9106846,"longitude":4.4814932}'
```

User or manager login:

```bash
curl -X POST http://127.0.0.1:8001/api/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"demo.user@example.com","password":"password"}'
```

Officer login (Cluster Centrum coordinates after seed):

```bash
curl -X POST http://127.0.0.1:8001/api/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"demo.officer@example.com","password":"password","latitude":51.9106846,"longitude":4.4814932}'
```

### 2. Call protected routes

```bash
curl http://127.0.0.1:8001/api/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <access_token>"
```

### 3. Update feed districts (users)

```bash
curl -X PATCH http://127.0.0.1:8001/api/auth/me/feed-districts \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <access_token>" \
  -H "Content-Type: application/json" \
  -d '{"district_ids":[1,2]}'
```

### 4. Log out

```bash
curl -X POST http://127.0.0.1:8001/api/auth/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <access_token>"
```

For broader manual coverage, import the Postman collection from [docs/postman](../postman/README.md).
