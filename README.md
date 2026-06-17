# RAUW — Rotterdams Actie Uit de Wijken

RAUW is a neighbourhood reporting platform for Rotterdam. Citizens (*burgers*) report local issues (*meldingen*) on a map and in a community feed. BOA officers (*handhavers*) triage and resolve those reports within district-scoped workflows. Each canonical issue can have **1:1 chat** between the reporting citizen and the assigned officer.

The product is built for Rotterdam's wijken context: Dutch UI routes, district and department scoping, and officer shift management on the API side.

## Repository layout

This is a plain monorepo — no workspaces, no shared packages. Each application is self-contained under `apps/` with its own dependencies and lifecycle.

```
RAUW/
├── apps/
│   ├── backend/     # Laravel API
│   └── web/         # React + Vite web client
├── docs/            # Cross-cutting guides and API reference
└── plans/           # Implementation and documentation plans
```

There is **no** `apps/mobile` directory in this repository.

## Applications

| Application | Stack | Role |
|-------------|-------|------|
| **Backend API** | Laravel 13, PHP 8.3+, Sanctum, SQLite | REST API for auth, issues, districts, departments, notifications, issue chat, and officer workflows |
| **Web client** | React 19, Vite 8, Tailwind 4, Leaflet | Two portals in one SPA: **user** (map, feed, report, chat) and **officer** (meldingen, rapport, chat) |

**Web portals (shipped):**

- **User** — `/map`, `/feed`, `/meld`, `/nieuws`, `/chat`, `/account`, `/instellingen`
- **Officer** — `/meldingen`, `/rapport`, `/sectorinstellingen`, `/dienstprofiel`, `/chat`

**Not shipped:** a manager web UI (`apps/web/src/manager/` exists on disk but is not routed). Manager actors use the backend API only. See [Web app README](apps/web/README.md) for details.

## Quick start

1. Clone the repository.
2. Follow [Local development](docs/local-development.md) for prerequisites, env files, and migrations.
3. Start the backend API on **port 8001**:

   ```bash
   cd apps/backend
   php -S 127.0.0.1:8001 -t public
   ```

4. In a second terminal, start the web client:

   ```bash
   cd apps/web
   npm run dev
   ```

Set `VITE_API_BASE_URL=http://127.0.0.1:8001` in `apps/web/.env` (see `apps/web/.env.example`).

## Documentation index

| Document | Description |
|----------|-------------|
| [Local development](docs/local-development.md) | Prerequisites, ports, migrations, daily workflow |
| [Testing](docs/TESTING.md) | Test, lint, and validation commands |
| [Deployment](docs/DEPLOYMENT.md) | Deploying backend and web |
| [Backend app](apps/backend/README.md) | API service setup and architecture |
| [Web app](apps/web/README.md) | Frontend portals, routes, dev setup |
| [API guides](docs/api/README.md) | Narrative domain documentation |
| [API policies](docs/api-policy.md) | Auth, tiers, errors, inactive accounts |
| [OpenAPI](docs/openapi.yaml) | Canonical machine-readable API contract (WIJK title in spec) |
| [OpenAPI schemas](docs/openapi/schemas.yaml) | Shared request/response models |
| [Database schema](docs/DBML.txt) | Domain ERD for dbdiagram.io (Dutch field notes) |
| [Backend OWASP & deploy notes](docs/backend-owasp-installatie-deployment.txt) | Dutch install/deploy + OWASP summary |
| [Postman](docs/postman/README.md) | Collection and local environment |

## Tech stack

| Layer | Technologies |
|-------|--------------|
| **Backend** | PHP 8.3+, Laravel 13, Laravel Sanctum, SQLite (default local DB) |
| **Web** | React 19, Vite 8, Tailwind CSS 4, Leaflet |

## Conventions

- **Monorepo without workspaces** — run `composer` and `npm` commands from `apps/backend` or `apps/web`, not from the repository root.
- **Dutch UI routes** — e.g. `/meld`, `/meldingen`, `/instellingen`, `/chat`; public auth at `/login`, `/registreer`, `/loginhandhaver`, `/registreerhandhaver`.
- **Mixed EN/NL documentation** — English structure and contributor guides; Dutch labels where they match the product UI.
- **WIJK** — internal codename used in OpenAPI, DBML, and `docs/api-policy.md`; the product name is **RAUW**.

## Plans

Implementation and documentation plans live in [`plans/`](plans/).
