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
- The complete automated suite passed: **104 tests, 688 assertions**.
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

1. Financial reports do not yet include production-worker earnings and payments in the main profit/cash summaries.
2. Worker payment balance validation should use a database row lock to prevent simultaneous payments exceeding the balance.
3. Duplicate order-work assignments should have a database-backed uniqueness/concurrency rule in addition to application validation.
4. Independent tailor login safely rejects duplicate phone numbers across businesses, but the login screen should accept a shop identifier to make valid duplicate phone use possible.
5. Fixed-salary and commission compensation plans are stored, but automated payroll accrual is not yet implemented.
6. Dedicated cutter/other production-worker portals remain planned; only independent tailors currently have a worker portal.

## Git checkpoints

- `9f0e117` — feature inventory and QA coverage.
- `56073ce` — super-admin approval and activation lifecycle.
- `0c49beb` — MySQL client metric regression fix.
