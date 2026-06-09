<?php
require 'vendor/autoload.php';
include 'Configs.php';

$gameId = '101';
$uid = '10001';
$token = 'ABCD123';
$roomId = '100000';
$key = $_ENV['API_SIGN_KEY'] ?? 'xR26YzHB5Sm3qX2R3i676WfAoSQXkfCDha9WVqZ';

$toHash = $gameId . $uid . $token . $roomId . $key;
$sign = md5($toHash);

echo "Step 1 - Concatenate:\n";
echo "  gameId: $gameId\n";
echo "  uid: $uid\n";
echo "  token: $token\n";
echo "  roomId: $roomId\n";
echo "  key: $key\n";
echo "\n";
echo "Step 2 - Raw string to hash:\n";
echo "  $toHash\n";
echo "\n";
echo "Step 3 - MD5 hash:\n";
echo "  $sign\n";
echo "\n";
echo "Use this in Postman:\n";
echo json_encode([
    "gameId" => $gameId,
    "uid" => $uid,
    "token" => $token,
    "roomId" => $roomId,
    "sign" => $sign
], JSON_PRETTY_PRINT);
?>
