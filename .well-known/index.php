<?php
/**
 * Well-Known Assets Handler
 * Serves .well-known assets from the tools directory
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$requested_file = basename($_SERVER['REQUEST_URI']);

// Map of well-known files to their source locations
$file_mappings = [
    'assetlinks.json' => '../tools/assetlinks.json'
];

if (isset($file_mappings[$requested_file])) {
    $source_file = __DIR__ . '/' . $file_mappings[$requested_file];
    
    if (file_exists($source_file)) {
        readfile($source_file);
        http_response_code(200);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'File not found']);
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}
?>
