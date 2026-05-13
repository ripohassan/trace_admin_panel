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

if ($currUser->get("role") !== "admin") {
    header("Refresh:0; url=../auth/logout.php");
    exit;
}

$_SESSION['token'] = $currUser->getSessionToken();

function syncUserRoleForTraderRequest($request, $statusOverride = null)
{
    $targetUser = $request->get('user');
    if (!$targetUser) {
        return false;
    }

    $status = $statusOverride ?? ($request->get('status') ?? 'pending');
    $desiredRole = ($status === 'approved') ? 'trader' : 'user';

    try {
        $userQuery = new ParseQuery('_User');
        $freshUser = $userQuery->get($targetUser->getObjectId(), true);
        $freshUser->set('role', $desiredRole);
        $freshUser->save(true);
        return true;
    } catch (ParseException $e) {
        error_log('Trader request role sync failed: ' . $e->getMessage());
        return false;
    }
}

function ensureTraderRequestsClassExists($seedUser, &$schemaErrorMsg = '')
{
    try {
        $checkQuery = new ParseQuery("TraderRequests");
        $checkQuery->limit(1);
        $checkQuery->find(true);
        return true;
    } catch (ParseException $e) {
        try {
            $bootstrap = ParseObject::create("TraderRequests");
            $bootstrap->set("user", $seedUser);
            $bootstrap->set("countryCode", "");
            $bootstrap->set("mobileNumber", "");
            $bootstrap->set("note", "bootstrap");
            $bootstrap->set("status", "bootstrap");
            $bootstrap->set("is_approve", false);
            $bootstrap->save(true);
            $bootstrap->destroy(true);
            return true;
        } catch (ParseException $createEx) {
            $schemaErrorMsg = "TraderRequests table create hocche na. Back4App app settings/class permissions check korun. Details: " . $createEx->getMessage();
            return false;
        }
    }
}

$traderRequestsReady = ensureTraderRequestsClassExists($currUser, $schemaErrorMsg);
if (!$traderRequestsReady) {
    $errorMsg = $schemaErrorMsg;
}

if ($traderRequestsReady && isset($_POST['action']) && $_POST['action'] === 'approve_trader_request') {
    $requestId = $_POST['request_id'] ?? '';

    if ($requestId !== '') {
        try {
            $requestQuery = new ParseQuery("TraderRequests");
            $requestQuery->includeKey("user");
            $request = $requestQuery->get($requestId, true);

            if (($request->get("status") ?? "pending") !== "pending") {
                $errorMsg = "This request is already processed.";
            } else {
                $targetUser = $request->get("user");
                if (!$targetUser) {
                    $errorMsg = "User not found in this request.";
                } else {
                    // Fetch user directly to ensure proper update
                    $userQuery = new ParseQuery("_User");
                    $targetUserFresh = $userQuery->get($targetUser->getObjectId(), true);
                    
                    $existingTrader = null;
                    try {
                        $existingTraderQuery = new ParseQuery("CoinTraders");
                        $existingTraderQuery->equalTo("user", $targetUserFresh);
                        $existingTrader = $existingTraderQuery->first(true);
                    } catch (ParseException $e) {
                        $existingTrader = null;
                    }

                    if (!$existingTrader) {
                        $newTrader = ParseObject::create("CoinTraders");
                        $newTrader->set("user", $targetUserFresh);
                        $newTrader->set("coinBalance", 0);
                        $newTrader->set("spentCoins", 0);
                        $newTrader->set("countryCode", $request->get("countryCode") ?? '');
                        $newTrader->set("mobileNumber", $request->get("mobileNumber") ?? '');
                        $newTrader->set("isActive", true);
                        $newTrader->set("status", "approved");
                        $newTrader->save(true);
                    } else {
                        $existingTrader->set("isActive", true);
                        $existingTrader->set("status", "approved");
                        $existingTrader->save(true);
                    }

                    syncUserRoleForTraderRequest($request, 'approved');

                    $request->set("status", "approved");
                    $request->set("is_approve", true);
                    $request->set("approvedBy", $currUser);
                    $request->set("approvedAt", new DateTime());
                    $request->save(true);

                    $successMsg = "Trader request approved successfully. User role updated to trader.";
                }
            }
        } catch (ParseException $e) {
            $errorMsg = $e->getMessage();
        }
    }
}

if ($traderRequestsReady && isset($_POST['action']) && $_POST['action'] === 'reject_trader_request') {
    $requestId = $_POST['request_id'] ?? '';

    if ($requestId !== '') {
        try {
            $requestQuery = new ParseQuery("TraderRequests");
            $requestQuery->includeKey("user");
            $request = $requestQuery->get($requestId, true);

            if (($request->get("status") ?? "pending") !== "pending") {
                $errorMsg = "This request is already processed.";
            } else {
                syncUserRoleForTraderRequest($request, 'rejected');

                $request->set("status", "rejected");
                $request->set("is_approve", false);
                $request->set("rejectedBy", $currUser);
                $request->set("rejectedAt", new DateTime());
                $request->save(true);

                $successMsg = "Trader request rejected. User role reset to user.";
            }
        } catch (ParseException $e) {
            $errorMsg = $e->getMessage();
        }
    }
}

// Handle suspend trader action
if ($traderRequestsReady && isset($_POST['action']) && $_POST['action'] === 'suspend_trader') {
    $requestId = $_POST['request_id'] ?? '';

    if ($requestId !== '') {
        try {
            $requestQuery = new ParseQuery("TraderRequests");
            $requestQuery->includeKey("user");
            $request = $requestQuery->get($requestId, true);

            syncUserRoleForTraderRequest($request, 'suspended');

            $request->set("status", "suspended");
            $request->set("suspendedBy", $currUser);
            $request->set("suspendedAt", new DateTime());
            $request->save(true);

            $successMsg = "Trader suspended. User role reset to user.";
        } catch (ParseException $e) {
            $errorMsg = $e->getMessage();
        }
    }
}

// Handle revert to pending action
if ($traderRequestsReady && isset($_POST['action']) && $_POST['action'] === 'revert_to_pending') {
    $requestId = $_POST['request_id'] ?? '';

    if ($requestId !== '') {
        try {
            $requestQuery = new ParseQuery("TraderRequests");
            $requestQuery->includeKey("user");
            $request = $requestQuery->get($requestId, true);

            syncUserRoleForTraderRequest($request, 'pending');

            $request->set("status", "pending");
            $request->set("is_approve", false);
            $request->set("revertedBy", $currUser);
            $request->set("revertedAt", new DateTime());
            $request->save(true);

            $successMsg = "Request reverted to pending. User role reset to user.";
        } catch (ParseException $e) {
            $errorMsg = $e->getMessage();
        }
    }
}

$allRequests = [];
if ($traderRequestsReady) {
    try {
        $allRequestsQuery = new ParseQuery("TraderRequests");
        $allRequestsQuery->includeKey("user");
        $allRequestsQuery->includeKey("approvedBy");
        $allRequestsQuery->descending("createdAt");
        $allRequestsQuery->limit(1000);
        $allRequests = $allRequestsQuery->find(true);

        foreach ($allRequests as $requestItem) {
            syncUserRoleForTraderRequest($requestItem);
        }
    } catch (ParseException $e) {
        $errorMsg = $e->getMessage();
    }
}

?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Coin System</a></li>
                <li class="breadcrumb-item active">Trader Requests</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Trader Requests</h4>
                <p class="text-muted">Approve or reject trader applications from users.</p>

                <?php if (isset($successMsg)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
                <?php endif; ?>
                <?php if (isset($errorMsg)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($errorMsg); ?></div>
                <?php endif; ?>

                <div class="table-responsive m-t-20">
                    <table class="table table-hover" id="traderRequestsTable">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Country Code</th>
                                <th>Mobile</th>
                                <th>Note</th>
                                <th>Status</th>
                                <th>Is Approve</th>
                                <th>Requested At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allRequests)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No trader requests found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allRequests as $req): ?>
                                    <?php
                                    $requestId = $req->getObjectId();
                                    $user = $req->get("user");
                                    $username = $user ? htmlspecialchars($user->get("username") ?? '-') : '-';
                                    $name = $user ? htmlspecialchars($user->get("name") ?? 'Unknown') : 'Unknown';
                                    $countryCode = htmlspecialchars($req->get("countryCode") ?? '-');
                                    $mobileNumber = htmlspecialchars($req->get("mobileNumber") ?? '-');
                                    $note = htmlspecialchars($req->get("note") ?? '-');
                                    $status = $req->get("status") ?? 'pending';
                                    $isApprove = $req->get("is_approve") === true ? 'Yes' : 'No';
                                    $createdAt = $req->getCreatedAt();
                                    $createdAtText = $createdAt ? $createdAt->format("M d, Y h:i A") : '-';
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $name; ?></strong><br>
                                            <small>@<?php echo $username; ?></small>
                                        </td>
                                        <td><?php echo $countryCode; ?></td>
                                        <td><?php echo $mobileNumber; ?></td>
                                        <td style="max-width: 220px; white-space: normal;"><?php echo $note; ?></td>
                                        <td>
                                            <?php if ($status === 'approved'): ?>
                                                <span class="badge badge-success">Approved</span>
                                            <?php elseif ($status === 'rejected'): ?>
                                                <span class="badge badge-danger">Rejected</span>
                                            <?php elseif ($status === 'suspended'): ?>
                                                <span class="badge badge-warning">Suspended</span>
                                            <?php else: ?>
                                                <span class="badge badge-info">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $isApprove; ?></td>
                                        <td><?php echo htmlspecialchars($createdAtText); ?></td>
                                        <td>
                                            <?php if ($status === 'pending'): ?>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="action" value="approve_trader_request">
                                                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($requestId); ?>">
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approve this trader request?');">Approve</button>
                                                </form>
                                                <form method="post" style="display:inline; margin-left: 4px;">
                                                    <input type="hidden" name="action" value="reject_trader_request">
                                                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($requestId); ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reject this trader request?');">Reject</button>
                                                </form>
                                            <?php elseif ($status === 'approved'): ?>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="action" value="suspend_trader">
                                                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($requestId); ?>">
                                                    <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Suspend this trader? User role will be reset to user.');">Suspend</button>
                                                </form>
                                                <form method="post" style="display:inline; margin-left: 4px;">
                                                    <input type="hidden" name="action" value="revert_to_pending">
                                                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($requestId); ?>">
                                                    <button type="submit" class="btn btn-info btn-sm" onclick="return confirm('Revert to pending? User role will be reset to user.');">Revert</button>
                                                </form>
                                            <?php elseif ($status === 'suspended'): ?>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="action" value="revert_to_pending">
                                                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($requestId); ?>">
                                                    <button type="submit" class="btn btn-info btn-sm" onclick="return confirm('Revert to pending?');">Revert</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">Completed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    if ($.fn.DataTable) {
        $('#traderRequestsTable').DataTable({
            ordering: true,
            pageLength: 25,
            order: [[6, 'desc']]
        });
    }
});
</script>
