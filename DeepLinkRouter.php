<?php
/**
 * DeepLink Router - Handles URL deeplinks and routes to appropriate pages
 * Reads configuration from config/deeplinks.json
 * 
 * Usage:
 * - ?deeplink=users
 * - ?deeplink=ads
 * - ?deeplink=payments&action=view&id=123
 * - ?link=users (alias)
 */

class DeepLinkRouter {
    
    /**
     * Configuration file path
     */
    private static $config_file = null;
    
    /**
     * Cached deeplinks array
     */
    private static $deeplinks = null;
    
    /**
     * Initialize the router with config file path
     */
    public static function init($config_path = null) {
        if ($config_path === null) {
            $config_path = __DIR__ . '/config/deeplinks.json';
        }
        self::$config_file = $config_path;
        self::loadConfig();
    }
    
    /**
     * Load configuration from JSON file
     */
    private static function loadConfig() {
        if (!file_exists(self::$config_file)) {
            error_log("Deeplink config file not found: " . self::$config_file);
            self::$deeplinks = [];
            return;
        }
        
        $json_content = file_get_contents(self::$config_file);
        $config = json_decode($json_content, true);
        
        if (!$config || !isset($config['deeplinks'])) {
            error_log("Invalid deeplink config format");
            self::$deeplinks = [];
            return;
        }
        
        // Flatten the nested structure
        self::$deeplinks = [];
        foreach ($config['deeplinks'] as $category => $data) {
            if (isset($data['links']) && is_array($data['links'])) {
                foreach ($data['links'] as $name => $link_data) {
                    if (isset($link_data['path'])) {
                        self::$deeplinks[$name] = $link_data['path'];
                    }
                }
            }
        }
    }
    
    /**
     * Get the file path for a deeplink
     * 
     * @param string $deeplink The deeplink identifier
     * @return string|null The file path or null if not found
     */
    public static function getPath($deeplink) {
        if (self::$deeplinks === null) {
            self::init();
        }
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
        if (self::$deeplinks === null) {
            self::init();
        }
        return isset(self::$deeplinks[strtolower(trim($deeplink))]);
    }
    
    /**
     * Get all available deeplinks (for documentation/API)
     * 
     * @return array Associative array of deeplink => path
     */
    public static function getAll() {
        if (self::$deeplinks === null) {
            self::init();
        }
        return self::$deeplinks;
    }
    
    /**
     * Get organized deeplinks by category (from raw config)
     * 
     * @return array Organized deeplinks with categories and metadata
     */
    public static function getOrganized() {
        if (!file_exists(self::$config_file ?? __DIR__ . '/config/deeplinks.json')) {
            return [];
        }
        
        $config_file = self::$config_file ?? __DIR__ . '/config/deeplinks.json';
        $json_content = file_get_contents($config_file);
        $config = json_decode($json_content, true);
        
        return $config['deeplinks'] ?? [];
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
