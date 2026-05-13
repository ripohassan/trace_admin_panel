# Quick Start - App Deep Link

## 📱 What Happens?

### **Scenario 1: App is INSTALLED**

```
User clicks: https://mypartychat.site/live?roomId=UaNPfaBOIY
                          ⬇️
        Android verifies assetlinks.json
                          ⬇️
        App's Intent Filter catches the URL
                          ⬇️
        ✅ MY PARTY APP OPENS with roomId=UaNPfaBOIY
```

### **Scenario 2: App is NOT INSTALLED**

```
User clicks: https://mypartychat.site/live?roomId=UaNPfaBOIY
                          ⬇️
        Browser loads live.php landing page
                          ⬇️
        JavaScript tries to open app (1.5 seconds wait)
                          ⬇️
        App doesn't respond → Fallback triggered
                          ⬇️
        Device Detection:
        ├─ Android → Redirect to Play Store
        ├─ iPhone → Redirect to App Store  
        └─ Desktop → Show Install button + Website link
```

## 🎯 Backend Files

| File | Purpose | Status |
|------|---------|--------|
| `live.php` | Deep link handler + landing page | ✅ Created |
| `.well-known/assetlinks.json` | Android app verification | ✅ Created |
| `config/deeplinks.json` | Deeplink configuration | ✅ Created |

## 🔗 Test URLs

### Android Test:
```
https://mypartychat.site/live?roomId=TestRoom123
```

### With Custom Room ID:
```
https://mypartychat.site/live?roomId=UaNPfaBOIY
```

## ⚡ Device Detection

```
┌─────────────────────────────────────────┐
│     Browser lands on live.php           │
└─────────────────┬───────────────────────┘
                  │
        ┌─────────┼─────────┐
        │         │         │
    ┌───▼──┐ ┌───▼──┐ ┌───▼──┐
    │ iOS  │ │ Android
    │      │ │
    │      │ │
    └──────┘ └───────┘
```

## 🛠️ What You Need to Do

### 1️⃣ Update `live.php` (Line 18-19)
```php
$android_package = "com.chat.myparty";        // Your Android package
$app_store_id = "YOUR_APP_STORE_ID";          // Your iOS app ID
```

### 2️⃣ Update Android App
Add to `AndroidManifest.xml`:
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

### 3️⃣ Update Flutter/Native App
Add deep link handler:
```dart
void handleDeepLink(Uri uri) {
  if (uri.host == "mypartychat.site" && uri.path == "/live") {
    final roomId = uri.queryParameters["roomId"];
    // Navigate to room
  }
}
```

### 4️⃣ Test It

Visit in browser:
```
https://mypartychat.site/live?roomId=test123
```

## ✅ Verification Checklist

- [ ] `.well-known/assetlinks.json` returns raw JSON
- [ ] Android app has AndroidManifest intent-filter
- [ ] `live.php` has correct package names
- [ ] App can handle deep links with roomId parameter
- [ ] Test: App opens when installed
- [ ] Test: Falls back to store when not installed

## 📊 How Browser Routes It

```
Step 1: Browser opens https://mypartychat.site/live?roomId=...
         ↓
Step 2: .htaccess rule redirects to live.php
         ↓
Step 3: live.php loads landing page
         ↓
Step 4: JavaScript runs:
        - Attempts myparty://live?roomId=... (app scheme)
        - Waits 1.5 seconds
        - If no app: Redirects to Play Store/App Store
         ↓
Step 5: User sees loading screen or install prompt
```

## 🎨 Landing Page Features

✅ Beautiful animated UI  
✅ Auto-detects device type  
✅ Shows room ID  
✅ Dual buttons: Install App / Continue on Website  
✅ Smart fallback (1.5s delay)  
✅ Responsive design  
✅ Proper MIME types  
✅ CORS headers enabled  

## 🔐 Security Verified

✅ HTTPS only  
✅ App link verification  
✅ Certificate fingerprint check  
✅ No HTML/redirect from assetlinks.json  
✅ Proper error handling  

---

## 📞 URLs Ready to Use

**Landing Page (No App)**:
```
https://mypartychat.site/live?roomId=abc123
```

**App Link Verification**:
```
https://mypartychat.site/.well-known/assetlinks.json
```

**Deeplink API**:
```
https://mypartychat.site/deeplink_api.php?action=organized
```
