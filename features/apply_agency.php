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
    header("Refresh:0; url=../auth/login.php");
    exit;
}

$error = '';
$success = '';
$applicationExists = false;
$userApplication = null;

// Check if user has already applied
try {
    $query = new ParseQuery("Agency");
    $query->equalTo("userId", $currUser->getObjectId());
    $existing = $query->find(true);
    
    if (count($existing) > 0) {
        $applicationExists = true;
        $userApplication = $existing[0];
    }
} catch (ParseException $e) {
    // Error checking, continue
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply') {
    if ($applicationExists) {
        $error = "You have already applied for agency. Please wait for admin review.";
    } else {
        try {
            $fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $agencyName = isset($_POST['agency_name']) ? trim($_POST['agency_name']) : '';
            $description = isset($_POST['description']) ? trim($_POST['description']) : '';
            $contactPhone = isset($_POST['contact_phone']) ? trim($_POST['contact_phone']) : '';
            $region = isset($_POST['region']) ? trim($_POST['region']) : '';

            if (empty($fullName) || empty($email) || empty($agencyName) || empty($description)) {
                $error = "All required fields must be filled.";
            } else {
                $agency = new ParseObject("Agency");
                $agency->set("userId", $currUser->getObjectId());
                $agency->set("fullName", $fullName);
                $agency->set("email", $email);
                $agency->set("agencyName", $agencyName);
                $agency->set("description", $description);
                $agency->set("contactPhone", $contactPhone);
                $agency->set("region", $region);
                $agency->set("status", "pending");
                $agency->save();
                $success = "Your agency application has been submitted successfully! Please wait for admin review.";
                $applicationExists = true;
                $userApplication = $agency;
            }
        } catch (ParseException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/dashboard/images/favicon.png">
    <title><?php echo $app_name;?> - Apply for Agency</title>
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
        <!-- Header -->
        <div class="header">
            <nav class="navbar navbar-expand-lg navbar-light">
                <div class="navbar-header" style="width: 300px;">
                    <a class="navbar-brand" href="../dashboard/panel.php">
                        <span class="db"><img src="../assets/dashboard/images/logo.png" alt="<?php echo $app_name; ?>" style="height: 35px;"></span>
                    </a>
                </div>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-muted  " href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-user"></i> <?php echo $currUser->getUsername(); ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="../auth/logout.php"><i class="fa fa-sign-out"></i> Logout</a>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>

        <div class="page-wrapper">
            <div class="container-fluid">
                <div class="row page-titles">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../dashboard/panel.php">Home</a></li>
                            <li class="breadcrumb-item active">Apply for Agency</li>
                        </ol>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8" style="margin: 0 auto;">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="m-0">Apply for Agency</h4>
                            </div>

                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($error); ?>
                                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($success)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($success); ?>
                                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                </div>
                            <?php endif; ?>

                            <div class="card-body">
                                <?php if (!$applicationExists): ?>
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="apply">

                                        <div class="form-group">
                                            <label for="full_name">Full Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Enter your full name" required>
                                            <small class="form-text text-muted">0 / 100</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="email">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                                            <small class="form-text text-muted">0 / 100</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="contact_phone">Contact Phone</label>
                                            <input type="text" class="form-control" id="contact_phone" name="contact_phone" placeholder="Enter your contact phone">
                                            <small class="form-text text-muted">0 / 20</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="agency_name">Agency Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="agency_name" name="agency_name" placeholder="Enter your agency name" required>
                                            <small class="form-text text-muted">0 / 50</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="region">Region</label>
                                            <select class="form-control" id="region" name="region">
                                                <option value="">Select Region</option>
                                                <option value="Bangladesh">Bangladesh</option>
                                                <option value="India">India</option>
                                                <option value="Pakistan">Pakistan</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="description">Agency Description <span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="description" name="description" placeholder="Describe your agency and why you want to become an agency" rows="5" required></textarea>
                                            <small class="form-text text-muted">0 / 500</small>
                                        </div>

                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-paper-plane"></i> Submit Application
                                            </button>
                                            <a href="../dashboard/panel.php" class="btn btn-secondary">Cancel</a>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <h5>Application Status</h5>
                                        <hr>
                                        <p><strong>Status:</strong> 
                                            <?php 
                                                $status = $userApplication->get('status');
                                                if ($status === 'pending') {
                                                    echo '<span class="badge badge-warning">Pending Review</span>';
                                                } elseif ($status === 'active' || $status === 'approved') {
                                                    echo '<span class="badge badge-success">Approved</span>';
                                                } elseif ($status === 'rejected') {
                                                    echo '<span class="badge badge-danger">Rejected</span>';
                                                } else {
                                                    echo '<span class="badge badge-secondary">' . htmlspecialchars($status) . '</span>';
                                                }
                                            ?>
                                        </p>
                                        <p><strong>Agency Name:</strong> <?php echo htmlspecialchars($userApplication->get('agencyName')); ?></p>
                                        <p><strong>Applied Date:</strong> <?php echo $userApplication->getCreatedAt()->format('Y-m-d H:i:s'); ?></p>
                                        
                                        <?php if ($status === 'rejected' && $userApplication->get('rejectionReason')): ?>
                                            <hr>
                                            <p><strong>Rejection Reason:</strong> <?php echo htmlspecialchars($userApplication->get('rejectionReason')); ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if ($status === 'approved'): ?>
                                            <hr>
                                            <p class="text-success"><strong>Congratulations!</strong> Your agency application has been approved. You are now an agency member.</p>
                                        <?php endif; ?>
                                    </div>
                                    <a href="../dashboard/panel.php" class="btn btn-secondary">Back to Dashboard</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <div class="footer-wrap">
                    <p class="mb-0">© 2024 <?php echo $app_name; ?>. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/dashboard/js/lib/jquery/jquery.min.js"></script>
    <script src="../assets/dashboard/js/lib/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
