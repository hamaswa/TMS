# BuyNStitch Shop Hub

## Purpose

The Shop Hub is the authoritative Laravel instance running on the shop LAN. When the internet or BuyNStitch cloud is unavailable, every authorized dashboard browser and sales-agent Android device uses this same instance and database. Devices do not synchronize directly with one another.

## Required deployment shape

1. Use an always-on shop computer connected to the shop router.
2. Run the normal TMS Laravel application and its database on that computer.
3. Give the computer a reserved LAN address in the router, for example `192.168.1.10`.
4. Configure the local `.env`:

   ```dotenv
   APP_URL=http://192.168.1.10:8010
   SHOP_HUB_MODE=hub
   SHOP_HUB_ID=shop-<permanent-business-identifier>
   SHOP_HUB_NAME="Main Counter Hub"
   SHOP_HUB_CLOUD_URL=https://buynstitch.com
   ```

5. Run migrations and expose the Laravel server to the LAN. Production use requires a supervised web server; `php artisan serve --host=0.0.0.0` is for controlled QA only.
6. Open the dashboard using the hub address on shop computers.
7. On each sales-agent phone, enter the same hub address on the login screen and use the existing employee login. Existing roles and `clothing.sales` permission are enforced by the hub.

The Android build explicitly permits cleartext LAN traffic so a shop can use a private `http://192.168.x.x` address. Treat the shop Wi-Fi as controlled infrastructure, use a strong WPA2/WPA3 password, and move the hub to locally trusted HTTPS where the deployment environment supports certificate provisioning.

## Connection states

- `Cloud online`: the device is using the configured cloud API.
- `Shop connected`: the device is using the shared LAN hub. Other authorized shop users see changes through the same local database.
- `Isolated`: neither the selected hub nor cloud API is reachable. The phone retains its local draft but does not claim that other devices have received it.

## Consistency rules in the first slice

- Sale sessions retain their UUID and monotonic revision.
- Stale writes receive a conflict instead of silently replacing another user's changes.
- Claim, forward, and completion transitions increment the revision.
- Completion and stock deduction remain transactional in `CounterSaleService`.
- Hub actions are appended to `shop_hub_events` with event UUID, device ID, actor, aggregate UUID, base revision, resulting revision, and full serialized session payload.
- Repeated event UUIDs are idempotent in the journal.
- The web Sales Inbox reads the same hub database, so agent drafts and status changes are immediately visible on the LAN.

## Current delivery boundary

The scheduler runs `php artisan shop-hub:sync` every minute. When the internet returns, it signs an ordered batch and posts it to the cloud relay. The cloud applies each event once, preserves revision ordering, and returns `conflict` rather than overwriting a newer cloud revision. The hub badge remains visible until events are either synchronized or marked as conflicts requiring attention.

The hub and cloud must share a strong, randomly generated `SHOP_HUB_SYNC_KEY`. This first relay supports sale-session save, forward, claim, and completion events. Other offline business aggregates must be added to the same event contract before they can be described as cloud-synchronized.
