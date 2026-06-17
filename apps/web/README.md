# RAUW Web App

React/Vite single-page application for **RAUW** (Rotterdams Actie Uit de Wijken). Citizens report neighbourhood issues; BOA officers (*handhavers*) triage, assign, and resolve them. Routes and UI copy are Dutch; the API is documented in English under `docs/api/`.

The app talks to the Laravel backend via `VITE_API_BASE_URL` (default `http://127.0.0.1:8001`). Copy `apps/web/.env.example` to `.env` or `.env.local` before running locally.

## Portals and actors

Two active portals ship in this app:

| Portal | `user_type` (localStorage) | Login route | Primary nav |
|--------|---------------------------|-------------|-------------|
| **Citizen** (*burger*) | `user` | `/login` | Map, feed, report, news, chat, account |
| **Officer** (*handhaver* / BOA) | `officer` | `/loginhandhaver` | Command center, reports, sector settings, service profile, chat |

Authentication is client-side: after login the app stores `auth_token` and `user_type` in `localStorage`. Protected routes are wrapped by `PortalGuard` in `src/App.jsx`, which checks the token and redirects to `/login` or `/` when the actor type does not match.

There is **no manager portal** in the shipped product (see [Scrapped / not shipped](#scrapped--not-shipped)).

## Route map

| Path | Actor | Component | Purpose |
|------|-------|-----------|---------|
| `/` | Public | Onboarding | Entry / portal selection |
| `/login` | Public | U_Login | User login |
| `/registreer` | Public | U_Register | User registration |
| `/loginhandhaver` | Public | H_Login | Officer login |
| `/registreerhandhaver` | Public | H_Register | Officer registration |
| `/map` | User | Map | Issue map |
| `/feed` | User | Feed | Activity feed |
| `/meld` | User | Report | Report issue |
| `/nieuws` | User | NewsFeed | Community news |
| `/chat` | User | U_Chat | Issue chat |
| `/account` | User | Account | Account overview |
| `/instellingen` | User | AccountSettings | Account settings |
| `/meldingen` | Officer | CommandCenter | Issue command center |
| `/rapport` | Officer | H_ReportsOverview | Reports overview |
| `/sectorinstellingen` | Officer | SectorSettings | Hub/sector settings |
| `/dienstprofiel` | Officer | ServiceProfile | Service profile |
| `/chat` | Officer | H_Chat | Issue chat |

> **Note:** `/chat` is the active product route for both portals. The route entries in `App.jsx` may still be commented out while chat UI is wired; documentation reflects the intended release path.

## User journeys

- **Discover issues** — `/map` shows issues on a Leaflet map; `/feed` lists recent activity.
- **Report** — `/meld` submits a new issue (categories, location, attachments).
- **Community** — `/nieuws` reads the community news feed.
- **Chat** — `/chat` opens 1:1 issue chat with the assigned officer on canonical issues (`U_Chat.jsx`).
- **Account** — `/account` for profile overview; `/instellingen` for settings (password, preferences).

Navigation for citizens uses `U_Nav.jsx` and related user components under `src/user/`.

## Officer journeys

- **Triage and work issues** — `/meldingen` (Command Center): filter, assign self, status updates, officer notes and resolution.
- **Reporting** — `/rapport` for date-range issue reports.
- **Chat** — `/chat` for 1:1 issue chat with citizens (`H_Chat.jsx`); same path as the user portal, different component behind `PortalGuard`.
- **Sector / hub** — `/sectorinstellingen` selects hub and districts for the shift.
- **Service profile** — `/dienstprofiel` shows the officer’s assigned issues and profile.

Officer screens live under `src/handhaver/`; navigation uses `HM_Nav.jsx` with links from `src/config/navConfig.js`.

## Issue chat (web)

Issue chat is **private 1:1 messaging** on **canonical issues** between the assigned officer and one eligible citizen per chat row. It is separate from public issue comments.

| Portal | Component | Path |
|--------|-----------|------|
| Citizen | `src/user/U_Chat.jsx` | `/chat` |
| Officer | `src/handhaver/H_Chat.jsx` | `/chat` |

API contract, eligibility rules, and endpoints: [docs/api/issue-chat.md](../../docs/api/issue-chat.md). The web client consumes `issueCommentService.js` and related services; the exact `/chat` route wiring in `App.jsx` may change before release.

## Scrapped / not shipped

**Manager UI** — Code under `src/manager/` (dashboard, flagged content, user management, `M_Login.jsx`) is **not part of the shipped product**. No manager routes are registered in `App.jsx`. Leftover manager entries in `navConfig.js` are legacy only.

## Project structure

```
src/
├── user/           # Citizen portal (Map, Feed, Report, U_Chat, Account, …)
├── handhaver/      # Officer portal (CommandCenter, H_Chat, SectorSettings, …)
├── components/     # Shared UI (nav, cards, map, notifications)
├── services/       # API clients (issueService, issueCommentService, notificationService)
├── config/         # navConfig.js — officer nav links
├── modal/          # Modals (duplicate suggestion, story detail, edit issue, …)
├── hooks/          # Polling hooks (notifications, comments)
├── utils/          # Formatting and error helpers
├── constants/      # Districts, attachments, limits
├── manager/        # Scrapped — not shipped
├── App.jsx         # Router and PortalGuard
└── index.css       # Tailwind entry and global tokens
```

## Local development

From this directory:

```bash
cd apps/web
npm install
npm run dev      # Vite dev server (default http://localhost:5173)
npm run build    # Production bundle
npm run lint     # ESLint
```

The backend must be running on the URL configured in `.env` (see [docs/local-development.md](../../docs/local-development.md) for full monorepo setup).

## Environment variables

| Variable | Default (`.env.example`) | Purpose |
|----------|--------------------------|---------|
| `VITE_API_BASE_URL` | `http://127.0.0.1:8001` | Laravel API base URL (no `/api` suffix) |

Only `VITE_*` variables are exposed to the client bundle.

### Known debt: hardcoded API URLs

Several modules still use `http://localhost:8001` directly instead of `import.meta.env.VITE_API_BASE_URL`, including:

- `src/services/issueService.js`, `issueCommentService.js`, `notificationService.js`
- Parts of `src/user/` (e.g. `Map.jsx`, `Feed.jsx`, `U_Login.jsx`, `Account.jsx`)

Officer screens added later generally use `VITE_API_BASE_URL`. When pointing at a non-local API, set `.env` **and** be aware that hardcoded callers will still hit `localhost:8001` until consolidated.

## Styling

- **Tailwind CSS v4** via `@tailwindcss/vite` in `vite.config.js`
- Entry: `src/index.css` (`@import 'tailwindcss';`)
- Portal-specific CSS: e.g. `Handhaver_styling.css`, `User_styling.css`, `HM_styling.css`
- Prefer Tailwind utilities for layout; use CSS files for reusable or semantic styles and design tokens in `:root`

## Related documentation

| Document | Purpose |
|----------|---------|
| [Backend README](../backend/README.md) | Laravel API setup |
| [API guides](../../docs/api/README.md) | Narrative API index and domain docs |
| [Issue chat API](../../docs/api/issue-chat.md) | Chat endpoints and rules |
| [API policies](../../docs/api-policy.md) | Auth tiers, errors, inactive accounts |
| [OpenAPI](../../docs/openapi.yaml) | Canonical machine-readable API contract |
| [Postman](../../docs/postman/README.md) | Collection and local environment |
| [Local development](../../docs/local-development.md) | Full stack local setup |
| [Testing](../../docs/TESTING.md) | Lint and test commands |
| [Deployment](../../docs/DEPLOYMENT.md) | Deploying web and backend |
