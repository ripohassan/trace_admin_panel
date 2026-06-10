<?php
/**
 * DEBUG: Back4App User Search Test
 * Browser থেকে open করুন: http://localhost/trace_admin_panel/debug_user_search.php?uid=YOUR_UID
 *
 * IMPORTANT: Test শেষে এই file delete করুন!
 */

// শুধু localhost থেকে access করতে দেওয়া হবে
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost'])) {
    http_response_code(403);
    die('Access denied. This debug file is only accessible from localhost.');
}

// Configs load করা
stream_context_set_default(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
require 'vendor/autoload.php';
include 'Configs.php';

$testUid = trim($_GET['uid'] ?? '');

// Configs.php থেকে আসা variables
$APP_ID     = $parse_app_id;
$MASTER_KEY = $parse_master_key;
$BASE_URL   = 'https://parseapi.back4app.com';

echo '<html><head><meta charset="UTF-8"><title>Debug User Search</title>';
echo '<style>
    body { font-family: monospace; background: #1a1a1a; color: #e0e0e0; padding: 20px; }
    h2 { color: #61dafb; }
    h3 { color: #ffd700; margin-top: 30px; }
    .ok   { color: #4caf50; }
    .err  { color: #f44336; }
    .warn { color: #ff9800; }
    .info { color: #90caf9; }
    pre { background: #2d2d2d; padding: 12px; border-radius: 6px; overflow-x: auto; border-left: 3px solid #61dafb; }
    .box { background: #2d2d2d; padding: 15px; border-radius: 8px; margin: 10px 0; }
    input { background: #333; color: #fff; border: 1px solid #555; padding: 8px 12px; border-radius: 4px; width: 300px; }
    button { background: #61dafb; color: #000; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-left: 8px; }
</style></head><body>';

echo '<h2>🔍 Back4App User Search Debug</h2>';

// UID input form
echo '<div class="box">';
echo '<form method="GET">';
echo 'Test UID দিন: <input type="text" name="uid" value="' . htmlspecialchars($testUid) . '" placeholder="e.g. 12345 or objectId">';
echo '<button type="submit">Search করুন</button>';
echo '</form>';
echo '</div>';

// ── Step 1: Config check ──────────────────────────────────────────────────
echo '<h3>⚙️ Step 1: Configuration Check</h3>';
echo '<div class="box">';
echo '<b>.env APP_ID:</b> <span class="info">' . htmlspecialchars($_ENV['PARSE_APP_ID'] ?? 'NOT SET') . '</span><br>';
echo '<b>Configs.php APP_ID:</b> <span class="info">' . htmlspecialchars($APP_ID) . '</span><br>';
echo '<b>Master Key:</b> <span class="info">' . substr($MASTER_KEY, 0, 6) . '...' . substr($MASTER_KEY, -4) . '</span><br>';

// App ID মিলছে কি না চেক
$envAppId = $_ENV['PARSE_APP_ID'] ?? '';
if ($envAppId && $envAppId !== $APP_ID) {
    echo '<br><span class="warn">⚠️ WARNING: .env PARSE_APP_ID এবং Configs.php এর app_id আলাদা!</span><br>';
    echo '<span class="warn">   .env:        ' . htmlspecialchars($envAppId) . '</span><br>';
    echo '<span class="warn">   Configs.php: ' . htmlspecialchars($APP_ID) . '</span><br>';
    echo '<span class="warn">   Configs.php hard-coded value ব্যবহার হচ্ছে।</span>';
}
echo '</div>';

// ── Step 2: Back4App connection test ──────────────────────────────────────
echo '<h3>🌐 Step 2: Back4App Connection Test</h3>';
echo '<div class="box">';

function doGet(string $url, string $appId, string $masterKey): array
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
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);
    return ['body' => $response, 'code' => $httpCode, 'error' => $error];
}

// Class list পাওয়ার চেষ্টা
$schemaResult = doGet($BASE_URL . '/1/schemas/_User', $APP_ID, $MASTER_KEY);
if ($schemaResult['code'] === 200) {
    $schema = json_decode($schemaResult['body'], true);
    echo '<span class="ok">✅ Back4App connection সফল!</span><br><br>';
    echo '<b>_User class এর সব Columns:</b><br>';
    echo '<pre>';
    $fields = $schema['fields'] ?? [];
    $fieldNames = array_keys($fields);
    sort($fieldNames);
    foreach ($fieldNames as $f) {
        $type = $fields[$f]['type'] ?? 'unknown';
        echo "  • <b>" . htmlspecialchars($f) . "</b> ($type)\n";
    }
    echo '</pre>';
    
    // uid column আছে কি না
    if (isset($fields['uid'])) {
        echo '<span class="ok">✅ "uid" column আছে! Type: ' . $fields['uid']['type'] . '</span><br>';
        $uidType = $fields['uid']['type'];
    } else {
        echo '<span class="err">❌ "_User" class-এ "uid" নামে কোনো column নেই!</span><br>';
        $uidType = null;
    }
} else {
    echo '<span class="err">❌ Connection failed! HTTP: ' . $schemaResult['code'] . '</span><br>';
    echo '<span class="err">Response: ' . htmlspecialchars($schemaResult['body']) . '</span><br>';
    if ($schemaResult['error']) {
        echo '<span class="err">cURL Error: ' . htmlspecialchars($schemaResult['error']) . '</span>';
    }
    echo '</div></body></html>';
    exit;
}
echo '</div>';

// ── Step 3: User count ──────────────────────────────────────────────────
echo '<h3>📊 Step 3: Total User Count</h3>';
echo '<div class="box">';
$countResult = doGet($BASE_URL . '/1/users?count=1&limit=0', $APP_ID, $MASTER_KEY);
$countData   = json_decode($countResult['body'], true);
echo '<b>Total Users in _User:</b> <span class="ok">' . ($countData['count'] ?? 'N/A') . '</span><br>';

// ২টা sample user দেখানো
$sampleResult = doGet($BASE_URL . '/1/users?limit=3', $APP_ID, $MASTER_KEY);
$sampleData   = json_decode($sampleResult['body'], true);
if (!empty($sampleData['results'])) {
    echo '<br><b>Sample Users (প্রথম ৩টা):</b>';
    echo '<pre>';
    foreach ($sampleData['results'] as $u) {
        $uid_val = $u['uid'] ?? 'N/A';
        $obj_id  = $u['objectId'] ?? 'N/A';
        $uname   = $u['username'] ?? 'N/A';
        echo "objectId: <b>$obj_id</b>  |  uid: <b>$uid_val</b>  |  username: <b>$uname</b>\n";
    }
    echo '</pre>';
}
echo '</div>';

// ── Step 4: Specific UID search ──────────────────────────────────────────
if ($testUid !== '') {
    echo '<h3>🔎 Step 4: "' . htmlspecialchars($testUid) . '" দিয়ে Search</h3>';
    echo '<div class="box">';

    // 4a. uid field (string)
    $where = urlencode(json_encode(['uid' => $testUid]));
    $r = doGet($BASE_URL . '/1/users?where=' . $where . '&limit=1', $APP_ID, $MASTER_KEY);
    $d = json_decode($r['body'], true);
    $count = count($d['results'] ?? []);
    echo '<b>uid = "' . htmlspecialchars($testUid) . '" (string):</b> ';
    echo $count > 0 ? '<span class="ok">✅ Found!</span>' : '<span class="err">❌ Not found (HTTP ' . $r['code'] . ')</span>';
    echo '<br>';

    // 4b. uid field (number)
    if (is_numeric($testUid)) {
        $where = urlencode(json_encode(['uid' => (int)$testUid]));
        $r2 = doGet($BASE_URL . '/1/users?where=' . $where . '&limit=1', $APP_ID, $MASTER_KEY);
        $d2 = json_decode($r2['body'], true);
        $count2 = count($d2['results'] ?? []);
        echo '<b>uid = ' . htmlspecialchars($testUid) . ' (number/integer):</b> ';
        echo $count2 > 0 ? '<span class="ok">✅ Found!</span>' : '<span class="err">❌ Not found</span>';
        echo '<br>';

        if ($count2 > 0) {
            echo '<pre>' . json_encode($d2['results'][0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
        }
    }

    if ($count > 0) {
        echo '<pre>' . json_encode($d['results'][0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
    }

    // 4c. objectId
    $r3 = doGet($BASE_URL . '/1/users/' . urlencode($testUid), $APP_ID, $MASTER_KEY);
    $d3 = json_decode($r3['body'], true);
    echo '<b>objectId = "' . htmlspecialchars($testUid) . '":</b> ';
    echo !empty($d3['objectId']) ? '<span class="ok">✅ Found!</span>' : '<span class="err">❌ Not found (HTTP ' . $r3['code'] . ')</span>';
    echo '<br>';
    if (!empty($d3['objectId'])) {
        echo '<pre>' . json_encode($d3, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
    }

    // 4d. username
    $where = urlencode(json_encode(['username' => $testUid]));
    $r4 = doGet($BASE_URL . '/1/users?where=' . $where . '&limit=1', $APP_ID, $MASTER_KEY);
    $d4 = json_decode($r4['body'], true);
    $count4 = count($d4['results'] ?? []);
    echo '<b>username = "' . htmlspecialchars($testUid) . '":</b> ';
    echo $count4 > 0 ? '<span class="ok">✅ Found!</span>' : '<span class="err">❌ Not found</span>';
    echo '<br>';

    echo '</div>';

    // ── Step 5: Raw query result ──────────────────────────────────────────
    echo '<h3>📦 Step 5: Raw API Response (uid string search)</h3>';
    echo '<div class="box">';
    $where = urlencode(json_encode(['uid' => $testUid]));
    $rawUrl = $BASE_URL . '/1/users?where=' . $where . '&limit=1';
    echo '<b>URL:</b> <span class="info">' . htmlspecialchars($rawUrl) . '</span><br><br>';
    $raw = doGet($rawUrl, $APP_ID, $MASTER_KEY);
    echo '<b>HTTP Code:</b> ' . $raw['code'] . '<br>';
    echo '<b>Response:</b>';
    echo '<pre>' . htmlspecialchars(json_encode(json_decode($raw['body']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
    echo '</div>';
}

echo '<div class="box" style="margin-top:30px; border-left: 3px solid #f44336;">';
echo '<span class="err">⚠️ SECURITY: Test শেষে এই file delete করুন: <b>debug_user_search.php</b></span>';
echo '</div>';

echo '</body></html>';
