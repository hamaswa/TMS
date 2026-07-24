# Marketplace payment and catalog QA — 24 July 2026

## Result

The public clothing and tailoring storefronts passed detailed desktop and mobile QA. The live QA workspace now contains realistic catalog, stock, customer, employee, clothing-order, and tailoring-inquiry records.

- Automated suite: **142 tests passed, 1,039 assertions**
- Public clothing products: **9**
- Clothing brands: **8**
- Product colour choices: **17**
- Tailoring services: **5**
- New public clothing orders: **3**
- New public tailoring inquiries: **3**
- Broken images found: **0**
- Horizontal overflow found at 390px mobile width: **0**

## Payment behaviour verified

- **No payment:** the complete order value remains due.
- **Cash on delivery:** available for delivered clothing orders and rejected for pickup orders.
- **EasyPaisa:** sender phone and transaction reference are mandatory. The full amount remains due until the client manually verifies receipt; entering a reference never marks an order paid automatically.
- Tailoring records capture the customer's payment preference. No tailoring payment is posted until the client confirms the work and amount.

The database migration is additive. It adds nullable payment-reference fields and a default payment preference without deleting or rewriting existing customer data.

## Expanded catalog

| Brand | Category |
| --- | --- |
| Gul Ahmed | Wash & Wear |
| J. | Cotton |
| Alkaram Studio | Linen |
| Khaadi | Khaddar |
| Sapphire | Lawn |
| Nishat Linen | Cambric |
| Bonanza Satrangi | Karandi |
| Siddiqui Select | Boski |

Tailoring services cover men's stitching, waistcoats, children's stitching, women's suits, and alteration/fitting.

## Live QA journeys

### Clothing

| Customer | Order | Payment | Fulfilment |
| --- | --- | --- | --- |
| عائشہ خان | `TMSO-20260724-E1HE5Y` | No payment | Pickup |
| عثمان علی | `TMSO-20260724-NJRH2B` | Cash on delivery | Delivery |
| مریم نور | `TMSO-20260724-GPOB67` | EasyPaisa `EPQA-20260724-001` | Pickup |

### Tailoring

| Customer | Inquiry | Service | Payment preference |
| --- | --- | --- | --- |
| عائشہ خان | `TMSI-000002` | Women's simple suit | No payment |
| عثمان علی | `TMSI-000003` | Children's shalwar kameez | Cash on delivery |
| مریم نور | `TMSI-000004` | Alteration/fitting | EasyPaisa `EP-TAILOR-20260724-01` |

## QA credentials

Client employee:

- Username: `zara.marketplace.qa`
- Password: `QaMarketplace@2026`

Customer PIN records:

| Customer | Phone | PIN |
| --- | --- | --- |
| عائشہ خان | `03005550101` | `415263` |
| عثمان علی | `03005550102` | `526374` |
| مریم نور | `03005550103` | `637485` |
| فہد اقبال | `03005550104` | `748596` |

These are local QA records and must not be reused as production credentials.

## Visual evidence

- `01-expanded-clothing-catalog.png` — expanded public clothing catalog
- `02-client-order-queue-payment-methods.png` — client order queue with payment choices
- `03-client-tailoring-inquiries.png` — client tailoring inquiry queue
- `04-mobile-expanded-catalog-full.png` — full mobile clothing catalog
- `05-mobile-tailoring-services-full.png` — full mobile tailoring services page
- `06-mobile-checkout-payment-options-full.png` — mobile checkout payment choices

## Additional defect fixed

The production-worker “previous week” report excluded Friday records because its range ended at the start of Friday. It now uses the complete Saturday-to-Friday period and includes Friday through the end of the day.
