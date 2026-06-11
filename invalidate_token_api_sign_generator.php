<?php
/**
 * Invalidate Token API - Signature Generator (For Testing Only)
 *
 * Helper endpoint to generate correct MD5 signatures for testing
 *
 * URL: invalidate_token_api_sign_generator.php?token=ABCD123&uid=10001
 */

require 'vendor/autoload.php';
include 'Configs.php';

header('Content-Type: application/json');

$token = trim((string)($_GET['token'] ?? ''));
$uid = trim((string)($_GET['uid'] ?? ''));

if ($token === '' || $uid === '') {
    http_response_code(400);
    echo json_encode([
        'errorCode' => 4005,
        'errorMessage' => 'Parameter error: Missing required params (token, uid)',
        'example_url' => '?token=ABCD123&uid=10001'
    ]);
    exit;
}

$secretKey = $_ENV['API_SIGN_KEY'] ?? $_ENV['WEBHOOK_KEY'] ?? $_ENV['REST_API_KEY'] ?? '';
if ($secretKey === '') {
    http_response_code(500);
    echo json_encode(['errorCode' => 4000, 'errorMessage' => 'Network timeout: API_SIGN_KEY not configured']);
    exit;
}

$sign = md5($token . $uid . $secretKey);

$postmanBody = [
    'token' => $token,
    'uid' => $uid,
    'sign' => $sign,
];

echo json_encode([
    'success' => true,
    'token' => $token,
    'uid' => $uid,
    'sign' => $sign,
    'postman_body' => $postmanBody,
    'note' => 'Copy the postman_body object into Postman as your request body. Then POST to /invalidate_token_api.php'
]);
?>
