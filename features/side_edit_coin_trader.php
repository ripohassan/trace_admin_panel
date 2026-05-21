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

$traderId = $_GET['objectId'] ?? '';
$trader = null;
$traderUser = null;

if ($traderId) {
    try {
        $query = new ParseQuery("CoinTraders");
        $query->includeKey("user");
        $trader = $query->get($traderId, true);
        $traderUser = $trader->get("user");
    } catch (ParseException $e) {
        $errorMsg = $e->getMessage();
    }
}

// Handle Update
if (isset($_POST['action']) && $_POST['action'] === 'update_trader' && $trader) {
    $coinBalance = (int)($_POST['coin_balance'] ?? 0);
    $countryCode = $_POST['country_code'] ?? '';
    $mobileNumber = $_POST['mobile_number'] ?? '';
    $isActive = isset($_POST['is_active']);

    try {
        $trader->set("coinBalance", $coinBalance);
        $trader->set("countryCode", $countryCode);
        $trader->set("mobileNumber", $mobileNumber);
        $trader->set("isActive", $isActive);
        $trader->save(true);

        $successMsg = "Coin Trader updated successfully!";
        // Refresh data
        $query = new ParseQuery("CoinTraders");
        $query->includeKey("user");
        $trader = $query->get($traderId, true);
        $traderUser = $trader->get("user");
    } catch (ParseException $e) {
        $errorMsg = $e->getMessage();
    }
}

// Handle Add Coins
if (isset($_POST['action']) && $_POST['action'] === 'add_coins' && $trader) {
    $addAmount = (int)($_POST['add_amount'] ?? 0);
    if ($addAmount > 0) {
        try {
            $currentBalance = $trader->get("coinBalance") ?? 0;
            $newBalance = $currentBalance + $addAmount;
            $trader->set("coinBalance", $newBalance);
            $trader->save(true);

            $successMsg = number_format($addAmount) . " coins added! New balance: " . number_format($newBalance) . ".";
            // Refresh
            $query = new ParseQuery("CoinTraders");
            $query->includeKey("user");
            $trader = $query->get($traderId, true);
            $traderUser = $trader->get("user");
        } catch (ParseException $e) {
            $errorMsg = $e->getMessage();
        }
    }
}

?>

<style>
    .edit-header { padding: 20px 0; }
    .edit-header h2 { font-size: 22px; font-weight: 600; color: #2d3436; }
    .edit-header p { color: #636e72; font-size: 14px; }
    .trader-profile {
        display: flex; align-items: center; gap: 16px; padding: 20px;
        background: #f8f9fa; border-radius: 12px; margin-bottom: 24px;
    }
    .trader-profile img { width: 64px; height: 64px; border-radius: 50%; object-fit: cover; }
    .trader-profile .info h4 { margin: 0; font-size: 18px; }
    .trader-profile .info p { margin: 2px 0 0 0; color: #636e72; font-size: 14px; }
    .stat-cards { display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .stat-card {
        flex: 1; min-width: 150px; padding: 20px; border-radius: 12px; text-align: center;
        background: #fff; border: 1px solid #eee;
    }
    .stat-card .number { font-size: 24px; font-weight: 700; color: #2d3436; }
    .stat-card .label { font-size: 12px; color: #636e72; text-transform: uppercase; letter-spacing: 1px; }
    .stat-card.balance { border-left: 4px solid #6c5ce7; }
    .stat-card.spent { border-left: 4px solid #e74c3c; }
    .stat-card.requests { border-left: 4px solid #00b894; }
    .form-section { margin-top: 20px; }
    .form-section h5 { margin-bottom: 16px; font-weight: 600; }
    .btn-save {
        background: #6c5ce7; color: #fff; border: none; padding: 10px 24px;
        border-radius: 8px; cursor: pointer; font-size: 14px;
    }
    .btn-save:hover { background: #5a4bd1; }
    .btn-add-coins {
        background: #00b894; color: #fff; border: none; padding: 10px 24px;
        border-radius: 8px; cursor: pointer; font-size: 14px;
    }
    .btn-add-coins:hover { background: #00a381; }
    .quick-add { display: flex; gap: 10px; align-items: end; flex-wrap: wrap; }
    .quick-add input { max-width: 200px; }
</style>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Coin System</a></li>
                <li class="breadcrumb-item"><a href="../dashboard/coin_traders.php">Coin Traders</a></li>
                <li class="breadcrumb-item active">Edit Trader</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <?php if (!$trader): ?>
        <div class="alert alert-danger">Trader not found. <a href="../dashboard/coin_traders.php">Go back</a></div>
        <?php else: ?>

        <?php if (isset($successMsg)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($successMsg); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php endif; ?>
        <?php if (isset($errorMsg)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>

        <?php
        $userName = $traderUser ? htmlspecialchars($traderUser->get("name") ?? 'Unknown') : 'Unknown';
        $userHandle = $traderUser ? htmlspecialchars($traderUser->get("username") ?? '') : '';
        $avatarUrl = '';
        if ($traderUser && $traderUser->get("avatar")) {
            $avatarUrl = $traderUser->get("avatar")->getURL();
        }
        $coinBalance = $trader->get("coinBalance") ?? 0;
        $spentCoins = $trader->get("spentCoins") ?? 0;
        $countryCode = htmlspecialchars($trader->get("countryCode") ?? '');
        $mobileNumber = htmlspecialchars($trader->get("mobileNumber") ?? '');
        $isActive = $trader->get("isActive") ?? false;

        // Count requests for this trader
        $requestCount = 0;
        try {
            $rQuery = new ParseQuery("CoinRequests");
            $traderQuery = new ParseQuery("CoinTraders");
            $traderObj = $traderQuery->get($traderId, true);
            $rQuery->equalTo("trader", $traderObj);
            $requestCount = $rQuery->count(true);
        } catch (ParseException $e) { /* ignore */ }
        ?>

        <div class="trader-profile">
            <?php if ($avatarUrl): ?>
            <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="">
            <?php else: ?>
            <img src="../assets/dashboard/images/default-avatar.png" alt="">
            <?php endif; ?>
            <div class="info">
                <h4><?php echo $userName; ?></h4>
                <p>@<?php echo $userHandle; ?> &bull; Trader ID: <?php echo htmlspecialchars($traderId); ?></p>
            </div>
        </div>

        <div class="stat-cards">
            <div class="stat-card balance">
                <div class="number">🪙 <?php echo number_format($coinBalance); ?></div>
                <div class="label">Coin Balance</div>
            </div>
            <div class="stat-card spent">
                <div class="number">🪙 <?php echo number_format($spentCoins); ?></div>
                <div class="label">Spent Coins</div>
            </div>
            <div class="stat-card requests">
                <div class="number"><?php echo number_format($requestCount); ?></div>
                <div class="label">Total Requests</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card" style="border-radius: 12px;">
                    <div class="card-body">
                        <h5>Quick Add Coins</h5>
                        <form method="post" class="quick-add">
                            <input type="hidden" name="action" value="add_coins">
                            <div class="form-group" style="margin-bottom:0;">
                                <input type="number" class="form-control" name="add_amount" placeholder="Amount to add" min="1" required>
                            </div>
                            <button type="submit" class="btn-add-coins">+ Add Coins</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card" style="border-radius: 12px;">
                    <div class="card-body">
                        <h5>Quick Links</h5>
                        <a href="../dashboard/coin_requests.php?trader_id=<?php echo urlencode($traderId); ?>" class="btn btn-outline-primary btn-sm" style="margin-right: 8px;">
                            <i class="fa fa-list"></i> View Requests
                        </a>
                        <a href="../dashboard/coin_traders.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> Back to Traders
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-md-8" style="margin: 0 auto;">
                <div class="card" style="border-radius: 12px;">
                    <div class="card-body">
                        <h5>Edit Trader Details</h5>
                        <form method="post">
                            <input type="hidden" name="action" value="update_trader">

                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">User</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" value="<?php echo $userName; ?> (@<?php echo $userHandle; ?>)" disabled>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">Coin Balance</label>
                                <div class="col-sm-8">
                                    <input type="number" class="form-control" name="coin_balance" value="<?php echo $coinBalance; ?>" min="0" required>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">Country Code</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" name="country_code" value="<?php echo $countryCode; ?>">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">Mobile Number</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" name="mobile_number" value="<?php echo $mobileNumber; ?>">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">Active Status</label>
                                <div class="col-sm-8">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="is_active" id="isActive" <?php echo $isActive ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="isActive">Trader is active</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-sm-8 offset-sm-4">
                                    <button type="submit" class="btn-save">Save Changes</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>
</div>
