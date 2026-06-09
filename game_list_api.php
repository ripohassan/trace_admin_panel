<?php
/**
 * Game List API
 *
 * Returns all available games with their details
 *
 * Response:
 * [
 *   {
 *     "gameId": "101",
 *     "name": "GreedyStar",
 *     "title": "摩天轮宇航版",
 *     "ver": 15,
 *     "half_url": "https://xxx.quantum-nexus.net/games/greedy_star_half/index.html?pl=pikilive&v=15",
 *     "full_url": "https://xxx.quantum-nexus.net/games/greedy_star/index.html?pl=pikilive&v=15",
 *     "hd_url": "https://xxx.quantum-nexus.net/games/greedy_star_medium/index.html?pl=pikilive&v=15"
 *   }
 * ]
 */

// Disable SSL verification for development
stream_context_set_default([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
    ]
]);

require 'vendor/autoload.php';
include 'Configs.php';

use Parse\ParseQuery;
use Parse\ParseException;

header('Content-Type: application/json');

try {
    // Query all games from Parse
    $query = new ParseQuery('Game');
    $query->ascending('gameId'); // Sort by gameId
    $games = $query->find();
    
    $gamesList = [];
    
    foreach ($games as $game) {
        $gameData = [
            'gameId' => (string)$game->get('gameId'),
            'name' => (string)($game->get('name') ?? ''),
            'title' => (string)($game->get('title') ?? ''),
            'ver' => (int)($game->get('ver') ?? 0),
        ];
        
        // Add optional URL fields if they exist
        if ($game->get('full_url')) {
            $gameData['full_url'] = (string)$game->get('full_url');
        }
        if ($game->get('hd_url')) {
            $gameData['hd_url'] = (string)$game->get('hd_url');
        }
        if ($game->get('half_url')) {
            $gameData['half_url'] = (string)$game->get('half_url');
        }
        
        $gamesList[] = $gameData;
    }
    
    http_response_code(200);
    echo json_encode($gamesList);
    
} catch (ParseException $e) {
    http_response_code(500);
    echo json_encode([
        'errorCode' => 4000,
        'errorMessage' => 'Network timeout',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'errorCode' => 4000,
        'errorMessage' => 'Network timeout',
        'details' => $e->getMessage()
    ]);
}
