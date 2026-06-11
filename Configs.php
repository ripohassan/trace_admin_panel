<?php
// Suppress deprecation warnings from vendor libraries
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

require 'vendor/autoload.php';

// Load environment variables from .env file if it exists
if (file_exists(__DIR__ . '/.env')) {
    $env_file = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_file as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Remove wrapping quotes if present.
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            $value = substr($value, 1, -1);
        }

        $_ENV[$key] = $value;
    }
}

if (!function_exists('getEnvValue')) {
    function getEnvValue(array $keys, $default = '')
    {
        foreach ($keys as $key) {
            if (isset($_ENV[$key])) {
                $value = trim((string) $_ENV[$key]);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return $default;
    }
}

// Prefer PARSE_* keys but support legacy aliases from .env.example.
$parse_app_id = "NXgg3EtUgqRLryHea3pjIHWf0qNdyWTxbfZAFQ9b";
$parse_rest_key = "X4d5nqHZGa06RutBeJ024Tb73JpfJLWKkKUeTQmt";
$parse_master_key = "cx30LCUA8mfrKhS88Zetjo5PU5syyMk2Vh49n54u";
$parse_server_url = 'https://parseapi.back4app.com/';

// Support both "https://host" and "https://host/parse" in PARSE_SERVER_URL.
$parse_mount_path = '/';
$parsed_url = parse_url($parse_server_url);
if ($parsed_url !== false && isset($parsed_url['scheme'], $parsed_url['host'])) {
    $server_base = $parsed_url['scheme'] . '://' . $parsed_url['host'];
    if (isset($parsed_url['port'])) {
        $server_base .= ':' . $parsed_url['port'];
    }

    $path = trim($parsed_url['path'] ?? '');
    if ($path !== '' && $path !== '/') {
        $parse_mount_path = $path;
    }
    $parse_server_url = $server_base;
}

$app_name = $_ENV['APP_NAME'] ?? 'MyParty';
$default_icon_color = $_ENV['DEFAULT_ICON_COLOR'] ?? 'text-white'; // use Bootstrap text color sintax

use Parse\ParseClient;
use Parse\ParseSessionStorage;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    if ($parse_app_id && $parse_rest_key && $parse_master_key) {
        ParseClient::initialize(
            $parse_app_id,
            $parse_rest_key,
            $parse_master_key
        );
        ParseClient::setServerURL($parse_server_url, $parse_mount_path);
        ParseClient::setStorage(new ParseSessionStorage());
        ParseClient::setHttpClient(new \Parse\HttpClients\ParseStreamHttpClient());
    } else {
        error_log('Missing Parse configuration. Please set PARSE_APP_ID/PARSE_REST_API_KEY/PARSE_MASTER_KEY in .env');
    }
} catch (Exception $e) {
    error_log('Parse initialization error: ' . $e->getMessage());
}

// Website root url
$GLOBALS['WEBSITE_PATH'] = $_ENV['WEBSITE_PATH'] ?? 'https://parseapi.back4app.com'; 
