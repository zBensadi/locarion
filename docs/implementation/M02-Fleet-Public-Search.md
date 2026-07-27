# M02: Fleet & Public Search MVP

## Objective
Establish the primary domain models and API endpoints required to manage a vehicle fleet and expose an aggregated public search functionality for available cars. 

## What Was Implemented

1. **Domain Models & Migrations**:
   - `VehicleCategory`: Platform-level classification system for vehicles.
   - `Vehicle`: Core entity for fleet tracking, supporting availability statuses and daily pricing.
   - Introduced soft deletes and tenancy scoping for vehicles to guarantee isolation.

2. **API Boundaries**:
   - **Admin API**: `GET|POST|PUT|DELETE /api/v1/admin/categories` (Global management).
   - **Back-Office API**: `GET /api/v1/back-office/categories` (Read-only reference) and `GET|POST|PUT|DELETE /api/v1/back-office/vehicles` (Tenant-scoped fleet management).
   - **Public API**: `GET /api/v1/public/vehicles` (Anonymous availability search).

3. **Business Logic & Actions**:
   - `CreateVehicleAction` and `UpdateVehicleAction` encapsulate creation and updates.
   - `SearchVehiclesAction` manages public queries, purposefully bypassing `TenantScope` using `withoutGlobalScope()` while actively filtering by `status = 'available'` and enforcing active agency validation to prevent data leakage.

4. **Authorization**:
   - `VehicleCategoryPolicy` enforces `platform.categories.manage` permission for modifications.
   - `VehiclePolicy` guarantees strict `fleet.create|update|delete` permissions mapped against tenant IDs.
   - Enhanced `SetPermissionsTeamId` middleware to fallback to a dummy UUID for global super-admins, ensuring Spatie's `team_id` constraint resolves effectively.

## Important Engineering Decisions
- **Factory Resolution Overhaul**: Updated `AppServiceProvider::boot()` with `Factory::guessFactoryNamesUsing` to natively resolve factories that align with the custom Domain-oriented folder structure (e.g. `App\Domain\Identity\Models\User` mapping properly to `Database\Factories\UserFactory`).
- **Policy Enforcement**: `Gate::authorize()` was maintained at the controller level rather than form requests, in alignment with Laravel 11's shift towards simplified, dedicated controller gates.
- **Tenant Context For Roles**: Spatie's `$table->uuid('team_id')` composite primary key was enforcing not-null values. To maintain global roles, `00000000-0000-0000-0000-000000000000` is now consistently utilized as a dummy key for users unassociated with an agency.

## Lessons Learned
- Testing authorization in a tenant-aware setup requires careful ordering. Roles provisioned globally (without a team context) will cause validation failures if evaluated inside a tenant-scoped request unless the team contexts properly match up.
- Laravel 11's default factory guessing must be explicitly retrained when adopting Domain-Driven directories to prevent runtime `Class Not Found` exceptions during tests.

## Major Files and Folders Created
- `app/Domain/Fleet/Models/Vehicle.php`
- `app/Domain/PlatformAdmin/Models/VehicleCategory.php`
- `app/Http/Controllers/Api/V1/Admin/VehicleCategoryController.php`
- `app/Http/Controllers/Api/V1/BackOffice/VehicleController.php`
- `app/Http/Controllers/Api/V1/Public/VehicleController.php`
- `database/factories/VehicleFactory.php`
- `database/factories/VehicleCategoryFactory.php`
- `tests/Feature/FleetAndPublicSearchTest.php`

## Suggested Commit Message
`feat(m2): implement fleet management and public vehicle search`

## Next Milestone
**M3: Reservation Engine & Pricing**
