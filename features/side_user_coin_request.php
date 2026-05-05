<?php

require '../vendor/autoload.php';
include '../Configs.php';

use Parse\ParseException;
use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseUser;

session_start();

$currUser = ParseUser::getCurrentUser();
if (!$currUser) {
    header("Refresh:0; url=../index.php");
    exit;
}

$_SESSION['token'] = $currUser->getSessionToken();

// Handle Create Coin Request
if (isset($_POST['action']) && $_POST['action'] === 'create_request') {
    $traderId = $_POST['trader_id'] ?? '';
    $planId = $_POST['plan_id'] ?? '';
    
    if ($traderId && $planId) {
        try {
            // Get trader
            $traderQuery = new ParseQuery("CoinTraders");
            $trader = $traderQuery->get($traderId, true);
            
            // Get plan
            $planQuery = new ParseQuery("CoinPlans");
            $plan = $planQuery->get($planId, true);
            
            if ($trader && $plan) {
                $request = ParseObject::create("CoinRequests");
                $request->set("user", $currUser);
                $request->set("trader", $trader);
                $request->set("plan", $plan);
                $request->set("coinAmount", $plan->get("coins"));
                $request->set("amount", $plan->get("amount"));
                $request->set("status", "pending");
                $request->set("requestedAt", new DateTime());
                $request->save(true);
                
                $successMsg = "Coin request created successfully! Waiting for trader approval.";
            } else {
                $errorMsg = "Invalid trader or plan selected.";
            }
        } catch (ParseException $e) {
            $errorMsg = $e->getMessage();
        }
    } else {
        $errorMsg = "Please select both trader and coin plan.";
    }
}

// Fetch active traders
$traders = [];
try {
    $traderQuery = new ParseQuery("CoinTraders");
    $traderQuery->includeKey("user");
    $traderQuery->equalTo("isActive", true);
    $traderQuery->descending('createdAt');
    $traderQuery->limit(1500);
    $traders = $traderQuery->find(true);
} catch (ParseException $e) {
    $errorMsg = "Error fetching traders: " . $e->getMessage();
}

// Fetch active coin plans
$plans = [];
try {
    $planQuery = new ParseQuery("CoinPlans");
    $planQuery->equalTo("isActive", true);
    $planQuery->ascending('coins');
    $planQuery->limit(1500);
    $plans = $planQuery->find(true);
} catch (ParseException $e) {
    $errorMsg = "Error fetching plans: " . $e->getMessage();
}

// Fetch user's coin requests
$userRequests = [];
try {
    $requestQuery = new ParseQuery("CoinRequests");
    $requestQuery->equalTo("user", $currUser);
    $requestQuery->includeKey("trader");
    $requestQuery->includeKey("trader.user");
    $requestQuery->includeKey("plan");
    $requestQuery->descending('createdAt');
    $requestQuery->limit(1500);
    $userRequests = $requestQuery->find(true);
} catch (ParseException $e) {
    // ignore
}

?>

<style>
    .coin-request-container {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }
    
    .request-section {
        background: #fff;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    }
    
    .section-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .section-title i {
        color: #6c5ce7;
        font-size: 20px;
    }
    
    .traders-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .trader-card {
        border: 2px solid #eee;
        border-radius: 10px;
        padding: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .trader-card:hover {
        border-color: #6c5ce7;
        box-shadow: 0 4px 12px rgba(108, 92, 231, 0.15);
    }
    
    .trader-card.selected {
        border-color: #6c5ce7;
        background: #f0f1ff;
    }
    
    .trader-card input[type="radio"] {
        position: absolute;
        top: 10px;
        right: 10px;
        cursor: pointer;
    }
    
    .trader-info {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    
    .trader-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        background: #f0f0f0;
    }
    
    .trader-details h3 {
        font-size: 14px;
        font-weight: 600;
        margin: 0;
        color: #2c3e50;
    }
    
    .trader-details p {
        font-size: 12px;
        color: #636e72;
        margin: 3px 0 0 0;
    }
    
    .trader-balance {
        background: #f8f9fa;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12px;
        color: #636e72;
    }
    
    .trader-balance .coin-icon {
        color: #f5a623;
        font-weight: bold;
        margin-right: 4px;
    }
    
    .plans-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    
    .plan-card {
        border: 2px solid #eee;
        border-radius: 10px;
        padding: 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        background: #fff;
    }
    
    .plan-card:hover {
        border-color: #6c5ce7;
        box-shadow: 0 4px 12px rgba(108, 92, 231, 0.15);
    }
    
    .plan-card.selected {
        border-color: #6c5ce7;
        background: #f0f1ff;
    }
    
    .plan-card input[type="radio"] {
        position: absolute;
        top: 10px;
        right: 10px;
        cursor: pointer;
    }
    
    .plan-coins {
        font-size: 20px;
        font-weight: 700;
        color: #f5a623;
        margin-bottom: 5px;
    }
    
    .plan-coins .coin-icon {
        font-size: 18px;
    }
    
    .plan-label {
        font-size: 12px;
        color: #636e72;
        margin-bottom: 8px;
    }
    
    .plan-price {
        font-size: 16px;
        font-weight: 600;
        color: #2c3e50;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 10px;
        color: #2c3e50;
        font-size: 14px;
    }
    
    .btn-request {
        background: #6c5ce7;
        color: #fff;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-request:hover {
        background: #5a4bd1;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(108, 92, 231, 0.3);
    }
    
    .btn-request:disabled {
        background: #b2bec3;
        cursor: not-allowed;
        transform: none;
    }
    
    .request-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .request-table thead {
        background: #f8f9fa;
        border-bottom: 2px solid #eee;
    }
    
    .request-table th {
        padding: 12px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #636e72;
    }
    
    .request-table td {
        padding: 12px;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }
    
    .trader-name {
        font-weight: 600;
        color: #2c3e50;
    }
    
    .trader-handle {
        font-size: 12px;
        color: #636e72;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-pending {
        background: #ffeaa7;
        color: #d68910;
    }
    
    .status-approved {
        background: #d5f5e3;
        color: #27ae60;
    }
    
    .status-rejected {
        background: #fadbd8;
        color: #e74c3c;
    }
    
    .coin-amount {
        color: #f5a623;
        font-weight: bold;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #b2bec3;
    }
    
    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.5;
    }
    
    .empty-state p {
        font-size: 14px;
        margin: 0;
    }
    
    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .alert-success {
        background: #d5f5e3;
        color: #27ae60;
        border: 1px solid #a9dfbf;
    }
    
    .alert-danger {
        background: #fadbd8;
        color: #e74c3c;
        border: 1px solid #f5b7b1;
    }
    
    .alert-close {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 18px;
        opacity: 0.7;
    }
    
    .alert-close:hover {
        opacity: 1;
    }
    
    .form-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }
    
    .modal-overlay.active {
        display: flex;
    }
    
    .modal-box {
        background: #fff;
        border-radius: 12px;
        padding: 30px;
        width: 500px;
        max-width: 95%;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        position: relative;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .modal-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }
    
    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #999;
    }
</style>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Coin System</a></li>
                <li class="breadcrumb-item active">Request Coins</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div class="coin-request-container">
            
            <!-- Request Form Section -->
            <div class="request-section">
                <h2 class="section-title">
                    <i class="fa fa-plus-circle"></i>
                    Request Coins from Traders
                </h2>
                
                <?php if (isset($successMsg)): ?>
                <div class="alert alert-success">
                    <span><?php echo htmlspecialchars($successMsg); ?></span>
                    <button class="alert-close" onclick="this.parentElement.style.display='none';">&times;</button>
                </div>
                <?php endif; ?>
                
                <?php if (isset($errorMsg)): ?>
                <div class="alert alert-danger">
                    <span><?php echo htmlspecialchars($errorMsg); ?></span>
                    <button class="alert-close" onclick="this.parentElement.style.display='none';">&times;</button>
                </div>
                <?php endif; ?>
                
                <form method="post" id="coinRequestForm">
                    <input type="hidden" name="action" value="create_request">
                    
                    <!-- Select Trader -->
                    <div class="form-section">
                        <label style="font-weight: 600; margin-bottom: 15px; display: block; color: #2c3e50;">
                            <i class="fa fa-user"></i> Select a Trader
                        </label>
                        
                        <?php if (empty($traders)): ?>
                        <div class="empty-state">
                            <p>No active traders available at the moment.</p>
                        </div>
                        <?php else: ?>
                        <div class="traders-grid">
                            <?php foreach ($traders as $trader): 
                                $traderId = $trader->getObjectId();
                                $traderUser = $trader->get("user");
                                $traderName = $traderUser ? htmlspecialchars($traderUser->get("name") ?? 'Trader') : 'Unknown Trader';
                                $traderHandle = $traderUser ? htmlspecialchars($traderUser->get("username") ?? '') : '';
                                $traderBalance = $trader->get("coinBalance") ?? 0;
                                
                                $avatar = '';
                                if ($traderUser && $traderUser->get("avatar")) {
                                    $avatarFile = $traderUser->get("avatar");
                                    if (is_object($avatarFile) && method_exists($avatarFile, 'getURL')) {
                                        $avatar = $avatarFile->getURL();
                                    } else {
                                        $avatar = (string)$avatarFile;
                                    }
                                }
                                
                                $avatarHtml = $avatar 
                                    ? '<img src="'.htmlspecialchars($avatar).'" alt="" class="trader-avatar">'
                                    : '<div class="trader-avatar" style="display: flex; align-items: center; justify-content: center; font-size: 24px; background: #f0f0f0;"><i class="fa fa-user"></i></div>';
                            ?>
                            <label class="trader-card">
                                <input type="radio" name="trader_id" value="<?php echo htmlspecialchars($traderId); ?>" onchange="updateTraderSelection(this)">
                                <div class="trader-info">
                                    <?php echo $avatarHtml; ?>
                                    <div class="trader-details">
                                        <h3><?php echo $traderName; ?></h3>
                                        <p>@<?php echo $traderHandle; ?></p>
                                    </div>
                                </div>
                                <div class="trader-balance">
                                    <span class="coin-icon">🪙</span>
                                    <?php echo number_format($traderBalance); ?> coins available
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Select Plan -->
                    <div class="form-section">
                        <label style="font-weight: 600; margin-bottom: 15px; display: block; color: #2c3e50;">
                            <i class="fa fa-coins"></i> Choose a Coin Plan
                        </label>
                        
                        <?php if (empty($plans)): ?>
                        <div class="empty-state">
                            <p>No coin plans available.</p>
                        </div>
                        <?php else: ?>
                        <div class="plans-grid">
                            <?php foreach ($plans as $plan):
                                $planId = $plan->getObjectId();
                                $coins = $plan->get("coins") ?? 0;
                                $amount = $plan->get("amount") ?? 0;
                            ?>
                            <label class="plan-card">
                                <input type="radio" name="plan_id" value="<?php echo htmlspecialchars($planId); ?>" onchange="updatePlanSelection(this)">
                                <div class="plan-coins">
                                    <span class="coin-icon">🪙</span> <?php echo number_format($coins); ?>
                                </div>
                                <div class="plan-label">coins</div>
                                <div class="plan-price">$<?php echo number_format($amount, 2); ?></div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Submit Button -->
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="submit" class="btn-request" id="submitBtn" disabled>
                            <i class="fa fa-send"></i> Send Request
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- My Requests Section -->
            <div class="request-section">
                <h2 class="section-title">
                    <i class="fa fa-history"></i>
                    My Coin Requests
                </h2>
                
                <?php if (empty($userRequests)): ?>
                <div class="empty-state">
                    <i class="fa fa-inbox"></i>
                    <p>You haven't requested any coins yet.<br>Start by selecting a trader and coin plan above.</p>
                </div>
                <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="request-table">
                        <thead>
                            <tr>
                                <th>Trader</th>
                                <th>Coins</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Requested Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userRequests as $req):
                                $trader = $req->get("trader");
                                $traderUser = $trader ? $trader->get("user") : null;
                                $traderName = $traderUser ? htmlspecialchars($traderUser->get("name") ?? 'Unknown') : 'Unknown';
                                $traderHandle = $traderUser ? htmlspecialchars($traderUser->get("username") ?? '') : '';
                                
                                $coins = $req->get("coinAmount") ?? 0;
                                $amount = $req->get("amount") ?? 0;
                                $status = $req->get("status") ?? 'pending';
                                $statusClass = 'status-' . $status;
                                
                                $createdAt = $req->getCreatedAt();
                                $createdDate = $createdAt ? $createdAt->format("M d, Y h:i A") : '';
                            ?>
                            <tr>
                                <td>
                                    <div class="trader-name"><?php echo $traderName; ?></div>
                                    <div class="trader-handle">@<?php echo $traderHandle; ?></div>
                                </td>
                                <td><span class="coin-amount"><i class="fa fa-coins"></i> <?php echo number_format($coins); ?></span></td>
                                <td>$<?php echo number_format($amount, 2); ?></td>
                                <td><span class="status-badge <?php echo htmlspecialchars($statusClass); ?>"><?php echo ucfirst($status); ?></span></td>
                                <td><?php echo $createdDate; ?></td>
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

<script>
function updateTraderSelection(elem) {
    document.querySelectorAll('.trader-card').forEach(card => {
        card.classList.remove('selected');
    });
    if (elem.checked) {
        elem.closest('.trader-card').classList.add('selected');
    }
    checkFormValidity();
}

function updatePlanSelection(elem) {
    document.querySelectorAll('.plan-card').forEach(card => {
        card.classList.remove('selected');
    });
    if (elem.checked) {
        elem.closest('.plan-card').classList.add('selected');
    }
    checkFormValidity();
}

function checkFormValidity() {
    const traderId = document.querySelector('input[name="trader_id"]:checked');
    const planId = document.querySelector('input[name="plan_id"]:checked');
    const submitBtn = document.getElementById('submitBtn');
    
    if (traderId && planId) {
        submitBtn.disabled = false;
    } else {
        submitBtn.disabled = true;
    }
}

document.getElementById('coinRequestForm').addEventListener('submit', function(e) {
    const traderId = document.querySelector('input[name="trader_id"]:checked');
    const planId = document.querySelector('input[name="plan_id"]:checked');
    
    if (!traderId || !planId) {
        e.preventDefault();
        alert('Please select both a trader and a coin plan.');
    }
});
</script>
