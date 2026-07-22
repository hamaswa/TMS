# TMS implementation status

Last updated: 2026-07-23

## 23 July 2026 — Super-admin client lifecycle and full QA

- Inventoried all application features and route surfaces in `docs/FEATURE_INVENTORY_AND_QA_MATRIX.md`.
- Added safe pending/active/suspended/rejected business lifecycle controls with full status audit history.
- Added an English super-admin client list and details screen with filters, module access, business metrics, approval metadata, and activate/deactivate actions.
- Preserved every existing business as active and disabled permanent client deletion to protect live data.
- Enforced account status for business owners and employees at login and on subsequent requests, using Urdu blocked-account messages on client-facing screens.
- Completed the full automated suite with 106 passing tests and 720 assertions.
- Browser QA found and fixed a MySQL-only client order-count failure caused by the legacy camel-case `orders.userId` column.
- Recorded current findings and the prioritized backlog in `docs/FULL_QA_REPORT_2026-07-23.md`.

## Completed

- Upgraded the application to Laravel 12.64.0.
- Verified clean migrations and seeders, route/config/view caches, tests, and production assets.
- Secured tailor passwords with hashing and transparent one-time migration of legacy plaintext passwords.
- Removed tailor password output from management screens.
- Added shop ownership checks to core tailor, order, sale, setting, expense, cart, and online-order operations.
- Extended shop ownership checks across cloth brands/types, cloth and stock records, designs, option categories/options, customers, and direct payments.
- Added validation and database transactions to core tailoring orders, payments, counter sales, cart reservations, and online-order stock changes.
- Rebuilt the stock-sale write path with row locking, server-side stock validation, atomic stock/payment updates, and shop-scoped printing and reporting.
- Added sale ownership through `sales.user_id` and backfilled existing sales from their transactions.
- Repaired legacy cloth/design schemas so both upgraded and freshly installed databases have the required catalog tables and ownership columns.
- Replaced unsafe original upload filenames in the cloth and brand workflows with framework-generated storage paths.
- Converted known destructive GET routes to DELETE/PATCH/POST routes with CSRF-protected forms.
- Fixed online-order cancellation/reorder inventory restoration and deduction.
- Fixed the dashboard tags-input JavaScript error and service-worker 404.
- Protected the mobile API with Sanctum tokens and restricted orders, transactions, notifications, and logout to the authenticated customer.
- Restricted account editing to the signed-in shop owner and require the current password before a password change.
- Added six tenant/security feature tests; the full suite now passes with 8 tests and 23 assertions.
- Fixed shared asset URLs under the PHP development server and browser-verified the dashboard, cloth-brand list, and stock list at port 8010.
- Added the tailor job lifecycle: assigned, cutting, stitching, trial, ready, and delivered.
- Added an admin/tailor job board with deadlines, overdue highlighting, workload counters, filters, and controlled next-stage actions.
- Added immutable status-history records identifying whether the shop owner or tailor made each transition.
- Added per-order tailor earnings, paid totals, unpaid/partial/paid state, and salary ledger entries.
- Restricted tailors to their own jobs and reserved final customer delivery confirmation for the shop owner.
- Normalized legacy `new`, `start`, and `complete` order states into the new lifecycle.
- Added five lifecycle/security tests; the full suite now passes with 13 tests and 47 assertions.
- Added tenant-scoped supplier management with contacts, opening balances, activation, and general supplier payments.
- Added draft purchase orders with multiple cloth/color lines, supplier references, dates, quantities, and unit costs.
- Added atomic goods receipt: locked color inventory updates, duplicate-receipt protection, and receipt timestamps.
- Added purchase returns with received-quantity and live-stock limits, payable adjustments, and automatic stock reduction.
- Added purchase-specific supplier payments with overpayment prevention and updated purchase balances.
- Added an inventory movement ledger for purchase receipts and supplier returns with signed quantities and source references.
- Added six purchase/security tests; the full suite now passes with 19 tests and 89 assertions.
- Centralized cloth quantity changes through a transactional inventory service with signed movement quantities and running balances.
- Extended the ledger to counter sales, direct online orders, online cancellations/reorders, cart reservations/releases, purchase receipts/returns, and manual adjustments.
- Added moving weighted-average cost per cloth color and automatic recalculation on purchases and positive adjustments.
- Added cost-per-meter and total cost-of-goods snapshots to counter-sale records.
- Added a tenant-scoped manual stock-adjustment workflow requiring a reason and preventing negative stock.
- Added stock valuation reporting for meters on hand, inventory cost, retail value, and potential margin.
- Repaired missing legacy sale, transaction, and shop-slug schema fields required by active application code.
- Added four costing/ledger tests and expanded cart assertions; the full suite now passes with 23 tests and 113 assertions.
- Added a shop-owner financial dashboard with selectable date ranges and clearly separated accrual profit and actual cash flow.
- Added revenue breakdowns for tailoring, counter cloth sales, manual product sales, and non-cancelled online orders.
- Added direct-cost reporting for counter/online cost of goods and accrued tailor labor, plus monthly, daily, and worker expenses.
- Added as-of-date customer receivables and supplier payables, period purchases/returns, and current stock-value summaries.
- Added CSV exports for financial summaries, receivables, and payables with spreadsheet-formula injection protection.
- Added cost snapshots to new online orders so online cost of goods is included in future profit reports.
- Added reconciliation and authorization tests; the full suite now passes with 25 tests and 130 assertions.
- Added database/mobile customer notifications for cutting, trial, ready, and delivered milestones.
- Added per-order notification delivery logs with sent, failed, and skipped states, attempt counts, timestamps, and captured errors.
- Added transaction-safe duplicate suppression so each customer receives at most one notification for each order milestone.
- Added delivery status to the tailor job board and owner-only retries for failed or skipped notifications.
- Kept lifecycle progress independent from notification availability so a delivery outage cannot roll back valid workshop work.
- Added notification idempotency and retry-recovery tests; the full suite now passes with 27 tests and 142 assertions.
- Replaced unbounded tailor-job, purchase, inventory-ledger, stock-valuation, receivable, and payable result loading with database pagination.
- Added server-side search, date, status, supplier, tailor, deadline, movement-type, and configurable page-size filters where relevant.
- Preserved active filters and independent page positions in pagination links, including separate receivable and payable pages on the financial dashboard.
- Reworked financial receivable/payable balances into grouped database queries so summary totals do not require loading every customer or supplier.
- Added Bootstrap-compatible pagination rendering and visible matching-record counts on operational lists.
- Added four pagination/filter regression tests; the full suite now passes with 31 tests and 159 assertions.
- Rebuilt the login page as a responsive TMS-branded workshop experience with clearer form hierarchy, accessible controls, mobile behavior, and secure-access messaging.
- Repaired the guest layout's broken legacy asset URLs by switching authentication pages to the compiled Vite asset manifest.
- Added branded-login asset coverage; the full suite now passes with 32 tests and 164 assertions.
- Added independent `tailoring` and `clothing` client entitlements with a safe migration that preserves both modules for every existing client.
- Added route middleware that enforces module access server-side for tailoring and clothing operations, including direct URL requests.
- Rebuilt super-admin client creation/editing around explicit Tailoring System and Clothing Sales & Purchases authorization cards, requiring at least one module for client accounts.
- Prevented individual client permission edits from mutating shared role permissions for every user in that role.
- Rebuilt the client navigation and dashboard so tailoring-only, clothing-only, and combined clients see a focused workspace with relevant metrics and shortcuts.
- Made financial reports module-aware so disabled-module revenue, costs, balances, purchases, and inventory are not exposed historically.
- Added the complete client access matrix and super-admin assignment coverage; the full suite now passes with 37 tests and 196 assertions.
- Added an Urdu workspace chooser for combined clients, with separate focused Tailoring and Shop dashboards and an explicit workspace switcher.
- Added module-aware post-login routing: single-module clients go directly to their dashboard, while combined clients choose their working area.
- Added a backward-compatible `businesses` membership layer and linked every existing client owner to a business while retaining the owner's existing tenant ID for all historical records.
- Added owner workspace preferences and business membership fields needed for future client-managed employees.
- Added workspace routing and entitlement regression coverage; the focused module-access suite passes with 5 tests and 42 assertions.
- Converted operational tailoring, sales, purchasing, stock, reporting, expenses, settings, and import/export controllers from signed-in-user ownership to the linked business owner's tenant ID.
- Added client-managed Urdu employee and role screens with permissions for Tailoring, Shop/Sales/Purchases, Finance, Expenses, Settings, and Team Management.
- Added automatic employee routing: one authorized workspace opens directly, while two authorized workspaces retain the chooser and explicit switcher.
- Added active/inactive employee enforcement, business-scoped role and employee editing, password hashing, and cross-client isolation.
- Browser-QA created a Shop-only employee, verified its automatic Shop dashboard redirect, confirmed Finance/Team links stay hidden, and found no browser console errors.
- The complete suite now passes with 41 tests and 226 assertions.
- Split Shop employee access into separately enforceable Sales, Inventory, Purchasing, and Supplier permissions, including direct URL protection and permission-aware dashboard metrics/navigation.
- Preserved existing Shop roles during the granular-permission migration, then narrowed the live Sales QA role to Sales and Online Orders only.
- Extended client-owned employee profiles with unique username, full name, phone, address, email, password, job title, role, active status, and login by either username or email.
- Added live client-owned Sales, Tailoring, and Manager QA profiles under the combined client, plus a tailoring customer/job and shop cloth/supplier/draft-purchase dataset shared through the business tenant.
- Browser-QA verified Sales-only inventory denial, Tailoring-only automatic routing and Shop denial, Manager workspace selection and Team Management, Urdu rendering, and zero console errors.
- The complete suite now passes with 43 tests and 243 assertions.
- Split Tailoring employee access into Customers/Measurements, Orders/Payments, Workshop Jobs, Tailor/Rates Management, and Design/Measurement Configuration permissions.
- Added direct-route enforcement and permission-aware Tailoring dashboard metrics, shortcuts, and navigation; existing Tailoring roles were safely backfilled.
- Narrowed the live Tailoring QA role to Customers, Orders, and Workshop duties, with Tailor Management and Configuration explicitly denied.
- Added a tenant-scoped employee activity audit that records successful mutating actions, employee identity, time, route, method, and record identifiers without storing passwords or submitted form contents.
- Added an Urdu activity dashboard with employee, date, and action filters, daily totals, active-user counts, pagination, and client-controlled `activity.view` authorization.
- Browser-QA verified the live Manager activity records and the narrowed Tailoring employee menu/direct-URL denial with zero console errors.
- The complete suite now passes with 45 tests and 262 assertions.
- Added client-issued temporary employee passwords with remembered-session invalidation, reset timestamps, and identification of the client user who issued each reset.
- Added mandatory first-login password replacement: employees cannot open any business route until they submit the current temporary password and choose a different confirmed password.
- Added a dedicated responsive Urdu employee security screen plus an optional employee self-service password-change link.
- Separated ordinary employee profile updates from credential resets so changing a name, phone, address, email, role, or status cannot silently alter a password.
- Added audit entries for both client-issued temporary passwords and employee-completed password changes without capturing either password.
- Completed 390x844 mobile QA for employee editing, password reset, first-login security, Sales dashboard return, and the activity report; corrected long email fields to render left-to-right.
- Restored the live Sales QA account password to `QaEmployee@2026` after the end-to-end reset test and left the Manager on the activity report showing all four audited changes.
- The complete suite now passes with 46 tests and 279 assertions.
- Replaced technical-only activity wording with friendly Urdu descriptions for employee security, team management, suppliers, purchases, stock, sales, tailoring jobs, orders, and payments, with a clear Urdu fallback for other audited routes.
- Added a tenant-scoped CSV activity export that reuses the screen's employee/date/action filters, writes an Excel-compatible UTF-8 file, and neutralizes spreadsheet-formula prefixes in every exported value.
- Browser-QA verified the updated live Urdu activity report, four friendly action descriptions, the CSV download control, and zero browser console errors.
- The complete suite now passes with 47 tests and 290 assertions.
- Added an optional client-controlled employee password-expiry policy with 30, 60, 90, 180, and 365-day choices; it remains disabled by default.
- Added a policy-change grace period so enabling or changing expiry does not immediately lock out existing staff, while genuinely expired employees are restricted to the password-security screen.
- Enforced strong employee and temporary passwords on the server: at least 8 characters with uppercase, lowercase, number, and symbol requirements.
- Added reusable interactive Urdu password-strength guidance to employee creation, temporary-password reset, and employee self-service password screens.
- Applied the password-policy migration and browser-QA verified the Manager's Urdu policy control, weak/strong meter transitions, and zero console errors without changing live credentials or enabling expiry.
- The complete suite now passes with 49 tests and 307 assertions.

## Next when the user says "go next" or "proceed"

1. Implement fixed-salary and commission payroll accrual.
2. Add dedicated portals for cutters and other production workers.

## Latest accounting checkpoint — 23 July 2026

- Included non-legacy production-worker earnings in tailoring direct costs and gross/net profit.
- Included their payments in cash out and net cash flow.
- Excluded mirrored legacy-tailor ledger entries to prevent double counting.
- Made worker payments atomic with a tenant-scoped row lock, balance recheck, and ledger insert in one transaction.
- Added database-enforced active work-assignment uniqueness while preserving cancelled history and reassignment.
- Applied the additive migration with all 6 live assignments preserved and verified.
- Added unique business shop codes and tenant-scoped tailor login using shop code, phone, and password.
- Applied the additive migration with all 3 businesses and both tailor accounts preserved.
- Full regression suite: 106 tests and 720 assertions passing.

## Current continuation point

- The workspace chooser, focused dashboards, remembered workspace, and owner module authorization are implemented.
- The business membership migration is applied and existing clients are backfilled.
- Employee login and client-managed roles are enabled with safe business tenant scoping.
- Live client-owned QA profiles:
  - Sales: username `qa.shop.sales`, email `qa.employee.shop.09629983@example.test`, password `QaEmployee@2026`.
  - Tailoring: username `qa.tailoring.staff`, email `qa.employee.tailoring.09629983@example.test`, password `QaEmployee@2026`.
  - Manager: username `qa.business.manager`, email `qa.employee.manager.09629983@example.test`, password `QaManager@2026`.
- The in-app browser is left on the Urdu employee/role management screen as the Manager profile.
- The Manager profile is left signed in on the Urdu employee activity report with two live audited changes.
- The Manager profile is left signed in on the activity report, which includes the live temporary-password issuance and employee password-change events.
- The Manager is signed in and the in-app browser is left on the Urdu Team page with password expiry disabled.
- Resume with production-ready audit retention and backup/restore verification controls.

## Known remaining technical debt

- Some administrator-only global queries intentionally remain unscoped and need explicit role-policy coverage.
- The legacy stock-sale implementation remains unreachable below the new transactional path and should be removed in a cleanup pass.
- Mobile clients must be updated to send `shop_id` at login, save the returned Sanctum token, and use it as a Bearer token.
- Broader regression coverage is still needed for accounting totals and failure rollbacks.
- Vite/esbuild dependency advisories require a separate breaking frontend upgrade.
- Sass emits deprecated API and `@import` warnings.
- Browserslist's local `caniuse-lite` data is outdated.
- Historical stock mutations created before the inventory ledger was introduced are not retroactively reconstructed.
- Historical counter sales have no captured weighted-average cost; new counter sales record cost correctly.
