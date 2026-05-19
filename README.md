# RAW

**Real Art Walls** — a single repository containing three independent applications.

## Repository Structure

This repository is a plain single repo (no workspaces, no task runner, no shared packages). Each application lives in its own top-level directory and is fully self-contained with its own dependencies, tooling, and lifecycle.

```
RAW/
├── backend/   # Laravel (PHP) API — independent
├── web/       # Vite + React web app — independent
├── mobile/    # Expo / React Native mobile app — independent
└── plans/     # Implementation plans and documentation
```

### Apps

- **`backend/`** — Laravel PHP backend API. Managed with Composer.
- **`web/`** — React web application built with Vite. Managed with npm (or your preferred Node package manager).
- **`mobile/`** — React Native mobile app using Expo. Managed with npm (or your preferred Node package manager).

## Conventions

- **No shared code package.** The three apps do not share source code through a workspace or internal package. If something needs to be shared (e.g., API types), it is duplicated or generated per app.
- **Independent tooling.** Each app has its own `package.json` / `composer.json`, lockfile, scripts, and environment configuration.
- **Independent installs and runs.** Commands are always executed from within the respective app directory (`backend/`, `web/`, or `mobile/`).

## Getting Started

Each application has its own setup instructions in its directory's README once it is bootstrapped. Until then, the directories are placeholders.
