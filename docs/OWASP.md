# Backend security (OWASP Top 10)

Summary of how the RAUW Laravel API addresses the [OWASP Top 10](https://owasp.org/www-project-top-ten/) for web application security. For deployment hardening, see [Deployment](DEPLOYMENT.md). For auth tiers and error behaviour, see [API policies](api-policy.md).

**Not covered in this overview:** open officer login and temporarily broader manager actions (documented separately in policy).

## A01 — Broken Access Control

- Global middleware blocks inactive accounts on most routes; sensitive paths use a limited whitelist.
- Issue read access is scoped by visibility rules; unauthorized access often returns **404** instead of **403** to avoid leaking whether a record exists.
- `FormRequest::authorize()` guards mutations (issues, attachments, categories, districts); officer workflow routes require Tier B/C.

## A02 — Cryptographic Failures

- Passwords are hashed via Laravel; tokens are never returned as plain text.
- Production deployments must set `APP_DEBUG=false` so stack traces do not leak to clients.

## A03 — Injection

- Database access uses Eloquent / query builder with parameter binding.
- Input is validated per endpoint in Form Requests (types, enums, `exists` rules).

## A04 — Insecure Design

- Issue priority is derived server-side from category; clients must not send `priority`.
- Registration uses generic error messages to limit email enumeration.
- Inactive accounts may update profile fields in a restricted way (username/password), not all fields.

## A05 — Security Misconfiguration

- Explicit CORS configuration with allowed frontend origins only (no wildcard).
- Sanctum token expiry via `SANCTUM_TOKEN_EXPIRATION` (default 7 days).
- Logout revokes only the current bearer token; manager end-shift or hub reassignment may revoke all officer tokens.

## A06 — Vulnerable and Outdated Components

No backend-specific measures beyond routine dependency updates (`composer update` on a schedule). See the [security checklist](DEPLOYMENT.md#security-checklist) in Deployment.

## A07 — Identification and Authentication Failures

- Login rejects inactive accounts with the same generic error as invalid credentials.
- Bearer tokens expire; logout invalidates only the current token.
- Rate limiting on sensitive route groups (auth and protected API).

## A08 — Software and Data Integrity Failures

- Sensitive model fields (e.g. `created_by_manager_id`, `is_main_manager`) are not mass-assignable from requests.
- Department links require active departments on assignment and registration.
- Unique database index on `issues.anonymous_alias` with bounded retry on collision.

## A09 — Security Logging and Monitoring Failures

- Officer login / start-shift coordinates are recorded on `officer_sessions` for audit.
- API errors in production return generic messages; technical detail belongs in server logs.

## A10 — Server-Side Request Forgery (SSRF)

No relevant backend endpoints fetch external URLs based on client input.

## Backend edge cases

| Area | Behaviour |
|------|-----------|
| **Inactive accounts** | Blocked on critical routes while limited self-service or auth handling remains available (`GET/PATCH me`, logout only — not `feed-districts` or `me/districts`). |
| **Issue visibility** | Unauthorized users may receive 404 instead of 403 to avoid leaking record existence. |
| **Issue priority** | Derived server-side from category/logic; not client-controlled. |
| **Issue departments** | Derived from category on create/update; never accepted from the client. |
| **Attachments** | Downloads and mutations require authorization; files are not public assets. Attachment id mismatch with route issue → 404 (anti-probing). |
| **Unique / sensitive fields** | Uniqueness and validation enforced at API and database level to limit race conditions. |
| **Mass assignment** | System fields stay protected; only controlled fields are set from requests. |
| **Anonymous reports** | `anonymous_alias` is server-generated; `user_id` is retained for ownership either way. |
| **Officer Tier B/C** | Tier B: browse many issue routes without an active shift. Tier C: workflow writes require future `hub_active_until` (`hub_active_required`). |
| **GPS trust (v1)** | Login/start-shift coordinates are client-reported only; no per-request geofence; out-of-radius login does not clear an existing shift. |
| **Logout vs end-shift** | Logout revokes only the current token. Manager end-shift or hub reassignment may revoke all officer tokens. |
| **Duplicate issues** | Child ids resolve to canonical on read routes; mark-duplicate needs officer district access on both child and canonical; same-owner merge vs different-owner hidden re-parent. |
| **Issue participants** | Join/leave on canonical issues only; legacy owner fallback when `participant_count = 0`. |
| **District scoping** | Officers and ordinary managers: assigned districts only; main managers city-wide; empty assignments → empty lists; `district_id` null on issue → no officer/manager access. |
| **Hub district bounds** | With `hub_id` set, self-service `district_ids` must be active districts in that hub (422). |
| **User feed districts** | Community posts and saved posts scoped to subscribed active feed districts. |
| **Officer workflow locks** | Assignee, transitions, assignability, and resolution limits run on `lockForUpdate` rows; resolution attachment cap enforced inside the transaction. |
| **Issue chat** | 1:1 per canonical issue + user; assigned officer opens/closes; managers excluded; unassign closes open chats; `gesloten` closes chats with a system message. |
| **Feedback** | Closed issues only; 7-day write window after close; 24 h edit/delete after `submitted_at`; duplicate per reviewer → 409. |
| **Manager/officer binding** | Wrong manager type (main vs ordinary) or officer outside manager hub → 404. |
| **Self-assign** | 409 when another officer owns the issue; idempotent when already self; blocked when status is `opgelost` or `gesloten`. |
| **Notifications** | `notification_id` must belong to the authenticated actor or 404. |

## Related documentation

| Document | Purpose |
|----------|---------|
| [Deployment](DEPLOYMENT.md) | Production env, CORS, Sanctum, security checklist |
| [API policies](api-policy.md) | Auth tiers, inactive accounts, GPS trust |
| [Local development](local-development.md) | Local API setup and ports |
| [Backend app README](../apps/backend/README.md) | Service overview and env variables |
