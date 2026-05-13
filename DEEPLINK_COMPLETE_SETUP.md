# Deep Link Setup Guide - My Party App

## 🎯 Overview

This is a complete **Android/iOS App Deep Link** system with automatic web fallback. When users click a deep link:

1. **If app is installed** → App opens directly with the room ID
2. **If app is not installed** → Beautiful landing page appears, then redirects to Play Store/App Store

## 🔗 Deep Link Format

```
https://mypartychat.site/live?roomId=UaNPfaBOIY
```

## 📁 Files Created

| File | Purpose |
|------|---------|
| `live.php` | Main deep link handler with web fallback |
| `.well-known/assetlinks.json` | Android App Link verification |
| `.well-known/index.php` | Serves assetlinks.json from tools |
| `.well-known/.htaccess` | Apache rules for .well-known |
| `.htaccess.live` | Optional routing rules for /live |

## ⚙️ Setup Steps

### 1. **Update App Store IDs**

Edit `live.php` and replace:

```php
$app_store_id = "YOUR_APP_STORE_ID"; // iOS app ID
$android_package = "com.chat.myparty"; // Android package
```

Find your values:
- **iOS**: Open App Store → My Party → Get "App ID" from URL: `https://apps.apple.com/app/id{ID}`
- **Android**: Use your package name from AndroidManifest.xml

### 2. **Android Configuration**

Add this to your `AndroidManifest.xml`:

```xml
<intent-filter android:autoVerify="true">
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />

    <data
        android:scheme="https"
        android:host="mypartychat.site"
        android:pathPrefix="/live" />
</intent-filter>
```

### 3. **Verify assetlinks.json**

Check that this URL returns **raw JSON only** (no HTML):

```
https://mypartychat.site/.well-known/assetlinks.json
```

⚠️ **Important**: If you see HTML or redirect, Android App Links won't verify!

### 4. **Deep Link URL Scheme (Android)**

Add to AndroidManifest.xml for scheme-based deep linking:

```xml
<intent-filter>
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />

    <data
        android:scheme="myparty"
        android:host="live" />
</intent-filter>
```

### 5. **Flutter/Native App Handler**

**Flutter Example:**

```dart
void handleDeepLink(Uri uri) {
  // HTTPS deep link: https://mypartychat.site/live?roomId=...
  if (uri.host == "mypartychat.site" && uri.path == "/live") {
    final roomId = uri.queryParameters["roomId"];
    if (roomId != null) {
      Get.to(() => LiveAudioRoomScreen(roomId: roomId));
    }
  }
  
  // Scheme deep link: myparty://live?roomId=...
  if (uri.scheme == "myparty" && uri.host == "live") {
    final roomId = uri.queryParameters["roomId"];
    if (roomId != null) {
      Get.to(() => LiveAudioRoomScreen(roomId: roomId));
    }
  }
}
```

## 🧪 Testing

### Test on Android

1. **With App Installed**:
   ```bash
   adb shell am start -a android.intent.action.VIEW \
     -d "https://mypartychat.site/live?roomId=test123"
   ```

2. **Without App** (browser):
   - Visit: `https://mypartychat.site/live?roomId=test123`
   - Should redirect to Play Store

### Test on iOS

1. **With App**:
   - Visit: `https://mypartychat.site/live?roomId=test123`
   - Should open app

2. **Without App**:
   - Visit: `https://mypartychat.site/live?roomId=test123`
   - Should show landing page, then redirect to App Store

### Test on Browser (Desktop)

- Visit: `https://mypartychat.site/live?roomId=test123`
- Should show landing page with "Continue on Website" button

## 📊 How It Works

```
┌─────────────────────────────────────────────────────────┐
│  User clicks: https://mypartychat.site/live?roomId=... │
└──────────────────────┬──────────────────────────────────┘
                       │
                ┌──────▼──────┐
                │  Browser    │
                └──────┬──────┘
                       │
            ┌──────────┴──────────┐
            │                     │
      ┌─────▼─────┐        ┌─────▼──────┐
      │  App Link │        │   Browser  │
      │ Intercept │        │   Fallback │
      └──────┬────┘        └─────┬──────┘
             │                   │
        ┌────▼────┐          ┌───▼────────┐
        │   App   │          │ Landing    │
        │  Opens  │          │   Page     │
        └─────────┘          └────┬───────┘
                                  │
                        ┌─────────┴─────────┐
                        │                   │
                   ┌────▼──┐          ┌────▼──┐
                   │ Play  │          │  App  │
                   │ Store │          │ Store │
                   └───────┘          └───────┘
```

## 🔐 Security

- ✅ App Link verification via assetlinks.json
- ✅ HTTPS only (no plain HTTP)
- ✅ Certificate fingerprint validation
- ✅ Proper CORS headers set
- ✅ Prevents unauthorized URL handlers

## 📝 URL Parameters

**Current supported parameter**:
- `roomId` - The room ID to join

**Example**:
```
https://mypartychat.site/live?roomId=abc123xyz789
```

## 🐛 Troubleshooting

### Android App Link not opening
1. Verify `assetlinks.json` is accessible at `/.well-known/assetlinks.json`
2. Check certificate fingerprint matches your signing key
3. Ensure AndroidManifest includes `android:autoVerify="true"`
4. Test with: `adb shell am start -d "https://mypartychat.site/live?roomId=test"`

### assetlinks.json shows HTML instead of JSON
1. Check `.well-known/.htaccess` rules
2. Verify server returns `Content-Type: application/json`
3. Make sure no redirects are happening

### iOS not opening app
1. Add to `Info.plist`:
   ```xml
   <key>NSBonjourServices</key>
   <array>
       <string>_myparty._tcp</string>
   </array>
   ```
2. Verify URL scheme in Info.plist matches

### Landing page not loading
1. Check `live.php` file permissions
2. Verify PHP is executable
3. Check web server error logs

## 📚 Resources

- [Android App Links Documentation](https://developer.android.com/training/app-links)
- [iOS Universal Links Guide](https://developer.apple.com/ios/universal-links/)
- [assetlinks.json Generator](https://developers.google.com/digital-asset-links/tools/generator)

---

**Configured App Package**: `com.chat.myparty`  
**Website Domain**: `mypartychat.site`  
**Landing Page**: `https://mypartychat.site/live`  
**App Links Verification**: `https://mypartychat.site/.well-known/assetlinks.json`
