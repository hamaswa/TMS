# Sales Agent API boundary

This React Native app must reuse the existing employee `User` account and the existing `clothing.sales` permission. It must not introduce a separate sales-agent account, role, or permission.

The current Laravel `/api/login` endpoint authenticates customers using shop, phone, and PIN. It is not suitable for employee authentication and must not be reused for this app.

## Required server capabilities

1. Securely issue a mobile token for the existing employee account after the authentication design is reviewed.
2. Search customers inside the authenticated business only.
3. Resolve a whole-set QR code to current tenant inventory, availability, brand, cloth type, optional/default color, price and rack.
4. Idempotently create or update a sale draft using a client UUID and revision.
5. Return HTTP 409 with the current server draft when the supplied revision is stale.
6. Complete a sale through the same atomic domain workflow used by the web counter sale, including stock deduction, transaction and receipt creation.
7. Optionally leave a draft for dashboard completion and notify authorized dashboard users.

## QR payload

The QR contains only an opaque, non-sequential set code such as `BNS-S-7F3K9Q`. Price, shop ID and stock quantities must be resolved and validated by the server rather than trusted from the QR or mobile form.

## Current boundary

QR capture and durable local draft autosave are implemented. Server lookup, continuous sync, completion, receipt creation and dashboard notifications intentionally report that the API is not connected instead of showing false success.
