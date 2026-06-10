<?php
/**
 * Game List API
 *
 * Returns all available games with their details
 *
 * Response:
 * [
 *   {
 *     "gameId": "101",
 *     "name": "GreedyStar",
 *     "title": "摩天轮宇航版",
 *     "ver": 15,
 *     "half_url": "https://xxx.quantum-nexus.net/games/greedy_star_half/index.html?pl=pikilive&v=15",
 *     "full_url": "https://xxx.quantum-nexus.net/games/greedy_star/index.html?pl=pikilive&v=15",
 *     "hd_url": "https://xxx.quantum-nexus.net/games/greedy_star_medium/index.html?pl=pikilive&v=15"
 *   }
 * ]
 */

header('Content-Type: application/json');

// SSL bypass (development)
stream_context_set_default([
    'ssl' => [
        'verify_peer'      => false,
        'verify_peer_name' => false,
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

$sign   = trim((string)($data['sign']   ?? ''));

// ── Required field check ──────────────────────────────────────────────

if ($sign === '') {
    http_response_code(400);
    echo json_encode(['errorCode' => 4005, 'errorMessage' => 'Parameter error: sign is required']);
    exit;
}

// ── Signature verify ──────────────────────────────────────────────────
$secretKey = $_ENV['API_SIGN_KEY'] ?? $_ENV['WEBHOOK_KEY'] ?? $_ENV['REST_API_KEY'] ?? '';
if ($secretKey === '') {
    http_response_code(500);
    echo json_encode(['errorCode' => 4000, 'errorMessage' => 'Server error: secret key not configured']);
    exit;
}

$expectedSign = md5($secretKey);
if (strtolower($sign) !== strtolower($expectedSign)) {
    http_response_code(401);
    echo json_encode(['errorCode' => 10004, 'errorMessage' => 'Signature error']);
    exit;
}

/**
 * Back4App REST API GET request.
 * Master Key ব্যবহার করায় ACL বাধা নেই।
 */
function b4aQuery(string $url, string $appId, string $masterKey): ?array
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

// ── Back4App config ─────────────────────────────────────────────────────────
$B4A_APP_ID     = $parse_app_id;
$B4A_MASTER_KEY = $parse_master_key;
$B4A_BASE       = 'https://parseapi.back4app.com';

try {

    // try-1: uid = "10001"  (String)
    $result = b4aQuery($B4A_BASE . '/classes/Game', $B4A_APP_ID, $B4A_MASTER_KEY);
    $games = $result['results'] ?? [];
    
    $gamesList = [];
    
    foreach ($games as $game) {
        $gameData = [
            'gameId' => (string)$game['gameId'],
            'name' => (string)($game['name'] ?? ''),
            'title' => (string)($game['title'] ?? ''),
            'ver' => (int)($game['ver'] ?? 0),
        ];
        
        // Add optional URL fields if they exist
        if (isset($game['full_url'])) {
            $gameData['full_url'] = (string)$game['full_url'];
        }
        if (isset($game['hd_url'])) {
            $gameData['hd_url'] = (string)$game['hd_url'];
        }
        if (isset($game['half_url'])) {
            $gameData['half_url'] = (string)$game['half_url'];
        }
        
        $gamesList[] = $gameData;
    }
    
    http_response_code(200);
    echo json_encode($gamesList);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'errorCode' => 4000,
        'errorMessage' => 'Network timeout',
        'details' => $e->getMessage()
    ]);
}
