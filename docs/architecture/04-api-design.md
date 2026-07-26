# 04 — API Design
## Locarion — Multi-Tenant SaaS Car Rental Platform

> **Document status:** Version 1.0 (Frozen)
> **Source of truth (frozen, do not reinterpret):** `PROJECT-BLUEPRINT.md`, `01-system-architecture.md` (v1.0), `02-database-design.md` (v1.0), `03-authorization-and-roles.md` (v1.0)
> **Purpose of this document:** the complete, implementation-ready REST API specification for Locarion — every MVP endpoint, its authorization requirements, request/response contract, and conventions — so backend and frontend developers can build directly from it without making further architectural decisions.
> **Scope discipline:** this document does not redesign or re-explain decisions already fixed upstream. Where it needs a fact from a prior document, it references the section (e.g., "per `03-authorization-and-roles.md` §7.5") rather than repeating the reasoning. New, API-specific decisions are marked **`> Assumption:`**.
> **Companion documents:** `05-cicd-and-deployment.md`, `06-testing-strategy.md`.

---

## Table of Contents

1. [Purpose](#1-purpose)
2. [API Architecture](#2-api-architecture)
3. [Authentication](#3-authentication)
4. [Endpoint Organization](#4-endpoint-organization)
5. [Endpoint Specifications](#5-endpoint-specifications)
6. [API Resources](#6-api-resources)
7. [Pagination](#7-pagination)
8. [Filtering & Search](#8-filtering--search)
9. [Validation](#9-validation)
10. [Error Handling](#10-error-handling)
11. [File Uploads](#11-file-uploads)
12. [API Conventions](#12-api-conventions)
13. [Rate Limiting](#13-rate-limiting)
14. [OpenAPI Strategy](#14-openapi-strategy)
15. [Future Evolution](#15-future-evolution)
16. [API ADRs](#16-api-adrs)

---

## 1. Purpose

### 1.1 API Goals

Give both frontend applications (`back-office`, `public-web`) a single, predictable contract to build against, per Architecture §7's "one API, two SPAs" decision. This document exists so that contract is written down once, precisely, rather than negotiated endpoint-by-endpoint during implementation.

### 1.2 REST Philosophy

Resourceful, convention-driven routes — one URL per resource, HTTP verbs carry the action — per Blueprint §11 and Architecture §8.1. This document is the concrete fulfillment of Architecture §8.1's statement that "the later API Design document is mostly about filling in this skeleton rather than inventing conventions from scratch."

### 1.3 Consistency

Every endpoint shares the same envelope shapes (§7, §10), the same pagination metadata, the same error format, and the same field-naming/type conventions (§12) — a developer who has implemented one list endpoint has effectively learned the shape of all of them.

### 1.4 Versioning Strategy

`/api/v1/...` from day one (Blueprint §11, Architecture §8.2). A breaking change to any contract fixed in this document requires a new `/api/v2/...` prefix, never an in-place breaking change to `v1` (Architecture §8.2) — restated here only because every endpoint below is, by virtue of appearing in this document, a `v1` contract commitment.

### 1.5 Why the API Is Structured This Way

Two route surfaces (public/authenticated), enforced at the routing layer, are the seam that makes the "one shared codebase, two audiences" trade-off safe (Architecture §2.3). This document's job is to place every one of the ~75 MVP endpoints on the correct side of that seam, unambiguously.

---

## 2. API Architecture

### 2.1 Three Route Surfaces

| Surface | Prefix | Auth | Who calls it |
|---|---|---|---|
| Public | `/api/v1/public/*` | None | `public-web`, Anonymous Visitors |
| Authenticated (tenant-scoped) | `/api/v1/*` | Sanctum session | `back-office` — Agency Admin, Employee |
| Platform Admin | `/api/v1/admin/*` | Sanctum session + `access-platform-admin` Gate | `back-office` — Super Admin only |

This is an implementation of Architecture §2.1/§6 and `03-authorization-and-roles.md` §8.2/§8.3 — this document does not alter that split, only enumerates every route within it.

### 2.2 Route Grouping

Within the authenticated surface, routes are grouped by domain module (Architecture §3.1), matching this document's §4/§5 organization: `vehicles`, `customers`, `reservations`, `invoices`, `booking-requests`, `employees`, `reports`, and the singleton `agency`. Nested resources (vehicle images, invoice payments, reservation contracts) are routed under their parent per §12.2.

### 2.3 URI Conventions

- Plural nouns for collections (`/vehicles`, not `/vehicle`), singular for a singleton (`/agency`, `/me`) — §12.2.
- Route parameters are UUIDs, bound via tenant-aware implicit route-model binding (`03-authorization-and-roles.md` §7.7) — a wrong-tenant ID resolves to 404 automatically, with no extra code per route.
- Action-style endpoints that don't map to plain CRUD (`suspend`, `approve`, `status`, `impersonate`) are modeled as a `POST` to a sub-resource verb (`POST /vehicles/{vehicle}` is never used for this; instead `POST /reservations/{reservation}/status`) — this keeps the verb explicit in the URL rather than overloading a generic `PATCH` with implicit side effects.

---

## 3. Authentication

This section states only what is API-surface-specific. Mechanics, session lifecycle, CSRF handshake, and cookie security are fully specified in `03-authorization-and-roles.md` §2 and are not repeated here.

| Concern | API behavior |
|---|---|
| Sanctum mode | Cookie-session, for every `/api/v1/*` and `/api/v1/admin/*` route. `/api/v1/public/*` never requires it. |
| CSRF | Every non-`GET` request under `/api/v1/*`/`/api/v1/admin/*` must carry `X-XSRF-TOKEN` (`03-authorization-and-roles.md` §2.4). `/api/v1/public/*` is exempt — it is stateless and unauthenticated, so there is no session to forge. |
| Cookies | `back-office` requests every endpoint with `credentials: include`; no endpoint in this document accepts or expects an `Authorization` header in the MVP. |
| Future token auth | When Sanctum token mode is enabled (`03-authorization-and-roles.md` §2.10, §15 of this document), the exact same routes below accept `Authorization: Bearer {token}` as an alternative to the session cookie — no endpoint's URL, method, or response shape changes. |

**Auth-specific endpoints** (login/logout/me/password reset) are specified in §5.1.

---

## 4. Endpoint Organization

| Module | Prefix | Resource relationships | Authorization surface |
|---|---|---|---|
| Authentication | `/api/v1/{login,logout,me,password/*}` | — | Public (login/password) or authenticated (`me`, logout) |
| Public Search & Discovery | `/api/v1/public/vehicles`, `/api/v1/public/agencies`, `/api/v1/public/regions`, `/api/v1/public/vehicle-categories` | Vehicle → VehicleCategory, Agency, Region | Public |
| Booking Requests (public submission) | `/api/v1/public/booking-requests` | BookingRequest → Vehicle, Agency | Public (write-only: create) |
| Agency Settings | `/api/v1/agency` | Agency (singleton, current tenant) | Authenticated — `agency.settings.*` |
| Fleet | `/api/v1/vehicles` | Vehicle → Agency, VehicleCategory | Authenticated — `fleet.*` |
| Vehicle Images | `/api/v1/vehicles/{vehicle}/images` | VehicleImage → Vehicle | Authenticated — `fleet.images.manage` |
| Customers | `/api/v1/customers` | Customer → Agency | Authenticated — `customers.*` |
| Reservations | `/api/v1/reservations` | Reservation → Vehicle, Customer, Agency, User (creator) | Authenticated — `reservations.*` |
| Contracts | `/api/v1/reservations/{reservation}/contract` | Contract → Reservation | Authenticated — `contracts.*` |
| Invoices | `/api/v1/invoices` | Invoice → Reservation | Authenticated — `billing.invoices.*` |
| Payments | `/api/v1/invoices/{invoice}/payments` | Payment → Invoice | Authenticated — `billing.payments.record` |
| Booking Requests (back office) | `/api/v1/booking-requests` | BookingRequest → Vehicle, Reservation (nullable) | Authenticated — `booking-requests.*` |
| Employees | `/api/v1/employees` | User (agency-scoped) | Authenticated — `employees.*` |
| Reports | `/api/v1/reports` | Read-model over Reservation/Invoice/Vehicle | Authenticated — `reports.view` |
| Platform Administration | `/api/v1/admin/*` | Agency, Region, VehicleCategory | Platform Admin surface — `platform.*` |

---

## 5. Endpoint Specifications

Every table below lists: **Method · URL · Purpose · Auth · Permission · Policy method**. Validation-rule tables follow for every write endpoint. Response codes use the standard set defined in §10; only codes specific to that endpoint beyond the standard 401/403/404/422/429/500 baseline are called out per-endpoint. A representative JSON request/response pair is given per module rather than per endpoint, per this document's stated focus on implementation detail over repetition.

### 5.1 Authentication

| Method | URL | Purpose | Auth | Permission | Policy |
|---|---|---|---|---|---|
| GET | `/sanctum/csrf-cookie` | Issue CSRF cookie (Sanctum built-in, unversioned) | None | — | — |
| POST | `/api/v1/login` | Authenticate, start session | None | — | — |
| POST | `/api/v1/logout` | End session | Session | — | — |
| GET | `/api/v1/me` | Current user + resolved roles/permissions | Session | — | — |
| POST | `/api/v1/password/forgot` | Send password reset email | None | — | — |
| POST | `/api/v1/password/reset` | Reset password via signed token | None | — | — |

**Validation — `POST /api/v1/login`**

| Field | Rules |
|---|---|
| `email` | required, string, email |
| `password` | required, string |

**Example — `POST /api/v1/login`**

Request:
```json
{ "email": "admin@sunrise-rentals.example", "password": "correct-horse-battery-staple" }
```

Response `200 OK`:
```json
{
  "data": {
    "id": "b3f1...uuid",
    "name": "Amel Haddad",
    "email": "admin@sunrise-rentals.example",
    "agency_id": "9ac2...uuid",
    "roles": ["agency-admin"],
    "permissions": ["fleet.view", "fleet.create", "reservations.view", "..."]
  }
}
```
Response `401 Unauthorized` on bad credentials or `is_active = false` (`03-authorization-and-roles.md` §2.7) — see §10 for the shared error shape.

### 5.2 Public Search & Discovery

| Method | URL | Purpose | Auth |
|---|---|---|---|
| GET | `/api/v1/public/vehicles/search` | Filtered, paginated vehicle search (§8) | None |
| GET | `/api/v1/public/vehicles/{vehicle}` | Vehicle detail page | None |
| GET | `/api/v1/public/agencies/{agency}` | Agency profile page (by `slug` or `id`, see §12.7) | None |
| GET | `/api/v1/public/regions` | List active regions (for filter UI) | None |
| GET | `/api/v1/public/vehicle-categories` | List active categories (for filter UI) | None |

**Example — `GET /api/v1/public/vehicles/search?region=north&category=suv&start_date=2026-08-10&end_date=2026-08-15&page=1`**

Response `200 OK`:
```json
{
  "data": [
    {
      "id": "1a2b...uuid",
      "make": "Toyota",
      "model": "RAV4",
      "year": 2023,
      "transmission": "automatic",
      "fuel_type": "petrol",
      "seats": 5,
      "daily_rate": "42.00",
      "category": { "id": "cat-uuid", "name": "SUV", "slug": "suv" },
      "agency": { "id": "ag-uuid", "name": "Sunrise Rentals", "slug": "sunrise-rentals", "region": { "name": "North", "code": "N" } },
      "primary_image_url": "https://locarion.example/storage/agencies/ag-uuid/vehicles/1a2b/primary.jpg"
    }
  ],
  "meta": { "current_page": 1, "per_page": 15, "total": 8, "last_page": 1 },
  "links": { "first": "...", "last": "...", "prev": null, "next": null }
}
```

### 5.3 Booking Requests — Public Submission

| Method | URL | Purpose | Auth |
|---|---|---|---|
| POST | `/api/v1/public/booking-requests` | Submit a booking inquiry for a specific vehicle | None |

**Validation**

| Field | Rules |
|---|---|
| `vehicle_id` | required, uuid, must reference an existing, `active` vehicle (`exists:vehicles,id`) |
| `customer_name` | required, string, max:150 |
| `customer_email` | nullable, email, max:150 |
| `customer_phone` | required, string, max:30 |
| `requested_start_date` | required, date, after_or_equal:today |
| `requested_end_date` | required, date, after:requested_start_date (`02-database-design.md` §6 Rule 5) |
| `message` | nullable, string, max:1000 |

Response `201 Created` returns the created `BookingRequestResource` (§6.8); `agency_id` is derived server-side from `vehicle_id`'s owning agency (§12.9 — never accepted from the request body).

### 5.4 Agency Settings

| Method | URL | Purpose | Permission | Policy |
|---|---|---|---|---|
| GET | `/api/v1/agency` | View own agency's profile/settings | `agency.settings.view` | `AgencyPolicy::view` |
| PATCH | `/api/v1/agency` | Update own agency's profile/settings | `agency.settings.update` | `AgencyPolicy::update` |

**Validation — `PATCH /api/v1/agency`**

| Field | Rules |
|---|---|
| `name` | sometimes, string, max:150 |
| `contact_email` | sometimes, email, max:150 |
| `contact_phone` | nullable, string, max:30 |
| `address` | nullable, string |
| `description` | nullable, string, max:2000 |
| `logo` | nullable, file, image, max size and MIME rules per §11 |

`region_id`, `status`, and `slug` are **not** editable through this endpoint (`region_id`/`slug` are Super-Admin-managed via `/api/v1/admin/agencies/{agency}`; `status` changes only via the platform admin suspend/reactivate actions, §5.14) — an attempt to include them is silently ignored (not validated, not written), consistent with `agency.settings.update` being scoped to profile/branding fields only.

### 5.5 Fleet

| Method | URL | Purpose | Permission | Policy |
|---|---|---|---|---|
| GET | `/api/v1/vehicles` | List own agency's vehicles (filterable, §8) | `fleet.view` | `VehiclePolicy::viewAny` |
| POST | `/api/v1/vehicles` | Create a vehicle | `fleet.create` | `VehiclePolicy::create` |
| GET | `/api/v1/vehicles/{vehicle}` | View a vehicle | `fleet.view` | `VehiclePolicy::view` |
| PATCH | `/api/v1/vehicles/{vehicle}` | Update a vehicle | `fleet.update` | `VehiclePolicy::update` |
| DELETE | `/api/v1/vehicles/{vehicle}` | Soft-delete a vehicle | `fleet.delete` | `VehiclePolicy::delete` |

**Validation — `POST /api/v1/vehicles` / `PATCH` (fields `sometimes` on PATCH)**

| Field | Rules |
|---|---|
| `vehicle_category_id` | required, uuid, exists:vehicle_categories,id |
| `plate_number` | required, string, max:20, unique per agency (`02-database-design.md` §4.5 Rule 15 — validated as `Rule::unique('vehicles')->where('agency_id', $agencyId)`) |
| `make` | required, string, max:100 |
| `model` | required, string, max:100 |
| `year` | required, integer, min:1990, max: current year + 1 |
| `transmission` | required, in:manual,automatic |
| `fuel_type` | required, in:petrol,diesel,hybrid,electric |
| `seats` | required, integer, min:1, max:20 |
| `daily_rate` | required, numeric, min:0 |
| `status` | sometimes, in:active,maintenance,retired |

Response `201 Created` / `200 OK` returns `VehicleResource` (§6.1). `DELETE` returns `204 No Content` on success.

### 5.6 Vehicle Images

| Method | URL | Purpose | Permission | Policy |
|---|---|---|---|---|
| GET | `/api/v1/vehicles/{vehicle}/images` | List a vehicle's images | `fleet.view` | `VehiclePolicy::view` (parent) |
| POST | `/api/v1/vehicles/{vehicle}/images` | Upload an image (multipart, §11) | `fleet.images.manage` | `VehiclePolicy::update` (parent) |
| PATCH | `/api/v1/vehicles/{vehicle}/images/{image}` | Reorder / set as primary | `fleet.images.manage` | `VehiclePolicy::update` (parent) |
| DELETE | `/api/v1/vehicles/{vehicle}/images/{image}` | Remove an image | `fleet.images.manage` | `VehiclePolicy::update` (parent) |

> **Assumption:** image-specific authorization piggybacks on the parent `VehiclePolicy` (an image has no independent business meaning apart from its Vehicle, `02-database-design.md` §4.6) rather than a separate `VehicleImagePolicy` — one fewer Policy class for a child resource with no independent lifecycle.

**Validation — `POST .../images`**: `file` (required, image, MIME/size rules §11), `is_primary` (sometimes, boolean). **`PATCH .../images/{image}`**: `is_primary` (sometimes, boolean), `sort_order` (sometimes, integer, min:0).

### 5.7 Customers

| Method | URL | Purpose | Permission | Policy |
|---|---|---|---|---|
| GET | `/api/v1/customers` | List own agency's customers | `customers.view` | `CustomerPolicy::viewAny` |
| POST | `/api/v1/customers` | Create a customer record | `customers.create` | `CustomerPolicy::create` |
| GET | `/api/v1/customers/{customer}` | View a customer | `customers.view` | `CustomerPolicy::view` |
| PATCH | `/api/v1/customers/{customer}` | Update a customer / verify document | `customers.update` | `CustomerPolicy::update` |
| DELETE | `/api/v1/customers/{customer}` | Soft-delete a customer | `customers.delete` | `CustomerPolicy::delete` |

**Validation**

| Field | Rules |
|---|---|
| `full_name` | required, string, max:150 |
| `email` | nullable, email, max:150 |
| `phone` | required, string, max:30 |
| `document_type` | required, in:national_id,passport,driver_license |
| `document_number` | required, string, max:50, unique per (`agency_id`, `document_type`) (`02-database-design.md` §4.7 Rule 16) |
| `document_verified` | sometimes, boolean — `true` sets `document_verified_at = now()` server-side; `false`/absent leaves or clears it |

### 5.8 Reservations

| Method | URL | Purpose | Permission | Policy |
|---|---|---|---|---|
| GET | `/api/v1/reservations` | List own agency's reservations (filterable, §8) | `reservations.view` | `ReservationPolicy::viewAny` |
| POST | `/api/v1/reservations` | Create a reservation | `reservations.create` | `ReservationPolicy::create` |
| GET | `/api/v1/reservations/{reservation}` | View a reservation | `reservations.view` | `ReservationPolicy::view` |
| PATCH | `/api/v1/reservations/{reservation}` | Update dates/locations/notes | `reservations.update` | `ReservationPolicy::update` |
| POST | `/api/v1/reservations/{reservation}/status` | Transition status | `reservations.status.update` | `ReservationPolicy::updateStatus` |

No `DELETE` — a Reservation is a business record and is never deleted, only cancelled via the status endpoint (`02-database-design.md` §2.4, §4.8).

**Validation — `POST /api/v1/reservations`**

| Field | Rules |
|---|---|
| `vehicle_id` | required, uuid, exists:vehicles,id (tenant-scoped by the Global Scope, `03-authorization-and-roles.md` §7.2) |
| `customer_id` | required, uuid, exists:customers,id (tenant-scoped) |
| `start_date` | required, date, after_or_equal:today |
| `end_date` | required, date, after:start_date (`02-database-design.md` §6 Rule 4) |
| `pickup_location` | nullable, string, max:255 |
| `dropoff_location` | nullable, string, max:255 |
| `notes` | nullable, string |

Cross-table/business validation performed in the `CreateReservationAction`, not the Form Request (Architecture §3.7): the Vehicle's and Customer's `agency_id` must match the acting user's `agency_id` (already guaranteed by the Global Scope having filtered both lookups, but re-verified per `02-database-design.md` §6 Rule 3), and no overlapping `confirmed`/`active` reservation may exist for the same vehicle (Rule 6) — violating either returns `409 Conflict` (§10), not `422`, since the request is well-formed but conflicts with current state. `daily_rate_snapshot` and `total_price` are computed server-side from the Vehicle's current `daily_rate` and the requested date range — never accepted from the request body.

**Validation — `POST /api/v1/reservations/{reservation}/status`**

| Field | Rules |
|---|---|
| `status` | required, in:pending,confirmed,active,completed,cancelled |

Allowed transitions are enforced in `TransitionReservationStatusAction` (Architecture §3.4): `pending → confirmed → active → completed`, with `cancelled` reachable from `pending`, `confirmed`, or `active`. An invalid transition (e.g., `completed → pending`) returns `409 Conflict`.

**Example — `POST /api/v1/reservations/{id}/status`**

Request: `{ "status": "confirmed" }`
Response `200 OK`: full `ReservationResource` (§6.2) with `"status": "confirmed"`.
Response `409 Conflict`:
```json
{ "message": "This reservation cannot transition from 'completed' to 'confirmed'.", "error_code": "invalid_status_transition" }
```

### 5.9 Contracts

| Method | URL | Purpose | Permission | Policy |
|---|---|---|---|---|
| GET | `/api/v1/reservations/{reservation}/contract` | View contract metadata | `contracts.view` | `ContractPolicy::view` |
| POST | `/api/v1/reservations/{reservation}/contract` | Generate (or regenerate) the contract PDF | `contracts.generate` | `ContractPolicy::create` |
| GET | `/api/v1/contracts/{contract}/download` | Stream the contract PDF file | `contracts.view` | `ContractPolicy::view` |

> **Note:** because the MVP infrastructure processes jobs inline via the `sync` queue driver (`01-system-architecture.md` §9.1, as revised), `POST .../contract` returns **`201 Created`** with the fully generated `ContractResource` (§6.5) in the same response — not `202 Accepted` — since generation completes within the request/response cycle. If a dedicated worker is introduced later (per that same section's documented future note), this endpoint's response code would change to `202 Accepted` with the client polling or receiving a notification; that is a documented future change, not the MVP contract.

**Example — `POST /api/v1/reservations/{id}/contract`**

Response `201 Created`:
```json
{
  "data": {
    "id": "c1c1...uuid",
    "reservation_id": "r1r1...uuid",
    "generated_at": "2026-07-25T14:32:00Z",
    "download_url": "/api/v1/contracts/c1c1.../download"
  }
}
```

### 5.10 Invoices

| Method | URL | Purpose | Permission | Policy |
|---|---|---|---|---|
| GET | `/api/v1/invoices` | List own agency's invoices (filterable, §8) | `billing.invoices.view` | `InvoicePolicy::viewAny` |
| POST | `/api/v1/invoices` | Create a draft invoice for a reservation | `billing.invoices.manage` | `InvoicePolicy::create` |
| GET | `/api/v1/invoices/{invoice}` | View an invoice | `billing.invoices.view` | `InvoicePolicy::view` |
| PATCH | `/api/v1/invoices/{invoice}` | Update a `draft` invoice | `billing.invoices.manage` | `InvoicePolicy::update` |
| POST | `/api/v1/invoices/{invoice}/issue` | Transition `draft → issued` | `billing.invoices.manage` | `InvoicePolicy::update` |
| POST | `/api/v1/invoices/{invoice}/void` | Transition any non-`paid` status `→ void` | `billing.invoices.manage` | `InvoicePolicy::update` |

No `DELETE` — an invoice is never deleted, only voided (`02-database-design.md` §2.4, §4.10).

**Validation — `POST /api/v1/invoices`**

| Field | Rules |
|---|---|
| `reservation_id` | required, uuid, exists:reservations,id (tenant-scoped) |
| `amount` | required, numeric, min:0.01 |
| `due_at` | nullable, date, after_or_equal:today |

`invoice_number` is generated server-side (agency-scoped sequential formatting, e.g. `INV-{agency-short-code}-{sequence}`) — never client-supplied, since uniqueness is enforced per (`agency_id`, `invoice_number`) (`02-database-design.md` §4.10). `PATCH` is only permitted while `status = 'draft'` (`InvoicePolicy::update` returns `false` otherwise, producing `403 Forbidden`); attempting to edit an `issued`/`paid`/`void` invoice is a permission-level rejection, not a validation error, since the record does belong to the requester's agency but the current state disallows the action.

### 5.11 Payments

| Method | URL | Purpose | Permission | Policy |
|---|---|---|---|---|
| GET | `/api/v1/invoices/{invoice}/payments` | List payments recorded against an invoice | `billing.invoices.view` | `PaymentPolicy::viewAny` |
| POST | `/api/v1/invoices/{invoice}/payments` | Record a payment | `billing.payments.record` | `PaymentPolicy::create` |

No `PATCH`/`DELETE` — payments are immutable ledger entries (`02-database-design.md` §4.11).

**Validation — `POST .../payments`**

| Field | Rules |
|---|---|
| `amount` | required, numeric, min:0.01 |
| `method` | required, in:cash,card,bank_transfer,other |
| `reference` | nullable, string, max:100 |
| `paid_at` | required, date, before_or_equal:now |

Business validation in `RecordPaymentAction`: the sum of this invoice's existing payments plus `amount` must not exceed `Invoice.amount` (`02-database-design.md` §6 Rule 14) — violating this returns `409 Conflict`, and successfully recording a payment that brings the sum to exactly `Invoice.amount` automatically transitions the invoice's `status` to `paid` (a business rule executed by the Action, not the client).

### 5.12 Booking Requests (Back Office)

| Method | URL | Purpose | Permission | Policy |
|---|---|---|---|---|
| GET | `/api/v1/booking-requests` | List own agency's booking requests (filterable by `status`) | `booking-requests.view` | `BookingRequestPolicy::viewAny` |
| GET | `/api/v1/booking-requests/{bookingRequest}` | View a request | `booking-requests.view` | `BookingRequestPolicy::view` |
| POST | `/api/v1/booking-requests/{bookingRequest}/approve` | Approve and convert into a Reservation | `booking-requests.approve` | `BookingRequestPolicy::approve` |
| POST | `/api/v1/booking-requests/{bookingRequest}/reject` | Reject the request | `booking-requests.reject` | `BookingRequestPolicy::reject` |

**Validation — `.../approve`**: no body required beyond an optional `notes` (nullable, string) carried onto the created Reservation. The Action creates exactly one `Reservation` (using the request's `vehicle_id`, a `Customer` resolved-or-created from the request's name/email/phone/document-free contact details, and the request's date range), sets `booking_requests.reservation_id`, `reviewed_by_user_id`, `reviewed_at`, and `status = 'approved'` — enforcing "at most once" (`02-database-design.md` §6 Rule 11) by rejecting with `409 Conflict` if `reservation_id` is already set.

> **Assumption:** approving a `BookingRequest` that has no matching `Customer` yet (the visitor was never a known customer) creates a new `Customer` record from the request's contact fields as part of the same Action, since a `Reservation` requires a `customer_id` (`02-database-design.md` §4.8) and the Blueprint's public flow collects only name/email/phone (§6), not a full document — the created `Customer`'s `document_type`/`document_number` are then completed by staff via the normal Customer-update endpoint (§5.7) before/after confirming, consistent with Blueprint §6's "identified by phone/email/document **at booking time**" allowing document capture to happen as part of the staff-side approval workflow rather than the public submission itself.

**Validation — `.../reject`**: `reason` (nullable, string, max:500).

### 5.13 Employees

| Method | URL | Purpose | Permission | Policy |
|---|---|---|---|---|
| GET | `/api/v1/employees` | List own agency's staff (Agency Admin + Employees) | `employees.view` | `UserPolicy::viewAny` |
| POST | `/api/v1/employees` | Create a new Employee account | `employees.create` | `UserPolicy::create` |
| GET | `/api/v1/employees/{user}` | View an employee | `employees.view` | `UserPolicy::view` |
| PATCH | `/api/v1/employees/{user}` | Update an employee's profile | `employees.update` | `UserPolicy::update` |
| POST | `/api/v1/employees/{user}/deactivate` | Set `is_active = false` | `employees.deactivate` | `UserPolicy::deactivate` |
| POST | `/api/v1/employees/{user}/reactivate` | Set `is_active = true` | `employees.deactivate` | `UserPolicy::deactivate` |
| GET | `/api/v1/employees/{user}/permissions` | List an employee's granted permissions | `employees.permissions.manage` | `UserPolicy::managePermissions` |
| PATCH | `/api/v1/employees/{user}/permissions` | Assign/revoke permissions | `employees.permissions.manage` | `UserPolicy::managePermissions` |

**Validation — `POST /api/v1/employees`**

| Field | Rules |
|---|---|
| `name` | required, string, max:150 |
| `email` | required, email, max:150, unique (global — `03-authorization-and-roles.md` §2.6/`02-database-design.md` §4.4 assumption) |
| `password` | required, confirmed, per `Password::defaults()` (`03-authorization-and-roles.md` §10.1) — or an invitation-link flow; see assumption below |

> **Assumption:** the exact account-creation UX (Agency Admin sets an initial password directly vs. an email invitation link the new Employee completes themselves) is not fixed by prior documents. This document specifies the simpler of the two for MVP: Agency Admin sets the account up with an initial password (or the API generates one and returns it once, prompting a forced change on first login — implementation detail for `06-testing-strategy.md`/build time), since an invitation-email flow adds a token/expiry state machine that Blueprint's scope does not otherwise require.

**Validation — `PATCH .../permissions`**: `permissions` (required, array; each item `string`, must exist in the permission catalogue, `03-authorization-and-roles.md` §5) — the endpoint performs a full replace of the employee's individually-granted permission set (idempotent: submitting the same array twice has the same effect), scoped by Spatie's team context to the acting Agency Admin's own agency (`03-authorization-and-roles.md` §4.3).

### 5.14 Reports

| Method | URL | Purpose | Permission | Gate |
|---|---|---|---|---|
| GET | `/api/v1/reports/revenue` | Revenue over a date range (Blueprint §4, §11) | `reports.view` | `view-agency-reports` |
| GET | `/api/v1/reports/utilization` | Fleet utilization over a date range | `reports.view` | `view-agency-reports` |

**Query parameters (both endpoints):** `from` (required, date), `to` (required, date, after_or_equal:from).

**Example — `GET /api/v1/reports/revenue?from=2026-07-01&to=2026-07-31`**

Response `200 OK`:
```json
{
  "data": {
    "from": "2026-07-01",
    "to": "2026-07-31",
    "total_invoiced": "12450.00",
    "total_collected": "10980.00",
    "outstanding": "1470.00",
    "by_day": [{ "date": "2026-07-01", "invoiced": "410.00", "collected": "410.00" }]
  }
}
```

### 5.15 Platform Administration

All routes below sit under `/api/v1/admin/*`, guarded first by the `access-platform-admin` Gate (`03-authorization-and-roles.md` §8.2, §8.6), then by the specific permission listed.

| Method | URL | Purpose | Permission |
|---|---|---|---|
| GET | `/api/v1/admin/agencies` | List all agencies (filter: `status`, `region_id`) | `platform.agencies.manage` |
| POST | `/api/v1/admin/agencies` | Create an agency (+ its first Agency Admin user) | `platform.agencies.manage` |
| GET | `/api/v1/admin/agencies/{agency}` | View an agency | `platform.agencies.manage` |
| PATCH | `/api/v1/admin/agencies/{agency}` | Update `region_id`/`slug`/`name` at the platform level | `platform.agencies.manage` |
| POST | `/api/v1/admin/agencies/{agency}/suspend` | `status → suspended` | `platform.agencies.manage` |
| POST | `/api/v1/admin/agencies/{agency}/reactivate` | `status → active` | `platform.agencies.manage` |
| DELETE | `/api/v1/admin/agencies/{agency}` | Soft-delete an agency | `platform.agencies.manage` |
| POST | `/api/v1/admin/agencies/{agency}/impersonate` | Begin logged impersonation (`03-authorization-and-roles.md` §7.4) | `platform.agencies.impersonate` |
| POST | `/api/v1/admin/impersonation/end` | End impersonation | `platform.agencies.impersonate` |
| GET | `/api/v1/admin/regions` | List regions (incl. inactive) | `platform.regions.manage` |
| POST | `/api/v1/admin/regions` | Create a region | `platform.regions.manage` |
| PATCH | `/api/v1/admin/regions/{region}` | Update / deactivate a region | `platform.regions.manage` |
| GET | `/api/v1/admin/vehicle-categories` | List categories (incl. inactive) | `platform.categories.manage` |
| POST | `/api/v1/admin/vehicle-categories` | Create a category | `platform.categories.manage` |
| PATCH | `/api/v1/admin/vehicle-categories/{category}` | Update / deactivate a category | `platform.categories.manage` |
| GET | `/api/v1/admin/stats` | Platform-wide statistics | `platform.stats.view` |

**Validation — `POST /api/v1/admin/agencies`**

| Field | Rules |
|---|---|
| `name` | required, string, max:150 |
| `slug` | required, string, max:150, unique:agencies,slug |
| `region_id` | required, uuid, exists:regions,id |
| `contact_email` | required, email, max:150 |
| `admin_name` | required, string, max:150 — the first Agency Admin's name |
| `admin_email` | required, email, max:150, unique:users,email — the first Agency Admin's login |

Creating an agency and its first Agency Admin `User` (with `agency-admin` role assigned under that agency's new `team_id`) happens atomically in `CreateAgencyAction`, inside a single database transaction — an agency without at least one Agency Admin would be unreachable by any staff login.

> **Assumption:** Regions and Vehicle Categories have no `DELETE` endpoint, only `PATCH` to set `is_active = false` (`02-database-design.md` §2.4/§6 Rule 9 — hard delete would violate `ON DELETE RESTRICT` while referenced, so the API doesn't expose an operation that would routinely fail).

---

## 6. API Resources

Every Resource wraps a single record as `{"data": {...}}` (collections as `{"data": [...], "meta": {...}, "links": {...}}`, §7). "Hidden" fields are never present in the JSON, regardless of context.

| Resource | Key fields | Hidden fields | Computed fields | Nested resources |
|---|---|---|---|---|
| **6.1 `VehicleResource`** (authenticated) | `id, agency_id, vehicle_category_id, plate_number, make, model, year, transmission, fuel_type, seats, daily_rate, status, created_at, updated_at, deleted_at` | — (staff sees the full record) | — | `category` (`VehicleCategoryResource`), `images` (`VehicleImageResource[]`) |
| **`PublicVehicleResource`** (public) | `id, make, model, year, transmission, fuel_type, seats, daily_rate` | `agency_id, plate_number, status, deleted_at, timestamps` | `primary_image_url` | `category` (name/slug only), `agency` (name/slug/region only) |
| **6.2 `ReservationResource`** | `id, agency_id, vehicle_id, customer_id, created_by_user_id, status, start_date, end_date, pickup_location, dropoff_location, daily_rate_snapshot, total_price, notes, created_at, updated_at` | — | `duration_days` (`end_date − start_date`) | `vehicle` (summary: id/make/model/plate_number), `customer` (summary: id/full_name/phone) — full nested objects only when the client requests `?include=vehicle,customer` (§12.10); otherwise IDs only, to keep list responses lean |
| **6.3 `CustomerResource`** | `id, agency_id, full_name, email, phone, document_type, document_number, document_verified_at, created_at, updated_at` | — | `is_document_verified` (boolean, `document_verified_at !== null`) | — |
| **6.4 `InvoiceResource`** | `id, agency_id, reservation_id, invoice_number, amount, status, issued_at, due_at, created_at, updated_at` | — | `balance_due` (`amount − Σpayments.amount`) | `payments` (`PaymentResource[]`, included when requested) |
| **6.5 `ContractResource`** | `id, agency_id, reservation_id, generated_at, created_at, updated_at` | `file_path` (internal storage path never exposed) | `download_url` (`/api/v1/contracts/{id}/download`) | — |
| **6.6 `PaymentResource`** | `id, agency_id, invoice_id, amount, method, reference, paid_at, created_at` | — | — | — |
| **6.7 `AgencyResource`** (authenticated, `/api/v1/agency`) | `id, region_id, name, slug, status, contact_email, contact_phone, address, logo_path, description, created_at, updated_at` | — | `logo_url` (public URL derived from `logo_path`) | `region` (`RegionResource`) |
| **`PublicAgencyResource`** (public) | `name, slug, contact_email, contact_phone, address, description` | `id` (uses `slug` as the public identifier, §12.7), `status, region_id, timestamps` | `logo_url` | `region` (name only), `vehicles` (paginated `PublicVehicleResource[]`, this agency's active fleet) |
| **6.8 `BookingRequestResource`** | `id, agency_id, vehicle_id, customer_name, customer_email, customer_phone, requested_start_date, requested_end_date, message, status, reservation_id, reviewed_by_user_id, reviewed_at, created_at` | — | — | `vehicle` (summary) |
| **6.9 `UserResource`** (Employees module) | `id, agency_id, name, email, is_active, created_at, updated_at` | `password, remember_token, deleted_at` | `roles` (array of role names), `permissions` (array of granted permission strings, own-team-scoped) | — |
| **6.10 `ReportResource`** | Shape varies per report type (§5.14) — not a single fixed field set, since Reporting has no dedicated table (`02-database-design.md` §3 note) | — | Every field in a `ReportResource` is, by definition, computed/aggregated | — |

> **Assumption:** the split between an authenticated Resource and a `Public*` variant (Vehicle, Agency) is this document's proposal for keeping internal fields (plate numbers, internal status values, IDs where a slug suffices) out of anonymous responses — Architecture §3.1/§8 does not mandate this split explicitly but does establish that API Resources exist precisely to control response shape per Blueprint §11's two distinct route surfaces; two Resource classes per model, sharing the same underlying Eloquent model, is the natural implementation of that already-established intent.

---

## 7. Pagination

### 7.1 Format

Every collection endpoint returns Laravel's default paginated-resource shape:

```json
{
  "data": [ ... ],
  "links": { "first": "https://.../vehicles?page=1", "last": "https://.../vehicles?page=5", "prev": null, "next": "https://.../vehicles?page=2" },
  "meta": { "current_page": 1, "from": 1, "last_page": 5, "per_page": 15, "to": 15, "total": 68 }
}
```

### 7.2 Parameters

`page` (default `1`), `per_page` (default `15`, max `100` — a request for more is clamped, not rejected).

### 7.3 Sorting

Back-office list endpoints accept `sort_by` (a whitelisted column per resource, e.g. `created_at`, `start_date`, `daily_rate`) and `sort_dir` (`asc`|`desc`, default `desc`). An unrecognized `sort_by` value is ignored (falls back to the endpoint's default), not a `422` — sorting is a refinement, not a required contract.

### 7.4 Default Ordering

| Resource | Default order |
|---|---|
| Vehicles, Customers, Employees | `created_at desc` (newest first) |
| Reservations, Invoices, Booking Requests | `created_at desc` |
| Public vehicle search | `daily_rate asc` (cheapest first) |

> **Assumption:** "cheapest first" is a reasonable default for public search absent any stated ranking requirement; it is trivially overridden by `sort` (§8.5).

### 7.5 Cursor vs. Offset — Why Offset

**Offset pagination** (Laravel's standard `paginate()`) is used everywhere, per Architecture §11's explicit statement that cursor pagination "solves a deep-offset performance problem this platform's expected table sizes do not yet have." This document does not revisit that call — every endpoint above uses offset pagination uniformly, with no per-endpoint exceptions.

---

## 8. Filtering & Search

### 8.1 Query Parameter Convention

Filters are plain query-string parameters, not a POST-based "search" body — keeps every search/list request cacheable and bookmarkable (relevant especially for public search, Blueprint §7's "cache-friendly" requirement) and consistent with REST's resource-retrieval-via-`GET` convention.

### 8.2 Public Vehicle Search Parameters

| Parameter | Type | Effect |
|---|---|---|
| `region` | region `code` | Filter to agencies in this region |
| `category` | category `slug` | Filter to this vehicle category |
| `transmission` | `manual`\|`automatic` | Exact match |
| `fuel_type` | `petrol`\|`diesel`\|`hybrid`\|`electric` | Exact match |
| `seats_min` | integer | `seats >= value` |
| `price_max` | numeric | `daily_rate <= value` |
| `start_date` / `end_date` | date | Availability filter — excludes vehicles with an overlapping `confirmed`/`active` reservation (`02-database-design.md` §6 Rule 6); both or neither must be present |
| `sort` | `price_asc`\|`price_desc`\|`newest` | See §7.4 for default |

### 8.3 Category & Region Filters (Back Office)

`/api/v1/vehicles` accepts `category_id`, `status` for the equivalent agency-scoped fleet-list filtering.

### 8.4 Status Filters

`/api/v1/reservations`, `/api/v1/invoices`, `/api/v1/booking-requests` each accept `status` (matching that resource's own enum, §5), for the common "show me only pending/active/etc." back-office view.

### 8.5 Date Filtering

`/api/v1/reservations` additionally accepts `start_date_from`/`start_date_to` for filtering by reservation window, and `/api/v1/reports/*` require `from`/`to` (§5.14) rather than defaulting silently to "all time," to keep report queries bounded (Architecture §11's performance principle).

### 8.6 Example URLs

```
GET /api/v1/public/vehicles/search?region=north&category=suv&seats_min=5&start_date=2026-08-10&end_date=2026-08-15&sort=price_asc
GET /api/v1/reservations?status=confirmed&start_date_from=2026-08-01&sort_by=start_date&sort_dir=asc
GET /api/v1/invoices?status=issued&per_page=25
```

---

## 9. Validation

### 9.1 Strategy

Form Requests validate input shape; Actions validate state-dependent business rules; the database is the last-resort backstop — this three-layer strategy is fixed by Architecture §3.7/§10.3 and not re-litigated here. This document's contribution is the concrete rule table per endpoint (§5).

### 9.2 The 422 Format

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "end_date": ["The end date must be a date after start date."],
    "vehicle_id": ["The selected vehicle id is invalid."]
  }
}
```

One entry per invalid field, each an array of one or more human-readable messages (Laravel's default `ValidationException` rendering — used as-is, not customized, per the "boring technology" principle).

### 9.3 Reusable Validation

Rules that repeat across endpoints (date-range ordering, per-agency uniqueness) are implemented as **custom Rule classes** (e.g., `EndDateAfterStartDate`, `UniquePerAgency`) living alongside the Form Requests that use them (Architecture §3.1's `Domain/{Context}/Requests/`), rather than copy-pasted closures — one definition, referenced everywhere the same shape of check applies.

### 9.4 Business & Cross-Table Validation

Restated pointer, not repeated: every case in §5 marked "validated in the Action" (reservation overlap, payment-sum-vs-invoice-amount, agency-ownership consistency) follows `02-database-design.md` §6 and §2.8's principle 3 exactly — these are never expressed as Form Request rules, because they require querying current database state, which a Form Request's synchronous, request-scoped validation is not the right layer for.

---

## 10. Error Handling

### 10.1 Standard Envelope

Every non-2xx response shares this shape:

```json
{
  "message": "Human-readable summary.",
  "error_code": "machine_readable_snake_case_string",
  "errors": { "field": ["..."] }
}
```

`errors` is present **only** on `422`. `error_code` is present on every error response and is the value frontend code should branch on (`packages/ui`'s API client, Architecture §4.5) — `message` is for display/logging, never for program logic, since its wording may change without notice.

> **Assumption:** `error_code` is this document's addition on top of Laravel's bare default error shape (which is only `message`/`errors`) — a small, low-risk enrichment that gives the shared frontend client something stable to switch on (e.g., distinguishing `invalid_status_transition` from a generic `409`) without parsing `message` strings, consistent with Architecture §8.3's deferral of "exact JSON shape" to this document.

### 10.2 Status Code Reference

| Code | Meaning | Example `error_code` | Example scenario |
|---|---|---|---|
| `200` | Success (read, or write with a body) | — | `GET /vehicles/{id}` |
| `201` | Resource created | — | `POST /vehicles` |
| `204` | Success, no body | — | `DELETE /vehicles/{id}` |
| `400` | Malformed request (unparseable JSON, wrong content-type) | `malformed_request` | Invalid JSON body |
| `401` | Not authenticated | `unauthenticated` | No/expired session |
| `403` | Authenticated, but not permitted (same-tenant record, missing permission or disallowed state) | `forbidden` | Employee without `billing.invoices.manage` |
| `404` | Not found — including any cross-tenant record (`03-authorization-and-roles.md` §7.5) | `not_found` | Wrong-agency vehicle ID |
| `409` | Well-formed request, conflicts with current state | `invalid_status_transition`, `overlapping_reservation`, `payment_exceeds_balance`, `already_converted` | Double-booking attempt |
| `422` | Validation failure | `validation_failed` | Missing required field |
| `429` | Rate limit exceeded | `rate_limited` | Too many login attempts |
| `500` | Unhandled server error — no internal detail leaked (Architecture §8.3) | `server_error` | Uncaught exception |

### 10.3 Example — `404` (Cross-Tenant)

```json
{ "message": "The requested resource could not be found.", "error_code": "not_found" }
```

### 10.4 Example — `429`

```json
{ "message": "Too many login attempts. Please try again in 47 seconds.", "error_code": "rate_limited" }
```

---

## 11. File Uploads

### 11.1 Vehicle Images

`POST /api/v1/vehicles/{vehicle}/images` — `multipart/form-data`, field `file`.

| Rule | Value |
|---|---|
| Max size | 5 MB |
| Allowed MIME types | `image/jpeg`, `image/png`, `image/webp` |
| Storage path | `agencies/{agency_id}/vehicles/{vehicle_id}/{uuid}.{ext}` (local disk, Architecture §7.1's tenant-namespaced convention) |
| Response | `201 Created` + `VehicleImageResource` (`id, vehicle_id, path` → served as `url`, `is_primary, sort_order`) |

> **Assumption:** the 5 MB / JPEG-PNG-WebP limits are this document's proposal, since no prior document specifies image constraints — chosen as conventional, unremarkable defaults for vehicle listing photos, easily adjusted via a single config value.

### 11.2 Contract PDFs

Never uploaded by a client — always server-generated (§5.9). Served only through the authenticated, Policy-checked `GET /api/v1/contracts/{contract}/download` route (Architecture §7.1's explicit rule: files are never directly web-accessible), which streams the file with `Content-Type: application/pdf` and `Content-Disposition: attachment`.

### 11.3 Future Cloud Storage Compatibility

Both upload and download endpoints above go through Laravel's `Storage` facade (Architecture §7.2) — migrating the underlying disk to S3-compatible storage later changes zero routes, request shapes, or response shapes in this document; only the `logo_url`/`primary_image_url`/`download_url` computed fields' underlying resolution logic changes (from a local-disk URL to a signed cloud URL), transparently to the client.

---

## 12. API Conventions

| Convention | Rule |
|---|---|
| **Naming** | `snake_case` for all JSON keys, matching the database column names (`02-database-design.md` §2.6) — no camelCase translation layer |
| **Plural resources** | Collections are plural nouns (`/vehicles`); a singleton is a singular noun (`/agency`, `/me`) |
| **Nested resources** | Used only where the child has no independent existence apart from its parent (`vehicles/{v}/images`, `invoices/{i}/payments`, `reservations/{r}/contract`) — everything else is a top-level resource, even if it always happens to relate to one parent (e.g., `/invoices` is top-level, filterable by `reservation_id`, rather than nested under `/reservations/{r}/invoices`, since an agency's invoice list as a whole is itself a common view, per §5.14/§8.4) |
| **HTTP verbs** | `GET` read, `POST` create or non-CRUD action, `PATCH` partial update, `DELETE` soft-delete (where it exists at all, §12.6) |
| **Idempotency** | `GET`/`PATCH`/`DELETE` are idempotent by definition; `POST` is not, except status/action endpoints (`POST .../status`, `.../approve`) which are written to be safely retryable (re-posting the same target status, or re-approving an already-approved request, is rejected with `409`, not a duplicate side effect) |
| **PATCH vs. PUT** | `PATCH` exclusively — no endpoint requires a full-resource replacement, so `PUT`'s all-fields-required semantics are never needed |
| **DELETE behavior** | Present only for entities with a `deleted_at` column (`02-database-design.md` §2.4): Vehicle, VehicleImage (hard, §2.5), Customer, Agency (platform-level only). Absent entirely for Reservation, Invoice, Payment, BookingRequest — their lifecycle is status-only |
| **UUID usage** | Every route parameter and every `id` field is a UUID string (`02-database-design.md` §2.2) — never a sequential integer |
| **Date formats** | Date-only fields (`start_date`, `requested_start_date`) as `YYYY-MM-DD`; timestamps as ISO 8601 UTC (`YYYY-MM-DDTHH:mm:ssZ`), per `01-system-architecture.md` §10.7 |
| **Boolean formats** | JSON `true`/`false` — never `1`/`0` or `"yes"`/`"no"` |
| **Money formatting** | Every monetary field (`daily_rate`, `amount`, `total_price`, `balance_due`, ...) is serialized as a **string** with exactly two decimal places (e.g., `"42.00"`), never a JSON number — avoids floating-point misrepresentation of a `numeric(10,2)` value across the JSON boundary; the frontend's shared money-formatting utility (`packages/ui`) is the single place this string is parsed for display/arithmetic |
| **Null handling** | Nullable fields are always present in the response with an explicit `null`, never omitted — keeps the shape predictable for the generated/hand-maintained TypeScript types (`01-system-architecture.md` §4.3, §8.4) |

---

## 13. Rate Limiting

Restated as concrete, configured policy — mechanism already fixed by `01-system-architecture.md` §9.2 and `03-authorization-and-roles.md` §10.2.

| Surface | Limit | Key |
|---|---|---|
| `/api/v1/public/*` (general) | 60 requests / minute | Per IP |
| `/api/v1/public/booking-requests` (`POST` specifically) | 10 requests / hour | Per IP |
| `/api/v1/login` | 5 attempts / minute | Per `email + IP` (`03-authorization-and-roles.md` §10.2) |
| `/api/v1/*`, `/api/v1/admin/*` (authenticated, general) | 120 requests / minute | Per authenticated user |
| Future API tokens (§15) | Per-token limit, defaulting to the same 120/minute unless a partner agreement specifies otherwise | Per token |

> **Assumption:** the specific numeric ceilings above (60/min, 10/hour, 120/min) are this document's proposal — `01`/`03` established that public routes are throttled "more aggressively" than authenticated ones and that login has its own stricter policy, without fixing exact numbers. These are reasonable, easily-tuned starting values (a single config change each), not a structural commitment.

---

## 14. OpenAPI Strategy

Per `01-system-architecture.md` §8.4, OpenAPI-based automatic TypeScript generation is **intentionally deferred until after the API surface is stable** — this document, not a generated schema, is the source of truth for the MVP. In the interim:

- This document is kept in sync with the implementation by convention: any endpoint, validation rule, or response shape change made in code during MVP development must be reflected here in the same pull request (mirroring the manual-sync discipline `01-system-architecture.md` §8.4 already specifies for `packages/ui`'s hand-maintained TypeScript types).
- Once OpenAPI generation is introduced post-MVP, the schema will be generated **from the actual API Resources and Form Requests** (`01-system-architecture.md` §8.4) — the tables in §5/§6 of this document are written to map directly onto that eventual generation (one row per field, explicit nullability, explicit types) specifically so the transition requires no restructuring of this document's content, only a change in how it's produced.

---

## 15. Future Evolution

| Future item | API impact — additive only |
|---|---|
| **Customer accounts** | A new, separate route group (`/api/v1/customer/*` or similar) and a new Sanctum guard (`03-authorization-and-roles.md` §11); zero change to any route in §5 of this document |
| **Native mobile app** | Same routes, same Resources, same validation — only the auth header changes from cookie to Bearer token (§3, `03-authorization-and-roles.md` §2.10) |
| **OAuth (post Customer accounts)** | New login-adjacent endpoints (`/api/v1/auth/oauth/{provider}/redirect`, `/callback`); produces the same authenticated identity every other endpoint already expects |
| **Public integrations / Partner APIs** | Sanctum token abilities (`03-authorization-and-roles.md` §11) scope a partner token to a subset of existing endpoints — no new endpoints required unless a partner needs a bulk/webhook-style surface, which would be additive `/api/v1/partners/*` routes, not a change to existing ones |
| **GraphQL** | Would sit **alongside** REST as an additional query surface over the same Actions/Resources (Architecture §8.1 chose REST over GraphQL for this domain's caching/tooling story) — not a replacement; every Action in this document remains the single business-logic entry point either surface would call into |
| **v2 evolution** | Any breaking change to a contract fixed in this document ships as `/api/v2/...` (§1.4) — `v1` continues serving existing clients (initially just the two first-party SPAs, later possibly partners) unchanged until they migrate |

---

## 16. API ADRs

Prefixed `ADR-API` to distinguish from `01`'s `ADR-01..16`, `02`'s `ADR-DB-01..12`, and `03`'s `ADR-AUTH-01..12`.

| # | Decision | Rationale | Source |
|---|---|---|---|
| ADR-API-01 | REST over GraphQL | Restated, not re-decided — simpler caching/tooling story for this domain | Architecture §8.1 (ADR carried forward, not new) |
| ADR-API-02 | URL-path versioning (`/api/v1/...`), breaking changes only via a new version prefix | Lets existing clients keep working indefinitely; a single, unambiguous compatibility boundary | Blueprint §11; Architecture §8.2 |
| ADR-API-03 | Plural resource nouns; nested routes only for children with no independent existence | Predictable URL shape; avoids deep, artificial nesting (e.g., invoices stay top-level) | This document §12.2 |
| ADR-API-04 | Session (Sanctum cookie) authentication for both first-party SPAs in the MVP; token mode deferred, not removed | Restated from `03-authorization-and-roles.md` ADR-AUTH-01 — this document only confirms no endpoint requires a different mechanism | `03-authorization-and-roles.md` ADR-AUTH-01 |
| ADR-API-05 | Offset pagination everywhere | Restated from Architecture §11 — matches current table-size expectations; cursor pagination solves a problem this platform doesn't yet have | Architecture §11 |
| ADR-API-06 | Filtering via plain query parameters, never a POST "search" body | Keeps every list/search request cacheable and bookmarkable, especially for public search | This document §8.1 |
| ADR-API-07 | Three-layer validation (Form Request → Action → DB constraint), state-dependent rules always in Actions | Restated from Architecture §3.7/§10.3; this document only supplies the per-endpoint rule tables | Architecture §3.7 |
| ADR-API-08 | Standardized error envelope with an added `error_code` field on every error response | Gives the shared frontend client a stable value to branch on without parsing `message` text | This document §10.1 |
| ADR-API-09 | UUIDs in every route parameter and `id` field | Restated from `02-database-design.md` ADR-DB-02 — no endpoint introduces a sequential identifier anywhere | `02-database-design.md` ADR-DB-02 |
| ADR-API-10 | Separate `Public*` and authenticated Resource classes for Vehicle and Agency | Keeps internal fields (plate numbers, internal status values) out of anonymous responses without branching logic inside a single Resource class | This document §6 |
| ADR-API-11 | OpenAPI schema generation deferred until after the API stabilizes; this document is the interim source of truth | Restated from Architecture §8.4 — avoids a code-generation pipeline before the contract has settled | Architecture §8.4 |
| ADR-API-12 | Money fields serialized as fixed-2-decimal strings, never JSON numbers | Prevents floating-point misrepresentation of `numeric(10,2)` values across the JSON boundary | This document §12 |
| ADR-API-13 | No `DELETE` endpoint for Reservation, Invoice, Payment, or BookingRequest | These are business/financial records; their lifecycle is expressed entirely through status transitions, never deletion | `02-database-design.md` §2.4, ADR-DB-03 |
| ADR-API-14 | `409 Conflict`, not `422`, for well-formed requests that violate current-state business rules (overlapping reservation, invalid status transition, payment exceeding balance) | `422` means "the request itself is malformed"; these requests are perfectly valid in shape but conflict with existing data — a distinct, more accurate signal for the client | This document §5.8, §5.10, §5.11 |

---

*This document expands `01-system-architecture.md`, `02-database-design.md`, and `03-authorization-and-roles.md` and introduces no endpoint, resource, or contract decision those documents do not already imply, except where explicitly marked `> Assumption:`. Those assumptions are proposals, not settled fact, and should be confirmed or corrected before the next document (`05-cicd-and-deployment.md`) is written.*

**Awaiting confirmation before proceeding to `05-cicd-and-deployment.md`.**
