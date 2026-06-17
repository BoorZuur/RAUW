# Issue chat

Private **1:1 messaging** between the **assigned officer** and one eligible citizen per chat row on a **canonical issue**. Distinct from [issue comments](./issues.md#issue-comments) (public thread).

OpenAPI tag: **Issue Chat**. Policy: [api-policy.md](../api-policy.md#issue-chat-v1). Officer Tier rules: [officer-shift.md](./officer-shift.md).

## Web UI

Both portals use route **`/chat`** (same path; different components behind `PortalGuard`):

| Portal | Component | Path |
|--------|-----------|------|
| Citizen | `U_Chat.jsx` | `apps/web/src/user/U_Chat.jsx` |
| Officer | `H_Chat.jsx` | `apps/web/src/handhaver/H_Chat.jsx` |

See [apps/web/README.md](../../apps/web/README.md) for route table and journeys. The web client calls the Issue Chat API endpoints below; chat UI may still be wiring in `App.jsx` during active development — the API contract is stable.

**Product rules:** multiple parallel chats per canonical issue (unique `issue_id` + `user_id`); only assignee officer opens/closes; eligible users are canonical owner or `issue_participants`; managers are excluded from all chat routes.

## Data model (summary)

| Table | Role |
|-------|------|
| `issue_chats` | One row per officer–user pair per canonical issue; `status` `open` or `closed` |
| `issue_chat_messages` | Text and/or attachments; `message_type` `user`, `officer`, or `system` |

Duplicate child issue IDs in routes resolve to the canonical parent.

## Who may do what

| Actor | List chats | Open / close | Read messages | Send / mark-read | Download attachment |
|-------|------------|--------------|---------------|------------------|---------------------|
| Assignee officer | All on issue | Yes | Any chat on issue | Open chats only | Yes (Tier C for officers) |
| Eligible user | Own chat only (`[]` if none) | No | Own chat (open or closed) | Own open chat only | Yes (not hub-gated) |
| Non-assignee officer | **403** `not_assigned_officer` | — | — | — | — |
| Ineligible user | **403** `not_chat_participant` | — | — | — | — |
| Manager | **403** on all routes | — | — | — | — |

## API endpoints

Base path: `/api/issues/{issueId}/chats` (canonical issue).

| Method | Path | Tier (officer) | Description |
|--------|------|----------------|-------------|
| `GET` | `/api/issues/{issue}/chats` | B | Paginated chat list |
| `PATCH` | `/api/issues/{issue}/chats/open` | C | Body `{ "user_id": <id> }`; **201** new, **200** reopen/idempotent |
| `PATCH` | `/api/issues/{issue}/chats/{chat}/close` | C | Manual close; no system message |
| `GET` | `/api/issues/{issue}/chats/{chat}/messages` | B | Paginated history (open or closed) |
| `POST` | `/api/issues/{issue}/chats/{chat}/messages` | C | Text and/or multipart `files` |
| `POST` | `/api/issues/{issue}/chats/{chat}/messages/mark-read` | C | Returns `{ "marked_read": N }` |
| `GET` | `.../messages/{message}/attachments/{attachment}/download` | C | Participant or assignee |

### Open chat

- Blocked when issue status is `gesloten` → **422** `issue_closed`.
- Ineligible `user_id` → **422** `chat_user_not_eligible`.

### Messages

- Send and mark-read only when chat `status` is `open` → **422** `chat_closed` when closed.
- Attachment-only messages allowed.
- Max **3** images per message, **5 MB** each (`jpg`, `jpeg`, `png`, `gif`, `webp`).
- No message edit/delete in v1.
- System messages: `message_type: system`, `meta` e.g. `chat_closed_status_gesloten`.

## Lifecycle side effects

| Event | Open chats | System message |
|-------|------------|----------------|
| `POST .../unassign-self` | All closed | No |
| Issue status → `gesloten` | All closed | Yes, one per chat |

**Reassignment handoff:** no takeover — `assign-self` keeps **409** `issue_already_assigned` while another officer is assigned. Flow: unassign → successor assign-self → `PATCH .../chats/open` to continue; prior history remains readable.

## Structured error codes

| Code | HTTP | When |
|------|------|------|
| `chat_closed` | 422 | Send or mark-read on closed chat |
| `chat_user_not_eligible` | 422 | Open with non-owner/non-participant |
| `not_chat_participant` | 403 | Outside 1:1 partnership |
| `not_assigned_officer` | 403 | Non-assignee officer |
| `chat_not_found` | 404 | Chat not on canonical issue in route |
| `issue_closed` | 422 | Open chat on `gesloten` issue |
| `hub_active_required` | 403 | Officer Tier C without shift |

## Typical client flows

### Officer (web `H_Chat.jsx` or mobile)

1. Hub login (Tier C) — [officer-shift.md](./officer-shift.md)
2. `POST .../assign-self` on issue in assigned district
3. `PATCH .../chats/open` with owner's `user_id`
4. Poll or page `GET .../messages`; `POST .../messages` to send
5. `POST .../messages/mark-read` when viewing citizen messages
6. Optional `PATCH .../chats/{chat}/close` or automatic close on unassign/`gesloten`

### Citizen (web `U_Chat.jsx`)

1. Participate on canonical (owner or join)
2. `GET .../chats` — expect single chat or `data: []` until officer opens
3. `GET .../messages` and `POST .../messages` while chat is open
4. Download attachments via authenticated download URL

## Manual verification

See [manual-checklists.md](./manual-checklists.md) — **Manual Issue Chat Checklist** (12 steps).

Postman folder **Issue Chat** in [docs/postman](../postman/README.md) auto-fills `chat_id`, `chat_message_id`, and related variables.
