# TMS Recommendation Implementation Roadmap

Last updated: 24 July 2026
Branch: `feature/client-public-storefronts`

This file is the durable continuation point for the QA recommendations. When work resumes with “go next”, start at the first unchecked phase and preserve existing production data through additive migrations and backward-compatible defaults.

## Phase 1 — production blockers

- [x] Block new tailoring orders when a selected measurement template has missing required system or custom measurements.
- [x] Preserve legacy orders by enforcing the rule only for configured templates.
- [x] Give clothing sales roles access to the online-order queue without giving storefront-settings access.
- [x] Add the online-order queue to the salesperson’s clothing navigation.
- [x] Remove the insecure public `/new-tab` redirect.
- [x] Add EasyPaisa verification states for storefront orders and tailoring inquiries.
- [x] Store verifier identity, decision time, rejection time, and notes.
- [x] Post a verified clothing payment to the unified customer transaction exactly once under database locking.
- [x] Prevent completion of an EasyPaisa order before verification.
- [x] Prevent cancellation of a received-payment order until a refund workflow exists.
- [x] Record verification actions through the existing client activity log.
- [x] Add regression coverage and apply the additive migration.

## Phase 2 — bilingual public experience

- [x] Move public storefront text, validation messages, checkout messages, tracking messages, and errors into Laravel language files.
- [x] Add a visible **اردو | English** language switch to every public page.
- [x] Keep Urdu as the current default, allow every client to select the storefront default, and persist each visitor’s choice.
- [x] Keep the super administrator interface English.
- [x] Ensure `lang`, `dir`, typography, icons, number formatting, and form alignment change correctly between Urdu RTL and English LTR.
- [x] Add automated locale tests and complete desktop/mobile browser layout QA.

## Phase 3 — accessibility and Pakistan identity handling

- [x] Add labels to public search, colour, date-range, and other filter controls.
- [x] Increase mobile login and public-action touch targets to at least 44 × 44 pixels.
- [x] Add semantic headings to legacy sales, tailor, expense, and daily-expense screens.
- [x] Introduce Pakistan phone normalization for `03xx...`, `+92...`, `0092...`, spacing, punctuation, and Urdu/Arabic digits.
- [x] Store a canonical phone value, preserve the entered value, prevent format-based duplicates, and safely flag ambiguous legacy conflicts without merging customer records.
- [x] Add customer self-registration/guest checkout using PIN, rate limiting, and account linking.

## Phase 4 — public commerce quality

- [x] Add city, category, price, delivery, and availability discovery filters.
- [x] Add SEO titles/descriptions, canonical URLs, Open Graph cards, and LocalBusiness/Product structured data.
- [x] Centralize currency formatting and provide consistent Urdu/English PKR labels.
- [x] Let each client independently choose catalogue-only publishing, online ordering, and accepted manual payment methods.
- [x] Add customer-visible payment verification status and payment-history details.
- [x] Add full verified-payment refund and paid-order cancellation with stock and cash-ledger reversal.
- [x] Add item-level partial return, exchange, and replacement workflows.
- [ ] Add configurable A4 and 80 mm print templates, controlled page breaks, and order QR codes.

## Phase 5 — Pakistan payments and delivery

- [ ] Extend the verified manual-payment workflow to JazzCash, bank transfer, and Raast QR.
- [ ] Add payment evidence uploads with private storage and access control.
- [ ] Add settlement and daily payment-method reconciliation.
- [ ] Add province, district, tehsil, city, and delivery-area fields.
- [ ] Add configurable delivery charges and courier/tracking support.
- [ ] Add COD settlement reconciliation.

## Phase 6 — tailoring and inventory growth

- [ ] Add design/photo attachments, fitting appointments, home-measurement requests, and pre-cutting approval.
- [ ] Add alteration and rework workflows.
- [ ] Add cloth roll/lot tracking and metre/yard/suit units.
- [ ] Add barcode/QR labels, stock transfers, branches, and minimum-stock alerts.
- [ ] Add product variants and richer catalogue media.

## Phase 7 — reliability and maintenance

- [ ] Remove stale external HTTP print assets and unused starter views.
- [ ] Remove commented debug statements from legacy controllers.
- [ ] Upgrade deprecated Sass imports/APIs and refresh Browserslist data.
- [ ] Add production configuration checks for `APP_DEBUG=false`.
- [ ] Add encrypted backup, restore drill, error monitoring, audit alerts, and export procedures.
- [ ] Run the externally connected Composer advisory audit when network permission is available.

## Verification baseline

At the end of Phase 1:

- Full suite: 148 tests passed, 1,097 assertions.
- Focused P1 suite: 40 tests passed, 351 assertions.
- Database migration applied without deleting or replacing existing records.
- Browser QA confirmed the tailoring inquiry verification UI and salesperson online-order access/navigation.

At the end of Phase 2:

- Full suite: 150 tests passed, 1,126 assertions.
- Focused storefront suite: 33 tests passed, 296 assertions.
- Additive `default_locale` migration applied without deleting or replacing existing records.
- Browser QA confirmed Urdu RTL and English LTR on the marketplace, combined storefront, clothing catalogue, cart, and tailoring inquiry flow.
- Responsive QA confirmed no horizontal overflow at desktop or 390 px mobile width, with no browser console errors on the tested pages.

At the Phase 3 accessibility checkpoint:

- Full suite: 152 tests passed, 1,145 assertions.
- Public marketplace browser QA confirmed 44 px language controls, 44 px navigation actions, and 45 px shop actions at 390 px width.
- Browser QA confirmed correct Urdu RTL semantics, one page-level heading, no horizontal overflow, and no console errors.
- No database migration or client-data update was required for this checkpoint.

At the Phase 3 phone identity checkpoint:

- Full suite: 165 tests passed, 1,166 assertions.
- Focused phone/storefront suite: 35 tests passed, 164 assertions.
- Additive canonical-phone migration applied to 20 existing customers; all 20 normalized successfully and no conflicts were found.
- Original phone values were preserved. The database now prevents a second customer under the same client from using an equivalent local/international phone identity.
- If a future database contains a legacy formatting collision, records are flagged rather than merged and PIN login refuses the ambiguous identity.
- Browser QA confirmed the Urdu public phone field uses a telephone control, accepts both documented formats, remains 47 px high, has no overflow, and produces no console errors.

At the completed Phase 3 customer registration checkpoint:

- Full suite: 169 tests passed, 1,197 assertions.
- Focused cart identity suite: 9 tests passed, 66 assertions.
- An additive migration introduced self-registration and phone-verification timestamps without replacing or deleting customer data.
- Public registration creates the same client-owned customer record used by clothing and tailoring, hashes the PIN, links the cart atomically, rejects duplicate phone identities and weak PINs, and rate-limits repeated attempts.
- A self-registered customer can proceed through checkout into the existing unified customer balance; no payment is falsely recorded.
- Browser QA confirmed bilingual Urdu RTL and English LTR forms, clear no-OTP/unverified-phone messaging, 47 px mobile inputs, no horizontal overflow at 390 px, weak-PIN feedback, and successful cart linking.

At the Phase 4 public discovery checkpoint:

- Full suite: 172 tests passed, 1,225 assertions.
- Focused discovery suite: 3 tests passed, 28 assertions; combined storefront regression suite: 22 tests passed, 167 assertions.
- Marketplace discovery now combines shop text, city, clothing/tailoring category, and home-delivery filters while keeping inactive or unpublished businesses hidden.
- Clothing catalogues now combine fabric type, colour, minimum/maximum price, and live availability filters. “In stock” subtracts active cart reservations and automatically restores availability after expiry.
- Filter values are tenant-scoped, validated, retained across pagination, and translated in Urdu and English. No database migration or existing-data update was required.
- Browser QA confirmed English LTR and Urdu RTL results, complete desktop labels, 44–47 px controls, and no horizontal overflow at 375 px mobile width.

At the Phase 4 SEO and structured-data checkpoint:

- Full suite: 176 tests passed, 1,273 assertions.
- Focused metadata and checkout privacy suite: 17 tests passed, 193 assertions.
- Public marketplace, shop, catalogue, product, tailoring catalogue, and service pages now provide localized descriptions, clean canonical URLs, Open Graph/Twitter cards, Pakistan locales, and safe fallback share images.
- JSON-LD now describes the marketplace as a `WebSite`, shops as `LocalBusiness`, catalogues as `CollectionPage`, fabrics as `Product` with PKR offers and live availability, and tailoring work as `Service`.
- Filter query strings canonicalize to the underlying public page to avoid duplicate search URLs.
- Cart, order-tracking, and client-preview pages are `noindex,nofollow`. They publish no structured data, and private customer, address, payment, note, and order-reference values are excluded from the document head.
- Browser source QA confirmed parseable schemas, correct `en_PK`/`ur_PK` Open Graph locales, clean product canonicals, live `InStock` data, and cart privacy protection.
- No database migration or existing-data update was required.

At the Phase 4 Pakistan currency checkpoint:

- Full suite: 178 tests passed, 1,283 assertions.
- Focused currency, catalogue, cart, checkout, and tailoring suite: 32 tests passed, 285 assertions.
- Public clothing, cart, order, and tailoring prices now use one fixed-Pakistan formatter: `Rs 1,450.00` in English and `1,450.00 روپے` in Urdu.
- Currency amounts use bidirectional isolation and non-wrapping presentation, while Product and Service structured data retains ISO `PKR` values.
- Browser QA confirmed English LTR and Urdu RTL output, correct cart unit/line/total amounts, five live tailoring prices, no horizontal overflow at 390 px or desktop widths, and no console errors.
- No database migration or existing client-data update was required.

At the Phase 4 client commerce-control checkpoint:

- Full suite: 181 tests passed, 1,310 assertions.
- Focused storefront foundation, cart, and checkout suite: 32 tests passed, 302 assertions.
- Clients can keep clothing pages as a public catalogue without exposing a cart, reservation, customer-registration, or checkout flow.
- Online ordering is independently opt-in, and clients choose whether to accept unpaid orders, COD, or manually verified EasyPaisa references.
- Disabled ordering and payment methods are enforced by the server, not only hidden in the interface. Existing secure order-tracking links remain available if a client later pauses new orders.
- The settings explicitly explain that the current EasyPaisa reference flow is manual verification and is not a live payment gateway.
- The additive migration retained existing behavior through compatibility defaults and did not delete or rewrite customer, product, order, or payment records.
- Browser QA confirmed the Urdu client settings, 66 px mobile payment controls, public ordering controls, 390 px overflow safety, and no console errors.

At the Phase 4 customer payment-visibility checkpoint:

- Full suite: 181 tests passed, 1,329 assertions.
- Focused checkout and private-metadata suite: 19 tests passed, 230 assertions.
- Authenticated customers now see the payment method, verification status, amount verified against the online order, remaining order amount, and a timestamped payment timeline.
- Pending, verified, rejected, pay-later, COD, and cancelled-order records use the same bilingual summary without implying that a general unified-account payment belongs only to one order.
- The page does not expose internal verification notes or staff identities, and unauthenticated visitors cannot see the payment summary or item details.
- Browser QA confirmed a real EasyPaisa-pending order in Urdu RTL and English LTR, `noindex,nofollow` privacy, one-column 390 px payment cards, logical-direction timelines, no horizontal overflow, and no console errors.
- Mobile order-summary label spacing was corrected during visual QA. No migration or existing-data update was required.

At the Phase 4 audited paid-order refund checkpoint:

- Full suite: 182 tests passed, 1,372 assertions.
- Focused checkout/refund suite: 16 tests passed, 231 assertions.
- A paid pending order can only be cancelled through an explicit full-refund form. The client records cash, EasyPaisa, bank-transfer, or Raast refund method; non-cash refunds require a provider reference.
- Database locking, a one-refund-per-order database constraint, and order-state validation prevent simultaneous or repeated refunds. Stock is restored once, the received-cash ledger receives an equal negative entry, and the historical paid amount remains auditable.
- Customers see the refund amount, safe TMS refund reference, method, provider reference, and timestamp in their secure bilingual payment history. Internal notes and staff identity remain private.
- The schema migration was additive and did not delete or rewrite existing customer, order, inventory, or payment data.
- Browser QA completed a real EasyPaisa QA order from verification through refund/cancellation, confirmed the Urdu client form and English customer history, and left the secure customer refund record open for review.

At the Phase 4 item-level return and exchange checkpoint:

- Full suite: 185 tests passed, 1,439 assertions.
- Focused checkout, return, refund, and exchange suite: 19 tests passed, 298 assertions.
- Clients can return part of a fabric line for a payment refund or unified-balance credit, or exchange the same quantity for another colour of the same fabric.
- The client explicitly marks returned fabric as resellable or damaged. Only resellable quantities return to stock; exchanges issue the replacement colour through a separate inventory movement.
- Order, item, return rows, and affected colour stocks are locked in deterministic order. Cumulative returned quantity cannot exceed the original quantity, cross-client items/colours are rejected, and pending EasyPaisa claims must be verified first.
- Orders with partial activity cannot later use full cancellation or full refund, preventing double stock restoration or duplicated financial reversals.
- Financial reports subtract return revenue in the return period and reverse cost only when returned fabric was restocked. Inventory-ledger filters now expose storefront sale, cancellation, return, and exchange movements.
- Customers see safe bilingual return/exchange references, quantities, replacement colours, refund methods, balance credits, and timestamps. Internal notes and staff identity remain private.
- The additive migration introduced only return headers and line records. A failed first migration attempt caused by MySQL's identifier-length limit created two empty tables; both were verified empty, removed, recreated with a short explicit index name, and the migration then completed successfully without touching existing data.
- Browser QA completed a real 0.50-metre COD colour exchange for Usman Ali, confirmed the Urdu client history, English and Urdu customer histories, and the linked stock return/issue entries. The filtered client order is left open for review.

Next starting point: add configurable A4 and 80 mm print templates, controlled page breaks, and order QR codes.
