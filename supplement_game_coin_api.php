<?php
/**
 * Supplementary Polling API
 *
 * Adds game coins to user account for game replenishment polling.
 *
 * Request Body (JSON):
 * {
 *   "orderId": "abcefg12345",
 *   "gameId": "101",
 *   "roundId": "abcdeee123",
 *   "uid": "10001",
 *   "coin": 100,
 *   "rewardType": 2,
 *   "winId": "",
 *   "roomid": "100000",
 *   "sign": "md5_hash"
 * }
 *
 * Response:
 * {
 *   "errorCode": 0,
 *   "data": {
 *     "coin": 9999
 *   }
 * }
 */

// Disable SSL verification for development
stream_context_set_default([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
    ]
]);

require 'vendor/autoload.php';
include 'Configs.php';

use Parse\ParseQuery;
use Parse\ParseException;
use Parse\ParseObject;

header('Content-Type: application/json');

try {
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

    // Validate required fields
    $orderId = trim((string)($data['orderId'] ?? ''));
    $gameId = trim((string)($data['gameId'] ?? ''));
    $roundId = trim((string)($data['roundId'] ?? ''));
    $uid = trim((string)($data['uid'] ?? ''));
    $coin = (int)($data['coin'] ?? 0);
    $rewardType = (int)($data['rewardType'] ?? 0);
    $winId = trim((string)($data['winId'] ?? ''));
    $roomid = trim((string)($data['roomid'] ?? ''));
    $sign = trim((string)($data['sign'] ?? ''));

    // Check required fields
    if ($orderId === '' || $gameId === '' || $roundId === '' || $uid === '' || 
        $coin === 0 || $rewardType === 0 || $sign === '') {
        http_response_code(400);
        echo json_encode(['errorCode' => 4005, 'errorMessage' => 'Parameter error: Missing required fields']);
        exit;
    }

    // Get secret key for signature verification
    $secretKey = $_ENV['API_SIGN_KEY'] ?? $_ENV['WEBHOOK_KEY'] ?? $_ENV['REST_API_KEY'] ?? '';
    if ($secretKey === '') {
        http_response_code(500);
        echo json_encode(['errorCode' => 4000, 'errorMessage' => 'Server error: signature key not configured']);
        exit;
    }

    // Verify signature
    $expectedSign = md5($orderId . $gameId . $roundId . $uid . $coin . $rewardType . $winId . $secretKey);
    if (strtolower($sign) !== strtolower($expectedSign)) {
        http_response_code(401);
        echo json_encode(['errorCode' => 10004, 'errorMessage' => 'Signature error']);
        exit;
    }

    // Find user by uid field
    $userQuery = new ParseQuery('_User');
    $userQuery->equalTo('uid', $uid);
    $user = $userQuery->first();

    // Fallback: try to find by objectId if uid field not found
    if (!$user) {
        $user = ParseObject::create('_User', $uid);
        try {
            $user->fetch();
        } catch (ParseException $e) {
            http_response_code(404);
            echo json_encode(['errorCode' => 4000, 'errorMessage' => 'User not found']);
            exit;
        }
    }

    // Check for duplicate orderId (deduplication)
    $transactionQuery = new ParseQuery('GameTransaction');
    $transactionQuery->equalTo('orderId', $orderId);
    $existingTransaction = $transactionQuery->first();

    if ($existingTransaction) {
        // Return the previous coin balance for duplicate orders
        $currentCoin = (int)($user->get('coins') ?? 0);
        http_response_code(200);
        echo json_encode([
            'errorCode' => 0,
            'data' => ['coin' => $currentCoin]
        ]);
        exit;
    }

    // Get current coin balance
    $currentCoin = (int)($user->get('coins') ?? 0);

    // Calculate new coin balance (always obtaining/replenishing)
    $newCoin = $currentCoin + $coin;

    // Update user coins
    $user->set('coins', $newCoin);
    $user->save();

    // Log transaction for deduplication
    $transaction = new ParseObject('GameTransaction');
    $transaction->set('orderId', $orderId);
    $transaction->set('uid', $uid);
    $transaction->set('gameId', $gameId);
    $transaction->set('roundId', $roundId);
    $transaction->set('coin', $coin);
    $transaction->set('type', 2); // Type 2: Obtain/Add
    $transaction->set('rewardType', $rewardType);
    $transaction->set('winId', $winId);
    $transaction->set('roomid', $roomid);
    $transaction->set('oldCoin', $currentCoin);
    $transaction->set('newCoin', $newCoin);
    $transaction->save();

    http_response_code(200);
    echo json_encode([
        'errorCode' => 0,
        'data' => ['coin' => $newCoin]
    ]);

} catch (ParseException $e) {
    http_response_code(500);
    echo json_encode([
        'errorCode' => 4000,
        'errorMessage' => 'Network timeout',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'errorCode' => 4000,
        'errorMessage' => 'Network timeout',
        'details' => $e->getMessage()
    ]);
}
