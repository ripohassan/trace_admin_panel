<?php
/**
 * Deep Link Handler for /live route
 * 
 * This file is accessible WITHOUT authentication
 * Used for app deep linking and web fallback
 * 
 * If app is installed: Opens My Party app with roomId
 * If app is not installed: Shows fallback page with store links
 * 
 * URL Format: https://mypartychat.site/live?roomId=UaNPfaBOIY
 */

// Disable any automatic redirects to login
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Clear any previous output
if (ob_get_level()) {
    ob_end_clean();
}

$roomId = $_GET['roomId'] ?? null;
$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$full_url = $scheme . '://' . $host . $_SERVER['REQUEST_URI'];

// Android App Configuration
$android_package = "com.chat.myparty";
$android_app_scheme = "myparty://live";

// App Store IDs
$app_store_id = "YOUR_APP_STORE_ID"; // Replace with your iOS app ID
$playstore_url = "https://play.google.com/store/apps/details?id=" . urlencode($android_package);
$appstore_url = "https://apps.apple.com/app/id" . $app_store_id;
$website_url = "https://mypartychat.site";

// Set headers FIRST - before any HTML output
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-UA-Compatible: IE=edge');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Party - Live Room</title>
    
    <!-- iOS Smart App Banner -->
    <meta name="apple-itunes-app" content="app-id=<?php echo $app_store_id; ?>, app-argument=<?php echo urlencode($full_url); ?>">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 100%;
            padding: 40px 30px;
            text-align: center;
        }
        
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        
        .status {
            background: #f0f2f5;
            border-left: 4px solid #667eea;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 12px;
            color: #555;
        }
        
        .room-id {
            background: #f0f2f5;
            padding: 12px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #333;
            margin-bottom: 25px;
            word-break: break-all;
        }
        
        .button-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        button {
            padding: 14px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #f0f2f5;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #e4e6eb;
            transform: translateY(-2px);
        }
        
        .loader {
            width: 40px;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
            margin: 0 auto 20px;
            animation: loading 1.5s ease-in-out infinite;
        }
        
        @keyframes loading {
            0% { width: 40px; margin-left: 0; }
            50% { width: 80px; margin-left: -20px; }
            100% { width: 40px; margin-left: 0; }
        }
        
        .hidden {
            display: none;
        }
        
        .note {
            font-size: 12px;
            color: #999;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon" id="icon">🎵</div>
        <div class="loader" id="loader"></div>
        
        <h1 id="title">Joining Live Room...</h1>
        <p class="subtitle" id="subtitle">Opening My Party app...</p>
        
        <?php if ($roomId): ?>
        <div class="status">
            ✓ Room ID verified
        </div>
        <div class="room-id">
            Room: <?php echo htmlspecialchars($roomId); ?>
        </div>
        <?php else: ?>
        <div class="status" style="border-left-color: #ff6b6b;">
            ⚠ No room ID provided
        </div>
        <?php endif; ?>
        
        <div class="button-group">
            <button class="btn-primary" onclick="installApp()">
                📲 Install / Open App
            </button>
            <button class="btn-secondary" onclick="goToWebsite()">
                🌐 Continue on Website
            </button>
        </div>
        
        <div class="note">
            If app doesn't open, tap "Install / Open App" to get it from store
        </div>
    </div>

    <script>
        // Configuration
        const config = {
            packageName: "<?php echo $android_package; ?>",
            appScheme: "<?php echo $android_app_scheme; ?>",
            roomId: "<?php echo htmlspecialchars($roomId ?? ''); ?>",
            playStoreUrl: "<?php echo $playstore_url; ?>",
            appStoreUrl: "<?php echo $appstore_url; ?>",
            websiteUrl: "<?php echo $website_url; ?>",
            fallbackDelay: 1500 // milliseconds
        };

        /**
         * Get device type
         */
        function getDeviceType() {
            const ua = navigator.userAgent || navigator.vendor || window.opera;
            
            if (/android/i.test(ua)) return 'android';
            if (/iPad|iPhone|iPod/.test(ua) && !window.MSStream) return 'ios';
            return 'web';
        }

        /**
         * Attempt to open the app
         */
        function openApp() {
            const device = getDeviceType();
            const roomId = config.roomId;
            
            if (!roomId) {
                console.log('No room ID provided');
                updateUI('Error', 'No room ID provided');
                return false;
            }

            if (device === 'android') {
                // Android: Try deep link scheme first
                const deepLinkUrl = config.appScheme + "?roomId=" + encodeURIComponent(roomId);
                console.log('Attempting Android deep link:', deepLinkUrl);
                
                // Try opening with intent
                window.location.href = deepLinkUrl;
                return true;
            } else if (device === 'ios') {
                // iOS: Use URL scheme
                const iosUrl = config.appScheme + "?roomId=" + encodeURIComponent(roomId);
                console.log('Attempting iOS URL scheme:', iosUrl);
                
                window.location.href = iosUrl;
                return true;
            } else {
                // Web: Show install prompt
                console.log('Desktop device detected');
                updateUI('Install App', 'Get My Party app to join live rooms');
                return false;
            }
        }

        /**
         * Fallback: Redirect to appropriate store
         */
        function redirectToStore() {
            const device = getDeviceType();
            
            console.log('Fallback redirect for device:', device);
            
            if (device === 'android') {
                window.location.href = config.playStoreUrl;
            } else if (device === 'ios') {
                window.location.href = config.appStoreUrl;
            } else {
                // Desktop - show website
                window.location.href = config.websiteUrl;
            }
        }

        /**
         * Update UI text
         */
        function updateUI(title, subtitle) {
            document.getElementById('title').textContent = title;
            document.getElementById('subtitle').textContent = subtitle;
            document.getElementById('loader').style.display = 'none';
            document.getElementById('icon').style.animation = 'none';
        }

        /**
         * Install/Open App button handler
         */
        function installApp() {
            redirectToStore();
        }

        /**
         * Go to website button handler
         */
        function goToWebsite() {
            window.location.href = config.websiteUrl;
        }

        /**
         * Main entry point
         */
        function initialize() {
            const device = getDeviceType();
            
            console.log('Device type:', device);
            console.log('Room ID:', config.roomId);
            
            if (!config.roomId) {
                updateUI('Error', 'Invalid room ID');
                return;
            }
            
            // Attempt to open app
            const appOpened = openApp();
            
            if (appOpened) {
                // Set fallback timer
                setTimeout(function() {
                    console.log('App did not open, falling back to store');
                    redirectToStore();
                }, config.fallbackDelay);
            }
        }

        // Start on page load
        window.addEventListener('load', initialize);
        
        // Also try immediately for faster response
        setTimeout(initialize, 100);
    </script>
</body>
</html>
