<?php

require '../vendor/autoload.php';
include '../Configs.php';

use Parse\ParseFile;
use Parse\ParseObject;
use Parse\ParseUser;


session_start();

$createAvatarFrameError = '';
$createAvatarFrameSuccess = '';

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
        $name = trim($_POST['val-name']);
        $credits = (int)($_POST['val-credits'] ?? 0);
        $filePath = $_FILES['val-file']['tmp_name'] ?? '';
        $fileName = $_FILES['val-file']['name'] ?? '';
        $fileError = $_FILES['val-file']['error'] ?? UPLOAD_ERR_NO_FILE;
        $detectedMimeType = '';

        $safeBaseName = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($fileName, PATHINFO_FILENAME));
        $safeExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'svga'];
        $allowedMimeTypes = ['image/png', 'image/jpeg', 'image/pjpeg', 'image/jpg', 'image/jfif', 'image/x-png', 'image/x-jpeg', 'application/octet-stream', 'application/x-svga', 'application/vnd.svga', 'image/svga', 'application/zip', 'application/x-zip-compressed'];

        if ($name === '' || $credits <= 0) {
            $createAvatarFrameError = 'Please provide a valid name and credits amount.';
        } elseif ($fileError !== UPLOAD_ERR_OK || !$filePath || !is_uploaded_file($filePath)) {
            $createAvatarFrameError = 'Please upload a valid PNG, JPG, or SVGA file.';
        } elseif (!in_array($safeExtension, $allowedExtensions, true)) {
            $createAvatarFrameError = 'Invalid file extension. Only PNG, JPG, and SVGA files are allowed.';
        } else {
            if ($safeExtension !== 'svga') {
                $imageType = 0;

                if (function_exists('exif_imagetype')) {
                    $imageType = (int) @exif_imagetype($filePath);
                }

                if ($imageType === 0 && function_exists('getimagesize')) {
                    $imageInfo = @getimagesize($filePath);
                    if (is_array($imageInfo)) {
                        $imageType = (int) ($imageInfo[2] ?? 0);
                        if (!empty($imageInfo['mime'])) {
                            $detectedMimeType = (string) $imageInfo['mime'];
                        }
                    }
                }

                if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    if ($finfo !== false) {
                        $detectedMimeType = (string) finfo_file($finfo, $filePath);
                        finfo_close($finfo);
                    }
                }

                if ($detectedMimeType === '') {
                    $detectedMimeType = (string)($_FILES['val-file']['type'] ?? '');
                }

                $isAllowedByImageType = in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG], true);
                $isAllowedByMime = in_array(strtolower($detectedMimeType), $allowedMimeTypes, true)
                    || preg_match('/^image\/(jpeg|jpg|pjpeg|png|x-png|x-jpeg|jfif)$/i', $detectedMimeType) === 1;

                if (!$isAllowedByImageType && !$isAllowedByMime) {
                    $createAvatarFrameError = 'Invalid file type. Only PNG, JPG, and SVGA files are allowed.';
                }
            }
        }

        if ($createAvatarFrameError === '') {
            $isWorking = isset($_POST['val-working']) ? true : false;
            $period = 15;

            $safeFileName = $safeBaseName ?: 'avatar_frame';
            $normalizedExtension = $safeExtension === 'jpeg' ? 'jpg' : $safeExtension;
            if ($normalizedExtension !== '') {
                $safeFileName .= '.' . $normalizedExtension;
            }

            $newGift = ParseObject::create('Gifts');

            $newGift->set('name', $name);
            $newGift->set('categories', 'avatar_frame');
            $newGift->set('coins', $credits);
            $newGift->set('period', (int)$period);
            $newGift->set('isWorking', $isWorking);
            $newGift->set('file', ParseFile::createFromFile($filePath, $safeFileName));

            try {
                $newGift->save(true);
                $createAvatarFrameSuccess = 'Avatar Frame created successfully.';
            } catch (Exception $e) {
                $createAvatarFrameError = $e->getMessage();
            }
        }
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
                <li class="breadcrumb-item active">Add new Avatar Frame </li>
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
                        <?php if ($createAvatarFrameError !== ''): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($createAvatarFrameError); ?></div>
                        <?php endif; ?>

                        <?php if ($createAvatarFrameSuccess !== ''): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($createAvatarFrameSuccess); ?></div>
                        <?php endif; ?>

                        <div class="needs-validation">
                        <form class="form-valide" enctype="multipart/form-data" action="" method="post" novalidate>
                        <div class="form-group row">
                            <label for="val-name" class="col-sm-4 col-form-label">Avatar Frame Name<span class="text-danger">*</span> </label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="val-name" name="val-name" placeholder="Give a name to Avatar Frame" required>
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
                            <label for="val-working" class="col-sm-4 col-form-label">Working</label>
                            <div class="col-sm-8" style="padding-top: 8px;">
                                <input id="val-working" name="val-working" type="checkbox" checked>
                                <span style="margin-left:8px;">Enable this frame</span>
                            </div>
                        </div>

                            <div class="form-group row">
                                <label for="val-file" class="col-sm-4 col-form-label">Image / SVGA file (PNG/JPG/SVGA)<span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <input id="val-file" name="val-file" type="file" accept="image/png, image/jpeg, .png, .jpg, .jpeg, .svga" required />
                                    <div class="invalid-feedback">Please choose your Avatar Frame file (PNG/JPG/SVGA).</div>
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


