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

Next starting point.

- [ ] Move public storefront text, validation messages, checkout messages, tracking messages, and errors into Laravel language files.
- [ ] Add a visible **اردو | English** language switch to every public page.
- [ ] Keep Urdu as the current default, allow every client to select the storefront default, and persist each visitor’s choice.
- [ ] Keep the super administrator interface English.
- [ ] Ensure `lang`, `dir`, typography, icons, number formatting, and form alignment change correctly between Urdu RTL and English LTR.
- [ ] Add automated locale and browser layout tests.

## Phase 3 — accessibility and Pakistan identity handling

- [ ] Add labels to public search, colour, and other filter controls.
- [ ] Increase mobile login/action touch targets to at least 44 × 44 pixels.
- [ ] Add semantic headings to legacy sales, tailor, expense, and daily-expense screens.
- [ ] Introduce Pakistan phone normalization for `03xx...` and `+92...`.
- [ ] Store a canonical phone value and safely migrate/deduplicate existing values.
- [ ] Add customer self-registration/guest checkout using PIN, rate limiting, and account linking.

## Phase 4 — public commerce quality

- [ ] Add city, category, price, delivery, and availability discovery filters.
- [ ] Add SEO titles/descriptions, canonical URLs, Open Graph cards, and LocalBusiness/Product structured data.
- [ ] Centralize currency formatting and provide consistent Urdu/English PKR labels.
- [ ] Add customer-visible payment verification status and payment-history details.
- [ ] Add return, exchange, refund, and paid-order cancellation workflows.
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
