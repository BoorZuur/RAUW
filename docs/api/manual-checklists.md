# Manual API checklists

Step-by-step verification after local setup:

```bash
cd apps/backend
php artisan migrate:fresh --seed
php -S 127.0.0.1:8001 -t public
```

Use demo accounts (`demo.user@example.com`, `demo.officer@example.com`, `demo.manager@example.com`, password `password`) or tokens from curl/Postman ([authentication.md](./authentication.md), [postman/README.md](../postman/README.md)).

Officer hub coordinates (Cluster Centrum): `latitude: 51.9106846`, `longitude: 4.4814932`. Demo officer is seeded in **Cool** wijk (`district_id: 1`).

---

## Manual Shared Shift Test Checklist

1. **Hub login starts shift** — Login as `demo.officer@example.com` at Cluster Centrum coords. Expect `hub_active: true`, `hub_active_until` ~10h ahead. `GET /api/issues` → 200.
2. **Re-login at hub does not extend** — Note `hub_active_until`. Hub login again immediately. Expect same `hub_active_until` (not extended).
3. **Outside login preserves shift** — With active shift, login from distant coords. Expect `hub_active: true`. `GET /api/issues` → 200. `GET /api/auth/me` → `hub_active: true`.
4. **Outside login without shift** — Officer with expired/null shift, remote login. Expect `hub_active: false`. `GET /api/issues` → 200 (Tier B). `POST /api/issues/{id}/assign-self` → 403 `hub_active_required`.
5. **Registration does not start shift** — `POST /api/auth/register/officer` at hub coords. Expect `hub_active: false`, `hub_active_until: null`.
6. **Start shift route** — Authenticated officer at hub, no active shift: `POST /api/auth/start-shift` with coords → 200. Repeat → 422 `shift_already_active`. Remote coords → 403 `outside_hub_radius`. Officer with no `hub_id` → 403 `hub_not_assigned`.
7. **Logout preserves shift** — Device A starts shift. Device B logs in (any coords). Logout device B. Device A `GET /api/issues` still 200 until TTL.
8. **Logout scope** — Logout revokes only that token; `officer_sessions` row for that token closed; other sessions remain open.
9. **Hub reassignment ends shift** — Manager `PATCH /api/officers/{id}/hub`. Expect `hub_active_until` cleared; tokens still authenticate but Tier C → 403 until new shift.
10. **Manager end-shift** — `PATCH /api/officers/{id}/end-shift`. Shift cleared; tokens not revoked; Tier C blocked.
11. **Disable full revoke** — Manager disables officer. All tokens invalid; shift cleared; all sessions closed.
12. **Hub deactivated mid-shift** — Deactivate hub while shift active. Officer workflows still work until `hub_active_until` expires.
13. **Manager session list** — `GET /api/officer-sessions` works; audit fields populated; response does **not** include `personal_access_token_id`.
14. **Inactive actor structured 403** — Disable officer but retain stale token. `GET /api/issues` → 403 `account_inactive`. Whitelisted `GET /api/auth/me` still works.
15. **Profile PATCH hub fields rejected** — `PATCH /api/auth/me` with `hub_id` or `hub_active_until` → 422 (prohibited).

Details: [officer-shift.md](./officer-shift.md).

---

## Manual Officer Issue Workflow Checklist

After hub login as `demo.officer@example.com` (Cool / `district_id: 1`):

1. **Assign-self** — `POST /api/issues/{unassigned_open_issue}/assign-self` → 200, `assigned_officer_id` set; open issues also become `in_behandeling`.
2. **District block** — Officer without issue district → 403 `officer_not_in_district`.
3. **Conflict** — Second officer assigns same issue → 409 `issue_already_assigned`.
3b. **Terminal assign block** — `POST .../assign-self` on `opgelost`/`gesloten` → 422 `issue_not_assignable`.
4. **Unassign** — Current assignee `POST .../unassign-self` → 200, assignee cleared, status unchanged.
5. **Status** — `PATCH /api/issues/{id}/status` with `{ "status": "opgelost", "note": "Fixed" }` → 200, history row, `resolved_at` set.
6. **Invalid transition** — Direct `open` → `opgelost` → 422. Direct `in_behandeling` → `gesloten` → 422.
7. **Not assigned** — Another officer PATCH status → 403 `not_assigned_officer`.
8. **List vs show embeds** — `GET /api/issues` includes `officer_resolution` when present, omits `status_history`. Officer/manager `GET /api/issues/{id}` adds `status_history`; user GET omits it.
9. **Resolution create** — Multipart POST with title, content, images → 201; second POST → 409. On `in_behandeling`, POST also moves to `opgelost` with history (`note: "Oplossing geplaatst"`).
10. **Resolution update** — PATCH with new title/content, remove one attachment, add one → 200, ≤3 attachments total.
10b. **Resolution blocked on gesloten** — PATCH (or POST) on `gesloten` issue → 422 `issue_closed`; `opgelost` remains writable.
11. **Resolution read** — User, officer, manager who can view issue → GET `/officer-resolution` 200; hidden issue → 404.
12. **Download** — Same visibility as show.
13. **Browse without shift** — Officer with expired shift → `GET /api/issues` and `GET /api/issues/{id}` still 200; Tier C writes → 403 `hub_active_required`.
14. **Status history (paginated)** — `GET /api/issues/{id}/status-history?page=1&per_page=20` → 200, newest first; duplicate child route id returns canonical history. Tier B.
15. **Mark duplicate (re-parent)** — `POST /api/issues/{child}/mark-duplicate` with `{ "duplicate_of_id": <canonical> }` for different owners → 200 child with `duplicate_of_id` set, `visibility: hidden`.
16. **Mark duplicate (merge)** — Same-owner child + canonical → 200 canonical; child id 404 on subsequent GET.
17. **Resolution attachment delete** — Assignee `DELETE .../officer-resolution/attachments/{attachment}` → 204; repeat → 404. Tier C.

Details: [issues.md](./issues.md).

---

## Manual Issue Chat Checklist

After hub login as `demo.officer@example.com`, assign-self on an issue in Cool (`district_id: 1`):

1. **Open chat** — `PATCH /api/issues/{id}/chats/open` with `{ "user_id": <owner> }` → 201; repeat → 200.
2. **Parallel chats** — Open second chat with a participant `user_id` → two distinct `chat_id` values.
3. **List (Tier B)** — `GET .../chats` without shift → 200; user with no chat → `data: []`.
4. **Send** — `POST .../chats/{chat}/messages` with text and/or multipart `files` → 201.
5. **Closed send block** — Close chat; POST message → 422 `chat_closed`.
6. **Read closed history** — `GET .../messages` on closed chat → 200.
7. **Mark read** — `POST .../messages/mark-read` in open chat → `{ "marked_read": N }`; on closed chat → 422.
8. **Attachment download** — Participant `GET .../attachments/.../download` → 200; outsider → 403 `not_chat_participant`.
9. **Unassign closes chats** — `POST .../unassign-self` → open chats closed, no system message.
10. **Handoff** — Second officer: assign-self blocked while assigned → 409; after unassign → assign-self → reopen chat → read prior messages → send.
11. **Gesloten system message** — Status to `gesloten` → open chats closed with system message per chat.
12. **Manager excluded** — Manager `GET .../chats` → 403.

Details: [issue-chat.md](./issue-chat.md).
