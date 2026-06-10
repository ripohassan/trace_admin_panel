<?php
/**
 * User Info API
 *
 * Request Body (JSON):
 * {
 *   "gameId": "101",
 *   "uid": "10001",
 *   "token": "ABCD123",
 *   "roomId": "100000",   // optional
 *   "sign": "md5(gameId + uid + token + roomId + key)"
 * }
 *
 * Response:
 * {
 *   "errorCode": 0,
 *   "data": {
 *     "uid": "10001",
 *     "nickname": "lucky",
 *     "avatar": "https://leadercc.com/avatar.png",
 *     "coin": 10000
 *   }
 * }
 */

// Disable SSL verification for development BEFORE loading Parse SDK
// This must be done before require/include to take effect
stream_context_set_default([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
    ]
]);

require 'vendor/autoload.php';
include 'Configs.php';

use Parse\ParseException;
use Parse\ParseQuery;

header('Content-Type: application/json');

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
$uid = trim((string)($data['uid'] ?? ''));
$token = trim((string)($data['token'] ?? ''));
$roomId = trim((string)($data['roomId'] ?? ''));
$sign = trim((string)($data['sign'] ?? ''));

$secretKey = $_ENV['API_SIGN_KEY'] ?? $_ENV['WEBHOOK_KEY'] ?? $_ENV['REST_API_KEY'] ?? '';
if ($secretKey === '') {
    http_response_code(500);
    echo json_encode(['errorCode' => 4000, 'errorMessage' => 'Server error: signature key not configured']);
    exit;
}

if ($gameId === '' || $uid === '' || $token === '' || $sign === '') {
    http_response_code(400);
    echo json_encode(['errorCode' => 4005, 'errorMessage' => 'Parameter error: Missing required fields']);
    exit;
}

$expectedSign = md5($gameId . $uid . $token . $roomId . $secretKey);
if (strtolower($sign) !== strtolower($expectedSign)) {
    http_response_code(401);
    echo json_encode(['errorCode' => 10004, 'errorMessage' => 'Signature error']);
    exit;
}

// ── Back4App REST API diye user search ──────────────────────────────────
$appId      = $parse_app_id;
$masterKey  = $parse_master_key;
$serverUrl  = 'https://parseapi.back4app.com';

/**
 * Back4App REST API call helper
 */
function back4appGet(string $endpoint, string $appId, string $masterKey): ?array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $endpoint,
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
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode >= 400) {
        return null;
    }
    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : null;
}

$foundUser = null;

// 1. uid field দিয়ে খোঁজা (custom field)
$encoded  = urlencode(json_encode(['uid' => $uid]));
$url      = $serverUrl . '/1/users?where=' . $encoded . '&limit=1';
$result   = back4appGet($url, $appId, $masterKey);
if (!empty($result['results'])) {
    $foundUser = $result['results'][0];
}

// 2. userId field দিয়ে খোঁজা (অনেক app-এ userId নামে থাকে)
if ($foundUser == null) {
    $encoded  = urlencode(json_encode(['userId' => $uid]));
    $url      = $serverUrl . '/1/users?where=' . $encoded . '&limit=1';
    $result   = back4appGet($url, $appId, $masterKey);
    if (!empty($result['results'])) {
        $foundUser = $result['results'][0];
    }
}

// 3. objectId দিয়ে সরাসরি খোঁজা
if ($foundUser == null) {
    $url    = $serverUrl . '/1/users/' . urlencode($uid);
    $result = back4appGet($url, $appId, $masterKey);
    if (!empty($result['objectId'])) {
        $foundUser = $result;
    }
}

// 4. username দিয়ে খোঁজা
if ($foundUser == null) {
    $encoded  = urlencode(json_encode(['username' => $uid]));
    $url      = $serverUrl . '/1/users?where=' . $encoded . '&limit=1';
    $result   = back4appGet($url, $appId, $masterKey);
    if (!empty($result['results'])) {
        $foundUser = $result['results'][0];
    }
}

if ($foundUser == null) {
    echo json_encode([
        'errorCode'    => 4005,
        'errorMessage' => 'User not found',
        'debug'        => 'Searched uid, userId, objectId, username fields in _User',
    ]);
    exit;
}

// ── Response তৈরি করা ─────────────────────────────────────────────────
$nickname = $foundUser['name']
    ?? $foundUser['nickname']
    ?? $foundUser['displayName']
    ?? $foundUser['username']
    ?? '';

// Avatar URL বের করা (ParseFile object বা plain string দুটোই handle)
$avatarUrl = '';
if (!empty($foundUser['avatar'])) {
    $av = $foundUser['avatar'];
    if (is_array($av) && !empty($av['url'])) {
        $avatarUrl = $av['url'];
    } elseif (is_string($av)) {
        $avatarUrl = $av;
    }
}
if (!empty($foundUser['profileImage'])) {
    $av = $foundUser['profileImage'];
    if (is_array($av) && !empty($av['url'])) {
        $avatarUrl = $av['url'];
    } elseif (is_string($av)) {
        $avatarUrl = $av;
    }
}

// http → https
if ($avatarUrl !== '' && stripos($avatarUrl, 'http://') === 0) {
    $avatarUrl = 'https://' . substr($avatarUrl, 7);
}

$coinBalance = $foundUser['coins'] ?? $foundUser['coin'] ?? $foundUser['balance'] ?? 0;
$coinBalance = is_numeric($coinBalance) ? (int)$coinBalance : 0;

echo json_encode([
    'errorCode' => 0,
    'data'      => [
        'uid'      => $uid,
        'nickname' => (string)$nickname,
        'avatar'   => (string)$avatarUrl,
        'coin'     => $coinBalance,
    ],
]);
