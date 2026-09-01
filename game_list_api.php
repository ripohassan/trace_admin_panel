<?php
/**
 * Game List API (High-Performance Optimized)
 *
 * Returns all available games with their details
 *
 * Response:
 * [
 *   {
 *     "gameId": "101",
 *     "name": "GreedyStar",
 *     "title": "摩天轮宇航版",
 *     "ver": 15,
 *     "half_url": "https://xxx.quantum-nexus.net/games/greedy_star_half/index.html?pl=pikilive&v=15",
 *     "full_url": "https://xxx.quantum-nexus.net/games/greedy_star/index.html?pl=pikilive&v=15",
 *     "hd_url": "https://xxx.quantum-nexus.net/games/greedy_star_medium/index.html?pl=pikilive&v=15"
 *   }
 * ]
 */

header('Content-Type: application/json');

// ── 1. Request parse ─────────────────────────────────────────────────────

$rawBody = file_get_contents('php://input');
if (!$rawBody) {
    http_response_code(400);
    echo json_encode(['errorCode' => 4005, 'errorMessage' => 'Empty request body']);
    exit;
}

$data = json_decode($rawBody, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['errorCode' => 4005, 'errorMessage' => 'Invalid JSON body']);
    exit;
}

$sign = trim((string)($data['sign'] ?? ''));

// ── 2. Required field check ──────────────────────────────────────────────

if ($sign === '') {
    http_response_code(400);
    echo json_encode(['errorCode' => 4005, 'errorMessage' => 'Parameter error: sign is required']);
    exit;
}

// ── 3. Load lightweight env & verify signature ───────────────────────────

if (!isset($_ENV['API_SIGN_KEY'])) {
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }
            list($k, $v) = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v, " \t\n\r\0\x0B\"'");
            $_ENV[$k] = $v;
        }
    }
}

$secretKey = $_ENV['API_SIGN_KEY'] ?? $_ENV['WEBHOOK_KEY'] ?? $_ENV['REST_API_KEY'] ?? 'xR26YzHB5Sm3qX2R3i676WfAoSQXkfCDha9WVqZ';
$expectedSign = md5($secretKey);
if (strtolower($sign) !== strtolower($expectedSign)) {
    http_response_code(401);
    echo json_encode(['errorCode' => 10004, 'errorMessage' => 'Signature error']);
    exit;
}

// ── 4. Cache check (Fast sub-5ms response) ───────────────────────────────

$cacheTtl = 60; // 60 seconds TTL
$cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'b4a_game_cache';
$cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'game_list.json';

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTtl)) {
    $cachedData = @file_get_contents($cacheFile);
    if ($cachedData) {
        http_response_code(200);
        echo $cachedData;
        exit;
    }
}

// ── 5. Back4App REST API query ───────────────────────────────────────────

$B4A_APP_ID = $_ENV['APPLICATION_ID'] ?? $_ENV['PARSE_APP_ID'] ?? 'NXgg3EtUgqRLryHea3pjIHWf0qNdyWTxbfZAFQ9b';
$B4A_MASTER_KEY = $_ENV['PARSE_MASTER_KEY'] ?? 'cx30LCUA8mfrKhS88Zetjo5PU5syyMk2Vh49n54u';
$B4A_BASE = 'https://parseapi.back4app.com';

$keys = 'gameId,name,title,ver,full_url,hd_url,half_url';
$url = $B4A_BASE . '/classes/Game?keys=' . urlencode($keys);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 4,
    CURLOPT_CONNECTTIMEOUT => 2,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    CURLOPT_TCP_NODELAY => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
    CURLOPT_HTTPHEADER => [
        'X-Parse-Application-Id: ' . $B4A_APP_ID,
        'X-Parse-Master-Key: ' . $B4A_MASTER_KEY,
        'Content-Type: application/json',
    ],
]);

$responseBody = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($responseBody === false || $httpCode < 200 || $httpCode >= 300) {
    http_response_code(500);
    echo json_encode([
        'errorCode' => 4000,
        'errorMessage' => 'Network timeout'
    ]);
    exit;
}

$decoded = json_decode($responseBody, true);
$games = $decoded['results'] ?? [];

$gamesList = [];
foreach ($games as $game) {
    $gameData = [
        'gameId' => (string)($game['gameId'] ?? ''),
        'name' => (string)($game['name'] ?? ''),
        'title' => (string)($game['title'] ?? ''),
        'ver' => (int)($game['ver'] ?? 0),
    ];

    if (isset($game['full_url'])) {
        $gameData['full_url'] = (string)$game['full_url'];
    }
    if (isset($game['hd_url'])) {
        $gameData['hd_url'] = (string)$game['hd_url'];
    }
    if (isset($game['half_url'])) {
        $gameData['half_url'] = (string)$game['half_url'];
    }

    $gamesList[] = $gameData;
}

$responseJson = json_encode($gamesList);

// Write to cache
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0777, true);
}
@file_put_contents($cacheFile, $responseJson, LOCK_EX);

http_response_code(200);
echo $responseJson;

