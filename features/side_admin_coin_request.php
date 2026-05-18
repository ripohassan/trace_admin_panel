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

// Only admins can access
if ($currUser->get("role") !== "admin") {
    header("Refresh:0; url=../index.php");
    exit;
}

$_SESSION['token'] = $currUser->getSessionToken();

// Handle Create Coin Request (Admin sends to Trader)
if (isset($_POST['action']) && $_POST['action'] === 'create_request') {
    $traderId = $_POST['trader_id'] ?? '';
    $coinAmount = (int)($_POST['coin_amount'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    
    if ($traderId && $coinAmount > 0 && $amount > 0) {
        try {
            // Get trader
            $traderQuery = new ParseQuery("CoinTraders");
            $trader = $traderQuery->get($traderId, true);
            
            if ($trader) {
                $request = ParseObject::create("CoinRequests");
                $request->set("user", $currUser); // Admin sending request
                $request->set("trader", $trader);
                $request->set("coinAmount", $coinAmount);
                $request->set("amount", $amount);
                $request->set("status", "pending");
                $request->set("is_approve", false); // Not approved yet
                $request->set("requestedAt", new DateTime());
                $request->save(true);
                
                $successMsg = "Coin request sent to trader successfully!";
            } else {
                $errorMsg = "Invalid trader selected.";
            }
        } catch (ParseException $e) {
            $errorMsg = $e->getMessage();
        }
    } else {
        $errorMsg = "Please fill all fields with valid values.";
    }
}

// Handle Update is_approve status
if (isset($_POST['action']) && $_POST['action'] === 'update_approve') {
    $requestId = $_POST['request_id'] ?? '';
    $isApprove = $_POST['is_approve'] === '1';
    
    if ($requestId) {
        try {
            $query = new ParseQuery("CoinRequests");
            $request = $query->get($requestId, true);
            $request->set("is_approve", $isApprove);
            $request->save(true);
            
            $successMsg = "Request approval status updated.";
        } catch (ParseException $e) {
            $errorMsg = $e->getMessage();
        }
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

// Fetch all coin requests
$allRequests = [];
try {
    $requestQuery = new ParseQuery("CoinRequests");
    $requestQuery->includeKey("trader");
    $requestQuery->includeKey("trader.user");
    $requestQuery->includeKey("user");
    $requestQuery->descending('createdAt');
    $requestQuery->limit(1500);
    $allRequests = $requestQuery->find(true);
} catch (ParseException $e) {
    $errorMsg = "Error fetching requests: " . $e->getMessage();
}

?>

<style>
    .admin-requests-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 0;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .admin-requests-header h2 {
        font-size: 22px;
        font-weight: 600;
        color: #fff;
        margin: 0;
    }
    
    .admin-requests-header p {
        color: #636e72;
        margin: 2px 0 0 0;
        font-size: 14px;
    }
    
    .form-container {
        background: #fff;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }
    
    .form-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .form-title i {
        color: #6c5ce7;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr auto;
        gap: 15px;
        align-items: flex-end;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
    }
    
    .form-group label {
        font-weight: 600;
        margin-bottom: 8px;
        color: #2c3e50;
        font-size: 13px;
    }
    
    .form-group select,
    .form-group input {
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 13px;
        outline: none;
        color: #333;
    }
    
    .form-group select:focus,
    .form-group input:focus {
        border-color: #6c5ce7;
    }
    
    .btn-send {
        background: #6c5ce7;
        color: #fff;
        border: none;
        padding: 10px 25px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-send:hover {
        background: #5a4bd1;
    }
    
    .table-container {
        background: #fff;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        overflow-x: auto;
    }
    
    .table-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .table-title i {
        color: #6c5ce7;
    }
    
    .requests-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .requests-table thead {
        background: #f8f9fa;
        border-bottom: 2px solid #eee;
    }
    
    .requests-table th {
        padding: 12px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #636e72;
    }
    
    .requests-table td {
        padding: 12px;
        border-bottom: 1px solid #eee;
        font-size: 13px;
        color: #333;
    }
    
    .requests-table tbody tr:hover {
        background: #f8f9fa;
    }
    
    .trader-name {
        font-weight: 600;
        color: #2c3e50;
    }
    
    .trader-handle {
        font-size: 12px;
        color: #636e72;
    }
    
    .coin-badge {
        background: #ffeaa7;
        color: #d68910;
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: 600;
        display: inline-block;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-pending {
        background: #ffeaa7;
        color: #d68910;
    }
    
    .status-completed {
        background: #d5f5e3;
        color: #27ae60;
    }
    
    .approve-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #6c5ce7;
    }
    
    .approve-form {
        display: inline;
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
    
    @media (max-width: 1200px) {
        .form-row {
            grid-template-columns: 1fr 1fr;
        }
        .btn-send {
            grid-column: 1 / -1;
        }
    }
</style>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Coin System</a></li>
                <li class="breadcrumb-item active">Coin Requests Management</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        
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
        
        <!-- Request Form -->
        <!-- <div class="form-container">
            <div class="admin-requests-header">
                <div>
                    <h2>Send Coin Request</h2>
                    <p>Send coin requests to traders</p>
                </div>
            </div>
            
            <form method="post">
                <input type="hidden" name="action" value="create_request">
                <div class="form-row">
                    <div class="form-group">
                        <label>Select Trader</label>
                        <select name="trader_id" required>
                            <option value="">-- Choose Trader --</option>
                            <?php foreach ($traders as $trader): 
                                $traderId = $trader->getObjectId();
                                $traderUser = $trader->get("user");
                                $traderName = $traderUser ? htmlspecialchars($traderUser->get("name") ?? 'Trader') : 'Unknown Trader';
                            ?>
                            <option value="<?php echo htmlspecialchars($traderId); ?>">
                                <?php echo $traderName; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Coin Amount</label>
                        <input type="number" name="coin_amount" placeholder="e.g., 1000" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Amount ($)</label>
                        <input type="number" name="amount" placeholder="e.g., 10.99" step="0.01" min="0.01" required>
                    </div>
                    
                    <button type="submit" class="btn-send">
                        <i class="fa fa-send"></i> Send Request
                    </button>
                </div>
            </form>
        </div> -->
        
        <!-- Requests Table -->
        <div class="table-container">
            <div class="table-title">
                <i class="fa fa-list"></i>
                All Coin Requests
            </div>
            
            <?php if (empty($allRequests)): ?>
            <div class="empty-state">
                <i class="fa fa-inbox"></i>
                <p>No coin requests yet.</p>
            </div>
            <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="requests-table" id="requestsTable">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Trader</th>
                            <th>Admin</th>
                            <th>Coins</th>
                            <th>Amount ($)</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th style="text-align: center;">Is Approve</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allRequests as $req):
                            $reqId = $req->getObjectId();
                            
                            // Trader info
                            $trader = $req->get("trader");
                            $traderUser = $trader ? $trader->get("user") : null;
                            $traderName = $traderUser ? htmlspecialchars($traderUser->get("name") ?? 'Unknown') : 'Unknown';
                            $traderHandle = $traderUser ? htmlspecialchars($traderUser->get("username") ?? '') : '';
                            
                            // Admin info
                            $admin = $req->get("user");
                            $adminName = $admin ? htmlspecialchars($admin->get("name") ?? 'Admin') : 'Unknown';
                            
                            $coins = $req->get("coinAmount") ?? 0;
                            $amount = $req->get("amount") ?? 0;
                            $status = $req->get("status") ?? 'pending';
                            $isApprove = $req->get("is_approve") ?? false;
                            
                            $createdAt = $req->getCreatedAt();
                            $createdDate = $createdAt ? $createdAt->format("M d, Y h:i A") : '';
                            
                            $statusClass = 'status-pending';
                            if ($status === 'completed') {
                                $statusClass = 'status-completed';
                            }
                        ?>
                        <tr>
                            <td style="font-size:11px; color:#636e72;">
                                <?php echo htmlspecialchars(substr($reqId, 0, 10)); ?>
                            </td>
                            <td>
                                <div class="trader-name"><?php echo $traderName; ?></div>
                                <div class="trader-handle">@<?php echo $traderHandle; ?></div>
                            </td>
                            <td>
                                <span style="font-weight: 600;"><?php echo $adminName; ?></span>
                            </td>
                            <td>
                                <span class="coin-badge">🪙 <?php echo number_format($coins); ?></span>
                            </td>
                            <td>$<?php echo number_format($amount, 2); ?></td>
                            <td>
                                <span class="status-badge <?php echo htmlspecialchars($statusClass); ?>">
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                            <td><?php echo $createdDate; ?></td>
                            <td style="text-align: center;">
                                <form method="post" class="approve-form" style="display: inline;">
                                    <input type="hidden" name="action" value="update_approve">
                                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($reqId); ?>">
                                    <input type="hidden" name="is_approve" value="<?php echo $isApprove ? '0' : '1'; ?>">
                                    <input type="checkbox" class="approve-checkbox" <?php echo $isApprove ? 'checked' : ''; ?> onchange="this.form.submit();" title="Toggle approval status">
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>


        "pageLength": 25,
        "responsive": true,
        "columnDefs": [
            {
                "targets": [7], // is_approve column
                "orderable": false
            }
        ]
    });
});
</script>
