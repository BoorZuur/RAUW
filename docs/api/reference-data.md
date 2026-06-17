# Reference data and administration

Reference reads and main-manager administration for hubs, districts, departments, categories, managers, and officers. OpenAPI tags: **Managers**, **Officers**, **Hubs**, **Districts**, **Departments**, **Categories**.

Officer hub login and Tier B/C rules: [officer-shift.md](./officer-shift.md). Authentication profile fields: [authentication.md](./authentication.md).

## Departments

| Method | Path | Auth | Who |
|--------|------|------|-----|
| `GET` | `/api/departments` | None (public) | Active departments for unauthenticated callers; active main managers also see inactive |
| `GET` | `/api/departments/{department}` | None (public) | Inactive → **404** for non–main-manager callers |
| `POST` | `/api/departments` | Bearer | Active main manager |
| `PATCH`/`PUT` | `/api/departments/{department}` | Bearer | Active main manager |
| `DELETE` | `/api/departments/{department}` | Bearer | Active main manager |

Public reads are rate-limited to **30 requests per minute**.

- Categories attach via `category_department` pivot; issue departments are **derived from category** on create/update (clients must not send issue department fields).
- Manager/officer registration and create require ≥1 active `department_ids`.
- Delete blocked while assigned to managers or officers (**409**). Category pivot rows cascade on delete when otherwise unused.
- Deactivate/reactivate via `PATCH` with `is_active` (no `/disable` route).

## Hubs

Rotterdam BOA cluster locations. `radius_meters` (default 100) is for officer hub-active login — distinct from `districts.radius_meters` (district auto-assignment).

| Method | Path | Auth | Who |
|--------|------|------|-----|
| `GET` | `/api/hubs` | Bearer | Any authenticated actor |
| `GET` | `/api/hubs/{hub}` | Bearer | Any authenticated actor |
| `POST` | `/api/hubs` | Bearer | Active main manager |
| `PATCH`/`PUT` | `/api/hubs/{hub}` | Bearer | Active main manager |
| `DELETE` | `/api/hubs/{hub}` | Bearer | Active main manager |

- Create requires `latitude` and `longitude` (not geocoded server-side). New hubs default `is_active=false`; seed activates four cluster hubs.
- Deactivate via `PATCH` `is_active: false` — **422** when active districts or active officers remain (managers on hub do not block).
- Delete → **409** while districts, officers, or managers reference the hub.
- Hub assignment (`PATCH .../hub` on officers, managers, main-managers) requires active hub; clears district pivot on change; officer hub change also ends shared shift without revoking tokens.

## Districts

| Method | Path | Auth | Who |
|--------|------|------|-----|
| `GET` | `/api/districts` | Bearer | Any authenticated actor |
| `GET` | `/api/districts/{district}` | Bearer | Any authenticated actor |
| `POST` | `/api/districts` | Bearer | Active main manager |
| `PATCH`/`PUT` | `/api/districts/{district}` | Bearer | Active main manager |
| `DELETE` | `/api/districts/{district}` | Bearer | Active main manager |

- Each district belongs to one **active** hub (`hub_id` required on create).
- Create requires `center_lat` and `center_lng`.
- Deactivate via `PATCH` `is_active` — deactivation prunes `district_user` feed subscriptions and sets `community_posts.district_id` to `null` (orphan posts).
- Delete → **409** while assigned to managers/officers or referenced by issues.
- **`issues.district_id`** is issue location only — not updated by actor district assignment or district CRUD.

### District assignment endpoints

| Method | Path | Who |
|--------|------|-----|
| `PATCH` | `/api/auth/me/districts` | Active manager or officer (self-service) |
| `PATCH` | `/api/officers/{officer}/districts` | Active manager (hub-scoped for ordinary managers) |
| `PATCH` | `/api/managers/{manager}/districts` | Active main manager (ordinary managers only) |
| `PATCH` | `/api/auth/me/feed-districts` | Active user (feed subscriptions — see [community-feed.md](./community-feed.md)) |

When actor has `hub_id`, district IDs must belong to that hub; cross-hub IDs → **422**.

## Categories

Two-level hierarchy: main categories (`parent_id` null) ordered by `priority` (lower = more urgent); subcategories inherit parent priority and sort alphabetically.

| Method | Path | Auth | Who |
|--------|------|------|-----|
| `GET` | `/api/categories` | Bearer | Any authenticated actor |
| `GET` | `/api/categories/{category}` | Bearer | Any authenticated actor |
| `POST` | `/api/categories` | Bearer | Active main manager |
| `PATCH`/`PUT` | `/api/categories/{category}` | Bearer | Active main manager |
| `DELETE` | `/api/categories/{category}` | Bearer | Active main manager |

- Mutations: users, officers, ordinary managers, inactive managers → **403**.
- No sub-under-sub; legacy `weight` field rejected with **422**.
- Delete guarded: main with children → **409**; referenced by issues → **409**.

## Managers

Main managers (`is_main_manager=true`) administer ordinary managers and most reference writes.

| Resource | List | Show | Create | Update | Disable/Enable | Hub | Departments | Districts |
|----------|------|------|--------|--------|----------------|-----|-------------|-----------|
| Main managers | `GET /api/main-managers` | `GET /api/main-managers/{id}` | — | `PATCH` | `PATCH .../disable`, `.../enable` | `PATCH .../hub` | — | — |
| Ordinary managers | `GET /api/managers` | `GET /api/managers/{id}` | `POST /api/managers` | `PATCH` | `PATCH .../disable`, `.../enable` | `PATCH .../hub` | `PATCH .../departments` | `PATCH .../districts` |

- List/create/update/disable for managers requires **active main manager**.
- `POST /api/managers` does not issue a token for the created manager.
- Show endpoints for managers: active officers or managers; hub-scoped (**404** on hub mismatch; no main-manager city-wide bypass on show).
- Disable main manager → **409** when last active main manager.

## Officers

| Method | Path | Who | Notes |
|--------|------|-----|-------|
| `GET` | `/api/officers` | Active officer or manager | Hub-scoped for officers and ordinary managers; main managers city-wide |
| `GET` | `/api/officers/{officer}` | Any active actor | City-wide; soft-deleted → **404** |
| `PATCH` | `/api/officers/{officer}/disable` | Active manager | Hub-scoped for ordinary managers |
| `PATCH` | `/api/officers/{officer}/enable` | Active manager | Idempotent |
| `PATCH` | `/api/officers/{officer}/end-shift` | Active manager | Clears shift; tokens preserved |
| `PATCH` | `/api/officers/{officer}/hub` | Active manager | Clears district pivot; ends shift |
| `PATCH` | `/api/officers/{officer}/districts` | Active manager | Sync `district_officer` |
| `PATCH` | `/api/officers/{officer}/departments` | Active **main** manager | Sync `department_officer` |
| `GET` | `/api/officer-sessions` | Active manager | Login audit — see [officer-shift.md](./officer-shift.md) |
| `GET` | `/api/officers/me/feedback` | Officer | Tier B — see [issues.md](./issues.md#issue-feedback-user-satisfaction) |

Optional list filters: `district_id`, `department_id`, `is_active` (default active-only when omitted).

## Actor profile shapes (compact)

Auth and list responses use compact nested objects:

- **Department:** `{ id, code, name }`
- **District:** `{ id, name, postal_prefix }`
- **Hub:** includes `radius_meters` when loaded

Passwords and internal flags (`is_active`, `email_verified_at`, etc.) are never returned on auth paths.

## Local seed reference

After `migrate:fresh --seed`: four active cluster hubs, 67 Rotterdam wijken, demo officer/manager on **Cluster Centrum** / **Cool** (`district_id: 1`). Use public `GET /api/departments` before officer registration to discover `department_ids`.
