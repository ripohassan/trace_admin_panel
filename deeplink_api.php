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
            
        case 'rooms':
            // Return room-related deeplinks
            $organized = DeepLinkRouter::getOrganized();
            $room_links = [];
            if (isset($organized['room_management']['links'])) {
                foreach ($organized['room_management']['links'] as $name => $data) {
                    $room_links[$name] = [
                        'label' => $data['label'],
                        'path' => $data['path'],
                        'icon' => $data['icon'],
                        'url' => DeepLinkRouter::generateUrl($name),
                        'deeplink' => 'index.php?deeplink=' . $name
                    ];
                }
            }
            echo json_encode([
                'status' => 'success',
                'category' => 'Room Management',
                'total' => count($room_links),
                'deeplinks' => $room_links
            ]);
            break;
            
        case 'category':
            // Get deeplinks for a specific category
            $category_name = $_GET['name'] ?? null;
            if (!$category_name) {
                http_response_code(400);
                echo json_encode(['error' => 'category name parameter required']);
                exit;
            }
            
            $organized = DeepLinkRouter::getOrganized();
            if (!isset($organized[$category_name])) {
                http_response_code(404);
                echo json_encode(['error' => "Category '{$category_name}' not found"]);
                exit;
            }
            
            $category = $organized[$category_name];
            $category_links = [];
            if (isset($category['links'])) {
                foreach ($category['links'] as $name => $data) {
                    $category_links[$name] = [
                        'label' => $data['label'],
                        'path' => $data['path'],
                        'icon' => $data['icon'],
                        'url' => DeepLinkRouter::generateUrl($name)
                    ];
                }
            }
            echo json_encode([
                'status' => 'success',
                'category' => $category['label'],
                'total' => count($category_links),
                'deeplinks' => $category_links
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'error' => 'Unknown action',
                'available_actions' => ['list', 'organized', 'rooms', 'category', 'url', 'check', 'detailed']
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
