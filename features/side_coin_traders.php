<?php

require '../vendor/autoload.php';
include '../Configs.php';

use Parse\ParseException;
use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseUser;

session_start();

$currUser = ParseUser::getCurrentUser();
if ($currUser) {
    $_SESSION['token'] = $currUser->getSessionToken();
} else {
    header("Refresh:0; url=../index.php");
}

// Handle Create Coin Trader
if (isset($_POST['action']) && $_POST['action'] === 'create_trader') {
    $errorMsg = "Manual create disabled. Please approve trader requests from Trader Requests page.";
}

// Handle Toggle Status
if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $traderId = $_POST['trader_id'] ?? '';
    $newStatus = $_POST['new_status'] === '1';
    if ($traderId) {
        try {
            $query = new ParseQuery("CoinTraders");
            $trader = $query->get($traderId, true);
            $trader->set("isActive", $newStatus);
            $trader->save(true);
        } catch (ParseException $e) {
            $errorMsg = $e->getMessage();
        }
    }
}

// Handle Delete Trader
if (isset($_POST['action']) && $_POST['action'] === 'delete_trader') {
    $traderId = $_POST['trader_id'] ?? '';
    if ($traderId) {
        try {
            $query = new ParseQuery("CoinTraders");
            $trader = $query->get($traderId, true);
            $trader->destroy(true);
        } catch (ParseException $e) {
            $errorMsg = $e->getMessage();
        }
    }
}

// Handle Trader Status Update
if (isset($_POST['action']) && $_POST['action'] === 'update_trader_status') {
    $traderId = $_POST['trader_id'] ?? '';
    $status = $_POST['status'] ?? 'approved';
    $allowed = ['approved', 'suspended'];

    if ($traderId && in_array($status, $allowed, true)) {
        try {
            $query = new ParseQuery("CoinTraders");
            $trader = $query->get($traderId, true);
            $trader->set("status", $status);
            $trader->set("isActive", $status === 'approved');
            $trader->save(true);
        } catch (ParseException $e) {
            $errorMsg = $e->getMessage();
        }
    }
}

?>

<style>
    .coin-icon { color: #f5a623; font-weight: bold; }
    .modal-overlay {
        display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;
        color: #000;
    }
    .modal-overlay.active { display: flex; }
    .modal-box {
        background: #fff; border-radius: 12px; padding: 30px; width: 500px; max-width: 95%;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3); position: relative;
    }
    .modal-box h3 { margin-bottom: 20px; font-size: 20px; }
    .modal-box .close-btn {
        position: absolute; top: 15px; right: 20px; font-size: 24px;
        cursor: pointer; color: #999; border: none; background: none;
    }
    .modal-box .form-group { margin-bottom: 15px; }
    .modal-box label { font-weight: 500; margin-bottom: 5px; display: block; color: #555; }
    .modal-box input, .modal-box select {
        width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px;
        font-size: 14px; outline: none; transition: border-color 0.2s;
        color: #333;
    }
    .modal-box input:focus, .modal-box select:focus { border-color: #6c5ce7; }
    .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
    .modal-actions .btn-cancel {
        padding: 8px 24px; border: 1px solid #ddd; border-radius: 8px;
        background: #fff; cursor: pointer; font-size: 14px;
    }
    .modal-actions .btn-create {
        padding: 8px 24px; border: none; border-radius: 8px;
        background: #6c5ce7; color: #fff; cursor: pointer; font-size: 14px;
    }
    .row-half { display: flex; gap: 10px; }
    .row-half .form-group { flex: 1; }
    .status-toggle { cursor: pointer; }
    .badge-active { background: #00b894; color: #fff; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
    .badge-inactive { background: #b2bec3; color: #fff; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
    .status-badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .action-icons a, .action-icons button {
        border: none; background: none; cursor: pointer; font-size: 16px;
        color: #636e72; margin: 0 3px; padding: 4px;
    }
    .action-icons a:hover, .action-icons button:hover { color: #6c5ce7; }
    .page-header-custom {
        display: flex; justify-content: space-between; align-items: center;
        padding: 20px 0; flex-wrap: wrap; gap: 10px;
    }
    .page-header-custom h2 { font-size: 22px; font-weight: 600; color: #fff; margin: 0; }
    .page-header-custom p { color: #636e72; margin: 2px 0 0 0; font-size: 14px; }
    .header-actions { display: flex; gap: 10px; align-items: center; }
    .btn-add-trader {
        background: #6c5ce7; color: #fff; border: none; padding: 10px 20px;
        border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500;
    }
    .btn-add-trader:hover { background: #5a4bd1; color: #fff; text-decoration: none; }
    .btn-filter {
        background: #fff; border: 1px solid #ddd; padding: 10px 20px;
        border-radius: 8px; cursor: pointer; font-size: 14px; color: #636e72;
    }
    .search-box-custom {
        border: 1px solid #ddd; border-radius: 8px; padding: 8px 14px;
        font-size: 14px; outline: none; width: 200px;
    }
    .user-cell { display: flex; align-items: center; gap: 10px; }
    .user-cell img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
    .user-cell .user-info { display: flex; flex-direction: column; }
    .user-cell .user-name { font-weight: 600; font-size: 14px; }
    .user-cell .user-handle { color: #636e72; font-size: 12px; }
    .copy-btn {
        border: none; background: none; cursor: pointer; color: #b2bec3;
        font-size: 12px; padding: 2px;
    }
    .copy-btn:hover { color: #6c5ce7; }
</style>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Coin System</a></li>
                <li class="breadcrumb-item active">Coin Traders</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg">
                <div class="card" style="border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
                    <div class="card-body">

                        <div class="page-header-custom">
                            <div>
                                <h2>Coin Traders</h2>
                                <p>Traders are added by approving Trader Requests from mobile app users</p>
                            </div>
                            <div class="header-actions">
                                <input type="text" class="search-box-custom" id="searchTraders" placeholder="Search Coin Traders">
                            </div>
                        </div>

                        <?php if (isset($errorMsg)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMsg); ?></div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table id="coinTradersTable" class="display nowrap table table-hover" cellspacing="0" width="100%">
                                <thead>
                                <tr style="text-transform: uppercase; font-size: 11px; letter-spacing: 1px; color: #636e72;">
                                    <th>User</th>
                                    <th>Unique ID</th>
                                    <th>Coin Balance</th>
                                    <th>Spent Coins</th>
                                    <th>Mobile</th>
                                    <th>Created Date</th>
                                    <th>Status</th>
                                    <th>Active</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                try {
                                    $query = new ParseQuery("CoinTraders");
                                    $query->descending('createdAt');
                                    $query->includeKey("user");
                                    $query->limit(1500);
                                    $traders = $query->find(true);

                                    foreach ($traders as $trader) {
                                        $traderId = $trader->getObjectId();
                                        $user = $trader->get("user");
                                        $userName = $user ? htmlspecialchars($user->get("name") ?? 'Unknown') : 'Unknown';
                                        $userHandle = $user ? htmlspecialchars($user->get("username") ?? '') : '';
                                        $userId = $user ? $user->getObjectId() : '';

                                        $avatar = '';
                                        if ($user && $user->get("avatar")) {
                                            $avatarFile = $user->get("avatar");
                                            // Check if avatar is a ParseFile object or a string URL
                                            if (is_object($avatarFile) && method_exists($avatarFile, 'getURL')) {
                                                $avatar = $avatarFile->getURL();
                                            } else {
                                                $avatar = (string)$avatarFile;
                                            }
                                        }
                                        $avatarHtml = $avatar ? '<img src="'.htmlspecialchars($avatar).'" alt="" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">' : '<div style="width: 40px; height: 40px; border-radius: 50%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-size: 20px;"><i class="fa fa-user"></i></div>';

                                        // Badge icon (random color for demo)
                                        $badgeColors = ['💙', '🩷', '💜', '💚'];
                                        $badge = $badgeColors[array_rand($badgeColors)];

                                        $uniqueId = $user ? htmlspecialchars($user->get("uniqueId") ?? $userId) : $traderId;
                                        $coinBalance = number_format($trader->get("coinBalance") ?? 0);
                                        $spentCoins = number_format($trader->get("spentCoins") ?? 0);
                                        $countryCode = htmlspecialchars($trader->get("countryCode") ?? '');
                                        $mobileNumber = htmlspecialchars($trader->get("mobileNumber") ?? '');
                                        $mobile = $countryCode ? $countryCode . ' ' . $mobileNumber : $mobileNumber;

                                        $createdAt = $trader->getCreatedAt();
                                        $createdDate = $createdAt ? $createdAt->format("M d, Y, h:i A") : '';

                                        $traderStatus = $trader->get("status") ?? 'approved';
                                        if ($traderStatus !== 'approved' && $traderStatus !== 'suspended') {
                                            $traderStatus = ($trader->get("isActive") ?? false) ? 'approved' : 'suspended';
                                        }
                                        $isActive = $trader->get("isActive") ?? false;
                                        $toggleValue = $isActive ? '0' : '1';

                                        echo '
                                        <tr>
                                            <td>
                                                <div class="user-cell">
                                                    '.$avatarHtml.'
                                                    <div class="user-info">
                                                        <span class="user-name">'.$userName.' '.$badge.'</span>
                                                        <span class="user-handle">@'.$userHandle.'</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>'.$uniqueId.' <button class="copy-btn" onclick="copyText(\''.$uniqueId.'\')"><i class="fa fa-copy"></i></button></td>
                                            <td><span class="coin-icon">🪙</span> '.$coinBalance.'</td>
                                            <td><span class="coin-icon">🪙</span> '.$spentCoins.'</td>
                                            <td>'.$mobile.'</td>
                                            <td>'.$createdDate.'</td>
                                            <td style="text-align: center;">
                                                <form method="post" style="margin:0;">
                                                    <input type="hidden" name="action" value="update_trader_status">
                                                    <input type="hidden" name="trader_id" value="'.$traderId.'">
                                                    <select name="status" onchange="this.form.submit()" style="padding:4px 8px; border:1px solid #ddd; border-radius:6px; font-size:12px; color: #333;">
                                                        <option value="approved" '.($traderStatus === 'approved' ? 'selected' : '').'>Approved</option>
                                                        <option value="suspended" '.($traderStatus === 'suspended' ? 'selected' : '').'>Suspended</option>
                                                    </select>
                                                </form>
                                            </td>
                                            <td style="text-align: center;">
                                                <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                                                    
                                                    <form method="post" style="display:inline; margin: 0;">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="trader_id" value="'.$traderId.'">
                                                        <input type="hidden" name="new_status" value="'.$toggleValue.'">
                                                        <label class="switch" style="margin: 0;">
                                                            <input type="checkbox" '.($isActive ? 'checked' : '').' onchange="this.form.submit()">
                                                            <span class="slider round"></span>
                                                        </label>
                                                    </form>
                                                </div>
                                            </td>
                                            <td class="action-icons">
                                                <a href="../dashboard/edit_coin_trader.php?objectId='.$traderId.'" title="Edit"><i class="fa fa-pencil"></i></a>
                                                <a href="../dashboard/coin_requests.php?trader_id='.$traderId.'" title="View Requests"><i class="fa fa-list"></i></a>
                                                <a href="../dashboard/coin_trader_history.php?trader_id='.$traderId.'" title="History"><i class="fa fa-history"></i></a>
                                                <button title="Send Notification" onclick="alert(\'Notification sent!\')"><i class="fa fa-bell"></i></button>
                                            </td>
                                        </tr>';
                                    }
                                } catch (ParseException $e) {
                                    echo '<tr><td colspan="9" class="text-center text-danger">' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toggle Switch CSS -->
<style>
    .switch { position: relative; display: inline-block; width: 44px; height: 0; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 12px; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .3s; }
    .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: -3px; background-color: white; transition: .3s; }
    input:checked + .slider { background-color: #6c5ce7; }
    input:checked + .slider:before { transform: translateX(20px); }
    .slider.round { border-radius: 24px; }
    .slider.round:before { border-radius: 50%; }
</style>

<script>
function copyText(text) {
    navigator.clipboard.writeText(text);
}

// Search functionality
document.getElementById('searchTraders').addEventListener('keyup', function() {
    var value = this.value.toLowerCase();
    var rows = document.querySelectorAll('#coinTradersTable tbody tr');
    rows.forEach(function(row) {
        row.style.display = row.textContent.toLowerCase().includes(value) ? '' : 'none';
    });
});
</script>
