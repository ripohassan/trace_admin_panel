<?php
require '../vendor/autoload.php';
include '../Configs.php';

use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseUser;
use Parse\ParseException;

session_start();

$currUser = ParseUser::getCurrentUser();
if (!$currUser) {
    header("Refresh:0; url=../index.php");
} elseif ($currUser->get("role") !== "admin"){
    header("Refresh:0; url=../auth/logout.php");
}

$addError = '';
$addSuccess = '';

// Handle Approve Application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve') {
    try {
        $appId = isset($_POST['app_id']) ? trim($_POST['app_id']) : '';
        
        if (!empty($appId)) {
            $query = new ParseQuery("Agency");
            $app = $query->get($appId, true);
            $app->set("status", "active");
            $app->set("approvedAt", new DateTime());
            $app->set("approvedBy", $currUser->getObjectId());
            $app->save();
            $addSuccess = "Application approved successfully!";
        }
    } catch (ParseException $e) {
        $addError = "Error: " . $e->getMessage();
    }
}

// Handle Reject Application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject') {
    try {
        $appId = isset($_POST['app_id']) ? trim($_POST['app_id']) : '';
        $rejectReason = isset($_POST['reject_reason']) ? trim($_POST['reject_reason']) : '';
        
        if (!empty($appId)) {
            $query = new ParseQuery("Agency");
            $app = $query->get($appId, true);
            $app->set("status", "rejected");
            $app->set("rejectionReason", $rejectReason);
            $app->set("rejectedAt", new DateTime());
            $app->set("rejectedBy", $currUser->getObjectId());
            $app->save();
            $addSuccess = "Application rejected successfully!";
        }
    } catch (ParseException $e) {
        $addError = "Error: " . $e->getMessage();
    }
}

?>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/dashboard/images/favicon.png">
    <title><?php echo $app_name;?> - Agency Applications</title>
    <link href="../assets/dashboard/css/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/dashboard/css/lib/calendar2/semantic.ui.min.css" rel="stylesheet">
    <link href="../assets/dashboard/css/lib/calendar2/pignose.calendar.min.css" rel="stylesheet">
    <link href="../assets/dashboard/css/lib/owl.carousel.min.css" rel="stylesheet" />
    <link href="../assets/dashboard/css/lib/owl.theme.default.min.css" rel="stylesheet" />
    <link href="../assets/dashboard/css/helper.css" rel="stylesheet">
    <link href="../assets/dashboard/css/style.css" rel="stylesheet">
    <link href="../assets/dashboard/css/aliki.css" rel="stylesheet">
</head>

<body class="fix-header fix-sidebar">
    <div id="main-wrapper">
        <?php
        include '../admin/header_admin.php';
        include '../admin/left_sidebar_admin.php';
        ?>

        <div class="page-wrapper">
            <div class="row page-titles">
                <div class="col">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0)">Agency</a></li>
                        <li class="breadcrumb-item active">Applications Review</li>
                    </ol>
                </div>
            </div>

            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="m-0">Agency Applications Review</h4>
                            </div>

                            <?php if (!empty($addError)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($addError); ?>
                                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($addSuccess)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($addSuccess); ?>
                                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                </div>
                            <?php endif; ?>

                            <div class="card-body">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-toggle="tab" href="#pending" role="tab">Pending</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#approved" role="tab">Approved</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#rejected" role="tab">Rejected</a>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    <!-- Pending Applications -->
                                    <div class="tab-pane fade show active" id="pending" role="tabpanel">
                                        <div class="table-responsive" style="margin-top: 20px;">
                                            <table class="display nowrap table table-hover table-striped table-bordered" cellspacing="0" width="100%">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th style="color:#65131f;">ID</th>
                                                        <th style="color:#65131f;">User ID</th>
                                                        <th style="color:#65131f;">Full Name</th>
                                                        <th style="color:#65131f;">Email</th>
                                                        <th style="color:#65131f;">Agency Name</th>
                                                        <th style="color:#65131f;">Description</th>
                                                        <th style="color:#65131f;">Applied Date</th>
                                                        <th style="color:#65131f;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    try {
                                                        $query = new ParseQuery("Agency");
                                                        $query->equalTo("status", "pending");
                                                        $query->descending('createdAt');
                                                        $query->limit(500);
                                                        $appArray = $query->find(true);

                                                        if (count($appArray) === 0) {
                                                            echo '<tr><td colspan="8" class="text-center text-warning font-weight-bold">No pending applications</td></tr>';
                                                        } else {
                                                            foreach ($appArray as $app) {
                                                                $appId = $app->getObjectId();
                                                                $userId = $app->get('userId');
                                                                $fullName = $app->get('fullName');
                                                                $email = $app->get('email');
                                                                $agencyName = $app->get('agencyName');
                                                                $description = substr($app->get('description'), 0, 50) . '...';
                                                                $createdAt = $app->getCreatedAt();
                                                                $appliedDate = $createdAt ? $createdAt->format('Y-m-d H:i:s') : 'N/A';

                                                                echo '
                                                                <tr>
                                                                    <td>' . htmlspecialchars($appId) . '</td>
                                                                    <td>' . htmlspecialchars($userId) . '</td>
                                                                    <td>' . htmlspecialchars($fullName) . '</td>
                                                                    <td>' . htmlspecialchars($email) . '</td>
                                                                    <td>' . htmlspecialchars($agencyName) . '</td>
                                                                    <td>' . htmlspecialchars($description) . '</td>
                                                                    <td>' . htmlspecialchars($appliedDate) . '</td>
                                                                    <td>
                                                                        <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#approveModal" onclick="setAppId(\'' . $appId . '\')">
                                                                            <i class="fa fa-check"></i> Approve
                                                                        </button>
                                                                        <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#rejectModal" onclick="setAppId(\'' . $appId . '\')">
                                                                            <i class="fa fa-times"></i> Reject
                                                                        </button>
                                                                        <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#viewModal" onclick="viewDetails(\'' . addslashes($userId) . '\', \'' . addslashes($fullName) . '\', \'' . addslashes($email) . '\', \'' . addslashes($agencyName) . '\', \'' . addslashes($app->get('description')) . '\')">
                                                                            <i class="fa fa-eye"></i> View
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                                ';
                                                            }
                                                        }
                                                    } catch (ParseException $e) {
                                                        echo '<tr><td colspan="8" class="text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Approved Applications -->
                                    <div class="tab-pane fade" id="approved" role="tabpanel">
                                        <div class="table-responsive" style="margin-top: 20px;">
                                            <table class="display nowrap table table-hover table-striped table-bordered" cellspacing="0" width="100%">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th style="color:#65131f;">ID</th>
                                                        <th style="color:#65131f;">User ID</th>
                                                        <th style="color:#65131f;">Agency Name</th>
                                                        <th style="color:#65131f;">Status</th>
                                                        <th style="color:#65131f;">Applied Date</th>
                                                        <th style="color:#65131f;">Approved Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    try {
                                                        $query = new ParseQuery("Agency");
                                                        $query->equalTo("status", "active");
                                                        $query->descending('approvedAt');
                                                        $query->limit(500);
                                                        $appArray = $query->find(true);

                                                        if (count($appArray) === 0) {
                                                            echo '<tr><td colspan="6" class="text-center text-warning font-weight-bold">No approved applications</td></tr>';
                                                        } else {
                                                            foreach ($appArray as $app) {
                                                                $appId = $app->getObjectId();
                                                                $userId = $app->get('userId');
                                                                $agencyName = $app->get('agencyName');
                                                                $createdAt = $app->getCreatedAt();
                                                                $appliedDate = $createdAt ? $createdAt->format('Y-m-d H:i:s') : 'N/A';
                                                                $approvedAt = $app->get('approvedAt');
                                                                $approvedDate = $approvedAt ? $approvedAt->format('Y-m-d H:i:s') : 'N/A';

                                                                echo '
                                                                <tr>
                                                                    <td>' . htmlspecialchars($appId) . '</td>
                                                                    <td>' . htmlspecialchars($userId) . '</td>
                                                                    <td>' . htmlspecialchars($agencyName) . '</td>
                                                                    <td><span class="badge badge-success">Approved</span></td>
                                                                    <td>' . htmlspecialchars($appliedDate) . '</td>
                                                                    <td>' . htmlspecialchars($approvedDate) . '</td>
                                                                </tr>
                                                                ';
                                                            }
                                                        }
                                                    } catch (ParseException $e) {
                                                        echo '<tr><td colspan="6" class="text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Rejected Applications -->
                                    <div class="tab-pane fade" id="rejected" role="tabpanel">
                                        <div class="table-responsive" style="margin-top: 20px;">
                                            <table class="display nowrap table table-hover table-striped table-bordered" cellspacing="0" width="100%">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th style="color:#65131f;">ID</th>
                                                        <th style="color:#65131f;">User ID</th>
                                                        <th style="color:#65131f;">Agency Name</th>
                                                        <th style="color:#65131f;">Status</th>
                                                        <th style="color:#65131f;">Rejection Reason</th>
                                                        <th style="color:#65131f;">Rejected Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    try {
                                                        $query = new ParseQuery("Agency");
                                                        $query->equalTo("status", "rejected");
                                                        $query->descending('rejectedAt');
                                                        $query->limit(500);
                                                        $appArray = $query->find(true);

                                                        if (count($appArray) === 0) {
                                                            echo '<tr><td colspan="6" class="text-center text-warning font-weight-bold">No rejected applications</td></tr>';
                                                        } else {
                                                            foreach ($appArray as $app) {
                                                                $appId = $app->getObjectId();
                                                                $userId = $app->get('userId');
                                                                $agencyName = $app->get('agencyName');
                                                                $rejectionReason = $app->get('rejectionReason');
                                                                $rejectedAt = $app->get('rejectedAt');
                                                                $rejectedDate = $rejectedAt ? $rejectedAt->format('Y-m-d H:i:s') : 'N/A';

                                                                echo '
                                                                <tr>
                                                                    <td>' . htmlspecialchars($appId) . '</td>
                                                                    <td>' . htmlspecialchars($userId) . '</td>
                                                                    <td>' . htmlspecialchars($agencyName) . '</td>
                                                                    <td><span class="badge badge-danger">Rejected</span></td>
                                                                    <td>' . htmlspecialchars($rejectionReason ?? 'N/A') . '</td>
                                                                    <td>' . htmlspecialchars($rejectedDate) . '</td>
                                                                </tr>
                                                                ';
                                                            }
                                                        }
                                                    } catch (ParseException $e) {
                                                        echo '<tr><td colspan="6" class="text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
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
            </div>

            <?php include 'footer.php' ?>
        </div>
    </div>

    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1" role="dialog" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveModalLabel">Approve Application</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" id="approve_app_id" name="app_id">
                        <p>Are you sure you want to approve this application?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Approve</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Reject Application</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" id="reject_app_id" name="app_id">
                        <div class="form-group">
                            <label for="reject_reason">Rejection Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="reject_reason" name="reject_reason" placeholder="Please input rejection reason" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalLabel">Application Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>User ID:</label>
                        <p id="view_user_id"></p>
                    </div>
                    <div class="form-group">
                        <label>Full Name:</label>
                        <p id="view_full_name"></p>
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <p id="view_email"></p>
                    </div>
                    <div class="form-group">
                        <label>Agency Name:</label>
                        <p id="view_agency_name"></p>
                    </div>
                    <div class="form-group">
                        <label>Description:</label>
                        <p id="view_description"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/dashboard/js/lib/jquery/jquery.min.js"></script>
    <script src="../assets/dashboard/js/lib/bootstrap/js/bootstrap.min.js"></script>
    <script src="../assets/dashboard/js/lib/datatables/datatables.min.js"></script>
    <script src="../assets/dashboard/js/lib/datatables/cdn.datatables.min.js"></script>
    <script src="../assets/dashboard/js/lib/datatables/buttons.flash.min.js"></script>
    <script src="../assets/dashboard/js/lib/datatables/buttons.html5.min.js"></script>
    <script src="../assets/dashboard/js/lib/datatables/buttons.print.min.js"></script>
    <script src="../assets/dashboard/js/lib/datatables/buttons.colVis.min.js"></script>
    <script src="../assets/dashboard/js/lib/datatables/datatables-init.js"></script>
    <script>
        function setAppId(appId) {
            document.getElementById('approve_app_id').value = appId;
            document.getElementById('reject_app_id').value = appId;
        }

        function viewDetails(userId, fullName, email, agencyName, description) {
            document.getElementById('view_user_id').textContent = userId;
            document.getElementById('view_full_name').textContent = fullName;
            document.getElementById('view_email').textContent = email;
            document.getElementById('view_agency_name').textContent = agencyName;
            document.getElementById('view_description').textContent = description;
        }
    </script>
</body>
</html>
