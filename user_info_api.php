<?php
/**
 * User Info API
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

// SSL bypass (development)
stream_context_set_default([
    'ssl' => [
        'verify_peer'      => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
    ],
]);

require 'vendor/autoload.php';
include 'Configs.php';

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

$gameId = trim((string)($data['gameId'] ?? ''));
$uid    = trim((string)($data['uid']    ?? ''));
$token  = trim((string)($data['token']  ?? ''));
$roomId = trim((string)($data['roomId'] ?? ''));
$sign   = trim((string)($data['sign']   ?? ''));

// ── 2. Required field check ──────────────────────────────────────────────

if ($gameId === '' || $uid === '' || $token === '' || $sign === '') {
    http_response_code(400);
    echo json_encode(['errorCode' => 4005, 'errorMessage' => 'Parameter error: gameId, uid, token, sign are required']);
    exit;
}

// ── 3. Signature verify ──────────────────────────────────────────────────

$secretKey = $_ENV['API_SIGN_KEY'] ?? $_ENV['WEBHOOK_KEY'] ?? $_ENV['REST_API_KEY'] ?? '';
if ($secretKey === '') {
    http_response_code(500);
    echo json_encode(['errorCode' => 4000, 'errorMessage' => 'Server error: secret key not configured']);
    exit;
}

$expectedSign = md5($gameId . $uid . $token . $roomId . $secretKey);
if (strtolower($sign) !== strtolower($expectedSign)) {
    http_response_code(401);
    echo json_encode(['errorCode' => 10004, 'errorMessage' => 'Signature error']);
    exit;
}

// ── 4. Back4App REST API helper ──────────────────────────────────────────

/**
 * Back4App REST API GET request.
 * Master Key ব্যবহার করায় ACL বাধা নেই।
 */
function b4aQuery(string $url, string $appId, string $masterKey): ?array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => [
            'X-Parse-Application-Id: ' . $appId,
            'X-Parse-Master-Key: '     . $masterKey,
            'Content-Type: application/json',
        ],
    ]);
    $body     = curl_exec($ch);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $httpCode < 200 || $httpCode >= 300) {
        return null;
    }
    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : null;
}

// ── 5. Back4App _User table এ uid দিয়ে user খোঁজা ──────────────────────

$B4A_APP_ID     = $parse_app_id;
$B4A_MASTER_KEY = $parse_master_key;
$B4A_BASE       = 'https://parseapi.back4app.com';

$foundUser = null;

/*
 * Back4App-এ uid column String হতে পারে অথবা Number (Integer)।
 * দুইটাই try করা হচ্ছে — একটায় match হলেই user পাওয়া যাবে।
 */

// try-1: uid = "10001"  (String)
$where  = urlencode(json_encode(['uid' => (int)$uid]));
$result = b4aQuery($B4A_BASE . '/classes/_User?where=' . $where . '&limit=1', $B4A_APP_ID, $B4A_MASTER_KEY);

if (!empty($result['results'])) {
    $foundUser = $result['results'][0];
}

// try-2: uid = 10001  (Integer/Number)
if ($foundUser === null && is_numeric($uid)) {
    $where  = urlencode(json_encode(['uid' => (int)$uid]));
    $result = b4aQuery($B4A_BASE . '/1/users?where=' . $where . '&limit=1', $B4A_APP_ID, $B4A_MASTER_KEY);
    if (!empty($result['results'])) {
        $foundUser = $result['results'][0];
    }
}

// ── 6. User না পেলে error ────────────────────────────────────────────────

if ($foundUser === null) {
    echo json_encode(['errorCode' => 4005, 'errorMessage' => 'User not found']);
    exit;
}

// ── 7. User পেলে details তৈরি করে return করা ───────────────────────────

// Nickname: name → nickname → displayName → username
$nickname = $foundUser['name']
    ?? $foundUser['nickname']
    ?? $foundUser['displayName']
    ?? $foundUser['username']
    ?? '';

// Avatar: ParseFile object {url:...} অথবা plain string URL
$avatarUrl = '';
foreach (['avatar', 'profileImage', 'profilePicture', 'photo'] as $avatarKey) {
    if (!empty($foundUser[$avatarKey])) {
        $av = $foundUser[$avatarKey];
        if (is_array($av) && !empty($av['url'])) {
            $avatarUrl = $av['url'];
        } elseif (is_string($av)) {
            $avatarUrl = $av;
        }
        if ($avatarUrl !== '') break;
    }
}

// http → https force
if ($avatarUrl !== '' && stripos($avatarUrl, 'http://') === 0) {
    $avatarUrl = 'https://' . substr($avatarUrl, 7);
}

// Coin balance: coins → coin → balance → 0
$coinBalance = $foundUser['coins'] ?? $foundUser['coin'] ?? $foundUser['balance'] ?? 0;
$coinBalance = is_numeric($coinBalance) ? (int)$coinBalance : 0;

// ── 8. Response ──────────────────────────────────────────────────────────

echo json_encode([
    'errorCode' => 0,
    'data'      => [
        'uid'      => $uid,
        'nickname' => (string)$nickname,
        'avatar'   => (string)$avatarUrl,
        'coin'     => $coinBalance,
    ],
]);
