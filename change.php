<?php
require 'Configs.php';
use Parse\ParseQuery;

$newPassword = "Tr@ceAdm1n!2026";

try {
    $q = new ParseQuery('_User');
    $q->equalTo('role', 'admin');
    $users = $q->find(true);

    if (count($users) > 0) {
        $count = 0;
        foreach ($users as $admin) {
            $admin->setPassword($newPassword);
            $admin->save(true);
            echo "Successfully changed password for admin username: " . $admin->get('username') . "\n";
            $count++;
        }
        echo "Total $count admin(s) updated.\n";
    } else {
        echo "Error: No admin user found in the database.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
