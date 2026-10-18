# TMS — Department, Category & Unit-Aware Inventory Execution Plan

**Status:** Planning complete; implementation not started  
**Date:** 2026-10-18  
**Repository:** `hamaswa/TMS`  
**Working branch:** `feature/inventory-product-classification` (from `master`)  
**Scope:** Existing TMS purchasing, inventory, POS/sales, reporting and administration  
**Rule:** No merge to `master` and no deployment without owner approval.

## 1. Product objective and non-negotiable decisions

A textile/clothing shop owner must be able to classify products by **Department → Category → Product**, record all incoming stock through the **existing Purchase workflow**, and compare inventory, purchases, sales and profitability by department/category. Larger shops must be able to add their own departments and categories.

**Confirmed decisions**
- Departments are shop-owner-managed, not hard-coded genders. Seed/sample departments can be Gents, Ladies, Home Textiles; owners may add more.
- Categories belong to departments and are owner-managed. Material/fabric **Cloth Type** is a distinct attribute and must not be confused with category.
- **Purchase is the primary product creation and stock-entry interface.** Do not build a competing inventory creation flow. Preserve new-product and existing-product purchase lines, suppliers, colors, prices, QR codes, payments, draft/receive workflow.
- **Draft** purchases do not add stock. **Receive** posts stock once; protect against double posting.
- Units must be explicit: **metre**, **suit**, **piece**, **set** (extendable only after checking downstream assumptions). A 3-piece ladies suit purchased as 20 suits increases stock by **20 suits**, not 60 pieces or an invented metre quantity. Suit composition is descriptive metadata, not an automatic stock multiplier.
- Preserve existing data, transactions, and legacy metre-based behavior; never silently reinterpret historic quantities.
- Inventory is primarily for browsing, filtering, managing and auditing existing products. Review any existing standalone inventory-create route before hiding/removing it.
- Urdu/RTL is the existing purchase UI language. Maintain language, accessible controls and mobile responsiveness.

## 2. Existing implementation: verified entry points and risks

Reviewed on `master`:
- `app/Http/Controllers/PurchaseController.php`: `create()` loads suppliers, existing cloths/colors, brands, types and catalog; `store()` accepts existing color lines and `new_sets`; creates Cloth, ClothColor, PurchaseItem in a transaction. New sets currently require `default_sale_length`, `suit_sale_price`, and calculate sale price per metre. Receiving posts stock. The flow is **not yet unit-neutral**.
- `resources/views/purchases/create.blade.php`: purchase header, line-item sheet, new/existing product choice, colors, QR, payment summary. This is the primary UX to extend, not replace.
- `app/Models/Cloth.php`, `app/Models/ClothType.php`: current catalog lacks a department/category hierarchy.
- `app/Http/Controllers/ClothController.php`, `resources/views/cloths/index.blade.php`: inventory list filters by cloth type/brand/etc.; department/category and unit-aware summaries absent.
- `app/Services/FinancialReportService.php`: existing aggregate reporting; category breakdown absent.
- `app/Http/Controllers/InventoryLedgerController.php` and `app/Services/InventoryService.php`: review for unit semantics and stock movements before implementation.
- Purchase and stock schema currently stores `quantity` and cost without explicit per-line stock unit. Existing UI/messages use metres.

**Mandatory further audit before code changes:** inspect all purchase receive/cancel/return/edit paths; POS/manual/online sales, returns, adjustments, stock valuation, labels/QR, exports, reports, stock ledger, permissions, migrations, and JS form serialization. Record the exact impacted files and compatibility plan in the progress log.

## 3. Target domain model

| Entity / field | Purpose | Rules |
| --- | --- | --- |
| `departments` | Owner-scoped top-level group | `user_id`, name, optional translated names, display order, active flag; unique normalized name per owner |
| `product_categories` | Owner-scoped child of department | `user_id`, `department_id`, name, display order, active; verify same owner on every write |
| `cloths.department_id` / `category_id` | Classifies existing product | Category must belong to selected department and owner; legacy rows may initially remain unclassified |
| `cloths.stock_unit` | `metre`, `suit`, `piece`, `set` | Never change on stocked/transacted product without a documented conversion workflow |
| `cloths.pieces_per_suit` | Optional composition metadata | Applicable to suit products; not a stock multiplier |
| `purchase_items` unit snapshot | Unit as purchased | Needed to interpret historic and future transaction quantities accurately; audit schema first |
| Sales/ledger unit snapshot | Unit as sold/moved | Prefer explicit or reliably resolved immutable unit; audit all consumers first |

Keep existing Cloth/ClothColor and product identifiers to preserve sales history, QR codes, purchase lines, and stock links. Do not invent a separate product catalog in parallel.

**Historical-data policy:** default legacy products to `metre` only if verified by schema and transaction semantics; otherwise use a safe `legacy/unclassified` state and a controlled classification UI. Do not auto-assign historical Gents/Ladies or categories by product name. Never rewrite historical purchase costs or quantities as part of classification.

**Integrity:** database indexes and foreign keys where feasible; owner scoping on department/category/product; prevent cross-owner assignment; use deactivation rather than destructive deletion when referenced.

## 4. UI/UX blueprints (wireframes; not implemented)

### A. Department & category management — Admin

```text
┌───────────────────────────────────────────────────────────────┐
│ Departments & Categories                        [+ Department]│
├─────────────────────┬─────────────────────────────────────────┤
│ Gents           [⋮]│ GENTS                                   │
│ Ladies          [⋮]│ [+ Add Category]                        │
│ Home Textiles   [⋮]│ Unstitched Fabric     Edit  Deactivate  │
│                     │ Ready-made Suits     Edit  Deactivate  │
│                     │ Waistcoats           Edit  Deactivate  │
└─────────────────────┴─────────────────────────────────────────┘
```

Small screens: stacked department selector and category list. Empty states explain how to start. Owner may create/rename/reorder/deactivate. Existing classified products remain intact.

### B. Purchase form — existing screen, extended

```text
┌────────────────────────────────────────────────────────────────┐
│ New Purchase   Supplier [____]  Date [____] Invoice [____]    │
├────────────────────────────────────────────────────────────────┤
│ Purchased Products                          [+ Add Product]    │
│ Row 1: [Existing Product ▼]  Search/QR [____________]          │
│        Current unit: metre    Qty [____] Cost [____]            │
│ Row 2: [New Product ▼]                                       │
│        Department [Ladies ▼] Category [Unstitched Suits ▼]     │
│        Product [Sapphire Lawn] Brand [___] Cloth Type [___]    │
│        Unit [Suit ▼]  Pieces per suit [3]                      │
│        Colors/variants [optional] Qty [20]                     │
│        Total purchase cost [____] Sale price per suit [____]   │
│        (No compulsory metre length for suit-stock products)    │
├────────────────────────────────────────────────────────────────┤
│ Purchase Total [____] Payment [____]                           │
│ [Save Draft] [Receive Purchase & Update Inventory]             │
└────────────────────────────────────────────────────────────────┘
```

New/existing choice stays prominent. Selecting an existing product loads its fixed department/category/unit; quantity and price fields use that unit. Category list depends on department. Allow inline create of department/category only if safe and permissions permit; otherwise a quick management link. Preserve unsaved purchase rows on validation failure. On mobile use vertically stacked rows, never force wide horizontal scrolling for essential actions.

### C. Inventory list

```text
Inventory    [Department: All ▼] [Category: All ▼] [Unit: All ▼]
[Search products] [Brand ▼] [Stock ▼]
Product                  Department / Category       Stock
Sapphire Lawn 3-piece    Ladies / Unstitched Suits    20 suits
Dubai Abaya              Ladies / Abayas              12 pieces
Cotton Fabric            Gents / Unstitched Fabric    85 metres
```

Show **separate totals by unit**; never sum 20 suits + 12 pieces + 85 metres as one meaningful stock quantity. Keep existing brand/color filters.

### D. Business reporting

```text
Sales & Purchases by Department   [Date Range] [Department ▼]
Department       Purchases      Sales       COGS      Gross Profit
Gents            Rs ...         Rs ...      Rs ...    Rs ...
Ladies           Rs ...         Rs ...      Rs ...    Rs ...
Home Textiles    Rs ...         Rs ...      Rs ...    Rs ...
[Expand Ladies → Suits / Abayas / ...]
```

Financial numbers are monetary and comparable across units; stock quantities are not. Define sales attribution, purchase returns, sales returns, discounts, taxes and COGS consistently with existing financial reports. Gross profit = net sales − recognized COGS, **not** sales minus purchases in period.

**Image assets:** These text wireframes are canonical until PNGs are prepared and checked against existing Blade styling. Proposed image destinations: `docs/inventory-classification/ui/departments.png`, `purchase-product-entry.png`, `inventory-filters.png`, `department-report.png`. Do not mark PNG deliverables complete until committed and reviewed. No mockup should be mistaken for a working UI.

## 5. Execution phases and acceptance criteria

Mark tasks `[x]` only after code is committed and validated. Include commit SHAs in progress log.

### P0 — Audit and design lock  [ ]
- [ ] Map all schema/model/controller/view/JS paths for purchase, receive, returns, inventory, POS/sales, ledger and financial reports.
- [ ] Confirm exact current stock-unit semantics and migration/backfill strategy; list edge cases.
- [ ] Capture screenshots/reference of existing Purchase page and confirm layout direction.
- [ ] Confirm whether mixed-unit products can coexist in same purchase and same sale.
- **Gate:** No schema/code work until a written impact matrix and compatibility strategy exist.

### P1 — Owner-scoped departments & categories  [ ]
- [ ] Add migrations, models, relationships, validation and ownership checks.
- [ ] Add CRUD/deactivation, list/detail pages, navigation, localized labels and responsive layouts.
- [ ] Seed defaults only for new shops or via explicit safe action; no duplicate defaults.
- [ ] Ensure referenced categories cannot be destructively removed.
- **Acceptance:** Two shop owners cannot see/assign each other's departments. Owners can manage categories without touching inventory.

### P2 — Unit-aware product model and migration  [ ]
- [ ] Add classification and unit fields to existing Cloth model, with safe backfill.
- [ ] Add optional suit composition and transaction-unit handling as indicated by P0 audit.
- [ ] Make stock, price and cost calculation services unit-aware; retain legacy metre calculations.
- [ ] Protect stocked products from unsafe unit changes.
- **Acceptance:** 20 three-piece suits remain 20 suits; 85 metres remain 85 metres. Historical records and QR links remain valid.

### P3 — Purchase-first product entry  [ ]
- [ ] Extend **existing** `purchases.create` new-product row with department/category/unit/composition.
- [ ] Existing-product row shows classification/unit and accepts restock in same unit.
- [ ] Adjust backend validation, product creation, per-color allocations, pricing and purchase-item storage.
- [ ] Preserve draft, receive, payment, supplier, invoice, color, image, QR and error-recovery behavior.
- [ ] Validate idempotent receive and correct stock ledger/valuation.
- **Acceptance:** Receive a 20-suit purchase → +20 suits exactly once; draft → +0; restock same SKU → adds quantity; existing metre purchases still work.

### P4 — Inventory and sales integration  [ ]
- [ ] Department/category/unit filters and badges on inventory page; separate unit totals.
- [ ] Review inventory details, adjustments, returns and valuation.
- [ ] Make POS/manual/online sale quantity and price labels unit-aware; protect against overselling.
- [ ] Preserve transaction snapshots and support returns/reversals correctly.
- [ ] Review/hide standalone inventory creation only when all callers are understood and safe.
- **Acceptance:** Selling 2 of 20 suits leaves 18 suits; returns restore correct unit; Gents/Ladies filters work.

### P5 — Purchases, sales and profitability reporting  [ ]
- [ ] Department/category breakdown of purchase value, net sales, recognized COGS and gross profit.
- [ ] Add period, department and category filters; ensure totals reconcile to existing financial report.
- [ ] Separate unclassified legacy products, include returns/cancellations appropriately.
- [ ] Unit-specific stock count and inventory valuation; no mixed-unit summed quantity.
- **Acceptance:** Category subtotals reconcile to department totals; department totals + unclassified reconcile to overall reports; profit uses COGS.

### P6 — UX polish, QA and handoff  [ ]
- [ ] Validate desktop, tablet, mobile and RTL purchase forms; error states and keyboard flow.
- [ ] Verify cross-owner isolation, invalid category/department, inactive category, mixed units, duplicate receive, returns, rounding, old records, QR and purchase payment.
- [ ] Run focused automated checks and manual QA on development environment, not production.
- [ ] Add final PNG UI references if prepared, update documentation and record screenshots.
- [ ] Prepare PR and a concise operator-facing release note; request owner approval before merge.
- **Acceptance:** Owner can perform a full new Ladies suit purchase → receive → inventory → sale → department report journey without separate inventory creation.

## 6. Progress tracker

| Phase | State | Commit / PR | Verification | Notes |
| --- | --- | --- | --- | --- |
| P0 Audit | Not started | — | — | Must precede implementation |
| P1 Departments/categories | Not started | — | — | |
| P2 Units/data model | Not started | — | — | |
| P3 Purchase form | Not started | — | — | |
| P4 Inventory/sales | Not started | — | — | |
| P5 Reports | Not started | — | — | |
| P6 QA/handoff | Not started | — | — | |

### Change log
- **2026-10-18:** Initial plan written on feature branch. Scope and purchase-first design documented. **No application feature code changed.**

## 7. Working agreement

1. Start each phase by checking this document and latest feature-branch state.
2. Make cohesive commits; update task checkboxes, phase status, relevant file list, commit SHA, and verification results in this file as work progresses.
3. Do not claim a UI change is done merely because backend schema exists; demonstrate the real purchase/inventory user journey.
4. Report blockers/uncertainties instead of silently changing product semantics.
5. Do not deploy or merge to `master` without explicit approval.
