# TMS Full QA Report — 23 July 2026

## Public cart and reservation QA

The storefront cart checkpoint was tested against the live MySQL database without resetting or deleting existing client data.

- Verified that expired carts release their reserved quantities even when their child item deadline has not yet expired.
- Verified that removed and expired items restore public availability, while active carts prevent over-reservation by another session.
- Verified that reserving or changing quantity does not alter `cloth_colors.length`, create inventory movements, create an order, create a payment, or change a customer balance.
- Found a MySQL deadline defect where updating cart activity implicitly changed the first `TIMESTAMP` column. Added an additive stabilization migration to retain all rows while converting cart deadlines to `DATETIME`.
- Live Chrome flow: 24.50 m available → reserve 2.50 m → link existing unified customer `فہد اقبال` by phone/PIN → update to 3.00 m → total Rs 4,950.
- Confirmed the linked-customer identity and reservation survive redirects and cart updates.
- At 390 px the Urdu cart has no horizontal overflow (`scrollWidth` 375 within a 390 px viewport); product controls, totals, reservation notice, and customer identity remain readable.
- Complete automated result after this checkpoint: **127 tests passed, 875 assertions**.

## Final client, employee, worker, and print QA pass

This follow-up pass covered every user-facing client back-office screen identified in the route/view inventory, the tailoring, sales, and finance employee experiences, independent-tailor access, production-worker management, and the active order/measurement/invoice print routes.

- Chrome desktop QA covered the workspace chooser, both client dashboards, shared customers and statements, measurements and templates, tailoring orders and workshop, tailors and rates, production workers, cloth catalog and stock, counter sales, suppliers and purchases, inventory reporting, finance, expenses, team/security/activity, settings, notifications, and online orders.
- Permission QA used a tailoring employee, sales employee, and finance/balance employee. Each account was redirected to the correct workspace and denied unrelated direct URLs.
- The accountant now sees the business owner's real combined tailoring/shop figures instead of an empty report, and a balance-only employee can open the shared customer statement.
- Active prints checked: one- and two-copy tailoring order/measurement sheets, manual sale invoice, one- and two-copy cloth sale receipts, and tailor weekly report.
- Print fixes include Urdu labels, decoded suit serial numbers, valid local Urdu font/logo paths, non-overlapping action controls, a compact five-column thermal sale table, and removal of blank header space.
- Broken resource endpoints were repaired for customer, tailor, cloth, brand, cloth type, option type, and option records. The stock-list alias and online-orders screens no longer return HTTP 500.
- Settings now ignores missing logo files instead of rendering broken images. DataTables defaults are applied before screen-specific initialization, so tables use Urdu controls consistently.
- No production records were deleted or reset during this pass.
- Final automated result: **111 tests passed, 765 assertions**.

### Chrome print QA continuation

The print pass was resumed after the earlier Chrome capture timeout and each active print route was opened separately. This exposed and resolved issues hidden by the timed-out batch:

- The one-copy order/measurement print returned HTTP 500 because a Blade `@php` opening directive was emitted literally while its closing directive compiled. Serial formatting is now rendered safely without the broken temporary variable.
- The `/admin/order/prints/{id}` route incorrectly returned the one-copy template. It now returns its intended alternate print view.
- Manual sale invoice lines labelled “total price” displayed unit price only. They now show `quantity × unit price`.
- Print-page titles and document direction are now Urdu/RTL.
- Legacy automatic popup printing and missing-element JavaScript were removed from the active sale, order, and cloth receipt views. Printing starts only from the visible print button.
- Clean Chrome console verification completed for order/measurement, alternate order, manual sale invoice, cloth receipt, alternate cloth receipt, and tailor weekly report: zero errors or warnings, zero broken images, and no horizontal page overflow.
- Layout measurements confirmed receipt widths of approximately 88 mm and no action-button overlap.
- Added regression coverage proving both order print routes render decoded suit serials and return distinct intended templates.

### Defects fixed in this pass

| Severity | Area | Defect | Resolution |
|---|---|---|---|
| High | Finance employee | Finance-only staff received zero totals because their role had no tailoring/shop access flag. | Reports now use the client's enabled business modules while retaining finance permission enforcement. |
| High | Customer balance access | A balance-only employee was blocked before reaching the shared statement. | Added the balance permission to route authorization and regression coverage. |
| High | Cloth/catalog | Cloth edit, stock alias, online orders, and several resource `show` routes returned 500, 404, or blank responses. | Repaired controller actions, tenant-scoped lookups, and safe redirects. |
| Medium | Print layouts | English labels, JSON serial text, overlapping controls, blank space, and cramped thermal columns reduced readability. | Localized and normalized all active receipt/order layouts. |
| Medium | Branding | Missing or legacy logo URLs produced broken images. | Added a validated `logo_url` accessor and used it on active screens/prints. |
| Low | Urdu tables | Some DataTables controls initialized in English. | Registered Urdu defaults before page table initialization. |

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

## Order receipt layout follow-up

- Reproduced the order-print defect where customer and payment values were positioned against the browser viewport instead of the thermal receipt.
- Constrained both order print layouts to a centered 88 mm receipt and corrected the Urdu font asset path.
- Kept every positioned field inside the receipt, removed horizontal overflow, and aligned amount, balance, and delivery-date rows.
- Cleaned the alternate print controls so they no longer overlap receipt content.
- Chrome verification found no broken images or console errors/warnings on either order print route.
- Final regression result: 111 tests passed, 765 assertions.

## Fresh shop workspace and complete employee-role QA

- Created and approved a new combined tailoring and clothing client for Kamran Siddiqui without modifying existing client records.
- Completed the Urdu shop profile as `صدیقی ٹیلرز اینڈ فیبرکس`, including phone, address, service note, and logo.
- Created all 6 supported role presets and one realistic employee for each: salesperson, workshop tailor, order manager, stock manager, accountant, and business manager.
- Verified the mandatory temporary-password replacement flow for every employee; all 6 accounts now show as active.
- Salesperson: shop dashboard and counter sales allowed; stock, purchasing, tailoring, finance, and team routes denied.
- Workshop tailor: tailoring dashboard and assigned-job board allowed; order administration, stock, and team routes denied.
- Order manager: customers, tailoring orders, and workshop allowed; tailor administration, stock, and team routes denied.
- Stock manager: stock, purchasing, and suppliers allowed; counter sales, tailoring, finance, and team routes denied.
- Accountant: financial reports and expenses allowed; both operational dashboards, stock, and team routes denied.
- Manager: workspace chooser plus tailoring, shop, finance, settings, team, and activity screens allowed.
- Chrome verification found no horizontal overflow, broken images, or console errors/warnings on the final roster and shop dashboard.
- UI improvements identified: the shop setup page still contains English messages/actions (`Setting Added`, `Choose File`, `Deactive`, `Delete setting`), its heading says `نائی ترتیب` instead of `نئی ترتیب`, and a logo is mandatory.
- Terminology improvement identified: the employee preset named `درزی` is workshop access, while independent production tailors remain separate; renaming the preset would reduce confusion.

## Public storefront foundation QA

- Added and ran the isolated `storefronts` migration after reviewing its SQL with `migrate --pretend`. It creates one new table and does not mutate existing customers, orders, sales, purchases, stock, payments, staff, or worker records.
- Verified the full owner lifecycle in Chrome: open storefront settings, save an Urdu draft, preview it while private, publish it, and open the public URL.
- Verified the public `/shops` directory lists the published active business with the correct Tailoring, Clothing, and Delivery capabilities.
- Verified an unpublished storefront returns 404 publicly, and suspending its business hides it without deleting the storefront.
- Verified an employee receives 403 unless the client explicitly grants the new storefront-management permission.
- Verified the English super-admin client detail view includes publication status, selected public modules, publication time, and public URL.
- Desktop viewport: no horizontal overflow, missing assets, console errors, or warnings.
- Mobile viewport (390 × 844): cards collapse into one column, navigation remains usable, Urdu headings remain readable, and no horizontal overflow occurs.
- Live QA storefront: `صدیقی ٹیلرز اینڈ فیبرکس`, URL `/shops/siddiqui-tailors-fabrics`.
- Full automated regression result: **115 tests passed, 792 assertions**.
- Deliberately not represented as complete yet: clothing products, public stock/catalog browsing, tailoring services, inquiries, cart, online checkout, and order tracking remain the next branch checkpoints.

## Public clothing catalog QA

- Added and ran the additive `storefront_clothing_listings` migration after SQL inspection. It references existing storefront and cloth records without copying or changing inventory.
- Verified clients can publish only their own cloth records and that unpublished or cross-storefront listing URLs return 404.
- Verified public quantities are read live from `cloth_colors`; changing inventory updates public availability without updating the listing.
- Verified Urdu search, color filtering, featured-item ordering, public prices, per-color quantities, detail pages, and direct shop contact.
- Found and fixed an existing cloth-onboarding defect where the browser required an image-color selection even without an image.
- Cloth creation now accepts both English and Urdu commas and validates an exact one-to-one color/length mapping.
- Live QA data: `صدیقی پریمیم فیبرکس`, type `گرمیوں کا واش اینڈ ویئر`, public item `صدیقی پریمیم سمر واش اینڈ ویئر`, 24.50 meters at Rs 1,650 per meter.
- Desktop and 390 × 844 mobile QA found no horizontal overflow and no browser console errors or warnings.
- Full automated regression result: **119 tests passed, 811 assertions**.
- Still pending: public tailoring service listings, inquiries/bookings, cart, checkout, payment allocation, and order tracking.

## Public tailoring services and inquiry QA

- Added and ran the additive tailoring-service and storefront-inquiry tables after SQL inspection; no existing customer, order, payment, inventory, employee, tailor, or worker record was changed.
- Verified service publication fields, featured ordering, initial prices, price units, estimated completion days, public detail pages, and disabled/private visibility.
- Verified the public inquiry form accepts a published service and returns a permanent reference while creating zero customer, tailoring-order, transaction, or stock records.
- Verified inquiry submission is limited to active published tailoring storefronts with inquiries enabled, includes a hidden anti-spam field, and is throttled to 10 attempts per minute.
- Verified clients can view only their own inquiry queue, search/filter it, add internal notes, and move it through New, Contacted, and Closed states with timestamps.
- Live QA service: `پریمیم مردانہ شلوار قمیض سلائی`, starting at Rs 1,800 per suit with an estimated seven days.
- Live QA inquiry: `محمد حمزہ خان`, reference `TMSI-000001`, preferred date 05-08-2026; status changed to Contacted with an internal follow-up note.
- Chrome initially detected 11,585 px horizontal overflow caused by the honeypot position. The field was repositioned without weakening validation; desktop and 390 px mobile retests have zero overflow.
- Browser console errors/warnings: zero.
- Full automated regression result: **122 tests passed, 840 assertions**.
- Still pending: safe cart reservations, unified-customer checkout, payment allocation, and public order tracking.
