<?php
/**
 * Deeplink API Endpoint
 * 
 * Usage:
 * - GET /deeplink_api.php?action=list - List all deeplinks
 * - GET /deeplink_api.php?action=organized - List deeplinks by category
 * - GET /deeplink_api.php?action=url&deeplink=users - Get URL for deeplink
 * - GET /deeplink_api.php?action=check&deeplink=users - Check if deeplink exists
 */

require 'vendor/autoload.php';
include 'Configs.php';
include 'DeepLinkRouter.php';

// Initialize the router
DeepLinkRouter::init();

use Parse\ParseUser;

header('Content-Type: application/json');

// Check if user is logged in (optional - comment out for public access)
// $currUser = ParseUser::getCurrentUser();
// if (!$currUser) {
//     http_response_code(401);
//     echo json_encode(['error' => 'Unauthorized']);
//     exit;
// }

$action = $_GET['action'] ?? 'organized';
$deeplink = $_GET['deeplink'] ?? null;

try {
    switch ($action) {
        case 'list':
            // Return all available deeplinks (flat list)
            $deeplinks = DeepLinkRouter::getAll();
            echo json_encode([
                'status' => 'success',
                'total' => count($deeplinks),
                'deeplinks' => array_keys($deeplinks),
                'format' => 'index.php?deeplink=<name>'
            ]);
            break;
            
        case 'organized':
            // Return deeplinks organized by category with metadata
            $organized = DeepLinkRouter::getOrganized();
            $total = 0;
            foreach ($organized as $category) {
                if (isset($category['links'])) {
                    $total += count($category['links']);
                }
            }
            echo json_encode([
                'status' => 'success',
                'total' => $total,
                'deeplinks' => $organized
            ]);
            break;
            
        case 'url':
            if (!$deeplink) {
                http_response_code(400);
                echo json_encode(['error' => 'deeplink parameter required']);
                exit;
            }
            
            if (DeepLinkRouter::exists($deeplink)) {
                $url = DeepLinkRouter::generateUrl($deeplink);
                echo json_encode([
                    'status' => 'success',
                    'deeplink' => $deeplink,
                    'url' => $url
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => "Deeplink '{$deeplink}' not found"]);
            }
            break;
            
        case 'check':
            if (!$deeplink) {
                http_response_code(400);
                echo json_encode(['error' => 'deeplink parameter required']);
                exit;
            }
            
            $exists = DeepLinkRouter::exists($deeplink);
            echo json_encode([
                'status' => 'success',
                'deeplink' => $deeplink,
                'exists' => $exists
            ]);
            break;
            
        case 'detailed':
            // Return detailed deeplink information
            $all = DeepLinkRouter::getAll();
            $detailed = [];
            foreach ($all as $name => $path) {
                $detailed[] = [
                    'name' => $name,
                    'path' => $path,
                    'url' => DeepLinkRouter::generateUrl($name),
                    'example' => 'index.php?deeplink=' . $name
                ];
            }
            echo json_encode([
                'status' => 'success',
                'total' => count($detailed),
                'deeplinks' => $detailed
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'error' => 'Unknown action',
                'available_actions' => ['list', 'organized', 'url', 'check', 'detailed']
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
?>
