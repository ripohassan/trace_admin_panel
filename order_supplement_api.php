<?php
/**
 * Order Supplement API (High-Performance Optimized)
 *
 * Handles re-notification for orders that exhausted the default 3-retry
 * mechanism in the game currency update flow. The APP service calls this
 * endpoint to manually trigger additional coin crediting for a specific order.
 *
 * Key differences from update_game_coin_api:
 *  - No `type` field — coins are always added (obtain)
 *  - No `token` field
 *  - Intended for targeted, on-demand order recovery — not periodic polling
 *  - Logged to a separate `OrderSupplement` class for auditing
 *  - Deduplication checks both GameTransaction AND OrderSupplement
 *
 * Sign formula:
 *   md5(orderId + gameId + roundId + uid + coin + rewardType + winId + key)
 *
 * Request Body (JSON):
 * {
 *   "orderId":    "abcefg12345",
 *   "gameId":     "101",
 *   "roundId":    "abcdeee123",
 *   "uid":        "10001",
 *   "coin":       100,
 *   "rewardType": 2,
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
 */

header('Content-Type: application/json');

// ── 1. Request parse ─────────────────────────────────────────────────────

$rawBody = file_get_contents('php://input');
if (!$rawBody) {
    http_response_code(400);
    echo json_encode([
        'errorCode'    => 4005,
        'errorMessage' => 'Empty request body',
    ]);
    exit;
}

$data = json_decode($rawBody, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        'errorCode'    => 4005,
        'errorMessage' => 'Invalid JSON body',
    ]);
    exit;
}

// ── 2. Validate required fields ──────────────────────────────────────────

$orderId    = trim((string) ($data['orderId']    ?? ''));
$gameId     = trim((string) ($data['gameId']     ?? ''));
$roundId    = trim((string) ($data['roundId']    ?? ''));
$uid        = trim((string) ($data['uid']        ?? ''));
$coin       = (int)         ($data['coin']       ?? 0);
$rewardType = (int)         ($data['rewardType'] ?? 0);
$winId      = trim((string) ($data['winId']      ?? ''));
$roomid     = trim((string) ($data['roomid']     ?? ''));
$sign       = trim((string) ($data['sign']       ?? ''));

if ($orderId === '' || $gameId === '' || $roundId === '' || $uid === '' ||
    $coin === 0 || $rewardType === 0 || $sign === '') {
    http_response_code(400);
    echo json_encode(['errorCode' => 4005, 'errorMessage' => 'Parameter error: Missing required fields']);
    exit;
}

$validRewardTypes = [2, 3, 4, 5, 6];
if (!in_array($rewardType, $validRewardTypes, true)) {
    http_response_code(400);
    echo json_encode(['errorCode' => 4005, 'errorMessage' => 'Parameter error: invalid rewardType (2=normal,3=piggy bank,4=daily leaderboard,5=weekly leaderboard,6=novice task)']);
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
$expectedSign = md5($orderId . $gameId . $roundId . $uid . $coin . $rewardType . $winId . $secretKey);
if (strtolower($sign) !== strtolower($expectedSign)) {
    http_response_code(401);
    echo json_encode(['errorCode' => 10004, 'errorMessage' => 'Signature error']);
    exit;
}

// ── 4. Fast Local Deduplication Check (< 1ms) ───────────────────────────

$orderCacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'b4a_orders';
$orderCacheFile = $orderCacheDir . DIRECTORY_SEPARATOR . md5('ord_' . $orderId) . '.json';

if (file_exists($orderCacheFile) && (time() - filemtime($orderCacheFile) < 3600)) {
    http_response_code(409);
    echo json_encode([
        'errorCode'    => 10003,
        'errorMessage' => 'Duplicate order number'
    ]);
    exit;
}

// ── 5. Parallel Back4App Queries (curl_multi for 3 endpoints) ───────────

$B4A_APP_ID    = $_ENV['APPLICATION_ID'] ?? $_ENV['PARSE_APP_ID'] ?? 'NXgg3EtUgqRLryHea3pjIHWf0qNdyWTxbfZAFQ9b';
$B4A_MASTER_KEY = $_ENV['PARSE_MASTER_KEY'] ?? 'cx30LCUA8mfrKhS88Zetjo5PU5syyMk2Vh49n54u';
$B4A_BASE      = 'https://parseapi.back4app.com';

$curlHeaders = [
    'X-Parse-Application-Id: ' . $B4A_APP_ID,
    'X-Parse-Master-Key: '     . $B4A_MASTER_KEY,
    'Content-Type: application/json',
];

$mh = curl_multi_init();

// 1. Find User
$whereUser = is_numeric($uid)
    ? ['$or' => [['uid' => (int) $uid], ['uid' => (string) $uid]]]
    : ['uid' => (string) $uid];
$userUrl = $B4A_BASE . '/users?where=' . urlencode(json_encode($whereUser)) . '&keys=' . urlencode('uid,credit,balance,coins,coin') . '&limit=1';

$chUser = curl_init($userUrl);
curl_setopt_array($chUser, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 4,
    CURLOPT_CONNECTTIMEOUT => 2,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    CURLOPT_TCP_NODELAY => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
    CURLOPT_HTTPHEADER => $curlHeaders,
]);
curl_multi_add_handle($mh, $chUser);

// 2. Check GameTransaction
$txWhere = urlencode(json_encode(['orderId' => $orderId]));
$txUrl = $B4A_BASE . '/classes/GameTransaction?where=' . $txWhere . '&keys=orderId&limit=1';

$chTx = curl_init($txUrl);
curl_setopt_array($chTx, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 4,
    CURLOPT_CONNECTTIMEOUT => 2,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    CURLOPT_TCP_NODELAY => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
    CURLOPT_HTTPHEADER => $curlHeaders,
]);
curl_multi_add_handle($mh, $chTx);

// 3. Check OrderSupplement
$supWhere = urlencode(json_encode(['orderId' => $orderId]));
$supUrl = $B4A_BASE . '/classes/OrderSupplement?where=' . $supWhere . '&keys=orderId&limit=1';

$chSup = curl_init($supUrl);
curl_setopt_array($chSup, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 4,
    CURLOPT_CONNECTTIMEOUT => 2,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    CURLOPT_TCP_NODELAY => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
    CURLOPT_HTTPHEADER => $curlHeaders,
]);
curl_multi_add_handle($mh, $chSup);

// Execute all 3 in parallel
$running = null;
do {
    curl_multi_exec($mh, $running);
    curl_multi_select($mh, 0.05);
} while ($running > 0);

$userBody = curl_multi_getcontent($chUser);
$txBody = curl_multi_getcontent($chTx);
$supBody = curl_multi_getcontent($chSup);

curl_multi_remove_handle($mh, $chUser);
curl_multi_remove_handle($mh, $chTx);
curl_multi_remove_handle($mh, $chSup);
curl_multi_close($mh);
curl_close($chUser);
curl_close($chTx);
curl_close($chSup);

// Parse User Result
$foundUser = null;
if ($userBody) {
    $userDecoded = json_decode($userBody, true);
    if (!empty($userDecoded['results'][0])) {
        $foundUser = $userDecoded['results'][0];
    }
}

if ($foundUser === null) {
    http_response_code(200);
    echo json_encode(['errorCode' => 4000, 'errorMessage' => 'User not found']);
    exit;
}

// Check Deduplication (GameTransaction or OrderSupplement)
$isDuplicate = false;
if ($txBody) {
    $txDecoded = json_decode($txBody, true);
    if (!empty($txDecoded['results'])) {
        $isDuplicate = true;
    }
}
if (!$isDuplicate && $supBody) {
    $supDecoded = json_decode($supBody, true);
    if (!empty($supDecoded['results'])) {
        $isDuplicate = true;
    }
}

if ($isDuplicate) {
    if (!is_dir($orderCacheDir)) {
        @mkdir($orderCacheDir, 0777, true);
    }
    @file_put_contents($orderCacheFile, '1', LOCK_EX);
    http_response_code(200);
    echo json_encode([
        'errorCode'    => 10003,
        'errorMessage' => 'Duplicate order number'
    ]);
    exit;
}

$userObjectId = $foundUser['objectId'];
$currentCoin  = (int) ($foundUser['credit'] ?? $foundUser['balance'] ?? $foundUser['coins'] ?? 0);

// ── 6. Calculate new coin balance (always add) ───────────────────────────

$newCoin = $currentCoin + $coin;

// ── 7. Update user coins via REST API ────────────────────────────────────

$chUpdate = curl_init($B4A_BASE . '/classes/_User/' . $userObjectId);
curl_setopt_array($chUpdate, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 4,
    CURLOPT_CONNECTTIMEOUT => 2,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_CUSTOMREQUEST => 'PUT',
    CURLOPT_POSTFIELDS => json_encode(['credit' => $newCoin]),
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    CURLOPT_TCP_NODELAY => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
    CURLOPT_HTTPHEADER => $curlHeaders,
]);
$updateBody = curl_exec($chUpdate);
$updateCode = curl_getinfo($chUpdate, CURLINFO_HTTP_CODE);
curl_close($chUpdate);

if ($updateBody === false || $updateCode < 200 || $updateCode >= 300) {
    http_response_code(500);
    echo json_encode(['errorCode' => 4000, 'errorMessage' => 'Failed to update user coins']);
    exit;
}

// ── 8. Local cache update & deduplication store ──────────────────────────

// Invalidate & update user info cache
$userCacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'b4a_user_cache';
$userCacheFile = $userCacheDir . DIRECTORY_SEPARATOR . md5('user_info_' . $uid) . '.json';
if (file_exists($userCacheFile)) {
    $cached = json_decode(@file_get_contents($userCacheFile), true);
    if (is_array($cached) && !isset($cached['__not_found'])) {
        $cached['coin'] = $newCoin;
        @file_put_contents($userCacheFile, json_encode($cached), LOCK_EX);
    } else {
        @unlink($userCacheFile);
    }
}

// Store order deduplication cache
if (!is_dir($orderCacheDir)) {
    @mkdir($orderCacheDir, 0777, true);
}
@file_put_contents($orderCacheFile, '1', LOCK_EX);

// ── 9. Log to OrderSupplement for audit & deduplication ──────────────────

$supplementLog = [
    'orderId'    => $orderId,
    'uid'        => $uid,
    'gameId'     => $gameId,
    'roundId'    => $roundId,
    'coin'       => $coin,
    'type'       => 2, // Always obtain/add
    'rewardType' => $rewardType,
    'winId'      => $winId,
    'roomid'     => $roomid,
    'oldCoin'    => $currentCoin,
    'newCoin'    => $newCoin,
    'source'     => 'order_supplement',
];

$chSupPost = curl_init($B4A_BASE . '/classes/OrderSupplement');
curl_setopt_array($chSupPost, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 4,
    CURLOPT_CONNECTTIMEOUT => 2,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($supplementLog),
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    CURLOPT_TCP_NODELAY => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
    CURLOPT_HTTPHEADER => $curlHeaders,
]);
curl_exec($chSupPost);
curl_close($chSupPost);

// ── 10. Response ─────────────────────────────────────────────────────────

http_response_code(200);
echo json_encode([
    'errorCode' => 0,
    'data'      => ['coin' => $newCoin]
]);

