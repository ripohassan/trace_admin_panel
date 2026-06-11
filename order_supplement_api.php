<?php
/**
 * Order Supplement API
 *
 * Handles re-notification for orders that exhausted the default 3-retry
 * mechanism in the game currency update flow. The APP service calls this
 * endpoint to manually trigger additional coin crediting for a specific order.
 *
 * Key differences from supplement_game_coin_api (polling):
 *  - Includes `type` and `token` fields (same signature formula as update_game_coin_api)
 *  - Intended for targeted, on-demand order recovery — not periodic polling
 *  - Logged to a separate `OrderSupplement` class for auditing
 *
 * Sign formula:
 *   md5(orderId + gameId + roundId + uid + coin + type + rewardType + token + winId + key)
 *
 * Request Body (JSON):
 * {
 *   "orderId":    "abcefg12345",
 *   "gameId":     "101",
 *   "roundId":    "abcdeee123",
 *   "uid":        "10001",
 *   "coin":       100,
 *   "type":       2,
 *   "rewardType": 2,
 *   "token":      "ABCD123",
 *   "winId":      "",
 *   "roomid":     "100000",
 *   "sign":       "md5_hash"
 * }
 *
 * rewardType values:
 *   2 = normal
 *   3 = piggy bank rewards
 *   4 = daily leaderboard rewards
 *   5 = weekly leaderboard rewards
 *   6 = novice task rewards
 *
 * Response:
 * {
 *   "errorCode": 0,
 *   "data": {
 *     "coin": 9999
 *   }
 * }
 *
 * Error Codes:
 *   4005  - Parameter error (missing required fields)
 *   10004 - Signature error
 *   4000  - Network timeout / server error
 *   4004  - Insufficient game coins (type=1 consume only)
 */

header('Content-Type: application/json');

// SSL bypass (development)
stream_context_set_default([
    'ssl' => [
        'verify_peer'       => false,
        'verify_peer_name'  => false,
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

// ── 2. Validate required fields ──────────────────────────────────────────

$orderId    = trim((string) ($data['orderId']    ?? ''));
$gameId     = trim((string) ($data['gameId']     ?? ''));
$roundId    = trim((string) ($data['roundId']    ?? ''));
$uid        = trim((string) ($data['uid']        ?? ''));
$coin       = (int)         ($data['coin']       ?? 0);
$type       = (int)         ($data['type']       ?? 0);
$rewardType = (int)         ($data['rewardType'] ?? 0);
$token      = trim((string) ($data['token']      ?? ''));
$winId      = trim((string) ($data['winId']      ?? ''));
$roomid     = trim((string) ($data['roomid']     ?? ''));
$sign       = trim((string) ($data['sign']       ?? ''));

// Validate required fields (orderId, gameId, roundId, uid, coin, type, rewardType, token, sign)
if ($orderId === '' || $gameId === '' || $roundId === '' || $uid === '' ||
    $coin === 0 || $type === 0 || $rewardType === 0 || $token === '' || $sign === '') {
    http_response_code(400);
    echo json_encode(['errorCode' => 4005, 'errorMessage' => 'Parameter error: Missing required fields']);
    exit;
}

// Validate type value (1 = consume, 2 = obtain)
if ($type !== 1 && $type !== 2) {
    http_response_code(400);
    echo json_encode(['errorCode' => 4005, 'errorMessage' => 'Parameter error: type must be 1 (consume) or 2 (obtain)']);
    exit;
}

// Validate rewardType value
$validRewardTypes = [2, 3, 4, 5, 6];
if (!in_array($rewardType, $validRewardTypes, true)) {
    http_response_code(400);
    echo json_encode(['errorCode' => 4005, 'errorMessage' => 'Parameter error: invalid rewardType (2=normal,3=piggy bank,4=daily leaderboard,5=weekly leaderboard,6=novice task)']);
    exit;
}

// ── 3. Signature verify ──────────────────────────────────────────────────

$secretKey = $_ENV['API_SIGN_KEY'] ?? $_ENV['WEBHOOK_KEY'] ?? $_ENV['REST_API_KEY'] ?? '';
if ($secretKey === '') {
    http_response_code(500);
    echo json_encode(['errorCode' => 4000, 'errorMessage' => 'Server error: signature key not configured']);
    exit;
}

// Sign = md5(orderId + gameId + roundId + uid + coin + type + rewardType + token + winId + key)
$expectedSign = md5($orderId . $gameId . $roundId . $uid . $coin . $type . $rewardType . $token . $winId . $secretKey);
if (strtolower($sign) !== strtolower($expectedSign)) {
    http_response_code(401);
    echo json_encode(['errorCode' => 10004, 'errorMessage' => 'Signature error']);
    exit;
}

// ── 4. Back4App REST API helpers ─────────────────────────────────────────

$B4A_APP_ID    = $parse_app_id;
$B4A_MASTER_KEY = $parse_master_key;
$B4A_BASE      = 'https://parseapi.back4app.com';

/**
 * Back4App REST API GET request.
 */
function b4aGet(string $url, string $appId, string $masterKey): ?array
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

/**
 * Back4App REST API PUT request (update object).
 */
function b4aPut(string $url, array $data, string $appId, string $masterKey): ?array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => json_encode($data),
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

/**
 * Back4App REST API POST request (create object).
 */
function b4aPost(string $url, array $data, string $appId, string $masterKey): ?array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
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

// ── 5. Find user by uid ──────────────────────────────────────────────────

$foundUser = null;

// try-1: uid as Integer
$where  = urlencode(json_encode(['uid' => (int) $uid]));
$result = b4aGet($B4A_BASE . '/classes/_User?where=' . $where . '&limit=1', $B4A_APP_ID, $B4A_MASTER_KEY);

if (!empty($result['results'])) {
    $foundUser = $result['results'][0];
}

// try-2: uid as String
if ($foundUser === null) {
    $where  = urlencode(json_encode(['uid' => (string) $uid]));
    $result = b4aGet($B4A_BASE . '/classes/_User?where=' . $where . '&limit=1', $B4A_APP_ID, $B4A_MASTER_KEY);
    if (!empty($result['results'])) {
        $foundUser = $result['results'][0];
    }
}

if ($foundUser === null) {
    http_response_code(404);
    echo json_encode(['errorCode' => 4000, 'errorMessage' => 'User not found']);
    exit;
}

$userObjectId = $foundUser['objectId'];
$currentCoin  = (int) ($foundUser['coins'] ?? $foundUser['coin'] ?? $foundUser['balance'] ?? 0);

// ── 6. Order deduplication (check both GameTransaction and OrderSupplement) ──

// Check original GameTransaction first — if it already processed, return current balance
$txWhere  = urlencode(json_encode(['orderId' => $orderId]));
$txResult = b4aGet($B4A_BASE . '/classes/GameTransaction?where=' . $txWhere . '&limit=1', $B4A_APP_ID, $B4A_MASTER_KEY);

if (!empty($txResult['results'])) {
    // Already processed by the main update_game_coin_api — idempotent success
    http_response_code(200);
    echo json_encode([
        'errorCode' => 0,
        'data'      => ['coin' => $currentCoin]
    ]);
    exit;
}

// Check OrderSupplement log — prevent duplicate supplement processing
$supWhere  = urlencode(json_encode(['orderId' => $orderId]));
$supResult = b4aGet($B4A_BASE . '/classes/OrderSupplement?where=' . $supWhere . '&limit=1', $B4A_APP_ID, $B4A_MASTER_KEY);

if (!empty($supResult['results'])) {
    // Already supplemented — return current balance without double-crediting
    http_response_code(200);
    echo json_encode([
        'errorCode' => 0,
        'data'      => ['coin' => $currentCoin]
    ]);
    exit;
}

// ── 7. Calculate new coin balance ────────────────────────────────────────

$newCoin = $currentCoin;

if ($type === 1) {
    // Type 1: Consume coins
    $newCoin = $currentCoin - $coin;
    if ($newCoin < 0) {
        http_response_code(400);
        echo json_encode(['errorCode' => 4004, 'errorMessage' => 'Insufficient game coins']);
        exit;
    }
} elseif ($type === 2) {
    // Type 2: Obtain / credit coins
    $newCoin = $currentCoin + $coin;
}

// ── 8. Update user coins via REST API ────────────────────────────────────

$updateResult = b4aPut(
    $B4A_BASE . '/classes/_User/' . $userObjectId,
    ['coins' => $newCoin],
    $B4A_APP_ID,
    $B4A_MASTER_KEY
);

if ($updateResult === null) {
    http_response_code(500);
    echo json_encode(['errorCode' => 4000, 'errorMessage' => 'Failed to update user coins']);
    exit;
}

// ── 9. Log to OrderSupplement for audit & deduplication ──────────────────

$supplementLog = [
    'orderId'    => $orderId,
    'uid'        => $uid,
    'gameId'     => $gameId,
    'roundId'    => $roundId,
    'coin'       => $coin,
    'type'       => $type,
    'rewardType' => $rewardType,
    'token'      => $token,
    'winId'      => $winId,
    'roomid'     => $roomid,
    'oldCoin'    => $currentCoin,
    'newCoin'    => $newCoin,
    'source'     => 'order_supplement', // distinguish from polling & main api
];

b4aPost($B4A_BASE . '/classes/OrderSupplement', $supplementLog, $B4A_APP_ID, $B4A_MASTER_KEY);

// ── 10. Response ─────────────────────────────────────────────────────────

http_response_code(200);
echo json_encode([
    'errorCode' => 0,
    'data'      => ['coin' => $newCoin]
]);
