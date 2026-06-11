<?php
/**
 * Supplementary Polling API - Signature Generator (For Testing Only)
 *
 * Helper endpoint to generate correct MD5 signatures for testing
 *
 * URL: supplement_game_coin_api_sign_generator.php?gameId=101&roundId=abc123&uid=10001&coin=100&rewardType=2&orderId=order123&winId=
 */

require 'vendor/autoload.php';
include 'Configs.php';

header('Content-Type: application/json');

try {
    $gameId = trim((string)($_GET['gameId'] ?? ''));
    $roundId = trim((string)($_GET['roundId'] ?? ''));
    $uid = trim((string)($_GET['uid'] ?? ''));
    $coin = trim((string)($_GET['coin'] ?? ''));
    $rewardType = trim((string)($_GET['rewardType'] ?? ''));
    $orderId = trim((string)($_GET['orderId'] ?? ''));
    $winId = trim((string)($_GET['winId'] ?? ''));
    $roomid = trim((string)($_GET['roomid'] ?? ''));

    if ($gameId === '' || $roundId === '' || $uid === '' || $coin === '' || 
        $rewardType === '' || $orderId === '') {
        http_response_code(400);
        echo json_encode([
            'errorCode' => 4005,
            'errorMessage' => 'Parameter error: Missing required fields (gameId, roundId, uid, coin, rewardType, orderId)',
            'required' => [
                'gameId', 'roundId', 'uid', 'coin', 'rewardType', 'orderId'
            ],
            'optional' => ['winId', 'roomid']
        ]);
        exit;
    }

    // Get secret key
    $secretKey = $_ENV['API_SIGN_KEY'] ?? $_ENV['WEBHOOK_KEY'] ?? $_ENV['REST_API_KEY'] ?? '';
    if ($secretKey === '') {
        http_response_code(500);
        echo json_encode(['errorCode' => 4000, 'errorMessage' => 'Server error: signature key not configured']);
        exit;
    }

    // Generate signature
    $sign = md5($orderId . $gameId . $roundId . $uid . $coin . $rewardType . $winId . $secretKey);

    // Build Postman body
    $postmanBody = [
        'orderId' => $orderId,
        'gameId' => $gameId,
        'roundId' => $roundId,
        'uid' => $uid,
        'coin' => (int)$coin,
        'rewardType' => (int)$rewardType,
        'winId' => $winId,
        'sign' => $sign
    ];
    
    if ($roomid !== '') {
        $postmanBody['roomid'] = $roomid;
    }

    http_response_code(200);
    echo json_encode([
        'sign' => $sign,
        'postman_body' => json_encode($postmanBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'errorCode' => 4000,
        'errorMessage' => 'Network timeout',
        'details' => $e->getMessage()
    ]);
}
?>
