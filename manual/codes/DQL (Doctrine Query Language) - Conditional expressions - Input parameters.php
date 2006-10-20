<?php

// POSITIONAL PARAMETERS:
$users = $conn->query("FROM User WHERE User.name = ?", array('Arnold'));

$users = $conn->query("FROM User WHERE User.id > ? AND User.name LIKE ?", array(50, 'A%'));


// NAMED PARAMETERS:

$users = $conn->query("FROM User WHERE User.name = :name", array(':name' => 'Arnold'));

$users = $conn->query("FROM User WHERE User.id > :id AND User.name LIKE :name", array(':id' => 50, ':name' => 'A%'));
?>
