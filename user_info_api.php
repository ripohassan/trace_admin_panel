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

try {
    $query = new ParseQuery('_User');
    $query->equalTo('uid', $uid);
    $query->limit(1);
    $users = $query->find(true);

    if (count($users) === 0) {
        $query = new ParseQuery('_User');
        $query->equalTo('objectId', $uid);
        $query->limit(1);
        $users = $query->find(true);
    }

    if (count($users) === 0) {
        echo json_encode(['errorCode' => 4005, 'errorMessage' => 'User not found']);
        exit;
    }

    $user = $users[0];
    $nickname = $user->get('name') ?? $user->get('username') ?? '';
    $avatarField = $user->get('avatar');
    $avatarUrl = '';

    if ($avatarField !== null) {
        if (is_object($avatarField) && method_exists($avatarField, 'getURL')) {
            $avatarUrl = $avatarField->getURL();
        } else {
            $avatarUrl = (string)$avatarField;
        }
    }

    if ($avatarUrl !== '' && stripos($avatarUrl, 'http://') === 0) {
        $avatarUrl = 'https://' . substr($avatarUrl, 7);
    }

    $coinBalance = $user->get('coins');
    $coinBalance = is_numeric($coinBalance) ? (int)$coinBalance : 0;

    echo json_encode([
        'errorCode' => 0,
        'data' => [
            'uid' => $uid,
            'nickname' => (string)$nickname,
            'avatar' => (string)$avatarUrl,
            'coin' => $coinBalance,
        ],
    ]);
} catch (ParseException $e) {
    http_response_code(500);
    echo json_encode(['errorCode' => 4000, 'errorMessage' => 'Network timeout', 'details' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['errorCode' => 4000, 'errorMessage' => 'Network timeout', 'details' => $e->getMessage()]);
}
