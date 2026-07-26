# Milestone M0: Repository Foundation

## Objective
Establish the project's base directory structure, foundational toolchain, and CI/CD skeleton to support a predictable, scalable development workflow. Validate that both the frontend (React SPAs) and backend (Laravel 12 API) can boot locally and pass initial CI checks.

## What was implemented
- **Root Tooling & Standards:** Configured `.editorconfig`, `.gitattributes`, `.gitignore`, `eslint.config.js`, `prettier.config.js`, and `package.json` for the monorepo to enforce strict formatting and linting.
- **Frontend Monorepo Setup:** Scaffolded `apps/back-office` and `apps/public-web` using Vite (React + TS) and initialized `packages/ui` as a shared component package, orchestrated via `pnpm-workspace.yaml`.
- **Backend Foundation:** Initialized a Laravel 12 project inside `backend/`. Set up the `app/Domain/` directories according to the frozen architecture to encourage bounded contexts, alongside `phpstan.neon` (Level 5) and `pint.json` for code quality enforcement.
- **Docker Compose (Local MVP):** Created `docker-compose.yml`, `docker/app/Dockerfile`, `docker/nginx/Dockerfile`, and `docker/nginx/default.conf` using official lightweight images (`php:8.4-fpm-alpine`, `nginx:alpine`, `postgres:16`).
- **CI/CD Pipeline:** Established `.github/workflows/ci.yml` that runs backend checks (Pint, PHPStan, Pest) and frontend checks (typecheck, build) concurrently.

## Important engineering decisions
- **pnpm workspaces over Lerna/Nx:** Chosen for simplicity. It allows local package resolution (`ui` used by the apps) without overhead.
- **Vite Native Execution vs Nginx Proxying (Local Dev):** Decided to run the Vite development servers directly on local ports rather than proxying their HMR (Hot Module Replacement) websockets through Nginx. This prevents proxy-induced connection drops and provides a faster feedback loop during frontend development.
- **Extending, not dismantling, Laravel:** Maintained Laravel's default structure (`app/Http`, `app/Models`) while layering `app/Domain/*` inside it. This provides strict boundaries without losing the conventions that make Laravel approachable.
- **Image Parity (Same Dev to Prod):** Local development mounts over a production-oriented container rather than relying on a bespoke "development image," honoring the architecture principle of maintaining artifact consistency across environments.

## Lessons learned
- **Monorepo Dependency Management:** Utilizing `pnpm workspaces` cleanly manages cross-references between applications and shared UI packages without necessitating external registries.
- **Domain-Oriented Laravel:** Grouping by domain context is a practical compromise that scales well for a multi-tenant platform, prioritizing data isolation over structural purity.
- **Container Networking:** By isolating the API to Nginx/FPM and running the frontend native tools, we achieve a lightweight, developer-friendly setup that doesn't compromise production accuracy where it matters (the backend).

## Major files and folders created
- `backend/` (Laravel application)
- `backend/app/Domain/` (Identity, Tenancy, Fleet, etc.)
- `apps/back-office/` (Vite SPA)
- `apps/public-web/` (Vite SPA)
- `packages/ui/` (Shared package)
- `docker/` (Dockerfile configurations for app and nginx)
- `docker-compose.yml` (Local execution orchestration)
- `.github/workflows/ci.yml` (GitHub Actions workflow)
- `pnpm-workspace.yaml` (Monorepo configuration)
- `eslint.config.js` & `prettier.config.js` (Code standards)

## Suggested commit message
`feat(repo): initialize locarion monorepo, laravel backend, react apps, and docker stack`

## Next milestone
**Milestone M1 — Identity and Tenancy**
Focuses on implementing Sanctum authentication, roles, permissions, the Agency model, and robust tenant scoping to ensure strict data isolation.
