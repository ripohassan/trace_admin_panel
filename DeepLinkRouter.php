<?php
/**
 * DeepLink Router - Handles URL deeplinks and routes to appropriate pages
 * 
 * Usage:
 * - ?deeplink=users
 * - ?deeplink=ads
 * - ?deeplink=payments&action=view&id=123
 * - ?link=users (alias)
 */

class DeepLinkRouter {
    
    /**
     * Deeplink mapping - Maps short identifiers to dashboard files
     */
    private static $deeplinks = [
        // User Management
        'users' => 'dashboard/all_users.php',
        'admin_users' => 'dashboard/admin_users.php',
        'edit_user' => 'dashboard/edit_user.php',
        
        // Ads Management
        'ads' => 'dashboard/all_ads.php',
        'add_ad' => 'dashboard/add_ad.php',
        'edit_ad' => 'dashboard/edit_ad.php',
        
        // Payments & Payouts
        'payments' => 'dashboard/payments.php',
        'payouts' => 'dashboard/payouts.php',
        'pending_withdrawals' => 'dashboard/pending_withdrawals.php',
        'withdrawals' => 'dashboard/withdrawals.php',
        'coin_requests' => 'dashboard/coin_requests.php',
        'coin_traders' => 'dashboard/coin_traders.php',
        
        // Agency
        'agency' => 'dashboard/agency_list.php',
        'agency_applications' => 'dashboard/agency_applications.php',
        'agency_members' => 'dashboard/agency_members.php',
        
        // Content Management
        'gifts' => 'dashboard/gift.php',
        'gift_categories' => 'dashboard/gift_category.php',
        'ads_settings' => 'dashboard/ads_settings.php',
        'announcements' => 'dashboard/announcement.php',
        'banners' => 'dashboard/banners.php',
        'categories' => 'dashboard/category.php',
        
        // Frames & Effects
        'avatar_frames' => 'dashboard/avatar_frame.php',
        'entrance_effects' => 'dashboard/entrance_effect.php',
        'party_themes' => 'dashboard/party_theme.php',
        
        // Stats & Analytics
        'comments' => 'dashboard/comments.php',
        'clicks' => 'dashboard/clicks.php',
        'calls' => 'dashboard/calls.php',
        'follows' => 'dashboard/follow.php',
        'favorites' => 'dashboard/favorites.php',
        
        // Coins & Converter
        'coin_plans' => 'dashboard/coin_plans.php',
        'converter_packages' => 'dashboard/converter_packages.php',
        
        // Approvals
        'photo_approvals' => 'dashboard/photo_aproval.php',
        'hangout_approvals' => 'dashboard/hangout_aproval.php',
        
        // Other
        'messages' => 'dashboard/messages.php',
        'panel' => 'dashboard/panel.php',
        'dashboard' => 'dashboard/panel.php',
    ];
    
    /**
     * Get the file path for a deeplink
     * 
     * @param string $deeplink The deeplink identifier
     * @return string|null The file path or null if not found
     */
    public static function getPath($deeplink) {
        $deeplink = strtolower(trim($deeplink));
        return self::$deeplinks[$deeplink] ?? null;
    }
    
    /**
     * Check if a deeplink exists
     * 
     * @param string $deeplink The deeplink identifier
     * @return bool True if deeplink exists
     */
    public static function exists($deeplink) {
        return isset(self::$deeplinks[strtolower(trim($deeplink))]);
    }
    
    /**
     * Get all available deeplinks (for documentation/API)
     * 
     * @return array Associative array of deeplink => path
     */
    public static function getAll() {
        return self::$deeplinks;
    }
    
    /**
     * Route a deeplink request
     * Handles both GET parameters: ?deeplink=users or ?link=users
     * 
     * @param string|null $deeplink The deeplink to route (optional, will check $_GET)
     * @return array ['success' => bool, 'path' => string|null, 'message' => string]
     */
    public static function route($deeplink = null) {
        // Get deeplink from parameter or GET request
        if ($deeplink === null) {
            $deeplink = $_GET['deeplink'] ?? $_GET['link'] ?? null;
        }
        
        if (!$deeplink) {
            return [
                'success' => false,
                'path' => null,
                'message' => 'No deeplink provided'
            ];
        }
        
        $path = self::getPath($deeplink);
        
        if (!$path) {
            return [
                'success' => false,
                'path' => null,
                'message' => "Unknown deeplink: {$deeplink}"
            ];
        }
        
        // Check if file exists
        $base_path = __DIR__ . DIRECTORY_SEPARATOR . $path;
        if (!file_exists($base_path)) {
            return [
                'success' => false,
                'path' => $path,
                'message' => "Deeplink file not found: {$path}"
            ];
        }
        
        return [
            'success' => true,
            'path' => $path,
            'message' => "Successfully routed to {$path}"
        ];
    }
    
    /**
     * Redirect to a deeplink
     * Handles authentication check and redirects
     * 
     * @param string|null $deeplink The deeplink to redirect to
     * @return void
     */
    public static function redirect($deeplink = null) {
        $result = self::route($deeplink);
        
        if ($result['success']) {
            header('Location: ' . $result['path']);
            exit;
        } else {
            // Log or handle error
            error_log("Deeplink Error: " . $result['message']);
            // Redirect to default dashboard
            header('Location: dashboard/panel.php');
            exit;
        }
    }
    
    /**
     * Generate a deeplink URL
     * 
     * @param string $deeplink The deeplink identifier
     * @param array $params Additional query parameters
     * @return string The full deeplink URL
     */
    public static function generateUrl($deeplink, $params = []) {
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $base_url = rtrim($base_url, '/') . '/'; // Adjust this path based on your installation
        
        $query_params = array_merge(['deeplink' => $deeplink], $params);
        $query_string = http_build_query($query_params);
        
        return $base_url . 'index.php?' . $query_string;
    }
}
?>
