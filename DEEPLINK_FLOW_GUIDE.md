# Deep Link Flow & Device Detection

## 🎯 Complete Flow Chart

### When User Clicks Deep Link

```
https://mypartychat.site/live?roomId=UaNPfaBOIY
                    ⬇️
        ┌───────────────────────────┐
        │   Browser Attempts:       │
        │ myparty://live?roomId=... │
        └────────┬──────────────────┘
                 │
        ┌────────▼────────┐
        │   1.5s Timeout  │
        │    (Waiting)    │
        └────────┬────────┘
                 │
         ┌───────┴────────┐
         │                │
    ┌────▼────┐      ┌────▼──────┐
    │ App      │      │ App Not   │
    │ Catches  │      │ Responding│
    │ Intent   │      │           │
    └────┬─────┘      └────┬──────┘
         │                 │
    ┌────▼──────────────────┴─────┐
    │  Identify Device Type        │
    └────┬─────────────┬───────┬───┘
         │             │       │
    ┌────▼──┐      ┌───▼───┐ ┌┴────┐
    │iOS    │      │Android│ │Web  │
    │       │      │       │ │     │
    └────┬──┘      └───┬───┘ └┬────┘
         │             │      │
    App Store      Play Store Website
    (URL: app-id)  (URL: pkg)
```

## 📱 Device Detection Logic

### JavaScript Detection:

```javascript
const ua = navigator.userAgent || navigator.vendor || window.opera;

if (/android/i.test(ua)) {
    // ANDROID
    // ✅ Scheme: myparty://live?roomId=...
    // ✅ Store: Play Store
    // ✅ Fallback: 1.5 seconds
}
else if (/iPad|iPhone|iPod/.test(ua) && !window.MSStream) {
    // iOS
    // ✅ Scheme: myparty://live?roomId=...
    // ✅ Store: App Store
    // ✅ Smart Banner: Auto-displays
}
else {
    // DESKTOP/WEB
    // ✅ No app opening attempt
    // ✅ Show Website button
    // ✅ Optional: Install button
}
```

## 🔄 Detailed Scenarios

### ✅ Scenario A: Android with App Installed

```
1. User clicks deep link
   └─ https://mypartychat.site/live?roomId=UaNPfaBOIY
   
2. Android OS checks Intent Filters
   └─ Finds: host=mypartychat.site, path=/live
   
3. assetlinks.json verification
   └─ App link certificate matches
   
4. Intent Filter Matches:
   <intent-filter android:autoVerify="true">
       <data android:scheme="https" 
             android:host="mypartychat.site"
             android:pathPrefix="/live" />
   </intent-filter>
   
5. ✅ MY PARTY APP LAUNCHES
   └─ Deep link handler receives:
      {
        "host": "mypartychat.site",
        "path": "/live",
        "roomId": "UaNPfaBOIY"
      }
   
6. App navigates to LiveAudioRoom
   └─ Joins room: UaNPfaBOIY
```

### ❌ Scenario B: Android WITHOUT App

```
1. User clicks deep link
   └─ https://mypartychat.site/live?roomId=UaNPfaBOIY
   
2. Chrome opens in browser
   
3. live.php loads landing page
   
4. JavaScript attempts:
   myparty://live?roomId=UaNPfaBOIY
   
5. Android doesn't recognize scheme
   ❌ No response
   
6. Wait 1.5 seconds...
   
7. Still no app → Fallback triggered
   
8. Device Detection:
   └─ Android detected
   
9. Redirect to:
   https://play.google.com/store/apps/details?id=com.chat.myparty
   
10. 📲 PLAY STORE OPENS
    └─ User can install app
```

### 🍎 Scenario C: iPhone with App

```
1. User clicks link from:
   └─ Safari, Email, Messages, Facebook, etc.
   
2. Apple URL Scheme Handler:
   myparty://live?roomId=UaNPfaBOIY
   
3. iOS checks URL Schemes in Info.plist:
   <key>CFBundleURLSchemes</key>
   <array>
       <string>myparty</string>
   </array>
   
4. ✅ MY PARTY APP LAUNCHES
   
5. App's deep link handler processes:
   {
     "scheme": "myparty",
     "host": "live",
     "roomId": "UaNPfaBOIY"
   }
```

### 🍎 Scenario D: iPhone WITHOUT App

```
1. User clicks link in Safari
   
2. Landing page appears
   └─ Shows animated UI
   
3. Smart App Banner displayed:
   <meta name="apple-itunes-app" 
         content="app-id=YOUR_ID, 
                  app-argument=https://...">
   
4. Banner offers two options:
   ├─ "Get" (Opens App Store)
   └─ "Open" (If app installed later)
   
5. User taps "Get"
   
6. 🍎 APP STORE OPENS
   └─ Can install app
```

### 💻 Scenario E: Desktop Browser

```
1. User clicks link on desktop
   
2. Chrome/Firefox opens
   
3. live.php loads landing page
   
4. JavaScript detects: Not mobile
   
5. App opening attempt skipped
   
6. Landing page shows:
   ├─ "Install / Open App" button
   └─ "Continue on Website" button
   
7. User clicks button
   └─ Redirects to appropriate store/website
```

## 🔀 Flow by Device Type

### Android Device Flow

```
┌─────────────────────────────────┐
│ User clicks deep link           │
│ https://mypartychat.site/live?  │
│ roomId=UaNPfaBOIY               │
└────────────┬────────────────────┘
             │
    ┌────────▼─────────┐
    │ Android Browser  │
    │ (Chrome/etc)     │
    └────────┬─────────┘
             │
    ┌────────▼─────────────────┐
    │ Check Intent Filters      │
    │ Does it match:            │
    │ host=mypartychat.site     │
    │ pathPrefix=/live          │
    └────────┬─────┬───────────┘
             │     │
         YES │     │ NO
             │     │
    ┌────────▼┐ ┌──▼──────────────┐
    │ Verify  │ │ Load in Browser │
    │ App Link│ │ (live.php)      │
    │ Cert    │ └──┬───────────────┘
    └────────┬┘   │
             │    │ (Continues below)
    ┌────────▼────┐
    │ Certificate │
    │ Matches?    │
    └────────┬─┬──┘
             │ │
         YES │ │ NO
             │ │
    ┌────────▼┐│
    │  LAUNCH ││
    │   APP   ││
    └─────────┘│
              │
    ┌─────────▼──────────┐
    │ Browser fallback   │
    │ (1.5s timeout)     │
    └─────────┬──────────┘
              │
    ┌─────────▼─────────────┐
    │ Detect Device: Android│
    └─────────┬─────────────┘
              │
    ┌─────────▼───────────────┐
    │ Redirect to Play Store  │
    │ (Install if needed)     │
    └─────────────────────────┘
```

### iOS Device Flow

```
┌──────────────────────────────┐
│ User clicks deep link        │
│ (from Safari, email, etc)    │
└────────────┬─────────────────┘
             │
    ┌────────▼────────────┐
    │ iOS URL Scheme      │
    │ Handler             │
    │ (myparty://)        │
    └────────┬─────┬──────┘
             │     │
         HAS │     │ MISSING
        APP  │     │
             │     │
    ┌────────▼┐┌────▼──────────┐
    │ LAUNCH  ││ Smart Banner   │
    │  APP    ││ Shows          │
    └─────────┘└────┬──────────┘
                    │
            ┌───────▼────────┐
            │ User sees:     │
            │ "Get" (Install)│
            │ or "Open"      │
            └───────┬────────┘
                    │
            ┌───────▼─────────┐
            │ Tap "Get"       │
            │ (Open App Store)│
            └─────────────────┘
```

## 📊 Backend Processing

### live.php Processing

```php
// 1. Receive request
https://mypartychat.site/live?roomId=UaNPfaBOIY
    ↓
// 2. Extract roomId
$roomId = $_GET['roomId']; // "UaNPfaBOIY"
    ↓
// 3. Set up URLs
$android_package = "com.chat.myparty";
$app_store_id = "YOUR_APP_STORE_ID";
$playstore_url = ".../details?id=com.chat.myparty"
$appstore_url = ".../app/id{ID}"
    ↓
// 4. Output HTML page
    ├─ JavaScript code
    ├─ Device detection
    ├─ Deep link attempt (myparty://...)
    └─ Fallback redirects
    ↓
// 5. User sees landing page
    ↓
// 6. JavaScript executes
    ├─ Try scheme: myparty://live?roomId=...
    ├─ Wait 1.5s
    ├─ Detect device
    └─ Redirect to store
```

## 🎯 roomId Parameter Flow

```
User Input:
  roomId = "UaNPfaBOIY"
      ↓
Web URL:
  ?roomId=UaNPfaBOIY
      ↓
Deep Link:
  myparty://live?roomId=UaNPfaBOIY
      ↓
App Receives:
  uri.queryParameters["roomId"] = "UaNPfaBOIY"
      ↓
App Actions:
  ├─ Get room data
  ├─ Setup audio
  ├─ Join room
  └─ Show UI
```

## ✅ Verification Checklist

Before launching:

```
Backend (.htaccess, live.php):
  ✅ live.php exists and returns HTML
  ✅ .well-known/assetlinks.json returns JSON only
  ✅ No redirects on assetlinks.json
  ✅ Correct MIME types set

Android:
  ✅ intent-filter added to AndroidManifest
  ✅ android:autoVerify="true" present
  ✅ Certificate fingerprint matches
  ✅ Deep link handler implemented
  ✅ Can extract roomId from query params

iOS:
  ✅ URL schemes in Info.plist
  ✅ Deep link handler implemented
  ✅ Smart App Banner supported
  ✅ App Store ID configured

Testing:
  ✅ Works with app installed
  ✅ Falls back without app
  ✅ Shows landing page
  ✅ Detects device correctly
  ✅ Redirects to correct store
```

---

**Summary**: This is a complete implementation of:
- ✅ Android App Links (assetlinks.json)
- ✅ iOS Universal Links (smart banner)
- ✅ URL scheme deep linking
- ✅ Web fallback landing page
- ✅ Automatic device detection
- ✅ Store redirect logic
