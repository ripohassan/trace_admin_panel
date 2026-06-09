<?php

require '../vendor/autoload.php';
include '../Configs.php';

use Parse\ParseException;
use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseUser;

session_start();

$currUser = ParseUser::getCurrentUser();
if ($currUser) {
    $_SESSION['token'] = $currUser->getSessionToken();
} else {
    header("Refresh:0; url=../index.php");
}

$gameMessage = '';
$gameError = '';
$gameData = [];
$isEdit = false;
$objectId = '';

// Get existing game if editing
if (isset($_GET['objectId'])) {
    $objectId = trim($_GET['objectId']);
    if ($objectId !== '') {
        try {
            $query = new ParseQuery('Game');
            $game = $query->get($objectId, true);
            $gameData = [
                'objectId' => $game->getObjectId(),
                'gameId' => (string)($game->get('gameId') ?? ''),
                'name' => (string)($game->get('name') ?? ''),
                'title' => (string)($game->get('title') ?? ''),
                'ver' => (int)($game->get('ver') ?? 0),
                'full_url' => (string)($game->get('full_url') ?? ''),
                'hd_url' => (string)($game->get('hd_url') ?? ''),
                'half_url' => (string)($game->get('half_url') ?? ''),
            ];
            $isEdit = true;
        } catch (ParseException $e) {
            $gameError = 'Failed to load game: ' . $e->getMessage();
        }
    }
}

// Handle form submission (create/update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gameId = trim($_POST['game_id'] ?? '');
    $name = trim($_POST['game_name'] ?? '');
    $title = trim($_POST['game_title'] ?? '');
    $ver = (int)($_POST['game_ver'] ?? 0);
    $fullUrl = trim($_POST['full_url'] ?? '');
    $hdUrl = trim($_POST['hd_url'] ?? '');
    $halfUrl = trim($_POST['half_url'] ?? '');

    // Validate required fields
    if ($gameId === '' || $name === '' || $title === '' || $ver <= 0) {
        $gameError = 'Please provide valid game details. Game ID, Name, Title, and Version are required.';
    } elseif ($fullUrl === '' && $hdUrl === '' && $halfUrl === '') {
        $gameError = 'At least one game URL (Full, HD, or Half) is required.';
    } else {
        try {
            if ($isEdit && $objectId !== '') {
                // Update existing game
                $query = new ParseQuery('Game');
                $game = $query->get($objectId, true);
                $game->set('gameId', $gameId);
                $game->set('name', $name);
                $game->set('title', $title);
                $game->set('ver', $ver);
                if ($fullUrl !== '') $game->set('full_url', $fullUrl);
                if ($hdUrl !== '') $game->set('hd_url', $hdUrl);
                if ($halfUrl !== '') $game->set('half_url', $halfUrl);
                $game->save(true);
                $gameMessage = 'Game updated successfully.';
                $gameData = [
                    'objectId' => $game->getObjectId(),
                    'gameId' => $gameId,
                    'name' => $name,
                    'title' => $title,
                    'ver' => $ver,
                    'full_url' => $fullUrl,
                    'hd_url' => $hdUrl,
                    'half_url' => $halfUrl,
                ];
            } else {
                // Create new game
                $newGame = ParseObject::create('Game');
                $newGame->set('gameId', $gameId);
                $newGame->set('name', $name);
                $newGame->set('title', $title);
                $newGame->set('ver', $ver);
                if ($fullUrl !== '') $newGame->set('full_url', $fullUrl);
                if ($hdUrl !== '') $newGame->set('hd_url', $hdUrl);
                if ($halfUrl !== '') $newGame->set('half_url', $halfUrl);
                $newGame->save(true);
                $gameMessage = 'Game created successfully.';
                $gameData = [
                    'objectId' => $newGame->getObjectId(),
                    'gameId' => $gameId,
                    'name' => $name,
                    'title' => $title,
                    'ver' => $ver,
                    'full_url' => $fullUrl,
                    'hd_url' => $hdUrl,
                    'half_url' => $halfUrl,
                ];
            }
        } catch (ParseException $e) {
            $gameError = 'Error saving game: ' . $e->getMessage();
        }
    }
}

$pageTitle = $isEdit ? 'Edit Game' : 'Add new Game';

?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Games</a></li>
                <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row bg-white m-l-0 m-r-0 box-shadow "></div>

        <div class="row">
            <div class="col-12 col-md-10 col-xl-6" style="margin-left:auto; margin-right:auto;padding:10px 30px">
                <div class="card">
                    <div class="card-body">

                        <?php if (!empty($gameMessage)): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($gameMessage); ?></div>
                        <?php endif; ?>

                        <?php if (!empty($gameError)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($gameError); ?></div>
                        <?php endif; ?>

                        <div class="needs-validation">
                            <form class="form-valide" action="" method="post" novalidate>

                                <div class="form-group row">
                                    <label for="game_id" class="col-sm-4 col-form-label">Game ID<span class="text-danger">*</span></label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="game_id" name="game_id" 
                                            placeholder="e.g., 101" value="<?php echo htmlspecialchars($gameData['gameId'] ?? ''); ?>" required>
                                        <div class="valid-feedback">Looks good!</div>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="game_name" class="col-sm-4 col-form-label">English Name<span class="text-danger">*</span></label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="game_name" name="game_name" 
                                            placeholder="e.g., GreedyStar" value="<?php echo htmlspecialchars($gameData['name'] ?? ''); ?>" required>
                                        <div class="valid-feedback">Looks good!</div>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="game_title" class="col-sm-4 col-form-label">Chinese Title<span class="text-danger">*</span></label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="game_title" name="game_title" 
                                            placeholder="e.g., 摩天轮宇航版" value="<?php echo htmlspecialchars($gameData['title'] ?? ''); ?>" required>
                                        <div class="valid-feedback">Looks good!</div>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="game_ver" class="col-sm-4 col-form-label">Version<span class="text-danger">*</span></label>
                                    <div class="col-sm-8">
                                        <input type="number" class="form-control" id="game_ver" name="game_ver" 
                                            placeholder="e.g., 15" value="<?php echo htmlspecialchars((string)($gameData['ver'] ?? '')); ?>" required>
                                        <div class="valid-feedback">Looks good!</div>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="full_url" class="col-sm-4 col-form-label">Full Screen URL</label>
                                    <div class="col-sm-8">
                                        <input type="url" class="form-control" id="full_url" name="full_url" 
                                            placeholder="https://example.com/games/full" value="<?php echo htmlspecialchars($gameData['full_url'] ?? ''); ?>">
                                        <small class="form-text text-muted">Full screen game link</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="hd_url" class="col-sm-4 col-form-label">HD Half Screen URL</label>
                                    <div class="col-sm-8">
                                        <input type="url" class="form-control" id="hd_url" name="hd_url" 
                                            placeholder="https://example.com/games/hd" value="<?php echo htmlspecialchars($gameData['hd_url'] ?? ''); ?>">
                                        <small class="form-text text-muted">HD half screen game link</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="half_url" class="col-sm-4 col-form-label">Half Screen URL</label>
                                    <div class="col-sm-8">
                                        <input type="url" class="form-control" id="half_url" name="half_url" 
                                            placeholder="https://example.com/games/half" value="<?php echo htmlspecialchars($gameData['half_url'] ?? ''); ?>">
                                        <small class="form-text text-muted">Half screen game link (at least one URL required)</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-sm-10">
                                        <button type="submit" class="btn text-white" style="background:#5d0375;">
                                            <?php echo $isEdit ? 'Update' : 'Save'; ?>
                                        </button>
                                        <a href="../dashboard/games.php" class="btn btn-secondary">Cancel</a>
                                    </div>
                                </div>

                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('form-valide');
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
