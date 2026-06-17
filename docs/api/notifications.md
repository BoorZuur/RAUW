# Notifications and user settings

Poll-friendly **domain notifications** for citizens and officers, plus **notification preference** flags for users. OpenAPI tags: **Notifications**, **User Settings**.

Schema: `DomainNotification`, `NotificationType` in [openapi/schemas.yaml](../openapi/schemas.yaml). Table: `domain_notifications` in [DBML.txt](../DBML.txt). User columns: `users.notify_status_changes`, `users.notify_district_news`.

**Managers have no notification inbox API** — only users and officers.

Officer notification routes are **Tier B** (no hub-active shift required) — [api-policy.md](../api-policy.md).

## User settings (preferences)

| Method | Path | Who |
|--------|------|-----|
| `GET` | `/api/user/settings` | Active user |
| `PATCH` | `/api/user/settings` | Active user |

Officers and managers receive **403** `This action is unauthorized.`

### Fields

| Field | Default | When `true` |
|-------|---------|-------------|
| `notify_status_changes` | `1` | User receives notifications for issue status changes on issues they own or participate in |
| `notify_district_news` | `1` | User receives notifications for new community posts in **subscribed feed districts** |

Partial `PATCH` supported; send at least one field when patching.

Preferences affect **list and unread-count responses** — rows still exist in the database but are filtered out when the preference is off or feed district does not match (for `new_community_post`).

## Notification types

Canonical `type` values (`NotificationType` enum):

| Type | Typical trigger |
|------|-----------------|
| `status_change` | Issue status transition |
| `new_message` | Issue chat message |
| `new_issue` | New issue in user's context |
| `chat_opened` | Officer opened 1:1 chat |
| `chat_closed` | Chat closed |
| `new_comment` | Issue comment |
| `resolution_posted` | Officer field report posted |
| `feedback_received` | User feedback submitted |
| `new_community_post` | New post in subscribed district |
| `issue_hidden` | Issue visibility hidden |

Each `DomainNotification` includes: `id`, `type`, `title`, `body`, `is_read`, optional `issue_id`, `community_post_id`, `payload` (navigation metadata such as `chat_id`, `comment_id`, `message_id`), `actor_type`, `actor_id`, timestamps.

## User notification endpoints

Base: `/api/notifications`

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/notifications/unread-count` | `{ "count": N }` — lightweight poll |
| `GET` | `/api/notifications` | Paginated list (`page`, `per_page` default 20, max 100) |
| `PATCH` | `/api/notifications/{id}` | Mark read — body `{ "is_read": true }` only |
| `PATCH` | `/api/notifications/bulk-read` | Body `{ "ids": [1,2,3] }` — foreign ids skipped |
| `POST` | `/api/notifications/mark-all-read` | Marks all unread for user |

### List filters

| Query | Description |
|-------|-------------|
| `is_read` | Boolean filter |
| `type` | `NotificationType` value |
| `since` | ISO 8601 — rows with `created_at` **strictly after** timestamp (incremental polling) |

Order: `created_at desc`, `id desc`. Unread count is **not** included in list `meta` — use the dedicated unread-count endpoint.

Foreign or unknown notification id on PATCH → **404** `notification_not_found`.

## Officer notification endpoints (mirror)

Base: `/api/officers/me/notifications` — same shapes and filters as user routes.

| Method | Path |
|--------|------|
| `GET` | `/api/officers/me/notifications/unread-count` |
| `GET` | `/api/officers/me/notifications` |
| `PATCH` | `/api/officers/me/notifications/{id}` |
| `PATCH` | `/api/officers/me/notifications/bulk-read` |
| `POST` | `/api/officers/me/notifications/mark-all-read` |

Users and managers calling officer routes → **403**. Officers may poll **without** active shared shift.

Use a dedicated token variable in Postman (`officer_access_token`) so user login does not overwrite officer notification tests — see [postman/README.md](../postman/README.md).

## Preference filtering behavior

| Preference / condition | Effect on API list |
|------------------------|-------------------|
| `notify_status_changes: false` | Hides `status_change` (and related status notifications per backend filter) |
| `notify_district_news: false` | Hides `new_community_post` |
| No feed districts subscribed | Hides `new_community_post` even when `notify_district_news: true` |
| Post district not in user's feed | `new_community_post` for that post hidden |

Unread count respects the same filters.

## Polling pattern (clients)

1. `GET /api/notifications/unread-count` (or officer mirror) on interval or app focus
2. When count > 0, `GET /api/notifications?since=<last_poll_iso>&is_read=false`
3. On open/detail: `PATCH /api/notifications/{id}` with `{ "is_read": true }`
4. On inbox clear: `POST /api/notifications/mark-all-read` or `PATCH .../bulk-read`

## Related features

| Feature | Doc |
|---------|-----|
| Feed districts (for `new_community_post`) | [community-feed.md](./community-feed.md), [authentication.md](./authentication.md) |
| Issue status / chat / comments | [issues.md](./issues.md), [issue-chat.md](./issue-chat.md) |
| Community posts | [community-feed.md](./community-feed.md) |

## Error codes

| Code | HTTP | When |
|------|------|------|
| `notification_not_found` | 404 | Notification id not owned by actor |

Inactive actors: standard `account_inactive` on non-whitelisted routes — [authentication.md](./authentication.md).
