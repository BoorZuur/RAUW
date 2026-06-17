# Officer hub login and shared shift

Officers share **one shift clock** across all devices via `officers.hub_active_until`. Workflow access (Tier C) is gated on **`is_active` and a future `hub_active_until`**, not on the Sanctum token's `hub-active` ability (audit-only).

Normative Tier B/C route lists: [api-policy.md](../api-policy.md). OpenAPI: **Auth** (`start-shift`) and **Officers** tags.

## Concepts

| Field / setting | Purpose |
|-----------------|---------|
| `hub_id` | Officer home hub (Rotterdam BOA cluster). Required before login. |
| `hub_active_until` | Shared shift expiry; when in the future, Tier C is enabled |
| `OFFICER_HUB_ACTIVE_TTL_HOURS` | Default **10** hours after hub-eligible login or start-shift |
| `radius_meters` on hub | Default **100** m for login/start-shift geofence (main managers may set 10–5000 on hub create/update) |
| `officer_sessions` | Login audit (`start_lat`, `start_lng`, `distance_meters_at_login`) |

Users and managers **ignore** `latitude`/`longitude` on login.

## Login scenarios

| Scenario | Login response | `hub_active_until` | Tier C |
|----------|----------------|-------------------|--------|
| Hub login, no active shift | `hub_active: true` | Started (~now + TTL) | Enabled |
| Hub login, shift already active | `hub_active: true` | Unchanged (not extended) | Enabled |
| Outside login, active shift | `hub_active: true` | Unchanged | Enabled |
| Outside login, no shift | `hub_active: false` | Unchanged (null) | Blocked |
| No `hub_id` | **403** `hub_not_assigned` | — | Login blocked |
| Inactive hub or missing hub coordinates | Login may succeed; shift not started remotely | Unchanged unless hub-eligible | Blocked without shift |

## Start shift

`POST /api/auth/start-shift` with `latitude` and `longitude`:

| Result | HTTP | Code |
|--------|------|------|
| Shift started | `200` | — |
| Shift already active | `422` | `shift_already_active` |
| Outside hub radius | `403` | `outside_hub_radius` |
| No `hub_id` | `403` | `hub_not_assigned` |

Registration does **not** start a shift. Officers without `hub_id` must receive hub assignment via `PATCH /api/officers/{officer}/hub` before login.

## Tier B vs Tier C (officers)

| Tier | Meaning |
|------|---------|
| **Tier B** | Browse/read without active shift — issue list/show, duplicates, participants, status history, officer-resolution read, officer-updates read, comments index, feedback index, notifications, community post list/show, attachment downloads (issues, resolutions, updates, community posts), `GET /api/officers/{officer}`, `GET /api/officers/me/feedback`, issue chat list/messages |
| **Tier C** | Workflow writes require future `hub_active_until` — assign-self, unassign-self, status PATCH, mark-duplicate, officer-resolution writes, officer-update writes, resolution attachment delete, issue chat open/close/send/mark-read, officer chat attachment download, community post writes, issue comment writes, issue feedback writes (user-only route) |

Officers on Tier C without a shift receive **403** `hub_active_required`.

Whitelisted **without** shift (Tier A profile/auth): `GET/PATCH /api/auth/me`, `POST /api/auth/logout`, `POST /api/auth/start-shift`, `PATCH /api/auth/me/districts`, reference reads (hubs, districts, categories), `GET /api/officers/{officer}`, `GET /api/officer-sessions` (managers only).

## Logout, reassignment, and disable

| Event | Tokens | Shared shift | Sessions |
|-------|--------|--------------|----------|
| `POST /api/auth/logout` | Current token revoked | Preserved | Current token's session closed |
| `PATCH /api/officers/{officer}/hub` | Not revoked | Cleared | — |
| `PATCH /api/officers/{officer}/end-shift` (manager) | Not revoked | Cleared | — |
| `PATCH /api/officers/{officer}/disable` | All revoked | Cleared | All closed |

**Reassignment handoff (issue chat):** no takeover on assign-self (**409** `issue_already_assigned`). Flow: current assignee `unassign-self` (closes open chats) → successor `assign-self` → successor reopens chat via `PATCH .../chats/open`.

## GPS trust (v1)

Coordinates are **client-reported only**. No mock-location detection, no per-request geofence re-check. Shared shift is operational policy, not proof of presence. Consumer GPS accuracy (±5–20 m) is absorbed by the default 100 m hub radius.

## Manager session audit

`GET /api/officer-sessions` — active managers only, paginated. Filters: `officer_id`, `hub_id`, `is_hub_active`. Response does **not** include `personal_access_token_id`.

## Environment variables

| Variable | Default | Config |
|----------|---------|--------|
| `OFFICER_HUB_ACTIVE_TTL_HOURS` | `10` | `config/officer.php` |
| `SANCTUM_TOKEN_EXPIRATION` | `10080` (7 days) | `config/sanctum.php` |

Configure in `apps/backend/.env` (see `.env.example`).

## Manual verification

See [manual-checklists.md](./manual-checklists.md) — **Manual Shared Shift Test Checklist** (15 steps).
