<?php
require 'vendor/autoload.php';
include 'Configs.php';
include 'DeepLinkRouter.php';

use Parse\ParseUser;

// Check for deeplink parameter first
$deeplink = $_GET['deeplink'] ?? $_GET['link'] ?? null;

// Open login.php in case current user is logged out
$currUser = ParseUser::getCurrentUser();
if ($currUser && in_array($currUser->get("role"), ['admin', 'bd'], true)) {
    // If deeplink is provided, route to it; otherwise go to dashboard
    if ($deeplink && DeepLinkRouter::exists($deeplink)) {
        DeepLinkRouter::redirect($deeplink);
    } else {
        header('Refresh:0; url=dashboard/panel.php');
    }
} else {
    header('Refresh:0; url=auth/login.php');
}

