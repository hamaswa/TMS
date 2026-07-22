# TMS Feature Inventory and QA Matrix

Generated from Laravel routes, controllers, middleware, views, models, migrations, and feature tests on 23 July 2026.

## Application surfaces

| Surface | Route count | Primary users |
|---|---:|---|
| Super admin | 20 | Platform administrator |
| Client administration | 224 | Client owners and permitted employees |
| Independent tailor portal | 8 | Contract tailors |
| Customer storefront | 18 | Online cloth customers |
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
- Existing destructive client deletion route.
- Missing before this audit: approval workflow, active/inactive lifecycle, client detail page, non-destructive suspension, and lifecycle audit metadata.

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

- Shop discovery and shop catalog.
- Stock search by brand, type, and color.
- Cart add/remove/purchase.
- Online order creation, cancellation, repeat order, history, and thank-you flow.
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
| Production workforce | Workers, rates, assignment, ledger, tenant scope | Cutter creation, assignment, completion, payment | Pass with finance gap |
| Shop sales | Costing, balance, overpayment, overselling | Counter-sale rejection | Pass |
| Purchases | Receive, return, payment, tenant scope | Purchase and return workflow | Pass |
| Inventory | Costing, movements, adjustment, filters | Stock, ledger, valuation | Pass |
| Finance | Reconciliation, tenant scope, exports | Dashboard reconciliation | Fails to include new workforce ledger |
| Customer storefront | Tenant/security tests partially cover | Full browser checkout still required | Pending exhaustive browser pass |
| Mobile API | Authentication/security coverage | API contract sweep still required | Pending exhaustive API pass |
| Super admin | Module access/client isolation tests | Existing client list/edit checked | Approval/status/details missing |

## Confirmed risks to address

1. Financial reports omit non-legacy production-worker earnings and payments.
2. Worker payment balance checks need transaction locks to prevent concurrent overpayment.
3. Non-legacy assignment duplicate checks need database-backed concurrency protection.
4. Duplicate tailor phones are safely blocked but need a shop/client identifier for usable login.
5. The super-admin delete action is inappropriate as the primary lifecycle control for live client data.

