# WIJK API — cross-cutting policies

Reference for behaviour that applies across many endpoints. Per-route details remain in `openapi.yaml` operation descriptions.

## Authentication

- **Scheme:** Laravel Sanctum bearer token (`Authorization: Bearer {token}`).
- **Token lifetime:** 7 days (10080 minutes; `SANCTUM_TOKEN_EXPIRATION` in `config/sanctum.php`, default 10080).
- **Public routes:** `POST /api/auth/register/*` and `POST /api/auth/login` only.
- **CORS:** Explicit origins only (no wildcard): `FRONTEND_URL` (default `http://localhost:5173`) and optional `FRONTEND_URL_PRODUCTION` in `config/cors.php`.

## Inactive accounts

After `auth:sanctum` resolves a token, global middleware returns **403** (`code: account_inactive`, message `This account is inactive.`) on protected routes when the actor is deactivated.

Login returns generic **401** (`Invalid credentials.`) for inactive credentials, unknown email, or wrong password.

**Whitelisted while inactive:** `GET /api/auth/me`, `POST /api/auth/logout`, `PATCH /api/auth/me` (username and password only). All other protected routes, including `PATCH /api/auth/me/districts`, return **403**.

## Officer shared shift

Officers share one shift clock via `officers.hub_active_until`.

| Tier | Access |
|------|--------|
| **Tier B** | Browse reads without an active shift (see list below). |
| **Tier C** | Workflow writes require `is_active` and a future `hub_active_until`. |

Shift is driven by `hub_active_until`, not the Sanctum `hub-active` ability (audit-only).

**Tier B routes (officers, no active shift required):**

- Issues: list, show, duplicates, participants, status-history, officer-resolution, officer-updates, comments, feedback.
- `GET /api/officers/me/feedback`
- Notifications (user and officer mirror routes): list, unread-count, patch, bulk-read, mark-all-read.
- Community posts: list, show.
- `GET /api/officers/{officerId}`
- Authenticated attachment downloads on issues, resolutions, officer-updates, and community posts.

**Shift lifecycle:**

- Officers send `latitude` and `longitude` on `POST /api/auth/login`.
- Hub-eligible login or `POST /api/auth/start-shift` starts the shift for **10 hours** (`OFFICER_HUB_ACTIVE_TTL_HOURS`, default 10).
- Outside-radius login does not clear an active shift.
- Registration does not start a shift.
- Logout revokes only the current token; hub reassignment and manager end-shift clear the shift without revoking tokens.
- Users and managers ignore coordinate fields.

**District scoping:** Issue list/show/duplicates/participants/status-history are district-scoped for officers and ordinary managers; main managers are city-wide.

**Community posts:** Users see posts in subscribed feed districts; officers see assigned districts; managers browse city-wide (`CommunityPostFeedQuery` / `CommunityPostVisibilityQuery`).

## Officer issue writes

Validate-after-lock: visibility and district checks run before the transaction; assignee, transition, assignability, and duplicate-resolution checks run on the locked issue row.

**Tier C** workflow writes also include mark-duplicate, officer-resolution attachment delete, issue chat open/close/messages/mark-read, issue feedback write/update/delete, and officer chat attachment download (not Tier B whitelisted).

Officer-resolution attachment uploads enforce the cumulative cap inside a `lockForUpdate` transaction so concurrent requests cannot exceed the per-resolution limit.

## GPS trust (v1)

Coordinates are client-reported only. No mock-location detection, no per-request geofence re-check. Shared shift is operational policy, not cryptographic proof of presence. Login and start-shift coordinates are audited on `officer_sessions` (`start_lat`, `start_lng`, `distance_meters_at_login`).

## Structured error codes

Hub-, account-, workflow-, chat-, feedback-, and notification-related responses include stable `code` values alongside `message` for mobile clients.

| Code | HTTP | When |
|------|------|------|
| `hub_active_required` | 403 | Officer on Tier C without active shared shift |
| `hub_not_assigned` | 403 | Officer login or start-shift when `hub_id` is null |
| `outside_hub_radius` | 403 | Start-shift outside hub radius / ineligible hub |
| `officer_not_in_district` | 403 | Officer workflow on an issue outside assigned districts |
| `not_assigned_officer` | 403 | Officer status/unassign/resolution/officer-update write without assignee ownership |
| `not_update_author` | 403 | PATCH/DELETE officer update when officer is assignee but not the authoring officer |
| `issue_already_assigned` | 409 | Self-assign when another officer already owns the issue |
| `officer_resolution_exists` | 409 | Duplicate POST on officer resolution (use PATCH to update) |
| `issue_not_assignable` | 422 | Self-assign when issue status is `opgelost` or `gesloten` |
| `issue_closed` | 422 | Resolution or officer-update POST/PATCH/DELETE when issue status is `gesloten` |
| `shift_already_active` | 422 | Start-shift when shift already active |
| `account_inactive` | 403 | Inactive actor on a non-whitelisted route |
| `duplicate_target_not_found` | 404 | Mark-duplicate: child, target, or resolved canonical not visible |
| `issue_not_linkable` | 422 | Mark-duplicate: child not linkable (status, assignee, or resolution) |
| `issue_has_duplicates` | 422 | Mark-duplicate: child has duplicate children of its own |
| `cannot_duplicate_self` | 422 | Mark-duplicate: child and canonical are the same issue |
| `issue_not_matchable` | 422 | Mark-duplicate: canonical not `open` or `in_behandeling` |
| `issue_is_duplicate_child` | 422 | Mark-duplicate: route child is already a duplicate |
| `chat_closed` | 422 | Send or mark-read while chat status is `closed` |
| `chat_user_not_eligible` | 422 | Open chat: `user_id` is not issue owner or participant |
| `not_chat_participant` | 403 | List/view/send/download outside the 1:1 chat partnership |
| `chat_not_found` | 404 | Chat id does not belong to the canonical issue in the route |
| `feedback_not_allowed` | 403 | Feedback write when issue status is not `gesloten` |
| `feedback_window_closed` | 403 | Feedback write more than 7 days after last close |
| `feedback_edit_window_closed` | 403 | Feedback PATCH/DELETE more than 24 h after `submitted_at` |
| `feedback_already_submitted` | 409 | Duplicate feedback `(issue_id, reviewer_user_id)` on POST |
| `not_issue_participant` | 403 | User is not an issue participant on feedback POST |
| `notification_not_found` | 404 | Notification id does not belong to the authenticated actor |

Schema shapes for error responses: `openapi/schemas.yaml` (`HubError`, `OfficerWorkflowError`, `IssueFeedbackError`, etc.).

## Issue chat (v1)

- Multiple parallel 1:1 chats per canonical issue (unique `issue_id` + `user_id`).
- Only the assigned officer opens or closes chats.
- Eligible users: canonical owner or participants. Managers excluded (**403**).
- Users with no chats receive **200** with `data: []`.
- Messages may be read in open or closed chats; send and mark-read require an open chat.
- Attachment-only messages allowed (max **3** images, **5 MB** each). No message edit/delete in v1.
- Officer reassignment: `POST .../unassign-self` then `POST .../assign-self` (no takeover; assign-self keeps **409** `issue_already_assigned`).
- Unassign closes all open chats without a system message; transitioning to `gesloten` closes all open chats with one system message per chat.

## Health check

`GET /up` (Laravel bootstrap) is intentionally omitted from `openapi.yaml`; it is not part of the JSON API contract under `/api`.
