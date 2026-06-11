<?php

require '../vendor/autoload.php';
include '../Configs.php';

use Parse\ParseException;
use Parse\ParseQuery;
use Parse\ParseUser;

session_start();

$currUser = ParseUser::getCurrentUser();
if (!$currUser) {
    header("Refresh:0; url=../index.php");
    exit;
}

// ── Back4App REST helper (Master Key — no ACL limit) ─────────────────────
function b4aGet(string $url, string $appId, string $masterKey): ?array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
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

$B4A_APP_ID     = $parse_app_id;
$B4A_MASTER_KEY = $parse_master_key;
$B4A_BASE       = 'https://parseapi.back4app.com';

// ── Filters from GET ─────────────────────────────────────────────────────
$filterUid    = trim($_GET['uid']    ?? '');
$filterGameId = trim($_GET['gameId'] ?? '');
$filterType   = trim($_GET['type']   ?? '');  // 1=consume, 2=obtain

// ── Build query URL ──────────────────────────────────────────────────────
$where = [];
if ($filterUid    !== '') $where['uid']    = $filterUid;
if ($filterGameId !== '') $where['gameId'] = $filterGameId;
if ($filterType   !== '') $where['type']   = (int)$filterType;

$whereParam = empty($where) ? '' : '&where=' . urlencode(json_encode($where));

// Total count
$countResult = b4aGet(
    $B4A_BASE . '/1/classes/GameTransaction?count=1&limit=0' . $whereParam,
    $B4A_APP_ID, $B4A_MASTER_KEY
);
$totalCount = (int)($countResult['count'] ?? 0);

// Fetch up to 1000 records (descending by createdAt)
$dataResult = b4aGet(
    $B4A_BASE . '/1/classes/GameTransaction?limit=1000&order=-createdAt' . $whereParam,
    $B4A_APP_ID, $B4A_MASTER_KEY
);
$transactions = $dataResult['results'] ?? [];

// ── Stats ─────────────────────────────────────────────────────────────────
$totalConsumed = 0;
$totalObtained = 0;
foreach ($transactions as $tx) {
    if (($tx['type'] ?? 0) == 1) $totalConsumed += (int)($tx['coin'] ?? 0);
    if (($tx['type'] ?? 0) == 2) $totalObtained += (int)($tx['coin'] ?? 0);
}

// reward type labels
$rewardLabels = [
    2 => 'Normal',
    3 => 'Piggy Bank',
    4 => 'Daily Leaderboard',
    5 => 'Weekly Leaderboard',
    6 => 'Novice Task',
];
?>

<style>
    .badge-consume  { background:#e74c3c; color:#fff; padding:3px 8px; border-radius:12px; font-size:11px; font-weight:600; }
    .badge-obtain   { background:#27ae60; color:#fff; padding:3px 8px; border-radius:12px; font-size:11px; font-weight:600; }
    .badge-reward   { background:#8e44ad; color:#fff; padding:3px 8px; border-radius:12px; font-size:11px; font-weight:600; }
    .stat-card      { border-radius:10px; padding:18px 22px; color:#fff; margin-bottom:10px; }
    .stat-card h3   { margin:0; font-size:28px; font-weight:700; }
    .stat-card p    { margin:4px 0 0; font-size:13px; opacity:.85; }
    .coin-pos       { color:#27ae60; font-weight:600; }
    .coin-neg       { color:#e74c3c; font-weight:600; }
    .filter-bar     { background:#f8f9fa; border-radius:8px; padding:15px 18px; margin-bottom:18px; }
</style>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Games</a></li>
                <li class="breadcrumb-item active">Game Coin Transactions</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">

        <!-- Stats Row -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="stat-card" style="background:linear-gradient(135deg,#65131f,#a93226);">
                    <h3><?= number_format($totalCount) ?></h3>
                    <p><i class="fa fa-list"></i> Total Transactions</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background:linear-gradient(135deg,#e74c3c,#c0392b);">
                    <h3><?= number_format($totalConsumed) ?></h3>
                    <p><i class="fa fa-minus-circle"></i> Total Consumed</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background:linear-gradient(135deg,#27ae60,#1e8449);">
                    <h3><?= number_format($totalObtained) ?></h3>
                    <p><i class="fa fa-plus-circle"></i> Total Obtained</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background:linear-gradient(135deg,#2980b9,#1a5276);">
                    <h3><?= number_format($totalObtained - $totalConsumed) ?></h3>
                    <p><i class="fa fa-balance-scale"></i> Net Change</p>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <form method="GET" action="" class="form-inline" id="filter-form">
                <div class="form-group mr-3 mb-2">
                    <label class="mr-2"><i class="fa fa-user"></i> UID:</label>
                    <input type="text" name="uid" class="form-control form-control-sm"
                           placeholder="Filter by UID..." value="<?= htmlspecialchars($filterUid) ?>" style="width:160px;">
                </div>
                <div class="form-group mr-3 mb-2">
                    <label class="mr-2"><i class="fa fa-gamepad"></i> Game ID:</label>
                    <input type="text" name="gameId" class="form-control form-control-sm"
                           placeholder="Game ID..." value="<?= htmlspecialchars($filterGameId) ?>" style="width:130px;">
                </div>
                <div class="form-group mr-3 mb-2">
                    <label class="mr-2"><i class="fa fa-filter"></i> Type:</label>
                    <select name="type" class="form-control form-control-sm" style="width:140px;">
                        <option value="">All Types</option>
                        <option value="1" <?= $filterType==='1'?'selected':'' ?>>🔴 Consume</option>
                        <option value="2" <?= $filterType==='2'?'selected':'' ?>>🟢 Obtain</option>
                    </select>
                </div>
                <div class="mb-2">
                    <button type="submit" class="btn btn-sm btn-primary mr-2">
                        <i class="fa fa-search"></i> Filter
                    </button>
                    <a href="game_transactions.php" class="btn btn-sm btn-secondary">
                        <i class="fa fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">

                        <?php if (empty($transactions)): ?>
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i>
                                No game coin transactions found<?= ($filterUid || $filterGameId || $filterType) ? ' for the selected filters' : '' ?>.
                                <?php if (!$filterUid && !$filterGameId && !$filterType): ?>
                                    Transactions are recorded when <code>update_game_coin_api.php</code> is called.
                                <?php endif; ?>
                            </div>
                        <?php else: ?>

                        <div class="table-responsive">
                            <table id="gameTxTable"
                                   class="display nowrap table table-hover table-striped table-bordered"
                                   cellspacing="0" width="100%">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="color:#65131f;">#</th>
                                        <th style="color:#65131f;">Order ID</th>
                                        <th style="color:#65131f;">Game ID</th>
                                        <th style="color:#65131f;">Round ID</th>
                                        <th style="color:#65131f;">UID</th>
                                        <th style="color:#65131f;">Type</th>
                                        <th style="color:#65131f;">Coin Change</th>
                                        <th style="color:#65131f;">Before</th>
                                        <th style="color:#65131f;">After</th>
                                        <th style="color:#65131f;">Reward Type</th>
                                        <th style="color:#65131f;">Win ID</th>
                                        <th style="color:#65131f;">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $i => $tx):
                                        $type       = (int)($tx['type']       ?? 0);
                                        $rewardType = (int)($tx['rewardType'] ?? 0);
                                        $coin       = (int)($tx['coin']       ?? 0);
                                        $oldCoin    = (int)($tx['oldCoin']    ?? 0);
                                        $newCoin    = (int)($tx['newCoin']    ?? 0);
                                        $createdAt  = $tx['createdAt'] ?? '';
                                        $dateStr    = $createdAt ? date('d/m/Y H:i', strtotime($createdAt)) : '—';
                                        $typeBadge  = $type === 1
                                            ? '<span class="badge-consume">🔴 Consume</span>'
                                            : '<span class="badge-obtain">🟢 Obtain</span>';
                                        $coinDisplay = $type === 1
                                            ? '<span class="coin-neg">-' . number_format($coin) . '</span>'
                                            : '<span class="coin-pos">+' . number_format($coin) . '</span>';
                                        $rewardLabel = $rewardLabels[$rewardType] ?? 'Type ' . $rewardType;
                                    ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><small><?= htmlspecialchars($tx['orderId'] ?? '—') ?></small></td>
                                        <td><?= htmlspecialchars($tx['gameId'] ?? '—') ?></td>
                                        <td><small><?= htmlspecialchars($tx['roundId'] ?? '—') ?></small></td>
                                        <td><b><?= htmlspecialchars($tx['uid'] ?? '—') ?></b></td>
                                        <td><?= $typeBadge ?></td>
                                        <td><?= $coinDisplay ?></td>
                                        <td><?= number_format($oldCoin) ?></td>
                                        <td><b><?= number_format($newCoin) ?></b></td>
                                        <td><span class="badge-reward"><?= htmlspecialchars($rewardLabel) ?></span></td>
                                        <td><?= htmlspecialchars($tx['winId'] ?? '—') ?></td>
                                        <td><?= $dateStr ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>

    </div><!-- container-fluid -->
</div><!-- page-wrapper -->

<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#gameTxTable')) {
        $('#gameTxTable').DataTable().destroy();
    }
    $('#gameTxTable').DataTable({
        "order": [[ 11, "desc" ]],
        "pageLength": 25,
        "dom": 'Bfrtip',
        "buttons": [
            { extend: 'excel', text: '<i class="fa fa-file-excel-o"></i> Excel', className: 'btn btn-success btn-sm' },
            { extend: 'pdf',   text: '<i class="fa fa-file-pdf-o"></i> PDF',   className: 'btn btn-danger btn-sm' },
            { extend: 'print', text: '<i class="fa fa-print"></i> Print',       className: 'btn btn-default btn-sm' }
        ],
        "language": {
            "search": "Search transactions:",
            "lengthMenu": "Show _MENU_ rows",
            "info": "Showing _START_ to _END_ of _TOTAL_ transactions",
            "zeroRecords": "No transactions found"
        },
        "columnDefs": [
            { "orderable": false, "targets": [0] }
        ]
    });
});
</script>
