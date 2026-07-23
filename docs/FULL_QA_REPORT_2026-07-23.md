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
- The complete automated suite passed: **109 tests, 753 assertions**.
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

1. Fixed-salary and commission compensation plans are stored, but automated payroll accrual is not yet implemented.
2. Dedicated cutter/other production-worker portals remain planned; only independent tailors currently have a worker portal.

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

## Tailor shop-code login follow-up

- Added a permanent unique shop code to every business and displayed it in Urdu client settings and English super-admin details.
- Tailor login now requires shop code, phone, and password, allowing identical phone numbers under different clients without opening the wrong tenant.
- Login errors remain generic and Urdu so shop, phone, and password existence are not disclosed separately.
- Suspended businesses or businesses without tailoring access cannot start or continue a tailor-portal session.
- The additive migration preserved all live records: 3 businesses received 3 unique codes, no code is missing, and both tailor accounts remain present.

## Client tailor-management browser QA follow-up

- Browser QA created tailor محمد وقاص, recorded a Rs. 500 advance, added the sewing type سادہ شلوار قمیض, and set a Rs. 400 per-suit rate.
- Found and fixed a live MySQL HTTP 500 caused by the missing `tailors.advance` column; the additive migration preserved all tailor records and initialized historical balances to zero.
- Advances are now additive, transactionally locked, tenant scoped, and recorded in `tailor_records` instead of silently overwriting the previous balance.
- Duplicate tailor phone numbers are rejected within one client while remaining valid across different businesses through shop-code login.
- Reworked the Urdu tailor list with visible labels for advances, accounts, rates, orders, editing, and deletion; invalid table/modal nesting was removed.
- Localized report filters, weekdays, transaction types, currency, success messages, and rate controls; removed the broken `weekFilter=undefined` redirect and external jQuery dependency from the report filter.
- Live verification: the client has 2 tailors; محمد وقاص has Rs. 500 advance, 1 transaction, and 1 rate. Browser console errors/warnings: zero.

## Git checkpoints

- `9f0e117` — feature inventory and QA coverage.
- `56073ce` — super-admin approval and activation lifecycle.
- `0c49beb` — MySQL client metric regression fix.

## Chrome client tailor-management QA

- Signed in as the live client owner and verified the tailor list, tailor creation form, advance-payment modal, account report, rate list, and tailor-order list in Google Chrome.
- Verified the real QA records for محمد وقاص: Rs. 500 current advance, the recorded advance transaction, and the Rs. 400 سادہ شلوار قمیض rate.
- Confirmed that the account report no longer produces `weekFilter=undefined`, weekdays and transaction types are localized, and the empty orders state is shown in Urdu.
- Found a visual contrast defect that made outline action labels appear blank against the table background. Replaced the affected outline styles with high-contrast action buttons and visually retested the page.
- Chrome console errors and warnings: zero.
- Focused regression result after the visual fix: 3 tests passed, 33 assertions.
