<?php

require '../vendor/autoload.php';
include '../Configs.php';

use Parse\ParseQuery;
use Parse\ParseUser;
use Parse\ParseException;

session_start();

$currUser = ParseUser::getCurrentUser();
if ($currUser){

    // Store current user session token, to restore in case we create new user
    $_SESSION['token'] = $currUser -> getSessionToken();
} else {

    header("Refresh:0; url=../index.php");
}

// Update data ------------------------------------------------
if(/*isset($_POST['username']) &&*/ isset($_POST['fullname'])){
    // $username = $_POST['username'];
    
    
    if($currUser->getObjectId() == "HwvtVkUbZp") {
        echo "<script>
        window.alert('You need administrator authorization to perform this action.');
        </script>";
    }else{
        
        $fullName = $_POST['fullname'];
        $gender = $_POST['gender'];
        $birthday = $_POST['birthday'];
        $isViwer = $_POST['mode'] == 1 ? true : false;
        $suspensionStatus = isset($_POST['suspension_status']) ? filter_var($_POST['suspension_status'], FILTER_VALIDATE_BOOLEAN) : false;
        // $status = $_POST['status'];
        // $account = $_POST['account'];
        
        
        $birthdayDate = new DateTime($birthday);
        
        // 4 days ago
        //$date = DateTime::createFromFormat('m/d/Y H:i:s', date("m/d/Y H:i:s"));
        
        $adObjID = $_GET['objectId'];
        
        $query = new ParseQuery("_User");
        try {
        $user = $query->get($adObjID, true);
        // The object was retrieved successfully.
        } catch (ParseException $ex) {
        // The object was not retrieved successfully.
        // error is a ParseException with an error code and message.
        }
        
        try {
        // $user->set("username", $username);
        $user->set("name", $fullName);
        $user->set("gender", $gender);
        $user->set("birthday", $birthdayDate);
        $user->set("isViewer", $isViwer);
        $user->set("activationStatus", $suspensionStatus);
        // $user->set("emailVerified", filter_var($status, FILTER_VALIDATE_BOOLEAN));
        // $user->set("activationStatus", filter_var($account, FILTER_VALIDATE_BOOLEAN));
        
        /*if ($status === "true"){
        
        
        } else {
        
        $user->set("emailVerified", false);
        }
        
        if ($account === "true"){
        
        $user->set("activationStatus", true);
        } else {
        
        $user->set("activationStatus", false);
        }*/
        $user->save(true);
        
        } catch (Exception $e) {}
    }
}

?>

<?php
// Get current User
$adObjID = $_GET['objectId'];

$query = new ParseQuery("_User");
try {
    $currUser = $query->get($adObjID, true);
    // The object was retrieved successfully.
} catch (ParseException $ex) {
    // The object was not retrieved successfully.
    // error is a ParseException with an error code and message.
}

//$currUser = ParseUser::getCurrentUser();
$cuObjectID = $currUser->getObjectId();

//Get username
$username = $currUser->get('username');

// Nome completo
$name = $currUser->get('name');

if ($name != null){

    $fullName = $name;
} else{

    $fullName = "N/A";
}

// genero
$genre = $currUser->get('gender');
$genderMap = [
    'MAL' => 'Male',
    'FML' => 'Female',
    'OTR' => 'Other',
];

$genero = null;
if ($genre !== null) {
    $genero = strtoupper(trim($genre));
    if ($genero === 'MALE') {
        $genero = 'MAL';
    } elseif ($genero === 'FEMALE') {
        $genero = 'FML';
    } elseif ($genero === 'OTHER') {
        $genero = 'OTR';
    }
    if (!isset($genderMap[$genero])) {
        $genero = null;
    }
}

$genderOptionsHtml = '<option value=""'.($genero === null ? ' selected' : '').'>Select gender</option>';
foreach ($genderMap as $code => $label) {
    $selected = ($code === $genero) ? ' selected' : '';
    $genderOptionsHtml .= '<option value="'.$code.'"'.$selected.'>'.$label.'</option>';
}

// Data de nascimento
$aniv = $currUser->get("birthday");


if ($aniv != null){

    $anivDate = date_format($aniv,"Y-m-d");

} else{

    $anivDate = "N/A";
}

// Mode
$viwer= $currUser->get('isViewer');

if ($viwer){
    $modeT1 = 1;
    $mode1 = "Viewer";
    
    $modeT2 = 0;
    $mode2 = "Challenger";
    
}else{
    $modeT1 = 0;
    $mode1 = "Challenger";
    
    $modeT2 = 1;
    $mode2 = "Viewer";
}

// genero
/*$accountStatusDisabled= $currUser->get('activationStatus');

if ($accountStatusDisabled == true){

    $status1 = "Suspended";
    $statusDisabled1 = "true";

    $status2 = "Enabled";
    $statusDisabled2 = "false";

} else {

    $status1 = "Enabled";
    $statusDisabled1 = "false";

    $status2 = "Suspended";
    $statusDisabled2 = "true";

}*/

// Account status - Suspension
$accountStatusDisabled = $currUser->get('activationStatus');

if ($accountStatusDisabled == true){
    $suspensionStatus1 = "Suspended";
    $suspensionStatusValue1 = "true";
    $suspensionStatus2 = "Active";
    $suspensionStatusValue2 = "false";
} else {
    $suspensionStatus1 = "Active";
    $suspensionStatusValue1 = "false";
    $suspensionStatus2 = "Suspended";
    $suspensionStatusValue2 = "true";
}

// status de processo

// genero
$emailVerified= $currUser->get('emailVerified');

if ($emailVerified == true){

    $email1 = "Verified";
    $emailVeri1 = "true";

    $email2 = "Unverified";
    $emailVeri2 = "false";

} else {

    $email1 = "Unverified";
    $emailVeri1 = "false";

    $email2 = "Verified";
    $emailVeri2 = "true";

}

// Show image
/*$sImage = $currUser->get('photo_verified_file');
if ($sImage != null) {
    $sImage = $currUser->get('photo_verified_file');
    $imageURL = $sImage->getURL();
}*/

$sImage = $currUser->get('avatar');
if ($sImage != null) {
    $sImage = $currUser->get('avatar');
    $imageURL = $sImage->getURL();
}

$_SESSION['name']   = $fullName;
$_SESSION['username']     = $username;
$_SESSION['gender'] = $genre;
$_SESSION['birthday']     = $anivDate;
$_SESSION['mode']     = $viwer;
$_SESSION['suspension_status'] = $accountStatusDisabled;
// $_SESSION['emailVerified']     = $status;
// $_SESSION['activationStatus']     = $account;

?>

<div class="page-wrapper">
    <!-- Bread crumb -->
    <div class="row page-titles">
        <!-- <div class="col-md-5 align-self-center">
            <h3 class="text-white">Edit Users </h3> </div> -->
        <div class="col">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Users</a></li>
                <li class="breadcrumb-item active">Edit user </li>
            </ol>
        </div>
    </div>

    <?php

    echo '
    
    <!-- Container fluid  -->
    <div class="container-fluid">
        <!-- Start Page Content -->
        <div class="row bg-white m-l-0 m-r-0 box-shadow ">
                 
            <!-- <a class="image-link" href="'.$imageURL.'"><img src="'.$imageURL.'" class="rounded img-fluid" width="100" height="100"> -->
        
        </div>
        <div class="row">
            <div class="col-12 col-md-10 col-xl-6" style="margin-left:auto; margin-right:auto;padding:10px 30px">
                <div class="card">
                    <div class="card-body">
                        <div class="form-validation">
                        <form class="form-valide" action="" method="post">
                        <div class="form-group row">
                            <label for="fullname" class="col-sm-4 col-form-label">Fullname <span class="text-danger">*</span> </label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="fullname" name="fullname" value="'.$name.'" placeholder="Por favor coloque o nome completo">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="username" class="col-sm-4 col-form-label">Username <span class="text-danger">*</span> </label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="username" name="username" value="'.$username.'" placeholder="Por favor coloque o nome de utilizador" disabled>
                            </div>
                        </div>
                        
                            <div class="form-group row">
                                <label class="col-lg-4 col-form-label" for="gender">Gender <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <select class="form-control" id="gender" name="gender">
                                        '.$genderOptionsHtml.'
                                    </select>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-4 col-form-label" for="birthday">Birthday <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <input type="date" class="form-control" id="aniversario" value="'.$anivDate.'" name="birthday" placeholder="dd/mm/yyyy">
                                </div>
                            </div>
                            
                            <div class="form-group row">
                                <label class="col-lg-4 col-form-label" for="mode">Mode <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <select class="form-control" id="mode" name="mode">
                                        <option value="'.$modeT1.'">'.$mode1.'</option>
                                        <option value="'.$modeT2.'">'.$mode2.'</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-lg-4 col-form-label" for="suspension_status">Suspension Status <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <select class="form-control" id="suspension_status" name="suspension_status">
                                        <option value="'.$suspensionStatusValue1.'">'.$suspensionStatus1.'</option>
                                        <option value="'.$suspensionStatusValue2.'">'.$suspensionStatus2.'</option>
                                    </select>
                                </div>
                            </div>
                      
                        <div class="form-group row">
                            <div class="col-sm-10">
                                <button type="submit" class="btn text-white" style="background:#5d0375;"> Save </button>
                                <a class="btn btn-inverse" href="../dashboard/all_users.php"> Back </a>

                            </div>
                        </div>
                    </form>
                </div>

            </div>
                </div>
        </div>
        </div>
        <!-- End PAge Content -->
    </div>
    <!-- End Container fluid  -->
    
    ';
    ?>

</div>

<!-- <div class="form-group row">
        <label class="col-lg-4 col-form-label" for="status">Status <span class="text-danger">*</span></label>
        <div class="col-sm-8">
            <select class="form-control" id="status" name="status">
                <option value="'.$emailVeri1.'">'.$email1.'</option>
                <option value="'.$emailVeri2.'">'.$email2.'</option>
            </select>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-lg-4 col-form-label" for="account">Account Status <span class="text-danger">*</span></label>
        <div class="col-sm-8">
            <select class="form-control" id="account" name="account">
                 <option value="'.$statusDisabled1.'">'.$status1.'</option>
                 <option value="'.$statusDisabled2.'">'.$status2.'</option>
            </select>
        </div>
    </div>
-->
