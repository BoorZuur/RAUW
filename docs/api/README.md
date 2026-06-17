# RAUW API documentation

Narrative reference for the **Rotterdams Actie Uit de Wijken (RAUW)** backend JSON API. Use this folder together with the machine-readable contract and policy docs below.

## Glossary

| Term | Meaning |
|------|---------|
| **RAUW** | **Rotterdams Actie Uit de Wijken** — product name used in user-facing documentation. |
| **WIJK** | Internal codename in `docs/openapi.yaml`, `docs/openapi/schemas.yaml`, `docs/DBML.txt`, and `docs/api-policy.md`. Spec filenames are not renamed in this documentation effort. |
| **Actor** | Authenticated API consumer: `user`, `officer`, or `manager`. |
| **Tier B** | Officer browse/read routes that work **without** an active shared shift (`hub_active_until` in the future). |
| **Tier C** | Officer workflow/write routes that require an active shared shift. See [officer-shift.md](./officer-shift.md) and [api-policy.md](../api-policy.md). |
| **Canonical issue** | A top-level issue (`duplicate_of_id` null). Duplicate children and chat routes resolve to the canonical parent where noted. |

## Canonical sources (read these first)

| Document | Role |
|----------|------|
| [`docs/openapi.yaml`](../openapi.yaml) + [`docs/openapi/schemas.yaml`](../openapi/schemas.yaml) | **Canonical API contract** — paths, request/response shapes, operation descriptions. |
| [`docs/DATABASE.md`](../DATABASE.md) | **Database ERD** — diagram and links to DBML source. |
| [`docs/DBML.txt`](../DBML.txt) | Editable DBML for dbdiagram.io (Dutch table notes). |
| [`docs/api-policy.md`](../api-policy.md) | Cross-cutting policies: inactive accounts, officer Tier B/C, structured error `code` values, GPS trust, issue chat rules. |
| [`docs/postman/`](../postman/README.md) | Importable Postman collection and local environment for manual testing. |

Narrative files in this folder **must align with** OpenAPI and `api-policy.md`. When they differ, treat OpenAPI + policy as normative and update narrative docs.

## Base URL and auth

- Local development API: `http://127.0.0.1:8001` (see [local-development.md](../local-development.md)).
- All `/api/*` routes except registration and login require `Authorization: Bearer <token>` (Laravel Sanctum).
- Token lifetime: **7 days** (`SANCTUM_TOKEN_EXPIRATION`, default 10080 minutes).
- Health check `GET /up` is intentionally **not** part of the `/api` JSON contract.

## Domain guides

| Guide | OpenAPI tags | Topics |
|-------|----------------|--------|
| [authentication.md](./authentication.md) | Auth | Register, login, profile, districts, feed districts, logout |
| [reference-data.md](./reference-data.md) | Managers, Officers, Hubs, Districts, Departments, Categories | Reference reads and main-manager administration |
| [officer-shift.md](./officer-shift.md) | Auth (`start-shift`), Officers | Hub login, shared shift, Tier B/C, sessions |
| [issues.md](./issues.md) | Issues, Issue Comments, Issue Feedback, Attachments | Meldingen lifecycle, duplicates, officer workflows, comments, feedback |
| [issue-chat.md](./issue-chat.md) | Issue Chat | 1:1 messaging API + web `/chat` UI |
| [community-feed.md](./community-feed.md) | Community Posts | District news feed, saves, attachments |
| [notifications.md](./notifications.md) | Notifications, User Settings | Domain notifications and user preferences |
| [manual-checklists.md](./manual-checklists.md) | — | Step-by-step manual verification after seed |

## Actor overview

| Actor | Primary API use |
|-------|-----------------|
| **User** | Register/login; create and manage own issues; community feed subscriptions; notifications; issue chat as participant |
| **Officer** | Hub login and shared shift; district-scoped issue browse and workflows; community posts; officer notifications |
| **Manager** | Backend administration (managers, officers, hubs, districts, departments, categories); issue visibility; **no** web manager UI in repo; **no** notification inbox API |

## Related documentation

- [local-development.md](../local-development.md) — setup, ports, seed accounts
- [TESTING.md](../TESTING.md) — automated tests and validation
- [DEPLOYMENT.md](../DEPLOYMENT.md) — deployment overview
- [apps/web/README.md](../../apps/web/README.md) — web routes and portals (including `/chat`)
