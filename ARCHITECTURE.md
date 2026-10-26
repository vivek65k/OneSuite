> **Note (2026-10-17):** this is the early planning document and no longer matches the code. The app runs on a single shared MySQL database with `company_id` per client, not database-per-tenant (stancl/tenancy is installed but unused), and the main interface is the custom Bootstrap admin, not Filament. See README.md for installing, moving and running OneSuite.

# OneSuite — SaaS ERP Architecture

Stack as installed: Laravel 13 (PHP 8.3), Filament 3.2, `nwidart/laravel-modules` 3.x, `stancl/tenancy` 3.10.

## 1. Guiding Principles

- **Modular monolith, not microservices.** At your stage, microservices add operational overhead (deployment, networking, distributed transactions) without paying for itself yet. A well-modularized Laravel monolith can scale to hundreds of tenants and later be split into services if a specific module (e.g. reporting/analytics) genuinely needs independent scaling.
- **Tenant isolation is a first-class concern**, not an afterthought bolted on later.
- **Every module is self-contained** — its own models, migrations, routes, Filament resources, policies, tests — so the codebase stays navigable as it grows past 10+ business domains.
- **Boring technology where it doesn't matter, strong opinions where it does** (tenancy, queues, auth, money handling).

## 2. Multi-Tenancy Strategy

### Database-per-tenant (recommended)

Using `stancl/tenancy` in multi-database mode:

- Each tenant (company/organization) gets its own MySQL database, e.g. `tenant_acme`, `tenant_globex`.
- One **central database** holds: tenant registry, domains, billing/subscription info, enabled-module list per tenant, and cross-tenant admin data.
- Tenant identification via subdomain (`acme.onesuite.app`) or custom domain (`erp.acme.com`) — `stancl/tenancy` supports both simultaneously via `InitializeTenancyByDomain` / `InitializeTenancyByRequestData`.

**Why database-per-tenant for an ERP specifically:**

- ERPs hold financial/HR data — clients will ask about data isolation, and "separate database" is a much easier answer than "shared table with `tenant_id`."
- Per-tenant backup/restore/export is trivial (`mysqldump tenant_acme`).
- No risk of a missing `WHERE tenant_id = ?` leaking data across tenants — a single forgotten global scope in a shared-DB model is a real, recurring source of SaaS data breaches.
- Per-tenant migrations can be run independently — useful when a specific client needs a hotfix or is on a delayed rollout.

**Trade-offs to accept:**

- Cross-tenant reporting (e.g. your own internal "total revenue across all tenants" dashboard) requires querying the central DB or a separate aggregation pipeline — not a simple SQL join.
- More DB connections to manage at scale (mitigated with connection pooling / read replicas later).
- Migrations must run per-tenant (`php artisan tenants:migrate`), which takes longer as tenant count grows — but `stancl/tenancy` handles this with queued/parallel migration runs.

### Central vs. tenant data split

| Lives in **central DB** | Lives in **tenant DB** |
|---|---|
| `tenants`, `domains` | All business data: customers, invoices, inventory, GL entries, employees, payroll runs, etc. |
| `plans`, `subscriptions`, `invoices` (SaaS billing, not ERP invoicing) | Tenant's own users (`users` table lives per-tenant so employee accounts are isolated with the company data) |
| `tenant_module` pivot (which ERP modules a tenant has enabled) | Tenant-level settings, roles/permissions, audit log |
| Platform admin users (your internal team) | Tenant file uploads (see §10) |
| Global feature flags / plan limits | Notifications, queued jobs specific to that tenant's data |

Rule of thumb: **if it's about the tenant, it's central; if it's the tenant's data, it's per-tenant DB.**

## 3. Module Architecture (`nwidart/laravel-modules`)

Each business domain is a self-contained module under `Modules/`, not scattered across `app/`. Standard skeleton per module:

```
Modules/
  Inventory/
    app/
      Models/
      Http/Controllers/
      Filament/Resources/        # Filament resources scoped to this module
      Policies/
      Services/                  # domain/business logic, kept out of controllers
      Events/ Listeners/
      Jobs/
    database/
      migrations/                # runs against the TENANT connection
      factories/
      seeders/
    routes/
      web.php
      api.php
    resources/views/
    tests/
      Feature/
      Unit/
    module.json                  # module metadata, dependency declarations
```

**Rules that keep this from rotting as you pass 10+ modules:**

- Modules may depend on a small set of shared **Core**/**Platform** modules (auth, settings, notifications) but should **not** depend on each other directly. Cross-module interaction goes through events (`InventoryStockAdjusted`) or explicit facades/contracts published by the producing module — never reaching into another module's Eloquent models directly.
- Each module owns its migrations and only its migrations run in that module's namespace — this is what lets you enable/disable a module per tenant later.
- Filament resources live inside the module (`Modules/Inventory/app/Filament/Resources/`), registered into the appropriate panel (see §8) via the module's service provider, not hand-wired centrally.
- A module's `module.json` declares dependencies explicitly (`"requires": ["Core", "Accounting"]`) so `nwidart/laravel-modules` can refuse to enable a module whose prerequisites are missing for a tenant.

## 4. Module Catalog

Suggested initial module set for an ERP, roughly in build order:

1. **Core / Platform** — tenancy bootstrap, settings, notifications, audit log, activity feed, file manager. Every other module depends on this.
2. **Identity** — tenant users, roles & permissions (`spatie/laravel-permission`, scoped per tenant DB), teams/departments, invitations, SSO (later).
3. **CRM** — leads, contacts, companies, pipelines/deals.
4. **Sales** — quotes, sales orders, contracts.
5. **Inventory / Warehouse** — items, stock levels, warehouses/bins, stock movements, valuation.
6. **Purchasing** — purchase requisitions, POs, vendor bills, supplier management.
7. **Accounting / Finance (GL)** — chart of accounts, journal entries, AR/AP, tax, financial statements. Other modules (Sales, Purchasing, Inventory) post into this via events rather than writing GL rows directly.
8. **HR & Payroll** — employees, attendance, leave, payroll runs, statutory compliance (jurisdiction-specific — plan for pluggable compliance rules per country).
9. **Projects & Timesheets** — projects, tasks, time tracking, billable hours feeding into Sales/Accounting.
10. **Manufacturing** (if in scope) — BOMs, work orders, production planning, consuming Inventory.
11. **POS** (if in scope) — retail checkout, consuming Inventory + Accounting.
12. **Reporting / BI** — cross-module dashboards within a tenant. This is the module most likely to eventually need to be pulled out of the monolith (see §17) since heavy aggregate queries can compete with OLTP workload.
13. **Support / Helpdesk** (optional) — tickets, SLAs.

Each module is **independently enable/disable-able per tenant** via the `tenant_module` pivot in the central DB, gated by the tenant's subscription plan (see §6).

## 5. Identity, Auth & Authorization

- **Central-side auth**: your own platform admin panel (impersonation, tenant provisioning, billing) is a separate Filament panel with its own guard, backed by the central DB. It must never share a session or guard with tenant panels.
- **Tenant-side auth**: each tenant's `users` table and sessions live in that tenant's database — a user in `tenant_acme` cannot authenticate against `tenant_globex` even if credentials collided, because the connection itself is scoped before auth runs (`stancl/tenancy`'s `InitializeTenancyByDomain` middleware runs before `auth`).
- **Authorization**: `spatie/laravel-permission` installed per tenant (its tables live in the tenant DB, so roles/permissions don't leak across tenants). Layer Filament's own resource-level `Policy` classes on top for UI-level gating.
- **Impersonation**: platform admins should be able to impersonate a tenant user for support, via a signed, time-boxed, audit-logged action — never by knowing/reusing the tenant user's real credentials.
- **2FA** on both central admin accounts and tenant accounts from day one (financial/HR data). SSO/SAML/OIDC is a later, enterprise-tier addition — design the `Identity` module's login flow to allow swapping the guard/provider without touching other modules.

## 6. Module Activation & Billing

Central DB has:

```
plans            (id, name, price, billing_interval, module_ids[])
subscriptions    (tenant_id, plan_id, status, trial_ends_at, ...)
tenant_module    (tenant_id, module_key, enabled_at, enabled_by)
```

- Plans bundle modules (e.g. "Starter" = Core + Identity + CRM + Sales; "Enterprise" = everything). Tenants can also purchase individual modules à la carte if that's the pricing model.
- On tenant creation, provisioning job: create tenant DB → run only the migrations for that tenant's enabled modules → seed defaults → send welcome email. Enabling a module later runs that module's migrations against the existing tenant DB as an incremental step — this is why "each module owns only its own migrations" (§3) matters operationally, not just architecturally.
- Billing itself (charging the tenant for OneSuite subscription) uses Laravel Cashier (Stripe) against the **central** DB — don't confuse this with the ERP's own Accounting module, which manages the tenant's own books.

## 7. Admin / UI Layer (Filament)

Two distinct Filament panels sharing the same codebase:

- **Central panel** (`/admin`, central DB guard): platform team only — tenant management, plan/module assignment, impersonation, platform-wide metrics, support tools.
- **Tenant panel** (`{tenant}.onesuite.app/app`, tenant DB guard, tenancy middleware applied): the actual ERP UI tenant users log into. Each module registers its Filament resources into this panel conditionally, based on which modules are enabled for the current tenant (`tenant_module` lookup at panel boot, not just hidden nav — enforce at the route/policy level too).
- If a customer-facing portal (e.g. clients viewing their own invoices) is ever needed, that's a **third**, even more restricted panel — don't bolt it onto the tenant panel with extra role checks.

## 8. Background Processing & Queues

- Use Redis-backed queues (swap out the current `QUEUE_CONNECTION=database` before going to production — the DB queue driver won't hold up under multi-tenant load).
- `stancl/tenancy`'s queue tenancy bootstrapper tags every queued job with its originating tenant so a worker correctly re-initializes tenancy (i.e., connects to the right tenant DB) before executing the job. Never queue a job without going through this — a mis-scoped job is a silent cross-tenant data bug.
- Run **Laravel Horizon** for queue visibility/monitoring once past the prototype stage; separate queue names per concern (`default`, `reports`, `imports`, `emails`) so a slow tenant report export doesn't starve transactional emails for everyone.
- Heavy/long-running work (payroll runs, large imports, PDF generation, report exports) always goes through queued jobs — never inline in a web request.

## 9. Caching, Sessions & Storage Isolation

- **Cache**: prefix cache keys per tenant (`stancl/tenancy`'s cache tagging bootstrapper handles this automatically) so cached data never crosses tenant boundaries even on a shared Redis instance.
- **Sessions**: tenant sessions are scoped by domain/subdomain already; still isolate the session driver's storage the same way as cache.
- **File storage**: use per-tenant disk roots, e.g. S3 with a `tenants/{tenant_id}/...` prefix or a per-tenant bucket for larger clients on higher tiers. Never let a signed URL or public disk path leak the tenant ID in a guessable, un-authorized way — files still need policy checks, not just prefix obscurity.

## 10. Search

For modules needing full-text/faceted search (CRM contacts, Inventory items, Support tickets) at scale, use Laravel Scout with a per-tenant index namespace (Meilisearch or Typesense — both self-hostable, unlike Algolia, which matters once you're paying per-tenant index costs). Don't reach for this until a module actually needs it; Eloquent + proper indexes cover most ERP list/filter screens.

## 11. API Layer

- Laravel Sanctum for first-party API auth (future mobile apps, SPA if you ever split the frontend out of Filament/Livewire).
- Public/partner integrations (accounting sync, e-commerce connectors) get versioned REST endpoints per module (`Modules/Inventory/routes/api.php` → `/api/v1/inventory/...`), tenant-scoped the same way web routes are, with per-integration API tokens and rate limiting.
- Don't design a GraphQL layer preemptively — add it only if a specific integration partner needs it.

## 12. Money, Numbers & Locale Conventions

- Store all monetary values as **integers in the minor unit** (cents) or use a dedicated value object (`akaunting/laravel-money` or similar) — never floats.
- Every tenant/company can operate in its own base currency; multi-currency transactions (foreign vendor bills, etc.) need an explicit exchange-rate-at-transaction-time record, not a live-converted display value, for audit correctness.
- Timezones: store all timestamps in UTC; display in tenant/user timezone. Fiscal year start is a per-tenant setting the Accounting module must respect everywhere (reports, period close), not hardcode to calendar year.

## 13. Audit Trail & Compliance

- A **Core** module service (e.g. `spatie/laravel-activitylog`, tenant-scoped) records who changed what, when, across all modules — required for ERP financial/HR data regardless of jurisdiction.
- Soft-deletes by default on business records (never hard-delete an invoice or payroll run); hard-delete only for genuinely disposable data (draft notifications, temp import staging rows).
- Plan for jurisdiction-specific compliance hooks (data residency, statutory payroll rules, tax formats) as pluggable strategies inside the relevant module rather than `if ($country === 'IN')` scattered through business logic.

## 14. Observability & Ops

- Centralized error tracking (Sentry or similar) tagged with `tenant_id` on every event, so you can tell which tenant a production error came from without grepping logs.
- Structured logging with tenant context injected automatically (a log processor/middleware, not manual `Log::info("tenant: $id ...")` calls scattered everywhere).
- Track per-tenant resource usage (DB size, queue volume, storage) early — you'll need it both for capacity planning and for usage-based billing tiers later.

## 15. Testing Strategy

- Pest, per module (`Modules/Inventory/tests/...`), run against a throwaway tenant DB created and torn down per test run — `stancl/tenancy` ships test helpers for exactly this (`Tenancy::initialize()` inside a test tenant).
- A shared `Core` test harness/trait handles tenant creation + module enabling boilerplate so each module's tests aren't reinventing tenant setup.
- Cross-module integration tests (e.g. "a Sales invoice posts correctly to Accounting") live in a dedicated `tests/Integration` suite, since they legitimately span module boundaries and shouldn't live inside either module's own test folder.

## 16. Environments & Deployment

- Central DB + Redis + queue workers + a horizontally-scaled app tier (stateless, so any web node can serve any tenant based on domain resolution).
- Tenant migrations run as a deploy step (`php artisan tenants:migrate`), queued/parallelized across tenants — budget for this taking meaningfully longer as tenant count grows into the hundreds; don't let it block the deploy pipeline synchronously past that point.
- Staging should include a small set of representative "fake" tenants (varying module combinations) so a deploy is validated against realistic tenant configurations, not just one clean tenant.

## 17. Scaling Roadmap — when to split out of the monolith

Stay a modular monolith until a **specific, measured** pressure justifies otherwise:

- **Reporting/BI** is the most likely first candidate — heavy aggregate/OLAP-style queries can be moved to a read-replica-backed service or a separate analytics DB (even just a nightly ETL into a warehouse) well before the module itself needs to become a network service.
- **Payroll/compliance calculation engines** are the second candidate if you expand into many jurisdictions — the calculation logic is a natural boundary and can be versioned/deployed independently of the rest of the ERP.
- Don't extract a module into a microservice just because it's "big" — extract when it has a genuinely different scaling profile, release cadence, or team ownership boundary than the rest of the monolith. The modular structure from §3 is what makes this extraction possible later without a rewrite — that's its actual payoff, not day-one service separation.

## 18. Build Order (suggested phases)

1. **Foundation**: `stancl/tenancy` config (multi-DB, domain identification), Core + Identity modules, central Filament panel, tenant provisioning flow, CI with per-tenant test harness.
2. **First vertical slice**: one full module end-to-end (e.g. CRM) through Filament, tenant panel, policies, tests — this validates the module skeleton pattern before stamping it out 10 more times.
3. **Core ERP loop**: Sales → Inventory → Purchasing → Accounting, since these four are tightly coupled and prove out the cross-module event pattern (§3).
4. **HR/Payroll**, **Projects**, then vertical-specific modules (Manufacturing/POS) based on which customer segment you're selling into first.
5. **Reporting/BI** once there's enough cross-module data to actually report on.
6. Billing/subscription-gated module activation (§6) can be built in parallel once at least two modules exist to gate.
