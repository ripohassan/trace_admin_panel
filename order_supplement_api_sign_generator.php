<?php
/**
 * Order Supplement API - Signature Generator (For Testing Only)
 *
 * Generates the correct MD5 signature for order_supplement_api.php requests.
 * Sign formula: md5(orderId + gameId + roundId + uid + coin + rewardType + winId + key)
 *
 * Note: No `type` or `token` fields — coins are always added (obtain).
 *
 * Usage (GET):
 *   order_supplement_api_sign_generator.php
 *     ?orderId=abcefg12345
 *     &gameId=101
 *     &roundId=abcdeee123
 *     &uid=10001
 *     &coin=100
 *     &rewardType=2
 *     &winId=
 *     &roomid=100000
 */

require 'vendor/autoload.php';
include 'Configs.php';

header('Content-Type: application/json');

try {
    $orderId    = trim((string) ($_GET['orderId']    ?? ''));
    $gameId     = trim((string) ($_GET['gameId']     ?? ''));
    $roundId    = trim((string) ($_GET['roundId']    ?? ''));
    $uid        = trim((string) ($_GET['uid']        ?? ''));
    $coin       = trim((string) ($_GET['coin']       ?? ''));
    $rewardType = trim((string) ($_GET['rewardType'] ?? ''));
    $winId      = trim((string) ($_GET['winId']      ?? ''));
    $roomid     = trim((string) ($_GET['roomid']     ?? ''));

    // Validate required fields
    if ($orderId === '' || $gameId === '' || $roundId === '' || $uid === '' ||
        $coin === '' || $rewardType === '') {
        http_response_code(400);
        echo json_encode([
            'errorCode'    => 4005,
            'errorMessage' => 'Parameter error: Missing required fields',
            'required'     => ['orderId', 'gameId', 'roundId', 'uid', 'coin', 'rewardType'],
            'optional'     => ['winId', 'roomid'],
            'note'         => 'No type or token fields needed — coins are always added',
            'rewardTypes'  => [
                2 => 'normal',
                3 => 'piggy bank rewards',
                4 => 'daily leaderboard rewards',
                5 => 'weekly leaderboard rewards',
                6 => 'novice task rewards',
            ],
        ]);
        exit;
    }

    // Retrieve secret key
    $secretKey = $_ENV['API_SIGN_KEY'] ?? $_ENV['WEBHOOK_KEY'] ?? $_ENV['REST_API_KEY'] ?? '';
    if ($secretKey === '') {
        http_response_code(500);
        echo json_encode(['errorCode' => 4000, 'errorMessage' => 'Server error: signature key not configured']);
        exit;
    }

    // Generate signature: md5(orderId + gameId + roundId + uid + coin + rewardType + winId + key)
    $sign = md5($orderId . $gameId . $roundId . $uid . $coin . $rewardType . $winId . $secretKey);

    // Build ready-to-paste Postman body
    $postmanBody = [
        'orderId'    => $orderId,
        'gameId'     => $gameId,
        'roundId'    => $roundId,
        'uid'        => $uid,
        'coin'       => (int) $coin,
        'rewardType' => (int) $rewardType,
        'winId'      => $winId,
        'sign'       => $sign,
    ];

    if ($roomid !== '') {
        $postmanBody['roomid'] = $roomid;
    }

    http_response_code(200);
    echo json_encode([
        'sign'         => $sign,
        'sign_formula' => 'md5(orderId + gameId + roundId + uid + coin + rewardType + winId + key)',
        'endpoint'     => 'order_supplement_api.php',
        'method'       => 'POST',
        'postman_body' => json_encode($postmanBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'errorCode'    => 4000,
        'errorMessage' => 'Server error',
        'details'      => $e->getMessage(),
    ]);
}
?>
