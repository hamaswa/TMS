# TMS Feature Inventory and QA Matrix

Generated from Laravel routes, controllers, middleware, views, models, migrations, and feature tests on 23 July 2026.

## Application surfaces

| Surface | Route count | Primary users |
|---|---:|---|
| Super admin | 24 | Platform administrator |
| Client administration | 237 | Client owners and permitted employees |
| Independent tailor portal | 8 | Contract tailors |
| Customer storefront | 34 | Online cloth customers across the new storefront and compatible legacy routes |
| Mobile API | 10 | Customer mobile application |
| Authentication and public | 20 | All account types |

## Complete feature inventory

### 1. Authentication and account security

- Email or username login for super admins, clients, and employees.
- Client-assigned employee usernames and temporary passwords.
- Forced employee password change and configurable expiry policy.
- Employee activation/deactivation.
- Password reset flow and login throttling.
- Separate phone/password login for independent tailors.
- Customer mobile PIN login, lockout, PIN reset, token logout, and PIN change.

### 2. Super-admin platform administration

- Create and edit client-owner accounts.
- Authorize tailoring, clothing, or both modules.
- Assign account roles and direct permissions.
- Manage global roles and permissions.
- Send client notifications.
- Approve, reject, suspend, and reactivate clients with required reasons and lifecycle history.
- View tenant-scoped client details and business/storefront activity metrics.
- Permanent client deletion is disabled; suspension preserves every business record.
- English marketplace oversight with publication, module, client-status, and moderation filters.
- Non-destructive storefront pause/resume controls with actor, reason, timestamp, and append-only moderation history.

### 3. Client dashboard and module switching

- Tailoring-only dashboard.
- Clothing-only dashboard.
- Workspace selection and switching for combined clients.
- Permission-aware employee landing routes.
- Tailoring job, sales, stock, purchasing, and finance summary cards.

### 4. Client team, roles, and security

- Client-defined roles and granular permissions.
- Preset salesperson, tailor/workshop, order manager, stock manager, accountant, and manager roles.
- Employee creation, editing, activation, profile details, and password reset.
- Tenant-scoped activity log and CSV export.
- Customer-balance visibility permission.

### 5. Unified customers and measurements

- Shared shop and tailoring customer record.
- Parent/sub-customer support.
- Customer statement, combined balance, and direct payments.
- System measurement fields.
- Client-defined custom measurement fields, validation, ordering, and select options.
- Measurement templates, defaults, activation, and field membership.
- Measurement history and immutable order snapshots.
- Customer PIN management.
- CSV customer import/export.

### 6. Tailoring orders and workshop

- Tailoring order creation, editing, printing, and receipt variants.
- Tailor/rate selection and server-calculated order balance.
- Order search, totals, rack number, and completion notifications.
- Assigned, cutting, stitching, trial, ready, and delivered lifecycle.
- Sequential-stage enforcement and status history.
- Customer lifecycle notification delivery, retry, and audit.
- Tailor payment tracking: unpaid, partial, and paid.
- Paginated/filterable workshop board.

### 7. Independent tailors

- Separate phone/password portal independent of employees.
- Only assigned orders are visible.
- Tailor may progress allowed stages but cannot mark delivery.
- Weekly suit, earned amount, and paid amount summary.
- Tailor rates, advances, records, weekly/monthly reports, and print output.
- Duplicate cross-client phone protection.

### 8. Production workforce and compensation

- Production workers kept separate from employee accounts.
- Contractor or employee relationship classification without granting access.
- Predefined stitching, cutting, embroidery, finishing, ironing, and quality work types.
- Client-defined work types and worker skills.
- Per-piece, fixed salary, commission, and hybrid compensation plans.
- Backward-compatible links to legacy tailors and rates.
- Rate-snapshotted order work assignments.
- Independent assignment lifecycle and completion earnings.
- Worker ledger, payable balance, and bounded payments.
- Legacy order/rate/payment backfill and dual-write compatibility.

### 9. Clothing catalog, stock, and sales

- Brands, cloth types, colors, images, racks, and stock records.
- CSV cloth import/export.
- Counter sales, multi-line sales, customer linking, receipts, and payments.
- Stock availability enforcement and Urdu validation.
- Customer sales history and combined customer statement.
- Online order notifications and processing.

### 10. Suppliers, purchases, and inventory accounting

- Supplier profiles, opening balances, and payments.
- Draft/received/cancelled purchase orders.
- Purchase items, receiving, partial/full supplier payments.
- Purchase returns with stock and payable reversal.
- Moving weighted-average inventory costing.
- Inventory movement ledger, manual adjustments, filters, and pagination.
- Inventory valuation and low-stock data.

### 11. Expenses and finance

- Daily expenses, monthly rent/bills/other expenses, and legacy worker salaries.
- Financial reporting period filters.
- Revenue by tailoring, counter sales, manual product sales, and online sales.
- Direct costs, operating expenses, profit, cash flow, receivables, payables, purchases, returns, and inventory value.
- Searchable/paginated receivables and payables.
- CSV exports.
- Known gap: new production-worker earnings/payments are not yet included in financial reports.

### 12. Customer storefront and mobile API

- Urdu public shop discovery, client branding, public contact information, and module-aware storefront landing pages.
- Tenant-managed public clothing listings with live reservable stock, brand/type/color search, and product details.
- Tenant-managed tailoring services and rate-limited customer inquiries.
- Session-token carts with 30-minute reservations that do not alter physical stock before checkout.
- Unified-customer phone/PIN linking with tenant isolation and throttling.
- Locked checkout, immutable order snapshots, inventory movements, unified balance charges, pickup/delivery, and PIN-protected tracking.
- Client order queue with completion and one-time cancellation/stock/balance reversal.
- Compatible legacy cart/order creation, cancellation, repeat order, history, and thank-you flow.
- Customer account details and profile changes.
- Mobile shops, orders, transactions, notifications, mark-read, login/logout, and PIN change APIs.

### 13. Settings, notifications, and supporting features

- Shop identity/settings and active shop configuration.
- Browser/database notifications and push subscription storage.
- Client and customer notification views and read state.
- Urdu client-facing navigation and English super-admin interface.

## QA matrix

| Area | Automated coverage | Live browser coverage | Current result |
|---|---|---|---|
| Authentication | Employee, client, tailor, customer PIN | Client and tailor login UI | Pass |
| Module access | Tailoring-only, clothing-only, combined | Workspace navigation | Pass |
| Employee roles | Permission matrix and direct URL enforcement | Team/activity screens | Pass |
| Customers | Creation, PIN, shared balances, tenant scope | Statements and payments | Pass |
| Measurements | Custom fields, templates, snapshots, history | Field/template screens | Pass |
| Tailoring orders | Create/edit/payment/rates | Full lifecycle and receipts | Pass |
| Tailor portal | Login, ownership, status restrictions | Urdu login and assigned work | Pass |
| Production workforce | Workers, rates, assignment, ledger, tenant scope | Cutter creation, assignment, completion, payment | Pass |
| Shop sales | Costing, balance, overpayment, overselling | Counter-sale rejection | Pass |
| Purchases | Receive, return, payment, tenant scope | Purchase and return workflow | Pass |
| Inventory | Costing, movements, adjustment, filters | Stock, ledger, valuation | Pass |
| Finance | Reconciliation, tenant scope, exports, workforce and storefront accounting | Dashboard reconciliation | Pass |
| Customer storefront | Publication, tenancy, inquiry, reservation, checkout, tracking, cancellation | Full Urdu storefront, checkout, tracking, and client queue | Pass |
| Mobile API | Authentication/security coverage | API contract sweep still required | Pending exhaustive API pass |
| Super admin | Lifecycle, client isolation, marketplace metrics, moderation boundaries | Client details, marketplace, pause/resume, public blocking | Pass |

## Remaining product decisions

1. No external payment gateway is connected; public orders are charged to the unified customer balance and the client records payment through the existing ledger.
2. New public customers still obtain their PIN from the shop; self-registration and verified PIN delivery require an email/SMS/WhatsApp provider or another approved identity flow.
3. The compatible legacy online-order flow remains available alongside the new storefront order ledger and may be consolidated in a future migration after production usage is reviewed.
4. The mobile API has automated authentication and tenant-security coverage, but a separate exhaustive device/API contract pass remains desirable before a mobile release.
