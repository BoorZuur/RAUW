# Community news feed

District-scoped **community posts** for citizens and officers. OpenAPI tag: **Community Posts**.

Users subscribe to districts via `PATCH /api/auth/me/feed-districts` ([authentication.md](./authentication.md)). Scoping uses `CommunityPostFeedQuery` and `CommunityPostVisibilityQuery` in the backend.

Officer Tier B/C for posts mirrors issues: browse without shift; writes require hub-active shift — [officer-shift.md](./officer-shift.md).

## District scoping

| Actor | Browse scope | Create posts |
|-------|--------------|--------------|
| **User** | `visible` posts in **subscribed feed districts** (`district_user` pivot) | **403** — officers only |
| **Officer** | Posts in **assigned districts** (`district_officer`) plus **own orphan posts** (`district_id` null) | Assigned districts only; Tier C |
| **Manager** | **City-wide** (all districts + orphan posts); optional `district_id` filter | **403** — officers only |

Users with **no feed districts** configured receive an **empty list** (`200`, `data: []`) — not an error. Configure subscriptions before expecting feed content.

Officers and managers see both `visible` and `hidden` posts in scope; users see **`visible` only**.

## Endpoints

| Method | Path | Who | Tier (officer) |
|--------|------|-----|----------------|
| `GET` | `/api/community-posts` | Authenticated actor | B |
| `POST` | `/api/community-posts` | Active officer | C |
| `GET` | `/api/community-posts/{id}` | Authenticated actor | B |
| `PATCH`/`PUT` | `/api/community-posts/{id}` | Authoring officer | C |
| `DELETE` | `/api/community-posts/{id}` | Manager (any) or officer (own) | C (officer) |
| `PATCH` | `/api/community-posts/{id}/visibility` | Officer (own) or manager | C (officer) |
| `POST` | `/api/community-posts/{id}/attachments` | Authoring officer or district-assigned officer | C |
| `DELETE` | `/api/community-posts/{id}/attachments/{id}` | Manager or authoring officer | C (officer) |
| `GET` | `/api/community-posts/{id}/attachments/{id}/download` | Anyone who can view post | B |
| `POST` | `/api/community-posts/{id}/save` | User | — |
| `DELETE` | `/api/community-posts/{id}/save` | User | — |

### List query parameters

| Param | Actors | Description |
|-------|--------|-------------|
| `district_id` | All | Optional filter within feed scope |
| `saved_only=1` | User | Saved posts only (ignored for officers/managers) |
| `page` | All | Default page size **15** |

User list responses include `is_saved` when applicable.

### Create body example

```json
{
  "district_id": 1,
  "title": "Wijkupdate parkeerregels",
  "content": "Vanaf volgende week gelden nieuwe parkeerregels in Cool.",
  "visibility": "visible"
}
```

Officer must be assigned to `district_id`; otherwise **403** `You are not assigned to this district.` Inactive district → **422** on `district_id`.

## Visibility and orphan posts

- Post `visibility`: `visible` or `hidden`. Users only see `visible`.
- When a district is **deactivated** (`is_active=false`):
  - User feed subscriptions (`district_user`) for that district are **pruned**
  - All community posts for that district get `district_id` set to **`null`** (orphan posts)
- **Orphan posts** are excluded from user and officer feeds (except the **authoring officer**, who can still show/update/delete their own orphan post). Managers still see orphans city-wide.

`PATCH .../visibility` with `hidden` **detaches all user saves** for that post.

## Saved posts

- Users save visible posts in their feed districts via `POST .../save` (**201**, idempotent).
- `GET /api/community-posts?saved_only=1` lists saved posts (optional `district_id`).
- If a saved post is hidden, the save is removed automatically; user can no longer view it (**404** on show).

## Attachments

- Max **5** attachments per post, **5 MB** each.
- Upload during create or via `POST .../attachments` (multipart `attachments[]`).
- Stored on non-public disk; download uses visibility-only authorization (Tier B).

## Manager moderation

| Action | Ordinary manager | Main manager |
|--------|------------------|--------------|
| `PATCH .../visibility` | Posts in assigned districts only | City-wide |
| `DELETE` post | Any post (officer delete: own only) | Same |

Officers require hub-active shift for visibility changes (Tier C).

## Notifications link

New posts can generate `new_community_post` notifications for users with `notify_district_news` enabled and matching feed districts — see [notifications.md](./notifications.md).

## Web UI

Citizen feed route: **`/nieuws`** (`NewsFeed.jsx`). Officers publish through the API (and future officer UI). Configure feed districts in account/settings flows that call `PATCH /api/auth/me/feed-districts`.

## Typical setup flow (user)

1. `POST /api/auth/login` or register
2. `PATCH /api/auth/me/feed-districts` with desired `district_ids`
3. `GET /api/community-posts` (optionally `?district_id=1`)
4. `POST .../save` on posts to bookmark; `GET ...?saved_only=1` to list saves

## Typical flow (officer)

1. Hub login — [officer-shift.md](./officer-shift.md)
2. `POST /api/community-posts` in assigned district
3. Optional `POST .../attachments`
4. Managers may `PATCH .../visibility` to hide inappropriate content

Postman: **Community news feed** section in [docs/postman](../postman/README.md).
