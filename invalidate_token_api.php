<?php
/**
 * Invalidate Token API
 *
 * Invalidates a game session token immediately by deleting the session from Parse
 * and logging it into the InvalidatedSession class to allow a 1-minute grace period.
 *
 * Request Body (JSON):
 * {
 *   "token": "ABCD123",
 *   "uid": "10001",
 *   "sign": "md5_hash"
 * }
 *
 * Response:
 * {
 *   "errorCode": 0,
 *   "errorMessage": "Token invalidated successfully"
 * }
 */

header('Content-Type: application/json');

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

// Back4App REST Request Helper
function b4aRestRequest(string $method, string $path, ?array $data = null)
{
    global $parse_app_id, $parse_master_key;
    $url = 'https://parseapi.back4app.com/' . ltrim($path, '/');
    
    $ch = curl_init();
    $headers = [
        'X-Parse-Application-Id: ' . $parse_app_id,
        'X-Parse-Master-Key: '     . $parse_master_key,
        'Content-Type: application/json',
    ];

    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_CUSTOMREQUEST  => $method,
    ]);

    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $body     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'data' => json_decode($body, true)
    ];
}

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

    $token = trim((string)($data['token'] ?? ''));
    $uid = trim((string)($data['uid'] ?? ''));
    $sign = trim((string)($data['sign'] ?? ''));

    if ($token === '' || $uid === '' || $sign === '') {
        http_response_code(400);
        echo json_encode(['errorCode' => 4005, 'errorMessage' => 'Parameter error: token, uid, sign are required']);
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
    $expectedSign = md5($token . $uid . $secretKey);
    if (strtolower($sign) !== strtolower($expectedSign)) {
        http_response_code(401);
        echo json_encode(['errorCode' => 10004, 'errorMessage' => 'Signature error']);
        exit;
    }

    // 1. Create a record in InvalidatedSession class for the 1-minute grace period
    $invalidatedAt = new DateTime();
    $b4aRestRequest('POST', 'classes/InvalidatedSession', [
        'token' => $token,
        'uid' => $uid,
        'invalidatedAt' => [
            '__type' => 'Date',
            'iso' => $invalidatedAt->format('Y-m-d\TH:i:s.v\Z')
        ]
    ]);

    // 2. Query the session to delete it
    $queryUrl = 'sessions?where=' . urlencode(json_encode(['sessionToken' => $token]));
    $queryResult = b4aRestRequest('GET', $queryUrl);

    if (!empty($queryResult['data']['results'])) {
        $sessionObj = $queryResult['data']['results'][0];
        $sessionObjectId = $sessionObj['objectId'];
        
        // Delete the session from Parse
        b4aRestRequest('DELETE', 'sessions/' . $sessionObjectId);
    }

    http_response_code(200);
    echo json_encode([
        'errorCode' => 0,
        'errorMessage' => 'Token invalidated successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'errorCode' => 4000,
        'errorMessage' => 'Network timeout',
        'details' => $e->getMessage()
    ]);
}
