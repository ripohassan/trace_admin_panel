<?php
require '../vendor/autoload.php';
include '../Configs.php';

use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseUser;
use Parse\ParseException;

session_start();

$currUser = ParseUser::getCurrentUser();
$currentRole = $currUser ? $currUser->get("role") : null;
if (!$currUser) {
    header("Refresh:0; url=../index.php");
} elseif (!in_array($currentRole, ["admin", "bd"], true)){
    header("Refresh:0; url=../auth/logout.php");
}

$addError = '';
$addSuccess = '';

// Handle Create Agency
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_agency') {
    try {
        $agencyId = isset($_POST['agency_id']) ? trim($_POST['agency_id']) : '';
        $agencyName = isset($_POST['agency_name']) ? trim($_POST['agency_name']) : '';
        $presidentId = isset($_POST['president_id']) ? trim($_POST['president_id']) : '';
        $contactAddress = isset($_POST['contact_address']) ? trim($_POST['contact_address']) : '';
        $realName = isset($_POST['real_name']) ? trim($_POST['real_name']) : '';
        $region = isset($_POST['region']) ? trim($_POST['region']) : '';
        $agencyLevel = isset($_POST['agency_level']) ? trim($_POST['agency_level']) : '';
        $remark = isset($_POST['remark']) ? trim($_POST['remark']) : '';
        $externalAdminUserId = isset($_POST['external_admin_user_id']) ? trim($_POST['external_admin_user_id']) : '';
        $externalAdminNickname = isset($_POST['external_admin_nickname']) ? trim($_POST['external_admin_nickname']) : '';
        $parentAdminOrSelect = isset($_POST['parent_admin_or']) ? trim($_POST['parent_admin_or']) : '';

        if (empty($agencyId) || empty($agencyName) || empty($presidentId) || empty($region) || empty($agencyLevel)) {
            $addError = "Agency ID, Name, President ID, Region, and Agency Level are required.";
        } else {
            // Check if agency already exists
            $checkQuery = new ParseQuery("Agency");
            $checkQuery->equalTo("agencyId", $agencyId);
            $existing = $checkQuery->find(true);
            
            if (count($existing) > 0) {
                $addError = "This agency ID already exists.";
            } else {
                $agency = new ParseObject("Agency");
                $agency->set("agencyId", $agencyId);
                $agency->set("agencyName", $agencyName);
                $agency->set("presidentId", $presidentId);
                $agency->set("contactAddress", $contactAddress);
                $agency->set("realName", $realName);
                $agency->set("region", $region);
                $agency->set("agencyLevel", $agencyLevel);
                $agency->set("remark", $remark);
                $agency->set("externalAdminUserId", $externalAdminUserId);
                $agency->set("externalAdminNickname", $externalAdminNickname);
                $agency->set("parentAdminOr", $parentAdminOrSelect);
                $agency->set("status", "active");
                $agency->save();
                $addSuccess = "Agency created successfully!";
            }
        }
    } catch (ParseException $e) {
        $addError = "Error: " . $e->getMessage();
    }
}

// Handle Delete Agency
if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    isset($_GET['action']) &&
    $_GET['action'] === 'delete' &&
    isset($_GET['id']) &&
    $currentRole === 'admin'
) {
    try {
        $query = new ParseQuery("Agency");
        $agency = $query->get($_GET['id'], true);
        $agency->destroy();
        header("Location: agency_list.php?deleted=1");
        exit;
    } catch (ParseException $e) {
        $addError = "Error deleting agency: " . $e->getMessage();
    }
}

?>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/dashboard/images/favicon.png">
    <title><?php echo $app_name;?> - Agency List</title>
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

        <style>
            .createAgencyModal label {
                color: #333;
            }
        </style>

        <div class="page-wrapper">
            <div class="row page-titles">
                <div class="col">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0)">Agency</a></li>
                        <li class="breadcrumb-item active">Agency List</li>
                    </ol>
                </div>
            </div>

            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="m-0">Agency List</h4>
                                <button class="btn btn-primary float-right" data-toggle="modal" data-target="#createAgencyModal">
                                    <i class="fa fa-plus"></i> Create Agency
                                </button>
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

                            <?php if (isset($_GET['deleted'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    Agency deleted successfully!
                                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                </div>
                            <?php endif; ?>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="display nowrap table table-hover table-striped table-bordered" cellspacing="0" width="100%">
                                        <thead class="bg-light">
                                            <tr>
                                                <th style="color:#65131f;">Internal</th>
                                                <th style="color:#65131f;">Agency ID</th>
                                                <th style="color:#65131f;">Agency Name</th>
                                                <th style="color:#65131f;">Status</th>
                                                <th style="color:#65131f;">President ID</th>
                                                <th style="color:#65131f;">External Admin User ID</th>
                                                <th style="color:#65131f;">External Admin Nickname</th>
                                                <th style="color:#65131f;">BD User ID</th>
                                                <th style="color:#65131f;">BD Nickname</th>
                                                <th style="color:#65131f;">Country</th>
                                                <th style="color:#65131f;">Region</th>
                                                <th style="color:#65131f;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            try {
                                                $query = new ParseQuery("Agency");
                                                $query->descending('createdAt');
                                                $query->limit(500);
                                                $agencyArray = $query->find(true);

                                                if (count($agencyArray) === 0) {
                                                    echo '<tr><td colspan="11" class="text-center text-warning font-weight-bold">No agencies found</td></tr>';
                                                } else {
                                                    $counter = 0;
                                                    foreach ($agencyArray as $agency) {
                                                        $counter++;
                                                        $agencyObjId = $agency->getObjectId();
                                                        $agencyId = $agency->get('agencyId');
                                                        $agencyName = $agency->get('agencyName');
                                                        $presidentId = $agency->get('presidentId');
                                                        $externalAdminUserId = $agency->get('externalAdminUserId');
                                                        $externalAdminNickname = $agency->get('externalAdminNickname');
                                                        $bdUserId = $agency->get('bdUserId') ?? '-';
                                                        $bdNickname = $agency->get('bdNickname') ?? '-';
                                                        $country = $agency->get('country') ?? '-';
                                                        $region = $agency->get('region');
                                                        $statusValue = $agency->get('status') ?? 'active';
                                                        $statusBadge = '<span class="badge badge-secondary">' . htmlspecialchars(ucfirst($statusValue)) . '</span>';
                                                        if ($statusValue === 'pending') {
                                                            $statusBadge = '<span class="badge badge-warning">Pending</span>';
                                                        } elseif ($statusValue === 'active' || $statusValue === 'approved') {
                                                            $statusBadge = '<span class="badge badge-success">Approved</span>';
                                                        } elseif ($statusValue === 'rejected') {
                                                            $statusBadge = '<span class="badge badge-danger">Rejected</span>';
                                                        }

                                                        echo '
                                                        <tr>
                                                            <td>' . $counter . '</td>
                                                            <td>' . htmlspecialchars($agencyId) . '</td>
                                                            <td>' . htmlspecialchars($agencyName) . '</td>
                                                            <td>' . $statusBadge . '</td>
                                                            <td>' . htmlspecialchars($presidentId) . '</td>
                                                            <td>' . htmlspecialchars($externalAdminUserId ?? 'N/A') . '</td>
                                                            <td>' . htmlspecialchars($externalAdminNickname ?? 'N/A') . '</td>
                                                            <td>' . htmlspecialchars($bdUserId) . '</td>
                                                            <td>' . htmlspecialchars($bdNickname) . '</td>
                                                            <td>' . htmlspecialchars($country) . '</td>
                                                            <td>' . htmlspecialchars($region) . '</td>
                                                            <td>';

                                                        if ($currentRole === 'admin') {
                                                            echo '
                                                                <a href="agency_list.php?action=delete&id=' . $agencyObjId . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure?\');">
                                                                    <i class="fa fa-trash"></i> Delete
                                                                </a>';
                                                        } else {
                                                            echo '<span class="badge badge-info">Create/View</span>';
                                                        }

                                                        echo '
                                                            </td>
                                                        </tr>
                                                        ';
                                                    }
                                                }
                                            } catch (ParseException $e) {
                                                echo '<tr><td colspan="11" class="text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
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

            <?php include 'footer.php' ?>
        </div>
    </div>

    <!-- Create Agency Modal -->
    <div class="modal fade createAgencyModal" id="createAgencyModal" tabindex="-1" role="dialog" aria-labelledby="createAgencyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createAgencyModalLabel">Create Agency</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_agency">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="agency_id">Agency ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="agency_id" name="agency_id" placeholder="Please input" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="agency_name">Agency Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="agency_name" name="agency_name" placeholder="Please input" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="president_id">President ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="president_id" name="president_id" placeholder="Please input" required>
                        </div>
                        <div class="form-group">
                            <label for="contact_address">Contact Address</label>
                            <input type="text" class="form-control" id="contact_address" name="contact_address" placeholder="Please input">
                        </div>
                        <div class="form-group">
                            <label for="real_name">Real Name</label>
                            <input type="text" class="form-control" id="real_name" name="real_name" placeholder="Please input">
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="region">Region <span class="text-danger">*</span></label>
                                <select class="form-control" id="region" name="region" required>
                                    <option value="">Select</option>
                                    <option value="Bangladesh">Bangladesh</option>
                                    <option value="India">India</option>
                                    <option value="Pakistan">Pakistan</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="agency_level">Agency Level <span class="text-danger">*</span></label>
                                <select class="form-control" id="agency_level" name="agency_level" required>
                                    <option value="">Select</option>
                                    <option value="Level 1">Level 1</option>
                                    <option value="Level 2">Level 2</option>
                                    <option value="Level 3">Level 3</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="external_admin_user_id">External Admin User ID</label>
                            <input type="text" class="form-control" id="external_admin_user_id" name="external_admin_user_id" placeholder="Please input">
                        </div>
                        <div class="form-group">
                            <label for="external_admin_nickname">External Admin Nickname</label>
                            <input type="text" class="form-control" id="external_admin_nickname" name="external_admin_nickname" placeholder="Please input">
                        </div>
                        <div class="form-group">
                            <label for="parent_admin_or">Parent Admin or</label>
                            <select class="form-control" id="parent_admin_or" name="parent_admin_or">
                                <option value="">Select</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="remark">Remark</label>
                            <textarea class="form-control" id="remark" name="remark" placeholder="Please input" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">OK</button>
                    </div>
                </form>
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
        (function ($) {
            'use strict';

            // Ensure stale backdrops never block clicks after a modal closes.
            $(document).on('hidden.bs.modal', '.modal', function () {
                $('body').removeClass('modal-open').css('padding-right', '');
                $('.modal-backdrop').remove();
            });
        })(jQuery);
    </script>
</body>
</html>
