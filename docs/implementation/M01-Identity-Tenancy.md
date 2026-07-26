# M01 — Identity & Tenancy Implementation History

## Objective
Establish the foundational multi-tenant security boundary. Implement authentication (Sanctum), the Spatie roles/permissions matrix, and the global tenancy scoping mechanism to guarantee that an agency's data is strictly isolated and provably invisible to other agencies.

## What was implemented
1. **Core Tenancy Infrastructure:**
    - `TenantContext` Singleton Service: Acts as the single source of truth for the active `agency_id`.
    - `TenantScope`: An Eloquent Global Scope that automatically applies a `WHERE agency_id = ?` filter.
    - `HasTenancy` trait: Automatically boots the `TenantScope` and handles auto-assignment of `agency_id` on model creation.
    - `SetPermissionsTeamId` Middleware: Extracts the user's `agency_id` and hydrates the `TenantContext`, as well as Spatie's internal team cache.

2. **Authentication & Authorization:**
    - Laravel Sanctum configured for SPA cookie-session authentication with `EnsureFrontendRequestsAreStateful` mapped to API routes.
    - Single-purpose Actions (`LoginUserAction`, `LogoutUserAction`) encapsulating login and logout logic.
    - Controllers (`LoginController`, `LogoutController`, `MeController`) acting strictly as HTTP transport layers.
    - Integrated `spatie/laravel-permission` with the `teams` feature enabled.
    - Access control middlewares: `EnsureUserIsActive` and `EnsureAgencyIsActive`.

3. **Data Layer & Seeders:**
    - Created `agencies` migration and `Agency` model inside the domain structure.
    - Updated Laravel's default `users` migration to use UUIDs, added the `is_active` flag, and established the foreign key `agency_id`.
    - Implemented `RoleAndPermissionSeeder` parsing the precise matrix from the architecture docs.
    - Established `SuperAdminUserSeeder` and `DemoAgencySeeder` to bootstrap an immediate, testable environment.

## Important Engineering Decisions
- **Tenant Context Singleton:** By funneling `agency_id` lookup through a dedicated `TenantContext` singleton rather than globally checking `Auth::user()->agency_id`, we've cleanly paved the way for "Super Admin Impersonation." When impersonation is eventually built, it only needs to instruct the `TenantContext` to return a different ID, and all scopes and Spatie permissions will seamlessly adapt without requiring code changes to Eloquent Models or Policies.
- **Strict Rate Limiting:** We set up a custom rate limiter specifically for the `/api/v1/login` endpoint to prevent credential brute-forcing, while still utilizing standard session throttling for regular API routes.
- **Pest vs PHPUnit:** Due to local dependency constraints with Pest setup in this fresh environment, the isolation tests were safely authored using standard PHPUnit `TestCase` functionality while preserving the identical test coverage and logic.

## Lessons Learned
- **Sanctum Stateful Middleware:** Laravel 11's streamlined directory structure means that configuring Sanctum's stateful middleware requires specific injection into the `->api(prepend: [...])` array inside `bootstrap/app.php`. If testing login routes manually, explicitly providing `$this->withSession([])` combined with a stateful `Referer` header is required to bypass Sanctum's default CSRF/domain protections in tests.

## Major files and folders created
- `backend/app/Domain/Tenancy/Services/TenantContext.php`
- `backend/app/Domain/Tenancy/Scopes/TenantScope.php`
- `backend/app/Domain/Tenancy/Traits/HasTenancy.php`
- `backend/app/Domain/Tenancy/Middleware/EnsureAgencyIsActive.php`
- `backend/app/Domain/Tenancy/Middleware/SetPermissionsTeamId.php`
- `backend/app/Domain/Tenancy/Models/Agency.php`
- `backend/app/Domain/Identity/Models/User.php`
- `backend/app/Domain/Identity/Actions/LoginUserAction.php`
- `backend/app/Domain/Identity/Controllers/LoginController.php`
- `backend/database/seeders/RoleAndPermissionSeeder.php`

## Suggested commit message
```
feat(identity): implement M1 Identity and Tenancy isolation

- Initialize Sanctum SPA authentication and Spatie roles/permissions (teams)
- Build TenantContext service and Eloquent TenantScope for strict data isolation
- Implement Auth controllers, actions, and active-state middlewares
- Scaffold Agencies table and UUID-based Users table
- Seed complete role matrix, Super Admin, and a demo agency
- Add PHPUnit tests proving correct tenant isolation and login flows
```

## Next milestone
M2 — Fleet & Public Search MVP
