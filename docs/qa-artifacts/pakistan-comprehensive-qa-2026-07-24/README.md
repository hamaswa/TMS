# TMS Pakistan Comprehensive QA Report

Date: 24 July 2026
Branch: `feature/client-public-storefronts`
Environment: Laravel 12.64.0, PHP 8.2.12, MySQL, `http://127.0.0.1:8010`

## Executive result

The core application is operational across super administration, tailoring, cloth sales and purchasing, inventory, workforce, unified customer accounts, public storefronts, online orders, tailoring inquiries, and print views.

- 143 automated tests passed with 1,050 assertions.
- 333 application routes were inventoried.
- More than 50 representative desktop and mobile screens were exercised in the browser.
- Four public storefronts, 20 customers, five QA employees, 11 clothing listings, 12 tailoring services, five public orders, and five public inquiries are now available for realistic review.
- Tenant isolation, employee permissions, tailor portal separation, stock safety, worker ledgers, and combined customer statements behaved correctly in the tested journeys.
- No database orphan records, negative stock, broken images, horizontal overflow, or server-error pages were found in the reviewed journeys.

The system is suitable for continued staged development, but the priority findings below should be resolved before treating the public marketplace and employee workflows as production-complete.

## Coverage

### Feature inventory

| Area | Inventory |
|---|---:|
| All routes | 333 |
| Client administration routes | 237 |
| Super administrator routes | 24 |
| Public storefront routes | 16 |
| API routes | 10 |
| Legacy customer routes | 18 |
| Tailor portal routes | 8 |
| Controllers | 49 |
| Blade views | 190 |
| Feature test files | 28 |

No duplicate route names were detected.

### Browser journeys

- Public marketplace home and all four storefronts
- Clothing catalog, product details, cart, customer PIN linking, checkout, COD, EasyPaisa reference entry, and order tracking
- Tailoring services, inquiry submission, and client inquiry processing
- Super administrator dashboard, marketplace overview, client details, client creation, activation controls
- Combined client workspace chooser and both tailoring/shop dashboards
- Customers, unified statements, measurements, orders, tailor jobs, workers, worker earnings/payments
- Sales, purchases, suppliers, stock ledger, inventory valuation, expenses, and financial reports
- Team members, roles, permissions, activity log, storefront settings, online orders, and inquiries
- Tailor login, tailor dashboard, and tailor job list
- Order receipt, measurement print, sale invoice, alternate print variants, and weekly tailor report
- Desktop and 390 × 844 mobile layouts on representative public and internal pages

### Live QA records

Public storefronts:

- `/shops/khan-tailors-fabrics-rawalpindi` — combined cloth and tailoring
- `/shops/bilal-tailors-peshawar` — tailoring only
- `/shops/ali-fabrics-faisalabad` — clothing only
- The existing Siddiqui storefront remains available

Public clothing order:

- Reference: `TMSO-20260724-N7BCVY`
- Customer: علی حیدر
- Product: Khaadi Fabrics — واش اینڈ وئیر, آسمانی, 3.5 metres
- Total: Rs 3,675
- Payment: cash on delivery

Public tailoring inquiry:

- Reference: `TMSI-000005`
- Customer: شہریار خان
- Service: پشاوری ویسٹ کوٹ
- Requested date: 10 August 2026
- Payment claim: EasyPaisa, `EP-PESH-20260724-01`
- Client status was successfully changed to “contacted” with an internal note

Internal tailoring workflow:

- Order ID: 6
- Customer: حسن رضا
- Total/received/due: Rs 3,200 / Rs 1,200 / Rs 2,000
- Tailor: Rashid Mahmood, Rs 900
- Cutter: Akram Hussain, Rs 50
- Cutter work was completed and a partial payment of Rs 20 left the correct Rs 30 balance

QA employee password: `PakistanQA@2026`

| Login | Intended access |
|---|---|
| `ahmed.manager.qa` | Combined manager |
| `bilal.orders.qa` | Tailoring order manager |
| `bilal.workshop.qa` | Tailoring workshop |
| `usman.sales.qa` | Clothing salesperson |
| `usman.stock.qa` | Stock keeper |

Customer PIN test records:

| Business | Phone | PIN |
|---|---|---|
| Ahmed | 03117001001 | 246801 |
| Ahmed | 03117001002 | 246802 |
| Ahmed | 03117001003 | 246803 |
| Bilal | 03337002001 | 357901 |
| Bilal | 03337002002 | 357902 |
| Bilal | 03337002003 | 357903 |
| Usman | 03217003001 | 468101 |
| Usman | 03217003002 | 468102 |
| Usman | 03217003003 | 468103 |

The QA seeder is additive and idempotent. Running it again updates its named QA records instead of duplicating them.

## Priority findings

### P1 — resolve before production marketplace rollout

1. **Tailoring orders can be created without completed measurements.** Order 6 was accepted for a customer with no measurement values, and its print displayed measurement labels with empty values. Require the configured mandatory measurements, or show a strong warning with an explicit authorized override.

2. **The salesperson permission promise conflicts with route enforcement.** `clothing.sales` is labelled “sales and online orders,” but `/admin/storefront/orders` requires both `storefront.manage` and `clothing.sales`. The tested salesperson could make sales but received 403 on the online-order queue. Either grant that queue to `clothing.sales` or introduce a clear `online_orders.manage` permission and update the role label/preset.

3. **EasyPaisa is currently a payment claim, not a verified payment.** Customers can enter a transaction reference, but the client needs an explicit approve/reject verification workflow, evidence attachment, verifier identity, and immutable audit entry before the order is marked paid.

4. **An active unauthenticated route redirects to insecure external HTTP.** `/new-tab` redirects to `http://heera.it`. Remove this legacy route or replace it with a trusted HTTPS destination.

### P2 — important usability, accessibility, and operational improvements

1. Public clothing search and colour controls have no programmatic labels; placeholders alone are insufficient for screen readers.
2. The public login’s marketplace link, forgot-password link, and checkbox are smaller than the recommended 44 × 44 pixel mobile touch target.
3. Several legacy client pages lack a semantic page heading: sales, tailors, expenses, and daily expenses.
4. A new public shopper cannot complete checkout unless the client has already created the customer and PIN. Add safe self-registration/guest checkout, with PIN now and optional OTP later.
5. Phone validation is broad rather than Pakistan-aware. Normalize `03xx...` and `+92...`, retain the canonical number, and reject malformed values.
6. Public discovery lacks city/category/price/availability search, SEO metadata, Open Graph cards, and local-business/product structured data.
7. Currency presentation alternates between `Rs` and `روپے`; use one formatter while allowing Urdu/English labels.
8. Old print templates still contain stale insecure external CSS asset references. Remove these even though current computed print styles did not load them.
9. Legacy controllers contain numerous commented debug statements. Remove them to reduce maintenance risk.
10. Asset compilation passes but reports deprecated Sass APIs/imports and an outdated Browserslist database.
11. `php artisan db:show --counts` cannot inspect `performance_schema.session_status` with the current MySQL user. Normal application queries work, but operational monitoring should use a narrowly authorized monitoring account or compatible DB inspection.
12. Production configuration must use `APP_DEBUG=false`; the reviewed local environment correctly remains in debug mode for development.

### P3 — polish

- Tailor dashboard text “پچھلہ ہفتہ” should be “پچھلا ہفتہ”.
- The unused default Laravel welcome view can be removed.
- Composer warns that `league/csv` is pinned to exact version `9.18.0`; use a compatible range if project constraints allow it.
- Add explicit A4 and 80 mm receipt choices, predictable page breaks, and QR-based order tracking to print views.

## Data integrity

Final reviewed counts:

| Entity | Count |
|---|---:|
| Users | 21 |
| Businesses | 4 |
| Business roles | 16 |
| Customers | 20 |
| Tailors | 3 |
| Production workers | 4 |
| Worker ledger entries | 9 |
| Cloth brands/types/items | 11 / 11 / 11 |
| Suppliers/purchases/inventory movements | 2 / 3 / 29 |
| Tailoring orders/sales/transactions | 6 / 2 / 15 |
| Storefronts/listings/services | 4 / 11 / 12 |
| Public orders/inquiries | 5 / 5 |

Integrity queries found:

- zero orphan customers
- zero orphan order-to-customer references
- zero orphan transaction-to-customer references
- zero orphan storefront orders
- zero orphan listings
- zero negative cloth stock

Individual payment ledger rows correctly use a negative `remainingBalance` amount to reduce the unified balance. This is expected double-entry-like behavior and is not a negative customer total.

## Automated verification

| Check | Result |
|---|---|
| `php artisan test` | PASS — 143 tests, 1,050 assertions |
| `php artisan migrate:status` | PASS — no pending migrations |
| `php artisan view:cache` | PASS |
| `npm run build` | PASS — 118 modules |
| `npm audit --omit=dev` | PASS — 0 vulnerabilities |
| `composer validate --no-check-publish` | PASS with exact-version warning |
| `composer audit --locked` | Not externally verified: network escalation was not approved |

## Recommended Pakistan product direction

### Public language

Do not change the storefront to English-only. For the current Pakistan-focused product:

1. Keep Urdu as the initial default for the present audience.
2. Add a clearly visible **اردو | English** switch on every public page.
3. Let each client choose the storefront default language.
4. Persist the visitor’s choice in session/cookie and respect it on the next visit.
5. Translate validation, checkout, payment, tracking, and error messages through Laravel language files instead of hardcoded view text.
6. Keep the super administrator interface English as requested.
7. Consider Roman Urdu later only if customer research shows demand.

The application locale is presently `en` while much public text is hardcoded Urdu. Moving both languages into translation files will prevent mixed-language system messages.

### Best next features

1. **Pakistan payments:** EasyPaisa and JazzCash verification queues, bank transfer, and Raast QR; keep COD and record settlement/reconciliation.
2. **WhatsApp communications:** order confirmation, measurement appointment, fitting reminder, ready-for-collection, and delivery templates.
3. **Delivery operations:** city/area charges, district and tehsil addresses, courier selection, tracking numbers, and COD reconciliation for TCS, Leopards, M&P, Trax, or the client’s chosen provider.
4. **Public conversion:** customer self-registration, guest checkout, saved addresses, reorder, returns/exchanges/refunds, and customer-visible payment status.
5. **Tailoring commerce:** design/photo attachments, home-measurement appointments, fitting slots, repeat measurements, alteration requests, and approval before cutting.
6. **Cloth inventory:** roll/lot tracking, metres/yards/suit units, barcode/QR labels, branch stock transfers, minimum-stock alerts, and colour/brand variants.
7. **Marketplace discovery:** city, category, price, delivery, rating, and availability filters; featured stores/products as an optional advertising product.
8. **Pakistan accounting:** configurable invoice numbering, NTN/STRN/GST fields, tax-inclusive/exclusive prices, daily cash closing, and payment-method reconciliation.
9. **Reliability:** automated encrypted backups, restore drills, audit alerts, error monitoring, and exports.
10. **Low-connectivity UX:** installable PWA, lightweight images, draft saving, and retry-safe submissions.

## Recommended implementation order

1. Fix missing-measurement order protection and the online-order permission mismatch.
2. Add verified payment states and audit records for EasyPaisa/JazzCash/bank transfer.
3. Introduce Urdu/English translation files and the storefront language switch.
4. Add Pakistan phone normalization and customer self-registration.
5. Improve accessibility, SEO, and print templates.
6. Add courier, returns, marketplace discovery, and advanced inventory features.
