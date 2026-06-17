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
- Logout removes all tokens for the actor, not only the current device.

## A06 — Vulnerable and Outdated Components

No backend-specific measures beyond routine dependency updates (`composer update` on a schedule). See the [security checklist](DEPLOYMENT.md#security-checklist) in Deployment.

## A07 — Identification and Authentication Failures

- Login rejects inactive accounts with the same generic error as invalid credentials.
- Bearer tokens expire; logout invalidates all sessions for the actor.
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
| **Inactive accounts** | Blocked on critical routes while limited self-service or auth handling remains available. |
| **Issue visibility** | Unauthorized users may receive 404 instead of 403 to avoid leaking record existence. |
| **Issue priority** | Derived server-side from category/logic; not client-controlled. |
| **Attachments** | Downloads and mutations require authorization; files are not public assets. |
| **Unique / sensitive fields** | Uniqueness and validation enforced at API and database level to limit race conditions. |
| **Mass assignment** | System fields stay protected; only controlled fields are set from requests. |

## Related documentation

| Document | Purpose |
|----------|---------|
| [Deployment](DEPLOYMENT.md) | Production env, CORS, Sanctum, security checklist |
| [API policies](api-policy.md) | Auth tiers, inactive accounts, GPS trust |
| [Local development](local-development.md) | Local API setup and ports |
| [Backend app README](../apps/backend/README.md) | Service overview and env variables |
