<?php

require '../vendor/autoload.php';
include '../Configs.php';

use Parse\ParseFile;
use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseUser;


session_start();

$categories = [];
try {
    $categoryQuery = new ParseQuery('GiftCategory');
    $categoryQuery->ascending('name');
    $categoryResults = $categoryQuery->find(true);
    foreach ($categoryResults as $categoryObj) {
        $categoryName = trim((string)($categoryObj->get('name') ?? ''));
        $categoryCode = trim((string)($categoryObj->get('code') ?? ''));
        if ($categoryCode === '' && $categoryName !== '') {
            $categoryCode = strtolower(preg_replace('/[^a-z0-9_]+/', '_', $categoryName));
            $categoryCode = trim($categoryCode, '_');
        }
        if ($categoryName !== '' && $categoryCode !== '') {
            $categories[$categoryCode] = $categoryName;
        }
    }
} catch (Exception $e) {
    $categories = [];
}

if (empty($categories)) {
    $categories = [
        'love' => 'Love',
        'moods' => 'Moods',
        'artists' => 'Artists',
        'collectibles' => 'Collectibles',
        'games' => 'Games',
        'family' => 'Family',
        'classic' => 'Classic',
        '3d' => '3D',
        'vip' => 'VIP',
        'country' => 'Country',
        'festival' => 'Festival',
        'trending' => 'Trending',
    ];
}

$currUser = ParseUser::getCurrentUser();
if ($currUser){
    // Store current user session token, to restore in case we create new user
    $_SESSION['token'] = $currUser -> getSessionToken();
} else {

    header("Refresh:0; url=../index.php");
}

// SIGN UP ------------------------------------------------
if(isset($_POST['val-name']) && isset($_POST['val-credits']) && isset($_FILES['val-file'])){
    
    if($currUser->getObjectId() == "HwvtVkUbZp") {
        echo "<script>
        window.alert('You need administrator authorization to perform this action.');
        </script>";
    }else{
    
        $name = $_POST['val-name'];
        $category = $_POST['val-category'];
        $credits = $_POST['val-credits'];
        $isFream = isset($_POST['val-is-fream']) ? (int)$_POST['val-is-fream'] : 0;
        $filePath = $_FILES["val-file"]["tmp_name"] ?? '';
        $fileName = $_FILES['val-file']['name'];
        $safeBaseName = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($fileName, PATHINFO_FILENAME));
        $safeExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $safeFileName = $safeBaseName ?: 'gift_file';
        if ($safeExtension !== '') {
            $safeFileName .= '.' . $safeExtension;
        }
    
        $newGift = ParseObject::create("Gifts");
    
        $newGift->set("name", $name);
        $newGift->set("categories",$category);
        $newGift->set("coins", (int)$credits);
        $newGift->set("is_fream", $isFream === 1 ? 1 : 0);
        if ($filePath && $fileName && is_uploaded_file($filePath)) {
            $newGift->set("file", ParseFile::createFromFile($filePath, $safeFileName));
        }
    
        $newGift->save(true);
        
    }

}

?>

<div class="page-wrapper">
    <!-- Bread crumb -->
    <div class="row page-titles">
        <!-- <div class="col-md-5 align-self-center">
            <h3 class="text-white">Gift </h3> </div> -->
        <div class="col">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Features</a></li>
                <li class="breadcrumb-item active">Add new gift </li>
            </ol>
        </div>
    </div>

    <!-- Container fluid  -->
    <div class="container-fluid">
        <!-- Start Page Content -->
        <div class="row bg-white m-l-0 m-r-0 box-shadow ">

        </div>
        <div class="row">
            <div class="col-12 col-md-10 col-xl-6" style="margin-left:auto; margin-right:auto;padding:10px 30px">
                <div class="card">
                    <div class="card-body">
                        <div class="needs-validation">
                        <form class="form-valide" enctype="multipart/form-data" action="" method="post" novalidate>
                        <div class="form-group row">
                            <label for="val-name" class="col-sm-4 col-form-label">Gift Name<span class="text-danger">*</span> </label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="val-name" name="val-name" placeholder="Give a name to the gift" required>
                                <div class="valid-feedback">Looks good!</div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="val-category" class="col-sm-4 col-form-label">Category<span class="text-danger">*</span></label>
                            <div class="col-sm-8">
                                <select class="form-control" id="val-category" name="val-category" required>
                                    <?php foreach($categories as $code => $label){
                                        echo '<option value="'.htmlspecialchars($code, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</option>';
                                    }?>
                                </select>
                                <div class="valid-feedback">Looks good!</div>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="val-credits" class="col-sm-4 col-form-label">Credits<span class="text-danger">*</span></label>
                            <div class="col-sm-8">
                                <input type="number" class="form-control" id="val-credits" name="val-credits" placeholder="Credits needed to send the gift" required>
                                <div class="valid-feedback">Looks good!</div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="val-is-fream" class="col-sm-4 col-form-label">Type<span class="text-danger">*</span></label>
                            <div class="col-sm-8">
                                <select class="form-control" id="val-is-fream" name="val-is-fream" required>
                                    <option value="0">Gift</option>
                                    <option value="1">Frame</option>
                                </select>
                                <div class="valid-feedback">Looks good!</div>
                            </div>
                        </div>

                            <div class="form-group row">
                                <label for="val-file" class="col-sm-4 col-form-label">Gift File / Photo<span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <input id="val-file" name="val-file" type="file" accept="application/json,image/png,image/jpeg,image/webp,image/gif" required />
                                    <div class="invalid-feedback">Please choose a JSON or image file for this gift.</div>
                                    <div class="valid-feedback">Looks good!</div>
                                </div>
                            </div>

                        <div class="form-group row">
                            <div class="col-sm-10">
                                <button type="submit" class="btn text-white" style="background:#5d0375;"> Save</button>
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
</div>

<script>
    // Example starter JavaScript for disabling form submissions if there are invalid fields
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.getElementsByClassName('form-valide');
            // Loop over them and prevent submission
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
</script>


