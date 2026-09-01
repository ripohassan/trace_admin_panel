<?php
/**
 * User Info API (High-Performance Optimized)
 *
 * Request Body (JSON):
 * {
 *   "gameId": "101",
 *   "uid":    "10001",
 *   "token":  "ABCD123",
 *   "roomId": "100000",   // optional
 *   "sign":   "md5(gameId + uid + token + roomId + secretKey)"
 * }
 *
 * Response (success):
 * {
 *   "errorCode": 0,
 *   "data": {
 *     "uid":      "10001",
 *     "nickname": "lucky",
 *     "avatar":   "https://example.com/avatar.png",
 *     "coin":     10000
 *   }
 * }
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

$gameId = trim((string) ($data['gameId'] ?? ''));
$uid = trim((string) ($data['uid'] ?? ''));
$token = trim((string) ($data['token'] ?? ''));
$roomId = trim((string) ($data['roomId'] ?? ''));
$sign = trim((string) ($data['sign'] ?? ''));

// ── 2. Required field check ──────────────────────────────────────────────

if ($gameId === '' || $uid === '' || $token === '' || $sign === '') {
    http_response_code(400);
    echo json_encode(['errorCode' => 4005, 'errorMessage' => 'Parameter error: gameId, uid, token, sign are required']);
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
$expectedSign = md5($gameId . $uid . $token . $roomId . $secretKey);
if (strtolower($sign) !== strtolower($expectedSign)) {
    http_response_code(401);
    echo json_encode(['errorCode' => 10004, 'errorMessage' => 'Signature error']);
    exit;
}

// ── 4. Cache check (Fast sub-50ms response) ──────────────────────────────

$cacheTtl = 30; // 30 seconds TTL for valid users
$negativeCacheTtl = 10; // 10 seconds TTL for non-existent users
$cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'b4a_user_cache';
$cacheFile = $cacheDir . DIRECTORY_SEPARATOR . md5('user_info_' . $uid) . '.json';

if (file_exists($cacheFile)) {
    $fileAge = time() - filemtime($cacheFile);
    $cachedData = @file_get_contents($cacheFile);
    if ($cachedData) {
        $cachedJson = json_decode($cachedData, true);
        if (is_array($cachedJson)) {
            // Check if it's a negative cache entry
            if (isset($cachedJson['__not_found']) && $fileAge < $negativeCacheTtl) {
                echo json_encode(['errorCode' => 4005, 'errorMessage' => 'User not found']);
                exit;
            } elseif (!isset($cachedJson['__not_found']) && $fileAge < $cacheTtl) {
                echo json_encode([
                    'errorCode' => 0,
                    'data' => $cachedJson,
                ]);
                exit;
            }
        }
    }
}

// ── 5. Back4App REST API single-query ───────────────────────────────────

$B4A_APP_ID = $_ENV['APPLICATION_ID'] ?? $_ENV['PARSE_APP_ID'] ?? 'NXgg3EtUgqRLryHea3pjIHWf0qNdyWTxbfZAFQ9b';
$B4A_MASTER_KEY = $_ENV['PARSE_MASTER_KEY'] ?? 'cx30LCUA8mfrKhS88Zetjo5PU5syyMk2Vh49n54u';
$B4A_BASE = 'https://parseapi.back4app.com';

$whereClause = is_numeric($uid)
    ? ['$or' => [['uid' => (int) $uid], ['uid' => (string) $uid]]]
    : ['uid' => (string) $uid];

$keys = 'uid,name,nickname,displayName,username,avatar,profileImage,profilePicture,photo,credit,coins,coin,balance';
$url = $B4A_BASE . '/users?where=' . urlencode(json_encode($whereClause)) . '&keys=' . urlencode($keys) . '&limit=1';

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

$foundUser = null;
if ($responseBody !== false && $httpCode >= 200 && $httpCode < 300) {
    $decoded = json_decode($responseBody, true);
    if (!empty($decoded['results'][0])) {
        $foundUser = $decoded['results'][0];
    }
}

// ── 6. User not found (with negative cache) ──────────────────────────────

if ($foundUser === null) {
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0777, true);
    }
    @file_put_contents($cacheFile, json_encode(['__not_found' => true]), LOCK_EX);
    echo json_encode(['errorCode' => 4005, 'errorMessage' => 'User not found']);
    exit;
}

// ── 7. Format user details ───────────────────────────────────────────────

$nickname = $foundUser['name']
    ?? $foundUser['nickname']
    ?? $foundUser['displayName']
    ?? $foundUser['username']
    ?? '';

$avatarUrl = '';
foreach (['avatar', 'profileImage', 'profilePicture', 'photo'] as $avatarKey) {
    if (!empty($foundUser[$avatarKey])) {
        $av = $foundUser[$avatarKey];
        if (is_array($av) && !empty($av['url'])) {
            $avatarUrl = $av['url'];
        } elseif (is_string($av)) {
            $avatarUrl = $av;
        }
        if ($avatarUrl !== '') {
            break;
        }
    }
}

if ($avatarUrl !== '' && stripos($avatarUrl, 'http://') === 0) {
    $avatarUrl = 'https://' . substr($avatarUrl, 7);
}

$coinBalance = $foundUser['credit'] ?? $foundUser['coins'] ?? $foundUser['coin'] ?? $foundUser['balance'] ?? 0;
$coinBalance = is_numeric($coinBalance) ? (int) $coinBalance : 0;

$userData = [
    'uid' => $uid,
    'nickname' => (string) $nickname,
    'avatar' => (string) $avatarUrl,
    'coin' => $coinBalance,
];

// Write cache
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0777, true);
}
@file_put_contents($cacheFile, json_encode($userData), LOCK_EX);

// ── 8. Response ──────────────────────────────────────────────────────────

echo json_encode([
    'errorCode' => 0,
    'data' => $userData,
]);

