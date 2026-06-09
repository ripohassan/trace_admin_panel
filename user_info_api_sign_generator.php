<?php
/**
 * User Info API - Signature Generator (For Testing Only)
 * 
 * This helper generates the correct "sign" value for your Postman requests.
 * 
 * Usage:
 * GET /user_info_api_sign_generator.php?gameId=101&uid=10001&token=ABCD123&roomId=100000
 * 
 * Returns:
 * {
 *   "gameId": "101",
 *   "uid": "10001",
 *   "token": "ABCD123",
 *   "roomId": "100000",
 *   "sign": "computed_md5_hash",
 *   "postman_body": { ... }  // Copy-paste ready
 * }
 */

require 'vendor/autoload.php';
include 'Configs.php';

header('Content-Type: application/json');

$gameId = trim((string)($_GET['gameId'] ?? ''));
$uid = trim((string)($_GET['uid'] ?? ''));
$token = trim((string)($_GET['token'] ?? ''));
$roomId = trim((string)($_GET['roomId'] ?? ''));

if ($gameId === '' || $uid === '' || $token === '') {
    http_response_code(400);
    echo json_encode([
        'errorCode' => 4005,
        'errorMessage' => 'Parameter error: Missing required params (gameId, uid, token)',
        'example_url' => '?gameId=101&uid=10001&token=ABCD123&roomId=100000'
    ]);
    exit;
}

$secretKey = $_ENV['API_SIGN_KEY'] ?? $_ENV['WEBHOOK_KEY'] ?? $_ENV['REST_API_KEY'] ?? '';
if ($secretKey === '') {
    http_response_code(500);
    echo json_encode(['errorCode' => 4000, 'errorMessage' => 'Network timeout: API_SIGN_KEY not configured']);
    exit;
}

$sign = md5($gameId . $uid . $token . $roomId . $secretKey);

$postmanBody = [
    'gameId' => $gameId,
    'uid' => $uid,
    'token' => $token,
    'roomId' => $roomId,
    'sign' => $sign,
];

echo json_encode([
    'success' => true,
    'gameId' => $gameId,
    'uid' => $uid,
    'token' => $token,
    'roomId' => $roomId,
    'sign' => $sign,
    'postman_body' => $postmanBody,
    'note' => 'Copy the postman_body object into Postman as your request body. Then POST to /user_info_api.php'
]);
?>
