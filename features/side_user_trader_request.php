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

$isAlreadyTrader = false;
try {
    $traderCheckQuery = new ParseQuery("CoinTraders");
    $traderCheckQuery->equalTo("user", $currUser);
    $traderCheckQuery->first(true);
    $isAlreadyTrader = true;
} catch (ParseException $e) {
    $isAlreadyTrader = false;
}

if (isset($_POST['action']) && $_POST['action'] === 'request_trader') {
    $countryCode = trim($_POST['country_code'] ?? '');
    $mobileNumber = trim($_POST['mobile_number'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if ($isAlreadyTrader) {
        $errorMsg = "You are already a trader.";
    } elseif ($countryCode === '' || $mobileNumber === '') {
        $errorMsg = "Country code and mobile number are required.";
    } else {
        try {
            $pendingQuery = new ParseQuery("TraderRequests");
            $pendingQuery->equalTo("user", $currUser);
            $pendingQuery->equalTo("status", "pending");
            $pendingRequest = $pendingQuery->first(true);

            if ($pendingRequest) {
                $errorMsg = "You already have a pending trader request.";
            }
        } catch (ParseException $e) {
            // No pending request found.
        }

        if (!isset($errorMsg)) {
            try {
                $request = ParseObject::create("TraderRequests");
                $request->set("user", $currUser);
                $request->set("countryCode", $countryCode);
                $request->set("mobileNumber", $mobileNumber);
                $request->set("note", $note);
                $request->set("status", "pending");
                $request->set("is_approve", false);
                $request->save(true);

                $successMsg = "Your trader request has been submitted.";
            } catch (ParseException $e) {
                $errorMsg = $e->getMessage();
            }
        }
    }
}

$myRequests = [];
try {
    $myRequestsQuery = new ParseQuery("TraderRequests");
    $myRequestsQuery->equalTo("user", $currUser);
    $myRequestsQuery->includeKey("approvedBy");
    $myRequestsQuery->descending("createdAt");
    $myRequestsQuery->limit(200);
    $myRequests = $myRequestsQuery->find(true);
} catch (ParseException $e) {
    $errorMsg = $e->getMessage();
}

?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Coin System</a></li>
                <li class="breadcrumb-item active">Trader Request</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Become a Trader</h4>
                        <p class="text-muted">Send a request and wait for admin approval.</p>

                        <?php if (isset($successMsg)): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
                        <?php endif; ?>
                        <?php if (isset($errorMsg)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMsg); ?></div>
                        <?php endif; ?>

                        <?php if ($isAlreadyTrader): ?>
                            <div class="alert alert-info">Your account is already approved as trader.</div>
                        <?php else: ?>
                            <form method="post">
                                <input type="hidden" name="action" value="request_trader">

                                <div class="form-group">
                                    <label>Country Code</label>
                                    <input type="text" name="country_code" class="form-control" placeholder="e.g. +880" required>
                                </div>

                                <div class="form-group">
                                    <label>Mobile Number</label>
                                    <input type="text" name="mobile_number" class="form-control" placeholder="e.g. 1712345678" required>
                                </div>

                                <div class="form-group">
                                    <label>Note (Optional)</label>
                                    <textarea name="note" class="form-control" rows="3" placeholder="Write your message..."></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary">Submit Request</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">My Trader Requests</h4>

                        <div class="table-responsive m-t-20">
                            <table class="table table-hover" id="myTraderRequestsTable">
                                <thead>
                                    <tr>
                                        <th>Requested At</th>
                                        <th>Mobile</th>
                                        <th>Status</th>
                                        <th>Is Approve</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($myRequests)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No requests found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($myRequests as $req): ?>
                                            <?php
                                            $status = $req->get("status") ?? "pending";
                                            $isApprove = $req->get("is_approve") === true ? 'Yes' : 'No';
                                            $countryCode = htmlspecialchars($req->get("countryCode") ?? '');
                                            $mobileNumber = htmlspecialchars($req->get("mobileNumber") ?? '');
                                            $requestedAt = $req->getCreatedAt();
                                            $requestedAtText = $requestedAt ? $requestedAt->format("M d, Y h:i A") : '-';
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($requestedAtText); ?></td>
                                                <td><?php echo $countryCode . ' ' . $mobileNumber; ?></td>
                                                <td>
                                                    <?php if ($status === 'approved'): ?>
                                                        <span class="badge badge-success">Approved</span>
                                                    <?php elseif ($status === 'rejected'): ?>
                                                        <span class="badge badge-danger">Rejected</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $isApprove; ?></td>
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
    </div>
</div>

<script>
$(function () {
    if ($.fn.DataTable) {
        $('#myTraderRequestsTable').DataTable({
            ordering: true,
            pageLength: 10,
            order: [[0, 'desc']]
        });
    }
});
</script>
