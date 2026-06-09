<?php

require '../vendor/autoload.php';
include '../Configs.php';

use Parse\ParseException;
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

// Handle delete action
if (isset($_POST['action']) && $_POST['action'] === 'delete_game') {
    $gameId = trim($_POST['game_id'] ?? '');
    if ($gameId === '') {
        $gameError = 'Invalid game selected for deletion.';
    } else {
        try {
            $query = new ParseQuery('Game');
            $game = $query->get($gameId, true);
            $game->destroy(true);
            $gameMessage = 'Game removed successfully.';
        } catch (ParseException $e) {
            $gameError = $e->getMessage();
        }
    }
}

?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Games</a></li>
                <li class="breadcrumb-item active">All Games</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row bg-white m-l-0 m-r-0 box-shadow "></div>

        <div class="row">
            <div class="col-lg">
                <div class="card">

                    <?php
                    $query = new ParseQuery('Game');
                    $matchCounter = 0;
                    try {
                        $matchCounter = $query->count();
                    } catch (Exception $e) {
                        $matchCounter = '?';
                    }

                    echo ' <h2 class="card-title">' . $matchCounter . ' Game(s) in total</h2> ';
                    ?>

                    <?php if (!empty($gameMessage)): ?>
                        <div class="alert alert-success m-3 mb-0"><?php echo htmlspecialchars($gameMessage); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($gameError)): ?>
                        <div class="alert alert-danger m-3 mb-0"><?php echo htmlspecialchars($gameError); ?></div>
                    <?php endif; ?>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="example23" class="display nowrap table table-hover table-striped table-bordered" cellspacing="0" width="100%">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="color:#65131f ;">ObjectId</th>
                                        <th style="color:#65131f ;">Game ID</th>
                                        <th style="color:#65131f ;">English Name</th>
                                        <th style="color:#65131f ;">Chinese Title</th>
                                        <th style="color:#65131f ;">Version</th>
                                        <th style="color:#65131f ;">Date</th>
                                        <th style="color:#65131f ;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <?php
                                    try {
                                        $query = new ParseQuery('Game');
                                        $query->ascending('gameId');
                                        $gameArray = $query->find(false);

                                        foreach ($gameArray as $game) {
                                            $objectId = $game->getObjectId();
                                            $gameId = $game->get('gameId') ?? '';
                                            $name = $game->get('name') ?? '';
                                            $title = $game->get('title') ?? '';
                                            $ver = (int)($game->get('ver') ?? 0);
                                            $date = $game->getCreatedAt();
                                            $created = date_format($date, 'd/m/Y');

                                            echo '
                                            <tr>
                                                <td>' . htmlspecialchars($objectId) . '</td>
                                                <td>' . htmlspecialchars($gameId) . '</td>
                                                <td>' . htmlspecialchars($name) . '</td>
                                                <td>' . htmlspecialchars($title) . '</td>
                                                <td>' . htmlspecialchars((string)$ver) . '</td>
                                                <td>' . htmlspecialchars($created) . '</td>
                                                <td>
                                                    <a href="../dashboard/add_game.php?objectId=' . urlencode($objectId) . '" class="btn btn-sm btn-warning">
                                                        <i class="fa fa-edit"></i> Edit
                                                    </a>
                                                    <form method="post" action="" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to remove this game?\')">
                                                        <input type="hidden" name="action" value="delete_game">
                                                        <input type="hidden" name="game_id" value="' . htmlspecialchars($objectId, ENT_QUOTES, 'UTF-8') . '">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="fa fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            ';
                                        }
                                    } catch (ParseException $e) {
                                        echo '<tr><td colspan="7" class="text-danger">' . htmlspecialchars($e->getMessage()) . '</td></tr>';
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
