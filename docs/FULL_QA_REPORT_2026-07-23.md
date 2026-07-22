# TMS Full QA Report — 23 July 2026

## Scope

The code-level feature catalogue and QA coverage map are maintained in `FEATURE_INVENTORY_AND_QA_MATRIX.md`. This pass covered all registered application surfaces:

- 22 super-admin routes, including the new client lifecycle controls.
- 224 client back-office routes.
- 8 independent-tailor portal routes.
- 18 customer storefront routes.
- 10 authenticated mobile API routes.
- 20 authentication and public routes.

## Verification completed

- Laravel migration SQL was inspected with `migrate --pretend` before execution.
- The additive lifecycle migration ran successfully on MySQL.
- Existing data remained present: 3 businesses, 8 users, 6 customers, 5 orders, 2 sales, 3 purchases, and all existing businesses remained active.
- The complete automated suite passed: **105 tests, 704 assertions**.
- Live in-app browser QA covered super-admin login, client list, search/status/module controls, client details, business metrics, status history, and account actions.
- The live client-details screen finished with no browser console errors or warnings.

## Super-admin improvements delivered

- New clients start as **Pending** and cannot log in until approved.
- Super admin can view client profile, enabled modules, approval metadata, employee/customer/tailor/order/worker/sale/purchase counts, and status history.
- Supported lifecycle: Pending → Active/Rejected, Active → Suspended, Suspended/Rejected → Active.
- Suspension and rejection require a reason and record the acting super admin and timestamp.
- Suspended, pending, or rejected accounts are blocked at login and on the next authenticated request; employees are blocked with the business owner.
- Existing client accounts were explicitly preserved as Active.
- Permanent client deletion is disabled; super admin is directed to suspend the account so live business data is retained.
- Client list now supports text search, lifecycle status filters, module filters, pagination, and direct detail access.

## Defect found and fixed during live QA

| Severity | Area | Defect | Resolution |
|---|---|---|---|
| High | Super-admin client details | MySQL returned HTTP 500 because tailoring orders use the legacy `userId` column while the metric queried `user_id`. SQLite setup did not expose it in the original test. | Corrected the query, added real-schema regression data, and live-browser retested successfully. |

## Confirmed remaining backlog

1. Independent tailor login safely rejects duplicate phone numbers across businesses, but the login screen should accept a shop identifier to make valid duplicate phone use possible.
2. Fixed-salary and commission compensation plans are stored, but automated payroll accrual is not yet implemented.
3. Dedicated cutter/other production-worker portals remain planned; only independent tailors currently have a worker portal.

## Financial reporting follow-up

- Non-legacy production-worker earnings now appear as tailoring direct costs and reduce gross/net profit.
- Payments to cutters and other new production workers now appear in the cash-out breakdown and net cash flow.
- Legacy tailor earnings and payments remain sourced from the established order and tailor-payment tables, preventing mirrored worker-ledger entries from being counted twice.
- Tenant, date-range, and module boundaries are applied to the new calculations.

## Worker payment concurrency follow-up

- Worker payments now lock the tenant-scoped production-worker row inside a database transaction.
- The current ledger balance is recalculated only after the lock is acquired, and the payment is inserted before releasing it.
- Simultaneous payment requests are therefore serialized; a waiting request sees the balance left by the first request and is rejected in Urdu if it is now too high.
- Regression coverage verifies that a second payment cannot use the worker's earlier balance.

## Work-assignment concurrency follow-up

- Added a nullable unique active-assignment key for each tenant/order/worker/work-type combination.
- The migration aborts before changing schema if pre-existing active duplicates are found; the live preflight found none.
- All 6 live assignments were preserved and backfilled, with zero missing active keys and zero duplicates after migration.
- A cancelled assignment releases its key, so the work may be legitimately reassigned while its history remains intact.
- A database collision is returned to the Urdu interface as the existing friendly duplicate-assignment message.

## Git checkpoints

- `9f0e117` — feature inventory and QA coverage.
- `56073ce` — super-admin approval and activation lifecycle.
- `0c49beb` — MySQL client metric regression fix.
