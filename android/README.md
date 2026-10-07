# BuyNStitch Sales Agent

An Expo React Native Android app isolated inside the Laravel repository's `android/` directory.

## Implemented foundation

- TypeScript and Expo SDK 57.
- Employee username/email and password login through the Laravel sales-agent API.
- Existing/new/walk-in customer modes.
- Whole-set QR, Code 128, EAN-13 and EAN-8 scanning with `expo-camera`.
- Counter-sale item and payment fields.
- Automatic local draft persistence with AsyncStorage.
- Online/offline indication with NetInfo.
- Existing permission identifier `clothing.sales`; no new role or login system.
- Explicit integration boundary: the app can save and forward a draft, but never completes a sale or deducts stock.

## Run on a physical Android phone

1. Install Expo Go on the phone.
2. In this directory run `npm start`.
3. Scan the displayed Expo QR code with the phone.

Camera barcode scanning is included in Expo Go. The phone and computer normally need to be able to reach each other; Expo tunnel mode can be used when local-network discovery is blocked.

## Builds

- First update-enabled test APK: `npm run build:preview`
- Play Store bundle: `eas build --platform android --profile production`

The first EAS build will ask you to sign in and associate this project with your existing Expo account. Do not commit Expo access tokens or Android signing credentials.

## Keep one test installation up to date

The `preview` build is connected to the EAS Update channel named `preview`. Share and
install the APK link from `npm run build:preview` once. For later JavaScript, styling,
and asset changes, publish to that same installed app with:

```bash
npm run update:preview -- --message "Describe the update"
```

The installed app checks for an update when it starts. Fully close and reopen it; a
second restart may be needed after the update downloads. A new APK is only required
when native dependencies, Android permissions/configuration, or the app version/runtime
changes. When that happens, run `npm run build:preview` and share the replacement APK.

## Server integration

Read `docs/BACKEND_CONTRACT.md` before implementing Laravel endpoints. The current `/api/login` is customer PIN authentication; the Sales Agent app must authenticate the existing employee account and enforce `clothing.sales`.
