# Issues (meldingen)

Citizen reports and officer/manager workflows on **issues**. OpenAPI tags: **Issues**, **Issue Comments**, **Issue Feedback**, **Attachments**.

Cross-cutting: [api-policy.md](../api-policy.md) (district scoping, Tier B/C, error codes). Officer shift: [officer-shift.md](./officer-shift.md). Private chat: [issue-chat.md](./issue-chat.md).

## Visibility and scoping

Before filters, list/show apply actor visibility:

| Actor | Sees |
|-------|------|
| **User** | Visible canonicals, own issues (any visibility), canonicals they participate on |
| **Officer / ordinary manager** | Issues in assigned districts (including hidden in those districts) |
| **Main manager** | All issues city-wide |

Issues outside scope return **404** on show (not **403**) to avoid leaking existence.

**Writes** (create, update, delete) require the active **user** who owns `issues.user_id`.

## CRUD and list filters

| Method | Path | Who |
|--------|------|-----|
| `GET` | `/api/issues` | Authenticated actor (Tier B for officers) |
| `POST` | `/api/issues` | Active user (owner) |
| `GET` | `/api/issues/{issue}` | Authenticated actor (Tier B) |
| `PATCH`/`PUT` | `/api/issues/{issue}` | Issue owner (active user) |
| `DELETE` | `/api/issues/{issue}` | Issue owner |
| `PATCH` | `/api/issues/{issue}/visibility` | Officer or manager |
| `POST` | `/api/issues/similar-check` | Active user |

### List query parameters

| Param | Actors | Description |
|-------|--------|-------------|
| `district_id`, `department` (code), `category_id`, `status` | All | AND filters |
| `mine=1` | User | Owned issues only |
| `followed=1` | User | Canonical stories user follows (deduped with duplicate children) |
| `participating=1` | User | Owned duplicate children with active canonical participation |
| `visibility` | Officer, manager | `visible` or `hidden` |
| `assigned_officer_id`, `unassigned=1` | Officer, manager | Mutually exclusive |
| `include_duplicates=1` | Officer, manager | Include duplicate child rows |

`mine`, `participating`, and `followed` are pairwise mutually exclusive.

### Create/update rules

- `district_id` required on create; must be active district.
- `category_id` drives read-only `departments` array and server-side `priority` (from main category; clients must not send `priority`).
- `assigned_officer_id` is read-only on user create/update — use officer assign-self.
- Optional `duplicate_of_id` on create — see [Duplicates](#duplicates-and-participants).

## Duplicates and participants

Flow: **similar-check** → optional **create with `duplicate_of_id`** → optional **join/leave**.

### Similar-check

`POST /api/issues/similar-check` (active users). Scores up to 50 open/`in_behandeling` canonical issues in the requested district; returns top five in `own_matches` (`linkable: false`) and `matches` (`linkable: true`).

### Create duplicate

`POST /api/issues` with `duplicate_of_id` creates a **hidden** child (`visibility=hidden`). Canonical `duplicate_count` increments; author may be added as participant (`joined_via=duplicate`). Normal creates add creator participant (`joined_via=creator`, `participant_count=1`).

### Join / leave

| Method | Path | Notes |
|--------|------|-------|
| `POST` | `/api/issues/{issue}/join` | Canonical only; optional `{ "is_anonymous": true }`; first join **201**, repeat **200** |
| `DELETE` | `/api/issues/{issue}/leave` | Removes participation; **422** `not_participant` |

Join on duplicate child → **422** `cannot_join_duplicate_child`.

### Participant list

`GET /api/issues/{canonical}/participants` — officers and managers only, Tier B. Paginated oldest-first. Anonymous participants: `is_anonymous: true` + stable `display_name`.

### Participant visibility (users)

Participants on canonicals they do not own receive redacted payloads (status, resolution, counters) while title, content, location, author, and attachments are hidden (`author: { is_participant: true }`).

### Delete semantics

Optional body: `{ "leave_participation": false }` (default keeps canonical participation when deleting duplicate child).

| Target | Behavior |
|--------|----------|
| Duplicate child | Decrements `duplicate_count`; optional leave participation |
| Canonical with children | Promotes oldest child, re-parents siblings, migrates participants |
| Canonical without children | Hard delete |

### Officer duplicates

| Method | Path | Who | Tier |
|--------|------|-----|------|
| `GET` | `/api/issues/{canonical}/duplicates` | Officer, manager | B |
| `POST` | `/api/issues/{issue}/mark-duplicate` | Officer, manager | C (officers) |

Body: `{ "duplicate_of_id": <canonical> }`. Branches:

| Branch | When | Response |
|--------|------|----------|
| Re-parent | Different owners | **200** child `IssueResource` (hidden) |
| Same-owner merge | Same owner | **200** canonical; child hard-deleted |

### Duplicate error codes

| Code | HTTP | When |
|------|------|------|
| `duplicate_target_not_found` | 404 | Target missing or not visible |
| `issue_not_matchable` | 422 | Canonical not `open`/`in_behandeling` |
| `cannot_duplicate_self` | 422 | Author owns canonical |
| `cannot_join_duplicate_child` | 422 | Join on child |
| `not_participant` | 422 | Leave when not participating |
| `issue_not_canonical` | 422 | Duplicates list on child |
| `issue_is_duplicate_child` | 422 | Operation requires canonical |
| `issue_not_linkable` | 422 | Child not linkable |
| `issue_has_duplicates` | 422 | Child has its own children |

## Officer workflows

Officers **browse** without shift (Tier B). Assignment, status, field reports, and most writes are **Tier C** (`hub_active_required`).

Validate-after-lock: visibility/district checks before transaction; assignee, transitions, and duplicate checks on locked row.

**District scoping:** officer/manager workflow on issues outside assigned districts → officer **403** `officer_not_in_district`; browse outside scope → **404**. Main managers city-wide.

### Self-assign / unassign

| Method | Path | Notes |
|--------|------|-------|
| `POST` | `/api/issues/{issue}/assign-self` | Idempotent for self; **409** `issue_already_assigned`; **422** `issue_not_assignable` on `opgelost`/`gesloten`; open → `in_behandeling` |
| `POST` | `/api/issues/{issue}/unassign-self` | Clears assignee; **403** `not_assigned_officer`; closes open issue chats (no system message) |

### Status

`PATCH /api/issues/{issue}/status` — assigned officer only. Body: `{ "status", "note" }`.

Directed transitions: `open` → `in_behandeling` → `opgelost` → `gesloten`. Sets `resolved_at` on first `opgelost`. Appends status history (no GPS).

`GET /api/issues/{issue}/status-history` — officer/manager, Tier B, paginated newest-first.

### Officer resolution (field report)

Distinct from user satisfaction feedback. **One** report per issue (`officer_issue_resolutions.issue_id` unique).

| Method | Path | Tier | Notes |
|--------|------|------|-------|
| `GET` | `.../officer-resolution` | B | **404** when none |
| `POST` | `.../officer-resolution` | C | Multipart; **409** `officer_resolution_exists`; **422** `issue_closed` on `gesloten`; `in_behandeling` POST also → `opgelost` + history |
| `PATCH` | `.../officer-resolution` | C | Multipart; cap 3 images under lock |
| `GET` | `.../officer-resolution/attachments/{id}/download` | B | Visibility-only |
| `DELETE` | `.../officer-resolution/attachments/{id}` | C | Assignee only |

Attachments: max **3** images (`jpg`, `jpeg`, `png`, `gif`, `webp`), **5 MB** each.

### Officer updates (progress log)

| Method | Path | Tier |
|--------|------|------|
| `GET` | `.../officer-updates` | B |
| `POST` | `.../officer-updates` | C |
| `PATCH` | `.../officer-updates/{id}` | C |
| `DELETE` | `.../officer-updates/{id}` | C |
| `GET` | `.../officer-updates/attachments/{id}/download` | B |

PATCH/DELETE: authoring assignee only (**403** `not_update_author`). Same attachment limits as resolution.

### Embeds

- `GET /api/issues` — includes `officer_resolution` when present; omits `status_history`.
- `GET /api/issues/{issue}` — same resolution embed; `status_history` for officers/managers only (newest first, no lat/lon).

## Issue feedback (user satisfaction)

Distinct from officer field reports. Table: `issue_feedback`. Window: **7×24 h** to submit after last close; **24 h** to edit/delete after `submitted_at`.

| Method | Path | Who | Tier |
|--------|------|-----|------|
| `GET` | `/api/issues/{issue}/feedback` | User, officer, manager | B |
| `POST` | `/api/issues/{issue}/feedback` | Participant user | — |
| `PATCH` | `/api/issues/{issue}/feedback/{id}` | Reviewer owner | — |
| `DELETE` | `/api/issues/{issue}/feedback/{id}` | Reviewer owner | — |
| `GET` | `/api/officers/me/feedback` | Officer | B |

Errors: `feedback_not_allowed`, `feedback_window_closed`, `feedback_edit_window_closed`, `not_issue_participant`, **409** `feedback_already_submitted`.

## Issue comments

Public thread on the issue (distinct from [issue chat](./issue-chat.md)).

| Method | Path | Tier (officers) | Who |
|--------|------|-----------------|-----|
| `GET` | `/api/issues/{issue}/comments` | B | Anyone who can view issue |
| `POST` | `/api/issues/{issue}/comments` | C | User, officer, manager |
| `PATCH` | `/api/issues/{issue}/comments/{id}` | C | Author |
| `DELETE` | `/api/issues/{issue}/comments/{id}` | C | Author or manager |
| `PATCH` | `/api/issues/{issue}/comments/{id}/visibility` | C | Officer or manager |

Anonymous issue owners commenting are redacted with stable alias (`Melder#…`). Officers/managers always show real identity.

## Issue attachments (user-owned)

| Method | Path | Who |
|--------|------|-----|
| `POST` | `/api/issues/{issue}/attachments` | Issue owner |
| `GET` | `/api/issues/{issue}/attachments/{id}/download` | Anyone who can view issue (Tier B) |
| `DELETE` | `/api/issues/{issue}/attachments/{id}` | Issue owner |

Max **5** files per issue, **5 MB** each: `jpg`, `jpeg`, `png`, `gif`, `webp`, `pdf`. Served only via authenticated download (non-public storage).

## Officer workflow error codes

| Code | HTTP | When |
|------|------|------|
| `hub_active_required` | 403 | Tier C without shift |
| `officer_not_in_district` | 403 | Workflow outside districts |
| `not_assigned_officer` | 403 | Not current assignee |
| `not_update_author` | 403 | Officer update PATCH/DELETE |
| `issue_already_assigned` | 409 | Self-assign conflict |
| `officer_resolution_exists` | 409 | Duplicate resolution POST |
| `issue_not_assignable` | 422 | Assign on terminal status |
| `issue_closed` | 422 | Write on `gesloten` issue |

Manual verification: [manual-checklists.md](./manual-checklists.md).
