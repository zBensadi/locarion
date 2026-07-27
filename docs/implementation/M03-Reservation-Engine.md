# Milestone M03: Reservation Engine & Pricing

## Objective
The objective of this milestone was to implement the foundational V1 components of the Locarion Reservation Engine. This establishes the complete rental workflow, allowing public booking requests, vehicle availability validation, and basic daily pricing calculation while deferring advanced features (like payments, multi-step approvals, and dynamic pricing) to V2+.

## What was Implemented
1. **Domain Model & Migration:** Created the `Reservation` model equipped with `HasTenancy`, `HasUuids`, and `SoftDeletes`. Added the `reservations` table containing relations to `agencies` and `vehicles`, guest details (`customer_name`, `customer_email`, `customer_phone`), booking dates, and pricing snapshot (`daily_rate_snapshot`).
2. **Domain Actions:**
   - `CheckVehicleAvailabilityAction`: Validates that a vehicle is not overbooked for requested dates, explicitly ignoring terminal states (`rejected`, `cancelled`, `completed`).
   - `CreateReservationAction`: Validates the minimum 1-day rental, checks availability, calculates the total price from the vehicle's `daily_rate`, and records the snapshot.
   - `UpdateReservationStatusAction`: Enforces strict lifecycle state transitions (`pending` -> `confirmed` -> `completed` / `cancelled`).
3. **API & Controllers:**
   - **Public API:** `POST /api/v1/public/reservations` for guest booking, utilizing a global scope bypass to properly assign the tenant.
   - **Back-Office API:** Standard CRUD (`index`, `store`, `show`, `destroy`) and a specialized `updateStatus` action endpoint scoped by `TenantScope` and Spatie permissions.
4. **Permissions & Security:** Added `reservations.view`, `reservations.create`, `reservations.update`, and `reservations.status.update` into the `RoleAndPermissionSeeder`.
5. **Testing Infrastructure:** Created `ReservationTest` proving the public creation, tenant isolation, availability overlap protection, and admin state management.

## Important Engineering Decisions
- **Lean Customer Management:** To satisfy the aggressive V1 timeline, we intentionally deferred a dedicated `Customer` entity and authentication system, embedding the guest details directly into the `Reservation` model.
- **Route Model Binding with Tenancy:** Added `resolveRouteBinding` to the `HasTenancy` trait to automatically bypass `TenantScope` when Laravel performs route model binding. This resolves timing issues with Laravel 11's implicit bindings and guarantees `Gate::authorize()` can correctly evaluate 403 (Unauthorized) instead of throwing obscure 404s.
- **Price Snapshotting:** To protect historical data against future adjustments to the vehicle's `daily_rate`, the exact rate at the time of creation is saved as `daily_rate_snapshot` and drives all `total_price` calculations.

## Lessons Learned
Laravel 11 routing middleware architecture dictates that implicit Route Model Binding (`SubstituteBindings`) executes before custom route-level middlewares (like `tenant.team`). By overriding `resolveRouteBinding` inside `HasTenancy`, we bypassed structural 404 errors while retaining strict domain-level authorization via Policies.

## Major Files Modified/Created
- `app/Domain/Fleet/Models/Reservation.php` (NEW)
- `database/migrations/2026_07_27_183509_create_reservations_table.php` (NEW)
- `app/Domain/Fleet/Actions/CheckVehicleAvailabilityAction.php` (NEW)
- `app/Domain/Fleet/Actions/CreateReservationAction.php` (NEW)
- `app/Domain/Fleet/Actions/UpdateReservationStatusAction.php` (NEW)
- `app/Http/Controllers/Api/V1/Public/ReservationController.php` (NEW)
- `app/Http/Controllers/Api/V1/BackOffice/ReservationController.php` (NEW)
- `app/Domain/Fleet/Policies/ReservationPolicy.php` (NEW)
- `app/Domain/Tenancy/Traits/HasTenancy.php` (MODIFIED)
- `routes/api.php` (MODIFIED)
- `tests/Feature/ReservationTest.php` (NEW)

## Next Milestone
Milestone M04 — Core Customer Experience
