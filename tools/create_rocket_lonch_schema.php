<?php
require __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../Configs.php';

use Parse\ParseObject;
use Parse\ParseClient;
use Parse\ParseException;

// This script creates a Parse class `rocket_lonch` with the requested columns
// Run from project root: php tools/create_rocket_lonch_schema.php

try {
    // Ensure Parse client is initialized in Configs.php as in the rest of the app

    // Create a new object to ensure class and columns are created
    $obj = ParseObject::create("rocket_lonch");

    // Set fields (types chosen from common expectations)
    $obj->set("time", new DateTime());                // Date field
    $obj->set("is_lonch", (bool)false);               // Boolean
    $obj->set("Room_id", "");                       // String
    $obj->set("Level", (int)0);                       // Number
    $obj->set("is_check", (bool)false);               // Boolean
    $obj->set("col_1", "");                         // String
    $obj->set("col_2", "");                         // String
    $obj->set("col_3", "");                         // String

    // Save the object to create schema columns on Parse server
    $obj->save(true);

    echo "rocket_lonch class created/updated and columns added. ObjectId: " . $obj->getObjectId() . PHP_EOL;
    echo "If you want to remove the created object keep note of the ObjectId above and delete it via dashboard or code." . PHP_EOL;
} catch (ParseException $ex) {
    echo "ParseException: " . $ex->getMessage() . PHP_EOL;
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
}
